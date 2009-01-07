<?php
//SoapClient->_cookies[<session_id_name>][0]


	// authenticate
	$client = new SoapClient("usage.wsdl");

	$client->__setLocation('https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api/services/usage.php');
	$sessionid = $client->authenticate("username", "password");

	unset($client);



	// post records
	$client = new SoapClient("usage.wsdl");
	//$client->__setLocation("https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api/services/usage.php?$sessionid");
	$client->__setLocation("https://devel-centos5-64.jethrocarr.local/development/amberdms/billing_system/htdocs/api/services/usage.php?PHPSESSID=9q3eqr30jm5n4nrg1rfjnr4964");
    	print_r($client->set_usage_record("collector", "customer_id", "date", "usage1", "usage2"));
    	print_r($client->set_usage_record("collector", "customer_id", "date", "usage1", "usage2"));


?>
