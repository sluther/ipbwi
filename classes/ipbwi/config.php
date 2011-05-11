<?php

namespace Ipbwi;

class Ipbwi_Config {
	
	public $config = array();
	
	public function __construct($config = array()) {
		
		if(!empty($config)) {
			foreach($config as $key => $value) {
				$this->config[$key] = $value;
			}
		}
		return $this;
		
	}
	
	public function __set($key, $value) {
		
		$this->config[$key] = $value;
		return $this->config[$key];
		
	}
	
	public function __get($key) {
		
		return $this->config[$key];
		
	}
	
	
}