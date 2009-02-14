<?php
/*
	sql-ledger-migration.php

	(c) Copyright 2009 Amberdms Ltd

	This script allows you to migrate data from SQL-Ledger into the Amberdms Billing System. This script
	connects to the SQL-Ledger PostgreSQL database, converts the data and inserts into the Amberdms Billing System
	via the SOAP APIs.

	----
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License version 3
	only as published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
	----


	Program Requirements:

	PHP version 5+

	PHP extensions:
		* php-soap
		* php-pgsql

*/


/*
	Configuration
*/
$sql_ledger_db_hostname		= "localhost";
$sql_ledger_db_port		= "5432";
$sql_ledger_db_name		= "amberdms";
$sql_ledger_db_username		= "sql-ledger";
//$sql_ledger_db_password	= "setme";


$url		= "https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api";

$auth_account	= 0;		// only used by Amberdms Billing System - Hosted Version
$auth_username	= "soap";
$auth_password	= "setup123";


/*
	CONNECT TO POSTGRESQL DATABASE
*/

$pgdb = pg_connect("host=$sql_ledger_db_hostname port=$sql_ledger_db_port dbname=$sql_ledger_db_name user=$sql_ledger_db_username password=$sql_ledger_db_password");

if (!$pgdb)
{
	die("Unable to connect to postgres database");
}



/*
	AUTHENTICATE TO BILLING SYSTEM
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
	MIGRATE CHARTS/ACCOUNTS
*/

// connect to SOAP APi
$client = new SoapClient("$url/accounts/charts_manage.wsdl");
$client->__setLocation("$url/accounts/charts_manage.php?$sessionid");

// fetch information we need from Amberdms Billing System in order to
// be able to migrate the charts.
try
{
	// create array of all chart types
	print "Fetching array of chart types...\n";
	$list_chart_types = $client->list_chart_type();
}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}




// fetch all charts from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM chart ORDER BY accno");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing chart/account ". $pgrow["accno"] ."... ";
	$data = array();

	// lookup the chart type
	if ($pgrow["charttype"] == "H")
	{
		$data["chart_type"] = "Heading";
	}
	else
	{
		switch ($pgrow["category"])
		{
			case "A":
				$data["chart_type"] = "Asset";
			break;

			case "L":
				$data["chart_type"] = "Liability";
			break;

			case "Q":
				$data["chart_type"] = "Equity";
			break;

			case "I":
				$data["chart_type"] = "Income";
			break;

			case "E":
				$data["chart_type"] = "Expense";
			break;
		}
	}

	// fetch the chartID for the selected charttype
	foreach ($list_chart_types as $data_chart_type)
	{	
		if ($data_chart_type["value"] == $data["chart_type"])
		{
			$data["chart_type_id"] = $data_chart_type["id"];
		}
	}



	try
	{

		// create chart
		$data["id"] = $client->set_chart_details("",
							$pgrow["accno"],
							$pgrow["description"],
							$data["chart_type_id"]);

		$migrationmap["charts"][ $pgrow["id"] ] = $data["id"];


		// create array of menu options
		$data["link_options"] = split(":", $pgrow["link"]);

		foreach ($data["link_options"] as $data_link_option)
		{
			switch ($data_link_option)
			{
				case "AR":
					$client->set_chart_menuoption($data["id"], "ar_summary_account", "on");
				break;

				case "AR_amount":
					$client->set_chart_menuoption($data["id"], "ar_income", "on");
				break;

				case "AR_paid":
					$client->set_chart_menuoption($data["id"], "ar_payment", "on");
				break;

				case "AR_tax":
					$client->set_chart_menuoption($data["id"], "tax_summary_account", "on");
				break;

				case "AP":
					$client->set_chart_menuoption($data["id"], "ap_summary_account", "on");
				break;

				case "AP_amount":
					$client->set_chart_menuoption($data["id"], "ap_expense", "on");
				break;

				case "AP_paid":
					$client->set_chart_menuoption($data["id"], "ap_payment", "on");
				break;

				case "AP_tax":
					$client->set_chart_menuoption($data["id"], "tax_summary_account", "on");
				break;
			}
		}

	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

	print "complete\n";
} // end of loop through charts



/*
	MIGRATE TAXES
*/

print "PERFORMING TAX MIGRATION...\n";

