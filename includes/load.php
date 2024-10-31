<?php
// Smanager Woocommerce Gateway
// Author: sManager
// Author Link: https://www.smanager.xyz/
namespace smanger;
 
	/**
	 * this class for plugin load :)
	 */
	class SmanagerWc 
	{
		public function depencies(){  
			require_once( smanager_dir . "includes/class//api.class.php" );
			require_once( smanager_dir . "includes/class/srzwogateway.class.php" );
			require_once( smanager_dir . "includes/class/woo.class.php" );
			require_once( smanager_dir . "includes/function/SrzWalletGateway.php" );
			require_once( smanager_dir . "includes/class/payment.class.php" );
			require_once( smanager_dir . "includes/class/rewrite.class.php" );
		}
		public function run()
		{
			//depencies load
			self::depencies();
			
			//loading all object & class
			new SmanagerSrzWcGateway();
			new SmanagerSrzWcRewrite();
		} 
	}
