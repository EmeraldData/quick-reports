<?php

class security {
	
	public function __construct() {}
	
	public function sanitizePostVars() {

		foreach ($_POST as $key => $value) { 
			if (is_array($_POST[$key]))
				$_POST[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_REQUIRE_ARRAY);
			else
				$_POST[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		}
	}
	
	public function sanitizeServerQueryString() {
	
		$_SERVER['QUERY_STRING'] = filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_SPECIAL_CHARS);
	}

	public function validateInteger(&$value) {
		if ((NULL != $value) && (0 != $value) && is_numeric($value) && is_int($value * 1)) {
			$value = (int)$value;	//cast as integer. Value is passed by reference so the passed in value will be updated!
			return true;
		}
		else 
			return false;
	}	
	
}
?>
