<?php
//This class generates a report output 
//There is no session / login security. Anyone can access a report from a shared URL.

include 'controllers/executiveReportGenerator.controller.php';
new executiveReportGenerator();
?>

