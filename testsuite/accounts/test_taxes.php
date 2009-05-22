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

$url		= "https://devel-centos5-32.jethrocarr.local/development/amberdms/billing_system/htdocs/api";

$auth_account	= "devel";		// only used by Amberdms Billing System - Hosted Version
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

$data["name_tax"]			= "test tax";
$data["taxrate"]			= "15.6";
$data["chartid"]			= "3";
$data["taxnumber"]			= "111-222-333-444";
$data["description"]			= "SOAP TEST TAX";
$data["autoenable_tax_customer"]	= "on";
$data["autoenable_tax_vendor"]		= "on";





/*
	CONNECT TO ACCOUNTS_TAXES_MANAGE SERVICE

*/

$client = new SoapClient("$url/accounts/taxes_manage.wsdl");
$client->__setLocation("$url/accounts/taxes_manage.php?$sessionid");



/*
	FETCH LIST OF CURRENT TAXES
*/

try
{
	print "Fetching tax list...\n";

	// fetch list
	$data_tmp = $client->list_taxes();

	print_r($data_tmp);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}



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
							$data["description"],
							$data["autoenable_tax_customer"],
							$data["autoenable_tax_vendor"]);

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