// connect to SOAP API
$client = new SoapClient("$url/accounts/taxes_manage.wsdl");
$client->__setLocation("$url/accounts/taxes_manage.php?$sessionid");


// fetch all taxes from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM tax");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing tax ". $pgrow["name"] ."... ";
	
	$data = array();


	// fetch name of the tax by getting the name of the chart.
	$pg_chart_result	= pg_query("SELECT description FROM chart WHERE id='". $pgrow["chart_id"] ."' LIMIT 1");
	$pg_chart_row		= pg_fetch_array($pg_chart_result);
	
	$data["name_tax"]	= $pg_chart_row["description"];


	// convert tax rate
	$data["taxrate"]	= $pgrow["rate"] * 100;


	try
	{
		// upload data and get ID back
		$migrationmap["taxes"][ $pgrow["chart_id"] ] = $client->set_tax_details("",
								$data["name_tax"],
								$data["taxrate"],
								$migrationmap["charts"][ $pgrow["chart_id"] ],
								$pgrow["taxnumber"],
								"");

		print "complete\n";
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

} // end of loop through taxes





/*
	IMPORT EMPLOYEES
*/

print "PERFORMING EMPLOYEE MIGRATION...\n";


// connect to SOAP API
$client = new SoapClient("$url/hr/staff_manage.wsdl");
$client->__setLocation("$url/hr/staff_manage.php?$sessionid");


// fetch all employees from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM employee");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing employee ". $pgrow["name"] ."... ";

	try
	{
		// upload data and get ID back
		$migrationmap["employees"][ $pgrow["id"] ] = $client->set_employee_details("",
							$pgrow["name"],
							$pgrow["employeenumber"],
							$pgrow["role"],
							$pgrow["workphone"],
							"",
							$pgrow["email"],
							$pgrow["startdate"],
							$pgrow["enddate"]);

		print "complete\n";
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");
	
	}
} // end of loop through employees.







/*
	MIGRATE CUSTOMERS
*/

print "PERFORMING CUSTOMER MIGRATION...\n";

// connect to SOAP API
$client = new SoapClient("$url/customers/customers_manage.wsdl");
$client->__setLocation("$url/customers/customers_manage.php?$sessionid");


// fetch all customers from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM customer");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing customer ". $pgrow["name"] ."... ";

	try
	{
		// upload data and get ID back
		print "details ";
		$migrationmap["customers"][ $pgrow["id"] ] = $client->set_customer_details("",
								$pgrow["customernumber"],
								$pgrow["name"],
								$pgrow["contact"],
								$pgrow["email"],
								$pgrow["phone"],
								$pgrow["fax"],
								$pgrow["startdate"],
								$pgrow["enddate"],
								$pgrow["taxnumber"],
								"",
								$pgrow["address1"] ."\n". $pgrow["address2"],
								$pgrow["city"],
								$pgrow["state"],
								$pgrow["country"],
								$pgrow["zipcode"],
								"",
								"",
								"",
								"",
								"");



		// upload all the taxes enabled for this customer
		print "taxes ";
		$result_taxes = pg_query("SELECT chart_id FROM customertax WHERE customer_id='". $pgrow["id"] ."'");

		while ($pgrow_taxes = pg_fetch_array($result_taxes))
		{
			$client->set_customer_tax($migrationmap["customers"][ $pgrow["id"] ], $migrationmap["taxes"][ $pgrow_taxes["chart_id"] ], "on");
		}

		print "complete\n";
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

} // end of loop through customers




/*
	MIGRATE VENDORS
*/

print "PERFORMING VENDOR MIGRATION...\n";

// connect to SOAP API
$client = new SoapClient("$url/vendors/vendors_manage.wsdl");
$client->__setLocation("$url/vendors/vendors_manage.php?$sessionid");


