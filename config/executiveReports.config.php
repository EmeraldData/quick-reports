<?php
$report[] = (object) array (
		'id' => 'P1',
		'category' => 'Patrons',
		'name' => 'Active Users',
		'description' => 'Count of active users');

$report[] = (object) array (
		'id' => 'P2',
		'category' => 'Patrons',
		'name' => 'New Users',
		'description' => 'Count of new users');
	
$report[] = (object) array (
		'id' => 'P3',
		'category' => 'Patrons',
		'name' => 'Users Who Circulated Items',
		'description' => 'Count of users who circulated items');

$report[] = (object) array (
		'id' => 'P4',
		'category' => 'Patrons',
		'name' => 'Users Who Placed Holds',
		'description' => 'Count of users who placed holds');

$report[] = (object) array (
		'id' => 'B1',
		'category' => 'Bills',
		'format' => 'currency',
		'name' => 'Amount Owed By My Patrons',
		'description' => 'Total amount owed by my patrons');

$report[] = (object) array (
		'id' => 'B2',
		'category' => 'Bills',
		'format' => 'currency',
		'name' => 'Amount Billed To My Patrons',
		'description' => 'Amount billed to my patrons this month');

$report[] = (object) array (
		'id' => 'B3',
		'category' => 'Bills',
		'format' => 'currency',
		'name' => 'Amount Collected From My Patrons',
		'description' => 'Amount collected this month');

$report[] = (object) array (
		'id' => 'C1',
		'category' => 'Circulation',
		'name' => 'Circulations',
		'description' => 'Count of circulation');

$report[] = (object) array (
		'id' => 'C2',
		'category' => 'Circulation',
		'name' => 'Circulations by Circulation Modifier',
		'description' => 'Count of circulation by circulation modifier');

$report[] = (object) array (
		'id' => 'C3',
		'category' => 'Circulation',
		'name' => 'Circulations by MARC Type',
		'description' => 'Count of circulation by MARC type');

$report[] = (object) array (
		'id' => 'C4',
		'category' => 'Circulation',
		'name' => 'Circulations by Non-Cataloged',
		'description' => 'Count of circulation by non-cataloged types');

$report[] = (object) array (
		'id' => 'C5',
		'category' => 'Circulation',
		'name' => 'In-House Use',
		'description' => 'Count of in-house use');

$report[] = (object) array (
		'id' => 'H1',
		'category' => 'Holds/Transits',
		'name' => 'Holds Sent From My Library',
		'description' => 'Holds sent from  my library');

$report[] = (object) array (
		'id' => 'H2',
		'category' => 'Holds/Transits',
		'name' => 'Holds Received At My Library',
		'description' => 'Holds received at my library from another library');

$report[] = (object) array (
		'id' => 'H3',
		'category' => 'Holds/Transits',
		'name' => 'Internal Holds',
		'description' => 'Internal holds');

$report[] = (object) array (
		'id' => 'H4',
		'category' => 'Holds/Transits',
		'name' => 'Total Incoming Transits',
		'description' => 'Total incoming transits');

$report[] = (object) array (
		'id' => 'H5',
		'category' => 'Holds/Transits',
		'name' => 'Total Outgoing Transits',
		'description' => 'Total outgoing transits');

$report[] = (object) array (
		'id' => 'H6',
		'category' => 'Holds/Transits',
		'name' => 'Total IntraPINES Sent',
		'description' => 'Total IntraPINES sent (HQ-HQ)');

$report[] = (object) array (
		'id' => 'H7',
		'category' => 'Holds/Transits',
		'name' => 'Total IntraPINES Received',
		'description' => 'Total IntraPINES received (HQ-HQ)');

$report[] = (object) array (
		'id' => 'I1',
		'category' => 'Collections/Items',
		'name' => 'Total Items',
		'description' => 'Count of items');

$report[] = (object) array (
		'id' => 'I2',
		'category' => 'Collections/Items',
		'format' => 'currency',
		'name' => 'Value of Items',
		'description' => 'Value of items');

$report[] = (object) array (
		'id' => 'I3',
		'category' => 'Collections/Items',
		'name' => 'Added Items',
		'description' => 'Count of added items');

$report[] = (object) array (
		'id' => 'I4',
		'category' => 'Collections/Items',
		'name' => 'Deleted Items',
		'description' => 'Count of deleted items');

?>
