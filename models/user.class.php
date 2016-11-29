<?php 
class user extends db
{

	public $userID;
	public $firstName;
	public $lastName;
	public $email;
	public $isAdmin;
	public $isLoggedIn;
	public $executiveReportsOnly;
	public $loginFailureCode;
		
	public function __construct() {
		
		parent::__construct();			
	}
		
	function validateUserCredentials($userName, $password) {
		
		try {
			$query = 'select id, home_ou, profile, first_given_name, family_name, email from actor.usr usr where usrname=:usrname and (actor.verify_passwd(usr.id, ''main'', md5((select salt from actor.passwd where usr = usr.id) || md5(:passwd)))) and active=true';
			$params = array("usrname"=>$userName, "passwd"=>$password);
			$user = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
		
		if (NULL == $user) {	
			$this->isLoggedIn = false;
			if (!isset($_SESSION['failedLoginAttempts'])) $_SESSION['failedLoginAttempts'] = 1; 
			if ($_SESSION['failedLoginAttempts'] < QR_MAX_LOGIN_ATTEMPTS_ALLOWED) {
				$this->loginFailureCode = QR_INVALID_LOGIN_ATTEMPT;	
				$_SESSION['failedLoginAttempts']++;
			}
			else {
				//$_SESSION['failedLoginAttempts'] = 1;	//start counter over
				$this->loginFailureCode = QR_MAX_LOGIN_ATTEMPTS_REACHED;
			}	

			return false;
		}

		$this->firstName = $user->first_given_name;
		$this->lastName = $user->family_name;
		$this->userID = $user->id;
		$this->homeOU = $user->home_ou;
		$this->profile = $user->profile;
		$this->email = $user->email;
		$this->isLoggedIn=true;
		
		return true;	//User successfully logged in.	
	}
	
	
	function getUserPermissions($userID, $permissionList) {
		
		try {
			$query = "select count(*) as cnt from permission.usr_perm_map where usr=:usr and perm in ($permissionList)";
			$params = array("usr"=>$userID);
			$userPermission = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
				
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}		
		
		return ($userPermission->cnt > 0);
	}

	
	function getUserExecutiveReportsOnlyPermission($userID, $permissionList) {
		try {
			$query = "select count(*) as cnt from permission.usr_perm_map where usr=:usr and perm in ($permissionList)";
			$params = array("usr"=>$userID);
			$userPermission = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
		
		return ($userPermission->cnt > 0);
	}
	
	
	function getGroupPermissions($userID, $profile, $permissionList) {
		
		if (!QR_ALLOW_GROUP_PERMISSIONS) return false;
		
		try {
			$query = "
				select count(*) as cnt from permission.usr_grp_map
				right join permission.grp_perm_map on permission.usr_grp_map.grp=permission.grp_perm_map.grp
				where perm in ($permissionList) and (usr=:usr";
			if (is_int($profile)) $query.= ' or  permission.grp_perm_map.grp=:profile';	//user's profile is a group membership
			$query .= ')';

			$params = array("usr"=>$userID);
			if (is_int($profile)) $params["profile"]=$profile;
	
			$groupPermission = $this->executeQuery($query, $params, QR_QUERY_RETURN_ONE_ROW);
		}
		catch (PDOException $e) {
			$this->handleDatabaseErrors($e);
		}
	
		return ($groupPermission->cnt > 0);
	}
	
	
	function validateUserPermissions($userID, $profile) {
		
		//assume full permissions
		$this->executiveReportsOnly = false;
		
		//User has permissions. Check if user is also an admin.
		if ($this->getUserPermissions($userID, QR_ADMINS_ALLOWED_PERMISSIONS) || $this->getGroupPermissions($userID, $profile, QR_ADMINS_ALLOWED_PERMISSIONS)) {	
			$this->isAdmin = true;
			return true;	//user has permission	
		}

		//not an admin, check if regular user
		$this->isAdmin = false;
		
		//check for indiviual and group permissions.
		if ($this->getUserPermissions($userID, QR_USERS_ALLOWED_PERMISSIONS) || $this->getGroupPermissions($userID, $profile, QR_USERS_ALLOWED_PERMISSIONS)) {
			return true;	//user has permission
		}
		
		//check if they can run executive reports only
		if (QR_EXECUTIVE_REPORTS_ENABLED) {
			if ($this->getUserExecutiveReportsOnlyPermission($userID, QR_EXECUTIVE_REPORTS_ADDITIONAL_ALLOWED_PERMISSIONS)) {
				$this->executiveReportsOnly = true;
				return true;	//user has permission
			}
		}
		
		//user does not have permissions
		$this->loginFailureCode = QR_INSUFFICIENT_PERMISSIONS;
		$this->isLoggedIn = false;
		return false;
	}
	
}	
?>
