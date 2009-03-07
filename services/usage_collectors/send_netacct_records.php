#!/usr/bin/php -q
<?php
/*
	send_netacct_records.php

	Copyright (c) 2009 Amberdms Ltd

	netacct is a program for monitoring traffic usage and records usage
	records frequently into a MySQL database.

	This script uploads a daily total of the usage for each customer configured
	into the Amberdms Billing System via SOAP for billing purposes.


	>> SAMPLE CODE <<

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


// billing system
$url		= "https://www.amberdms.com/products/billing_system/online/api";
$auth_account	= "example";
$auth_username	= "example";
$auth_password	= "example";


// local database
$netacct_sql_host	= "localhost";
$netacct_sql_db		= "netacct";
$netacct_sql_user	= "acct";
$netacct_sql_password	= "example";

// map all the customer IPs to service numbers
$customer_ip_map = array("XXX.XXX.XXX.XXX" => "2",
			 "XXX.XXX.XXX.XXX" => "3",
			 "XXX.XXX.XXX.XXX" => "4");


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

	Fetch usage information from the netacct MySQL database on the local server
*/

// login to the database
$link = mysql_connect($netacct_sql_host, $netacct_sql_user, $netacct_sql_password);
if (!$link)
	die("Unable to connect to DB:" . mysql_error());

// select the database
$db_selected = mysql_select_db($netacct_sql_db, $link);
if (!$db_selected)
	die("Unable to connect to DB:" . mysql_error());


// fetch all the usage for yesterday
$date		= date("Y-m-d", mktime() - 86400);
$date_now	= date("Y-m-d");

print "Fetching netacct usage for $date\n";

$mysql_string = "SELECT 
			ip, 
			SUM(input) as usage1, 
			SUM(output) as usage2 
				FROM traffic WHERE
				time > '$date' 
				AND time < '$date_now'
				GROUP BY ip";

$mysql_result = mysql_query($mysql_string);

if (!$mysql_result)
{
	die("Unable to fetch usage information from MySQL database.");
}


if (!mysql_num_rows($mysql_result))
{
	die("No records in netacct database for $date - possible crashed netacct daemon?\n");
}

/*
	3. UPLOAD USAGE DATA

	Now we send the data to the billing system via SOAP and check that it succeeds OK.
*/


// connect to the SOAP interface
$client = new SoapClient("$url/services/usage.wsdl");
$client->__setLocation("$url/services/usage.php?$sessionid");


// run through the usage results from MySQL
while ($mysql_data = mysql_fetch_array($mysql_result))
{
	$data = array();

	// fetch services_customers_id from the mapping table
	$data["services_customers_id"] = $customer_ip_map[ $mysql_data["ip"] ];


	if ($data["services_customers_id"])
	{
		print "Processing usage for ". $mysql_data["ip"] ."\n";

		// send data
		try
		{
			$client->set_usage_record("netacct", $data["services_customers_id"], $date, $mysql_data["usage1"], $mysql_data["usage2"]);
		}
		catch (SoapFault $exception)
		{
			die($exception);
		}
	}
	else
	{
		print "No services_customers_id record for ". $mysql_data["ip"] .", not processing\n";
	}
}



/*
	3. CLEAN UP

	Remove the processed records from the netacct database
*/

// delete all the records which have now been processed
print "Deleting processed records from local MySQL database\n";

$mysql_string = "DELETE FROM traffic WHERE time < '$date'";
if (!mysql_query($mysql_string))
{
	die("DB Error:" . mysql_error());
}


?>
