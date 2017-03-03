<?php
include 'views/baseReport.view.php';
include 'views/report.view.php';
include_once 'models/db.class.php';
include 'models/report.class.php';

class reportController {
	
	protected $security;

	public function __construct($params=NULL) {

		$this->security = new security();
		
		switch ($params['action']) {
			case 'create': $this->createReport($params['templateID'], NULL); break;
			case 'save': $this->saveReport(); break;
			case 'saved': $this->displaySavedReportsList(); break;
			case 'draft': $this->editSavedReport($params['draftID']); break;
			case 'deleteDraft': $this->deleteSavedReport($params); break;
		}	
	}
	
	function displaySavedReportsList() {
		//Generate the My Drat Reports page
		
		$reportObj = new report();
		$queryParams = array('owner'=>$_SESSION['userID']);
		$reportList = $reportObj->getDraftReportsList($queryParams);
		
		$reportView = new reportView();
		$reportView->displaySavedReportsList($reportList);
		
	}
	
	function saveReport() {
		//save as a draft
		
		$reportObj = new report();	
		$queryParams = new stdClass();
		$queryParams->name=$_POST['name'];
		$queryParams->draftID=$_POST['draftID'];
		$queryParams->templateID=$_POST['templateID'];
		$queryParams->description=$_POST['description'];

		unset($_POST['draftID']);
		unset($_POST['templateID']);
		unset($_POST['name']);
		unset($_POST['description']);
		
		$queryParams->params=json_encode($_POST);
		if (NULL == $queryParams->params) {
			new displayMessageView('JSON format error encoding report data.');
			exit;
		}
		
		if ($this->security->validateInteger($queryParams->draftID)) {
			unset($queryParams->templateID);
			$result = $reportObj->updateDraftReport($queryParams);
		}
		else {
			unset($queryParams->draftID);
			$queryParams->owner=$_SESSION['userID'];
			$queryParams->create_time='NOW()';
			$result = $reportObj->createDraftReport($queryParams);
		}
		
		if (isset($result->id)) {
			new displayMessageView('&nbsp;The report was saved.', true);
			$this->editSavedReport($result->id);
			exit;
		}
		else {
			new displayMessageView('There was an error saving the report.');
			exit;
		}	
	}

	public function deleteSavedReport($params) {
	
		if (!isset($params['confirmed']) || !$params['confirmed'])
			new displayMessageView('Delete draft action was not confirmed. The report was not deleted.');
		else {
			
			if (!$this->security->validateInteger($params['draftID'])) {
				$error = new displayMessageView('Invalid report #');
				exit;
			}
	
			$reportObj = new report();
			$report = $reportObj->deleteDraftReport($params['draftID']);
		
			if (NULL == $report) {
				new displayMessageView('The report was not deleted.');
				//exit;
			}	
		}
				
		$this->displaySavedReportsList();
	}
	
	public function editSavedReport($draftID) {
		//edit a saved draft report

		if (!$this->security->validateInteger($draftID)) {
			$error = new displayMessageView('Invalid report #');
			exit;
		}
			
		$queryParams = new stdClass();
		$queryParams->id=$draftID;
		$queryParams->owner=$_SESSION['userID'];
		
		//get the saved parameters already entered by the user
		$reportObj = new report();
		$report = $reportObj->getDraftReport($queryParams);
		
		if (NULL == $report) {
			new displayMessageView('Report could not be found.');
			exit;
		}

		$report->paramsDecoded = json_decode($report->params);
		if (NULL == $report->paramsDecoded) {
			new displayMessageView('JSON format error decoding report data.');
			exit;
		}
		
		$this->createReport($report->template, $report);	//pass in saved values
	}
	
