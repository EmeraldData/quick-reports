<?php 
class displayMessageView {

	public function __construct($message, $successMessage=false) {
		
			if (NULL != $message)
				echo '<br><span class=',($successMessage)?'"successText"':'"errorText"','>',$message,'</span><br>';
			else
				echo '<br><br>';
		
	}
	
}	
?>
	