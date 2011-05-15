<?php

$config = require_once('config.inc.php');
// check if PHP version is 5 or higher

// Credit goes to the Fuel team for this... :)
// http://www.fuelphp.com/
define('DS', DIRECTORY_SEPARATOR);
function autoload($class) {
	if(!class_exists($class, false)) {
		$class = ltrim($class, '\\');
		$has_namespace = ($pos = strripos($class, '\\')) !== false;
	
		if($has_namespace) {
			$namespace = '\\'.ucfirst(strtolower(substr($class, 0, $pos)));
			
			$class_no_ns = substr($class, $pos + 1);
			$file_path = str_replace('_', DS, $class_no_ns);
			$file_path = __DIR__.DS.'classes'.DS.strtolower($file_path).'.php';
			if (is_file($file_path))
			{
				require_once($file_path);
			}
		} else {
			$file_path = str_replace('_', DS, $class);
			$file_path = __DIR__.DS.'classes'.DS.strtolower($file_path).'.php';
			
			if (file_exists($file_path))
			{
				require_once($file_path);
			}
		}
	}
}
spl_autoload_register('autoload', true, true);
$ipbwi = \Ipbwi\Ipbwi::instance()->init($config);