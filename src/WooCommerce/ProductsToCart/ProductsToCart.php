<?php

namespace DentalAdvocacyCore\Core\WooCommerce\ProductsToCart;

class ProductsToCart {
	
	public function __construct() {
	  add_action('init', [$this, 'products_to_cart_action_callback']);
	  add_action('wp_ajax_da_core_get_customer_names', [$this, 'suggests_users']);
	  add_action('wp_ajax_da_core_get_products', [$this, 'suggests_products']);
	  add_action('wp_ajax_da_core_prepare_products_to_cart', [$this, 'products_to_cart_action_callback']);
	}
  
  public function products_to_cart_action_callback()
  {
    if(isset($_POST['da-core-prepare-products-to-cart-submit'])) {
	    global $wpdb;
	    $error_string = $price = $product_level_stock = $stock_status = $incompatible_product_type = '';
      
      $product_ids = sanitize_text_field($_POST['product_ids']);
      $user_names = sanitize_text_field($_POST['user_names']);
      $quantity = sanitize_text_field($_POST['quantity']);
      $vitals = $_POST['vitals'];
	
	    $get_vitals = get_posts([
		    'numberposts'   => -1,
		    'post_type'     => 'da-core-vitals'
	    ]);
      
      if( ! empty ( $get_vitals) ) {
        foreach ($get_vitals as $get_vital) {
          $vitals[$get_vital->ID] = da_core_prepare($vitals[$get_vital->post_name]);
        }
      }
      
      /**
       * Required fields
       */
	    $empty_fields = [];
	    $required_fields = array('product_ids', 'quantity', 'user_names');
	    $empty_fields = array_merge($empty_fields, da_core_check_required_fields($required_fields));
	    $empty_fields = implode(", ", $empty_fields);
	
      /** Length Validation */
	    $too_long_fields = array();
	    $fields_max_lengths = array('quantity' => 10);
	    $too_long_fields = array_merge($too_long_fields, da_core_check_field_length($fields_max_lengths));
	    $too_long_fields = implode(", ", $too_long_fields);
      
      
      $product_ids =  $_POST["product_ids"];
      $user_names =  $_POST["user_names"];
      $quantity = da_core_prepare($_POST['quantity']);
      
      /** validate the products */
      foreach ($product_ids as $product_id) {
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
      
      if( $empty_fields !== '') {
	      $error_string .= "<div class='da-core-message da-core-message-error'>". __("Field(s) {$empty_fields} is/are required. Please try again.") . "</div>";
      }
	
	    if( $too_long_fields !== '') {
		    $error_string .= "<div class='da-core-message da-core-message-error'>". __("Field(s) {$empty_fields} is/are required. Please try again.") . "</div>";
	    }
      
      // validate user_names by the ID
      if(!empty($user_names)) {
        foreach ($user_names as $user_name) {
	        $query = "SELECT ID FROM {$wpdb->users} WHERE ID = '$user_name' LIMIT 1";
	        $user_id = $wpdb->get_results($query);
	
	        if(count($user_id) < 1) {
		        $error_string .= "<div class='da-core-message da-core-message-error'>". __("User with ID {$user_name} does not exist. Please try again.") . "</div>";
	        }
        }
      }
      
      
     
    }
  }
	
	public function admin_menu_add_to_cart_callback() {
		
    $vitals = get_posts([
        'numberposts'   => -1,
        'post_type'     => 'da-core-vitals'
    ]);
    
    $users = get_users([
	    'number'  => -1,
      'fields'  => ['ID', 'display_name'],
	    'orderby' => 'display_name',
	    'order' => 'ASC'
    ]);
		?>
		<div class="wrap" id="dental-advocacy-products-to-cart">
			<h1 class="wp-heading-inline">Products to Customer Cart</h1>
		  
      <div class="wp-filter">
        <!-- Description -->
        <div class='da-core-title-and-description'>
          <h3><?php echo __('Select Product(s) to add to Customer Cart', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></h3>
          <p>
            <?php echo __('When selected user logs in, products added here will appear in their shopping cart.
            This way, you can prepare customer\'s cart content for them - fill it with items you think that suits him best. ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?>
          </p>
          <form action="" method="post" id="dental-advocacy-products-add-to-cart-form">
            <div class='da-core-form'>
              <div class="">
                <label for="product_ids">Enter Product ID</label>
                <select name="product_ids[]" id="product_ids" class="da-core-product-ids-select2" multiple required>
                  <option value=""><?php echo __('Find products by ID or Title..', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></option>
                </select>
              </div>
              <div class="">
                <label for="user_names">Select Customer</label>
                <select name="user_names[]" id="user_names" class="da-core-customer-details-select2" multiple required>
                  <option value=""><?php echo __('Select customers..', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></option>
                </select>
              </div>
              <div class="">
                <label for="quantity">Enter Quantity</label>
                <input  type='number' name='quantity' id='quantity' placeholder="<?php echo __('Quantity', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?>" min="1" />
              </div>
              <input  type='hidden' name='product_id' value=""/>
              <input  type='hidden' name='action' value='da_core_prepare_products_to_cart' />
	            <?php
		            foreach ($vitals as $vital) {
			            ?>
                      <div class="">
                        <label for="<?php echo $vital->post_name. '_'. $vital->ID; ?>"><?php echo $vital->post_title; ?></label>
                        <input  type='text' name='vitals[<?php echo $vital->post_name; ?>]' id='<?php echo $vital->post_name. '_'. $vital->ID; ?>' placeholder="<?php echo __('Enter value', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?> " />
                      </div>
		  
		            <?php } ?>

            </div>
            <hr />
            <br />
            <button type='submit' name="da-core-prepare-products-to-cart-submit" class='button da-core-prepare-products-to-cart-submit button-primary'><?php echo __('Add to customer\'s cart', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></button>

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
	}
  
  
  public function suggests_users() {
	  global $wpdb;
   
	  $name = $wpdb->esc_like(stripslashes($_POST['user_name'])) . '%'; // escape for use in LIKE statement
	  $query = "SELECT * FROM {$wpdb->prefix}users WHERE display_name LIKE %s ORDER BY user_login ASC";
	
	  $query = $wpdb->prepare($query, $name);
	
	  $results = $wpdb->get_results($query);
	
	  $users = array();
	  foreach ($results as $result)
		  $users[] = [
          'id'    => intval($result->ID),
          'text'  => addslashes($result->display_name)
      ];
	
	  echo json_encode($users);
	
	  wp_die();
  }
	
	public function suggests_products() {
		global $wpdb;
		
		$product_id = $wpdb->esc_like(stripslashes($_POST['product_id'])) . '%'; // escape for use in LIKE statement
		$query = "SELECT * FROM {$wpdb->posts} WHERE ( post_title LIKE %s OR ID LIKE %s) AND post_type = 'product' ORDER BY post_title ASC";
		
		$query = $wpdb->prepare($query, $product_id, $product_id);
		
		$results = $wpdb->get_results($query);
		
		$users = array();
		foreach ($results as $result)
			$users[] = [
				'id'    => intval($result->ID),
				'text'  => addslashes($result->post_title)
			];
		
		echo json_encode($users);
		
		wp_die();
	}
	
	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}