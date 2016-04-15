<?php 
class templateView {

	public function __construct() {}
	
	public function showTemplateList($list, $allowAdminView=false, $showSelectMessage=false) {
		
		if (NULL == $list) {
			new displayMessageView('No templates found.');
			exit;
		}
		
		$group = $list[0]->group;	//first row
		$firstRow = true;

		if ($allowAdminView) 
			$sectionHeader = '<td class="defaultTD"><b>ID</b></td><td class="defaultTD"><b>Active</b></td><td class="defaultTD"><b>Linked By</b></td><td class="defaultTD"><b>Linked On</b></td><td class="defaultTD"><b>Type</b></td><td class="defaultTD"><b>Template Name</b></td><td class="defaultTD"><b>&nbsp;Description&nbsp;</b></td><td class="defaultTD"><b>Actions</b></td></tr>';
		else 
			$sectionHeader = '<td class="defaultTD"><b>Type</b></td><td class="defaultTD"><b>Template Name</b></td><td class="defaultTD"><b>&nbsp;Description&nbsp;</b></td><td class="defaultTD"><b>Actions</b></td></tr>';
		
		if ($showSelectMessage) 
			new displayMessageView('&nbsp;Please select a template.', true);
		elseif ($allowAdminView) 
			echo '&nbsp;<span id="adminPageTitle">Manage Templates</span>';
		echo '<table>';
					
		foreach ($list as $row) {
			if ($firstRow || $group != $row->group) {								
				echo '<tr><td colspan="2"><br><a class="groupLink" href="javascript:void(0);" onclick="toggleCollapsibleSection(\''.$row->group.'\')"><span id="'.$row->group.'_plus">+</span>&nbsp;',$row->group, '</a></td><td colspan="10"></td></tr><tr class="'.$row->group.'_row hideRow"><td></td>',$sectionHeader;
				$group = $row->group;
				$firstRow = false;
			}

			echo '<tr class="'.$row->group.'_row hideRow"><td>&nbsp;</td>';
			if ($allowAdminView) {
				echo 
				'<td class="defaultTD">',$row->reporter_template_id,'</td><td class="defaultTD">',($row->active)?'Yes':'No',
				'</td><td class="defaultTD">',($row->usrname == '')?'user id# '.$row->creator:$row->usrname,
				'</td><td class="defaultTD">',date('m/d/Y', strtotime($row->create_time));
			}
			echo 
				'</td><td class="defaultTD">',$row->type,
				'</td><td class="defaultTD maxWidth300">',$row->name,
				'</td><td class="defaultTD">&nbsp;<a class="defaultLink" href="javascript:void(0);" onclick=\'javascript:openWindow	("',preg_replace(unserialize(QR_DEFAULT_ESCAPE_PATTERNS), unserialize(QR_DEFAULT_ESCAPE_REPLACEMENTS), $row->description),'");\'>Description</a>';
			if (!empty($row->doc_url)) echo '<br>&nbsp<a class="defaultLink" target="_blank" href="',$row->doc_url,'">Documentation</a>'; 
			echo '</td><td class="nowrap defaultTD">';
				
			if ($allowAdminView) 
				echo '<img alt="Edit Template Icon" width="15" height="15" src="images/icon_16_pencil.png" class="imageBottom">&nbsp;<a class="defaultLink" href="',QR_SITE_ROOT,QR_EDIT_TEMPLATE_PAGE,$row->id,'/">Edit Template</a></td></tr>';
			else
				echo '<img alt="Create Report Icon" width="15" height="15" src="images/icon_16_add.png" class="imageBottom">&nbsp;<a class="defaultLink" href="',QR_SITE_ROOT,QR_CREATE_REPORT_PAGE,$row->id,'/">Create a Report</a></td></tr>';									
			
		}
		echo '</table><br>';
	}


