<?php
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']=='localhost' || $_SERVER['SERVER_NAME']=='dev'))
	include '../config/dev.config.php';
else	
	include '../config/production.config.php';
include '../config/app.config.php';
include '../models/session.class.php';

if (!QR_SESSIONS_IN_MEMCACHE) die('Sessions are not configured to use memcache.');

if (QR_MEMCACHE_USE_OPENSRF_XML_CONFIG) {
	include '../models/openilsConfig.class.php';
	new openilsConfig();	//parse openils xml config file
}

//set_error_handler("noticeHandler", E_NOTICE);
$testSession = new session(true);
echo 'Session=',session_id(),'<br>';

//$testSession = new session(true);

if (!isset($_SESSION['testMemcache'])) {
	$_SESSION['testMemcache']='ok';
	echo 'A session variable was set. Please refresh the page.';
}
else {
	if (isset($_SESSION['testMemcache']) && $_SESSION['testMemcache'] =='ok') {
		echo 'Session variables appear to be operating correctly. <br>';
		session_regenerate_id(true);
	}
	else
		echo 'Error: Unable to store session variables. <br>';
	print_r($_SESSION);
	
}

?>