	public function editQueuedReport($params) {
		//edit a queued report using data from reporter.schedule and reporter.report
	
		$defaultValues = new stdClass();
			
		if (NULL == $params) {
			new displayMessageView('Missing report parameters.');
			exit;
		}

		//set submit button value
		if (NULL == $params->complete_time) {
			$defaultValues->submitButtonText = 'Update ';
			$updateReport = true;	
		
		}
		else {
			$defaultValues->submitButtonText = 'Run';
			$updateReport = false;
		}
		
		if (!isset($params->srtid)) {
			new displayMessageView('Missing template ID.');
			exit;
		}
		$templateID = (int)$params->srtid;
		$defaultValues->template = $templateID;
				
		if (!isset($params->name)) {
			new displayMessageView('Missing report name.');
			exit;
		}
		$defaultValues->name = $params->name;
		if (!$updateReport) $defaultValues->name .= ' (copy)';

		if (!isset($params->description)) {
			new displayMessageView('Missing report name.');
			exit;
		}
		$defaultValues->description = $params->description;
		
		if (!isset($params->data)) {
			new displayMessageView('Missing report data.');
			exit;
		}
		
		$defaultValues->paramsDecoded = json_decode($params->data);
		if (NULL == $defaultValues->paramsDecoded) {
			new displayMessageView('JSON format error decoding report data.');
			exit;
		}
		
		//set run date - always set as scheduled, not asap
		if (!isset($params->run_time)) {
			new displayMessageView('Missing run time.');
			exit;
		}
		$defaultValues->paramsDecoded->runTimeRadio = 'scheduledTime';
		$defaultValues->paramsDecoded->runDate = substr($params->run_time,5,2).'/'.substr($params->run_time,8,2).'/'.substr($params->run_time,0,4);
		
		//set the run time - hour only
		$defaultValues->paramsDecoded->runTimeHour = substr($params->run_time,11,2);
		if (substr($defaultValues->paramsDecoded->runTimeHour,0,1)=='0' || $defaultValues->paramsDecoded->runTimeHour=='10' || $defaultValues->paramsDecoded->runTimeHour=='11') {
			$defaultValues->paramsDecoded->runTimeAMPM = 'AM';
			if  ($defaultValues->paramsDecoded->runTimeHour=='00') $defaultValues->paramsDecoded->runTimeHour=='midnight';
		}
		else {
			$defaultValues->paramsDecoded->runTimeAMPM = 'PM';
			switch ($defaultValues->paramsDecoded->runTimeHour) {	
				case '12': $defaultValues->paramsDecoded->runTimeHour=='noon'; break; 
				case '13': $defaultValues->paramsDecoded->runTimeHour = '01'; break;
				case '14': $defaultValues->paramsDecoded->runTimeHour = '02'; break;
				case '15': $defaultValues->paramsDecoded->runTimeHour = '03'; break;
				case '16': $defaultValues->paramsDecoded->runTimeHour = '04'; break;
				case '17': $defaultValues->paramsDecoded->runTimeHour = '05'; break;
				case '18': $defaultValues->paramsDecoded->runTimeHour = '06'; break;
				case '19': $defaultValues->paramsDecoded->runTimeHour = '07'; break;
				case '20': $defaultValues->paramsDecoded->runTimeHour = '08'; break;
				case '21': $defaultValues->paramsDecoded->runTimeHour = '09'; break;
				case '22': $defaultValues->paramsDecoded->runTimeHour = '10'; break;
				case '23': $defaultValues->paramsDecoded->runTimeHour = '11'; break;
			}
		}
		
		//set recurrence
		if ($params->recur == 1) {
			$recurValues = explode(' ', $params->recurrence);	
			$defaultValues->paramsDecoded->intervalRadio = 'recur';
			$defaultValues->paramsDecoded->interval = $recurValues[0];
			$defaultValues->paramsDecoded->intervalPeriod = $recurValues[1];
		}
		else {
			$defaultValues->paramsDecoded->intervalRadio = 'runOnce';
		}
			
		//email notification
		$defaultValues->paramsDecoded->notifyEmail = (isset($params->email) ? $params->email : '');
		
		//output options
		if ($params->csv_format == 1) 	$defaultValues->paramsDecoded->csvOutput = 'on';
		if ($params->html_format == 1)	$defaultValues->paramsDecoded->htmlOutput = 'on';
		if ($params->excel_format == 1) $defaultValues->paramsDecoded->excelOutput = 'on';
		if ($params->chart_bar == 1) 	$defaultValues->paramsDecoded->barChartOutput = 'on';
		if ($params->chart_line == 1) 	$defaultValues->paramsDecoded->lineChartOutput = 'on';
		
		//set table primary keys for editing
		$defaultValues->rid = $params->rid;
		$defaultValues->sid = $params->sid;
		
		$reportObj = new report();
		$report = $this->createReportObjectFromTemplate($templateID, $reportObj);
		$report->dataDecoded=json_decode($report->data);
		
		if (count(get_object_vars($report->dataDecoded->userParams)) > 0) {
			foreach ($report->dataDecoded->userParams as $param) {
								
				$passes = 0;	//generate 2 sets of inputs (range) when op is 'between'
				$paramName = substr($param->param, 2);
				$unsetName = $paramName;
				$newParamName = $paramName;
				
				do {
					
					if (strtolower($param->dataType) == 'timestamp') {
						$dateField=$defaultValues->paramsDecoded->$paramName;
						if (isset($dateField[$passes]->transform)) {
							$field = $newParamName.'_type';
							$defaultValues->paramsDecoded->$field = 'relative';
							$field = $newParamName.'_relative_value';
							$defaultValues->paramsDecoded->$field = $dateField[$passes]->params[0];
						}
						else {
							$field = $newParamName.'_type';
							$defaultValues->paramsDecoded->$field = 'real';
							$field = $newParamName.'_date';				
							$defaultValues->paramsDecoded->$field = $dateField[$passes];
							$defaultValues->paramsDecoded->$field = substr($dateField[$passes],5,2).'/'.substr($dateField[$passes],8,2).'/'.substr($dateField[$passes],0,4);
						}
					}
						
					if (strtolower($param->op) != 'between') break; 	//op is not 'between' so no second pass needed
					
					//set up the range.
					$newParamName.='_end';
					
				}
				while (++$passes < 2);
				
				if (strtolower($param->dataType) == 'timestamp') unset($defaultValues->paramsDecoded->$unsetName);
			}
		}
		
		$this->createReport($templateID, $defaultValues);	//pass in saved values
	}
	
	
	public function createReport($templateID, $defaultValues=NULL) {
		//generate the report form page that allows users to edit or run reports
		//if $defaultValues is NULL create a new report, else prepopulate the input fields

		//get the template that the report is built from
		$reportObj = new report();
		$report = $this->createReportObjectFromTemplate($templateID, $reportObj);
			
		if (NULL == $report) {
			new displayMessageView('Invalid template #'.$templateID);
			exit;
		}
		else {
			$report->dataDecoded=json_decode($report->data);
			if (NULL == $report->dataDecoded) {
				new displayMessageView('JSON format error decoding report data.');
				exit;
			}
				
			$reportView = new reportView($reportObj);
			$reportView->showReport($report, $defaultValues);
		}
	}

	public function createReportObjectFromTemplate($templateID, $reportObj){
		
		if ($this->security->validateInteger($templateID)) {		
			//get the template that the report is built from
			$report = $reportObj->getTemplate($templateID);
		}
		else {
			$report = NULL;
		}
		
		return $report;
	}
}
?>
