<?php
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']=='localhost' || $_SERVER['SERVER_NAME']=='dev'))
	include '../config/dev.config.php';
else	
	include '../config/production.config.php';
include '../config/app.config.php';
include '../models/db.class.php';

error_reporting(E_ALL);		
ini_set( 'display_errors', true );

if (QR_PGSQL_USE_OPENSRF_XML_CONFIG) {
	include '../models/openilsConfig.class.php';
	new openilsConfig();	//parse openils xml config file
}

if (php_sapi_name()!='cli') echo '<pre>';
$dbObj = new db();
if (NULL == $dbObj) die('There was an error connecting to the database.');

print_r($dbObj);

try {
	$query =
	'select count(*) as num_templates from reporter.template';
	$result = $dbObj->executeQuery($query, NULL, QR_QUERY_RETURN_ONE_ROW);
		
	echo $result->num_templates," templates were found in reporter.template \nDone.";
}
catch (PDOException $e) {
	$this->handleDatabaseErrors($e);
}

?>