// fetch all vendors from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM vendor");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing vendor ". $pgrow["name"] ."... ";

	try
	{
		// upload data and get ID back
		print "details ";
		$migrationmap["vendors"][ $pgrow["id"] ] = $client->set_vendor_details("",
								$pgrow["vendornumber"],
								$pgrow["name"],
								$pgrow["contact"],
								$pgrow["email"],
								$pgrow["phone"],
								$pgrow["fax"],
								$pgrow["startdate"],
								$pgrow["enddate"],
								$pgrow["taxnumber"],
								"",
								$pgrow["address1"] ."\n". $pgrow["address2"],
								$pgrow["city"],
								$pgrow["state"],
								$pgrow["country"],
								$pgrow["zipcode"],
								"",
								"",
								"",
								"",
								"");


		// upload all the taxes enabled for this vendor
		print "taxes ";
		$result_taxes = pg_query("SELECT chart_id FROM vendortax WHERE vendor_id='". $pgrow["id"] ."'");

		while ($pgrow_taxes = pg_fetch_array($result_taxes))
		{
			$client->set_vendor_tax($migrationmap["vendors"][ $pgrow["id"] ], $migrationmap["taxes"][ $pgrow_taxes["chart_id"] ], "on");
		}


		print "complete\n";
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

} // end of loop through vendors









/*
	MIGRATE PRODUCTS
*/

print "PERFORMING PRODUCT MIGRATION...\n";

// connect to SOAP API
$client = new SoapClient("$url/products/products_manage.wsdl");
$client->__setLocation("$url/products/products_manage.php?$sessionid");


// fetch all products from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$result = pg_query("SELECT * FROM parts");

