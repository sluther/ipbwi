<?php

return array(
	/**
	 * The full qualified filesystem path to the folder of your IPB installation.
	 * You must add a trailing slash.
	 *
	 * Example path: '/home/public_html/community/forums/'
	 */

	'board_path' => '',
	/**
	 * The full qualified filesystem path to the folder of your IPB Admin directory.
	 * You must add a trailing slash.
	 *
	 * Example path: '/home/public_html/community/forums/admin/'
	 */
	'board_admin_path' => '',
	
	/**
	 * The base-URL of your website. This is needed to get the live-examples viewed properly.
	 * You must add a trailing slash.
	 *
	 * Example url: 'http://ipbwi.com/examples/'
	 */
	'web_url' => '',

	/**
	 * Make login possible on a different domain as the domain where the board is installed.
	 *
	 * If not set, the board's cookie domain will be used.
	 * Do not touch this setting, if you don't know how to use it.
	 *
	 * Please insert a dot before the domain.
	 * Example: .domain.com
	 * Example for subdomain: .site.domain.com
	 */
	'cookie_domain' => '',

	/**
	 * IP.board 2 does not support natively UTF-8 character encoding.
	 * Turn this option to true, if you want to get all output-strings
	 * in UTF-8 encoding, otherwise turn to false to get them in ISO encoding.
	 */
	'utf8' => true,

	/**
	 * If you want to define another prefix for ipbwi-tables in your board's database,
	 * you are able to define it here.
	 */
	'db_prefix' => 'ipbwi_',

	/**
	 * The Default IPBWI Language Pack.
	 *
	 * Language packs should be named XX.inc.php where 'XX' is the
	 * language and be situated in the lib/lang/ folder.
	 * By default, this uses the "en" (English) language pack.
	 */
	'lang' => 'en',

	/**
	 * Set a forced encoding.
	 * 
	 * If you set a encoding here this encoding will be forced instead
	 * of the encoding that is given in the language pack you use.
	 * By default false
	 * e.g. give value like 'ISO-8859-1'
	 * Notice: This will also overwrite ipbwi_UTF8!
	 */
	'overwrite_encoding' => false,

	/**
	 * Set a forced localisation.
	 * 
	 * If you set a localisation here this localisation will be forced
	 * instead of the localisation given in the language pack you use.
	 * By default false
	 * e.g. give value like 'de_DE'
	 * More informations: http://php.net/setlocale
	 */
	'overwrite_locale' => false,

	/**
	 * The IPBWI captcha mode.
	 *
	 * Choose between 'gd' for forcing a GD based captcha, 'recaptcha' for using reCaptcha.
	 * Otherwise you can choose 'auto', this will take the method that is configured in
	 * your IP.Board.
	 */
	'captcha_mode' => 'auto',
	
	/**
	 * The recaptcha public key from the board
	 */
	'recaptcha_public' => '6Lcl1rwSAAAAAG3JJSiAnTyAPwO8BQAZDegKIUIJ ',
	
	/**
	 * The recaptcha private key from the board
	 */
	'recaptcha_private' => '6Lcl1rwSAAAAADYb2N92hphwEzV41gHwMmKme2wt ',
	
	/**
	 * Set on 'true' if you use the IPBWI in your IPB installation, otherwise 'false'
	 */
	'in_ipb' => false,


);