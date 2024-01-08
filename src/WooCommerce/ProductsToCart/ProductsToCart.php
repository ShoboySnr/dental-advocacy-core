<?php

namespace DentalAdvocacyCore\Core\WooCommerce\ProductsToCart;

class ProductsToCart {
	
	public function __construct() {
	  
    add_action('init', [$this, 'products_to_cart_action_callback']);
	  
    add_action('wp_ajax_da_core_get_customer_names', [$this, 'suggests_users']);
	  
    add_action('wp_ajax_da_core_get_products', [$this, 'suggests_products']);
    
    add_action('wp_ajax_da_core_prepare_products_to_cart', [$this, 'products_to_cart_action_callback']);
    
    add_action('wp_ajax_da_core_modify_meta_details', [$this, 'modify_meta_details_action_callback']);
    
    add_action('wp_ajax_da_core_cancel_meta_details', [$this, 'cancel_meta_details_action_callback']);
    
    add_action('wp_ajax_da_core_delete_product_carts_entry', [$this, 'delete_product_carts_entry_action_callback']);
    
    add_action( 'wp', [$this, 'da_core_add_to_cart'] );
	  
	  add_action('save_post_shop_order', [$this, 'delete_from_prepared_items_table'] );
	  
	  add_filter( 'woocommerce_get_item_data', [$this, 'get_item_data'], 10, 2 );
	  
	  add_action( 'woocommerce_checkout_create_order_line_item', [$this, 'checkout_create_order_line_item'], 10, 4 );
	  
	  add_action('wp_ajax_da_core_get_meta_details_form', [$this, 'get_meta_details_form']);
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
            $find_prev = $this->get_product_to_cart_detail($user_id, $product_id);
            
            
            if(empty( $find_prev) ) {
              $insert_sql = "INSERT INTO {$wpdb->prefix}da_core_products_to_cart_items (`user_id`, `product_id`, `quantity`, `metadata`) VALUES (%s, %s, %s, %s)";
              $insert_sql = $wpdb->prepare($insert_sql, $user_id, $product_id, $quantity, $vitals );
              $wpdb->get_results($insert_sql);
	
	            if ($wpdb->last_error) {
	              $wpdb_error_string = "<div class='da-core-message da-core-message-error'>" . __('Could not connect: ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . $wpdb->last_error . "</div>";
	              wp_send_json(['success' => false, 'message' => $wpdb_error_string ]);
              }
            } else {
	            $exist_error_string = "<div class='da-core-message da-core-message-error'>" . __('This product - '.$_product->get_title(). ' is already added to this user - '.$user->display_name , DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . $wpdb->last_error . "</div>";
	            wp_send_json(['success' => false, 'message' => $exist_error_string ]);
            }
          }
        }
	
        $success_string = "<div class='da-core-message da-core-message-success'>". __(' Cart will be updated on login. ', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "<a href='" . admin_url( 'admin.php?page=da-core-add-to-cart') . "'>" . __('Refresh this page', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "</a></div>";
	      wp_send_json(['success' => true, 'message' => $success_string ]);
       
      } else {
        wp_send_json(['success' => false, 'message' => $error_string ]);
      }
	    wp_die();
    }
  }
  
  
  public function modify_meta_details_action_callback() {
	
	  if(isset($_POST['modify_meta_details_nonce']) && wp_verify_nonce($_POST['modify_meta_details_nonce'], 'modify-meta-data-nonce')) {
      global $wpdb;
      
      $vitals = $_POST['vitals'];
      $user_id = sanitize_text_field($_POST['user_id']);
      $product_id = sanitize_text_field($_POST['product_id']);
      
      //get the product to cart details where user_id and product_id
	    $results = $this->get_product_to_cart_detail($user_id, $product_id);
      
      if(empty($results)) {
	      $exist_error_string = "<div class='da-core-message da-core-message-error'>" . __('Product cart details not found for this record, refresh and try again.', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "</div>";
	      wp_send_json(['success' => false, 'message' => $exist_error_string ]);
      }
	  
	    $query = "UPDATE {$wpdb->prefix}da_core_products_to_cart_items SET `metadata` = %s  WHERE `product_id` = %s AND `user_id` = %s";
	    $query = $wpdb->prepare($query, json_encode($vitals), $product_id, $user_id); // 2 signifies that changes has been made to the cart, hence rerun the cart hook function when a user logs in
	    $wpdb->get_results($query);
      
      //update all the cart items to imported_to_cart to 2
      $get_results = $this->get_add_to_carts_details_by_user_id($user_id);
      if(!empty($get_results)) {
	      $query = "UPDATE {$wpdb->prefix}da_core_products_to_cart_items SET `imported_to_cart` = %s  WHERE `user_id` = %s";
	      $query = $wpdb->prepare($query, 2, $user_id); // 2 signifies that changes has been made to the cart, hence rerun the cart hook function when a user logs in
	      $wpdb->get_results($query);
      }
	  
	    $success_string = $this->get_products_cart_meta_html($vitals);
	  
	    $success_string .= '<hr /><button class="button button-secondary da-core-metadata-update-button" data-product-id="'. $product_id. '" data-user-id="'. $user_id. '" data-modify-meta-data-nonce="'. wp_create_nonce('modify-meta-data-nonce') . '">Modify</button>';
      
      wp_send_json(['success' => true, 'message' => $success_string ]);
      
    }
    
    wp_die();
  }
	
	/**
	 * Cancel meta details form and show the meta listing
	 */
  public function cancel_meta_details_action_callback() {
	
	  $user_id = $_POST['user_id'];
	  $product_id = $_POST['product_id'];
	
	  //get the product to cart details where user_id and product_id
	  $results = $this->get_product_to_cart_detail($user_id, $product_id);
    
    if(empty($results[0])) {
	    $exist_error_string = "<div class='da-core-message da-core-message-error'>" . __('Product cart details not found for this record, refresh and try again.', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "</div>";
	    wp_send_json(['success' => false, 'message' => $exist_error_string ]);
    }
   
	  $success_string = $this->get_products_cart_meta_html(json_decode($results[0]->metadata));
	
	  $success_string .= '<hr /><button class="button button-secondary da-core-metadata-update-button" data-product-id="'. $product_id. '" data-user-id="'. $user_id. '" data-modify-meta-data-nonce="'. wp_create_nonce('modify-meta-data-nonce') . '">Modify</button>';
   
	  wp_send_json(['success' => true, 'message' =>  $success_string ]);
  }
  
  
  public function delete_product_carts_entry_action_callback() {
    
    if(isset($_POST['product_carts_entry_delete_nonce']) && wp_verify_nonce($_POST['product_carts_entry_delete_nonce'], 'product-carts-entry-delete-data-nonce')) {
	    global $wpdb;
      
      $user_id = $_POST['user_id'];
	    $product_id = $_POST['product_id'];
	
	    //get the product to cart details where user_id and product_id
	    $results = $this->get_product_to_cart_detail($user_id, $product_id);
	
	    if(empty($results[0])) {
		    $exist_error_string = "<div class='da-core-message da-core-message-error'>" . __('Product cart details not found for this record, refresh and try again.', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN) . "</div>";
		    wp_send_json(['success' => false, 'message' => $exist_error_string ]);
	    }
	
	
	    $query = "DELETE FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE product_id = %s AND user_id = %s";
      $query = $wpdb->prepare($query, $product_id, $user_id);
	    $wpdb->get_results($query);
	
	    wp_send_json(['success' => true, 'message' => 'This specific product cart is successfully deleted' ]);
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
                <input  type='number' value="1" name='quantity' id='quantity' placeholder="<?php echo __('Quantity', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?>" min="1" required/>
              </div>
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
  
  public function da_core_add_to_cart()
  {
    if ( ! is_admin() ) {
      
      global $wpdb;
      
      $current_user = wp_get_current_user();
      if(empty($current_user)) return;
      
      $query = "SELECT * FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE `user_id` = %s AND ( `imported_to_cart` = %s OR `imported_to_cart` = %s)";
      $query = $wpdb->prepare($query, $current_user->ID, 0, 2);
      $results = $wpdb->get_results($query);
      
      // check if there are existing products in cart -  to handle when there is updates, remove all the carts and add them back later
      $get_carts = WC()->cart->get_cart();
      if(!empty($get_carts)) {
	      foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
          WC()->cart->remove_cart_item( $cart_item_key );
	      }
      }
      
      if(!empty($results)) {
        foreach ($results as $result) {
	        $vitals = json_decode($result->metadata);
          $add_to_cart = WC()->cart->add_to_cart($result->product_id, $result->quantity, $result->variation_id, [], ['vitals' => $vitals]);
          
          if(!empty($add_to_cart) ) {
            // update the add to cart value to 1
            $query = "UPDATE {$wpdb->prefix}da_core_products_to_cart_items SET `imported_to_cart` = %s WHERE `product_id` = %s AND `user_id` = %s";
            $query = $wpdb->prepare($query, 1, $result->product_id, $current_user->ID);
            $wpdb->get_results($query);
          }
        }
      }
      
    }
  }
	
	/**
	 * Delete the cart items in the table after checking out
   *
	 */
  public function delete_from_prepared_items_table() {
	  global $wpdb;
	
	  $current_user = wp_get_current_user();
	  if ( empty($current_user ) ) return;
	
	  $query = "DELETE FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE imported_to_cart = '1' AND user_id = '$current_user->ID'";
	  $wpdb->get_results($query);
  }
  
  public function get_product_to_cart_detail($user_id, $product_id) {
    global $wpdb;
    
	  $query = "SELECT * FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE `user_id` = %s AND `product_id` = %s LIMIT 1";
	  $query = $wpdb->prepare($query, $user_id, $product_id );
	  $results = $wpdb->get_results($query);
    
    return $results;
  }
	
	
	/**
	 * @param $vitals
	 *
	 * @return string
	 */
  public function get_products_cart_meta_html($vitals) {
	  $meta_html = '';
    foreach ($vitals as $key => $data) {
		  if ( ! empty( $data ) ) {
			  $my_vitals = get_posts( [
				  'name'        => $key,
				  'numberposts' => 1,
				  'post_type'   => 'da-core-vitals'
			  ] );
			
			  if ( ! empty( $my_vitals[0] ) ) {
		    $meta_html .= "<div class='da-core-metadata'><span>" . $my_vitals[0]->post_title . ":</span> " . $data . "</div>";
			  }
		  }
	  }
    
    return $meta_html;
  }
	
	/**
	 * Display custom item data in the cart
	 */
  public function get_item_data( $item_data, $cart_item_data )
  {
	  if ( isset( $cart_item_data['vitals'] ) ) {
      global $wpdb;
      
      $vitals = $cart_item_data['vitals'];
      foreach ($vitals as $key => $vital) {
        if(!empty($vital)) {
	        $vital_name = $this->get_core_vitals_name($key);
          if(!empty($vital_name[0])) {
	          $item_data[] = [
		          'key'   => $vital_name[0]->post_title,
		          'name'   => $vital_name[0]->post_title,
		          'value' => wc_clean( $vital ),
	          ];
          }
        }
      }
    }
    
    return $item_data;
  }
	
	
	/**
	 * Add custom meta to order
	 */
  public function checkout_create_order_line_item(  $item, $cart_item_key, $values, $order ) {
    if(isset( $values['vitals'] )) {
	    $vitals = $values['vitals'];
	    
      foreach ($vitals as $key => $vital) {
	      $vital_name = $this->get_core_vitals_name($key);
	      if(!empty($vital_name[0])) {
	        $item->add_meta_data(
	          $vital_name[0]->post_title,
		        $vital,
		        true
	        );
        }
      }
    }
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

    <div id='da-core-overview-feedback' class="da-core-overview-loading-overlay"></div>
    <div class='da-core-overview-loader-image'></div>
    
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
          <tr class="entry-id-<?php echo $cart_detail->id; ?>" data-product-id="<?php echo $cart_detail->product_id; ?>" data-user-id="<?php echo $id; ?>">
              <td><?php echo $row_number ?></td>
              <td><?php echo $product->get_title(); ?></td>
            <td><?php echo $cart_detail->qntty; ?></td>
            <td><?php echo $product->get_price_html(); ?></td>
            <td>
                <?php
                    foreach ($metadata as $key => $data) {
	                    if ( ! empty( $data ) ) {
                        $my_vitals = get_posts( [
                          'name'        => $key,
                          'numberposts' => 1,
                          'post_type'   => 'da-core-vitals'
                        ] );
    
                        if ( ! empty( $my_vitals[0] ) ) {
                          echo "<div class='da-core-metadata'><span>" . $my_vitals[0]->post_title . ":</span> " . $data . "</div>";
                        }
                      }
                    }
                ?>
                <hr />
                <button class="button button-secondary da-core-metadata-update-button" data-product-id="<?php echo $cart_detail->product_id; ?>" data-user-id="<?php echo $cart_detail->user_id; ?>" data-modify-meta-data-nonce="<?php echo wp_create_nonce('modify-meta-data-nonce') ?>">Modify</button>
              
            </td>
            <td>
                <?php
                    if($cart_detail->imported_to_cart == 0) {
                      ?>
                        <button class="button button-primary da-core-entry-delete-button" value="<?php echo $cart_detail->id; ?>" data-nonce="<?php echo wp_create_nonce('product-carts-entry-delete-data-nonce') ?>">Delete</button>
                      <?php
                    } else if ($cart_detail->imported_to_cart == 2) {
                      ?>
                        <span class="da-core-already-in-cart">Cart items changed, will be updated when user logs in</span>
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
  
  public function get_core_vitals_name($post_name) {
	  $my_vitals = get_posts( [
	    'name'        => $post_name,
	    'numberposts' => 1,
	    'post_type'   => 'da-core-vitals'
    ] );
    
    return $my_vitals;
  }
  
  public function get_add_to_carts_details_by_user_id($user_id) {
    global $wpdb;
	
	  $query = "SELECT  id,user_id, product_id,imported_to_cart, metadata, SUM(quantity) qntty FROM {$wpdb->prefix}da_core_products_to_cart_items WHERE user_id = $user_id GROUP BY id";
	  $details_per_user_id = $wpdb->get_results($query);
    
    return $details_per_user_id;
  }
	
	/**
	 * Get the meta details form
   *
	 */
	public function get_meta_details_form()
	{
    if(isset($_POST['modify_meta_details_nonce']) && wp_verify_nonce($_POST['modify_meta_details_nonce'], 'modify-meta-data-nonce')) {
      $product_id = sanitize_text_field($_POST['product_id']);
      $user_id = sanitize_text_field($_POST['user_id']);
	
	    $vitals = get_posts([
		    'numberposts'   => -1,
		    'post_type'     => 'da-core-vitals'
	    ]);
      
      $get_vitals = $this->get_product_to_cart_detail($user_id, $product_id);
      $metadata = isset($get_vitals[0]) ? json_decode($get_vitals[0]->metadata) : [];

      ob_start();
      ?>
        <form action="post" class="dental-advocacy-modify-meta-details-form" id="dental-advocacy-modify-meta-details-form">
	        <?php
		        foreach ($vitals as $vital) {
              $post_name = $vital->post_name;
              $vital_value = $metadata->$post_name ?? '';
			        ?>
                  <div class="">
                    <label for="<?php echo $vital->post_name. '_'. $vital->ID; ?>"><?php echo $vital->post_title; ?></label>
                    <input value="<?php echo $vital_value; ?>"  type='text' name='vitals[<?php echo $vital->post_name; ?>]' id='<?php echo $vital->post_name. '_'. $vital->ID; ?>' placeholder="<?php echo __('Enter value', DENTAL_ADVOCACY_CORE_TEXT_DOMAIN); ?> " />
                  </div>
		
		        <?php } ?>
            <input name="product_id" value="<?php echo $product_id; ?>" type="hidden" />
            <input name="user_id" value="<?php echo $user_id; ?>" type="hidden" />
            <input name="action" value="da_core_modify_meta_details" type="hidden" />
            <?php wp_nonce_field('modify-meta-data-nonce', 'modify_meta_details_nonce'); ?>
            <br />
            <button type="submit" name="da-core-modify-meta-details-submit" class="button button-primary">Save</button>
            <button type="button" class="button button-secondary da-core-cancel-meta-details-submit" data-user-id="<?php echo $user_id; ?>" data-product-id="<?php echo $product_id; ?>">Cancel</button>
        </form>
      <?php
      
      $output_string = ob_get_clean();
	
	    wp_send_json(['success' => false, 'message' => $output_string ]);
    }
	}
	
	/**
	 * Suggests Users based on search criteria
	 */
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
	
	
	/**
	 * Suggests products based on search criteria
	 */
	public function suggests_products() {
		global $wpdb;
		
		$product_id = $wpdb->esc_like(stripslashes($_POST['product_id'])) . '%'; // escape for use in LIKE statement
		$query = "SELECT * FROM {$wpdb->posts} WHERE ( post_title LIKE %s OR ID LIKE %s) AND post_type = 'product' AND post_status = 'publish' ORDER BY post_title ASC";
		
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