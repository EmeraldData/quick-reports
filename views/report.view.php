<?php 
class reportView extends baseReportView {
			
	public function __construct($modelObj=NULL) {
		
		if (NULL != $modelObj) $this->reportModel = $modelObj;
	}
	
	public function displaySavedReportsList($list) {
		
		if (NULL == $list) {
			new displayMessageView('No reports found.');
			exit;
		}
		
		echo '<table><tr><td class="defaultTD"><b>Report Name</b></td><td class="defaultTD"><b>Description</b></td><td class="defaultTD"><b>Last Update</b></td><td class="defaultTD"><b>Action</b></td></tr>';
		foreach ($list as $row) {
		echo 
			'<tr><td class="defaultTD maxWidth500">'.$row->name.'</td><td class="defaultTD maxWidth500">'.$row->description.'</td><td class="nowrap defaultTD">'.date('m/d/y h:i:s A', strtotime($row->create_time)).'</td>
			<td class="nowrap defaultTD"><img alt="Edit Report Icon" width="15" height="15" src="images/icon_16_pencil.png" class="imageBottom">&nbsp;<a class="defaultLink" href="'.QR_SITE_ROOT.QR_EDIT_DRAFT_REPORT_PAGE,$row->id,'/">Edit Report</a><br>
			<img alt="Delete Report Icon" width="15" height="15" src="images/icon_16_delete.png" class="imageBottom">&nbsp;<a class="defaultLink" href="javascript:void(0);" onclick="return confirmDeleteReport(',$row->id,',\'',$row->name,'\',\'Delete\',false,false,\'',QR_SITE_ROOT.QR_DELETE_DRAFT_REPORT_PAGE,'\');">Delete Report</a></td></tr>';
		}
		echo '</table>';
	}
	
