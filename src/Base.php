<?php

namespace DentalAdvocacyCore\Core;

use DentalAdvocacyCore\Core\Admin\Admin;
use DentalAdvocacyCore\Core\PostTypes\PostType;
use DentalAdvocacyCore\Core\WooCommerce\WooInit;

class Base {
	
	public function __construct()
	{
		$this->admin_hooks();
		
		$this->user_hooks();
	}
	
	
	public function admin_hooks()
	{
		Admin::get_instance();
		PostType::get_instance();
		
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			WooInit::get_instance();
		}
	}
	
	public function user_hooks()
	{
	
	}
	
	/**
	 * Singleton.
	 *
	 * @return Base
	 */
	public static function get_instance()
	{
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}

