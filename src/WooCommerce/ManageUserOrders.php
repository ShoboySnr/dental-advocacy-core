<?php

namespace DentalAdvocacyCore\Core\WooCommerce;

class ManageUserOrders {
	
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
    
    add_action( 'wp_ajax_da_core_add_products_to_user', [$this, 'add_products_to_user_callback']);
	
	}
  
  
  public function add_products_to_user_callback() {
    if(isset($_POST['da_core_add_products_to_users_nonce']) && wp_verify_nonce('da-core-add-products-to-user-nonce', 'da_core_add_products_to_users_nonce')) {
	    global $wpdb;
	    $error_string = $price = $product_level_stock = $stock_status = $incompatible_product_type = '';
	
	    $product_ids =  $_POST["product_ids"];
	    $user_names =  $_POST["user_names"];
	
	    /** validate the products */
	    foreach ($product_ids as $product_id) {
		    $error_string .= $this->validate_products($product_id);
	    }
	
	    // validate user_names by the ID
	    if(!empty($user_names)) {
		    foreach ($user_names as $user_name) {
			    $error_string .= $this->validate_products($user_name);
		    }
	    }
     
	    if(empty($quantity)) $quantity = 1;
	
	    if( empty( $error_string ) ) {
      
      }
    }
  }
  
  public function validate_usernames ( $user_name ) {
	  $error_string = '';
    if(!empty($user_name)) {
	    global $wpdb;
	    $query = "SELECT ID FROM {$wpdb->users} WHERE ID = '$user_name' LIMIT 1";
	    $user_id = $wpdb->get_results($query);
	
	    if(count($user_id) < 1) {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("User with ID {$user_name} does not exist. Please try again.") . "</div>";
	    }
    }
    
    return $error_string;
  }
  
  public function validate_products ( $product_id ) {
    $error_string = '';
    if(!empty($product_id)) {
	    $product_level_stock = get_post_meta($product_id, '_manage_stock', true);
	    $stock_status = get_post_meta($product_id, '_stock_status', true);
	    $price = get_post_meta($product_id, '_price', true);
	    $_product = wc_get_product( $product_id );
	
	    if ($_product->is_type( 'grouped')) {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} is a grouped product and can not be added to cart") . "</div>";
	    }
	
	    if ($_product->is_type( 'external')) {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} is an external product and can not be added to cart") . "</div>";
	    }
	
	    if ($_product->is_type( 'variable')) {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} is a variable product and can not be added to cart") . "</div>";
	    }
	
	    if ($product_level_stock !== 'no') {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} level is at stock management and can not be added to cart") . "</div>";
	    }
	
	    if ($stock_status !== 'instock') {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} is currently out of stock") . "</div>";
	    }
	
	    if ($price == '') {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Sorry, this product {$_product->get_title()} is without price") . "</div>";
	    }
    }
    return $error_string;
  }
	
	public function admin_menu_manage_user_orders_callback() {
		
		$vitals = get_posts([
			'numberposts'   => -1,
			'post_type'     => 'da-core-vitals'
		]);
  
		?>
		<div class="wrap" id="dental-advocacy-products-to-cart">
			<h1 class="wp-heading-inline"><?php echo __('Manage User Orders', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></h1>
			
			<div class="wp-filter">
				<!-- Description -->
				<div class='da-core-title-and-description'>
					<h3><?php echo __('Add Subscription Products to Existing Users', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></h3>
					<p>
						<?php echo __('When selected user logs in, products added here will appear in their shopping cart.
            This way, you can prepare customer\'s cart content for them - fill it with items you think that suits him best. ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?>
					</p>
					<form action="" method="post" id="dental-advocacy-add-products-to-user-form">
						<div class='da-core-form'>
							<div class="">
								<label for="user_names">Select Customer</label>
								<select name="user_names[]" id="user_names" class="da-core-customer-details-select2" multiple required>
									<option value=""><?php echo __('Select customers..', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></option>
								</select>
							</div>
							<div class="">
								<label for="product_ids">Select Subscription Products</label>
								<select name="product_ids[]" id="product_ids" class="da-core-product-ids-select2" multiple required>
									<option value=""><?php echo __('Find products by ID or Title..', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></option>
								</select>
							</div>
							
<!--							<div class="">-->
<!--								<label for="quantity">Enter Quantity</label>-->
<!--								<input  type='number' value="1" name='quantity' id='quantity' placeholder="--><?php //echo __('Quantity', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?><!--" min="1" required/>-->
<!--							</div>-->
<!--							--><?php
//								foreach ($vitals as $vital) {
//									?>
<!--									<div class="">-->
<!--										<label for="--><?php //echo $vital->post_name. '_'. $vital->ID; ?><!--">--><?php //echo $vital->post_title; ?><!--</label>-->
<!--										<input  type='text' name='vitals[--><?php //echo $vital->post_name; ?><!--]' id='--><?php //echo $vital->post_name. '_'. $vital->ID; ?><!--' placeholder="--><?php //echo __('Enter value', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?><!-- " />-->
<!--									</div>-->
<!--								-->
<!--								--><?php //} ?>
						
						</div>
            <div class="da-core-form da-core-add-products-to-user-next-step"></div>
						<hr />
						<br />
						<input  type='hidden' name='action' value='da_core_add_products_to_user' />
						<?php wp_nonce_field('da-core-add-products-to-user-nonce', 'da_core_add_products_to_users_nonce'); ?>
						<button type='submit' name="da-core-add-products-to-user-submit" class='button da-core-add-products-to-user-submit button-primary'><?php echo __('Add to customer\'s order', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></button>
						
						<div id='da-core-form-feedback' class='da-core-form-feedback'></div>
						
						<div class='da-core-loading-overlay'></div>
						<div class='da-core-loader-image'>
							<div class='da-core-loader-image-inner'></div>
						</div>
					</form>
				
				</div>
			</div>
		</div>
		<?php
//		$this->view_add_to_cart_admin_overview();
	}
	
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}