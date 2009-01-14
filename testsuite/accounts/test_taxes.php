<?php
/*
	test_taxes.php

	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* accounts_taxes_manage (all functions)

	This script performs the following actions:
	* Connects to the billing system
	* Creates a new tax and returns the ID
	* Fetch the data for the tax
	* Deletes the tax
*/


/*
	CONFIGURATION
*/

$url		= "https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api";

$auth_account	= 0;		// only used by Amberdms Billing System - Hosted Version
$auth_username	= "soap";
$auth_password	= "setup123";



/*
	AUTHENTICATE
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
	GATHER DATA

	This section is a good place to add your own code to fetch the data you need to post to the system.
*/

$data["name_tax"]	= "test tax";
$data["taxrate"]	= "15.6";
$data["chartid"]	= "3";
$data["taxnumber"]	= "111-222-333-444";
$data["description"]	= "SOAP TEST TAX";





/*
	CONNECT TO ACCOUNTS_TAXES_MANAGE SERVICE

*/

$client = new SoapClient("$url/accounts/taxes_manage.wsdl");
$client->__setLocation("$url/accounts/taxes_manage.php?$sessionid");




/*
	CREATE NEW TAX
*/


try
{
	print "Creating new tax...\n";

	// upload data and get ID back
	$data["id"] = $client->set_tax_details($data["id"],
							$data["name_tax"],
							$data["taxrate"],
							$data["chartid"],
							$data["taxnumber"],
							$data["description"]);

	print "Created new tax with ID of ". $data["id"] ."\n";
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}






/*
	GET TAX DETAILS
*/


try
{
	$data_tmp = $client->get_tax_details($data["id"]);

	print "Executing get_tax_details for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}





/*
	DELETE TAX
*/



try
{
	print "Deleting tax with ID of ". $data["id"] ."\n";
	$client->delete_tax($data["id"]);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




?>
