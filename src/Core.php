<?php

namespace DentalAdvocacyCore\Core;

class Core {
	
	public function __construct()
	{
		RegisterScripts::get_instance();
		Base::get_instance();
	}
	
	/**
	 * @return \DentalAdvocacyCore\Core\Core|null
	 */
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
	
	/**
	 *
	 */
	public static function init()
	{
		Core::get_instance();
	}
}