	public function showReport($report, $defaultValues=NULL) {
		//main function to generate the report form

		$this->defaultValues = $defaultValues;
		?>
		
		<link rel="stylesheet" href="jquery/ui/1.11.3/themes/smoothness/jquery-ui.css">		
		<script src="jquery/jquery-1.10.2.js"></script>
		<script src="jquery/ui/1.11.3/jquery-ui.js"></script>
		<script>
		$(function() {$( "#runDate" ).datepicker();});
		//set defaults for all datepickers
		$.datepicker.setDefaults({
			showOn: "both",
			buttonImageOnly: true,
			buttonImage: "images/calendar.gif",
			buttonText: "Select date",
			});
		</script>

		<script  type="text/javascript">
		var reportFiltersState=[];
		reportFiltersState["reportShowHideFilters"]=true;	//hide by default
		reportFiltersState["reportShowHideOptions"]=true;	//hide by default
		</script>

		<form name="form1" id="form1" method="post" action="">
		<input name="templateID" type="hidden" value="<?php echo $report->template_id; ?>">
		<input name="draftID" type="hidden" value="<?php if (isset($defaultValues->id)) echo $defaultValues->id; ?>">
		<table cellpadding="2" cellspacing="0" border="0">
		<tr>
		<td colspan="2"><tr><td colspan="3" style="background-color:#15513d; color:#fff; font-weight:bold; font-size:11pt">Template Information</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<table cellpadding="10" cellspacing="0" border="0" width="100%">
		<tr>
		
		<td width="125" class="defaultTD">&nbsp;Template Name:&nbsp;</td>
		<td class="defaultTD">&nbsp;<?php echo $report->template_name;?>&nbsp;
			<?php 
			echo '<br>&nbsp;<a class="defaultLink" href="javascript:void(0);" onclick=\'javascript:openWindow	("',preg_replace(unserialize(QR_DEFAULT_ESCAPE_PATTERNS), unserialize(QR_DEFAULT_ESCAPE_REPLACEMENTS), $report->description),'");\'>Description</a>';			
			if (!empty($report->doc_url)) echo '<br>&nbsp<a class="defaultLink" target="_blank" href="',$report->doc_url,'">Documentation</a>';
			?>
		</td>
		</tr>
		<tr>
		
		<td>&nbsp;Report Columns:&nbsp;</td>
		<td>
		<?php 
		foreach ($report->dataDecoded->reportColumns as $rc)  {
			echo '&nbsp;',$rc->name;
			if ($rc->aggregate == '1') echo ' ('. $rc->transformLabel .')';
			echo '<br>';
		}
		?>
		</td>
		</tr>
		</table>
		</tr>
		<tr><td colspan="3" style="background-color:#15513d; color:#fff; font-weight:bold; font-size:11pt">Report Title</td></tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<table cellpadding="10" cellspacing="0" border="0" width="100%">
		<tr>
		
		<td width="125">&nbsp;Report Name:&nbsp;</td>
		<td>&nbsp;<input class="inputName" name="name" id="name" type="text" value="<?php echo (isset($defaultValues->name))?$defaultValues->name:''; ?>" maxlength="100">&nbsp;</td>
		</tr>
		<tr>
		<td nowrap>&nbsp;Report Description:&nbsp;</td>
		<td>&nbsp;<textarea name="description" id="description"><?php echo (isset($defaultValues->description))?$defaultValues->description:''; ?></textarea>&nbsp;</td>
		</tr>
		</table>
		</tr>
		<tr><td colspan="3" style="background-color:#15513d; color:#fff; font-weight:bold; font-size:11pt">Choose Report Filters</td></tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		
		<table cellpadding="10" cellspacing="0" border="0" width="100%">
		<tr>
		<th class="defaultTD">&nbsp;Column&nbsp;</th>
		<!-- <th class="defaultTD">&nbsp;Transform&nbsp;</th> -->
		<th class="defaultTD">&nbsp;Condition&nbsp;</th>
		<th class="defaultTD">&nbsp;Value&nbsp;</th>
		<th class="defaultTD"><span class="accessibility">Selected Values</span></th>
		</tr>

		<?php //user entered parameters
		if (count(get_object_vars($report->dataDecoded->userParams)) > 0) {
			$jsColumnNamesString=NULL;	//object to hold column names for javascript error checking messages 
			foreach ($report->dataDecoded->userParams as $param) {
				$jsColumnNamesString .= ((NULL==$jsColumnNamesString)?'':',').substr($param->param, 2).':"'.$param->column.'"';
				$up = $this->parseColumns($param);
				echo '
				<tr>
				<td class="defaultTD"><span class="nowrap">',str_replace('->', '</span><span style="white-space:normal">-> </span><span class="nowrap">', $up->column),((NULL == $up->transformLabel)?'':'&nbsp;('.$up->transformLabel.')'),'&nbsp;';
				if (QR_SHOW_FIELD_DOC && isset($up->fieldDoc) && NULL != $up->fieldDoc) echo '<br><span class="fieldDoc">(',$up->fieldDoc,')</span>&nbsp;';
				echo '</td>
				<!--<td class="defaultTD nowrap" align="center">',$up->transformLabel,'&nbsp;</td>-->
				<td class="defaultTD nowrap" align="center">',$up->opLabel,'&nbsp;</td>
				<td class="defaultTD">',$up->param,'&nbsp;</td>
				</tr>';
			}
		} 
		?>
		
		<?php //static parameters
		if (count(get_object_vars($report->dataDecoded->staticParams)) > 0) { 
			echo '<tr><td colspan="4"><a href="javascript:void(0);" onclick="toggleReportShowHide(\'reportShowHideFilters\',\'showFilterLabel\');"><span id="showFilterLabel">+Show</span> Static Filters</a></td></tr>';
			foreach ($report->dataDecoded->staticParams as $param) {
				$sp = $this->parseColumns($param);
				echo '
				<tr class="reportShowHideFilters" style="display:none;">
				<td class="defaultTD"><span class="nowrap">',str_replace('->', '</span><span style="white-space:normal">-> </span><span class="nowrap">', $sp->column),((NULL == $sp->transformLabel)?'':'&nbsp;('.$sp->transformLabel.')'),'&nbsp;';				
				if (QR_SHOW_FIELD_DOC && isset($sp->fieldDoc) && NULL != $sp->fieldDoc) echo '<br><span class="fieldDoc">(',$sp->fieldDoc,')</span>&nbsp;';
				echo '</td>
				<!--<td class="defaultTD" align="center">&nbsp;',$sp->transformLabel,'&nbsp;</td>-->
				<td class="defaultTD" align="center">&nbsp;',$sp->opLabel,'&nbsp;</td>
				<td class="defaultTD">&nbsp;',$sp->param,'&nbsp;</td>
				<td class="defaultTD"></td>	
				</tr>';
			}
		} 
		?>

		</table>
		</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td colspan="2"><tr><td colspan="3" style="background-color:#15513d; color:#fff; font-weight:bold; font-size:11pt">Choose Report Output Options</td>
		</tr>
		<tr>
		<td>&nbsp;</td>
		<td>
		<table cellpadding="10" cellspacing="0" border="0" width="100%">
		<tr>
		<td class="defaultTD">&nbsp;Recurrence Interval:</td>
		<td class="defaultTD">
		<input <?php echo (NULL==$defaultValues || (isset($defaultValues->paramsDecoded->intervalRadio) && $defaultValues->paramsDecoded->intervalRadio=='runOnce'))?' checked ':''; ?> type="radio" name="intervalRadio" id="intervalRadioOnce" value="runOnce">Run one time only <br>
		<input <?php echo (isset($defaultValues->paramsDecoded->intervalRadio) && $defaultValues->paramsDecoded->intervalRadio=='recur')?' checked ':''; ?> type="radio" name="intervalRadio" id="intervalRadioRecur" value="recur">Recur every
		<select name="interval" id="interval"> 
		<option value=""></option>
		<?php 
		for ($i=1; $i<=31; $i++) {
			echo '<option ', (isset($defaultValues->paramsDecoded->interval) && $defaultValues->paramsDecoded->interval==$i)?' selected ':'', 'value="', $i, '">', $i,'</option>'; 
		}	
		?>
		</select>
		&nbsp;
		<select name="intervalPeriod" id="intervalPeriod">
		<option></option>
		<option <?php echo (isset($defaultValues->paramsDecoded->intervalPeriod) && substr($defaultValues->paramsDecoded->intervalPeriod,0,3)=='day')?' selected ':''; ?> value="day">Day(s)</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->intervalPeriod) && substr($defaultValues->paramsDecoded->intervalPeriod,0,4)=='week')?' selected ':''; ?> value="week">Week(s)</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->intervalPeriod) && substr($defaultValues->paramsDecoded->intervalPeriod,0,3)=='mon')?' selected ':''; ?> value="mon">Month(s)</option>
		</select>
		</tr>
		<tr>
		
		<td class="defaultTD">&nbsp;Run Time:</td>
		<td class="defaultTD">
		<input <?php echo (NULL==$defaultValues || (isset($defaultValues->paramsDecoded->runTimeRadio) && $defaultValues->paramsDecoded->runTimeRadio=='asap'))?' checked ':''; ?> type="radio" name="runTimeRadio" id="runTimeRadioASAP" value="asap">As soon as possible <br>
		<input <?php echo (isset($defaultValues->paramsDecoded->runTimeRadio) && $defaultValues->paramsDecoded->runTimeRadio=='scheduledTime')?' checked ':''; ?> type="radio" name="runTimeRadio" id="runTimeRadioScheduled" value="scheduledTime">At a scheduled time on&nbsp;&nbsp;
			
		<input class="hasDatePicker" value="<?php echo (isset($defaultValues->paramsDecoded->runDate))?$defaultValues->paramsDecoded->runDate:''; ?>" name="runDate" id="runDate" type="text" size="8" maxlength="10">
		&nbsp;at&nbsp;
		<select name="runTimeHour" id="runTimeHour">
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='midnight')?' selected ':''; ?> value="midnight">Midnight</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='01')?' selected ':''; ?> value="01">1:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='02')?' selected ':''; ?> value="02">2:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='03')?' selected ':''; ?> value="03">3:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='04')?' selected ':''; ?> value="04">4:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='05')?' selected ':''; ?> value="05">5:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='06')?' selected ':''; ?> value="06">6:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='07')?' selected ':''; ?> value="07">7:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='08')?' selected ':''; ?> value="08">8:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='09')?' selected ':''; ?> value="09">9:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='10')?' selected ':''; ?> value="10">10:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='11')?' selected ':''; ?> value="11">11:00</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeHour) && $defaultValues->paramsDecoded->runTimeHour=='noon')?' selected ':''; ?> value="noon">Noon</option>
		</select>

		<select name="runTimeAMPM">
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeAMPM) && $defaultValues->paramsDecoded->runTimeAMPM=='AM')?' selected ':''; ?> value="AM">AM</option>
		<option <?php echo (isset($defaultValues->paramsDecoded->runTimeAMPM) && $defaultValues->paramsDecoded->runTimeAMPM=='PM')?' selected ':''; ?> value="PM">PM</option>
		</select>
		</td>
		</tr>

		<tr>		
			<td class="defaultTD">&nbsp;Email Notification:</td>
			<td class="defaultTD"><input class="inputName" name="notifyEmail" id="notifyEmail" type="text"  maxlength="500" value="<?php echo (isset($defaultValues->paramsDecoded->notifyEmail))?$defaultValues->paramsDecoded->notifyEmail:$_SESSION['email']; ?>"></td>
		</tr>
		
		<tr><td colspan="4"><a href="javascript:void(0);" onclick="toggleReportShowHide('reportShowHideOptions','showOptionsLabel');"><span id="showOptionsLabel">+Show</span> All Options</span></a></td></tr>
		<tr class="reportShowHideOptions" style="display:none;">
			<td class="defaultTD">&nbsp;Output Options:&nbsp;</td>
			<td class="defaultTD">
				<input name="excelOutput" id="excelOutput" type="checkbox" <?php if (!isset($defaultValues) || (isset($defaultValues->paramsDecoded->excelOutput) && $defaultValues->paramsDecoded->excelOutput=='on')) echo ' checked ' ?>>Excel Output<br>
				<input name="csvOutput" id="csvOutput" type="checkbox" <?php if (isset($defaultValues->paramsDecoded->csvOutput) && $defaultValues->paramsDecoded->csvOutput=='on') echo ' checked ' ?>>CSV Output<br>
				<input name="htmlOutput" id="htmlOutput" type="checkbox" <?php if (!isset($defaultValues) || (isset($defaultValues->paramsDecoded->htmlOutput) && $defaultValues->paramsDecoded->htmlOutput=='on')) echo ' checked ' ?>>HTML Output<br>
				&nbsp;&nbsp;&nbsp;&nbsp;<input name="barChartOutput" id="barChartOutput" type="checkbox" <?php if (isset($defaultValues->paramsDecoded->barChartOutput) && $defaultValues->paramsDecoded->barChartOutput=='on') echo ' checked ' ?>>Bar Charts<br>
				&nbsp;&nbsp;&nbsp;&nbsp;<input name="lineChartOutput" id="lineChartOutput" type="checkbox" <?php if (isset($defaultValues->paramsDecoded->lineChartOutput) && $defaultValues->paramsDecoded->lineChartOutput=='on') echo ' checked ' ?>>Line Charts<br>
			</td>
		</tr>
		
		<?php 
		//show pivot selections if there is an aggregate function in any of the report columns
		$hasAggregate = false;
		foreach ($report->dataDecoded->reportColumns as $rc)  {
			if ($rc->aggregate == 1) {
				$hasAggregate = true;
				break;
			}
		}
		if ($hasAggregate) {  
		?> 
		<tr class="reportShowHideOptions" style="display:none;">
			<td class="defaultTD" width="125">&nbsp;Pivot Label Column:&nbsp;</td>
			<td class="defaultTD" width="*">&nbsp;
				<select name="pivotLabelColumn" id="pivotLabelColumn">
					<option value=""> -- Select One (optional) -- </option>
					<?php
					foreach ($report->dataDecoded->reportColumns as $rc)  {
						if ($rc->aggregate != 1) echo '<option ',((isset($defaultValues->paramsDecoded->pivotLabelColumn) && $defaultValues->paramsDecoded->pivotLabelColumn==$rc->name)?' selected ':''),'value="',$rc->name,'">',$rc->name,'</option>'; 						
					}
					?>
				</select>&nbsp;
			</td>
		</tr>
		<tr class="reportShowHideOptions" style="display:none;">
			<td class="defaultTD">&nbsp;Pivot Data Column:&nbsp;</td>
			<td class="defaultTD">&nbsp;
				<select name="pivotDataColumn" id="pivotDataColumn">
					<?php
					$column = 1;	//pivot_data is the column number to pivot on
					foreach ($report->dataDecoded->reportColumns as $rc)  {
						if ($rc->aggregate == 1) echo '<option ',((isset($defaultValues->paramsDecoded->pivotDataColumn) && $defaultValues->paramsDecoded->pivotDataColumn==$rc->name)?' selected ':''),'value="',$column,'">',$rc->name,'</option>'; 						
						$column++;
					}
					?>
				</select>&nbsp;
			</td>
		</tr>
		<?php } ?>
		
		</table>
		</td>
		</tr>
		
		<tr>
		<td colspan="2" align="center">
			<input id="runButton" type="submit" class="submitButton" value="<?php echo (isset($defaultValues->submitButtonText)?$defaultValues->submitButtonText:'Run');?> Report"> &nbsp;
			<input id="saveButton" type="submit" class="submitButton" value="Save as Draft">
			<input type="hidden" name="reportAction" value="<?php echo (isset($defaultValues->submitButtonText)?$defaultValues->submitButtonText:'');?>">
			<input type="hidden" name="rid" value="<?php echo (isset($defaultValues->rid)?$defaultValues->rid:'');?>">
			<input type="hidden" name="sid" value="<?php echo (isset($defaultValues->sid)?$defaultValues->sid:'');?>">
		</td>	
		</tr>
		</table>
		</form>
		<br>
		
		<script type="text/javascript">
		var saveType='';
		document.getElementById("runButton").onclick = function(){
			saveType="run"; 
			document.getElementById('form1').action='<?php echo QR_RUN_REPORT_PAGE; ?>';
		}
		document.getElementById("saveButton").onclick = function(){
			saveType="save"; 
			document.getElementById('form1').action='<?php echo PNIES_SAVE_REPORT_PAGE; ?>';
		}
		document.getElementById("form1").onsubmit = function(){
			return submitReport(saveType); 
		}

		var columnNames=<?php echo '{',$jsColumnNamesString,"};\r\n"; ?>
		</script>
					
	<?php 			
	}

	private function parseColumns($param) {
	
		$p = $param;
		if (is_array($p->param)) $p->param = implode(',', $p->param);	//if array convert it back to a string

		$userParam = (substr($p->param,0,3) == '::P');					//user entered field or static
		$lowerOp = strtolower($p->op);
		$lowerParam = strtolower($p->param);
		$lowerOpLabel = strtolower($p->opLabel);
		$lowerDataType = strtolower($p->dataType);
		$lowerTransform = strtolower($p->transform);
	
		switch ($lowerOp) {
			case '=':
				$p->opLabel='Equals';
				break;
			case 'between':
				$p->opLabel='Between';
				break;
			case '<':
				if ($lowerDataType == 'timestamp')
					$p->opLabel='Before';
				else
					$p->opLabel='Less than';
				break;
			case '<=':
				if ($lowerDataType == 'timestamp')
					$p->opLabel='On or before';
				else
					$p->opLabel='Less than or equal to';
				break;				
			case '>':
				if ($lowerDataType == 'timestamp')
					$p->opLabel='After';
				else
					$p->opLabel='Greater than';
				break;
			case '>=':
				if ($lowerDataType == 'timestamp')
					$p->opLabel='On or after';
				else
					$p->opLabel='Greater than or equal to';
				break;
			case 'in':
				$p->opLabel='In List';
				break;
			case 'not in':
				$p->opLabel='Not In List';
				break;			
			case 'is':
				if (!$userParam && $lowerOpLabel == 'is null') {
					$param->param = 'Null';
				}
				$p->opLabel='Is';
				break;
			case 'is not':
				if (!$userParam && $lowerOpLabel == 'is not null') {
					$param->param = 'Null';
				}
				$p->opLabel='Is Not';
				break;
			default:
				$p->opLabel='unknown action -'.$param->op;
				break;
		}
	
		switch ($lowerTransform) {
			case 'bare':
				$p->transformLabel = NULL;
				break;
			case 'date':
				$p->transformLabel = NULL;
				break;
		}
	
		if (!$userParam && $lowerDataType == 'bool') {
			if ($lowerOp == '=') $p->opLabel='Is';
			if ($lowerParam == 't') {
				$p->param = 'True';
			}
			else {	
				$p->param = 'False';
			}
		}
	
		if ($userParam) {
			//generate user input fields
			$p->param = $this->generateUserInputFields($param);
		}
	
		return $p;
	}
	
	private function generateUserInputFields($param) {

		$cell = '';
		$paramName = substr($param->param, 2);
		$lowerOp = strtolower($param->op);
		$lowerParam = strtolower($param->param);
		$lowerOpLabel = strtolower($param->opLabel);
		$lowerDataType = strtolower($param->dataType);
		$lowerTransform = strtolower($param->transform);

		if (!isset($param->fieldDoc)) $param->fieldDoc = NULL;	//needed for version 3 templates
		if ($param->op == 'in' || $param->op == 'not in') {
			//process selects
			$hasSelectedDisplayColumn = true;
			$list = $this->reportModel->getListDataFromTable($param);

			if (count($list)==0) {
				$cell .= '<span class="errorText">Error: Unknown list</span>';
				$cell .= '<br>Debug info:<pre>'.print_r($param,true).'</pre></td><td class="defaultTD">'; 
			}
			else {	
				//if ($param->dataType == 'org_unit')
				if ($this->reportModel->multiSelect)	 
					$cell .= $this->createOrgUnitSelect($list, $paramName);
				else 
					$cell .= $this->createMultiSelectFromObject($list, $paramName);
			}
		}
		else {
				
			$passes = 0;	//generate 2 sets of inputs (range) when op is 'between'
			$hasSelectedDisplayColumn = false;
			do {
				$passes++;
	
				if (isset($param->aggregate) && $param->aggregate==1) $lowerDataType = 'int';	//treat aggregate as integer
				switch ($lowerDataType) {
					case 'timestamp':
						$paramNameDate = $paramName.'_type';	//::Px_type - relative or real
						$dateType = (isset($this->defaultValues->paramsDecoded->$paramNameDate)?$this->defaultValues->paramsDecoded->$paramNameDate:'real');
						$cell .= '<span class="nowrap"><select name="'.$paramName.'_type" id="'.$paramName.'_type" onchange=\'toggleRealRelativeDate(this,"'.$paramName.'_calendar_span","'.$paramName.'_days_span");\'>';
						$cell .= '<option '.(($dateType=='real')?' selected ':'').'value="real">Real Date</option><option '.(($dateType=='relative')?' selected ':'').'value="relative">Relative Date</option></select>&nbsp;';
						
						$paramNameDate = $paramName.'_date';	//::Px_date value
						$value = (isset($this->defaultValues->paramsDecoded->$paramNameDate)?$this->defaultValues->paramsDecoded->$paramNameDate:'');
						$cell .= '<span id="'.$paramName.'_calendar_span" '.(($dateType=='relative')?' style="display:none;"':'').'>
						<input class="userDate userInput hasDatePicker" name="'.$paramName.'_date" id="'.$paramName.'_date" type="text" size="6" maxlength="10" value="'.$value.'"></span>
						<script>
						$(function() {$( "#'.$paramName.'_date" ).datepicker({});});
						</script>';

						$paramNameDate = $paramName.'_relative_value';	//::Px_relative_value
						$value = (isset($this->defaultValues->paramsDecoded->$paramNameDate)?$this->defaultValues->paramsDecoded->$paramNameDate:'');
						$cell .= '<span id="'.$paramName.'_days_span" '.(($dateType=='real')?' style="display:none;"':'').'><select name="'.$paramName.'_relative_value" id="'.$paramName.'_relative_value" class="userInput" onchange="javascript:setDaysLabel(this, \''.$paramName.'_days_label\');">';
						for ($i = 1; $i <=90; $i++) $cell.='<option '.(($value == $i)?' selected ':'').'value="-'.$i.'">'.$i.'</option>';
						$cell .= '</select> <span id="'.$paramName.'_days_label">Day</span> ago</span></span>';
						break;
	
					case 'text':
						$cell .= '<input class="userText userInput" name="'.$paramName.'" id="'.$paramName.'" type="text" value="'.(isset($this->defaultValues->paramsDecoded->$paramName)?$this->defaultValues->paramsDecoded->$paramName:'').'">';
						break;
	
					case 'id':		
					case 'int':
						$cell .= '<input class="userInteger userInput" name="'.$paramName.'" id="'.$paramName.'" type="text" maxlength="10" value="'.(isset($this->defaultValues->paramsDecoded->$paramName)?$this->defaultValues->paramsDecoded->$paramName:'').'">';
						break;
	
					case 'bool':
						$list=array((object)array('id'=>'t', 'name'=>'True'),(object)array('id'=>'f', 'name'=>'False'));
						$cell .= $this->createSelectFromObject($list, $paramName);
						break;
				}
					
				if ($lowerOp != 'between') break; 	//op is not 'between' so no second pass needed
	
				//set up for second pass
				if ($passes < 2)  $cell.='<center>&nbsp;&nbsp;And</center>';
				$paramName.='_end';
			}
			while ($passes < 2);
		}
		
		if (!$hasSelectedDisplayColumn) $cell .= '<td class="defaultTD">';
		return $cell;
	}

}
?>
