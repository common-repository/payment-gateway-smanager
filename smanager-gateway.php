<?php 
/***
Plugin Name: Payment Gateway Plugin - sManager
Plugin URI: https://www.smanager.xyz/
Description: sManager Payment Link - Woocommerce Payment Gateway
Author: sManager
Version: 1.0.5
Text Domain: smanager-gateway
Author URI: https://smanager.xyz/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
***/

// No direct accessable
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}
define('smanager_dir', plugin_dir_path(__FILE__));
define('smanager_ass', plugins_url( 'assets/',__FILE__));
define('smanager_base_name', plugin_basename(__FILE__));

/****constants ****/
define('smanager_docs_link', 'https://smanager.xyz/#docs');
define('smanager_signup_link', 'https://www.smanager.xyz/register');
define('smanager_support_mail', 'info@sheba.xyz');
  
/***
*    Including main Loader of this plugin 
***/
require(smanager_dir.'includes/load.php');
if (!function_exists('smanager_gateway_init'))
{
	function smanager_gateway_init()
	{
	    //woocommerce plugin checking
	    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;
	    
		//main class run
		$runsrz= new smanger\SmanagerWc();
		$runsrz->run();
	}
}
// run the plugin 
smanager_gateway_init();