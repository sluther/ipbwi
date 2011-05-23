<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-01-18 03:52:31 +0000 (So, 18 Jan 2009) $
	 * @package			blog
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/topic.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Blog {
		public $installed		= false;
		public $online			= false;
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
			// check if IP.gallery is installed
			$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT conf_value,conf_default FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'core_sys_conf_settings WHERE conf_key="blog_online"');
			if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($query) != 0){
				$data = Ipbwi_IpsWrapper::instance()->DB->fetch($query);
				// retrieve Gallery URL
				$this->online = (($data['conf_value'] != '') ? $data['conf_value'] : $data['conf_default']);
				$this->installed = true;
			}
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
		 * @desc			lists latest Blog Entries from IP.blog
		 * @param	mixed	$blogIDs The blog IDs where the entries should be retrieved from (array-list or int) Use '*', leave empty or set to false for entries from all blogs)
		 * @param	array	$settings optional query settings. Settings allowed: limit and start
		 * + int start = Default: 0
		 * + int limit = Default: 15
		 * @return	array	Blog-Entry-Informations as multidimensional array
		 * @author			Matthias Reuter
		 * @since			2.04
		 */
		public function getLatestList($blogIDs=false,$settings=array()){
			if($this->installed === true){
				if(is_array($blogIDs)){
					// todo
				}elseif($blogIDs == '*'){
					$viewable = $this->getViewable();
					if(isset($viewable[1])){
						$viewable[0] = '0';
					}
					$blogQuery = ' AND (e.blog_id="'.implode('" OR e.blog_id="',$viewable).'")';
				}elseif(intval($blogIDs) != 0){
					$blogQuery = ' AND e.blog_id="'.$blogIDs.'"';
				}else{
					$blogQuery = false;
				}
				if(empty($settings['start'])){
					$settings['start'] = 0;
				}
				if(empty($settings['limit'])){
					$settings['limit'] = 15;
				}

				// get latest blog entries
				$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT e.*,b.* FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'blog_entries e LEFT JOIN '.Ipbwi::instance()->board['sql_tbl_prefix'].'blog_blogs b ON (b.blog_id=e.blog_id) WHERE e.entry_status="published"'.$blogQuery.' ORDER BY e.entry_id DESC LIMIT '.intval($settings['start']).','.intval($settings['limit']));
				if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($query) == 0){
					return false;
				}
				$data = array();
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($query)){
					$row['entry_author_name']	= Ipbwi::instance()->properXHTML($row['entry_author_name']);
					$row['entry_name']			= Ipbwi::instance()->properXHTML($row['entry_name']);
					$row['entry']				= Ipbwi::instance()->properXHTML($row['entry']);
					$row['entry_edit_name']		= Ipbwi::instance()->properXHTML($row['entry_edit_name']);
					$row['blog_name']			= Ipbwi::instance()->properXHTML($row['blog_name']);
					$row['blog_desc']			= Ipbwi::instance()->properXHTML($row['blog_desc']);
					$data[] = $row;
				}
				return $data;
			}else{
				return false;
			}
		}
	}
?>