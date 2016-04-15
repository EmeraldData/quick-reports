<?php
include 'views/homePage.view.php';

class homePage {
	
	public function __construct() {
		
		$homePageView = new homePageView();
		$homePageView->showHomePage();
	}
	
}
?>
