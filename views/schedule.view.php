<?php 
class scheduleView {

	public function __construct() {}
	
	public function showScheduledReportsList($list, $allowAdminView=false) {
		
		if (NULL == $list) {
			new displayMessageView('No reports found.');
			exit;
		}
			
		$group = $list[0]->group;	//first row
		$firstRow = true;
		$firstQueued = false;
		$firstRunning = false;
		$firstCompleted = false;
		$dividerShown = false;
		
		if ($allowAdminView)
			$sectionHeader = '<td class="defaultTD"><b>Run Date</b></td><td class="defaultTD"><b>Owner</b></td><td class="defaultTD"><b>Report Name</b></td><td class="defaultTD"><b>Template Name</b></td><td class="defaultTD"><b>Recurs</b></td><td class="defaultTD"><b>&nbsp;Recipient&nbsp;</b></td><td class="defaultTD"><b>Actions</b></td></tr>';
		else
			$sectionHeader = '<td class="defaultTD"><b>Run Date</b></td><td class="defaultTD"><b>Report Name</b></td><td class="defaultTD"><b>Description&nbsp;</b></td><td class="defaultTD"><b>Template Name</b></td><td class="defaultTD"><b>Recurs</b></td><td class="defaultTD"><b>Actions</b></td></tr>';

		if ($allowAdminView) echo '&nbsp;<span id="adminPageTitle">Manage Reports</span>';
		echo '<table>';
					
		foreach ($list as $row) {
			if ($firstRow || $group != $row->group) {
				echo '<tr><td colspan="2"><br><a class="groupLink" href="javascript:void(0);" onclick="toggleCollapsibleSection(\''.$row->group.'\')"><span id="'.$row->group.'_plus">+</span>&nbsp;',$row->group, '</a></td><td colspan="10"></td></tr>';
				echo '<tr class="'.$row->group.'_row hideRow"><td></td>',$sectionHeader;
				$group = $row->group;
				if ($firstRow) {
					$firstRow = false;
				}
				else {
					//reset row separator bar for new section
					$firstQueued = false;
					$firstRunning = false;
					$firstCompleted = false;
					$dividerShown = false;
				}
			}
						
			$isQueued = ($row->is_queued);
			$isRunning = ($row->is_running);
			$isCompleted = ($row->is_complete);
			
			if ($isCompleted && !$firstCompleted)
				$firstCompleted = true;
			elseif ($isQueued && !$firstQueued)
				$firstQueued =  true;
			elseif ($isRunning && !$firstRunning)
				$firstRunning =  true;
			
			if (!$dividerShown && $firstCompleted)
				$showSeparator = true;
			else
				$showSeparator = false;		
			
			if ($showSeparator) {
				//add row separator before completed reports
				$dividerShown = true;
				echo '
					<tr class="'.$row->group.'_row hideRow" style="height:6px;"><td colspan="10"></td></tr>
					<tr class="'.$row->group.'_row hideRow"><td class="reportStatusBar" colspan="7">Completed Reports</td></tr>';
			}
						
			echo '<tr class="'.$row->group.'_row hideRow"><td>&nbsp;</td>';
			echo '<td class="defaultTD">',(($isRunning)?'<span class="reportRunning">&nbsp;Running&nbsp;</span>':date('m/d/Y',strtotime($row->run_time)));
			if ($allowAdminView) echo '</td><td class="defaultTD">',$row->usrname;
			if ($isCompleted && $row->error_code == '')
				echo '</td><td class="defaultTD maxWidth300"><a class="defaultLink" target="_blank" href="'.QR_REPORT_OUTPUT_URL_PREFIX.$row->tid.'/'.$row->rid.'/'.$row->sid.QR_REPORT_OUTPUT_URL_SUFFIX.'">',$row->name,'</a>';
			else {	
				echo '</td><td class="defaultTD maxWidth300">',$row->name;
				if ($row->error_code != '') echo '<br><a class="defaultLink" href="javascript:void(0);" onclick=\'javascript:alert("',preg_replace(unserialize(QR_DEFAULT_ESCAPE_PATTERNS), unserialize(QR_DEFAULT_ESCAPE_REPLACEMENTS), $row->error_text),'");\'><span class="errorText">Error</span></a>';
			}
			
			if (!$allowAdminView) echo '</td><td class="defaultTD maxWidth300" >',$row->description;
			echo '</td><td class="defaultTD maxWidth300">',$row->templatename;
			echo '</td><td class="defaultTD">',($row->recur==1)?$row->recurrence:'','</td><td class="nowrap defaultTD">';
				
			if ($allowAdminView) echo $row->email,'</td><td class="nowrap defaultTD">';
			if ($isQueued) {
				echo '<img alt="Cancel Report Icon" class="imageBottom" src="images/icon_16_cancel.png">&nbsp;<a class="defaultLink" href="javascript:void(0);" onclick="return confirmDeleteReport(',$row->sid,',\'',$row->name,'\',\'Cancel\',',($allowAdminView?'true,':'false,'),(($row->recur==1)?'true':'false'),',\'',QR_SITE_ROOT.QR_CANCEL_REPORT_PAGE,'\');">Cancel Report</a><br>';
				echo '<img alt="Edit Report Icon" class="imageBottom" src="images/icon_16_pencil.png">&nbsp;<a class="defaultLink" href="',QR_SITE_ROOT,($allowAdminView?QR_ADMIN_QR_EDIT_REPORT_PAGE:QR_EDIT_REPORT_PAGE),$row->sid,'/">Edit Report</a></td></tr>';
			}
			elseif ($isCompleted) {
				echo '<img alt="Run Again Icon" class="imageBottom" width="15" height="15" src="images/icon_16_redo.png">&nbsp;<a class="defaultLink" href="',QR_SITE_ROOT,(($allowAdminView)?QR_ADMIN_QR_EDIT_REPORT_PAGE:QR_EDIT_REPORT_PAGE),$row->sid,'/">Run Again</a><br>';
				echo '<img alt="Delete Report Icon" class="imageBottom" width="15" height="15" src="images/icon_16_delete.png">&nbsp;<a class="defaultLink" href="javascript:void(0);" onclick="return confirmDeleteReport(',$row->sid,',\'',$row->name,'\',\'Delete\',',($allowAdminView?'true,':'false,'),'0,\'',QR_SITE_ROOT.QR_CANCEL_REPORT_PAGE,'\');">Delete Report</a></td></tr>';
			}
			
		}
		echo '</table><br>';
	}

}
?>
