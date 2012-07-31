#!/usr/bin/perl -w

use strict;

use SOAP::Lite;
use Data::Dumper qw(Dumper);


#
#	Configuration
#


my $url            = "https://devel-webapps.local.amberdms.com/development/amberdms_opensource/oss-amberdms-bs/trunk/api/";

my $auth_account	= 0;		# only used by Amberdms Billing System - Hosted Version
my $auth_username	= "soap";
my $auth_password	= "setup123";



#
#	Authenticate
#

# connect
my $client 	= SOAP::Lite
#			-> service("$url/authenticate/authenticate.wsdl")
			-> proxy("$url/authenticate/authenticate.php")
			-> uri("$url/authenticate/authenticate.php")
			-> on_fault(sub { my($soap, $res) = @_; 
				die ref $res ? $res->faultstring : $soap->transport->status, "\n";
				});

# login & get PHP session ID
my $result = $client->login($auth_account, $auth_username, $auth_password);

my $sessionid = $result->result;

print "Authenticated, SESSION ID is: $sessionid\n";



#
#	Connect to accounts_charts_manage
#

# connect
$client 	= SOAP::Lite
			-> proxy("$url/accounts/charts_manage.php?$sessionid")
			-> uri("$url/accounts/charts_manage.php?$sessionid")
			-> on_fault(sub { my($soap, $res) = @_; 
				die ref $res ? $res->faultstring : $soap->transport->status, "\n";
				});

#
# Fetch chart type list
#

$result = $client->get_chart_type_list();

warn "The value of the results are <\n" . Dumper( \$result->result ) . "\n>\n"; 


#my @list = @{$result->result};
#
#foreach my $line (@list)
#{
#	print "-----------------------------------------\n";
#	# print description for every listing
#	foreach my $key (keys %{$line})
#	{
#		print $key, ": ", $line->{$key} || '', "\n";
#	}        
#}







