<?php 
class pageView {

	public function __construct() {
		$this->displayHeader();
	}
	
	
	private function displayHeader() {
	?>
	
	<!doctype html>
	<html>
	<head>
		<meta charset="utf-8">
		<title><?php echo QR_DEFAULT_TITLE_TAG_TEXT; ?></title>
	
		<link type="text/css" href="css/site_v1.6.css" rel="stylesheet">
		<?php if (QR_EXECUTIVE_REPORTS_ENABLED)	echo '<link type="text/css" href="css/executiveReportMenu_v1.0.css" rel="stylesheet">'; ?>
		
		<script type="text/JavaScript" src="lib/site_v1.6.js"></script> 
	</head>
	<body>
	<div id="page">
		<div id="header">
	        	<div id="logo">
					<a href="<?php echo QR_SITE_ROOT; ?>"><span id="logoImage"></span><span id="logoText"><?php echo QR_DEFAULT_LOGO_TEXT; ?></span></a>	
					<span id="headerRight">
					</span>
	<?php 
	}
	
	
	public function displayWelcomeMessage() {
					echo '
					<span id="welcome">';
					if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']) 
						echo 'Welcome, ',$_SESSION['firstName'],' ',$_SESSION['lastName'],'<br><a id="logoutLink" href="',QR_LOGOUT_PAGE.'">Log Out</a>'; 
	
					echo '
					</span> 	
				</div><!-- logo -->
		</div><!-- header -->';
	}
	
	
	public function displayMenu($menuItemSelected=NULL) { 
		
		echo '
		<div id="content">
			<div id="menu">';
	
			if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']) {
				echo 
				'<a class="menuItemLink" href="',QR_SITE_ROOT,'">',
				'<span class=',($menuItemSelected==QR_MENU_HOME)?'"menuItemSelected"':'"menuItem"','> Home </span></a>&nbsp;|&nbsp;';
	
				if (!$_SESSION['executiveReportsOnly']) {
					echo
					'<a class="menuItemLink" href="',QR_SITE_ROOT,QR_LIST_TEMPLATES_PAGE,'">',
					'<span class=',($menuItemSelected==QR_MENU_TEMPLATES)?'"menuItemSelected"':'"menuItem"','> Quick Report Templates </span></a>&nbsp;|&nbsp;',
					
					'<a class="menuItemLink" href="',QR_SITE_ROOT,QR_LIST_REPORTS_PAGE,'">',
					'<span class=',($menuItemSelected==QR_MENU_REPORTS)?'"menuItemSelected"':'"menuItem"','> My Quick Reports </span></a>&nbsp;|&nbsp;',
					
					'<a class="menuItemLink" href="',QR_SITE_ROOT,QR_LIST_DRAFT_REPORTS_PAGE,'">',
					'<span class=',($menuItemSelected==QR_MENU_DRAFT_REPORTS)?'"menuItemSelected"':'"menuItem"','> My Draft Reports </span></a>';
					if (defined('QR_EXECUTIVE_REPORTS_ENABLED') && QR_EXECUTIVE_REPORTS_ENABLED) echo '&nbsp;|&nbsp;';			
				}
	
				if (defined('QR_EXECUTIVE_REPORTS_ENABLED') && QR_EXECUTIVE_REPORTS_ENABLED) {
					echo
					'<a class="menuItemLink" href="',QR_SITE_ROOT,QR_SHOW_EXECUTIVE_REPORTS_MENU_PAGE,'">',
					'<span class=',($menuItemSelected==QR_MENU_EXECUTIVE)?'"menuItemSelected"':'"menuItem"','> Executive Snapshot </span></a>';
				}
							
				if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
					echo
					'<a class="menuItemLink" href="',QR_SITE_ROOT,QR_ADMIN_MENU_PAGE,'">',
					'<span class="rightMenuItem ',($menuItemSelected==QR_MENU_ADMIN)?'menuItemSelected"':'menuItem"','>&nbsp;Admin Menu&nbsp;</span></a>';
				}
			}
	
			echo ' 
			</div><!-- menuItems -->';
	}	
	
	
	public function displayFooter() {
	?>
		</div><!-- content -->
	</div><!-- page -->
	
	<div id="footer">
	<br>  
	<?php 
		echo '&#64; 2015-',date('Y');
		echo ' Georgia Public Library Service, a Unit of the University System of Georgia.<br>', QR_PAGE_FOOTER_TEXT;
	?>		
	<br><br>
	</div>
		
	<?php 
	$verID='3fca9f8aa945f04e090b258b2fcf10e4';
	echo "<!--$verID-->
	
	</body>
	</html>
	";
	
	}

}
?>
