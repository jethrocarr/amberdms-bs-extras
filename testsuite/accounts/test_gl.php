<?php
/*
	test_gl.php

	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* accounts_gl_manage (all functions)

	This script performs the following actions:
	* Connects to the billing system
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


// details
$data["code_gl"]			= "";
$data["date_trans"]			= date("Y-m-d");
$data["employeeid"]			= 1;
$data["description"]			= "SOAP test";
$data["description_useall"]		= "on";
$data["notes"]				= "test notes";


// transaction rows #1
$data["trans"][0]["chartid"]		= 4;
$data["trans"][0]["credit"]		= 10;
$data["trans"][0]["debit"]		= 0;
$data["trans"][0]["source"]		= "";
$data["trans"][0]["description"]	= "";

// transaction rows #2
$data["trans"][1]["chartid"]		= 3;
$data["trans"][1]["credit"]		= 0;
$data["trans"][1]["debit"]		= 10;
$data["trans"][1]["source"]		= "";
$data["trans"][1]["description"]	= "";




/*
	CONNECT TO ACCOUNTS_GL_MANAGE SERVICE

*/

$client = new SoapClient("$url/accounts/gl_manage.wsdl");
$client->__setLocation("$url/accounts/gl_manage.php?$sessionid");




/*
	CREATE NEW TRANSACTION
*/


// prepare transaction details
try
{
	print "Preparing transaction details...\n";

	// upload data
	$client->prepare_gl_details($data["code_gl"],
					$data["date_trans"],
					$data["employeeid"],
					$data["description"],
					$data["description_useall"],
					$data["notes"]);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}

// add transaction rows
print "Preparing transaction rows...\n";
try
{

	// upload trans data - row 1
	$result = $client->prepare_gl_addtrans($data["trans"][0]["chartid"],
						$data["trans"][0]["credit"], 
						$data["trans"][0]["debit"], 
						$data["trans"][0]["source"], 
						$data["trans"][0]["description"]);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}

try
{

	// upload trans data - row 2
	$result = $client->prepare_gl_addtrans($data["trans"][1]["chartid"],
						$data["trans"][1]["credit"], 
						$data["trans"][1]["debit"], 
						$data["trans"][1]["source"], 
						$data["trans"][1]["description"]);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}



// save transaction
try
{
	print "Saving transaction/commiting data...\n";

	// upload data and get ID back
	$data["id"] = $client->set_gl_save("0");
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}





/*
	GET TRANSACTION DATA
*/


// details
try
{
	$data_tmp = $client->get_gl_details($data["id"]);

	print "Executing get_gl_details for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}

// transaction rows
try
{
	$data_tmp = $client->get_gl_trans($data["id"]);

	print "Executing get_gl_trans for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




/*
	DELETE TRANSACTION
*/


try
{
	print "Deleting transaction with ID of ". $data["id"] ."\n";
	$client->delete_gl($data["id"]);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




?>
