<?php
if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME']=='localhost' || $_SERVER['SERVER_NAME']=='dev'))
	include 'config/dev.config.php';
else
	include 'config/production.config.php';
include 'config/app.config.php';
include 'models/db.class.php';
include 'models/executiveReport.class.php';
include 'views/executiveReportGenerator.view.php';
include 'models/security.class.php';
include 'views/displayMessage.view.php';

class executiveReportGenerator {
	
	public function __construct() {

		if (QR_PGSQL_USE_OPENSRF_XML_CONFIG || (QR_SESSIONS_IN_MEMCACHE && QR_MEMCACHE_USE_OPENSRF_XML_CONFIG)) {
			include 'models/openilsConfig.class.php';
			new openilsConfig();	//parse openils xml config file
		}
		
		$security = new security();
		$security->sanitizeServerQueryString();
		
		$report = new executiveReport();
		$executiveReportsList = $report->getExecutiveReportsList();
		$maxReportCount = count($executiveReportsList);
		
		//url format will be ?[format]/ouID/year/month/reportID/reportID/...
		//strip leading / if present, add trailing / if missing,  and use explode to generate an array of strings
		if (substr($_SERVER['QUERY_STRING'], 0, 1) == '/') $_SERVER['QUERY_STRING']=substr($_SERVER['QUERY_STRING'], 1);
		if (substr($_SERVER['QUERY_STRING'],strlen($_SERVER['QUERY_STRING'])-1,1) != '/') $_SERVER['QUERY_STRING'] .= '/';
		$qsParamsArray = explode('/', $_SERVER['QUERY_STRING']);

		if ($qsParamsArray[0] == 'excel') {		//download excel
			$qsOffset = 1;
			$outputFormat = 'excel';
			unset($qsParamsArray[0]);
		}
		else {
			$qsOffset = 0;
			$outputFormat = 'html';
		}
		
		$reportView = new executiveReportsView($outputFormat);
		
		if (count($qsParamsArray) < (4)) {
			$reportView->displayReportHeader();
			new displayMessageView('Invalid parameters.');
			exit;
		}

		$buffer = '';
		$numReports = 0;
		$system 	= NULL;
		$consortium = NULL;
		
		$rowData = new stdClass();
		$rowData->system = NULL;
		$rowData->consortium = NULL;
		
		$reportData = new stdClass();
		$reportData->thisMonth 	= NULL;
		$reportData->lastMonth 	= NULL;
		$reportData->lastYear 	= NULL;
		$reportData->reportYear  = $qsParamsArray[1 + $qsOffset];
		$reportData->reportMonth = $qsParamsArray[2 + $qsOffset];
		$reportData->reportMonthStr = ($reportData->reportMonth < 10) ? '0'.$reportData->reportMonth : (string)$reportData->reportMonth;

		$orgID = $qsParamsArray[0 + $qsOffset];
		if (!$security->validateInteger($orgID)) {
			$reportView->displayReportOutput();
			new displayMessageView('Invalid branch or system specified.');
			exit;
		}
		
		unset($qsParamsArray[0 + $qsOffset]);
		unset($qsParamsArray[1 + $qsOffset]);
		unset($qsParamsArray[2 + $qsOffset]);
		
		$invalidDate = false;
		if (!$security->validateInteger($reportData->reportYear) || !$security->validateInteger($reportData->reportMonth)) {
			$invalidDate = true;
		}
		else {
			switch ($reportData->reportMonth) {
				case '1':  $reportData->monthName='January'; 	$reportPriorMonthStr='12';  break;
				case '2':  $reportData->monthName='February';	$reportPriorMonthStr='01';  break;
				case '3':  $reportData->monthName='March';		$reportPriorMonthStr='02';  break;
				case '4':  $reportData->monthName='April'; 	 	$reportPriorMonthStr='03';  break;
				case '5':  $reportData->monthName='May'; 		$reportPriorMonthStr='04';  break;
				case '6':  $reportData->monthName='June'; 		$reportPriorMonthStr='05';  break;
				case '7':  $reportData->monthName='July'; 		$reportPriorMonthStr='06';  break;
				case '8':  $reportData->monthName='August'; 	$reportPriorMonthStr='07';  break;
				case '9':  $reportData->monthName='September'; 	$reportPriorMonthStr='08';  break;
				case '10': $reportData->monthName='October'; 	$reportPriorMonthStr='09';  break;
				case '11': $reportData->monthName='November'; 	$reportPriorMonthStr='10';  break;
				case '12': $reportData->monthName='December';	$reportPriorMonthStr='11';  break;
				default: $invalidDate = true; break;
			}
		}
		
		if ($invalidDate) {
			$reportView->displayReportHeader();
			new displayMessageView('Invalid Report Date');
			exit;
		}
		
		$ouInfo = $report->getOUInfo($orgID, QR_QUERY_RETURN_ONE_ROW, true);
		if (NULL == $ouInfo) {
			$reportView->displayReportHeader();
			new displayMessageView('Invalid branch or system specified.');
			exit;
		}
				
		$reportView->createReportSubHeader($ouInfo, $reportData);
		
		//get current month, prior month and prior year data for this branch 
		$yearMonthList = $reportData->reportYear.$reportData->reportMonthStr;		//current month
		$yearMonthList .= ','.(($reportData->reportMonth==1) ? ($reportData->reportYear-1) : $reportData->reportYear).$reportPriorMonthStr;	//last month
		$yearMonthList .= ','.($reportData->reportYear-1).$reportData->reportMonthStr;	//last year
		$data = $report->getExecutiveReportData($ouInfo->id, $yearMonthList);
		
		//Determine which row of data represents which month and which org type - branch, system, consortium
		//Process only the most current rows of data. Records must be returned with "order by org_unit, year_month, desc, create_time desc"
		$alreadyProcessed = array();
		foreach ($data as $row) {
			if (isset($alreadyProcessed[$row->org_unit.$row->year_month])) continue;
	
			$alreadyProcessed[$row->org_unit.$row->year_month] = true;
			if ($row->year_month == $yearMonthList) {
				if ($row->org_unit == $ouInfo->id)
					$reportData->thisMonth = $row;
				elseif ($row->org_unit == $ouInfo->parent_ou && $ouInfo->ou_type == 3)
					$system = $row;
				else
					$consortium = $row;
			}
			elseif ($row->org_unit == $ouInfo->id && $row->year_month == ($reportData->reportYear-1).$reportData->reportMonthStr)
				$reportData->lastYear = $row;
			elseif ($row->org_unit == $ouInfo->id && $row->year_month == (($reportData->reportMonth==1) ? ($reportData->reportYear-1) : $reportData->reportYear).$reportPriorMonthStr)
				$reportData->lastMonth = $row;
		}
		
		if (NULL == $reportData->thisMonth) {
			$reportView->displayReportOutput();
			new displayMessageView("No data available for $reportData->monthName, $reportData->reportYear");
			exit;
		}
		
		//if no reports are specified default is process all reports
		if (isset($qsParamsArray[3 + $qsOffset]) && NULL == $qsParamsArray[3 + $qsOffset]) {
			unset($qsParamsArray[3 + $qsOffset]);
			foreach ($executiveReportsList as $reportListObj) $qsParamsArray[] = $reportListObj->id;
		}

		//process each selected report - one report is one row of output
		$reportView->createColumnHeader($ouInfo);
		
		foreach ($qsParamsArray as $reportID ) {

			if (NULL == $reportID) continue;	//skip trailing slash in querystring
			
			if ($numReports++ > $maxReportCount) {
				$reportView->displayReportOutput();
				new displayMessageView('Too many parameters.');
				exit;
			}
					
			//validate report exists
			$reportInfo = NULL;
			$reportID = strtolower($reportID);	//accept querystring parameters in lower or upper case - (e.g.)  /p1/ or /P1/
			foreach ($executiveReportsList as $er) {			
				if ($reportID == strtolower($er->id)) $reportInfo = $er;
			}
			if (NULL == $reportInfo) continue;	//invalid querystring parameter, skip reports that are not defined
			
			$rowData->format = isset($reportInfo->format) ? $reportInfo->format : NULL;
			$reportInfo->ou_type = $ouInfo->ou_type;

			switch ($reportInfo->ou_type) {
				case 1: $reportInfo->numColumns=6; break;
				case 2: $reportInfo->numColumns=7; break;
				default: $reportInfo->numColumns=8; break;
			}

			//data may be numeric or an array of json objects for reports that have subreports (group by in sql)
			$dataSystem 	= (!isset($system->$reportID)) 		? NULL : json_decode($system->$reportID, false);
			$dataConsortium	= (!isset($consortium->$reportID)) 	? NULL : json_decode($consortium->$reportID, false);
			$dataThisMonth 	= (!isset($reportData->thisMonth->$reportID)) ? NULL : json_decode($reportData->thisMonth->$reportID, false);
			$dataLastMonth 	= (!isset($reportData->lastMonth->$reportID)) ? NULL : json_decode($reportData->lastMonth->$reportID, false);
			$dataLastYear	= (!isset($reportData->lastYear->$reportID))  ? NULL :json_decode($reportData->lastYear->$reportID, false);

			if (is_array($dataThisMonth) || is_array($dataLastMonth)) {		//one of the months is an array of json objects and contains subreports
				$dataThisMonthArray=array();
				$dataLastMonthArray=array();
				$dataLastYearArray=array();
				$dataSystemArray=array();
				$dataConsortiumArray=array();
				
				
				//convert [{"key":"total","value":number},{"key":subreport,"value":numeric},...] to a simple array of key/value pairs and calculate totals
				$dataThisMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]=0;
				$dataLastMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]=0;
				$dataLastYearArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]=0;
				$dataSystemArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]=0;
				$dataConsortiumArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]=0;
				
				if (NULL != $dataThisMonth)  foreach ($dataThisMonth as $d)  {$dataThisMonthArray[$d->key]=$d->value; 	$dataThisMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]+=$d->value;}
				if (NULL != $dataLastMonth)  foreach ($dataLastMonth as $d)	 {$dataLastMonthArray[$d->key]=$d->value; 	$dataLastMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]+=$d->value;}
				if (NULL != $dataLastYear)	 foreach ($dataLastYear as $d)	 {$dataLastYearArray[$d->key]=$d->value; 	$dataLastYearArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]+=$d->value;}	
				if (NULL != $dataSystem)	 foreach ($dataSystem as $d) 	 {$dataSystemArray[$d->key]=$d->value;		$dataSystemArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]+=$d->value;}
				if (NULL != $dataConsortium) foreach ($dataConsortium as $d) {$dataConsortiumArray[$d->key]=$d->value; 	$dataConsortiumArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]+=$d->value;}

				//first display the total row
				$rowData->thisMonth  = (!isset($dataThisMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]))  ? NULL : $dataThisMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY];
				$rowData->lastMonth  = (!isset($dataLastMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]))  ? NULL : $dataLastMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY];
				$rowData->lastYear   = (!isset($dataLastYearArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]))   ? NULL : $dataLastYearArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY];
				$rowData->system	 = (!isset($dataSystemArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]))	  ? NULL : $dataSystemArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY];
				$rowData->consortium = (!isset($dataConsortiumArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY])) ? NULL : $dataConsortiumArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY];

				$reportView->createReportRow($reportInfo, $rowData);

				unset($dataThisMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]);
				unset($dataLastMonthArray[QR_EXECUTIVE_REPORTS_TOTAL_KEY]);

				//merge and sort subreport data for the current and the prior months - a key/value pair could be missing from one of the months
				$dataCombined = array_merge($dataThisMonthArray, $dataLastMonthArray);
				uksort($dataCombined, "strcasecmp");	//case insensitive sort

				//display each of the rows of data (subreports) that sum to the total
				foreach ($dataCombined as $key => $value) { 
					$reportInfo->description = QR_EXECUTIVE_REPORTS_SUBREPORT_PADDING.$key;
					$rowData->thisMonth  = (!isset($dataThisMonthArray[$key])) 	? NULL : $dataThisMonthArray[$key];
					$rowData->lastMonth  = (!isset($dataLastMonthArray[$key])) 	? NULL : $dataLastMonthArray[$key];
					$rowData->lastYear   = (!isset($dataLastYearArray[$key]))  	? NULL : $dataLastYearArray[$key];
					$rowData->system	 = (!isset($dataSystemArray[$key]))		? NULL : $dataSystemArray[$key];
					$rowData->consortium = (!isset($dataConsortiumArray[$key]))	? NULL : $dataConsortiumArray[$key];
					$reportView->createReportRow($reportInfo, $rowData);
				}
			}
			else {	//data is a numeric value, not json
				$rowData->thisMonth  = $dataThisMonth;
				$rowData->lastMonth  = $dataLastMonth;
				$rowData->lastYear   = $dataLastYear;
				$rowData->system 	 = $dataSystem;
				$rowData->consortium = $dataConsortium;
				$reportView->createReportRow($reportInfo, $rowData);
			}
		}

		$reportView->displayReportOutput($outputFormat);
	}

}
?>
