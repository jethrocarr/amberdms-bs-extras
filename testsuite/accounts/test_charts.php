<?php
/*
	test_charts.php

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
