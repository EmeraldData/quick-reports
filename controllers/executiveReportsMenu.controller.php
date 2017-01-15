<?php

//This class allows the user to select reports to view
class executiveReportsMenuController {
	
	protected $security;
	
	public function __construct($params=NULL) {
		
		$this->security = new security();
		
		switch ($params['action']) {
			case NULL: $this->executiveReportsMenu(); break;
			case 'reports': $this->createReportListLinks(); break;
			case 'report': $this->createReport(); break;
		}
	}


	protected function executiveReportsMenu() {	
		include_once 'models/db.class.php';
		include 'models/report.class.php';
		include 'models/executiveReport.class.php';
		include 'views/baseReport.view.php';
		include 'views/executiveReportsMenu.view.php';
		
		$reportObj = new report();
		$orgList = $reportObj->getListDataFromTable((object) array('dataType' => 'org_unit'));
				
		$executiveReportObj = new executiveReport();
		$executiveReportsList = $executiveReportObj->getExecutiveReportsList();
		
		$executiveReports = new executiveReportsView();
		$executiveReports->showExecutiveReportsMenu($orgList, $executiveReportsList);	
	}
	
	
	protected function createReportListLinks() {
		
		$params = new stdClass();
		$invalidDate = false;
		
		if (!isset($_POST['OUList']) || count($_POST['OUList']) == 0) {
			$error = new displayMessageView('No locations were specified');
			exit;
		}
		$orgList = $_POST['OUList'];
		
		$params->reportYear = $_POST['reportYear'];
		$params->reportMonth = $_POST['reportMonth'];
		if (!$this->security->validateInteger($params->reportYear) ||
			!$this->security->validateInteger($params->reportMonth) ||
			$params->reportMonth<1 || $params->reportMonth>12 || $params->reportYear>date('Y') 
			) $invalidDate = true;
		
		if ($invalidDate) {
			new displayMessageView('Invalid Report Date.');
			exit;
		}
		
		unset($_POST['OUList']);
		unset($_POST['reportYear']);
		unset($_POST['reportMonth']);
		//unset($_POST['currentMonthRadio']);
		//unset($_POST['executiveReportDate']); 
		
		if (count($_POST) == 0) {
			$error = new displayMessageView('No reports were specified');
			exit;
		}
		$params->reportList = $_POST;	//check boxes - save all remaining POST variables
	
		include_once 'models/db.class.php';
		include 'models/executiveReport.class.php';
		include 'views/baseReport.view.php';
		include 'views/executiveReportsMenu.view.php';
		
		$reportObj = new executiveReport();
		$params->orgList = $reportObj->getOUInfo(implode(',', $orgList), QR_QUERY_RETURN_ALL_ROWS); 
		$executiveReportsList = $reportObj->getExecutiveReportsList();
		
		$executiveReports = new executiveReportsView();
		$executiveReports->showReportListLinks($params, $executiveReportsList);
	}
	
}
?>

