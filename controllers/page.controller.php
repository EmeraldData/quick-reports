<?php
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']=='localhost' || $_SERVER['SERVER_NAME']=='dev'))
	include 'config/dev.config.php';
else	
	include 'config/production.config.php';
include 'config/app.config.php';
include 'models/session.class.php';
include 'models/security.class.php';
include 'views/page.view.php';
include 'views/displayMessage.view.php';
		
class page {

	public function __construct() {

		$page = new pageView();	//generate page header

		error_reporting(QR_ERROR_REPORTING_LEVEL);		//set the php reporting level
		ini_set( 'display_errors', true );

		date_default_timezone_set(QR_DEFAULT_TIME_ZONE);	//set the time zone

		$params = NULL;
		$className = NULL;
		$errorMessge = NULL;
		$controllerName = NULL;
		$menuItemSelected = NULL;
		$qsParamsArray = array();
		$allowExecutiveReportsOnlyUsers = false;
		
		if (QR_PGSQL_USE_OPENSRF_XML_CONFIG || (QR_SESSIONS_IN_MEMCACHE && QR_MEMCACHE_USE_OPENSRF_XML_CONFIG)) {
			include 'models/openilsConfig.class.php';	
			new openilsConfig();	//parse openils xml config file
		}

		//sanitize inputs
		$security = new security();
		$security->sanitizeServerQueryString();
		if (count($_POST)) $security->sanitizePostVars();
		
		//url format will be ?/param1/param2/... or ?param1/param2/...
		//strip leading / if present, add trailing / if missing,  and use explode to generate an array of strings
		if (substr($_SERVER['QUERY_STRING'], 0, 1) == '/') $_SERVER['QUERY_STRING']=substr($_SERVER['QUERY_STRING'], 1);
		if (substr($_SERVER['QUERY_STRING'],strlen($_SERVER['QUERY_STRING'])-1,1) != '/') $_SERVER['QUERY_STRING'] .= '/';
		$qsParamsArray = explode('/', $_SERVER['QUERY_STRING']);

		//set flag if destination page requires user to be logged in. User gets rdirected if they cannot be there.
		$allowNotLoggedIn=(isset($qsParamsArray[0]) && $qsParamsArray[0]=='login') ? true:false;
		$session = new session($allowNotLoggedIn, $qsParamsArray[0]);
		
		//redirect if user session is expired. If user was logging out after session expired allow logout to continue
		if (!(isset($qsParamsArray[0]) && $qsParamsArray[0]=='logout')) {
			if ((isset($session->isNotLoggedIn) && $session->isNotLoggedIn) || isset($session->isSessionExpired) && $session->isSessionExpired) {
				if (isset($session->redirectPage))
					header('Location: '.$session->redirectPage);
				else
					header('Location: '.QR_HOME_PAGE);
				exit;
			}
		}
		
		//clear session variable so we don't display welcome message after user has logged out (logout is handled below)
		if (isset($qsParamsArray[0]) && $qsParamsArray[0]=='logout') $_SESSION['isLoggedIn']=false;
		$page->displayWelcomeMessage();

		if (count($qsParamsArray) > 0 ) {		
			switch ($qsParamsArray[0]) {
				case 'report':
					$params = array('action' => $qsParamsArray[1]);
					switch ($qsParamsArray[1]) {												
						case 'create':	//create from template
							$controllerName = 'report.controller.php';
							$className = 'reportController';
							if (isset($qsParamsArray[2])) $params['templateID']=$qsParamsArray[2];	//template
							break;
						case 'draft':	//edit or delete a saved draft report
							$controllerName = 'report.controller.php';
							$className = 'reportController';
							if (isset($qsParamsArray[2])) {
								if ($qsParamsArray[2] == 'delete') {	// format for delete is /report/draft/delete/c/ID - c is for confirmed
									$params['action'] = 'deleteDraft';
									$params['confirmed'] = (isset($qsParamsArray[3]) && $qsParamsArray[3] == 'c');
									if (isset($qsParamsArray[4])) $params['draftID']=$qsParamsArray[4];	//draft ID to delete	
								}
								else {
									$params['draftID']=$qsParamsArray[2];	//draft ID to edit
								}
							}
							break;
						case 'saved':	//list user draft reports
							$controllerName = 'report.controller.php';
							$className = 'reportController';
							$menuItemSelected = QR_MENU_DRAFT_REPORTS;
							break;
						case 'save':	//save as a draft report
							$controllerName = 'report.controller.php';
							$className = 'reportController';
							break;																	
						//these actions use the reporter.schedule table	
						case '':	//list user reports
							$controllerName = 'schedule.controller.php';
							$className = 'scheduleController';
							$params = array('action'=>'list', 'adminView'=>false);
							$menuItemSelected = QR_MENU_REPORTS;
							break;
						case 'queue':	//admin list of queued reports
							$controllerName = 'schedule.controller.php';
							$className = 'scheduleController';
							$params = array('action'=>'list', 'adminView'=>true);
							break;
						case 'run':	//run a report / submit to scheduler
							$controllerName = 'schedule.controller.php';
							$className = 'scheduleController';
							break;
						case 'cancel':	
							$controllerName = 'schedule.controller.php';
							$className = 'scheduleController';
							if (isset($qsParamsArray[2])) {	// format is /report/cancel/c/id/a/ where /c/ is for confirmed; /a/ is for admin view
								$params['confirmed'] = (isset($qsParamsArray[2]) && $qsParamsArray[2] == 'c');
								if (isset($qsParamsArray[3])) $params['id']=$qsParamsArray[3];	//schedule.id to delete
							}
							$params['adminView'] = (isset($qsParamsArray[4]) && $qsParamsArray[4] == 'a'); 
							break;							
						case 'admin':
						case 'edit':
							$controllerName = 'schedule.controller.php';
							$className = 'scheduleController';
							if (isset($qsParamsArray[2])) $params['id']=$qsParamsArray[2];	//schedule ID									
					}
					break;
											
				case 'login':
					$allowExecutiveReportsOnlyUsers = true;
					$controllerName = 'login.controller.php';
					$className = 'login';
					$params = array();
					if (isset($qsParamsArray[1])) $params['messageNumber'] = $qsParamsArray[1];
					if (isset($session)) $params['session'] = $session;
					break;
		
				case 'logout':
					$allowExecutiveReportsOnlyUsers = true;
					$controllerName = 'logout.controller.php';
					$className = 'logout';
					if (isset($session)) $params = $session;
					break;
		
				case 'template':
					switch ($qsParamsArray[1]) {
						case '':		//list user templates
						case 'select':	//list and ask user to select a template							
							$params = array('action'=>'list', 'adminView'=>false, 'showSelectMessage'=>false);
							if (isset($qsParamsArray[1]) && $qsParamsArray[1]=='select') {
								$params['showSelectMessage']=true;
							}
							$controllerName = 'template.controller.php';
							$className = 'templateController';
							if ($qsParamsArray[1] == 'select')
								$menuItemSelected = QR_MENU_TEMPLATES;
							else	
								$menuItemSelected = QR_MENU_TEMPLATES;
							break;				
							
						case 'admin':	//list templates for admin
							$controllerName = 'template.controller.php';
							$className = 'templateController';
							$params = array('action'=>'list', 'adminView'=>true);;
							break;
																
						case 'link':
							$controllerName = 'template.controller.php';
							$className='templateController';
							$params = array('action'=>'link');
							break;
							
						case 'edit':
							$controllerName = 'template.controller.php';
							$className='templateController';
							$params = array('action'=>'edit');
							if (isset($qsParamsArray[2])) $params['id'] = $qsParamsArray[2];	//template ID
							break;		
					}
					break;
						
				case '':	//home page
					$allowExecutiveReportsOnlyUsers = true;
					$controllerName = 'homePage.controller.php';
					$className = 'homePage';
					$menuItemSelected = QR_MENU_HOME;
					break;
						
				case 'admin':
					$controllerName = 'admin.controller.php';
					$className = 'adminMenu';
					$menuItemSelected = QR_MENU_ADMIN;
					break;

				case 'executive': 
					if (defined('QR_EXECUTIVE_REPORTS_ENABLED') && QR_EXECUTIVE_REPORTS_ENABLED) {
						$allowExecutiveReportsOnlyUsers = true;
						switch ($qsParamsArray[1]) {
							case '': break;		//show executive reports menu
							case 'reports':
								$params = array('action'=>'reports');
								break;
						}
						
						$controllerName = 'executiveReportsMenu.controller.php';
						$className = 'executiveReportsMenuController';
						$menuItemSelected = QR_MENU_EXECUTIVE; break;
					}
									
				default:
					//invalid page - handled below
					break;
			}
			
			$page->displayMenu($menuItemSelected);

			//check for valid page and user has permissions 
			if (NULL == $controllerName || (isset($_SESSION['executiveReportsOnly']) && $_SESSION['executiveReportsOnly'] && !$allowExecutiveReportsOnlyUsers)) {
				new displayMessageView('Invalid parameter.');
				exit;
			}
			else {
				include "controllers/$controllerName";
				if (NULL != $className) $controller = new $className($params);
			}
			
		}
		else { //no querystring parameters - no page to load
			$page->displayMenu($menuItemSelected);
		}
		
		$page->displayFooter();
		
	}
	
}
?>
