<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-08-26 19:19:41 +0200 (Mi, 26 Aug 2009) $
	 * @package			poll
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/poll.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Poll {
		private static $instance = null;
	
		/**
		 * @desc			Singleton method - instantiates the class or returns an existing instance
		 * @author			Scott Luther
		 * @since			3.1
		 * 
		 * @ignore
		 */
		
		public static function instance() {
			if(!isset(self::$instance)) {
				$class = __CLASS__;
				self::$instance = new $class;
			}
			return self::$instance;
		}
		
		/**
		 * @desc			Inits the class, setting up vars
		 * @param	object	$config object containing config
		 * @return	object	instance of class
		 * @author			Scott Luther
		 * @since			3.1
		 * 
		 * @ignore
		 */
		
		public function init($config) {
			return self::$instance;
		}
		
		/**
		 * @desc			Loads and checks different vars when class is initiating
		 * @author			Matthias Reuter
		 * @since			2.0
		 * @ignore
		 */
		private function __construct(){
		}
		/**
		 * @desc			Returns whether a member has voted in the poll in a topic.
		 * @param	int		$topicID Topic ID of the Poll
		 * @param	int		$memberID If $memberID is ommitted the last known member is used.
		 * @return	mixed	Poll Vote Date if voted, false otherwise
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->voted(55,77);
		 * </code>
		 * @since			2.0
		 */
		public function voted($topicID, $memberID = false){
			if(!$memberID){
				$memberID = Ipbwi::instance()->member->myInfo['member_id'];
			}
			Ipbwi_IpsWrapper::instance()->DB->query('SELECT vote_date FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'voters WHERE tid="'.$topicID.'" AND member_id="'.$memberID.'"');
			if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
				return $row['vote_date'];
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns information on a poll.
		 * @param	int		$topicID Topic ID of the Poll
		 * @return	array	Poll Information
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->info(55);
		 * </code>
		 * @since			2.0
		 */
		public function info($topicID){
			if($cache = Ipbwi::instance()->cache->get('pollInfo', $topicID)){
				return $cache;
			}else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT p.pid, p.tid, p.start_date, p.choices, p.starter_id, m.name AS starter_name, p.votes, p.forum_id, p.poll_question FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls p LEFT JOIN '.Ipbwi::instance()->board['sql_tbl_prefix'].'members m ON (p.starter_id=m.id) WHERE p.tid="'.$topicID.'"');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					$choices = unserialize(stripslashes($row['choices']));
					$row['choices'] = array();
					// Make choices more readable... mainly for b/w compat
					foreach($choices as $k => $i){
						$row['choices'][$k]['question'] = $i['question'];
						$row['choices'][$k]['multi'] = $i['multi'];
						foreach($i['choice'] as $c => $d){
							$row['choices'][$k][$c] = array('option_id' => $c,
								'option_title' => $d,
								'votes' => $i['votes'][$c],
								'percentage' => array_sum($i['votes']) ? intval(($i['votes'][$c] / array_sum($i['votes'])) * 100) : '0',
							);
						}
					}
					// I think leaving this as 'poll_question' is silly...
					$row['title'] = $row['poll_question'];
					Ipbwi::instance()->cache->save('pollInfo', $topicID, $row);
					return $row;
				}else{
					return false;
				}
			}
		}
		/**
		 * @desc			Returns total number of votes in a poll.
		 * @param	int		$topicID Topic ID of the Poll
		 * @return	int		Poll Votes
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->totalVotes(55);
		 * </code>
		 * @since			2.0
		 */
		public function totalVotes($topicID){
			if($info = $this->info($topicID)){
				return $info['votes'];
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns Topic ID associated with Poll ID.
		 * @param	int		$pollID Poll ID of the Poll
		 * @return	int		Topic ID associated with Poll ID
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->id2topicid(55);
		 * </code>
		 * @since			2.0
		 */
		public function id2topicid($pollID){
			if(is_array($pollID)){
				$topics = array();
				foreach($pollID as $i => $j){
					Ipbwi_IpsWrapper::instance()->DB->query('SELECT tid FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE pid="'.$j.'" LIMIT 1');
					if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
						$topics[$i] = $row['tid'];
					}else{
						$topics[$i] = false;
					}
				}
				return $topics;
			}else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT tid FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE pid="'.$pollID.'" LIMIT 1');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					return $row['tid'];
				}else{
					return false;
				}
			}
		}
		/**
		 * @desc			Casts a vote in a poll.
		 * @param	int		$topicID Topic ID of the Poll
		 * @param	array	$optionid In format 'question number' => 'option'
		 * @param	int		$userID If no UserID is specified, the currently logged in user will vote
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->vote(55,'1'=>'2');
		 * $ipbwi->poll->vote(77,'1'=>'3',55);
		 * </code>
		 * @since			2.0
		 */
		public function vote($topicID, $optionid = array('1'=>''), $userID = false){
			if(!Ipbwi::instance()->member->isLoggedIn() && empty($userID)){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('membersOnly'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
			if(empty($userID) && isset(Ipbwi::instance()->member->myInfo['member_id'])){
				$userID = Ipbwi::instance()->member->myInfo['member_id'];
			}elseif(empty($userID) && empty(Ipbwi::instance()->member->myInfo['member_id'])){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('membersOnly'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
			if(!Ipbwi::instance()->permissions->has('g_vote_polls',$userID)){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('noPerms'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
			if(!is_array($optionid)){
				$optionid = array("1" => $optionid);
			}
			if($this->voted($topicID)){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollAlreadyVoted'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}else{
				// Insert Vote into Database
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE tid="'.$topicID.'"');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					$choices = unserialize(stripslashes($row['choices']));
					foreach($optionid as $q => $o){
						if(!isset($choices[$q])){
							Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollInvalidVote'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
							return false;
						}
						// cound single votes (radio)
						if(!is_array($o) && (int)$o > 0){
							if(!isset($choices[$q]['choice'][$o])){
								Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollInvalidVote'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
								return false;
							}
							++$choices[$q]['votes'][$o];
						// count multi votes (checkboxes)
						}elseif(is_array($o) && count($o) > 0){
							foreach($o as $s => $t){
								if(!isset($choices[$q]['choice'][$s])){
									Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollInvalidVote'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
									return false;
								}
								++$choices[$q]['votes'][$s];
							}
						}
					}
					$choices = addslashes(serialize($choices));
					Ipbwi_IpsWrapper::instance()->DB->query('UPDATE '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls SET choices="'.$choices.'", votes=votes+1 WHERE tid="'.$topicID.'"');
					Ipbwi_IpsWrapper::instance()->DB->query('INSERT INTO '.Ipbwi::instance()->board['sql_tbl_prefix'].'voters (ip_address, vote_date, tid, member_id, forum_id) VALUES ("'.$_SERVER['REMOTE_ADDR'].'", "'.time().'", "'.$row['tid'].'", "'.$userID.'", "'.$row['forum_id'].'")');
					return true;
				}else{
					Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollNotExist'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
					return false;
				}
			}
		}
		/**
		 * @desc			Casts a null vote in a poll.
		 * @param	int		$topicID Topic ID of the Poll
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->vote(55);
		 * </code>
		 * @since			2.0
		 */
		public function nullVote($topicID){
			// No Guests Please
			if(!Ipbwi::instance()->member->isLoggedIn()){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('membersOnly'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
			if(!Ipbwi::instance()->permissions->has('g_vote_polls')){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('noPerms'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
			if($this->voted($topicID)){
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollAlreadyVoted'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}else{
				// Insert Vote into Database
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE tid="'.$topicID.'"');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					Ipbwi_IpsWrapper::instance()->DB->query('INSERT INTO '.Ipbwi::instance()->board['sql_tbl_prefix'].'voters (ip_address, vote_date, tid, member_id, forum_id) VALUES ("'.$_SERVER['REMOTE_ADDR'].'", "'.time().'", "'.$row['tid'].'", "'.Ipbwi::instance()->member->myInfo['member_id'].'", "'.$row['forum_id'].'")');
					return false;
				}else{
					Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('pollNotExist'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
					return false;
				}
			}
		}
		/**
		 * @desc			Creates a new poll.
		 * @param	int		$topicID Topic ID of the Poll
		 * @param	array	$question Questions.
		 * @param	array	$choices The options to vote for for each question
		 * @param	string	$title The title of the poll
		 * @param	bool	$pollOnly Make the topic a poll only
		 * @param	array	$multi To define questions as multiplechoice, declare an array via poll_multi with question-id as array-key and 1 or 0 as array-value. 1 = multiplechoice/checkbox, 0 = singlechoice/radio-button
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->create(55,array('1' => 'Do you think IPBWI is useful?'),'1' => array('yes', 'no'),'Your opinion about IPBWI.');
		 * </code>
		 * @since			2.0
		 */
		public function create($topicID, $questions = array(), $choices = array(), $title='',$pollOnly=false,$multi=array()){
			// Check if we can do polls
			if(Ipbwi::instance()->permissions->has('g_post_polls')){
				// Check we have a good number of choices :)
				if(!is_array($questions) && strlen($questions) > 0){
					$questions = array($questions);
				}
				if(is_array($questions) AND count($questions) > 0 AND count($questions) <= Ipbwi_IpsWrapper::instance()->vars['max_poll_questions']){
					$title = ($title=='') ? $questions[0] : $title;
					// Some last-minute checks...
					if(count($choices) > count($questions)){
						$choices = array(0 => $choices);
					}
					$thelot = array();
					$count = 1;
					// Check our Topic exists
					if(!$topicinfo = Ipbwi::instance()->topic->info(intval($topicID))){
						Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('topicNotExist'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
						return false;
					}
					foreach($questions as $k => $v){
						if(is_array($choices[$k]) AND count($choices[$k]) > 1 AND count($choices[$k]) <= Ipbwi_IpsWrapper::instance()->vars['max_poll_choices']){
							if(is_array($multi) && isset($multi[$k])){
								$is_multi = $multi[$k];
							}else{
								$is_multi = 0;
							}
							$thechoices = array(); // Init
							$choicecount = '1';
							foreach($choices[$k] as $i){
								$thechoices[$choicecount] = Ipbwi::instance()->makeSafe($i);
								$thevotes[$choicecount] = 0;
								$choicecount++;
							}
							$thelot[$count] = array('question' => $v,'multi' => $is_multi,'choice' => $thechoices, 'votes' => $thevotes);
							$count++;
						}
						else {
							Ipbwi::instance()->addSystemMessage('Error',sprintf(Ipbwi::instance()->getLibLang('pollInvalidOpts'), Ipbwi_IpsWrapper::instance()->vars['max_poll_choices']),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
							return false;
						}
					}
					// Now add it into the polls table
					Ipbwi_IpsWrapper::instance()->DB->query('INSERT INTO '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls VALUES ("", "'.intval($topicID).'", "'.time().'", "'.serialize($thelot).'", "'.Ipbwi::instance()->member->myInfo['member_id'].'", "0", "'.$topicinfo['forum_id'].'","'.Ipbwi::instance()->makeSafe($title).'","'.intval($pollOnly).'")');
					// And change the topic's poll status to open
					Ipbwi_IpsWrapper::instance()->DB->query('UPDATE '.Ipbwi::instance()->board['sql_tbl_prefix'].'topics SET poll_state="open" WHERE tid="'.intval($topicID).'"');
					return true;
				}else{
					Ipbwi::instance()->addSystemMessage('Error',sprintf(Ipbwi::instance()->getLibLang('pollInvalidQuestions'), Ipbwi_IpsWrapper::instance()->vars['max_poll_questions']),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
					return false;
				}
			}else{
				Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('noPerms'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
				return false;
			}
		}
		/**
		 * @desc			Deletes Topic-Poll
		 * @param	int		$pollID ID of the Poll
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->poll->delete(55);
		 * </code>
		 * @since			2.0
		 */
		public function delete($pollID){
			Ipbwi_IpsWrapper::instance()->DB->query('DELETE FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE pid = "'.intval($pollID).'"');
			// Update the Topic
			if(Ipbwi_IpsWrapper::instance()->DB->query('UPDATE '.Ipbwi::instance()->board['sql_tbl_prefix'].'topics SET poll_state="0",last_vote="0",total_votes="0" WHERE tid="'.$this->id2topicid($pollID).'"')){
				return true;
			}else{
				return false;
			}
		}
	}
?>