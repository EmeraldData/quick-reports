<?php
//Application settings. DO NOT ALTER THESE!
//database
define('QR_QUERY_RETURN_ONE_ROW', 1);
define('QR_QUERY_RETURN_ALL_ROWS', 2);

//URLs
define ('QR_HOME_PAGE', QR_SITE_ROOT);
define ('QR_ADMIN_MENU_PAGE', '?admin/');
define ('QR_LINK_TEMPLATE_PAGE', '?template/link/');
define ('QR_EDIT_TEMPLATE_PAGE', '?template/edit/');
define ('QR_CREATE_REPORT_PAGE', '?report/create/');
define ('PNIES_SAVE_REPORT_PAGE', '?report/save/');
define ('QR_RUN_REPORT_PAGE', '?report/run/');
define ('QR_EDIT_REPORT_PAGE', '?report/edit/');
define ('QR_CANCEL_REPORT_PAGE', '?report/cancel/');
define ('QR_ADMIN_QR_EDIT_REPORT_PAGE', '?report/admin/');
define ('QR_EDIT_DRAFT_REPORT_PAGE', '?report/draft/');
define ('QR_DELETE_DRAFT_REPORT_PAGE', '?report/draft/delete/');
define ('QR_LIST_REPORTS_PAGE', '?report/');
define ('QR_LIST_TEMPLATES_PAGE', '?template/');
define ('QR_SELECT_TEMPLATE_PAGE', '?template/select/');
define ('QR_LIST_DRAFT_REPORTS_PAGE', '?report/saved/');
define ('QR_LOGIN_PAGE', '?login/');
define ('QR_LOGOUT_PAGE', '?logout/');

//executive reports
define ('QR_SHOW_EXECUTIVE_REPORTS_MENU_PAGE', '?executive/');
define ('QR_LIST_EXECUTIVE_REPORTS_PAGE', '?executive/reports/');
define ('QR_CREATE_EXECUTIVE_REPORT_PAGE', 'executiveReport.php?');
define ('QR_EXECUTIVE_REPORTS_MENU_COLUMNS', 3);

//login
define ('PINS_LOGOUT_EXPLICIT', 1);
define ('QR_LOGOUT_DUE_TO_INACTIVITY', 2);
define ('QR_INVALID_LOGIN_ATTEMPT', 3);
define ('QR_MAX_LOGIN_ATTEMPTS_REACHED', 4);
define ('QR_NOT_LOGGED_IN', 5);
define ('QR_INSUFFICIENT_PERMISSIONS', 6);

//menu
define ('QR_MENU_HOME', '10');
define ('QR_MENU_EXECUTIVE', '20');
define ('QR_MENU_TEMPLATES', '30');
define ('QR_MENU_REPORTS', '40');
define ('QR_MENU_DRAFT_REPORTS', '50');
define ('QR_MENU_ADMIN', '60');

//escape text shown in javascript alerts
define ('QR_DEFAULT_ESCAPE_PATTERNS', serialize(array("/\\\\/", '/&#10;/', '/&#13;/', '/\n/', '/\r/', '/\t/', '/\v/', '/\f/', "/'/", '/"/', '/&#34;/')));
define ('QR_DEFAULT_ESCAPE_REPLACEMENTS', serialize(array('\\\\\\', '\n', '\r', '\n', '\r', '\t', '\v', '\f', '&apos;', '\"', '\"')));
?>
