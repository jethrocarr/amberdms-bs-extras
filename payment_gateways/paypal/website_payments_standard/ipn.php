<?php
/*
	ipn.php

	Recieves and processes payments reported by paypal via IPN and creates
	the payments in the Amberdms Billing System via the SOAP API.

	Copyright (c) 2009 Amberdms Ltd


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

// session start
session_start();

// includes
include_once("include/config.php");
include_once("include/security.php");
include_once("include/errors.php");




/*
	Validate IPN Submission

	We need to make sure the IPN submission was really from paypal - to do this
	we send all the data back at paypal and wait for a return message.
*/

// read the post from PayPal system and add 'cmd'
$req = 'cmd=_notify-validate';

foreach ($_POST as $key => $value)
{
	$value = urlencode(stripslashes($value));
	$req .= "&$key=$value";
}

// post back to PayPal system to validate
$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

$fp = fsockopen ("ssl://". $GLOBALS["config"]["paypal"]["url"], 443, $errno, $errstr, 30);


$valid = 0;

if (!$fp)
{
	// HTTP error - report to admins
	mail($GLOBALS["config"]["email_debug"], "PayPal IPN: HTTP error", "Unknown HTTP error occured whilst validating IPN against ". $GLOBALS["config"]["paypal"]["url"] ."\n");
}
else
{
	fputs ($fp, $header . $req);

	while (!feof($fp))
	{
		$res = fgets ($fp, 1024);

		if (strcmp ($res, "VERIFIED") == 0)
		{
			// valid transaction information, process
			$valid = 1;
		}
		elseif (strcmp ($res, "INVALID") == 0)
		{
			// report to admins
			mail($GLOBALS["config"]["email_debug"], "PayPal IPN: INVALID IPN", "$res\n $req");
			exit(0);
		}
	}
}

fclose ($fp);





