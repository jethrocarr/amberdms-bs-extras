<?php
/*
	include/config.php

	Sample configuration file for the Amberdms Payment Gateway
*/

$GLOBALS["config"];


// Amberdms Billing System SOAP API Authentication
$config["url"]			= "https://www.amberdms.com/products/billing_system/online/api";

$config["auth_account"]		= "example";		// only used by Amberdms Billing System - Hosted Version
$config["auth_username"]	= "example";
$config["auth_password"]	= "example";


// destination account for chart ID
$config["payment"]["chartid"]	= "1";				// note - this is the true ID, not the user-selectable
								// "Account ID". You can get it by checking the &id value
								// whilst viewing/editing a page

// destination account for paypal fees
$config["payment"]["fees_chartid"] = "2";


// paypal configuration
//$config["paypal"]["url"]	= "www.paypal.com";		// production
$config["paypal"]["url"]	= "www.sandbox.paypal.com";		// developer's sandbox
$config["paypal"]["businessid"]	= "sales@example.com";


// IPN Monitoring
$config["email_debug"]		= "support@example.com";		// email this address any problems.
$config["email_accounts"]	= "accounts@example.com";		// email this address with processed payment notifications

?>
