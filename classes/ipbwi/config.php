<?php

namespace Ipbwi;

class Ipbwi_Config {
	
	public $config = array();
	
	protected static $instance;
	
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
	
	private function __construct() {
	}
	
	/**
	 * @desc			Sets the config values specified in the array
	 * @param	array	$config
	 * @author			Scott Luther
	 * @since			3.1
	 * 
	 * @ignore
	 */
	
	public function set_config($config = array()) {
		if(!empty($config)) {
			foreach($config as $key => $value) {
				$this->config[$key] = $value;
			}
		}
	}
	
	public function __set($key, $value) {
		$this->config[$key] = $value;
		return $this->config[$key];
	}
	
	public function __get($key) {
		return $this->config[$key];
	}
	
	
}