#!/usr/bin/perl

use strict;
use warnings;

db_schema 	=> 'quick_reports',
log_file 	=> 'executive_reports.log',
status_file 	=> 'erstatus.html',
email_notify 	=> 'example@youractualdomain.org',
consortium_ou	=> '1',

#config		=> '/openils/conf/opensrf_core.xml',
db_driver 	=> 'Pg',
db_host   	=> 'db.example.org',
db_port   	=> '5432',
db_name   	=> 'dbname',
db_user   	=> 'dbuser',
db_pw     	=> 'dbpass',
db_timeout 	=> '60',
email_host	=> 'emailhost.example.org',
sender_address	=> 'evergreen@example.org',

