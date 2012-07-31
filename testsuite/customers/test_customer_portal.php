<?php
/*
	test_customer_portal.php

	Copyright (c) 2010 Amberdms Ltd


	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* customers_manage (portal functions)

	This script performs the following actions:
	1. Connects to the billing system
	2. Attempts to authenticate agains the customer portal.

	----
	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
	----
*/


/*
	CONFIGURATION
*/


// API credentials
$url			= "https://devel-webapps.local.amberdms.com/development/amberdms_opensource/oss-amberdms-bs/trunk/api/";
//$url			= "https://www.amberdms.com/products/billing_system/online/api/";

$auth_account		= "devel";		// only used by Amberdms Billing System - Hosted Version
$auth_username		= "soap_portal";
$auth_password		= "setup123";


// data for API requests
$data["id"]		= "0";
$data["code_customer"]	= "0";
$data["password"]	= "setup123";



/*
	1. AUTHENTICATE
*/

// connect
$client = new SoapClient("$url/authenticate/authenticate.wsdl");
$client->__setLocation("$url/authenticate/authenticate.php");


// login & get PHP session ID
try
{
	$sessionid = $client->login($auth_account, $auth_username, $auth_password);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}

unset($client);



/*
	2. CONNECT TO CUSTOMERS_MANAGE SERVICE
*/

print "Session ID is: ". $sessionid ."\n";

$client = new SoapClient("$url/customers/customers_manage.wsdl");
$client->__setLocation("$url/customers/customers_manage.php?$sessionid");




/*
	3. AUTHENTICATING
*/

try
{
	print "Authenticating customer...\n";


	$return = $client->customer_portal_auth($data["id"], $data["code_customer"], $data["password"]);

	print "Return code of: $return\n";
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}


?>
