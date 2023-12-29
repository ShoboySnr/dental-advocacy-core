<?php

namespace DentalAdvocacyCore\Core\WooCommerce;

use DentalAdvocacyCore\Core\WooCommerce\ProductsToCart\Init as ProductsInit;

class WooInit {
	
	public function __construct() {
		ProductsInit::get_instance();
	}
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}