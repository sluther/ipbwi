<?php

namespace Ipbwi;

class Ipbwi_Config {
	
	public $config = array();
	
	protected static $instance;
	
	private function __construct() {
	}
	
	public function set_config($config = array()) {
		if(!empty($config)) {
			foreach($config as $key => $value) {
				$this->config[$key] = $value;
			}
		}
	}
	
	public static function instance() {
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
		}
		
		return self::$instance;
	}
	
	public function __set($key, $value) {
		$this->config[$key] = $value;
		return $this->config[$key];
	}
	
	public function __get($key) {
		return $this->config[$key];
	}
	
	
}