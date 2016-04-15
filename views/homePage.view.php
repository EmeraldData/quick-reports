<?php 
class homePageView {

	public function __construct() {}
	
	public function showHomePage() {
?>		
	<center><h2 id="homePageTitle"><?php echo QR_HOME_PAGE_WELCOME_TEXT; ?></h2>	
	<table>
	
	<?php if (!$_SESSION['executiveReportsOnly']) { ?>
	<tr>
		<td><a href="<?php echo QR_SITE_ROOT,QR_LIST_TEMPLATES_PAGE; ?>"><img src="images/icon-new.png" width="50" height="50"></a></td>
		<td>&nbsp;</td>
		<td>
			<a class="homePageLink" href="<?php echo QR_SITE_ROOT,QR_SELECT_TEMPLATE_PAGE; ?>"><b>New Quick Report</b></a><br>
			Create a new report by selecting from a list of possible report templates.
		</td>
	</tr>
	<tr>
		<td><a href="<?php echo QR_SITE_ROOT,QR_LIST_REPORTS_PAGE; ?>"><img src="images/icon-folder.png" width="50" height="50"></a></td>
		<td>&nbsp;</td>
		<td>
			<a class="homePageLink" href="<?php echo QR_SITE_ROOT,QR_LIST_REPORTS_PAGE; ?>"><b>My Quick Reports</b></a><br>
			View reports you have already run, or that you have scheduled.
		</td>
	</tr>
	<tr>
		<td><a href="<?php echo QR_SITE_ROOT,QR_LIST_DRAFT_REPORTS_PAGE; ?>"><img src="images/icon-draft.png" width="50" height="50"></a></td>
		<td>&nbsp;</td>
		<td>
			<a class="homePageLink" href="<?php echo QR_SITE_ROOT,QR_LIST_DRAFT_REPORTS_PAGE; ?>"><b>My Draft Reports</b></a><br>
			List of reports you have begun to create but have not yet scheduled to run.
		</td>
	</tr>
	<?php } ?>
	<tr>
		<td><a href="<?php echo QR_SITE_ROOT,QR_SHOW_EXECUTIVE_REPORTS_MENU_PAGE; ?>"><img src="images/icon-snapshot.png" width="50" height="50"></a></td>
		<td>&nbsp;</td>
		<td>
			<a class="homePageLink" href="<?php echo QR_SITE_ROOT,QR_SHOW_EXECUTIVE_REPORTS_MENU_PAGE; ?>"><b>Executive Snapshot Reports</b></a><br>
			Create an executive report.
		</td>
	</tr>
	<tr>
		<td><a href="<?php echo QR_LOGOUT_PAGE; ?>"><img src="images/icon-exit.png" width="50" height="50"></a></td>
		<td>&nbsp;</td>
		<td>
			<a class="homePageLink" href="<?php echo QR_LOGOUT_PAGE; ?>"><b>Log Out</b></a>
		</td>
	</tr>					
	</table>
	</center>
<?php 
	}	
}
?>