while ($pgrow = pg_fetch_array($result))
{
	print "Processing product ". $pgrow["description"] ."... ";
	
	try
	{
		// upload data and get ID back
		print "details ";
		$migrationmap["products"][ $pgrow["id"] ] = $client->set_product_details("",
							$pgrow["partnumber"],
							$pgrow["description"],
							$pgrow["unit"],			// TODO: fix unit code
							$pgrow["notes"],
							$pgrow["lastcost"],
							$pgrow["sellprice"],
							$pgrow["priceupdate"],
							"0", 				// should be $pgrow["onhand"], need to resolve negative int security check issues
							"0",
							"0",
							"",
							$migrationmap["charts"][ $pgrow["income_accno_id"] ]);



		// add taxes
		// 
		// Note: SQL-Ledger has no concept of manual tax amounts, so we just need to add
		// each tax as automatic
		//

		print "taxes ";
		$result_taxes = pg_query("SELECT chart_id FROM partstax WHERE parts_id='". $pgrow["id"] ."'");

		while ($pgrow_taxes = pg_fetch_array($result_taxes))
		{
			$client->set_product_tax($migrationmap["products"][ $pgrow["id"] ],
						"",
						$migrationmap["taxes"][ $pgrow_taxes["chart_id"] ],
						"0",
						"0",
						"");
		}



		print "complete\n";
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

} // end of loop through products




/*
	MIGRATE AR INVOICES/TRANSACTIONS
*/


// connect to SOAP API
$client = new SoapClient("$url/accounts/invoices_manage.wsdl");
$client->__setLocation("$url/accounts/invoices_manage.php?$sessionid");


// fetch all AR invoices from SQL-Ledger and insert them one-by-one via SOAP
$pg_ar_result = pg_query("SELECT * FROM ar");

while ($pg_ar_row = pg_fetch_array($pg_ar_result))
{
	print "Processing AR invoice ". $pg_ar_row["invnumber"] ."...\n";
	$data = array();


	/*
		Determine dest_account value

		We run through the invoice items until we find a debit transaction to
		an AR account - we then use the AR account as the dest_account field for
		creating the invoice in the Amberdms Billing System.
	*/

	print "* Determining dest_account... ";
	
	$pg_acc_trans_result = pg_query("SELECT * FROM acc_trans WHERE trans_id='". $pg_ar_row["id"] ."'");

	while ($pg_acc_trans_row = pg_fetch_array($pg_acc_trans_result))
	{
		// fetch the type of chart from the chart table
		$pg_chart_result	= pg_query("SELECT link FROM chart WHERE id='". $pg_acc_trans_row["chart_id"] ."' LIMIT 1");
		$pg_chart_row		= pg_fetch_array($pg_chart_result);

		// expand the chart mode
		$data["link_options"]	= split(":", $pg_chart_row["link"]);

		if (in_array("AR", $data["link_options"]) && $pg_acc_trans_row["amount"] < 0)
		{
			// use the chart_id value to set the dest_account
			$data["dest_account"] = $migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ];
		}
	}

	print "complete\n";



	/*
		Create invoice & set details
	*/
	print "* Creating invoice... ";

	try
	{

		// upload data and get ID back
		$data["id"] = $client->set_invoice_details("",
								"ar",
								0,
								$migrationmap["customers"][ $pg_ar_row["customer_id"] ],
								$migrationmap["employees"][ $pg_ar_row["employee_id"] ],
								$data["dest_account"],
								$pg_ar_row["invnumber"],
								$pg_ar_row["ordnumber"],
								$pg_ar_row["ponumber"],
								$pg_ar_row["duedate"],
								$pg_ar_row["transdate"],
								"",
								"",
								$pg_ar_row["notes"],
								"off");

	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

	print "complete\n";



	/*
		Process all invoice line items (inc taxes)
	*/
	print "Processing invoice items... ";

	$pg_acc_trans_result = pg_query("SELECT * FROM acc_trans WHERE trans_id='". $pg_ar_row["id"] ."'");

	while ($pg_acc_trans_row = pg_fetch_array($pg_acc_trans_result))
	{
		$structure = NULL;

		// fetch the type of chart from the chart table
		$pg_chart_result	= pg_query("SELECT link FROM chart WHERE id='". $pg_acc_trans_row["chart_id"] ."' LIMIT 1");
		$pg_chart_row		= pg_fetch_array($pg_chart_result);

		// expand the chart mode
		$data["link_options"]	= split(":", $pg_chart_row["link"]);


		if (in_array("AR_tax", $data["link_options"]) && $pg_acc_trans_row["amount"] >= 0)
		{
			/*
				Tax Item
			*/

		}

		if (in_array("AR_paid", $data["link_options"]) && $pg_acc_trans_row["amount"] < 0)
		{
			/*
				Payment Item
			*/

			$structure["date_trans"]	= $pg_acc_trans_row["transdate"];
			$structure["chartid"]		= $migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ];
			$structure["amount"]		= ($pg_acc_trans_row["amount"] * -1);				// convert negative to positive
			$structure["source"]		= $pg_acc_trans_row["source"];
			$structure["description"]	= $pg_acc_trans_row["memo"];
			
			$data["item"]["payments"][]	= $structure;
		}


		if (in_array("AR_amount", $data["link_options"]) && $pg_acc_trans_row["amount"] >= 0)
		{
			if ($pg_acc_trans_row["invoice_id"] != 0)
			{
				/*
					Product Item
				*/

				// fetch details from the invoice table
				$pg_invoice_result	= pg_query("SELECT * FROM invoice WHERE id='". $pg_acc_trans_row["invoice_id"] ."' LIMIT 1");
				$pg_invoice_row		= pg_fetch_array($pg_invoice_result);

				$structure["price"]		= $pg_invoice_row["sellprice"];
				$structure["quantity"]		= $pg_invoice_row["qty"];
				$structure["units"]		= $pg_invoice_row["unit"];
				$structure["productid"]		= $migrationmap["products"][ $pg_invoice_row["parts_id"] ];
				$structure["description"]	= $pg_invoice_row["description"];

				$data["item"]["products"][]	= $structure;
			}
			else
			{
				/*
					Standard Item
				*/


			}
		}
	}

	print "complete\n";



	/*
		Process product items
	*/
	if ($data["item"]["products"])
	{
		foreach ($data["item"]["products"] as $item_row)
		{
			print "* Adding product to invoice... ";

			try
			{
				$client->set_invoice_item_product($data["id"],
								"ar",
								"",
								$item_row["price"],
								$item_row["quantity"],
								$item_row["units"],
								$item_row["productid"],
								$item_row["description"]);
			}
			catch (SoapFault $exception)
			{
				die( "Fatal Error: ". $exception->getMessage() ."\n");
			}

			print "complete\n";
		}
	}


	/*
		Process payment items
	*/
	if ($data["item"]["payments"])
	{
		foreach ($data["item"]["payments"] as $item_row)
		{
			print "* Adding payment to invoice... ";

			try
			{
				$result = $client->set_invoice_payment($data["id"],
									"ar",
									"0",
									$item_row["date_trans"],
									$item_row["chartid"],
									$item_row["amount"],
									$item_row["source"],
									$item_row["description"]);
			}
			catch (SoapFault $exception)
			{
				die( "Fatal Error: ". $exception->getMessage() ."\n");
			}

			print "complete\n";
		}
	}


	/*
		Process tax override

		Only needed if invoice has any standard items, which means the tax value may
		have been modified.
	*/
	if ($data["item"]["standards"])
	{
		foreach ($data["item"]["taxes"] as $item_row)
		{
			print "tax_override ";
		}
	}




	print "complete.\n";



} // end of loop through invoices






