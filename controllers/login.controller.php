<?php
include 'views/login.view.php';

class login {

	public function __construct($params=NULL) {
		
		if (isset($_POST['username']) && isset($_POST['password'])) {
			//user is attempting to login
			include 'models/db.class.php';
			include 'models/user.class.php';
		
			$user=new user();
			
			if ($user->validateUserCredentials($_POST['username'], $_POST['password']) && $user->validateUserPermissions($user->userID, $user->profile)) {
				//user is authenticated - establish a new session object and update the PHP $_SESSION vars
				$user->isLoggedIn=true;
				$session = (isset($params['session'])) ? $params['session'] : NULL;
				if (NULL == $session) die('Invalid user session');
				session_regenerate_id(true);
				$session->createUserSession($user);
				header('Location: '.QR_HOME_PAGE);
				exit;
			}
			else {
				$user->isLoggedIn=false;
			}
		}

		if (isset($user) && !$user->isLoggedIn)
			$messageNumber = $user->loginFailureCode;
		else	
			$messageNumber = (isset($params['messageNumber'])) ? $params['messageNumber'] : NULL;
		
		switch ($messageNumber) {
			case PINS_LOGOUT_EXPLICIT:
				$message = 'You have successfully logged out.';
				$successMessage = true;
				break;

			case QR_LOGOUT_DUE_TO_INACTIVITY:
				$message = 'Your session has expired due to inactivity. Please login again.';
				$successMessage = false;
				break;
			
			case QR_INVALID_LOGIN_ATTEMPT:
				$message = 'Invalid Login. Please try again.';
				$successMessage = false;
				break;

			case QR_MAX_LOGIN_ATTEMPTS_REACHED:
				$message = 'Invalid Login. Please contact an administrator for assistance.';
				$successMessage = false;
				break;

			case QR_INSUFFICIENT_PERMISSIONS:
				$message = 'You do not have sufficient permissions. Please contact an administrator for assistance.';
				$successMessage = false;
				break;
					
			case QR_NOT_LOGGED_IN:
				$message = 'Please log in.';
				$successMessage = true;
				break;
											
			default:
				$message = NULL;
				$successMessage = NULL;
				unset($_SESSION['failedLoginAttempts']);
				break;
		}
		
		//Display the login screen and any messages.
		$loginView = new loginView();
		$login = $loginView->displayLoginForm($message, $successMessage);
	}	

}
?>
