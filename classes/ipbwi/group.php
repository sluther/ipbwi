<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-08-26 19:19:41 +0200 (Mi, 26 Aug 2009) $
	 * @package			group
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/group.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Group {
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
		 * @desc			Returns information on a group.
		 * @param	int		$group Group ID. If $group is ommited, the last known group (of the last member) is used.
		 * @return	array	Group Information
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->group->info(5);
		 * </code>
		 * @since			2.0
		 */
		public function info($group=false){
			if(!$group){
				// No Group? Return current group info
				$group = Ipbwi::instance()->member->myInfo['member_group_id'];
			}
			// Check for cache - if exists don't bother getting it again
			if($cache = Ipbwi::instance()->cache->get('groupInfo', $group)){
				return $cache;
			}else{
				// Return group info if group given
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT g.* FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'groups g WHERE g_id="'.intval($group).'"');
				if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows()){
					$info = Ipbwi_IpsWrapper::instance()->DB->fetch();
					Ipbwi::instance()->cache->save('groupInfo', $group, $info);
					return $info;
				}else{
					return false;
				}
			}
		}
		/**
		 * @desc			Changes Member group to delivered group-id.
		 * @param	int		$group Group ID
		 * @param	int		$member Member ID. If no Member-ID is delivered, the currently logged in member will moved.
		 * @param	array	$extra secondary Group-IDs
		 * @return	bool	true on success, otherwise false
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->group->change(5);
		 * $ipbwi->group->change(7,12,array(1,2,3,4));
		 * </code>
		 * @since			2.0
		 */
		public function change($group,$member=false,$extra=false){
			if(!$member){
				$member = Ipbwi::instance()->member->myInfo['member_id'];
			}
			if($extra !== false){
				$sql_extra = ',mgroup_others="'.implode(',',$extra).'"';
			}else{
				$sql_extra = '';
			}
			
			$SQL = 'UPDATE '.Ipbwi::instance()->board['sql_tbl_prefix'].'members SET member_group_id="'.$group.'"'.$sql_extra.' WHERE member_id="'.intval($member).'"';

			if(Ipbwi_IpsWrapper::instance()->DB->query($SQL)){
				Ipbwi::instance()->member->myInfo['member_group_id'] = $group;
				return true;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns whether a member is in the specified group(s).
		 * @param	int		$group Group ID or array of groups-ids separated with comma: 2,5,7
		 * @param	int		$member Member ID to find
		 * @param	bool	$extra Include secondary groups to test against?
		 * @return	mixed	Whether member is in group(s)
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->group->isInGroup(5);
		 * $ipbwi->group->isInGroup(7,12,true);
		 * </code>
		 * @since			2.0
		 */
		function isInGroup($group, $member = false, $extra = true) {
			if (!is_array($group)) $group = explode(',', $group);
			settype($group, 'array');
			if ($member) {
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT member_group_id,mgroup_others FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'members WHERE member_id="'.$member.'"');
				if($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					if(in_array($row['member_group_id'], $group)){
						return true;
					}
					if($extra){
						$others = explode(',',$row['mgroup_others']);
						foreach($others as $other){
							if(in_array($other,$group)){
								return true;
							}
						}
					}
				}
				return false;
			}else{
				if(in_array(Ipbwi::instance()->member->myInfo['member_group_id'], $group)){
					return true;
				}else{
					// START CHANGE
					$other = explode(',',Ipbwi::instance()->member->myInfo['mgroup_others']);
					if(is_array($other)) {
						foreach($other as $v) {
							if(in_array($v, $group)) {
								return true;
							}
						}
					}
					// END CHANGE
					return false;
				}
			}
		}
	}
?>