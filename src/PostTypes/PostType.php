<?php

namespace DentalAdvocacyCore\Core\PostTypes;

class PostType {
	
	public function __construct() {
		Vital::get_instance();
	}
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}