	public function displayLinkTemplateIDForm() {
			
			?>
			<br>
			Import a Template - Step 1 of 2
			<hr width="300" align="left">
			<form id="form1" name="form1" method="post" action="<?php echo QR_SITE_ROOT.QR_LINK_TEMPLATE_PAGE; ?>">
				<table>
					<tr>
						<td>
							Template ID:</td><td><input name="tid" id="tid" type="text" size="5" maxlength="6"> &nbsp;
							<input type="submit" value="Lookup">
						</td>
					</tr>
				</table>
			</form>
			<br><br>
			
			<script type="text/javascript">
			document.getElementById("form1").onsubmit = function(){
				var n=document.getElementById("tid").value;	
				if (!validateInteger(n)) {
					alert("Please enter an integer.");	 
					return false;
				}
				else return true;
			}
			</script>
			<?php 
	}
		
	public function displayTemplateInfoForm($template, $typeListData, $groupListData, $formType='link') {

		$typeListSelected = isset($template->type_id) ? $template->type_id : NULL;
		$groupListSelected = isset($template->group_id) ? $template->group_id : NULL;

		if ($formType == 'link') {
			echo 'Import a Template - Step 2 of 2';			
		}
		?>
		
		<hr width="600" align="left">
		<form name="form2" id="form2" method="post" action="<?php echo QR_SITE_ROOT,($formType=='link'?QR_LINK_TEMPLATE_PAGE:QR_EDIT_TEMPLATE_PAGE); ?>">
		<input name="tid" id="tid" type="hidden" value="<?php echo $template->id; ?>">
		<table>
		<?php if ($formType != 'link') { ?>
		<tr><td>Template ID:</td><td><?php echo $template->id; ?></td></tr>
		<tr><td></td><td>Linked by <?php echo ($template->linked_by)==''?'id# '.$template->creator:$template->linked_by, ' on ', date('m/d/Y \a\t h:i:s A', strtotime($template->linked_time)); ?></td></tr>
		<?php } ?>
		<tr><td>Linked Template ID:</td><td><?php echo $template->reporter_template_id; ?></td></tr>
		<tr><td></td><td>Created by <?php echo ($template->usrname)==''?'id# '.$template->owner:$template->usrname, ' on ', date('m/d/Y \a\t h:i:s A', strtotime($template->create_time)); ?></td></tr>
		<tr><td>Name:</td><td><input class="inputName" name="templateName" id="templateName" type="text" maxlength="100" value="<?php echo $template->name; ?>"></td></tr>
		<tr><td>Description:</td><td><Textarea class="descriptionTextArea" name="description" id="description"><?php echo $template->description; ?></Textarea></td></tr>
		<tr><td>Documentation URL:</td><td><input class="inputName" name="docURL" id="docURL" type="text" maxlength="100" value="<?php echo $template->doc_url; ?>"></td></tr>
		<tr><td>Type:</td><td><?php echo $this->createSimpleSelectFromObject($typeListData, 'typeList', $typeListSelected); ?></td></tr>
		<tr><td>Group:</td><td><?php echo $this->createSimpleSelectFromObject($groupListData, 'groupList', $groupListSelected); ?></td></tr>
		<tr><td>Active:</td><td><select name="active" id="active"><option value="1" <?php echo (isset($template->active) && $template->active==1)?' selected ':'';?>>Yes</option><option value="0" <?php echo (isset($template->active) && $template->active!=1)?' selected ':'';?>>No</option></select></td></tr>
		<tr><td></td><td><br><input type="submit" value="<?php echo ($formType=='link')?'Link':'Save';?> Template"></td></tr>
		</table>
		</form>
		<?php 
	}
	
	private function createSimpleSelectFromObject($list, $name, $selected=NULL){
		$select='<select name="'.$name.'" id="'.$name.'">';
		foreach ($list as $values) $select.='<option '.(($values->id==$selected)?'selected':'').' value="'.$values->id.'">'.$values->name.'</option>';
		return $select.'</select>';
	}
	
}
?>
