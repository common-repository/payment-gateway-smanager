<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/


/***
* run this method on plugins_loaded
* reurn void
***/
if(!function_exists('SrzSmanagerGatewayMakeWallet')){
  function SrzSmanagerGatewayMakeWallet(){  
srz_smanager_woo_method();
}
  
}

//hook for load when plugins loaded
add_action( 'plugins_loaded','SrzSmanagerGatewayMakeWallet', 11 );

/***
* run this method on check woocomerce payment gateways
* @param array
*return array
***/
if(!function_exists('wc_Srz_smanager_add_to_gateways')){
function wc_Srz_smanager_add_to_gateways( $gateways ) {
	$gateways[] = 'Srz_WC_Gateway_Smanager_CL';
	return $gateways;
}
}

//register woocomerce gateways
add_filter( 'woocommerce_payment_gateways', 'wc_Srz_smanager_add_to_gateways' ); 

