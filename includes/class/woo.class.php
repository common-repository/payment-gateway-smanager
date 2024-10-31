<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/
 
//function checking
if (!function_exists('srz_smanager_woo_method')) {

function srz_smanager_woo_method()
{
	/***
	extending woocomerce payment object
	***/
class Srz_WC_Gateway_Smanager_CL extends WC_Payment_Gateway {
	
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'sheba';
			$this->icon = apply_filters( 'woocommerce_gateway_icon', smanager_ass.'img/smanager.png' );  
			$this->has_fields         = false;
			$this->method_title       = __( 'sManager payment geteway', 'smanager-gateway');
			$this->method_description = __( 'Pay via sManager payment gateway','smanager-gateway');
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings(); 
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->srz_client_id =$this->get_option('srz_client_id');
			$this->api =$this->get_option('api');
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );  
			// Customer Emails 
			add_action('woocommerce_receipt_'.$this->id, array($this, 'receipt_page')); 

		}
		public function receipt_page($order)
		{
           	echo $this->generate_form($order);
		} 
		public function generate_form($order_id)
		{
            global $woocommerce; 
            
            $order = new WC_Order($order_id);
            //main valiadtion page  
            $redirect_url = home_url('sheba/gateway/v1/');
            //fail url
            $fail_url =  get_site_url();
            
            //api integration to get payment link
            $trx= $order_id.time().uniqid(); 
            update_post_meta( $order_id, 'smanger_trx_id', $trx );
            //redirect url after complete transection
            $redirect_url =  add_query_arg( array(
                'order_id' => $order_id,
                'trax_id' => $trx,
                'srzsecurity' => wp_create_nonce('wallet-nonce-srz')),$redirect_url );
            $declineURL =htmlspecialchars_decode($order->get_cancel_order_url());
            
            //cancel link
            update_post_meta( $order_id, 'smanger_cancel_link_1', $declineURL );
            $declineURL=$fail_url.'/?smanger_redirect='.base64_encode($declineURL);
             $redirect_url=$fail_url.'/?smanger_redirect='.base64_encode($redirect_url);
            
            update_post_meta( $order_id, 'smanger_success_link', $redirect_url );
            
            //cancel link
            update_post_meta( $order_id, 'smanger_cancel_link', $declineURL );
            $items = $woocommerce->cart->get_cart();
            //product title
            $product_title = array();
            foreach($items as $item => $values) 
            { 
                $_product =  wc_get_product( $values['data']->get_id()); 
                $product_title[] = $_product->get_title();
            } 
            $product_name = implode(",",$product_title);
            $apiClass= new smanger\smanagerApi($this->get_option( 'srz_client_id' ),$this->get_option( 'api' ));
            $sitename= get_bloginfo( 'name' );
            $ipn_data =[
                'customer_name'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'customer_mobile' => $order->get_billing_phone(),
                'amount'          => $order->get_total(),
                'success_url'     => $redirect_url,
                'transaction_id'  => $trx,
                'fail_url'        => $declineURL,
                'purpose'         => 'Payments For "'.$product_name.'"',
                'payment_details' => 'payment for " '.$product_name.' ". on '.$sitename.'.Main order id '.$order_id,
                ]; 
            $data= $apiClass->getpaymentlink($ipn_data);
            
            if(!$data['status']){
                return '<p class="error">
                <pre>'.print_r($data['body'],true).'</pre>
                <font color="red"><b>Something Went wrong with api connection, Please check all setup!</b></font></p>';
                //error
                update_post_meta( $order_id, 'smanger_error', 'something went wrong' );
            }
            $linktogo = $data['link'];
		    $order->add_order_note('sManager Payment Link => '.$linktogo);
		    
		    $order->add_order_note('sManager Transection ID => '.$trx);
		    
            //payment link
            update_post_meta( $order_id, 'smanger_payment_link', $linktogo );
            
            //api response
            update_post_meta( $order_id, 'smanger_payment_response', $data['response'] );
            { $out = '<p>' . __('Thank you for your order, please click the button below to pay with sManager.Xyz.', 'smanager-gateway') . '</p>';
            	$out .= '<form action="'.$linktogo.'" method="GET"> 
            	<input type="submit" class="button-alt" id="submit_srz_payment_form" value="' . __('Pay via sManager Payment Gateway', 'smanager-gateway') . '" />
            	<a class="button cancel" href="' . $declineURL. '">' . __('Cancel order &amp; restore cart', 'smanager-gateway') . '</a>
            	<script type="text/javascript">
	                    jQuery(function(){
	                        jQuery("body").block({
	                            message: "' . __('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'smanager-gateway') . '",
	                            overlayCSS: {
	                                background: "#fff",
	                                    opacity: 0.6
	                            },
	                            css: {
	                                padding:        20,
	                                textAlign:      "center",
	                                color:          "#555",
	                                border:         "3px solid #aaa",
	                                backgroundColor:"#fff",
	                                cursor:         "wait",
	                                lineHeight:"32px"
	                            }
	                        });
	                       jQuery("#submit_srz_payment_form").click();
	                    });
	                </script>
	                </form> ';
	                return $out;
            }
		}
		
		public function other_form_fields() {
    return array(
        'convert' => array(
            'type' => 'checkbox',
            'id' => 'convert',
            'title' => __( 'Convert?', 'custom_paypal' ),
            'description' => __( 'Convert other currencies?', 'custom_paypal' ),
        ),
    );
  }
  
  /**
  * Redefining the display of options.
  * If we are on a second screen, we will show the other fields.
  * If we are not on the second screen, show the original fields.
  */
  public function admin_options() {
            echo '<h2>' . __('sManager Payment Gateway', 'smanager-gateway') . '</h2>';
            echo '<p>' . __('Configure parameters to start accepting payments.') . '</p><hr>';
            echo '<p>' . __('Set your server ip on whitelist. Your ip is :') . '<b>'.$_SERVER['SERVER_ADDR'].'</b></p><hr>';
            echo "<h4 style='color:green;'>" . __("Register for get client credentials <a href='".smanager_signup_link."' target='blank'>Click Here</a> .") . "</h4><hr>";
            
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';}
  
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_'.$this->id.'_form_fields', array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'smanager-gateway'),
					'type'    => 'checkbox',
					'label'   => __( 'Enable sManager', 'smanager-gateway'),
					'default' => 'no'
				),
				'title' => array(
					'title'       => __( 'Title', 'smanager-gateway'),
					'type'        => 'text',
					'description' => __( 'Take payments vai sManager.', 'smanager-gateway'),
					'placeholder' => 'Payment Gateway title goes here.',
					'default'     => __( 'sManager Payment Gateway', 'smanager-gateway'),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'smanager-gateway'),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'smanager-gateway'),
					'placeholder' => 'How to pay customers description goes here.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'smanager-gateway'),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'smanager-gateway'),
					'default'     => '', 
					'placeholder' => 'If you want to place anything else.',
					'desc_tip'    => true,
				),
				'srz_client_id' => array(
					'title'       => __( 'Client ID', 'smanager-gateway'),
					'type'        => 'text',
					'description' => __( 'Client ID', 'smanager-gateway'),
					'default'     => '',
					'placeholder' => 'sManager Client ID here.',
					'desc_tip'    => true,
				),
				'api' => array(
					'title'       => __( 'Client Secret', 'smanager-gateway'),
					'type'        => 'text',
					'description' => __( 'Client secret from Smanager account.', 'smanager-gateway'),
					'default'     => '',
					'placeholder' => 'sManager secret code here.',
					'desc_tip'    => true,
				),
			) );
		}

		/**
		 * Payment fields on checkout field
		 */

		public function payment_fields()
		{

			if ( $this->description ) {
				if (empty($this->get_option( 'api' )) ) {
				echo __('Setup sManager Plugin client settings first!','smanager-gateway');
				}elseif(empty($this->get_option( 'srz_client_id' ))){
				echo __('Setup sManager Plugin client settings first!','smanager-gateway');
				}else{
				echo wpautop( wptexturize( $this->description ) );
				}
			} 
		}

		/**
		 * validation check
		 */
		public function validate_fields(){  
			return true;
		}
		 
	 
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				if (empty($this->get_option( 'api' ))) {
				echo __('Setup sManager Plugin client settings first!','smanager-gateway');
				}elseif(empty($this->get_option( 'srz_client_id' ))){
				echo __('Setup sManager Plugin client settings first!','smanager-gateway');
				}else{
				echo wpautop( wptexturize( $this->instructions ) );
				}
			}
		}
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			global $woocommerce;
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );
			$error= false;
			if ($error){ }else{
			 // Redirect to the thank you page
            return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
			} 
		}
	
  } # end  class
}
} # fucntion check