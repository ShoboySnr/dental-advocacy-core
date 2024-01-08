<?php

namespace DentalAdvocacyCore\Core\WooCommerce;

class WooInit {
	
	public function __construct() {
		ProductsToCart::get_instance();
		ManageUserOrders::get_instance();
	}
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}