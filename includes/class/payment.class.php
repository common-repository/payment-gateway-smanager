<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/


namespace smanger;
	/**
	 * rewrite class
	 */
	class sManagerPaymentChecker
	{
	    public $id,$order;
	    function __construct($orderID){
	        $this->id=$orderID;
		    $this->order = wc_get_order( $orderID);
	    }
	    public function check(){
	        $order =  $this->order;
			if (!$order) { return;}
			$trx_order= get_post_meta($order->get_id(),'smanger_trx_id',true);
			  if($order->get_status() !='pending')  {$order->add_order_note( 'Recheck: This transaction already processed!' ); return;}
			      
			      //Ipn
			    //get option data checker
			    $data=get_option('woocommerce_sheba_settings');
				$clientSecret = $data['api'];
				$clientID = $data['srz_client_id']; 
				$apiClass= new smanagerApi($clientID,$clientSecret);
				$check= $apiClass->validpayment($trx_order); 
				$apirescode= $check->code;
				if($apirescode == '502'){
				    $serverip= $_SERVER['SERVER_ADDR'];$order->add_order_note('Recheck: The ip <b>`'.$serverip.'`</b> you are accessing from is not whitelisted '); 
				    return;
				}
				$apiresData= $check->data;
				$apiTrx= $apiresData->transaction_id;
				$apiAmount= $apiresData->amount;
				if($apiAmount != $order->get_total()){ }
				$paymentStatus= $apiresData->payment_status; // pending,completed 
				if($paymentStatus === 'completed')
				{
				    #payment ok :) 
				    $order->payment_complete();
				    $method= $apiresData->payment_details->method;
				    $order->add_order_note('Recheck: Payment Completed With '.$method);
				    #back to received page :) 
				    $order->reduce_order_stock();
				     return;
				}elseif(empty($paymentStatus) or !$paymentStatus){
				    $order->add_order_note( 'Recheck: '.print_r($check,true) ); 
				}
				else{
				    $order->add_order_note( 'Recheck: This transaction status now "'.$paymentStatus.'" '); 
				    return;
				}
	        
	    }
	    
	} 