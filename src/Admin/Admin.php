<?php

namespace DentalAdvocacyCore\Core\Admin;

class Admin {
	
	public function __construct() {
		AdminMenu::get_instance();
	}
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}