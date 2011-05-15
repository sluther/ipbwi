<?php
namespace Ipbwi;
define('IPB_THIS_SCRIPT', 'public');
//define( 'IN_IPB', 1 );
define( 'IPS_IS_SHELL', TRUE); // make offlinemode possible without crashing IPBWI
define( 'ALLOW_FURLS', FALSE ); // disable friendly url check
define('CCS_GATEWAY_CALLED', 1);
$_POST['rememberMe'] = 1; // hotfix for sticky cookies

class Ipbwi_IpsWrapper extends \apiCore {
	public	$loggedIn;
	public	$DB;
	public	$settings;
	public	$request;
	public	$lang;
	public	$member;
	public	$cache;	
	public	$registry;
	public	$perm;
	public	$parser;
	private static $instance = null;
	
	public static function instance() {
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
		}
		
		return self::$instance;
	}
	
	public function init($config) {
		parent::init();
		$this->loggedIn					= (bool) $this->lang->memberData['member_id']; // status wether a member is logged in
		$this->settings['base_url']		= $this->settings['board_url'].'?';
		
		// get common functions
		require_once($config->board_admin_path.'sources/base/ipsController.php');
		$this->command		= new \ipsCommand_default();
		
		// initialize session
		require_once($config->board_admin_path.'sources/classes/session/publicSessions.php');
		$this->session		= new \publicSessions();

		// prepare bbcode functions
		$this->cache->rebuildCache( 'emoticons', 'global' );
		
		// force ability of using rich text editor
		$this->registry->member()->setProperty('_canUseRTE', TRUE );
		
		// MEMBER FUNCTIONS
		
		// get login / logout functions
		require_once($config->board_admin_path.'applications/core/modules_public/global/login.php');
		$this->login = new Ipbwi_Ips_Public_Core_Global_Login();
		$this->login->initHanLogin($this->registry); 
		
		// get registration function
		require_once($config->board_admin_path.'applications/core/modules_public/global/register.php');
		$this->register = new Ipbwi_Ips_Public_Core_Global_Register();
		$this->register->initRegister($this->registry);
		
		// deactivate redirect function
		require_once($config->board_admin_path.'sources/classes/output/publicOutput.php' );
		$this->registry->output = new Ipbwi_Ips_Output($this->registry, true);
		
		// get permission functions
		require_once($config->board_admin_path.'sources/classes/class_public_permissions.php');
		$this->perm = new \classPublicPermissions($this->registry);
		
		// get bbcode functions
		require_once($config->board_admin_path.'sources/handlers/han_parse_bbcode.php');
		$this->parser = new \parseBbcode($this->registry);
		
		// get messenger functions
		require_once($config->board_admin_path.'applications/members/sources/classes/messaging/messengerFunctions.php');
		$this->messenger = new \messengerFunctions($this->registry);
		
		// get member functions
		//require_once(ipbwi_BOARD_ADMIN_PATH.'sources/classes/member/memberFunctions.php');
		//$this->memberFunctions = new memberFunctions($this->registry);
		return self::$instance;
	}
	
	public function __construct(){		
	}
	
	public function memberDelete($id, $check_admin=false){
		if( !is_array($id) && !intval($id) )
		{
			$id = $this->member->member_id;
		}
		// first logout
		@$this->login->doLogout(false); // @ todo: check notices from ip.board
		// delete member
		$return = @IPSMember::remove($id, $check_admin); // @ todo: check notices from ip.board
		
        return $return === null ? true : false;
	}
	// return data of current member
	public function myInfo(){
		return $this->lang->memberData;
	}
	
	// change user's pw
	public function changePW($newPass, $member, $currentPass = false){
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$save_array = array();
		
		//-----------------------------------------
		// Generate a new random password
		//-----------------------------------------
		
		$new_pass = \IPSText::parseCleanValue( urldecode($newPass));
		
		//-----------------------------------------
		// Generate a new salt
		//-----------------------------------------
		
		$salt = IPSMember::generatePasswordSalt(5);
		$salt = str_replace( '\\', "\\\\", $salt );
		
		//-----------------------------------------
		// New log in key
		//-----------------------------------------
		
		$key  = IPSMember::generateAutoLoginKey();
		
		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$save_array['members_pass_salt']		= $salt;
		$save_array['members_pass_hash']		= md5( md5($salt) . md5( $new_pass ) );
		$save_array['member_login_key']			= $key;
		$save_array['member_login_key_expire']	= $this->settings['login_key_expire'] * 60 * 60 * 24;
		$save_array['failed_logins']			= null;
		$save_array['failed_login_count']		= 0;
		
		//-----------------------------------------
		// Load handler...
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/handlers/han_login.php', 'han_login' );
		$this->han_login =  new $classToLoad( $this->registry );
		$this->han_login->init();
		$this->han_login->changePass( $member['email'], md5( $new_pass ), $new_pass, $member );
		
		IPSMember::save( $member['member_id'], array( 'members' => $save_array ) );
		
		IPSMember::updatePassword( $member['member_id'], md5( $new_pass ) );
		IPSLib::runMemberSync( 'onPassChange', $member['member_id'], $new_pass );
	}
}
?>