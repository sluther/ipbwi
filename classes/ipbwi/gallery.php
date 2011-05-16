<?php
	/**
	 * @author			Matthias Reuter ($LastChangedBy: matthias $)
	 * @version			$LastChangedDate: 2009-01-18 03:52:31 +0000 (So, 18 Jan 2009) $
	 * @package			gallery
	 * @copyright		2007-2010 IPBWI development team
	 * @link			http://ipbwi.com/examples/topic.php
	 * @since			2.0
	 * @license			http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
	 */
	namespace Ipbwi;
	class Ipbwi_Gallery {
		private $ipbwi			= null;
		public $installed		= false;
		public $url				= false;

		/**
		 * @desc			Loads and checks different vars when class is initiating
		 * @author			Matthias Reuter
		 * @since			2.0
		 * @ignore
		 */
		public function __construct($ipbwi){
			// loads common classes
			Ipbwi::instance()-> = $ipbwi;

			// check if IP.gallery is installed
			$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT conf_value,conf_default FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'core_sys_conf_settings WHERE conf_key="gallery_images_url"');
			if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($query) != 0){
				$data = Ipbwi_IpsWrapper::instance()->DB->fetch($query);
				// retrieve Gallery URL
				$this->url = (($data['conf_value'] != '') ? $data['conf_value'] : $data['conf_default']).'/';
				$this->installed = true;
			}
		}
		/**
		 * @desc			Returns categories readable by the current member.
		 * @return	array	Readable category IDs
		 * @author			Matthias Reuter
		 * @author			Pita <peter@randomnity.com>
		 * @sample
		 * <code>
		 * $ipbwi->gallery->getViewable();
		 * </code>
		 * @since			2.0
		 */
		public function getViewable(){
			if($cache = Ipbwi::instance()->cache->get('galleryGetViewable', Ipbwi::instance()->member->myInfo['member_id'])){
				return $cache;
			}else{
				Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'gallery_categories');
				$cats = array();
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
					if(Ipbwi::instance()->group->isInGroup($row['perms_view'])){
						$cats[$row['id']] = $row['id'];
					}
				}
				Ipbwi::instance()->cache->save('galleryGetViewable', Ipbwi::instance()->member->myInfo['member_id'], $cats);
				return $cats;
			}
		}
		/**
		 * @desc			lists latest images from IP.gallery.
		 * @return	array	Image-Informations as multidimensional array
		 * @author			Matthias Reuter
		 * @since			2.02
		 */
		public function getLatestList($catIDs=false,$settings=array()){
			if($this->installed === true){
				if(is_array($catIDs)){
					// todo
				}elseif($catIDs == '*'){
					$viewable = $this->getViewable();
					if(isset($viewable[1])){
						$viewable[0] = '0';
					}
					$catquery = ' AND (category_id="'.implode('" OR category_id="',$viewable).'")';
				}elseif(intval($catIDs) != 0){
					$catquery = ' AND category_id="'.$catIDs.'"';
				}else{
					$catquery = false;
				}
				if(empty($settings['start'])){
					$settings['start'] = 0;
				}
				if(empty($settings['limit'])){
					$settings['limit'] = 15;
				}
				if(empty($settings['memberid'])){
					$fromMember = false;
				}else{
					$fromMember = ' AND member_id = "'.$settings['memberid'].'"';
				}

				// get latest images
				$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'gallery_images WHERE approved="1"'.$catquery.$fromMember.' ORDER BY id DESC LIMIT '.intval($settings['start']).','.intval($settings['limit']));
				if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($query) == 0){
					return false;
				}
				$data = array();
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($query)){
					$row['caption']			= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['caption']),false));
					$row['description']		= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['description']),false));
					$row['copyright']		= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['copyright']),false));
					$data[] = $row;
				}
				return $data;
			}else{
				return false;
			}
		}
		/**
		 * @desc			Returns all subcategories of the delivered cats.
		 * @param	mixed	$forums category IDs as int or array
		 * @param	string	$outputType The following output types are supported:<br>
		 * 					'html_form' to get a list of <option>-tags<br>
		 * 					'array' (default) for an array-list<br>
		 * 					'array_ids_only' for an array-list with forum IDs only<br>
		 * 					'name_id_with_indent' for an array list of names with indent according to the forum structure
		 * @param	string	$indentString The string for indent, default is '-'
		 * @return	mixed	List of all subcategories
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->gallery->getAllSubs(array(55,22,77),'html_form');
		 * </code>
		 * @since			2.0
		 */
		public function getAllSubs($cats,$outputType='array',$indentString='—',$indent=false,$selectedID=false){
			if($this->installed === true){
				$output = false;
				// get all categories, if needed
				if(is_string($cats) && $cats == '*'){
					$cats = $this->catList();
				// get forum information of requested category
				}elseif(is_string($cats)){
					$cats = array($this->info($cats));
				}
				// save original indent string
				if(isset($indent)){
					$orig_indent = $indent;
				}else{
					$orig_indent = false;
				}
				// grab all forums from every delivered cat-id
				if(is_array($cats) && count($cats) > 0){
					foreach($cats as $i){
						if($outputType == 'html_form'){ // give every forum its own option-tag
							$select = 'id,name';
							$output .= '<option'.(($selectedID == $i['id']) ? ' selected="selected"' : '').(($i['parent'] == '0') ? ' style="background-color:#2683AE;color:#FFF;font-weight:bold;"' : ' style="color:#666;"').' value="'.$i['id'].'">&nbsp;&nbsp;'.$indent.'&nbsp;&nbsp;'.$i['name'].'</option>';
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
							$output[$i['id']] = $i;
							$output[$i['id']]['name'] = $indent.$i['name'];
						}
						// grab all subforums from each delivered cat-id
						if($subqery = Ipbwi_IpsWrapper::instance()->DB->query('SELECT '.$select.' FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'gallery_categories WHERE parent = '.$i['id'].' ORDER BY c_order ASC')){
							// extend indent-string
							$indent = $indent.$indentString;
							// get all subforums in an array
							while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($subqery)){
								if($outputType == 'array_ids_only'){
									$subforums[$row['id']] = $row['id'];
								}else{
									$row['last_pic_name'] = Ipbwi::instance()->properXHTML($row['last_pic_name']);
									$row['name'] = Ipbwi::instance()->properXHTML($row['name']);
									$row['description'] = Ipbwi::instance()->properXHTML($row['description']);
									$subforums[] = $row;
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
			}else{
				return false;
			}
		}
		/**
		 * @desc			List categories.
		 * @return	array	Gallery's Categories
		 * @author			Matthias Reuter
		 * @sample
		 * <code>
		 * $ipbwi->forum->catList();
		 * </code>
		 * @since			2.0
		 */
		public function catList(){
			if($this->installed === true){
				if($cache = Ipbwi::instance()->cache->get('listGalleryCategories', '1')){
					return $cache;
				}else{
					Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'gallery_categories WHERE parent = "0"');
					$cat = array();
					while($row = Ipbwi_IpsWrapper::instance()->DB->fetch()){
						$row['last_pic_name'] = Ipbwi::instance()->properXHTML($row['last_pic_name']);
						$row['name'] = Ipbwi::instance()->properXHTML($row['name']);
						$row['description'] = Ipbwi::instance()->properXHTML($row['description']);
						$cat[$row['id']] = $row;
					}
					Ipbwi::instance()->cache->save('listGalleryCategories', '1', $cat);
					return $cat;
				}
			}else{
				return false;
			}
		}

		public function info($imgID){
			if($this->installed === true){
				// get image info
				$query = Ipbwi_IpsWrapper::instance()->DB->query('SELECT * FROM '.Ipbwi::instance()->board['sql_tbl_prefix'].'gallery_images WHERE id="'.intval($imgID).'"');
				if(Ipbwi_IpsWrapper::instance()->DB->getTotalRows($query) == 0){
					return false;
				}
				$data = array();
				while($row = Ipbwi_IpsWrapper::instance()->DB->fetch($query)){
					$row['caption']			= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['caption']),false));
					$row['description']		= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['description']),false));
					$row['copyright']		= Ipbwi::instance()->properXHTML(Ipbwi::instance()->bbcode->bbcode2html(Ipbwi::instance()->bbcode->html2bbcode($row['copyright']),false));
					$data = $row;
				}
				return $data;
			}else{
				return false;
			}
		}
	}
?>