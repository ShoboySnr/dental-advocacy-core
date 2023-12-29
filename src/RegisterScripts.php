<?php

namespace DentalAdvocacyCore\Core;

class RegisterScripts {
	
	public function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'da_core_load_scripts'] );
	}
	
	function da_core_load_scripts()
	{
		wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array());
		
		wp_enqueue_style('da-core-admin-css', DENTAL_ADVOCACY_CORE_SYSTEM_ASSETS_URL.'/css/da-core-admin.css');
		
		wp_enqueue_script('da-core-admin-js', DENTAL_ADVOCACY_CORE_SYSTEM_ASSETS_URL.'/js/da-core-admin.js', array('jquery'));
		
		wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
		
		wp_localize_script('da-core-admin-js', 'da_core_admin_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}
	

	public static function get_instance() {
		static $instance = null;
		
		if (is_null($instance)) {
			$instance = new self();
		}
		
		return $instance;
	}
}