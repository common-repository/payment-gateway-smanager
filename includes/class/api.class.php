<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/

namespace smanger;
    class smanagerApi{
        public $clientID,$secret;
        public $paymentlink_for_smanager ='https://api.sheba.xyz/v1/ecom-payment/initiate';
        public $checklink_for_smanager ='https://api.sheba.xyz/v1/ecom-payment/details?transaction_id=%id%';
        
       function __construct($clientId,$secret){
           $this->clientId=$clientId;
           $this->secret=$secret;
       }
       public function getpaymentlink($fields=array()){
           if(empty($fields)) return ['status'=>false,'body'=>'some param are empty'];
           $vdata= self::validData($fields);
           if($vdata['status']){
               $arDat= $vdata['data'];
               $response = wp_remote_post($this->paymentlink_for_smanager, array(
						'method'      => 'POST','timeout'     => 30,'redirection' => 10,'httpversion' => '1.1','blocking'    => true,
						'headers'     => array(
						    'client-id'=>$this->clientId,
						    'client-secret'=>$this->secret),
						'body'        => $arDat,
					));
				 
					if($response['response']['code'] == '200'){
					    $result = json_decode($response['body']);
					    if(isset($result->data->link)){
					        return ['status'=>true,'link'=>$result->data->link,'response'=>$response['body']];
					        }else{
					            return ['status'=>false,'body'=>$response['body']];
					        }
					}else{
					     return ['status'=>false,'body'=>$response['body']];
					}
           }else{
                return ['status'=>false,'body'=>$vdata['data']];
           }
          
          return ['status'=>false,'body'=>'unknown error'];
       }
       public function validpayment($trx){
           $apiurl = str_replace('%id%',$trx,$this->checklink_for_smanager);
           $response = wp_remote_post($apiurl, array(
						'method'      => 'GET','timeout'     => 30,'redirection' => 10,'httpversion' => '1.1','blocking'    => true,
						'headers'     => array(
						    'client-id'=>$this->clientId,
						    'client-secret'=>$this->secret),
						'body'        => array(),
					));
					if($response['response']['code'] != 200){
					    return false;
					}
			    $result = json_decode($response['body']);
			    
			return $result;
       } 
       public function validData($arr){
           $error=array();
           $need = array(
               'amount'=>true,
               'success_url'=>true,
               'transaction_id'=> true,
               'fail_url'=>true,
               'customer_name'=>false,
               'customer_mobile'=>false,
               'purpose'=>false,
               'payment_details'=>false);
            $rtback = array();
           foreach($need as $name=>$bool){
               if($bool){
                   if(!isset($arr[$name]) or empty($arr[$name])) {
                       $error[]='empty field '.$name;
                   }
               }
               if(isset($arr[$name])){
                   $rtback[$name]=$arr[$name];
               }else{
                   continue;
               }
           }
        if(empty($error)) return ['status'=>true,'data'=>$rtback];
        
        return ['status'=>false,'data'=>$error];
       }
    } 