if ($valid)
{
	/*
		Read in desired values

		Since the Amberdms Billing System has all the customer's information already, all we really care about
		is the value of the invoice field, transaction ID and amount paid.
	*/

	$data["txn_id"]			= security_script_input("/^\S*$/", $_POST["txn_id"]);
	$data["txn_type"]		= security_script_input("/^\S*$/", $_POST["txn_type"]);

	$data["payment_status"]		= security_script_input("/^\S*$/", $_POST["payment_status"]);
	$data["code_invoice"]		= security_script_input("/^\S*$/", $_POST["invoice"]);
	$data["mc_gross"]		= security_script_input("/^\S*$/", $_POST["mc_gross"]);
	$data["mc_fee"]			= security_script_input("/^\S*$/", $_POST["mc_fee"]);




	/*
		Check payment type

		We can only process web accept payment types and don't care about any other types.
	*/

	if ($data["txn_type"] != "web_accept")
	{
		mail($GLOBALS["config"]["email_accounts"], "PayPal IPN: Unknown Payment Type", "Recieved an unknown payment type of ". $data["txn_type"] ."  will not automatically enter transaction into Amberdms Billing System.\n");
		exit(0);
	}


	/*
		Check payment status

		We only enter Confirmed payments into the Amberdms Billing System - any other status
		could be a refund, cancellation or that the payment is pending - pending information is of
		little use to the billing system, since it only handles actual payments.
	*/

	if ($data["payment_status"] == "Pending")
	{
		// payment is pending - email accounts to let them know
		mail($GLOBALS["config"]["email_accounts"], "PayPal IPN: Pending Payment", "Recieved a payment of ". $data["mc_gross"] ." for invoice ". $data["code_invoice"] ." which is now pending. Payment will be added to the Amberdms Billing System once it has been cleared.\n");
		exit(0);
	}
	elseif ($data["payment_status"] != "Completed")
	{
		// some other status
		mail($GLOBALS["config"]["email_accounts"], "PayPal IPN: Unknown Status", "Recieved an unknown status IPN of ". $data["payment_status"] ." for invoice ". $data["code_invoice"] ."\n");
		exit(0);
	}



	/*
		Authenticate with the Amberdms Billing System
	*/

	// connect
	$client = new SoapClient($GLOBALS["config"]["url"] . "/authenticate/authenticate.wsdl");
	$client->__setLocation($GLOBALS["config"]["url"] . "/authenticate/authenticate.php");


	// login & get PHP session ID
	try
	{
		$sessionid = $client->login($GLOBALS["config"]["auth_account"], $GLOBALS["config"]["auth_username"], $GLOBALS["config"]["auth_password"]);
	}
	catch (SoapFault $exception)
	{
		mail($GLOBALS["config"]["email_debug"], "PayPal IPN: Authentication Failure", "SOAP error authentication failed for transaction ". $data["txn_id"] .": .". $exception->getMessage() ."\n");
		exit(0);
	}

	unset($client);



	/*
		Check if this payment has already been entered - this prevents multiple payments
		from being created if duplicate IPNs are recieved.
	*/

	// fetch payments for the selected invoice
	$client = new SoapClient($GLOBALS["config"]["url"] . "/accounts/invoices_manage.wsdl");
	$client->__setLocation($GLOBALS["config"]["url"] . "/accounts/invoices_manage.php?$sessionid");

	try
	{
		$data_invoice_id	= $client->get_invoice_id_from_code($data["code_invoice"], "ar");
		$data_invoice		= $client->get_invoice_payments($data_invoice_id, "ar");
	}
	catch (SoapFault $exception)
	{
		mail($GLOBALS["config"]["email_debug"], "PayPal IPN: Invoice Error", "SOAP error fetching data for invoice ". $data["code_invoice"] ." for transaction ". $data["txn_id"] .": .". $exception->getMessage() ."\n");
		exit(0);
	}


	// check if any of the payments match the txn_id
	foreach ($data_invoice as $payment)
	{
		if ($payment["source"] == "PayPal IPN")
		{

			if ($payment["description"] == $data["txn_id"])
			{
				// duplicate payment
				mail($GLOBALS["config"]["email_debug"], "PayPal IPN: Duplicate IPN", "The IPN transaction ". $data["txn_id"] ." has already been entered into the Amberdms Billing System.\n");
				exit(0);
			}
		}
	}


	/*
		Add payment to invoice

		We now define and post the payment to the Amberdms Billing System
	*/


	try
	{
		$client->set_invoice_payment($data_invoice_id,
						"ar",
						"",
						date("Y-m-d"),
						$GLOBALS["config"]["payment"]["chartid"],
						$data["mc_gross"],
						"PayPal IPN",
						$data["txn_id"]);
	}
	catch (SoapFault $exception)
	{
		mail($GLOBALS["email_debug"], "PayPal IPN: Invoice Error", "SOAP error fetching data for invoice ". $data["code_invoice"] ." for transaction ". $data["txn_id"] .": .". $exception->getMessage() ."\n");
		exit(0);
	}


	/*
		Create GL transaction for the paypal fees

		Every paypal transaction has it's fees subtracted from the amount deposited into the account
		balance, so we need to show this in the Amberdms Billing System by adding a GL transaction to
		transfer fees from the paypal account to the paypal expenses account.
	*/
	$client = new SoapClient($GLOBALS["config"]["url"] . "/accounts/gl_manage.wsdl");
	$client->__setLocation($GLOBALS["config"]["url"] . "/accounts/gl_manage.php?$sessionid");


	try
	{
		// set the details
		$client->prepare_gl_details("",
						date("Y-m-d"),
						"1",
						"PayPal Fees for transaction ". $data["txn_id"] ."",
						"on",
						"");

		// credit the paypal account
		$client->prepare_gl_addtrans($GLOBALS["config"]["payment"]["chartid"],
                                                $data["mc_fee"], 
                                                "0",
						"", 
                                                "");


		// debit the paypal fees account
		$client->prepare_gl_addtrans($GLOBALS["config"]["payment"]["fees_chartid"],
                                                "0", 
                                                $data["mc_fee"],
						"", 
                                                "");


	
		// save the GL transaction
		$client->set_gl_save("0");

	}
	catch (SoapFault $exception)
	{
		mail($GLOBALS["email_debug"], "PayPal IPN: GL Error", "SOAP error creating GL transaction fees for invoice ". $data["code_invoice"] ." for transaction ". $data["txn_id"] .": .". $exception->getMessage() ."\n");
		exit(0);
	}



	/*
		Complete
	*/
	mail($GLOBALS["config"]["email_accounts"], "PayPal IPN: Completed Payment", "A payment of ". $data["mc_gross"] ." has been made for invoice ". $data["code_invoice"] ."\n");

} // end of valid transaction


?>
