<?php
/**
 * Plugin Name:     Dental Advocacy Core
 * Plugin URI:      https://techwithdee.com
 * Description:     Core Library for Dental Advocacy Wesbite
 * Version:         1.0.0
 * Author:          Damilare Shobowale
 * Author URI:      https://techwithdee.com
 * Developer:       Damilare Shobowale
 * Developer URI:   https://techwithdee.com
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     dental-advocacy-core
 * Domain Path:     /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}

require __DIR__ . '/vendor/autoload.php';

define('DENTAL_ADVOCACY_CORE_SYSTEM_FILE_PATH', __FILE__);
define('DENTAL_ADVOCACY_CORE_TEXT_DOMAIN', 'dental-advocacy-core');
define('DENTAL_ADVOCACY_CORE_VERSION_NUMBER', '1.0.0');
define( 'DENTAL_ADVOCACY_CORE_SYSTEM_SRC_DIRECTORY', plugin_dir_url( __FILE__ ). 'src');
define( 'DENTAL_ADVOCACY_CORE_SYSTEM_ASSETS_URL', plugin_dir_url( __FILE__ ). 'assets');

/*
 * Create database table on plugin activation
 * */
function dental_advocacy_core_create_table(){
	\DentalAdvocacyCore\Core\RegisterTables::create_tables();
}

register_activation_hook( __FILE__, 'dental_advocacy_core_create_table' );

add_action( 'plugins_loaded', 'dental_advocacy_core_plugin_init', 11);

function dental_advocacy_core_plugin_init() {
	\DentalAdvocacyCore\Core\Core::init();
}