/*
	MIGRATE AP INVOICES/TRANSACTIONS

	(Note: this may appear like code duplication to the AR invoice code above,
	but there are some import key differences in calculations - be aware that most
	items are reversed (eg negative instead of positive, etc)
*/


// connect to SOAP API
$client = new SoapClient("$url/accounts/invoices_manage.wsdl");
$client->__setLocation("$url/accounts/invoices_manage.php?$sessionid");


// fetch all AP invoices from SQL-Ledger and insert them one-by-one via SOAP
$pg_ap_result = pg_query("SELECT * FROM ap");

while ($pg_ap_row = pg_fetch_array($pg_ap_result))
{
	print "Processing AP invoice ". $pg_ap_row["invnumber"] ."...\n";
	$data = array();


	/*
		Determine dest_account value

		We run through the invoice items until we find a debit transaction to
		an AR account - we then use the AR account as the dest_account field for
		creating the invoice in the Amberdms Billing System.
	*/

	print "* Determining dest_account... ";
	
	$pg_acc_trans_result = pg_query("SELECT * FROM acc_trans WHERE trans_id='". $pg_ap_row["id"] ."'");

	while ($pg_acc_trans_row = pg_fetch_array($pg_acc_trans_result))
	{
		// fetch the type of chart from the chart table
		$pg_chart_result	= pg_query("SELECT link FROM chart WHERE id='". $pg_acc_trans_row["chart_id"] ."' LIMIT 1");
		$pg_chart_row		= pg_fetch_array($pg_chart_result);

		// expand the chart mode
		$data["link_options"]	= split(":", $pg_chart_row["link"]);

		if (in_array("AP", $data["link_options"]) && $pg_acc_trans_row["amount"] < 0)
		{
			// use the chart_id value to set the dest_account
			$data["dest_account"] = $migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ];
		}
	}

	print "complete\n";



	/*
		Create invoice & set details
	*/
	print "* Creating invoice... ";
	try
	{

		// upload data and get ID back
		$data["id"] = $client->set_invoice_details("",
								"ap",
								0,
								$migrationmap["vendors"][ $pg_ap_row["vendor_id"] ],
								$migrationmap["employees"][ $pg_ap_row["employee_id"] ],
								$data["dest_account"],
								$pg_ap_row["invnumber"],
								$pg_ap_row["ordnumber"],
								$pg_ap_row["ponumber"],
								$pg_ap_row["duedate"],
								$pg_ap_row["transdate"],
								"",
								"",
								$pg_ap_row["notes"],
								"off");

	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}

	print "complete\n";


	/*
		Process all invoice line items (inc taxes)
	*/
	print "* Processing invoice items... ";

	$pg_acc_trans_result = pg_query("SELECT * FROM acc_trans WHERE trans_id='". $pg_ap_row["id"] ."'");

	while ($pg_acc_trans_row = pg_fetch_array($pg_acc_trans_result))
	{
		$structure = NULL;

		// fetch the type of chart from the chart table
		$pg_chart_result	= pg_query("SELECT link FROM chart WHERE id='". $pg_acc_trans_row["chart_id"] ."' LIMIT 1");
		$pg_chart_row		= pg_fetch_array($pg_chart_result);

		// expand the chart mode
		$data["link_options"]	= split(":", $pg_chart_row["link"]);


		if (in_array("AP_tax", $data["link_options"]) && $pg_acc_trans_row["amount"] < 0)
		{
			/*
				Tax Item
			*/

			$structure["taxid"]		= $migrationmap["taxes"][ $pg_acc_trans_row["chart_id"] ];
			$structure["amount"]		= ($pg_acc_trans_row["amount"] * -1);				// convert from negative to positive

			$data["item"]["taxes"][] =  $structure;
		}

		if (in_array("AP_paid", $data["link_options"]) && $pg_acc_trans_row["amount"] >= 0)
		{
			/*
				Payment Item
			*/

			$structure["date_trans"]	= $pg_acc_trans_row["transdate"];
			$structure["chartid"]		= $migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ];
			$structure["amount"]		= $pg_acc_trans_row["amount"];
			$structure["source"]		= $pg_acc_trans_row["source"];
			$structure["description"]	= $pg_acc_trans_row["memo"];
			
			$data["item"]["payments"][]	= $structure;
		}


		if (in_array("AP_amount", $data["link_options"]) && $pg_acc_trans_row["amount"] < 0)
		{
			if ($pg_acc_trans_row["invoice_id"] != 0)
			{
				/*
					Product Item
				*/

				// fetch details from the invoice table
				$pg_invoice_result	= pg_query("SELECT * FROM invoice WHERE id='". $pg_acc_trans_row["invoice_id"] ."' LIMIT 1");
				$pg_invoice_row		= pg_fetch_array($pg_invoice_result);

				$structure["price"]		= $pg_invoice_row["sellprice"];
				$structure["quantity"]		= $pg_invoice_row["qty"];
				$structure["units"]		= $pg_invoice_row["unit"];
				$structure["productid"]		= $migrationmap["products"][ $pg_invoice_row["parts_id"] ];
				$structure["description"]	= $pg_invoice_row["description"];

				$data["item"]["products"][]	= $structure;
			}
			else
			{
				/*
					Standard Item
				*/

				$structure["chartid"]		= $migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ];
				$structure["amount"]		= ($pg_acc_trans_row["amount"] * -1);			// convert negative to positive
				$structure["description"]	= $pg_acc_trans_row["memo"];


				$data["item"]["standards"][]	= $structure;
			}
		}
	}

	print "complete.\n";




	/*
		Process product items
	*/
	if ($data["item"]["products"])
	{
		foreach ($data["item"]["products"] as $item_row)
		{
			print "* Adding product to invoice... ";

			try
			{
				$client->set_invoice_item_product($data["id"],
								"ap",
								"",
								$item_row["price"],
								$item_row["quantity"],
								$item_row["units"],
								$item_row["productid"],
								$item_row["description"]);
			}
			catch (SoapFault $exception)
			{
				die( "Fatal Error: ". $exception->getMessage() ."\n");
			}

			print "complete";
		}
	}


	/*
		Process payment items
	*/
	if ($data["item"]["payments"])
	{
		foreach ($data["item"]["payments"] as $item_row)
		{
			print "* Adding payment to invoice...";


			try
			{
				$result = $client->set_invoice_payment($data["id"],
									"ap",
									"0",
									$item_row["date_trans"],
									$item_row["chartid"],
									$item_row["amount"],
									$item_row["source"],
									$item_row["description"]);
			}
			catch (SoapFault $exception)
			{
				die( "Fatal Error: ". $exception->getMessage() ."\n");
			}

			print "complete\n";

		}
	}


	/*
		Process standard items
	*/
	if ($data["item"]["standards"])
	{
		foreach ($data["item"]["standards"] as $item_row)
		{
			print "* Add standard items to invoice... ";

			try
			{
				// add standard item
				print "details ";
				$item_row["id"] = $client->set_invoice_item_standard($data["id"],
									"ap",
									"0",
									$item_row["chartid"],
									$item_row["amount"],
									$item_row["description"]);

				// enable taxes
				//
				// we need to tick all the taxes that are part of this invoice - we later
				// will override them, so it doesn't matter about the calculations ABS will do.
				//
				if ($data["item"]["taxes"])
				{
					print "setting_taxes ";

					foreach ($data["item"]["taxes"] as $tax_row)
					{

						// enable tax
						$client->set_invoice_item_standard_tax($data["id"],
											"ap",
											$item_row["id"],
											$tax_row["taxid"],
											"on");
					}
				}
			}
			catch (SoapFault $exception)
			{
				die( "Fatal Error: ". $exception->getMessage() ."\n");
			}

			print "complete\n";
		}
	}


	/*
		Process tax override

		Only needed if invoice has any standard items, which means the tax value may
		have been modified manually.
	*/
	if ($data["item"]["standards"])
	{
		// fetch array of all tax items on the invoice & create
		// a mapping table of tax ID to item ID
		print "* Fetching array of invoice tax items... ";

		$tax_item_map	= NULL;
		$tax_items	= $client->get_invoice_taxes($data["id"], "ap");

		foreach ($tax_items as $tax_row)
		{
			$tax_item_map[ $tax_row["taxid"] ] = $tax_row["itemid"];
		}

		print "complete\n";



		// overide tax values
		foreach ($data["item"]["taxes"] as $item_row)
		{
			print "* Overriding tax amount... ";

			$client->set_invoice_override_tax($data["id"],
								"ap",
								$tax_item_map[ $item_row["taxid"] ],
								$item_row["amount"]);

			print "complete\n";
		}
	}




	print "complete.\n";



} // end of loop through AP invoices













