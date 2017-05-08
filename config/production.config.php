<?php
//system configs
define ('QR_ERROR_REPORTING_LEVEL', E_ALL);
define ('QR_DEFAULT_TIME_ZONE', 'America/New_York');
define ('QR_REPORT_OUTPUT_URL_PREFIX', 'https://next.gapines.org/reporter/');
define ('QR_SITE_ROOT', '/report-creator/');
//define ('QR_REPORT_OUTPUT_URL_PREFIX', 'https://reports-dev.gapines.org/reporter/');
define ('QR_REPORT_OUTPUT_URL_SUFFIX', '/report-data.html');
define ('QR_REPORT_TRANSFORM_START_YEAR', '2006');

//page headings
define ('QR_DEFAULT_LOGO_TEXT', 'Quick Reports');
define ('QR_DEFAULT_TITLE_TAG_TEXT', 'Quick Reports');
define ('QR_HOME_PAGE_WELCOME_TEXT', 'Welcome to the PINES Quick Reports Tool');
define ('QR_LOGIN_PAGE_WELCOME_TEXT', 'Welcome to the PINES Quick Reports Tool.');
define ('QR_PAGE_FOOTER_TEXT', '');

//executive reports
define ('QR_EXECUTIVE_REPORTS_ENABLED', true);
define ('QR_EXECUTIVE_REPORTS_START_YEAR', '2016');
define ('QR_EXECUTIVE_REPORTS_ADDITIONAL_ALLOWED_PERMISSIONS', '141,143');
define ('QR_EXECUTIVE_REPORTS_DESCRIPTION_URL', '');
define ('QR_EXECUTIVE_REPORTS_TITLE_TAG_TEXT', 'PINES Executive Reports');
define ('QR_EXECUTIVE_REPORTS_OUTPUT_HEADER_TITLE', 'PINES Executive Reports');
define ('QR_EXECUTIVE_REPORTS_CONSORTIUM_COLUMN_HEADING', 'PINES');
define ('QR_EXECUTIVE_REPORTS_ZERO_VALUE', '---');
define ('QR_EXECUTIVE_REPORTS_TOTAL_KEY', '__total__');
define ('QR_EXECUTIVE_REPORTS_SUBREPORT_PADDING', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
define ('QR_EXECUTIVE_REPORTS_MISSING_SUBREPORT_LABEL', '*unknown');

//Sessions
define ('QR_SESSION_TIMEOUT', 120);	//minutes

//security 
define ('QR_BROWSER_PAGE_CACHE_TIMEOUT', '20');	//seconds
define ('QR_MAX_LOGIN_ATTEMPTS_ALLOWED', 3);
define ('QR_USERS_ALLOWED_PERMISSIONS', '141,143');
define ('QR_ADMINS_ALLOWED_PERMISSIONS', '-1,1200');	//-1 is super admin
define ('QR_ALLOW_GROUP_PERMISSIONS', true);
define ('QR_ADMIN_PERMISSION_REQUIRED_MESSAGE', 'You must be an administrator to acces this pasge.');

//general database constants
define('QR_DB_SCHEMA','quick_reports');	//schema created for tables specific to this reporting tool

//Folders
define('QR_PARENT_FOLDER_NAME', 'Quick Reports');
define('QR_REPORT_FOLDER_NAME', 'reporter.report_folder');
define('QR_OUTPUT_FOLDER_NAME', 'reporter.output_folder');

//Queries
define('QR_ADMIN_SCHEDULED_REPORTS_QUERY_DAYS', 30);

//Version 4 templates
define('QR_SHOW_DOC_URL', true);
define('QR_SHOW_FIELD_DOC', true);

//if OPENSRF.XML is true every page access will parse the xml file to obtain database credentials
define('QR_PGSQL_USE_OPENSRF_XML_CONFIG', true);
define('QR_OPENSRF_XML_PATH', '/openils/conf/opensrf.xml');

//PostgreSQL
if (!QR_PGSQL_USE_OPENSRF_XML_CONFIG) {
define ('QR_PGSQL_HOST', '');
define ('QR_PGSQL_PORT', '');
define ('QR_PGSQL_DBNAME', '');
define ('QR_PGSQL_USER', '');
define ('QR_PGSQL_PASSWORD', '');
}

//memcache
define ('QR_SESSIONS_IN_MEMCACHE', true);
define('QR_MEMCACHE_USE_OPENSRF_XML_CONFIG', true);

if (QR_SESSIONS_IN_MEMCACHE) {
define ('QR_MEMCACHE_PROTOCOL_1', 'tcp');
define ('QR_MEMCACHE_PARAMS_1', '?persistent=1&weight=2&timeout=2&retry_interval=10');
define ('QR_MEMCACHE_PROTOCOL_2', 'tcp');
define ('QR_MEMCACHE_PARAMS_2', '?persistent=1&weight=2&timeout=2&retry_interval=10');

if (!QR_MEMCACHE_USE_OPENSRF_XML_CONFIG) {
define ('QR_MEMCACHE_HOST_1', '10.30.30.151');
define ('QR_MEMCACHE_PORT_1', 11211);
//define ('QR_MEMCACHE_HOST_2', '10.10.10.152');
//define ('QR_MEMCACHE_PORT_2', 11211);
}
}
?>
