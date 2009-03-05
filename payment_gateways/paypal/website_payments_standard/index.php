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

session_start();
include_once("include/security.php");
include_once("include/errors.php");

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

	// filter any error data
	if ($_SESSION["error"]["message"])
	{
		security_script_error_input('/^[0-9]*$/', "code_customer");
		security_script_error_input('/^[0-9]*$/', "code_invoice");
	}


	// draw any error/notificiation messages	
	error_render_message();

	// Page Content/Logic
	print "<tr><td width=\"760\" bgcolor=\"#ffffff\" style=\"padding: 3px; border: 1px solid #ffe38c;\">";

		print "<h3>AMBERDMS PAYMENT GATEWAY (sample)</h3>";

		print "<p>This page allows you to pay your invoices via either credit card or a paypal account.</p>";


		// invoice & customer details form
		print "<form method=\"get\" action=\"invoices.php\">";
		print "<table width=\"100%\">";

		print "<tr ". error_render_table("code_customer") .">";
			print "<td width=\"30%\"><b>Customer ID</b></td>";
			print "<td width=\"70%\"><input name=\"code_customer\" style=\"width: 100px;\" value=\"". $_SESSION["error"]["code_customer"] ."\"></td>";
		print "</tr>";

		print "<tr ". error_render_table("code_invoice") .">";
			print "<td width=\"30%\"><b>Invoice Number</b></td>";
			print "<td width=\"70%\"><input name=\"code_invoice\" style=\"width: 100px;\" value=\"". $_SESSION["error"]["code_invoice"] ."\"></td>";
		print "</tr>";

		print "</table>";

		print "<input name=\"submit\" type=\"submit\" value=\"Display Account\">";
		print "</form>";


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