/*


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

	print "Creating time item...\n";

	// upload data and get ID back
	$result = $client->set_invoice_item_time($data["id"],
							$data["invoicetype"],
							$data["item"]["time"]["id"],
							$data["item"]["time"]["price"],
							$data["item"]["time"]["productid"],
							$data["item"]["time"]["timegroupid"],
							$data["item"]["time"]["description"]);

	print "Creating tax item...\n";

	// upload data and get ID back
	$result = $client->set_invoice_tax($data["id"],
							$data["invoicetype"],
							$data["item"]["tax"]["id"],
							$data["item"]["tax"]["taxid"],
							$data["item"]["tax"]["manual_option"],
							$data["item"]["tax"]["manual_amount"]);

	print "Creating payment item...\n";


}
catch (SoapFault $exception)
{
	die( "Fatal Error: ". $exception->getMessage() ."\n");

}


*/











/*
	MIGRATE GL TRANSACTIONS
*/

// connect to SOAP API
$client = new SoapClient("$url/accounts/gl_manage.wsdl");
$client->__setLocation("$url/accounts/gl_manage.php?$sessionid");


// fetch all GL transactions from SQL-Ledger and insert them one-by-one
// via SOAP, creating a table mapping ID conversions.
$pg_gl_result = pg_query("SELECT * FROM gl");

