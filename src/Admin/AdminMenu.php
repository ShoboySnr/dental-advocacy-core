<?php

namespace DentalAdvocacyCore\Core\Admin;

use DentalAdvocacyCore\Core\WooCommerce\ProductsToCart;
use DentalAdvocacyCore\Core\WooCommerce\ManageUserOrders;

class AdminMenu {
	
	public function __construct()
	{
		add_action( 'admin_menu', [$this, 'admin_menu'] );
  
	}
	
	/**
	 * Admin Menu Hook
	 */
	public function admin_menu()
	{
	  add_menu_page(
			'Dental Advocacy Core',
			'Dental Advocacy',
			'manage_options',
			'dental-advocacy-core',
			[$this, 'admin_menu_callback'],
			'',
        5
		);
    
    // Add posttype to change the vitals
	  add_submenu_page(
		  'dental-advocacy-core',
		  'Vitals',
		  'Vitals',
		  'manage_options',
		  'edit.php?post_type=da-core-vitals'
	  );
	  
	  // Add To Cart sub menu
	  add_submenu_page(
		  'dental-advocacy-core',
		  'Products to Cart',
		  'Products To Customer Cart',
		  'manage_options',
		  'da-core-add-to-cart',
		  [ProductsToCart::get_instance(), 'admin_menu_add_to_cart_callback']
	  );
    
    // Manage Orders for a User
	  add_submenu_page(
		  'dental-advocacy-core',
		  'Manage User Orders',
		  'Manage User Orders',
		  'manage_options',
		  'da-core-manage-user-orders',
		  [ManageUserOrders::get_instance(), 'admin_menu_manage_user_orders_callback']
	  );
    
    // Add settings sub menu
	  add_submenu_page(
        'dental-advocacy-core',
        'Dental Advocacy',
        'Settings',
        'manage_options',
        'dental-advocacy-core-settings',
        [$this, 'admin_menu_callback']
    );
	}
	
	
	public function admin_menu_callback() {
		?>
		<div class="wrap">
			<h1>Dental Advocacy</h1>
			
			
		</div>
		<?php
	}
	
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}