<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/
namespace smanger;
	/**
	 * rewrite class
	 */
	class SmanagerSrzWcRewrite
	{
		
		function __construct()
		{
			//init hook
			add_action('init',array($this,'reWrite'),10,1);
			//query var register
			add_filter('query_vars', array($this,'query_vars'));
			//redirect management 
			add_action('template_redirect',array($this,'redirect'));
			//showing order page
			add_action( 'template_include', function( $template ) {
				if ( get_query_var( 'srz_smanager' ) == false || get_query_var( 'srz_smanager' ) == '' && isset($_POST) ) {
					return $template;
				}
				$this->SrzSmanager();
			} );
		}
 
		/**
		 * callback & process of the order.
		 * return void
		 */
		public function SrzSmanager()
		{ 
			if (isset($_REQUEST['order_id']) && isset($_REQUEST['trax_id']) && isset($_REQUEST['srzsecurity'])) {
				$nonce = wp_verify_nonce($_REQUEST['srzsecurity'],'wallet-nonce-srz');
				//request checker
					if($nonce===1 or $nonce===2){}else{ 
					     wp_redirect(home_url().'#invalid_request');
					     exit(__('Invalid access','smanager-gateway'));
					}
				
			    //no getopen data
			    if (get_option('woocommerce_sheba_settings') === '' or empty(get_option('woocommerce_sheba_settings')))  exit(__('Please do setting first','smanager-gateway'));
			    
			    //get option data checker
			    $data=get_option('woocommerce_sheba_settings');
			    if (!isset($data['api']) or !isset($data['srz_client_id'])) exit(__('Please do setting first!','smanager-gateway'));
			    
			    //get order id
			    $order_id = sanitize_text_field($_REQUEST['order_id']);
			    $trx_id = sanitize_text_field($_REQUEST['trax_id']);
				$order =  wc_get_order($order_id);
				if (!$order) {
				  //  wp_redirect(home_url());
				    exit(__('Invalid Id','smanager-gateway'));}
				$trx_order= get_post_meta($order->get_id(),'smanger_trx_id',true);
				if($trx_order !== $trx_id){
				    //trx id not same :/ 
				    wp_redirect(home_url().'#trx_not_valid');
				    exit(__('Trx Id not same ,so its fake','smanager-gateway'));
				}
				
				$product_title = array();
				$items = $order->get_items();
				foreach($items as $item => $values) 
				{ 
				    $product        = $values->get_product(); 
				    $product_title[] = $product->get_title();
				} 
				$product_name = implode(",",$product_title);
			    if($order->get_status() ==='pending')  {}else{
			        //already proccesed so check view page
			        wp_redirect($order->get_view_order_url());
			        exit(__('Its Already Processed','smanager-gateway')); 
			      }
			      
			      //Ipn
				$clientSecret = $data['api'];
				$clientID = $data['srz_client_id']; 
				$apiClass= new smanagerApi($clientID,$clientSecret);
				$check= $apiClass->validpayment($trx_id); 
				 
				$apirescode= $check->code;
				if($apirescode == '502'){
				    $serverip= $_SERVER['SERVER_ADDR'];
				    exit('The ip <b>`'.$serverip.'`</b> you are accessing from is not whitelisted ');
				}
				$apiresData= $check->data;
				$apiTrx= $apiresData->transaction_id;
				$apiAmount= $apiresData->amount;
				if($apiAmount != $order->get_total()){
				    //cart amount not same with api ,so its fraud
				    wp_redirect(home_url().'#trx_wrong');
				    exit(__('amount not same ,so its fake','smanager-gateway'));
				}
				$paymentStatus= $apiresData->payment_status; // pending,completed 
				if($paymentStatus === 'completed')
				{
				    global $woocommerce;
				    #payment ok :) 
				    $order->payment_complete();
				    $method= $apiresData->payment_details->method;
				    $order->add_order_note('Payment Completed With '.$method);
				    #empty cart :)
				    $woocommerce->cart->empty_cart();
				    #back to received page :)
				    $return_url = $order->get_checkout_order_received_url();
				    $order->reduce_order_stock();
				    wp_redirect($return_url);
				    exit();
				}elseif($paymentStatus === 'initiation_failed' or $paymentStatus === 'validation_failed'){
				    #payment canceled or failed
				    $canceled =htmlspecialchars_decode($order->get_cancel_order_url());
				    wp_redirect($canceled);
				    exit(__('payment canceled or failed!','smanager-gateway'));
				}elseif($paymentStatus === 'pending' or $paymentStatus === 'initiated'){
				    #payment pending still or just start 
				    $payment_link = esc_url(get_post_meta($order->get_id(),'smanger_payment_link',true));
				    $backtpay= 'Payment is currently <b>'.$paymentStatus.'</b> if you want to continue to payment then <a href="'.$payment_link.'">Click Here</a>';
				    exit(__($backtpay,'smanager-gateway'));
				}else{
				    # payment error
				    wp_redirect($order->get_view_order_url());
				    exit(__('Server Issue!','smanager-gateway'));
				}
	  
		}
		#no param ,so go on home page
		wp_redirect(home_url());
		exit(__('Invalid Request','smanager-gateway'));
	}
 
	/**
	* Query var callback
	* @param $vars array
	*return array
	*/
	public function query_vars($vars) {
			$vars[] = 'srz_smanager';
			$vars[] = 'order_id';
			$vars[] = 'smanger_redirect';
			$vars[] = 'trax_id';
			$vars[] = 'srzsecurity';
			 return $vars;
			}
	public function redirect(){
	    if(!empty(get_query_var('smanger_redirect'))){
	        $url = base64_decode(get_query_var('smanger_redirect'));
	        $url= htmlspecialchars_decode($url);
	        wp_redirect($url);
	        exit();
	    }
	}
	
	/**
	* Custom rewrite for order check page
	*/
	public function reWrite()
		{
			flush_rewrite_rules();
			$regex ='^sheba/gateway/v1/?';
			add_rewrite_rule($regex, 'index.php?srz_smanager=sohagmimi', 'top');
		}
	} 