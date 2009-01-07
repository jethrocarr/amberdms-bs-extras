<?php
/*
	send_usage_records.php

	>> SAMPLE_CODE <<

	This script connects to the Amberdms Billing System and uploads usage records to the specified
	customer's records. This usage information is then used when the service invoices are generated
	to bill customers accordingly.
*/


/*
	CONFIGURATION
*/

$url		= "https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api";

$auth_account	= 0;		// only used by Amberdms Billing System - Hosted Version
$auth_username	= "soap";
$auth_password	= "setup123";



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
	die($exception);
}

unset($client);


/*
	2. GATHER DATA

	This section is a good place to add your own code to fetch the data you need to post to the system.
*/

$data["collector"]		= "samplecode";
$data["services_customers_id"]	= "2";
$data["date"]			= date("Y-m-d");
$data["usage1"]			= "1000";
$data["usage2"]			= "512";



/*
	3. UPLOAD USAGE DATA

	Now we send the data to the billing system via SOAP and check that it succeeds OK.
*/



// connect
$client = new SoapClient("$url/services/usage.wsdl");
$client->__setLocation("$url/services/usage.php?$sessionid");


// send data
try
{
	$client->set_usage_record($data["collector"], $data["services_customers_id"], $data["date"], $data["usage1"], $data["usage2"]);
}
catch (SoapFault $exception)
{
	die($exception);
}



?>
