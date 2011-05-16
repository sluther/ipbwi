<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-08-26 19:19:41 +0200 (Mi, 26 Aug 2009) $
	 * @package			forum
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/forum.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Forum {
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
		 * @desc			Converts forum name to forum-ids
		 * @param	string	$name Forum's Name
		 * @return	int		Forum's ID
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->name2id('forumname');
		 * </code>
		 * @since			2.0
		 */
		public function name2id($name){
			$sql = Ipbwi_IpsWrapper::instance()->DB->query('SELECT id FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE name="'.addslashes(htmlentities($name)).'"');
			$forums = Ipbwi_IpsWrapper::instance()->DB->fetch($sql);
			if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($sql) == 0){
				return false;
			}elseif(is_array($forums) && count($forums) === 1){
				// return matching forum-id
				return  $forums['id'];
			}else{
				// return array of matched forum-ids
				return $forums;
			}
		}
		/**
		 * @desc			Returns information on a forum.
		 * @param	int		$forumID Forum's ID
		 * @return	array	Forum's Information.
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->info(55);
		 * </code>
		 * @since			2.0
		 */
		public function info($forumID){
			if($cache = Ipbwi::instance()->cache->get('forumInfo', $forumID)){
				return $cache;
			}else{
				$sql = Ipbwi_IpsWrapper::instance()->DB->query('SELECT f.* from '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums f WHERE f.id="'.$forumID.'"');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch($sql)){
					$row['last_poster_name'] = Ipbwi::instance()->properXHTML($row['last_poster_name']);
					$row['name'] = Ipbwi::instance()->properXHTML($row['name']);
					$row['description'] = Ipbwi::instance()->properXHTML($row['description']);
					$row['last_title'] = Ipbwi::instance()->properXHTML($row['last_title']);
					$row['newest_title'] = Ipbwi::instance()->properXHTML($row['newest_title']);
					$perms = (isset($row['permission_array']) ? Ipbwi::instance()->permissions->sort($row['permission_array']) : false);
					$row['read_perms']   = $perms['read_perms'];
					$row['reply_perms']  = $perms['reply_perms'];
					$row['start_perms']  = $perms['start_perms'];
					$row['upload_perms'] = $perms['upload_perms'];
					$row['show_perms']   = $perms['show_perms'];
					Ipbwi::instance()->cache->save('forumInfo', $forumID, $row);
					return $row;
				}else{
					return false;
				}
			}
		}
		/**
		 * @desc			Returns forum permission data
		 * @param	string	$field, these are allowed: perm_view, perm_2, perm_3, perm_4, perm_5, perm_6, perm_7
		 * @return	array	permission data
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getData('perm_view');
		 * </code>
		 * @since			2.0
		 */
		 
		private function getData($field){
			// search in permission index
			$perms = explode(',',Ipbwi::instance()->member->myInfo['g_perm_id']);
			$query = 'SELECT perm_type_id, '.$field.' FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'permission_index WHERE perm_type = "forum"';
			$sql = Ipbwi_IpsWrapper::instance()->DB->query($query);
			$forums = array();
			while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($sql)){
				foreach($perms as $perm){
					if(strpos($row[$field],','.$perm.',') !== false || $row[$field] == '*'){
						$forums[$row['perm_type_id']]	= $row['perm_type_id'];
					}
				}
			}
			return $forums;
		}
		/**
		 * @desc			Returns whether a forum can be read by the current member.
		 * @param	int		$forumID Forum's ID
		 * @return	bool	Forum is readable
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->isReadable(55);
		 * </code>
		 * @since			2.0
		 */
		public function isReadable($forumID){
			$readable = $this->getReadable();
			if(isset($readable[$forumID])){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns forums readable by the current member.
		 * @return	array	Readable Forum Details
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getReadable();
		 * </code>
		 * @since			2.0
		 */
		public function getReadable(){
			if($cache = Ipbwi::instance()->cache->get('forumsGetReadable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				$forums = $this->getData('perm_2');
				Ipbwi::instance()->cache->save('forumsGetReadable', Ipbwi::instance()->member->myInfo['member_id'], $forums);
				return $forums;
			}
		}
		/**
		 * @desc			Returns whether a forum can be posted in by the current member.
		 * @param	int		$forumID Forum's ID
		 * @return	bool	Forum is postable in
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->isPostable(55);
		 * </code>
		 * @since			2.0
		 */
		public function isPostable($forumID){
			$postable = $this->getPostable();
			if(isset($postable[$forumID])){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns forums postable in by the current member.
		 * @return	array	Postable Forum Details
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getPostable();
		 * </code>
		 * @since			2.0
		 */
		public function getPostable(){
			if($cache = Ipbwi::instance()->cache->get('forumsGetPostable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				$forums = $this->getData('perm_3');
				Ipbwi::instance()->cache->save('forumsGetPostable', Ipbwi::instance()->member->myInfo['member_id'], $forums);
				return $forums;
			}
		}
		/**
		 * @desc			Returns whether a forum can start topics in.
		 * @param	int		$forumID Forum's ID
		 * @return	bool	Forum is startable in.
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->isStartable(55);
		 * </code>
		 * @since			2.0
		 */
		public function isStartable($forumID){
			$startable = $this->getStartable();
			if(isset($startable[$forumID])){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns forums in which the current member can start new topics in.
		 * @return	array	Startable Forum Details
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getStartable();
		 * </code>
		 * @since			2.0
		 */
		public function getStartable(){
			if($cache = Ipbwi::instance()->cache->get('forumsGetStartable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				$forums = $this->getData('perm_4');
				Ipbwi::instance()->cache->save('forumsGetStartable', Ipbwi::instance()->member->myInfo['member_id'], $forums);
				return $forums;
			}
		}
		/**
		 * @desc			Returns whether a forum can have uploads in topics
		 * @param	int		$forumID Forum's ID
		 * @return	bool	Forum allows uploads.
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->isUploadable(55);
		 * </code>
		 * @since			2.0
		 */
		public function isUploadable($forumID){
			$uploadable = $this->getUploadable();
			if(isset($uploadable[$forumID])){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns forums in which the current member can upload in topics.
		 * @return	array	Uploadable Forum Details
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getUploadable();
		 * </code>
		 * @since			2.0
		 */
		public function getUploadable(){
			if($cache = Ipbwi::instance()->cache->get('forumsgetUploadable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				$forums = $this->getData('perm_5');
				Ipbwi::instance()->cache->save('forumsgetUploadable', Ipbwi::instance()->member->myInfo['member_id'], $forums);
				return $forums;
			}
		}
		/**
		 * @desc			Returns whether a forum can have downloads in topics
		 * @param	int		$forumID Forum's ID
		 * @return	bool	Forum allows downloads.
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->isDownloadable(55);
		 * </code>
		 * @since			2.0
		 */
		public function isDownloadable($forumID){
			$Downloadable = $this->getDownloadable();
			if(isset($Downloadable[$forumID])){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns forums in which the current member can download in topics.
		 * @return	array	Downloadable Forum Details
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getDownloadable();
		 * </code>
		 * @since			2.0
		 */
		public function getDownloadable(){
			if($cache = Ipbwi::instance()->cache->get('forumsgetDownloadable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				$forums = $this->getData('perm_5');
				Ipbwi::instance()->cache->save('forumsgetDownloadable', Ipbwi::instance()->member->myInfo['member_id'], $forums);
				return $forums;
			}
		}
		/**
		 * @desc			Returns all subforums of the delivered forums.
		 * @param	mixed	$forums Forum IDs as int or array
		 * @param	string	$outputType The following output types are supported:<br>
		 * 					'html_form' to get a list of <option>-tags<br>
		 * 					'array' (default) for an array-list<br>
		 * 					'array_ids_only' for an array-list with forum IDs only<br>
		 * 					'name_id_with_indent' for an array list of names with indent according to the forum structure
		 * @param	string	$indentString The string for indent, default is '-'
		 * @return	mixed	List of all subforums
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->getAllSubs(array(55,22,77),'html_form');
		 * </code>
		 * @since			2.0
		 */
		public function getAllSubs($forums,$outputType='array',$indentString='—',$indent=false,$selectedID=false){
			$output = false;
			// get all categories, if needed
			if(is_string($forums) && $forums == '*'){
				$forums = $this->catList();
			// get forum information of requested category
			}elseif(is_string($forums) || is_int($forums)){
				$forums = array($this->info($forums));
			}
			// save original indent string
			if(isset($indent)){
				$orig_indent = $indent;
			}else{
				$orig_indent = false;
			}
			// grab all forums from every delivered cat-id
			if(is_array($forums) && count($forums) > 0){
				foreach($forums as $i){
					if($outputType == 'html_form'){ // give every forum its own option-tag
						$select = 'id,name';
						$output .= '<option'.(($selectedID == $i['id']) ? ' selected="selected"' : '').((isset($i['parent_id']) && $i['parent_id'] == '-1') ? ' style="background-color:#2683AE;color:#FFF;font-weight:bold;"' : ' style="color:#666;"').' value="'.$i['id'].'">&nbsp;&nbsp;'.$indent.'&nbsp;&nbsp;'.$i['name'].'</option>';
					}elseif($outputType == 'array'){ // merge all forum-data in one, big array
						$select = '*';
						$output[$i['id']] = $i;
					}elseif($outputType == 'array_ids_only'){ // merge all forum-data in one, big array
						$select = 'id';
						if(is_array($i)){
							$output[$i['id']] = $i['id'];
						}else{
							$output[$i] = $i;
						}
					}elseif($outputType == 'name_id_with_indent'){ // return name and id, with indent
						$select = 'id,name';
						$output[$i['id']]['id'] = $i['id'];
						$output[$i['id']]['name'] = $indent.$i['name'];
					}
					// grab all subforums from each delivered cat-id
					$sql = 'SELECT '.$select.' FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE parent_id = '.(isset($i['id']) ? $i['id'] : $i).' ORDER BY position ASC';
					if($subqery = Ipbwi_IpsWrapper::instance()->DB->query($sql)){
						// extend indent-string
						$indent = $indent.$indentString;
						// get all subforums in an array
						while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($subqery)){
							if($outputType == 'array_ids_only'){
								$subforums[$row['id']] = $row;
							}elseif($outputType == 'name_id_with_indent'){
								$subforums[$row['id']]['id'] = $row['id'];
								$subforums[$row['id']]['name'] = Ipbwi::instance()->properXHTML($row['name']);
							}else{
								if(isset($row['last_poster_name'])){ $row['last_poster_name'] = Ipbwi::instance()->properXHTML($row['last_poster_name']); }
								if(isset($row['name'])){ $row['name'] = Ipbwi::instance()->properXHTML($row['name']); }
								if(isset($row['description'])){ $row['description'] = Ipbwi::instance()->properXHTML($row['description']); }
								if(isset($row['last_title'])){ $row['last_title'] = Ipbwi::instance()->properXHTML($row['last_title']); }
								if(isset($row['newest_title'])){ $row['newest_title'] = Ipbwi::instance()->properXHTML($row['newest_title']); }
								$subforums[$row['id']] = $row;
							}
						}
						// make it rekursive
						if(isset($subforums) && is_array($subforums) && count($subforums) > 0){
							if($outputType == 'html_form'){
								// give every forum its own option-tag
								$output .= $this->getAllSubs($subforums,$outputType,$indentString,$indent,$selectedID);
							}elseif($outputType == 'array' || $outputType == 'array_ids_only'){
								// merge all forum-data in one, big array
								$output = $output+$this->getAllSubs($subforums,$outputType,$indentString,$indent,$selectedID);
							}elseif($outputType == 'name_id_with_indent'){
								$output = $output+$this->getAllSubs($subforums,$outputType,$indentString,$indent,$selectedID);
							}
						}
						// reset the temp-values
						$subforums = false;
						$indent = $orig_indent;
					}
				}
			}else{
				return false;
			}
			return $output;
		}
		/**
		 * @desc			Deletes the forum with delivered forum_id including all subforums, topics, polls and posts.
		 * @param	int		$forumID Forum's ID
		 * @return	bool	true or false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->delete(55);
		 * </code>
		 * @since			2.0
		 */
		public function delete($forumID){
			$forumsArray = $this->getAllSubs($forumID);
			if(isset($forumsArray) && is_array($forumsArray) && count($forumsArray) > 0){
				$forumsString = '"'.implode('","',array_keys($forumsArray)).'"';
			}else{
				return false;
			}
			if(Ipbwi_IpsWrapper::instance()->DB->query('SELECT tid FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'topics WHERE forum_id IN ('.$forumsString.')')){
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					$topicsArray[] = $row['tid'];
				}
				if(isset($topicsArray) && is_array($topicsArray) && count($topicsArray) > 0){
					$topicsString = '"'.implode('","',$topicsArray).'"';
				}
			}
			// delete posts
			if(isset($topicsString)){
				Ipbwi_IpsWrapper::instance()->DB->query('DELETE FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'posts WHERE topic_id IN ('.$topicsString.')');
			}
			// delete polls
			if(isset($topicsString)){
				Ipbwi_IpsWrapper::instance()->DB->query('DELETE FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'polls WHERE tid IN ('.$topicsString.')');
			}
			// delete topics
			if(isset($forumsString)){
				Ipbwi_IpsWrapper::instance()->DB->query('DELETE FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'topics WHERE forum_id IN ('.$forumsString.')');
			}
			// delete all subforums
			if(isset($forumsString)){
				Ipbwi_IpsWrapper::instance()->DB->query('DELETE FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE id IN ('.$forumsString.')');
			}
			Ipbwi_IpsWrapper::instance()->update_forum_cache();
			return true;
		}
		/**
		 * @desc			Creates a forum in the specified category
		 * @param	string	$forumName Forum's name
		 * @param	string	$forumDesc Forum's description
		 * @param	catID	$forumID Categories ID
		 * @param	perms	$forumID Forum's permissions as array
		 * + int <b>$perms[startperms]:</b> Group IDs for Start posts permission
		 * + int <b>$perms[replyperms]:</b> Group IDs for Reply-To posts permission
		 * + int <b>$perms[readperms]:</b> Group IDs for Read posts permission
		 * + int <b>$perms[uploadperms]:</b> Group IDs for Fileupload permission
		 * + int <b>$perms[showperms]:</b> Group IDs for Show permission
		 * @return	long	new forum's ID or false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->create('Forumname','Forum Description',2,array('show' => '*','read' => '*','start' => '*','reply' => '*','upload' => '*','download' => '*'));
		 * </code>
		 * @since			2.0
		 */
		public function create($forumName, $forumDesc, $catID, $perms){
			$forumName = Ipbwi::instance()->makeSafe($forumName);
			$forumDesc = Ipbwi::instance()->makeSafe($forumDesc);
			Ipbwi_IpsWrapper::instance()->DB->query('LOCK TABLE '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WRITE');
			Ipbwi_IpsWrapper::instance()->DB->query('SELECT MAX(id) as max FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums');
			$row = Ipbwi_IpsWrapper::instance()->DB->fetch();
			$max = $row['max'];
			Ipbwi_IpsWrapper::instance()->DB->query('UNLOCK TABLES');
			if($max < 1){
				$max = 0;
			}
			++$max;
			// Check Cat Exists.
			if($catID != '-1'){
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE id="'.intval($catID).'"');
				if(!Ipbwi_IpsWrapper::instance()->DB->fetch()){
					Ipbwi::instance()->addSystemMessage('Error',Ipbwi::instance()->getLibLang('catNotExist'),'Located in file <strong>'.__FILE__.'</strong> at class <strong>'.__CLASS__.'</strong> in function <strong>'.__FUNCTION__.'</strong> on line #<strong>'.__LINE__.'</strong>');
					return false;
				}
			}
			Ipbwi_IpsWrapper::instance()->DB->query('SELECT MAX(position) as pos FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE parent_id="'.intval($catID).'"');
			$row = Ipbwi_IpsWrapper::instance()->DB->fetch();
			$pos = $row['pos'];
			if($pos < 1) $pos = '0';
			++$pos;
			// Permissions
			$permissions = array(
				'start_perms' => $perms['start'],
				'reply_perms' => $perms['reply'],
				'read_perms' => $perms['read'],
				'upload_perms' => $perms['upload'],
				'download_perms' => $perms['download'],
				'show_perms' => $perms['show']
			);
			$permsfinal = array();
			// Get Groups
			$groups = array();
			Ipbwi_IpsWrapper::instance()->DB->query('SELECT perm_id FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forum_perms');
			while($groupsr = Ipbwi_IpsWrapper::instance()->DB->fetch()){
				$groups[] = $groupsr['perm_id'];
			}
			foreach($permissions as $i => $j){
				// if permission is to be set for category
				if($j == '*' && $catID == '-1'){
					$x = array();
					foreach($groups as $l){
						$x[] = intval($l);
					}
					$permsfinal[$i] = implode (',', $x);
				// if permission is to be set for forum
				}elseif($j == '*'){
					// All Groups
					$permsfinal[$i] = '*';
				}else{
					$x = array();
					foreach($j as $l){
						if(in_array($l, $groups)){
							$x[] = intval($l);
						}
					}
					$permsfinal[$i] = implode (',', $x);
				}
			}
			$perm_array = addslashes(serialize($permsfinal));
			// Finally Add it to the Database
			if($catID == '-1'){
				// category settings
				$DB_string = Ipbwi_IpsWrapper::instance()->DB->compile_db_insert_string(
					array(
						'id' =>						$max,
						'topics' =>					0,
						'posts' =>					0,
						'last_post' =>				0,
						'last_poster_id' =>			0,
						'last_poster_name' =>		'',
						'name' =>					$forumName,
						'description' =>			$forumDesc,
						'position' =>				$pos,
						'use_ibc' =>				0,
						'use_html' =>				0,
						'status' =>					0,
						'password' =>				'',
						'password_override' =>		'',
						'last_title' =>				'NULL',
						'last_id' =>				0,
						'sort_key' =>				'last_post',
						'sort_order' =>				'Z-A',
						'prune' =>					0,
						'topicfilter' =>			'all',
						'show_rules' =>				'NULL',
						'preview_posts' =>			0,
						'allow_poll' =>				0,
						'allow_pollbump' =>			0,
						'inc_postcount' =>			0,
						'skin_id' =>				'NULL',
						'parent_id' =>				-1,
						'sub_can_post' =>			0,
						'quick_reply' =>			0,
						'redirect_url' =>			'',
						'redirect_on' =>			0,
						'redirect_hits' =>			0,
						'redirect_loc' =>			'',
						'rules_title' =>			'',
						'rules_text' =>				'NULL',
						'topic_mm_id' =>			'',
						'notify_modq_emails' =>		'',
						'permission_custom_error'=>	'',
						'permission_array' =>		$perm_array,
						'permission_showtopic' =>	1,
						'queued_topics' =>			0,
						'queued_posts' =>			0,
						'forum_last_deletion' =>	0,
						'forum_allow_rating' =>		0,
						'newest_title' =>			'NULL',
						'newest_id' =>				0,
					)
				);
			}else{
				// forum settings
				$DB_string = Ipbwi_IpsWrapper::instance()->DB->compile_db_insert_string(
					array(
						'id' =>							$max,
						'topics' =>						0,
						'posts' =>						0,
						'last_post' =>					0,
						'last_poster_id' =>				0,
						'last_poster_name' =>			'',
						'name' =>						$forumName,
						'description' =>				$forumDesc,
						'position' =>					$pos,
						'use_ibc' =>					1,
						'use_html' =>					0,
						'status' =>						1,
						'password' =>					'',
						'password_override' =>			'',
						'last_title' =>					'',
						'last_id' =>					0,
						'sort_key' =>					'last_post',
						'sort_order' =>					'Z-A',
						'prune' =>						100,
						'topicfilter' =>				'all',
						'show_rules' =>					'NULL',
						'preview_posts' =>				0,
						'allow_poll' =>					1,
						'allow_pollbump' =>				0,
						'inc_postcount' =>				1,
						'skin_id' =>					'NULL',
						'parent_id' =>					intval($catID),
						'sub_can_post' =>				1,
						'quick_reply' =>				1,
						'redirect_url' =>				'',
						'redirect_on' =>				0,
						'redirect_hits' =>				0,
						'redirect_loc' =>				'',
						'rules_title' =>				'',
						'rules_text' =>					'NULL',
						'topic_mm_id' =>				'',
						'notify_modq_emails' =>			'',
						'permission_custom_error' =>	'',
						'permission_array' =>			$perm_array,
						'permission_showtopic' =>		0,
						'queued_topics' =>				0,
						'queued_posts' =>				0,
						'forum_last_deletion' =>		0,
						'forum_allow_rating' =>			1,
						'newest_title' =>				'',
						'newest_id' =>					0,
					)
				);
			}
			Ipbwi_IpsWrapper::instance()->DB->query('LOCK TABLE '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WRITE');
			Ipbwi_IpsWrapper::instance()->DB->query('INSERT INTO '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums ('.$DB_string['FIELD_NAMES'].') VALUES ('.$DB_string['FIELD_VALUES'].')');
			Ipbwi_IpsWrapper::instance()->DB->query('UNLOCK TABLES');
			Ipbwi_IpsWrapper::instance()->update_forum_cache();
			return $max;
		}
		/**
		 * @desc			List categories.
		 * @return	array	Board's Categories
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->catList();
		 * </code>
		 * @since			2.0
		 */
		public function catList(){
			if($cache = Ipbwi::instance()->cache->get('listCategories', '1')){
				return $cache;
			}else{
				$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'forums WHERE parent_id = "-1"');
				$cat = array();
				if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows() == 0) return false;
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($query)){
					$row['last_poster_name'] = Ipbwi::instance()->properXHTML($row['last_poster_name']);
					$row['name'] = Ipbwi::instance()->properXHTML($row['name']);
					$row['description'] = Ipbwi::instance()->properXHTML($row['description']);
					$row['last_title'] = Ipbwi::instance()->properXHTML($row['last_title']);
					$row['newest_title'] = Ipbwi::instance()->properXHTML($row['newest_title']);
					$cat[$row['id']] = $row;
				}
				Ipbwi::instance()->cache->save('listCategories', '1', $cat);
				return $cat;
			}
		}
		/**
		 * @desc			Get Information on a Category
		 * @param	int		$catID Unique ID of the category
		 * @return	array	Information of category categoryid
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->categoryInfo(5);
		 * </code>
		 * @since			2.0
		 */
		public function categoryInfo($catID){
			$cats = $this->catList();
			if($cats[$catID]){
				return $cats[$catID];
			}else{
				return false;
			}
		}
	}
?>