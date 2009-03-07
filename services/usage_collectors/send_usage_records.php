<?php
/*
	send_usage_records.php

	Copyright (c) 2009 Amberdms Ltd

	>> SAMPLE_CODE <<

	This script connects to the Amberdms Billing System and uploads usage records to the specified
	customer's records. This usage information is then used when the service invoices are generated
	to bill customers accordingly.

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
