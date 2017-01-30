#!/usr/bin/perl

use strict;
use warnings;
use DBI;
use DateTime;
use Try::Tiny;
use Email::Send;
use Getopt::Long;
use OpenSRF::System;

#load config values
my %config = do 'erconfig.pl';

print "Starting Process\n";
update_log_file($config{log_file}, "Starting Process");

#decalare variables
our ($config, $collection_table, $data_table, $start_date, $end_date, $year_month, $consortium_ou);
$year_month = DateTime->now()->subtract( months => 1 )->strftime('%Y%m');

#setup default values
my $start = 0;
my $show_help = 0;
my $collect = 1;
my $consolidate = 1;

#check for command line options
GetOptions(
	'help' => \$show_help,
	'start=s' => \$start, 
	'period=i' => \$year_month, 
	'collect!' => \$collect,
	'consolidate!' => \$consolidate,
);

if ($show_help) {
	show_help();
	exit 0;
}

#Datbase connectivity
my $sth;
my $row;
my $report;
my $email_server;
my $email_sender;
my $statement_timeout;
my (%data_db);

if ($config{config}) {
	OpenSRF::System->bootstrap_client( config_file => $config{config} );
	my $sc = OpenSRF::Utils::SettingsClient->new;
	
	$data_db{db_driver} = $sc->config_value( reporter => setup => database => 'driver' );
	$data_db{db_host}   = $sc->config_value( reporter => setup => database => 'host' );
	$data_db{db_port}   = $sc->config_value( reporter => setup => database => 'port' );
	$data_db{db_name}   = $sc->config_value( reporter => setup => database => 'db' );
	if (!$data_db{db_name}) {
	    $data_db{db_name} = $sc->config_value( reporter => setup => database => 'name' );
	    print STDERR "WARN: <database><name> is a deprecated setting for database name. For future compatibility, you should use <database><db> instead." if $data_db{db_name}; 
	}
	$data_db{db_user}   	= $sc->config_value( reporter => setup => database => 'user' );
	$data_db{db_pw}     	= $sc->config_value( reporter => setup => database => 'pw' );
	
	$email_server		= $sc->config_value( email_notify => 'smtp_server' );
	$email_sender 		= $sc->config_value( email_notify => 'sender_address' );
	$statement_timeout   	= $sc->config_value( reporter => setup => 'statement_timeout' ) // 60;
}
else {
	$data_db{db_driver} = $config{db_driver};
	$data_db{db_host}   = $config{db_host};
	$data_db{db_port}   = $config{db_port};
	$data_db{db_name}   = $config{db_name};
	$data_db{db_user}   = $config{db_user};
	$data_db{db_pw}     = $config{db_pw};
	$email_server 		= $config{email_host};
	$email_sender 		= $config{sender_address};
	$statement_timeout 	= $config{db_timeout};
}

$statement_timeout = 60 unless $statement_timeout =~ /^\d+$/;

die "Unable to retrieve database connection information"
    unless ($data_db{db_driver} && $data_db{db_host} && $data_db{db_port} && $data_db{db_name} && $data_db{db_user});
	
my $data_dsn = "dbi:" . $data_db{db_driver} . ":dbname=" . $data_db{db_name} . ';host=' . $data_db{db_host} . ';port=' . $data_db{db_port};
	
my $data_dbh = DBI->connect(
	$data_dsn,
	$data_db{db_user},
	$data_db{db_pw},
	{ AutoCommit => 1,
		pg_expand_array => 0,
	  	pg_enable_utf8 => 1,
	  	RaiseError => 1
	}
);
$data_dbh->do('SET statement_timeout = ?', {}, ($statement_timeout * 60 * 1000));

#load query definitions
$consortium_ou = $config{consortium_ou};
$data_table = $config{db_schema}.'.executive_reports_data';
$collection_table = $config{db_schema}.'.executive_reports_data_collection';

#calculate date range for queries in mm/dd/yyyy format with dd=01 for between clause in sql
$end_date = DateTime->new(
		year=>(substr $year_month,0,4),
		month=>(substr $year_month,4,2),
		day=>01
		)->add(months => 1)->strftime('%m/01/%Y');

$start_date = DateTime->new(
		year=>(substr $year_month,0,4),
		month=>(substr $year_month,4,2),
		day=>01)->strftime('%m/01/%Y');
		
my %queries = do 'erqueries.pl';
	
if ($collect) {

	print "Processing monthly data for $year_month\n";
	update_log_file($config{log_file}, "Processing monthly data for $year_month");

	if ($start) {	
		if (!defined $queries{$start}) {
			print "Invalid report $start specified. Aborting.\n";
			update_log_file($config{log_file}, "Invalid report $start specified. Aborting.");
			exit 1;
		}
		else {	
			print "Skipping to report $start\n";
			update_log_file($config{log_file}, "Skipping to report $start");
		}
	}

	foreach my $query (sort keys(%queries)) {
	
		#skip to starting point if specified 
		next unless ($start eq '' || $start le $query) && ($query ne 'consolidate');
	
		#execute
		try {
			if (length $query == 2) {
				$report = uc substr $query, 0 , 2;		
				$row = $data_dbh->do("delete from $collection_table where upper(report)='$report'");
			}

			print "Executing $query\n$queries{$query}\n";
			update_log_file($config{log_file}, "Starting query $query");
			update_status_file($config{status_file}, "$query");			
        	$row = $data_dbh->do($queries{$query});
		}
		catch {
			update_log_file($config{log_file}, $DBI::errstr);
			die $DBI::errstr;
		};
		
		print "Completed query $query\n";
		update_log_file($config{log_file}, "Completed query $query");
	}
}
else {
	print "Skipping data collection\n";
	update_log_file($config{log_file}, "Skipping data collection");
}

#consolidate the data for faster access
if ($consolidate) {
	print "Starting data consolidation\n";
	update_log_file($config{log_file}, "Starting data consolidation");
	update_status_file($config{status_file}, "Consolidation");

	#consolidate
	try {
		$row = $data_dbh->do("delete from $data_table where year_month=$year_month");
       	$row = $data_dbh->do($queries{consolidate});
	}
	catch {
		update_log_file($config{log_file}, $DBI::errstr);
		die $DBI::errstr;
	};

	print "Completed data consolidation\n";
	update_log_file($config{log_file}, "Completed data consolidation");
}

print "Process completed\n";
update_log_file($config{log_file}, "Process Completed");
update_status_file($config{status_file}, "Done");

my $message = <<'NOTIFICATION_EMAIL';
To: $config{email_notify}
From: $email_sender
Subject: Executive Reports Process
  
The Executive Reports process completed at 
NOTIFICATION_EMAIL

my $sender = Email::Send->new({mailer => 'SMTP'});
$sender->mailer_args([Host => $email_server]);
$sender->send($message);
  
$data_dbh->disconnect;

sub update_status_file {
	my $status_file = shift;
	my $msg = shift;

	open(STATUSFILE, ">$status_file") or die "Cannot write to status file.";
	print STATUSFILE "$msg\n";
	close STATUSFILE;
}

sub update_log_file {
	my $log_file = shift;
	my $msg = shift;

	my $current_time = DateTime->now()->strftime('%F %T');
	open(LOGFILE, ">>$log_file") or die "Cannot write to log file.";
	print LOGFILE "$current_time $msg\n";
	close LOGFILE;
}

sub show_help {
	print "Help";
}

