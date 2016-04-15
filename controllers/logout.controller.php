<?php
class logout {
	
	public function __construct($params=NULL) {
		
		$session = $params;
		if (NULL != $session) //valid session object 
			$session->logoutUser();
		else {	//ensure this session is destroyed
			if (isset($_SESSION)) unset($_SESSION);
			if (isset($_SESSION)) session_destroy();
			session_regenerate_id(true);
		}
		
		if (isset($session->redirectPage))
			header('Location: '.$session->redirectPage);
		else
			header('Location: '.QR_HOME_PAGE);	
		exit;
	}
}
?>