while ($pg_gl_row = pg_fetch_array($pg_gl_result))
{
	print "Processing GL transaction ". $pg_gl_row["reference"] ."... ";

	try
	{
		// define transaction details
		print "details ";
		$client->prepare_gl_details("",						// should really be $pg_gl_row["reference"], but GL table is not unique
						$pg_gl_row["transdate"],
						$migrationmap["employees"][ $pg_gl_row["employee_id"] ],
						$pg_gl_row["description"],
						"off",
						$pg_gl_row["notes"]);

		// define transaction rows
		print "transactions ";

		$pg_acc_trans_result = pg_query("SELECT * FROM acc_trans WHERE trans_id='". $pg_gl_row["id"] ."'");

		while ($pg_acc_trans_row = pg_fetch_array($pg_acc_trans_result))
		{
			$data = array();

			// if the amount is negative, then transaction is a debit. If the amount is positive, then
			// the transaction is positive.
			if ($pg_acc_trans_row["amount"] < 0)
			{
				// perform a * -1 to convert the value from being negative to being positive.
				$data["debit"]	= $pg_acc_trans_row["amount"] * -1;
				$data["credit"]	= 0;
			}
			else
			{
				$data["debit"]	= 0;
				$data["credit"]	= $pg_acc_trans_row["amount"];
			}

			// if the memo is blank, then set to the main description field of the GL transaction
			if ($pg_acc_trans_row["memo"] == "")
			{
				$data["description"] = $pg_gl_row["description"];
			}
			else
			{
				$data["description"] = $pg_acc_trans_row["memo"];
			}


			// prepare transaction row
			$client->prepare_gl_addtrans($migrationmap["charts"][ $pg_acc_trans_row["chart_id"] ],
							$data["credit"], 
							$data["debit"], 
							$pg_acc_trans_row["source"],
							$data["description"]);
		} // end of loop through transactions

	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");

	}



	// save GL transaction
	try
	{
		print "saving ";

		// upload data and get ID back
		$migrationmap["gl_transactions"][ $pg_gl_row["id"] ] = $client->set_gl_save("0");
	}
	catch (SoapFault $exception)
	{
		die( "Fatal Error: ". $exception->getMessage() ."\n");
	}

	print "complete\n";

} // end of loop through GL transactions

unset($pg_gl_result);
unset($pg_acc_trans_result);




?>
