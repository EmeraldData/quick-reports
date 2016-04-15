<?php
include 'views/admin.view.php';

class adminMenu {
	
	public function __construct($action=NULL) {
		
		if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
		
			new displayMessageView(QR_ADMIN_PERMISSION_REQUIRED_MESSAGE);
			exit;
		}
		
		if (NULL == $action) $this->adminMenu();
	}
	
	public function adminMenu() {	
		$adminView = new adminView();
		$adminView->showAdminMenu();
	}
	
}
?>
