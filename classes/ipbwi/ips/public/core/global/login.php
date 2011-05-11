<?php
namespace Ipbwi;
class Ipbwi_Ips_Public_Core_Global_Login extends \public_core_global_login {

	// load login handler. these functions are the base for login and logout
	public function initHanLogin($core=false)
	{
		/* Make object */
		$this->registry   = $core;
		$this->DB         = $this->registry->DB();
		$this->settings   = $this->registry->fetchSettings();
		$this->request    = $this->registry->fetchRequest();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData = $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     = $this->registry->cache()->fetchCaches();
	
    	require_once( IPS_ROOT_PATH . 'sources/handlers/han_login.php' );
    	$this->han_login =  new \han_login($this->registry);
    	$this->han_login->init();
	}
	
	public function doLogin()
    {
		return $this->han_login->verifyLogin(); // @ todo: check notices from ip.board
	}
}

?>