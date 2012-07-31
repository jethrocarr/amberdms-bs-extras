<?php
/*
	get_employee_details.php

	Copyright (c) 2009 Amberdms Ltd


	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* hr_staff_manage (all functions)

	This script connects to the billing system and creates an employee, then fetches
	the data for the newly created employee, before finally deleting the employee.


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
