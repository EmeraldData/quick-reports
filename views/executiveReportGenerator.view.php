<?php 
class executiveReportsView {
 
	private $lastCategory;
	private $reportHeader;
	private $reportOutput;
	private $outputFormat;
	private $resourcesPath;
	private $reportFirstRow;
	
	public function __construct($outputFormat = NULL) {
		
		//automatically display the header in case we abort because of an error
		$this->lastCategory = NULL;
		$this->reportFirstRow = true;
		$this->outputFormat = $outputFormat;
		$this->createReportHeader();
	}
	
	
	private function createReportHeader() {
		
		$sitePath = $this->outputFormat=='excel' ? ($_SERVER['SERVER_PORT']==443 ? 'https://':'http://') . $_SERVER['SERVER_NAME'] . QR_SITE_ROOT : '';
		$this->reportHeader = '
		<!doctype html>
		<html>
		<head>
		<meta charset="utf-8">
		<title>'.QR_EXECUTIVE_REPORTS_TITLE_TAG_TEXT.'</title>
		
		<link type="text/css" href="'.$sitePath.'css/executiveReport_v1.0.css" rel="stylesheet">
		
		</head>
		<body>
		
		<table cellpadding="0" cellspacing="0" border="0">
		<tr>
		<td id="subHeaderLeft">
		<table cellpadding="0" cellspacing="0" border="0" >
		<tr><td class="reportHeader" colspan="2">'.QR_EXECUTIVE_REPORTS_OUTPUT_HEADER_TITLE.'</td></tr>
		';		
		return;
	}


	public function createReportSubHeader($ouInfo, $reportDate) {
				
		$buffer = '<tr><td class="reportSubHeader">';
		switch ($ouInfo->ou_type) {
			case 1: $buffer .= 'Consortium:&nbsp;'; break;
			case 2: $buffer .= 'System:&nbsp;'; break;
			default: $buffer .= 'Branch:&nbsp;'; break;
		}

		//subHeader (report name, org unit, date) followed by column headings
		$buffer .= '
		</td><td class="reportSubHeader">'.$ouInfo->name.'</td></tr>
		<tr><td class="reportSubHeader">Date:</td><td class="reportSubHeader">'.$reportDate->monthName.', '.$reportDate->reportYear.'</td></tr></table>
		</td>
		<td id="logo"></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr><td colspan="2">
		<table class="contentTable">
		<tr>';
		$this->reportOutput .= $buffer;
	}


