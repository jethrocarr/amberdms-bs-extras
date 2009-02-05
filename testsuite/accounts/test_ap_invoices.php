<?php
/*
	test_ap_invoices.php

	TESTSUITE SCRIPT

	Tests following SOAP APIs:
	* authenticate (login function)
	* accounts_invoices_manage (all functions)

	This script performs the following actions:
	* Connects to the billing system
	* Creates a new invoice and returns the ID
	* Adds the following items to the invoice
		- standard item
		- product item
		- payment item
	* Fetch all the data for the invoice and all items
	* Deletes a single item
	* Deletes the invoice
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

// set the below ID to update an invoice, rather than create a new one
$data["id"]			= "";

// invoice details
$data["invoicetype"]		= "ap";
$data["locked"]			= 0;
$data["orgid"]			= "5";
$data["employeeid"]		= "4";
$data["dest_account"]		= "6";
$data["code_invoice"]		= "";
$data["code_ordernumber"]	= "";
$data["code_ponumber"]		= "";
$data["date_due"]		= date("Y-m-d");
$data["date_trans"]		= date("Y-m-d");
$data["notes"]			= "SOAP API TEST INVOICE";
$data["autotaxes"]		= "off";


// define standard item
$data["item"]["standard"]["id"]			= "";		// set to update existing items
$data["item"]["standard"]["chartid"]		= 5;
$data["item"]["standard"]["amount"]		= "100.00";
$data["item"]["standard"]["description"]	= "SOAP test standard item";

// define product item
$data["item"]["product"]["id"]			= "";		// set to update existing items
$data["item"]["product"]["price"]		= "10.00";
$data["item"]["product"]["quantity"]		= "5";
$data["item"]["product"]["units"]		= "Items";
$data["item"]["product"]["productid"]		= "1";
$data["item"]["product"]["description"]		= "SOAP test product item";

// define payment item
$data["item"]["payment"]["id"]			= "";			// set to update existing items
$data["item"]["payment"]["date_trans"]		= date("Y-m-d");
$data["item"]["payment"]["chartid"]		= "4";
$data["item"]["payment"]["amount"]		= "65.00";
$data["item"]["payment"]["source"]		= "cheque";
$data["item"]["payment"]["description"]		= "SOAP test customer payment";





/*
	CONNECT TO ACCOUNTS_INVOICES_MANAGE SERVICE

*/

$client = new SoapClient("$url/accounts/invoices_manage.wsdl");
$client->__setLocation("$url/accounts/invoices_manage.php?$sessionid");




/*
	CREATE NEW INVOICE
*/





// create account
try
{
	print "Creating new invoice...\n";

	// upload data and get ID back
	$data["id"] = $client->set_invoice_details($data["id"],
							$data["invoicetype"],
							$data["locked"],
							$data["orgid"],
							$data["employeeid"],
							$data["dest_account"],
							$data["code_invoice"],
							$data["code_ordernumber"],
							$data["code_ponumber"],
							$data["date_due"],
							$data["date_trans"],
							"",
							"",
							$data["notes"],
							$data["autotaxes"]);

	print "Created new invoice with ID of ". $data["id"] ."\n";
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}




// create items, taxes and payments
try
{
	print "Creating standard item...\n";

	// upload data and get ID back
	$data["item"]["standard"]["id"] = $client->set_invoice_item_standard($data["id"],
										$data["invoicetype"],
										$data["item"]["standard"]["id"],
										$data["item"]["standard"]["chartid"],
										$data["item"]["standard"]["amount"],
										$data["item"]["standard"]["description"]);


	print "Creating product item...\n";

	// upload data and get ID back
	$result = $client->set_invoice_item_product($data["id"],
							$data["invoicetype"],
							$data["item"]["product"]["id"],
							$data["item"]["product"]["price"],
							$data["item"]["product"]["quantity"],
							$data["item"]["product"]["units"],
							$data["item"]["product"]["productid"],
							$data["item"]["product"]["description"]);

	print "Creating payment item...\n";

	// upload data and get ID back
	$result = $client->set_invoice_payment($data["id"],
							$data["invoicetype"],
							$data["item"]["payment"]["id"],
							$data["item"]["payment"]["date_trans"],
							$data["item"]["payment"]["chartid"],
							$data["item"]["payment"]["amount"],
							$data["item"]["payment"]["source"],
							$data["item"]["payment"]["description"]);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}






/*
	GET INVOICE DETAILS
*/

try
{
	$data_tmp = $client->get_invoice_details($data["id"], $data["invoicetype"]);

	print "Executing get_invoice_details for ID ". $data["id"] ."\n";
	print_r($data_tmp);

}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}






/*
	GET INVOICE ITEMS
*/
try
{
	// items
	$data_tmp = $client->get_invoice_items($data["id"], $data["invoicetype"]);

	print "Executing get_invoice_items for ID ". $data["id"] ."\n";
	print_r($data_tmp);


	// taxes
	$data_tmp = $client->get_invoice_taxes($data["id"], $data["invoicetype"]);

	print "Executing get_invoice_taxes for ID ". $data["id"] ."\n";
	print_r($data_tmp);


	// payments
	$data_tmp = $client->get_invoice_payments($data["id"], $data["invoicetype"]);

	print "Executing get_invoice_payments for ID ". $data["id"] ."\n";
	print_r($data_tmp);


}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}




/*
	DELETE INVOICE
*/



// delete invoice standard item
try
{
	print "Deleting invoice standard item with ID of ". $data["item"]["standard"]["id"] ."\n";
	$client->delete_invoice_item($data["item"]["standard"]["id"]);


	print "Deleting invoice with ID of ". $data["id"] ."\n";
	$client->delete_invoice($data["id"], $data["invoicetype"]);
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");
}



?>
