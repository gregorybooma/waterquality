<?php  $nav_display=5;
include ('sess_start.php');

$success_message='';

/*if ($_SESSION['usr_type']!='T') {

	// Start defining the URL.
	$url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	// Check for a trailing slash.
		if ((substr($url, -1) == '/') OR (substr($url, -1) == '\\') ) {
		$url = substr ($url, 0, -1); // Chop off the slash.
	}
	$url .= '/data_add.php'; // Add the page.
	header("Location: $url");
	exit(); // Quit the script.

} else { */
	$user_id=$_SESSION['user_id'];
	$user_id=$_SESSION['user_id'];
	$sch_id=$_SESSION['sch_id'];
//}

require_once ('/home/mapserver/connects/mitsg_connect.php');

$errors = array(); // Initialize error array.

if (isset($_POST['submit'])) {

	if (empty($_POST['first_name'])) {
		$errors[] = 'You did not provide a First Name.';
	} else {
	$fname= escape_data($_POST['first_name']);
	}

	if (empty($_POST['last_name'])) {
		$errors[] = 'You did not provide a Last Name.';
	} else {
	$lname= escape_data($_POST['last_name']);
	}

	if (empty($_POST['post_addr_1'])) {
		//$errors[] = 'You did not provide an Address.';
	} else {
	$addr1= escape_data($_POST['post_addr_1']);
	}

	$addr2= escape_data($_POST['post_addr_2']);

	if (empty($_POST['post_min_subd'])) {
		//$errors[] = 'You did not provide a City.';
	} else {
	$city= escape_data($_POST['post_min_subd']);
	}
	if ($_POST['post_maj_subd']=='') {
		//$errors[] = 'You did not provide a State.';
	} else {
	$state= escape_data($_POST['post_maj_subd']);
	}
	if (empty($_POST['post_code'])) {
		//$errors[] = 'You did not provide a Zip Code.';
	} else {
	$zip= escape_data($_POST['post_code']);
	}
	if (empty($_POST['iso3_code'])) {
		//$errors[] = 'You did not provide a Country.';
	} else {
	$country= escape_data($_POST['iso3_code']);
	}
	if (empty($_POST['tele_num'])) {
		//$errors[] = 'You did not provide a Telephone Number.';
	} else {
	$tel= escape_data($_POST['tele_num']);
	}

	if (empty($errors)) { // If everything's okay.
		$query_usr = "UPDATE users SET first_name='$fname', last_name='$lname', post_addr_1='$addr1', post_addr_2='$addr2', post_min_subd='$city', post_maj_subd='$state', post_code='$zip', iso3_code='$country', tele_num='$tel', mod_user = '$user_id', mod_date = now() WHERE user_id='$user_id';";
		$result_usr = pg_query($con, $query_usr);
		$query_spx = "UPDATE mitsg_spx_users SET first_name='$fname', last_name='$lname', mod_user = '$user_id', mod_date = now() WHERE user_id='$user_id';";
		$result_spx = pg_query($con, $query_usr);
	//	$query_sch = "UPDATE schools SET sch_name='$sch_name', addr1='$addr1', addr2='$addr2', city='$city', state='$state', zip='$zip', country='$country' WHERE sch_id='$sch_id';";
	//	$result_sch = pg_query($con, $query_sch);

		if ($result_usr) { $success_message='<p style="color:blue;">Update Successful!</p>'; } //&& $result_sch
    }
}

include ('sp_header.php');

?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n\\n"; }  } ?>";
    if (errorString != "") { alert(errorString); }
</script>

<link rel="stylesheet" href="css/global.css" />

</head>

<body>

<div class="container">


    <div id="content">
		<div class="header">
			<p>
			<a href="http://seagrant.mit.edu/ecosys_health.php"><img src="http://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

		<!--<div id="textcontainer" style="border:1px solid blue;">-->
		<table width="98%" border="0" cellspacing="5" cellpadding="0">
		  <tr valign="top" align="left">
			<td id="nav_col_td">
				<?php include ('navcol.php');?>			</td>
			<td class="td_border_pad" style="border:none;">

			<h2>Account Management</h2>


<div class="curved_corners" style="padding:5 15px 15px 15px; margin-bottom:20px; margin-top:20px; line-height:1.75em;">

