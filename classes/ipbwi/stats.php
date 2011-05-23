<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-08-26 19:19:41 +0200 (Mi, 26 Aug 2009) $
	 * @package			stats
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/stats.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Stats {
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
			// checks if the current user is logged in
			if(Ipbwi_IpsWrapper::instance()->loggedIn == 0){
				$this->loggedIn = false;
			}else{
				$this->loggedIn = true;
			}
			
			$this->myInfo = Ipbwi_IpsWrapper::instance()->myInfo();
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
		 * @desc			Gets board statistics.
		 * @return	array	Board Statistics
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->stats->board();
		 * </code>
		 * @since			2.0
		 */
		public function board(){
			// Check for cache
			if($cache = Ipbwi::instance()->cache->get('statsBoard', '1')){
				return $cache;
			}else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT cs_value FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'cache_store WHERE cs_key = "stats"');
				$row = Ipbwi_IpsWrapper::instance()->DB->fetch();
				$stats = unserialize(stripslashes($row['cs_value']));
				Ipbwi::instance()->cache->save('statsBoard', 1, $stats);
				return $stats;
			}
		}
		/**
		 * @desc			Returns the active user count.
		 * @return	array	Active User Count
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->stats->activeCount();
		 * </code>
		 * @since			2.01
		 */
		 function activeCount() {
			if($cache = Ipbwi::instance()->cache->get('activeCount', '1')){
				return $cache;
			}else{
				// Init
				$count = array('total' => '0', 'anon' => '0', 'guests' => '0', 'members' => '0');
				$cutoff = Ipbwi_IpsWrapper::instance()->vars['au_cutoff'] ? Ipbwi_IpsWrapper::instance()->vars['au_cutoff'] : '15';
				$timecutoff = time() - ($cutoff * 60);
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT member_id, login_type FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'sessions WHERE running_time > "'.$timecutoff.'"');
				// Let's cache so we don't screw ourselves over :)
				$cached = array();
				// We need to make sure our man's in this count...
				if(Ipbwi::instance()->member->isLoggedIn()){
					if(substr(Ipbwi::instance()->member->myInfo['login_anonymous'],0, 1) == '1'){
						++$count['anon'];
					}else{
						++$count['members'];
					}
					$cached[Ipbwi::instance()->member->myInfo['member_id']] = 1;
				}
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					// Add up members
					if($row['login_type'] == '1' && !array_key_exists($row['member_id'],$cached)){
						++$count['anon'];
						$cached[$row['member_id']] = 1;
					}elseif($row['member_id'] == '0'){
						++$count['guests'];
					}elseif(!array_key_exists($row['member_id'],$cached)){
						++$count['members'];
						$cached[$row['member_id']] = 1;
					}
				}
				$count['total'] = $count['anon'] + $count['guests'] + $count['members'];
				Ipbwi::instance()->cache->save('activeCount', 'detail', $count);
				return $count;
			}
		}
		/**
		 * @desc			Returns members born on the given day of a month.
		 * @param	int		$day Optional. Current day is used if left as an empty string or zero.
		 * @param	int		$month Optional. Current month is used if left as an empty string or zero.
		 * @return	array	Birthday Members
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->stats->birthdayMembers();
		 * $ipbwi->stats->birthdayMembers(22,7);
		 * </code>
		 * @since			2.01
		 */
		function birthdayMembers($day = 0, $month = 0) {
			if((int)$day<=0){
				$day = date('j');
			}
			if((int)$month<=0){
				$month = date ('n');
			}
			Ipbwi_IpsWrapper::instance()->DB->query('SELECT m.*, me.signature, me.avatar_size, me.avatar_location, me.avatar_type, me.vdirs, me.location, me.msnname, me.interests, me.yahoo, me.website, me.aim_name, me.icq_number, g.*, cf.* FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'members m LEFT JOIN '.Ipbwi::instance()->board['sql_tbl_prefix'].'groups g ON (m.mgroup=g.g_id) LEFT JOIN '.Ipbwi::instance()->board['sql_tbl_prefix'].'pfields_content cf ON (cf.member_id=m.id) LEFT JOIN '.Ipbwi::instance()->board['sql_tbl_prefix'].'member_extra me ON (m.id=me.id) WHERE m.bday_day="'.intval($day).'" AND m.bday_month="'.intval($month).'"');
			$return = array();
			$thisyear = date ('Y');
			while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
				$row['age'] = $thisyear - $row['bday_year'];
				$return[] = $row;
			}
			return $return;
		}
	}
?>