<?php
/*
	test_charts.php

	Copyright (c) 2009 Amberdms Ltd

	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* accounts_charts_manage (all functions)

	This script performs the following actions:
	* Connects to the billing system
	* Fetch a list of all the avaliable chart types
	* Creates a new account and returns the ID
	* Fetch the data for the account
	* Deletes the account

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

$url		= "https://devel-webapps.local.amberdms.com/development/amberdms_opensource/oss-amberdms-bs/trunk/api/";
//$url		= "https://www.amberdms.com/products/billing_system/online/api/";

$auth_account	= "devel";		// only used by Amberdms Billing System - Hosted Version
$auth_username	= "setup";
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

$data["code_chart"]		= "9000";
$data["description"]		= "SOAP API Testscript";




/*
	CONNECT TO ACCOUNTS_CHARTS_MANAGE SERVICE

*/

$client = new SoapClient("$url/accounts/charts_manage.wsdl");
$client->__setLocation("$url/accounts/charts_manage.php?$sessionid");




/*
	FETCH CHART TYPE LIST
*/

try
{
	print "Fetching chart types...\n";

	// fetch list
	$chart_types = $client->list_chart_type();

	print_r($chart_types);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}


// lookup the ID of the "Asset" chart type
print "Performing ID lookup of Asset chart...\n";
foreach ($chart_types as $data_charttype)
{
	if ($data_charttype["value"] == "Asset")
	{
		$data["chart_type"] = $data_charttype["id"];

		print "Chart Type Asset has ID of ". $data["chart_type"] ."\n";
	}
}





/*
	FETCH AVAILABLE MENU OPTIONS
*/

try
{
	print "Fetching available menu options...\n";

	// fetch list
	$chart_menu = $client->list_chart_menu();

	print_r($chart_menu);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}







/*
	CREATE NEW ACCOUNT
*/


// create account
try
{
	print "Creating new chart...\n";

	// upload data and get ID back
	$data["id"] = $client->set_chart_details($data["id"],
							$data["code_chart"],
							$data["description"],
							$data["chart_type"]);

	print "Created new chart with ID of ". $data["id"] ."\n";
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}

// set menu values
try
{
	print "Setting menu option ar_income to on...\n";

	// upload data and get ID back
	$result = $client->set_chart_menuoption($data["id"], "ar_income", "on");
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}






/*
	GET ACCOUNT DETAILS
*/


try
{
	$data_tmp = $client->get_chart_details($data["id"]);

	print "Executing get_chart_details for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}






/*
	GET ACCOUNT MENU OPTIONS
*/
try
{
	$data_tmp = $client->get_chart_menu($data["id"]);

	print "Executing get_chart_menu for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




/*
	DELETE ACCOUNT
*/



try
{
	print "Deleting chart with ID of ". $data["id"] ."\n";
	$client->delete_chart($data["id"]);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




?>
