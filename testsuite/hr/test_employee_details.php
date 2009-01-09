<?php
/*
	get_employee_details.php

	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* hr_staff_manage (all functions)

	This script connects to the billing system and creates an employee, then fetches
	the data for the newly created employee, before finally deleting the employee.

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
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}

unset($client);


/*
	2. GATHER DATA

	This section is a good place to add your own code to fetch the data you need to post to the system.
*/

$data["name_staff"]		= "SOAP API Testscript";
$data["staff_code"]		= "TEST_STAFF";
$data["date_start"]		= date("Y-m-d");
$data["contact_email"]		= "test@example.com";



/*
	3. CONNECT TO HR_STAFF_MANAGE SERVICE

*/

$client = new SoapClient("$url/hr/staff_manage.wsdl");
$client->__setLocation("$url/hr/staff_manage.php?$sessionid");




/*
	4. CREATE NEW EMPLOYEE
*/


try
{
	print "Creating new employee...\n";

	// upload data and get ID back
	$data["id"] = $client->set_employee_details($data["id"],
							$data["name_staff"],
							$data["staff_code"],
							$data["staff_position"],
							$data["contact_phone"],
							$data["contact_fax"],
							$data["contact_email"],
							$data["date_start"],
							$data["date_end"]);

	print "Created new employee with ID of ". $data["id"] ."\n";
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}



/*
	5. SELECT EMPLOYEE DETAILS
*/
try
{
	$data_tmp = $client->get_employee_details($data["id"]);

	print "Executing get_employee_details for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}



/*
	6. DELETE EMPLOYEE DETAILS
*/


try
{
	print "Deleting employee with ID of ". $data["id"] ."\n";
	$client->delete_employee($data["id"]);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}



?>
