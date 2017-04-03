<?php
include ('sess_start.php');
include ('sp_header_secure.php');
include ('sp_header.php');

//ini_set('display_errors','On');
//error_reporting(E_ALL);
?>

<link rel="stylesheet" href="css/global.css" />

</head>

<body>

<div class="container">

  <div id="content">
    <div id="textcontainer">

		<div class="header">
			<p>
			<a href="http://seagrant.mit.edu/ecosys_health.php"><img src="http://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

  <div id="text1" style="margin-left:75px;">

<p style="padding-left:3px; margin-bottom:30px; margin-top:0px;"><span style="font-size:1.2em; padding-right:15px;"><b>Change Your Password</b></span> :: ... return to [<a href="account_management.php">Account Management</a>]</p>

<?php # Script 11.1 - upload_image.php

// Check if the form has been submitted.
if (isset($_POST['submit'])) {

require_once ('/home/mapserver/connects/mitsg_connect.php'); // Connect to the db.

$message = NULL; // Create an empty new variable.

	// Check for a username.
	if (empty($_POST['user_id'])) {
		$u = FALSE;
		$message .= '<p>You forgot to enter your login ID.</p>';
	} else {
		$u = escape_data($_POST['user_id']);
	}

	// Check for an existing password.
	if (empty($_POST['password'])) {
		$p = FALSE;
		$message .= '<p>You forgot to enter your existing password.</p>';
	} else {
		$p = escape_data($_POST['password']);
	}

	// Check for a password and match against the confirmed password.
	if (empty($_POST['password1'])) {
		$np = FALSE;
		$message .= '<p>You forgot to enter your new password.</p>';
	} else {
		if ($_POST['password1'] == $_POST['password2']) {
			$np = escape_data($_POST['password1']);
		} else {
			$np = FALSE;
			$message .= '<p>Your new password did not match the confirmed new password.</p>';
		}
	}

	if ($u && $p && $np) { // If everything's OK.

		$oldpasshashresult = pg_query($con,"select pass_hash from users where user_id = '$u';");
		$oldpasshash = pg_fetch_all_columns($oldpasshashresult);
		$passtestquery = "SELECT * FROM users WHERE user_id='$u' AND pass_hash = crypt('$p','$oldpasshash[0]');";
		$passtestresult = pg_query ($con,$passtestquery);
		$num = pg_num_rows ($passtestresult);
		if ($num == 1) {

			$row = pg_fetch_array($result);

			// Make the query.

			$query = "UPDATE users SET pass_unencr='$np' WHERE user_id='$u';";
			$result = pg_query ($con,$query); // Run the query.
			$hashquery = "UPDATE users SET pass_hash=crypt('$np',gen_salt('bf')) WHERE user_id='$u';";
			$hashresult = pg_query($con,$hashquery); // Run the query.
			if (pg_affected_rows($hashresult) == 1) { // If it ran OK.

				// Send an email, if desired.
				$message = "<p style=\"text-align:center;\"><b>Your password has been changed.<br />-- <a href=\"account_management.php\">Return to Account Management</a>. --</b></p>";


			} else { // If it did not run OK.
				$message = '<p>Your password could not be changed due to a system error. We apologize for any inconvenience.</p><p>' . pg_last_error() . '</p>';
			}
		} else {
			$message = '<p>Your username and password do not match our records.</p>'; //.'<p>query:'.$passtestquery;
		}

	} else {
		$message .= '<p>Please try again.</p>';
	}
pg_close(); // Close the database connection.
}

?>

<form action="change_pwd.php" method="post">
<fieldset><legend>Enter your information in the form below:</legend>
<table width="400" border="0" cellspacing="5" cellpadding="0" style="margin-bottom:30px;">
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><div align="right">Login ID:</div></td>
    <td width="165"><input class="input" type="text" name="user_id"  style="width:150px;" /></td>
  </tr>
  <tr>
    <td><div align="right">Current Password:</div></td>
    <td width="165"><input class="input" type="password" name="password" style="width:150px;" maxlength="20" /></td>
  </tr>
  <tr>
    <td><div align="right">New Password:</div></td>
    <td width="165"><input class="input" type="password" name="password1" style="width:150px;" maxlength="20" /></td>
  </tr>
  <tr>
    <td><div align="right">Confirm New Password:</div></td>
    <td width="165"><input class="input" type="password" name="password2" style="width:150px;" maxlength="20" /></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2"><div align="center"><input type="submit" name="submit" value="Change My Password" /></div>
</td>
    </tr>
</table>

</fieldset>


</form>

 <?php
 if (isset($message)) {
	echo $message;
}
?>

</div>

</body>
</html>
