<?php 
class executiveReportsView extends baseReportView {
	
	public function __construct() {}
	
	
	public function showReportListLinks($params, $executiveReportsList) {

		$pluralSuffix = (count($params->orgList)>1) ? 's' : '';	
		echo "Please click on the links below to view your report$pluralSuffix <br>";

		//truncate report list if all reports, else insert / as delimiter
		$params->reportList = (count($params->reportList) == count($executiveReportsList)) ? '' : implode('/', array_keys($params->reportList));

		foreach ($params->orgList as $orgListObj) {
			echo '<br>';
			if ($orgListObj->ou_type == 2) 
				echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';	
			elseif ($orgListObj->ou_type == 3)
				echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			echo $orgListObj->shortname,'&nbsp;';
			echo '&nbsp;&nbsp;<a target="_blank" class="executiveReportTypeLink" href="'.QR_CREATE_EXECUTIVE_REPORT_PAGE.$orgListObj->id.'/'.$params->reportYear.'/'.$params->reportMonth.'/'.$params->reportList.'">HTML</a>';
			echo '&nbsp;<span class="executiveReportTypeLink">|</span>';
			echo '&nbsp;<a class="executiveReportTypeLink" href="'.QR_CREATE_EXECUTIVE_REPORT_PAGE.'excel/'.$orgListObj->id.'/'.$params->reportYear.'/'.$params->reportMonth.'/'.$params->reportList.'">Excel</a>';
		}
		
		echo '<br><br>';
	}
	
	
	public function showExecutiveReportsMenu($orgList, $executiveReportsList) {

		?>
		<form name="reportForm" id="reportForm" method="post" action="<?php echo QR_LIST_EXECUTIVE_REPORTS_PAGE; ?>">
		<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
		<td><tr><td colspan="3" style="background-color:#15513d; color:#fff; font-weight:bold; font-size:11pt">Create Your Executive Snapshot Report:</td>
		</tr>
		<tr>
		<td>
					
		<div id="executiveReport">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr>
		<td width="562">
			<table class="executiveReportMenu executiveReportTopMenu" cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td class="width10"><b>1.&nbsp;</b></td>
				<td><b>Choose Location(s)</b></td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td>	
				<?php echo $this->createOrgUnitSelect($orgList, 'OUList', false); ?>
				</td>
			</tr>
			</table>
		</td>
		<td class="width20"></td>
		<td>
			<table class="executiveReportMenu executiveReportTopMenu" cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td class="width10"><b>2.&nbsp;</b></td>
				<td><b>Choose Month / Year</b><br><br>	

				<img class="imageBottom" src="images/icon_16_left_green.png" onclick="javascript:setExecutiveReportDate(-1);">

				<select name="reportMonth" id="reportMonth">
					<option value="1">Jan</option>
					<option value="2">Feb</option>
					<option value="3">Mar</option>
					<option value="4">Apr</option>
					<option value="5">May</option>
					<option value="6">June</option>
					<option value="7">July</option>
					<option value="8">Aug</option>
					<option value="9">Sep</option>
					<option value="10">Oct</option>
					<option value="11">Nov</option>
					<option value="12">Dec</option>
				</select>
				
				<select name="reportYear" id="reportYear" class="">
					<?php 
					$lastReportYear = (date('n') != 1) ? date('Y') : date('Y')-1;
					for ($i=$lastReportYear; $i>=QR_EXECUTIVE_REPORTS_START_YEAR; $i--) {
						echo '<option value="'.$i.'">'.$i.'</option>';
					}
					?>
				</select>
				<img class="imageBottom" src="images/icon_16_right_green.png" onclick="javascript:setExecutiveReportDate(1);">			
				</td>
			</tr>
			</table>
		</td>
		</tr>
		<tr style="height:10px"><td colspan="3"></td></tr>
		<tr>
		<td colspan="3">
			<table class="executiveReportMenu" cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td class="width10"><b>3.&nbsp;</b></td>
				<td><b>Choose Report Data</b> &nbsp;<a id="selectAllReportsLink" class="selectAllLink" href="javascript:void(0);" onclick="toggleAllCheckboxes('executiveReportCheckbox');">Select All</a></td>
				<td class="width20"></td>
				<td></td>
				<td class="width20"></td>
				<td colspan="2" align="right">
					<?php if (NULL != QR_EXECUTIVE_REPORTS_DESCRIPTION_URL) 
						echo '<a id="executiveReportDefinitionsLink" target="_blank" href="', QR_EXECUTIVE_REPORTS_DESCRIPTION_URL, '">Report Data Definitions</a>'; ?>
				</td>
			</tr>
			<tr style="height:10px"><td colspan="7"></td></tr>
			<tr>
				<td></td>
				<td><table class="executiveReportSubMenu executiveReportSubMenuRow1" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
			
			<?php 
			$newColumn = false;
			$numColumns = 1;
			$currentCategory = NULL;
			$categoryList = array();
			
			foreach ($executiveReportsList as $er) {

				// strip special characters and spaces from category	
				$cleanCategory = preg_replace(array('/[^a-zA-Z0-9_.]/'), array(''), $er->category);
								
				if (NULL == $currentCategory) {		//first row, first column
					echo '<b>',$er->category,'</b> &nbsp;<a id="',$cleanCategory,'SelectAllReportsLink" class="selectAllLink" href="javascript:void(0);" onclick="toggleAllCheckboxes(\'', $cleanCategory,'\');">Select All</a>';
					echo '<br><input class="executiveReportCheckbox ', $cleanCategory, '" type="checkbox" name="', strtolower($er->id),'" id="', strtolower($er->id),'">', $er->name;
					$categoryList[] = $cleanCategory;		
				}
				
				if ($er->category == $currentCategory)
					echo '<br><input class="executiveReportCheckbox ', $cleanCategory, '" type="checkbox" name="', strtolower($er->id),'" id="', strtolower($er->id),'">', $er->name;
				else {	//new category
					if ($er->category != $currentCategory && NULL != $currentCategory) {
						echo '</td></tr></table></td>';						
						$newColumn = true;
						if (QR_EXECUTIVE_REPORTS_MENU_COLUMNS == $numColumns) {
							$numColumns = 1;
							echo '</tr><tr style="height:15px"><td colspan="7"></td></tr><tr>';
						}
						else
							$numColumns++;
					}
					
					if ($newColumn) {
						$newColumn = false;
						echo '<td></td>';
						echo '<td><table class="executiveReportSubMenu executiveReportSubMenuRow1" cellpadding="0" cellspacing="0" border="0">';
						echo '<tr><td>';
						echo '<b>',$er->category,'</b> &nbsp;<a id="',$cleanCategory,'SelectAllReportsLink" class="selectAllLink" href="javascript:void(0);" onclick="toggleAllCheckboxes(\'', $cleanCategory,'\');">Select All</a>';
						echo '<br><input class="executiveReportCheckbox ', $cleanCategory, '" type="checkbox" name="', strtolower($er->id),'" id="', strtolower($er->id),'">', $er->name;
						$categoryList[] = $cleanCategory;			
					}
				}
				$currentCategory = $er->category;
			}
		 	?>
					</td></tr>
					</table>
				</td>
				<td></td>
				<td></td>
				<td></td>
			</tr>			
			</table>			
		</td>
		<td></td>
		<td>
		
		</td>
		</tr>
		
		<tr>
		<td align="center" colspan="7">
			<br>
			<input id="runButton" type="submit" class="submitButton" value="Run Report"> &nbsp;
		</td>
		</tr>
		
		</table>
		</div>
		
		</td>
		</tr>
		</table>
		</form>	
		<br>
		
		<script type="text/javascript">
		setExecutiveReportDate(0);
		document.getElementById("reportForm").onsubmit = function(){
			return submitExecutiveReport(); 
		}

		<?php 
		//set javascript select all toggle array values
		foreach ($categoryList as $cl) {
			echo '
				lastSelectAllState["',$cl,'"] = false;';
		}
		?>
		</script>
<?php 
	}
	
}
?>

