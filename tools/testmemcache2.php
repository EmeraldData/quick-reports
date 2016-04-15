<?php

if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']=='localhost' || $_SERVER['SERVER_NAME']=='dev'))
        include '../config/dev.config.php';
else
        include '../config/production.config.php';
include '../config/app.config.php';

if (!QR_SESSIONS_IN_MEMCACHE) die('Sessions are not configured to use memcache.');

if (QR_MEMCACHE_USE_OPENSRF_XML_CONFIG) {
        include '../models/openilsConfig.class.php';
        new openilsConfig();    //parse openils xml config file
}

$memcache = new Memcache;
$memcache->connect(QR_MEMCACHE_HOST_1, QR_MEMCACHE_PORT_1) or die ("Could not connect");

$version = $memcache->getVersion();
echo "Server's version: ".$version."<br/>\n";
?>
