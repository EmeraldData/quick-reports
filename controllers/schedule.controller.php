<?php

include 'views/schedule.view.php';
include_once 'models/db.class.php';
include 'models/schedule.class.php';

class scheduleController {
	
	protected $security;

	public function __construct($params) {

		$this->security = new security();
		
		switch ($params['action']) {
			case 'run': $this->scheduleReport(); break;
			case 'cancel': $this->deleteScheduledReport($params); break;
			case 'list': $this->displayUserReportList($params['adminView']); break;
			case 'admin':	
			case 'edit':
				$this->editScheduledReport($params);
				break;	
		}
	}
	
	function displayUserReportList($adminView) {
		//Generate the My Reports page
			
		$adminView = ($adminView && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']);
		
		$scheduleObj = new schedule();
		$scheduledReportsList = $scheduleObj->getScheduledReportsList($_SESSION['userID'], $adminView);
		
		$scheduleView = new scheduleView();
		$scheduleView->showScheduledReportsList($scheduledReportsList, $adminView);
		
	}
	
	public function deleteScheduledReport($params) {
	
		$adminView = ($params['adminView'] && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']);
		
		if (!isset($params['confirmed']) || !$params['confirmed'])
			new displayMessageView('Delete action was not confirmed. The report was not deleted.');
		else {
			if (!$this->security->validateInteger($params['id'])) {
				$error = new displayMessageView('Invalid report #'.$params['id']);
				exit;
			}
		
			$scheduleObj = new schedule();		
			$schedule = $scheduleObj->deleteScheduledReport($params['id'], $adminView);
	
			if (NULL == $schedule) {
				new displayMessageView('The report was not deleted.');
				exit;
			}
		}
			
		$this->displayUserReportList($adminView);
	}
	
	function scheduleReport() {
		
		//get the template that the report is built from
		include 'controllers/report.controller.php';
		
		$templateID = $_POST['templateID'];
		$reportObj = new report();
		$reportController = new reportController();

		$report = $reportController->createReportObjectFromTemplate($templateID, $reportObj);
		if (NULL == $report) {
			new displayMessageView('Invalid template #'.$templateID);
			exit;
		}

		$paramsOK = true;
		$paramsErrorMessage = NULL;
		
		$updateIDs = NULL;
		if (isset($_POST['reportAction']) && strtolower(trim($_POST['reportAction']))=='update') {
			if (!$this->security->validateInteger($_POST['rid']) || !$this->security->validateInteger($_POST['sid'])) {
				$paramsOK = false;
				$paramsErrorMessage = 'Primary keys must be integers.';
			}
			else {
				$updateIDs=array('rid'=>$_POST['rid'], 'sid'=>$_POST['sid']);
			}
		}
		
		if (!isset($_POST['name'])) {
			$paramsOK = false;
			$paramsErrorMessage = 'Report name is missing.';
		}
		elseif (!isset($_POST['description'])) {
			$paramsOK = false;
			$paramsErrorMessage = 'Report description is missing';
		}
		elseif (!isset($_POST['templateID'])) {
			$paramsOK = false;
			$paramsErrorMessage = 'Template ID is missing.';
		}
		elseif (!isset($_POST['notifyEmail'])) {
			$paramsOK = false;
			$paramsErrorMessage = 'Notification email is missing.';
		}
		elseif (!isset($_POST['excelOutput']) && !isset($_POST['csvOutput']) && !isset($_POST['htmlOutput'])) {
			$paramsOK = false;
			$paramsErrorMessage = 'No output format is selected.';
		}
		elseif (!isset($_POST['intervalRadio']) ||($_POST['intervalRadio']!='runOnce' && $_POST['intervalRadio']!='recur')) {
			$paramsOK = false;
			$paramsErrorMessage = 'Recurrence interval is not selected.';
		}
		elseif (!isset($_POST['runTimeRadio']) ||($_POST['runTimeRadio']!='asap' && $_POST['runTimeRadio']!='scheduledTime')) {
			$paramsOK = false;
			$paramsErrorMessage = 'Run time is not selected.';
		}
		elseif ($_POST['runTimeRadio']=='scheduledTime') {
			if (!isset($_POST['runDate'])  || !isset($_POST['runDate']) || !isset($_POST['runTimeHour']) || !isset($_POST['runTimeAMPM'])) {
				$paramsOK = false;
				$paramsErrorMessage = 'Scheduled run time is not specified.';
			}
			if (($_POST['runTimeHour']!='noon' && $_POST['runTimeHour']!='midnight' && ((int)$_POST['runTimeHour']<0 || (int)$_POST['runTimeHour']>12)) || ($_POST['runTimeAMPM']!='AM' && $_POST['runTimeAMPM']!='PM'))  {
				$paramsOK = false;
				$paramsErrorMessage = 'Scheduled run time is not valid.';
			}
			$convertedRunDate = $this->convertDateFormat($_POST['runDate']); 
			if (NULL == $convertedRunDate) {
				$paramsOK = false;
				$paramsErrorMessage = 'Scheduled run date is not valid.';
			}
		}
		
		if (! $paramsOK) {
			new displayMessageView('Invalid parameters. '.$paramsErrorMessage);
			exit;
		}
		
		$reportFolderID = $reportObj->checkFolders(QR_REPORT_FOLDER_NAME, $report->group_name);
		$outputFolderID = $reportObj->checkFolders(QR_OUTPUT_FOLDER_NAME, $report->group_name);
		if (NULL ==$reportFolderID || NULL == $outputFolderID) {
			new displayMessageView('Error validating user\'s folder structure. Aborting.');
			exit;
		}

		//If not updating a future report, ensure report name is unique in its folder
		if ((NULL == $updateIDs) & $reportObj->checkUniqueReportName($_POST['name'], $reportFolderID)->cnt > 0) {
			new displayMessageView('Error: Report name must be unique.');
			exit;
		}
		
		$queryParams = new stdClass();
		$queryParams->name = $_POST['name'];
		$queryParams->description = $_POST['description'];
		$queryParams->templateID = $report->reporter_template_id;
		$queryParams->recur = $_POST['intervalRadio'];
		$queryParams->interval = $_POST['interval'];
		$queryParams->intervalPeriod = $_POST['intervalPeriod'];
		$queryParams->notifyEmail = $_POST['notifyEmail'];
		
		if ($_POST['runTimeRadio'] != 'asap') {
			$queryParams->runTime = $convertedRunDate;
			if ($_POST['runTimeHour'] == 'noon')
				$queryParams->runTime .= ' 12:';
			elseif ($_POST['runTimeHour'] == 'midnight')
				$queryParams->runTime .= ' 00:';
			else
				$queryParams->runTime .= ' '.$_POST['runTimeHour'].':';
			$queryParams->runTime .= '00:00';	//always schedue for top of the hour
		}
		else {
			$queryParams->runTime = 'now()';
		}
		
		$queryParams->csvOutput = (isset($_POST['csvOutput']) && $_POST['csvOutput']=='on') ? 't' : 'f';
		$queryParams->excelOutput = (isset($_POST['excelOutput']) && $_POST['excelOutput']=='on') ? 't' : 'f';
		$queryParams->htmlOutput = (isset($_POST['htmlOutput']) && $_POST['htmlOutput']=='on') ? 't' : 'f';
		$queryParams->barChartOutput = (isset($_POST['barChartOutput']) && $_POST['barChartOutput']=='on') ? 't' : 'f';
		$queryParams->lineChartOutput = (isset($_POST['lineChartOutput']) && $_POST['lineChartOutput']=='on') ? 't' : 'f';
				
		//loop through all of the user filter params (P0, P1, ...)
		$userParams = array();
		$dateParam = array();
		$dateEndParam = array();

		$report->dataDecoded=json_decode($report->data);
		if (NULL == $report->dataDecoded) {
			new displayMessageView('JSON format error decoding report data.');
			exit;
		}
						
		if (count(get_object_vars($report->dataDecoded->userParams)) > 0) {
			foreach ($report->dataDecoded->userParams as $userParam) {

				$paramName = substr($userParam->param,2);
				$lowerOp = strtolower($userParam->op);
				$lowerDataType = strtolower($userParam->dataType);
				$lowerTransform = strtolower($userParam->transform);
					
				if ($lowerTransform == 'date') {
					//process date field
	
					$range=false;
					$paramsOK = true;
					$paramsErrorMessage = NULL;
					$dateParamString=NULL;
					$dateEndParamString=NULL;
					
					if (!isset($_POST[$paramName.'_type'])) {
						$paramsOK = false;
						$paramsErrorMessage = "Missing date type parameter $paramName";
						break;
					}					
					if ($_POST[$paramName.'_type'] == 'relative') {
						if (!isset($_POST[$paramName.'_relative_value'])) {
							$paramsOK = false;
							$paramsErrorMessage = "Missing date parameter $paramName";
							break;
						}
						$dateParam['transform'] = 'relative_'.$lowerTransform;
						$dateParam['params'] =  (array)$_POST[$paramName.'_relative_value'];
					}
					else {
						if (!isset($_POST[$paramName.'_date'])) {
							$paramsOK = false;
							$paramsErrorMessage = "Missing date parameter $paramName";
							break;
						}
						$dateParamString = $this->convertDateFormat($_POST[$paramName.'_date']);
						if (NULL == $dateParamString) {
							$paramsOK = false;
							$paramsErrorMessage = 'Date is not valid.';
						}
					}
				
					if ($lowerOp == 'between') {
					 	//process between range		
	
						$range = true;	
						if (!isset($_POST[$paramName.'_end_type'])) {
							$paramsOK = false;
							$paramsErrorMessage = "Missing date range type parameter $paramName";
							break;
						}
						if ($_POST[$paramName.'_end_type'] == 'relative') {
							if (!isset($_POST[$paramName.'_end_relative_value'])) {
								$paramsOK = false;
								$paramsErrorMessage = "Missing date range parameter $paramName";
								break;
							}
							$dateEndParam['transform'] = 'relative_'.$userParam->transform;
							$dateEndParam['params'] =  (array)$_POST[$paramName.'_end_relative_value'];
						}
						else {
							if (!isset($_POST[$paramName.'_end_date'])) {
								$paramsOK = false;
								$paramsErrorMessage = "Missing date parameter $paramName";
								break;
							}
							$dateEndParamString = $this->convertDateFormat($_POST[$paramName.'_end_date']);
							if (NULL == $dateEndParamString) {
								$paramsOK = false;
								$paramsErrorMessage = 'End date is not valid.';
							}
						}		
					}
					
					if (NULL != $dateParamString)
						$startDate = $dateParamString;		//real date string
					else
						$startDate = $dateParam;			//relative date array
	
					if ($range) {							//date range
						if (NULL != $dateEndParamString)
							$endDate = $dateEndParamString;	//real date string
						else
							$endDate = $dateEndParam;		//relative date array
						$userParams[$paramName] = array($startDate, $endDate);
					}
					else {	//single date (not a range)
						$userParams[$paramName] = array($startDate);
					}
				}
				else {	//not a date field	
					if (!isset($_POST[$paramName])) {
						$paramsOK = false;
						$paramsErrorMessage = "Missing parameter $paramName";
						break;
					}					
					if ($lowerOp == 'between') {
						if (!isset($_POST[$paramName.'_end'])) {
							$paramsOK = false;
							$paramsErrorMessage = "Missing range parameter $paramName";
							break;
						}
						$userParams[$paramName] = array($_POST[$paramName], $_POST[$paramName.'_end']);
					}
					else {
						if ($lowerOp == 'in' && $lowerDataType == 'text') {
							$userParams[$paramName] = explode(',', $_POST[$paramName][0]);
						}
						else {
							$userParams[$paramName] = $_POST[$paramName];
						}
					}	
				}
							
			}
		}
		
		if (isset($_POST['pivotLabelColumn']) && isset($_POST['pivotDataColumn'])) {
			$userParams['__pivot_label'] = $_POST['pivotLabelColumn'];
			$userParams['__pivot_data'] = $_POST['pivotDataColumn'];
		}

		if ($paramsOK) {
			$reportParams = new stdClass();
			$reportParams->data = json_encode($userParams);
			if (NULL == $reportParams->data) {
				new displayMessageView('JSON format error encoding report data.');
				exit;
			}
				
			$reportParams->owner = $_SESSION['userID'];
			$reportParams->name = $queryParams->name;
			$reportParams->description = $queryParams->description;
			$reportParams->template = $queryParams->templateID;
			$reportParams->folder = $reportFolderID;
			$reportParams->recur = ($queryParams->recur == 'runOnce') ? 'f' : 't';
			$reportParams->recurrence = ($queryParams->recur == 'runOnce') ? NULL : $queryParams->interval.' '.$queryParams->intervalPeriod;
			
			$scheduleParams = new stdClass();
			$scheduleParams->folder = $outputFolderID;
			$scheduleParams->runner = $_SESSION['userID'];
			$scheduleParams->email = $queryParams->notifyEmail;
			$scheduleParams->runTime = $queryParams->runTime;
			$scheduleParams->csvFormat = $queryParams->csvOutput;
			$scheduleParams->excelFormat = $queryParams->excelOutput;
			$scheduleParams->htmlFormat = $queryParams->htmlOutput;
			$scheduleParams->barChart = $queryParams->barChartOutput;
			$scheduleParams->lineChart = $queryParams->lineChartOutput;
									
			$scheduleObj = new schedule();
			$result = $scheduleObj->submitReport($reportParams, $scheduleParams, $updateIDs);
			
			if ($result->success) {
				if (NULL == $updateIDs)
					new displayMessageView('The report was sent to the scheduler.', true);
				else
					new displayMessageView('The scheduled report has been updated.', true);
			
				//delete the draft copy
				$draftID = (isset($_POST['draftID']) && $this->security->validateInteger($_POST['draftID'])) ? $_POST['draftID'] : NULL;
				if (NULL != $draftID) {			
					$result = $reportObj->deleteDraftReport($draftID);
					if (NULL == $result) {
						new displayMessageView('The draft report could not be deleted.', true);
					}
				}
				exit;
			}
			else {
				new displayMessageView('There was an error scheduling the report. ' . $result->errorMessage);
				exit;
			}	
		}
		else {
			new displayMessageView('Invalid parameters. '.$paramsErrorMessage);
			exit;
		}
	}

	public function editScheduledReport($params) {

		$adminView = (isset($params['action']) && $params['action']=='admin' && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']);
		
		if (!$this->security->validateInteger($params['id'])) {
			new displayMessageView('Invalid report id specified.');
			exit;
		}
	
		$scheduleObj = new schedule();
		$scheduledReport = $scheduleObj->getScheduledReportByID($params['id'], $adminView);
		
		if (NULL == $scheduledReport) {
			new displayMessageView('Report not found in schedule table.');
			exit;
		}
	
		include 'controllers/report.controller.php';
		$reportObj = new report();
		$reportController = new reportController();
		$report = $reportController->editQueuedReport($scheduledReport);
	}
	
	private function convertDateFormat($theDate) {
		//convert mm-dd-yyyy to yyyy-mm-dd
		
		if (strlen($theDate)!=10 || (substr($theDate,2,1)!='/' && substr($theDate,2,1)!='-' ) || (substr($theDate,5,1)!='/' && substr($theDate,5,1)!='-' )) return NULL;

		$yyyy = substr($theDate,6,4);
		$mm = substr($theDate,0,2);
		$dd = substr($theDate,3,2);
		
		//validateInteger method returns the parameter and drops the leading 0 so use a temp var to pass by reference
		$y=$yyyy;
		$m=$mm;
		$d=$dd;
		
		if (!$this->security->validateInteger($y) || !$this->security->validateInteger($m) || !$this->security->validateInteger($d)) return NULL;
		if ($m < 1 || $m > 12) return NULL;
		if ($d < 1 || $d > 31) return NULL;
		if (($m == 4 || $m == 6 || $m == 9 || $m == 11) && ($d > 30)) return NULL;
		if ($m ==2) {
			if (($y % 400) == 0 || (($y % 4) == 0 && ($y % 100) != 0)) { 	//leap year
				if ($d > 29) return NULL;
			}	
			else {	//not leap year
				if ($d > 28) return NULL;
			}
		}
		return $yyyy.'-'.$mm.'-'.$dd;
	}

}
?>
