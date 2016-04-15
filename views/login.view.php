<?php 
class loginView {

	public function __construct() {}
	
	public function displayLoginForm($message=NULL, $successMessage=false) {
			?>
			<div id="loginDiv" style="padding-left:25px;">
			<?php echo QR_LOGIN_PAGE_WELCOME_TEXT; ?><br><br>
			<form id="form1" name="form1" method="post" action="<?php echo QR_SITE_ROOT.QR_LOGIN_PAGE; ?>">
			<table>
				<tr><td colspan="2">
					<?php 
					if (NULL != $message)
						echo '<span class=',($successMessage)?'"successText"':'"errorText"','>', $message,'</span>'; 
					?> 
					&nbsp;</td>
				</tr>
				<tr><td colspan="2"><b>Authentication</b></td></tr>
				<tr><td width="70">Username:</td><td><input name="username" id="username" type="text" size="25"></td></tr>
				<tr><td width="70">Password:</td><td><input name="password" id="password" type="password" size="25" maxlength="25"></td></tr>
				<tr><td></td><td><input type="submit" value="Login"></td></tr>
			</table>
			</form>
			</div>
			
			<script type="text/javascript">
			document.getElementById("form1").onsubmit = function(){
				var uname=document.getElementById("username").value;	
				var pwd=document.getElementById("password").value;
				if (emptyString(uname) || emptyString(pwd)) {
					alert("Please enter a username and password.");	
					document.getElementById("username").focus();
					return false;
				}
				else return true;
			};
			window.onload=function(){
				document.getElementById("username").focus();
			};	
			</script>
<?php 
	}
}	
?>
	