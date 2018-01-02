<?php

class session {

	public $isNotLoggedIn;
	public $isSessionExpired;
	public $redirectPage;
	private $sessionCookieExists;
	
	public function __construct($allowNotLoggedIn=false, $page=NULL) {
		
		$this->validateUserSession($allowNotLoggedIn, $page);
	}
	
	public function validateUserSession($allowNotLoggedIn=false, $page=NULL) {
		
		//setup the PHP session
		session_cache_expire(QR_BROWSER_PAGE_CACHE_TIMEOUT);	//browser page cache time in seconds
		$timeoutSeconds=60 * QR_SESSION_TIMEOUT;  		//expire duration in seconds
		ini_set('session.gc_maxlifetime', $timeoutSeconds);

		if (!isset($_SESSION)) {
			if (QR_SESSIONS_IN_MEMCACHE) {
				ini_set('session.save_handler', 'memcache');
				ini_set('session.save_path', QR_MEMCACHE_PROTOCOL_1.'://'.QR_MEMCACHE_HOST_1.':'.QR_MEMCACHE_PORT_1.QR_MEMCACHE_PARAMS_1.((defined('QR_MEMCACHE_HOST_2') ? ', '.QR_MEMCACHE_PROTOCOL_2.'://'.QR_MEMCACHE_HOST_2.':'.QR_MEMCACHE_PORT_2.QR_MEMCACHE_PARAMS_2 : '')) .'  ');
			}
			
			//force php to throw an error on notices
			set_error_handler(function($errno, $errstr) {
				echo '<br><br><br><br><br>Error. Failed to start session.<br>',$errstr;
				die;
			});
			
			//check if session cookie exists
			$verID='3fca9f8aa945f04e090b258b2fcf10e4';
			$sessionName = session_name(); 
			$this->sessionCookieExists = isset($_COOKIE[$sessionName]) ? true : false;

			if (substr(phpversion(), 0, 1) == '7') {
			    //PHP7 workaround for memcache
			    include 'memcacheSession.class.php';
			    $mcSession = new MemcachedSession();
			    session_set_save_handler($mcSession, true);
			}
			
			@session_start();
			restore_error_handler();
		}
	
		if(!$allowNotLoggedIn && (!isset($_SESSION['lastAccessTime']) || isset($_SESSION['lastAccessTime']) && (time() - $_SESSION['lastAccessTime'] > $timeoutSeconds))) {
			$this->isSessionExpired = true;
		}

		$_SESSION['lastAccessTime'] = time();		
		
		//check if the session is still valid on pages that require user to be logged in (e.g.) not the login page
		if (! $allowNotLoggedIn) {
			$this->validateLoginStatus(false, $page);
		}
	}
	

	public function validateLoginStatus($forceLogout=false, $page=NULL) {
		
		if ($forceLogout) {
			//user logged out
			$this->isNotLoggedIn = true;
			$this->redirectPage = QR_SITE_ROOT.QR_LOGIN_PAGE.PINS_LOGOUT_EXPLICIT.'/';
			$this->endUserSession();
		}
		elseif ($this->isSessionExpired) {	
			//logged out due to inactivity
			$this->isNotLoggedIn = true;
			
			if ($this->sessionCookieExists)
				$this->redirectPage = QR_SITE_ROOT.QR_LOGIN_PAGE.QR_LOGOUT_DUE_TO_INACTIVITY.'/';
			else	
				$this->redirectPage = QR_SITE_ROOT.QR_LOGIN_PAGE.QR_NOT_LOGGED_IN.'/';
			$this->endUserSession();
		}
		elseif (!isset($_SESSION['userID'])) {
			//not logged in
			$this->isNotLoggedIn = true;
			if ($page == '')
				$this->redirectPage = QR_SITE_ROOT.QR_LOGIN_PAGE;
			else
				$this->redirectPage = QR_SITE_ROOT.QR_LOGIN_PAGE.QR_NOT_LOGGED_IN.'/';
			
			$this->endUserSession();
		}
	}
	
	public function createUserSession($user) {	
		
		foreach ($user as $key => $value) $_SESSION[$key] = $value;
	}
		
	public function logoutUser(){
		
		$this->endUserSession();
		$this->validateLoginStatus(true);
	}
	
	
	private function endUserSession() {
		
		if (isset($_SESSION)) unset($_SESSION);
		if (isset($_SESSION)) session_destroy();
		setcookie(session_name()," ",NULL,'/');
	}
	
    function __destruct() {}

}
?>