	public function createColumnHeader($ouInfo){
		
		$buffer = '
		<td class="columnLabel">Category</td>
		<td class="columnLabel">Report</td>
		<td class="columnLabel">Current<br>Month</td>
		<td class="columnLabel">Previous<br>Month</td>
		<td class="columnLabel">% Change<br>from<br>Previous<br>Month</td>
		<td class="columnLabel">% Change<br>from<br>Previous<br>Year</td>';
		
		//add columns for system and/or consortium
		if ($ouInfo->ou_type == 3) $buffer .= '<td class="columnLabel">% of<br>System<br>Total</td>';
		if ($ouInfo->ou_type >= 2) $buffer .= '<td class="columnLabel">% of<br>'.QR_EXECUTIVE_REPORTS_CONSORTIUM_COLUMN_HEADING.'<br>Total</td>';
		$this->reportOutput .= $buffer;
	}
	
	
	public function displayReportHeader() {
		
		if ($this->outputFormat == 'excel') {
			header("Content-Disposition: attachment; filename=\"executive_report.xls\"");
			header("Content-Type: application/vnd.ms-excel");
		}
		echo $this->reportHeader;
	}

	
	public function displayReportOutput($outputFormat = NULL) {
		
		$this->displayReportHeader($outputFormat);
		echo $this->reportOutput;
		$verID='3fca9f8aa945f04e090b258b2fcf10e4';
		echo "</table></table><!--$verID--><br></body></html>";
	}	
	
	
	public function createReportRow($reportInfo, $rowData) {
	
		//setup the data display variables
		$buffer = NULL;
		$thisMonth = $rowData->thisMonth;
		$lastMonth = $rowData->lastMonth;
		$yearlyChange  = NULL;
		$monthlyChange = NULL;
		$percentSystem = NULL;
		$percentConsortium = NULL;
		$thisMonthBackgroundClass  = NULL;
		$lastMonthBackgroundClass  = NULL;
		$yearlyChangeBackgroundClass  = NULL;
		$monthlyChangeBackgroundClass = NULL;
		$percentSystemBackgroundClass = NULL;
		$percentConsortiumBackgroundClass = NULL;

		if (0 == $rowData->thisMonth) $rowData->thisMonth = NULL;
		if (0 == $rowData->lastMonth) $rowData->lastMonth = NULL;
		
		if (NULL == $rowData->thisMonth) {
			$thisMonth = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$thisMonthBackgroundClass= ' class="zeroValueBackground" ';
	
			$yearlyChange = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$yearlyChangeBackgroundClass = ' class="zeroValueBackground" ';
	
			$monthlyChange = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$monthlyChangeBackgroundClass = ' class="zeroValueBackground" ';
	
			$rowData->system = NULL;
			$percentSystem = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$percentSystemBackgroundClass = ' class="zeroValueBackground" ';
	
			$rowData->consortium = NULL;
			$percentConsortium = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$percentConsortiumBackgroundClass = ' class="zeroValueBackground" ';
		}
	
		if (NULL == $rowData->lastMonth) {
			$lastMonth = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$lastMonthBackgroundClass= ' class="zeroValueBackground" ';
			$monthlyChange = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$monthlyChangeBackgroundClass = ' class="zeroValueBackground" ';
		}

		if (NULL == $rowData->lastYear) {
			$yearlyChange = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
			$yearlyChangeBackgroundClass= ' class="zeroValueBackground" ';
		}
	
		if (NULL == $monthlyChange) {
			if ($rowData->thisMonth == $rowData->lastMonth) {
				$monthlyChange = '0%';
			}
			else {
				$monthlyChange = number_format(100 * ($rowData->thisMonth - $rowData->lastMonth) / $rowData->lastMonth) . '%';
				if ($monthlyChange == '-0%') $monthlyChange = '0%';
				if (substr($monthlyChange, 0, 1) == '-') $monthlyChange = "<span class=\"negativeValue\">$monthlyChange</span>";
			}
		}
	
		if (NULL == $yearlyChange) {
			if ($rowData->thisMonth == $rowData->lastYear) {
				$yearlyChange = '0%';
			}
			else {
				$yearlyChange = number_format(100 * ($rowData->thisMonth - $rowData->lastYear) / $rowData->lastYear) . '%';
				if ($yearlyChange == '-0%') $yearlyChange = '0%';
				if (substr($yearlyChange, 0, 1) == '-') $yearlyChange = "<span class=\"negativeValue\">$yearlyChange</span>";
			}
		}
	
		//create a new row. Do not repeat the category in the first column. 
		//Blank row between categories. No blank row after the column headers. 
		$buffer .= '<tr><td class="rowLabel">';
		if ($reportInfo->category != $this->lastCategory) {
			if ($this->reportFirstRow)
				$this->reportFirstRow = false;
			else {
				//new category so this row will become the blank row between categories
				$buffer .= '</td>';
				for ($i=1; $i<=$reportInfo->numColumns-1; $i++) $buffer .= '<td>&nbsp;</td>';
				$buffer .= '<tr><td class="rowLabel">';
			}
			$buffer .= $reportInfo->category;	//column 1
			$this->lastCategory = $reportInfo->category;
		}

		//if format is set to currency display as currency, else default to integer
		$numDecimals = (isset($rowData->format) && $rowData->format == 'currency') ? 2 : 0;
		if (NUll != $rowData->thisMonth) $thisMonth = ($rowData->format == 'currency' ? '$' : '') . number_format($rowData->thisMonth, $numDecimals);
		if (NUll != $rowData->lastMonth) $lastMonth = ($rowData->format == 'currency' ? '$' : '') . number_format($rowData->lastMonth, $numDecimals);
		
		//display the remainig data columns
		$buffer .= '</td><td>'.((QR_EXECUTIVE_REPORTS_SUBREPORT_PADDING == $reportInfo->description) ? QR_EXECUTIVE_REPORTS_SUBREPORT_PADDING.QR_EXECUTIVE_REPORTS_MISSING_SUBREPORT_LABEL : $reportInfo->description).'</td>';
		$buffer .= "<td align=\"right\" $thisMonthBackgroundClass>".$thisMonth.'</td>';
		$buffer .= "<td align=\"right\" $lastMonthBackgroundClass>".$lastMonth.'</td>';
		$buffer .= "<td align=\"right\" $monthlyChangeBackgroundClass>$monthlyChange</td>";
		$buffer .= "<td align=\"right\" $yearlyChangeBackgroundClass>$yearlyChange</td>";
	
		//% of system column
		if ($reportInfo->ou_type == 3) {
			if (NULL == $percentSystem)	{
				if (NULL == $rowData->system || NULL == $rowData->thisMonth) {
					$percentSystem = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
					$percentSystemBackgroundClass = ' class="zeroValueBackground" ';
				}
				elseif ($rowData->thisMonth == $rowData->system) {
					$percentSystem = '100%';
				}
				else {
					$percentSystem = number_format(100 * $rowData->thisMonth / $rowData->system) . '%';
					if ($percentSystem == '-0%') $percentSystem = '0%';
					if (substr($percentSystem, 0, 1) == '-') $percentSystem = "<span class=\"negativeValue\">$percentSystem</span>";
				}
			}
			$buffer .= "<td align=\"right\" $percentSystemBackgroundClass>$percentSystem</td>";
		}
	
		// % of consortium column
		if ($reportInfo->ou_type >= 2) {
			if (NULL == $percentConsortium)	{
				if (NULL == $rowData->consortium) {
					$percentConsortium = '<span class="zeroValue">'.QR_EXECUTIVE_REPORTS_ZERO_VALUE.'</span>';
					$percentConsortiumBackgroundClass = ' class="zeroValueBackground" ';
				}
				elseif ($rowData->thisMonth == $rowData->consortium) {
					$percentConsortium = '100%';
				}
				else {
					$percentConsortium = number_format(100 * $rowData->thisMonth / $rowData->consortium) . '%';
					if ($percentConsortium == '-0%') $percentConsortium = '0%';
					if (substr($percentConsortium, 0, 1) == '-')  $percentConsortium = "<span class=\"negativeValue\">$percentConsortium</span>";
				}
			}
			$buffer .= "<td align=\"right\" $percentConsortiumBackgroundClass>$percentConsortium</td>";
		}
		
		$buffer .= '</tr>';		//complete the row
		$this->reportOutput .= $buffer;
		return;
	}

}
?>

