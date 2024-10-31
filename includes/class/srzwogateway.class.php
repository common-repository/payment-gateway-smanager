<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/


namespace smanger;
 
	/**
	 * main class 
	 */
	class SmanagerSrzWcGateway
	{
		
		function __construct()
		{
			//plugins loaded hook
			add_action('plugins_loaded', array($this,'loaded'), 0);
			add_action( 'woocommerce_order_actions', array($this,'do_acction') );
			
			add_action( 'woocommerce_order_action_wc_sheba_payment_action', array($this,'proccess_action') );
		}
		public function proccess_action( $order ) {
		    if(class_exists('sManagerPaymentChecker')){
		        $proccess= new sManagerPaymentChecker($order->get_id());
		        $proccess->check();
		    } 
		    
		}

		public function do_acction($actions){ 
		    $order = wc_get_order( get_the_ID() );
		    $method=  $order->get_payment_method_title();
		    if($order->get_status() !='pending' && $method != 'sheba') { return $actions;} 
		   $actions['wc_sheba_payment_action'] = __( 'Recheck sManager Payment', 'sheba' );
		  return $actions;
		    
		}
		public function loaded()
		{
			//hook for show links after plugin activation
		add_filter('plugin_action_links_' . smanager_base_name, array($this,'settings_link'));
		}
		public function check_payment_btn(){ }
		/**
		 * Show links on plugin name 
		 * @param array
		 * return array
		 */
		public function settings_link($links)
		{
		    $pluginLinks = array(
	            'settings' => '<a href="'. esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=sheba')) .'">Settings</a>',
	            'docs'     => '<a href="'.smanager_docs_link.'" target="blank">Docs</a>',
	            'create_acc'     => '<a href="'.smanager_signup_link.'" target="blank">Create Account</a>',
	            'support'  => '<a href="mailto:'.smanager_support_mail.'">Support</a>'
	        );
		    $links = array_merge($links, $pluginLinks);
		    return $links;
		}

	} # class end 