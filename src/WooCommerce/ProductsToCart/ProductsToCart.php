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
      
      // json encode vitals
      $vitals = json_encode($vitals);
      if(empty($quantity)) $quantity = 1;
      
      if( empty( $error_string ) ) {
        
        foreach ($product_ids as $product_id) {
	        $_product = wc_get_product( $product_id );
          foreach ($user_names as $user_id) {
            $user = get_user_by('ID', $user_id);
	          $select_sql = "SELECT * FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE `user_id` = %s AND `product_id` = %s LIMIT 1";
	          $select_sql = $wpdb->prepare($select_sql, $user_id, $product_id );
	          $find_prev = $wpdb->get_results($select_sql);
            
            
            if(empty( $find_prev) ) {
              $insert_sql = "INSERT INTO {$wpdb->prefix}da_core_products_to_cart_items (`user_id`, `product_id`, `quantity`, `metadata`) VALUES (%s, %s, %s, %s)";
              $insert_sql = $wpdb->prepare($insert_sql, $user_id, $product_id, $quantity, $vitals );
              $wpdb->get_results($insert_sql);
	
	            if ($wpdb->last_error) {
	              $wpdb_error_string = "<div class='da-core-message da-core-message-error'>" . __('Could not connect: ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . $wpdb->last_error . "</div>";
	              wp_send_json(['success' => false, 'message' => $wpdb_error_string ], 400);
              }
            } else {
	            $exist_error_string = "<div class='da-core-message da-core-message-error'>" . __('This product - '.$_product->get_title(). ' is already added to this user - '.$user->display_name , DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . $wpdb->last_error . "</div>";
	            wp_send_json(['success' => false, 'message' => $exist_error_string ], 400);
            }
          }
        }
	
        $success_string = "<div class='da-core-message da-core-message-success'>". __(' Cart will be updated on login. ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "<a href='" . admin_url( 'admin.php?page=da-core-add-to-cart') . "'>" . __('Refresh this page', ATCAA_TEXT_DOMAIN) . "</a></div>";
	      wp_send_json(['success' => true, 'message' => $success_string ]);
       
      } else {
        wp_send_json(['success' => false, 'message' => $error_string ], 400);
      }
	    wp_die();
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
                <input  type='number' value="1" name='quantity' id='quantity' placeholder="<?php echo __('Quantity', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?>" min="1" />
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
    $this->view_add_to_cart_admin_overview();
	}
	
	/**
	 *
	 */
  public function view_add_to_cart_admin_overview()
  {
  ?>
  <div class="wrap add-to-cart-admin-overview">
    <h2>Add to Cart Admin Overview</h2>
    <p>These items will be added to user's cart when he logs in. Until then, you can remove items added by mistake.
      After user place order, items for that user will be automatically removed from this page.</p>

    <div id='da-core-overview-feedback'></div>
    
      <?php
          $user_ids = $this->query_add_to_cart_users();
          if(!empty($user_ids)) {
            
            foreach ($user_ids as $user_id) {
              $id = $user_id->user_id;
              $user = get_user_by('ID', $id);
              $row_number = 0;
              
              $cart_details = $this->get_add_to_carts_details_by_user_id($id);
      ?>
        <table id="da-core-table-user-id-<?php echo $id; ?>" class="da-core-overview-table widefat fixed striped">
          <caption>
            <span><?php echo $user->display_name; ?></span>
          </caption>
          
          <thead>
            <tr>
              <td class='atcaa-table-row-number'><?php echo __('#', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?></td>
              <td><?php echo __('Product Name', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?> </td>
              <td><?php echo __('Quantity', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?> </td>
              <td><?php echo __('Price', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) ?> </td>
              <td><?php echo __('Meta Data', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) ?> </td>
              <td><?php echo __('Status', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) ?> </td>
            </tr>
          </thead>
          
          <tbody>
          <?php
              foreach ($cart_details as $cart_detail) {
	              $row_number++;
                $product = wc_get_product($cart_detail->product_id);
                $metadata = json_decode($cart_detail->metadata);
          ?>
          <tr class="entry-id-<?php echo $cart_detail->id; ?>">
              <td><?php echo $row_number ?></td>
              <td><?php echo $product->get_title(); ?></td>
            <td><?php echo $cart_detail->qntty; ?></td>
            <td><?php echo $product->get_price_html(); ?></td>
            <td>
                <?php
                    foreach ($metadata as $key => $data) {
	                    $my_vitals = get_posts([
		                    'name'        => $key,
		                    'numberposts'   => 1,
		                    'post_type'     => 'da-core-vitals'
	                    ]);
                      
                      if(!empty($my_vitals[0])) echo "<div class='da-core-metadata'><span>". $my_vitals[0]->post_title. ":</span> ". $data . "</div>";
                    }
                ?>
              
            </td>
            <td>
                <?php
                    if($cart_detail->imported_to_cart == 0) {
                      ?>
                        <button class="button button-primary da-core-entry-delete-button" value="<?php echo $cart_detail->id; ?>">Delete</button>
                      <?php
                    } else {
                      ?>
                        <span class="da-core-already-in-cart">Already added to cart</span>
                      <?php
                    }
                ?>
            </td>
            
          </tr>
          
          <?php
              
              }
          ?>
          </tbody>
        </table>
      <?php
            }
          }
      ?>
  </div>
  <?php
  }
  
  
  public function query_add_to_cart_users() {
    global $wpdb;
	  $query = "SELECT DISTINCT user_id FROM {$wpdb->prefix}da_core_products_to_cart_items";
    $user_ids = $wpdb->get_results($query);
    
    return $user_ids;
  }
  
  
  public function get_add_to_carts_details_by_user_id($user_id) {
    global $wpdb;
	
	  $query = "SELECT  id,product_id,imported_to_cart, metadata, SUM(quantity) qntty FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE user_id = $user_id GROUP BY id";
	  $details_per_user_id = $wpdb->get_results($query);
    
    return $details_per_user_id;
    
    
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