<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2008-10-27 22:51:07 +0000 (Mo, 27 Okt 2008) $
	 * @package			cache
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 * @ignore
	 */
	namespace Ipbwi;
	class Ipbwi_Cache {
		private $data			 = array();
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
		
		private function __construct(){

		}
		/**
		 * @desc			Gets function results cache.
		 * @param	string	$function API Method who's query results have been cached
		 * @param	string	$id Key to identify a query from the function
		 * @return	mixed	Cached item or false if $key does not exist.
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function get($function, $id){
			if(array_key_exists($function, $this->data)){
				return (array_key_exists($id, $this->data[$function])) ? $this->data[$function][$id] : false;
			}else{
				return false;
			}
		}
		
		/**
		 * @desc			Saves/Updates function results cache.
		 * @param	string	$function API Method who's query results have been cached
		 * @param	string	$id Key to identify a query from the function
		 * @param	string	$data Data being cached
		 * @return	bool	true
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function save($function, $id, $data){
			$this->data[$function][$id] = $data;
			return true;
		}
		/**
		 * @desc			Attempts to find some value/object in the cache for cross variable assignments.
		 * @param	string	$function API Method who's query results have been cached
		 * @param	string	$key Key to search for in this method's results
		 * @return	mixed	value/object whatever found in cache
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function find($function, $key){
			$data = array();
			if($this->data[$function]){
				foreach(array_keys($this->data[$function]) as $id){
					$vType = gettype($this->data[$function][$id]);
					if($vType == 'array' && isset($this->data[$function][$id][$key])){
						// find array element
						$val = &$this->data[$function][$id][$id][$key];
					}elseif($vType == 'object' && isset($this->data[$function][$id]->$key)){
						// find object property
						$val = &$this->data[$function][$id]->$key;
					}else{
						// find value
						$val = &$this->data[$function][$id];
					}
					if(isset($val)){
						$data[] = $val;
					}
					unset($val);
				}
			}
			return $data;
		}
		/**
		 * @desc			List all cache stores.
		 * @return	array	all cache store key, values and extra.
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function listStores(){
			if($cache = $this->get('listCacheStores', '1')){
				return $cache;
			}
			else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT cs_key, cs_value, cs_extra FROM '.$this->board['sql_tbl_prefix'].'cache_store');
				$cs = array();
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					$cs[$row['cs_key']] = $row;
				}
				$this->save('listCacheStores', '1', $cs);
				return $cs;
			}
		}
		/**
		 * @desc			Get the value of a cache store.
		 * @param	string	$key Key of the cache store
		 * @return	string	value of a cache store.
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function getStoreValue($key){
			$cs = $this->listStores();
			if($cs[$key]){
				return $cs[$key]['cs_value'];
			}else{
				return false;
			}
		}
		/**
		 * @desc			Sets or updates the value of a cache store.
		 * @param	string	$key Key of the cache store
		 * @param	string	$value Value to store
		 * @return	bool	true on success.
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function setStoreValue($key, $value = false){
			$cs = $this->listStores();
			if($cs[$key]){
				// Already exists so just use UPDATE
				Ipbwi_IpsWrapper::instance()->DB->query('UPDATE '.$this->board['sql_tbl_prefix'].'cache_store SET cs_value="'.$value.'", cs_extra="'.(time()+86400).'" WHERE cs_key="'.$key.'"');
				if(Ipbwi_IpsWrapper::instance()->DB->get_affected_rows()){
					// And update our cached copy
					$cs[$key] = array('cs_key' => $key,
						'cs_value' => $value,
						'cs_extra' => (time()+86400),
						);
					$this->save('listCacheStores', '1', $cs);
					return true;
				}else{
					return false;
				}
			}else{
				// Doesn't exist so use INSERT
				Ipbwi_IpsWrapper::instance()->DB->query('INSERT INTO '.$this->board['sql_tbl_prefix'].'cache_store (cs_key, cs_value, cs_extra) VALUES ("'.$key.'", "'.$value.'", "'.(time()+86400).'")');
				if(Ipbwi_IpsWrapper::instance()->DB->get_affected_rows()){
					// And update our cached copy
					$cs[$key] = array('cs_key' => $key,
						'cs_value' => $value,
						'cs_extra' => (time()+86400),
						);
					$this->save('listCacheStores', '1', $cs);
					return true;
				}else{
					return false;
				}
			}
		}
		/**
		 * @desc			Searches the cache store.
		 * @param	mixed	$value Storage value to search
		 * @param	bool	$exactmatch Use exact matching or wildcard search
		 * @return	array	cache stores matching criteria
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function searchStore($value, $exactmatch = FALSE){
			// Do the SQL Query
			if($exactmatch){
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.$this->board['sql_tbl_prefix'].'cache_store WHERE cs_value="'.$value.'"');
			}else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.$this->board['sql_tbl_prefix'].'cache_store WHERE cs_value LIKE "%'.$value.'%"');
			}
			$cs = array();
			while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
				$cs[$row['cs_key']] = $row;
			}
			return $cs;
		}
		/**
		 * @desc			Updates Forum-Cache and recounts Last-Count-Datas.
		 * @param	int		$forumID
		 * @param	array	$deleted_info An optional array with informations of deleted topic can be delivered to update the count-datas.
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function updateForum($forumID,$count=array()){
			if(empty($count['topics'])){
				$count['topics'] = 0;
			}
			if(empty($count['posts'])){
				$count['posts'] = 0;
			}
			// grab data from new latest post in forum
			$topic = $this->topic->getList($forumID,array('limit' => 1,'orderby' => 'last_post'));
			// Finally update the forum
			if($topic != false){
				foreach($topic as $lastTopicInfo){
					$query = '
						UPDATE '.$this->board['sql_tbl_prefix'].'forums SET
						posts=posts+'.$count['posts'].',
						topics=topics+'.$count['topics'].',
						last_title="'.$lastTopicInfo['title'].'",
						last_id="'.$lastTopicInfo['tid'].'",
						newest_title="'.$lastTopicInfo['title'].'",
						newest_id="'.$lastTopicInfo['tid'].'",
						last_poster_name="'.$lastTopicInfo['last_poster_name'].'",
						last_poster_id="'.$lastTopicInfo['last_poster_id'].'",
						last_post="'.$lastTopicInfo['last_post'].'"
						WHERE id="'.$forumID.'"';
				}
			}
			if(Ipbwi_IpsWrapper::instance()->DB->query($query)
			){
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Updates PMs-User-Cache.
		 * @param	int		$ownerID
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @since			2.0
		 */
		public function updatePM($ownerID){
			$ownerID = intval($ownerID);
			$folders = $this->pm->getFolders();
			foreach($folders as $folder){
			
				$sql = 'SELECT COUNT(t.mt_id) AS count FROM '.$this->board['sql_tbl_prefix'].'message_topics t LEFT JOIN '.$this->board['sql_tbl_prefix'].'message_topic_user_map m ON (m.map_topic_id=t.mt_id) WHERE m.map_folder_id="'.$folder['id'].'" AND m.map_user_id="'.$ownerID.'"';
				$query = Ipbwi_IpsWrapper::instance()->DB->query($sql);
				if($message = Ipbwi_IpsWrapper::instance()->DB->fetch($query)){
					if(($folder['id'] != 'myconvo' || $folder['id'] != 'drafts' || $folder['id'] != 'new') && $folder['real'] == ''){
					
					}else{
						if($folder['id'] == 'myconvo' && $folder['real'] == ''){
							$folder['real']			= 'My Conversations';
							$folder['protected']	= 1;
						}elseif($folder['id'] == 'drafts' && $folder['real'] == ''){
							$folder['real']			= 'Drafts';
							$folder['protected']	= 1;
						}elseif($folder['id'] == 'new' && $folder['real'] == ''){
							$folder['real']			= 'New';
							$folder['protected']	= 1;
						}
						
						$count[$folder['id']]['id']			= $folder['id'];
						$count[$folder['id']]['real']		= $folder['real'];
						$count[$folder['id']]['count']		= $message['count'];
						$count[$folder['id']]['protected']	= $folder['protected'];
					}
				}
			}
			
			// if a default folder is still missed, recreate it
			if(!isset($count['new']['id'])){
				$count['new']['id']			= 'new';
				$count['new']['real']			= 'New';
				$count['new']['protected']		= 1;
				$count['new']['count']			= 0;
			}
			if(!isset($count['myconvo']['id'])){
				$count['myconvo']['id']		= 'myconvo';
				$count['myconvo']['real']		= 'My Conversations';
				$count['myconvo']['protected']	= 1;
				$count['myconvo']['count']		= 0;
			}
			if(!isset($count['drafts']['id'])){
				$count['drafts']['id']			= 'drafts';
				$count['drafts']['real']		= 'Drafts';
				$count['drafts']['protected']	= 1;
				$count['drafts']['count']		= 0;
			}
		
			if(Ipbwi_IpsWrapper::instance()->DB->query('UPDATE '.$this->board['sql_tbl_prefix'].'profile_portal SET pconversation_filters="'.addslashes(serialize($count)).'" WHERE pp_member_id="'.$this->member->myInfo['member_id'].'" LIMIT 1')){
				return true;
			}else{
				return false;
			}
		}
	}
?>