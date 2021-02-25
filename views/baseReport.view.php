<?php 
class baseReportView {

	protected $defaultValues;
	
	public function __construct() {}
	
	protected function createOrgUnitSelect($list, $name, $defaultTD=true) {
		$index = 0;
		$firstSelected = NULL;
		$selectedValues = '';
		
		$select ='<table class="noSpacing" cellpadding="0" cellspacing="0" border="0"><tr><td class="noPadding">';
		$select.='<select multiple class="orgunitSelect userSelect userInput" name="'.$name.'[]" id="'.$name.'" onchange="showSelectChoices(this,\''.$name.'_selected\');">';
		foreach ($list as $values) {
			switch ($values->ou_type) {
				case 3:  $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; break;
				case 2:  $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;'; break;
				default: $spaces = '&nbsp;'; break;
			}

			$valueCount = false;
			if ( isset( $this->defaultValues ) && isset ( $values ) ){
				if ((isset($this->defaultValues->paramsDecoded->$name) && in_array($values->id, $this->defaultValues->paramsDecoded->$name))
				|| (count($this->defaultValues)==0 && $values->id==$_SESSION['homeOU'])) {
				$selected = ' selected ';
				$selectedValues .= $values->shortname.'&nbsp;&nbsp;<br>';
					if (NULL == $firstSelected) $firstSelected = $index;
				}
				else {
					$selected = '';
				}
			}
		
			$select .= '<option '.$selected.' value="'.$values->id.'">'.$spaces.$values->shortname.'</option>';
			$index++;
		}
		$select .= '</select></td></tr><tr><td class="noPadding" align="right"><a class="selectAllLink" href="javascript:void(0);" onclick="selectAllMultiselect(\''.$name.'\');">Select All</a></td></tr></table>';

		//scroll to the first selected element
		if (NULL != $firstSelected) {
			$select .= '
				<script type="text/javascript">
				document.getElementById("'.$name.'")['.$firstSelected.'].selected = false;
				document.getElementById("'.$name.'")['.$firstSelected.'].focus();
				document.getElementById("'.$name.'")['.$firstSelected.'].selected = true;
				</script>
				';
		}
		
		$select .= '</td><td '.(($defaultTD)?'class="defaultTD"':'').'><b>Selected:</b><br><div class="orgunitSelectedDiv" id="'.$name.'_selected">'.$selectedValues.'</div>';
		return $select;
	}
	
	protected function createMultiSelectFromObject($list, $name) {
		$index = 0;
		$firstSelected = NULL;
		$selectedValues = '';
	
		$select ='<table class="noSpacing" cellpadding="0" cellspacing="0" border="0"><tr><td class="noPadding">';
		$select.='<select multiple class="userSelect userMultiSelect userInput" name="'.$name.'[]" id="'.$name.'" onchange="showSelectChoices(this,\''.$name.'_selected\');">';
		foreach ($list as $values) {
			if (isset($this->defaultValues->paramsDecoded->$name) && in_array($values->id, $this->defaultValues->paramsDecoded->$name)) {
				$selected = ' selected ';
				$selectedValues .= $values->name.'&nbsp;&nbsp;<br>';
				if (NULL == $firstSelected) $firstSelected = $index;
			}
			else {
				$selected = '';
			}
			$quote = (strpos($values->id, '"') === NULL) ? '"' : "'";
			$select .= '<option '.$selected.'value='.$quote.$values->id.$quote.'>'.$values->name.'</option>';
			$index++;
		}
		$select .= '</select></td></tr><tr><td class="noPadding" align="right"><a class="selectAllLink" href="javascript:void(0);" onclick="selectAllMultiselect(\''.$name.'\');">Select All</a></td></tr></table>';
	
		//scroll to the first selected element
		if (NULL != $firstSelected) {
			//needed in IE to force scroll to first selected option
			$select .= '
				<script type="text/javascript">
				document.getElementById("'.$name.'")['.$firstSelected.'].selected = false;
				document.getElementById("'.$name.'")['.$firstSelected.'].focus();
				document.getElementById("'.$name.'")['.$firstSelected.'].selected = true;
				</script>
				';
		}
	
		$select .= '</td><td class="defaultTD"><b>Selected:</b><div class="userMultiSelectedDiv" id="'.$name.'_selected">'.$selectedValues.'</div>';
		return $select;
	}

	protected function createSelectFromObject($list, $name) {
		$select='<select class="userSelect userInput" name="'.$name.'" id="'.$name.'">';
		foreach ($list as $values) $select.='<option '.((isset($this->defaultValues->paramsDecoded->$name) && $this->defaultValues->paramsDecoded->$name==$values->id)?' selected ':'').'value="'.$values->id.'">'.$values->name.'</option>';
		return $select.'</select></td>';
	}
	
}
?>
