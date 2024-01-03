<?php

namespace DentalAdvocacyCore\Core;

class RegisterTables {
	
	public function __construct()
	{
	
	}
	
	public static function create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'da_core_products_to_cart_items';
		$charset_collate = $wpdb->get_charset_collate();
		
		$query = "CREATE TABLE $table_name (
id int(11) NOT NULL AUTO_INCREMENT,
user_id int(11) NOT NULL,
product_id int(11) NOT NULL,
variation_id int(11) DEFAULT NULL,
imported_to_cart tinyint(1) DEFAULT '0' NOT NULL,
ordered tinyint(1) DEFAULT '0' NOT NULL,
quantity int(11) DEFAULT '1' NOT NULL,
metadata varchar(255) DEFAULT NULL,
PRIMARY KEY  (id)
) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $query );
		
		add_option( 'da_core_db_version', '1.0' );
	}
}