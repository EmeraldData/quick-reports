<?php 
class adminView {

	public function __construct() {}
	
	public function showAdminMenu() {
		
		echo '
		<center>
		<br><br>
		<table>
		<tr><td align="center"><b>Admin Menu</b></td></tr>		
		<tr><td><a class="adminMenuItem" href="',QR_SITE_ROOT,QR_LINK_TEMPLATE_PAGE,'">Link a Template</a></td></tr>
		<tr><td><a class="adminMenuItem" href="',QR_SITE_ROOT,QR_LIST_TEMPLATES_PAGE,'admin/">Manage Templates</a></td></tr>
		<tr><td><a class="adminMenuItem" href="',QR_SITE_ROOT,QR_LIST_REPORTS_PAGE,'queue/">Manage Reports</a></td></tr>
		</table>		
		</center>';
		
	}	
}
?>
