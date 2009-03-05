<?php
/*
	Amberdms Billing System

	Invoice Payment Gateway Sample Application

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
	Get form data
*/

$code_customer	= security_script_input("/^[0-9]*$/", $_GET["code_customer"]);
$code_invoice	= security_script_input("/^[0-9]*$/", $_GET["code_invoice"]);

$_SESSION["error"]["code_customer"]	= $code_customer;
$_SESSION["error"]["code_invoice"]	= $code_invoice;


if (!$code_customer || $code_customer == "error")
{
	$_SESSION["error"]["message"]			.= "You must supply a customer ID.<br>";
	$_SESSION["error"]["code_customer-error"]	= 1;
}

if (!$code_invoice || $code_invoice == "error")
{
	$_SESSION["error"]["message"]			.= "You must supply an invoice number.<br>";
	$_SESSION["error"]["code_invoice-error"]	= 1;
}


if ($_SESSION["error"]["message"])
{
	header("Location: index.php");
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
	die("Authentication Fatal Error: ". $exception->getMessage() ."\n");
}

unset($client);



/*
	Fetch invoice & verify it matches the customer ID
*/

$client = new SoapClient($GLOBALS["config"]["url"] . "/customers/customers_manage.wsdl");
$client->__setLocation($GLOBALS["config"]["url"] . "/customers/customers_manage.php?$sessionid");

try
{
	$data_customer_id	= $client->get_customer_id_from_code($code_customer);
}
catch (SoapFault $exception)
{
	if ($exception->getMessage() == "INVALID_ID")
	{
		$_SESSION["error"]["message"]			= "Incorrect customer ID supplied";
		$_SESSION["error"]["code_customer-error"]	= 1;
	}
	else
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");
	}
}



if ($data_customer_id)
{
	$client = new SoapClient($GLOBALS["config"]["url"] . "/accounts/invoices_manage.wsdl");
	$client->__setLocation($GLOBALS["config"]["url"] . "/accounts/invoices_manage.php?$sessionid");

	try
	{
		$data_invoice_id	= $client->get_invoice_id_from_code($code_invoice, "ar");
		$data_invoice		= $client->get_invoice_details($data_invoice_id, "ar");
	}
	catch (SoapFault $exception)
	{
		if ($exception->getMessage() == "INVALID_ID")
		{
			$_SESSION["error"]["message"]			= "Incorrect invoice number supplied";
			$_SESSION["error"]["code_invoice-error"]	= 1;
		}
		else
		{
			die( "Fatal Error: ". $exception->getMessage() ."\n");
		}
	}


	if ($data_invoice["orgid"] != $data_customer_id)
	{
		$_SESSION["error"]["message"]			= "Invalid customer ID and/or invoice number";
		$_SESSION["error"]["code_customer-error"]	= 1;
		$_SESSION["error"]["code_invoice-error"]	= 1;
	}
}


// return on any errors
if ($_SESSION["error"]["message"])
{
	header("Location: index.php");
	exit(0);
}






?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Strict//EN" "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>

<head>
	<title>Amberdms Billing System</title>
	<meta name="copyright" content="Copyright (C) 2009 Amberdms Ltd.">
</head>


<body>
	<style type="text/css">@import url("include/style.css");</style>
	<a name="top"></a>

	<div align="center"><img src="images/logo.png"></div>


	<table width="900" cellpadding="0" cellspacing="5" border="0" align="center">


	<?php



	// draw any error/notificiation messages	
	error_render_message();

	// Page Content/Logic
	print "<tr><td width=\"760\" bgcolor=\"#ffffff\" style=\"padding: 3px; border: 1px solid #ffe38c;\">";

		print "<h3>AMBERDMS PAYMENT GATEWAY (sample)</h3>";
		print "<p>You can view the details of the selected invoice below:</p>";
		print "<br>";

		
		if ($data_invoice["amount_total"] == $data_invoice["amount_paid"])
		{
			/*
				Summarise the invoice as having been paid
			*/
			print "<table width=\"100%\" cellpadding=\"5\" bgcolor=\"#92dd90\">";
			print "<tr><td>";
				print "<p><b>Invoice $code_invoice is closed (fully paid).</b></p>";
				print "<p>This invoice has been fully paid and no further action is required.</p>";
			print "</td></tr>";
			print "</table>";
		
		}
		else
		{
			/*
				Display Invoice Details
			*/
			print "<table width=\"100%\" cellpadding=\"5\">";
			print "<tr>";
				print "<td width=\"30%\"><b>Invoice Number:</b></td>";
				print "<td width=\"70%\">". $data_invoice["code_invoice"] ."</td>";
			print "</tr>";
			print "<tr>";
				print "<td width=\"30%\"><b>Date Due:</b></td>";
				print "<td width=\"70%\">". $data_invoice["date_due"] ."</td>";
			print "</tr>";

			if ($data_invoice["amount_paid"] != "0.00")
			{
				print "<tr>";
					print "<td width=\"30%\"><b>Amount Paid: (so far)</b></td>";
					print "<td width=\"70%\">$". $data_invoice["amount_paid"] ."</td>";
				print "</tr>";
			}

			$data_invoice["amount_owed"] = $data_invoice["amount_total"] - $data_invoice["amount_paid"];

			print "<tr>";
				print "<td width=\"30%\"><b>Amount Owed:</b></td>";
				print "<td width=\"70%\">$". $data_invoice["amount_owed"] ."</td>";
			print "</tr>";

			print "</table>";


			/*
				Display Pay Button

				We use paypal buy now to pay the invoice.
			*/

			print "<br><br>";
			print "<form action=\"https://". $GLOBALS["config"]["paypal"]["url"] ."/cgi-bin/webscr\" method=\"post\">";

			// identify business
			print "<input type=\"hidden\" name=\"business\" value=\"". $GLOBALS["config"]["paypal"]["businessid"] ."\"> ";

			// specify buy now
			print "<input type=\"hidden\" name=\"cmd\" value=\"_xclick\">";


			// invoice details
			print "<input type=\"hidden\" name=\"invoice\" value=\"$code_invoice\">";
			print "<input type=\"hidden\" name=\"item_name\" value=\"Amberdms Invoice $code_invoice\">";
			print "<input type=\"hidden\" name=\"amount\" value=\"". $data_invoice["amount_owed"] ."\">";
			print "<input type=\"hidden\" name=\"currency_code\" value=\"NZD\">";


			// payment button
			print "<input type=\"submit\" name=\"submit\" value=\"Pay with Credit Card or Paypal\">";
		

			print "</form>";

		}



	print "<br><br><br><br><br><br>";
	print "</td></tr>";

	?>
	
	
	</table>

</body>

<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>

<p align="center" style="font-size: 10px;">(C) 2009 Amberdms Ltd.</p>


</html>


<?php

// erase error and notification arrays
$_SESSION["error"] = array();
$_SESSION["notification"] = array();
?>