<fieldset style="padding:10px;  margin-top:0px; margin-bottom:20px; border-width:0px 0px 1px 0px; text-align:center;">
<a href="account_management.php">User Info</a> / Add Sub-User / <a href="change_pwd.php">Change Password</a> / <a href="logout.php">Log out</a>
</fieldset>

<? //
$query = "SELECT * FROM users WHERE user_id='$user_id';";
$result = pg_query($con, $query); // Run the query.
$row = pg_fetch_array($result); // Return a record, if applicable.
?>
<form action="account_management.php" method="post">

<table width="550" border="0" cellspacing="8" cellpadding="0" style="font-size:1.1em;" align="center">
				  <tr valign="top">
				    <td align="right" valign="middle"><b>User Information</b></td>
				    <td align="left">&nbsp;</td>
			      </tr>
				  <tr valign="top">
					<td align="right" valign="middle">First Name</td>
					<td align="left"><input name="first_name" class="input_text" type="text" style="width:98%;" value="<?php if (!empty($errors)) { echo $_POST['first_name']; } else { echo $row['first_name']; } ?>" /></td>
				  </tr>
				  <tr valign="top">
					<td  align="right" valign="middle">Last Name</td>
					<td  align="left"><input name="last_name" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['last_name']; } else { echo $row['last_name']; } ?>" /></td>
				   </tr>
				  <tr valign="top">
					<td  align="right" valign="middle">Telephone</td>
					<td align="left"><input name="tele_num" type="text"  class="input_text" style="width:175px;" value="<?php if (!empty($errors)) { echo $_POST['tele_num']; } else { echo $row['tele_num']; } ?>" /></td>
				   </tr>
				  <tr valign="top">
				    <td align="right" valign="middle">&nbsp;</td>
				    <td align="left">&nbsp;</td>
			      </tr>
				  <tr valign="top">
				    <td align="right" valign="middle"><b>Address</b></td>
				    <td align="left">&nbsp;</td>
			      </tr>
				  <tr valign="top">
					<td align="right" valign="middle">Address Line 1</td>
					<td align="left"><input name="post_addr_1" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['post_addr_1']; } else { echo $row['post_addr_1']; } ?>" /></td>
				   </tr>
				  <tr valign="top">
					<td align="right" valign="middle">Address Line 2</td>
					<td align="left"><input name="post_addr_2" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['post_addr_2']; } else { echo $row['post_addr_2']; } ?>" /></td>
				   </tr>
				  <tr>
					<td  align="right" valign="middle">City/Locality</td>
					<td align="left"><input name="post_min_subd" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['post_min_subd']; } else { echo $row['post_min_subd']; } ?>" /></td>
				   </tr>
				  <tr>
					<td align="right" valign="middle">State/Province</td>
					<td align="left"><input name="post_maj_subd" type="text"  class="input_text" style="width:98%;" value="<?php  if (!empty($errors)) { echo $_POST['post_maj_subd']; } else { echo $row['post_maj_subd']; } ?>" /></td>
				   </tr>
				  <tr>
					  <td align="right" valign="middle">Postal/ZIP Code</td>
					  <td align="left"><input name="post_code" type="text"  class="input_text" size="10" value="<?php if (!empty($errors)) { echo $_POST['post_code']; } else { echo $row['post_code']; } ?>" /></td>
				  </tr>
				  <tr valign="top">
					<td align="right" valign="middle">Country (3-character ISO code, e.g. USA)</td>
					<td align="left"><input name="iso3_code" type="text"  class="input_text" style="width:98%;" value="<?php if (!empty($errors)) { echo $_POST['iso3_code']; } else { echo $row['iso3_code']; } ?>" /></td>
				  </tr>

				  <tr align="right" valign="middle">
				    <td>&nbsp;</td>
				    <td align="left">&nbsp;</td>
			      </tr>
				  <tr align="left" valign="middle">
					<td colspan="2" align="center"><input type="submit" name="submit" value="UPDATE"></td>
		          </tr>
				  <tr align="left" valign="middle">
				    <td colspan="2" align="center"><? echo $success_message; ?></td>
			      </tr>
			  </table>
</form>


</div>
 </div></td>
		  </tr>
		</table>
</div>
</body>
</html>
