<?php
include ('sess_start.php');

// Check if the form has been submitted.
if (isset($_POST['submit'])) {

require_once ('/home/mapserver/connects/mitsg_connect.php');


	$errors = array(); // Initialize error array.

	// Check for an login ID
	if (empty($_POST['login'])) {
		$errors[] = 'You forgot to enter your login ID.';
	} else {
		$login_id = escape_data($_POST['login']);
		$user_id = escape_data($_POST['login']);
	}

	// Check for a password.
	if (empty($_POST['password'])) {
		$errors[] = 'You forgot to enter your password.';
	} else {
		$pwd = escape_data($_POST['password']);
	}

	if (empty($errors)) { // If everything's OK.

		$query = "SELECT * FROM mitsg_spx_users WHERE user_id='$user_id' AND (select pass_unencr from users where user_id = '$login_id') = '$pwd';";
		//$spx_query = "SELECT * FROM spx_users WHERE user_id='$user_id';";
		$result = pg_query($con, $query); // Run the query.
		//$spx_result = pg_query($con, $spx_query); // Run the query.
		$row = pg_fetch_array($result); // Return a record, if applicable.
		//$spx_row = pg_fetch_array($spx_result); // Return a record, if applicable.

		if ($row ) { // && $spx_row A record was pulled from the database.

			// Set the session data & redirect.
			$_SESSION['login_id']=$login_id;
			$_SESSION['user_id']=$user_id;
			$_SESSION['sch_id']=$row['organization_id'];
			$_SESSION['organization_id']=$row['organization_id'];
			$_SESSION['user_affil']=$row['organization_id'];
			$_SESSION['usr_type']=$row['user_group'];
			$_SESSION['user_group']=$row['user_group'];

//			if ($_POST['dest']=='submit') { $dest='data_add.php'; } elseif ($row['user_group']=='T') { $dest='account_management.php'; } else { $dest='data_add.php'; }
			if ($_POST['dest']=='submit') { $dest='event_params.php'; } else { $dest='account_management.php'; }

			// Redirect the user to the user homepage.
			$url = 'https://mit.sea-grant.net/waterquality/'.$dest;

			header("Location: $url");
			pg_free_result ($ini_result);

//			exit(); // Quit the script.

		} else { // No record matched the query.
			$errors[] = 'The login ID and password entered do not match any on file.'; // Public message.
			//$errors[] = mysql_error() . '<br /><br />Query: ' . $query; // Debugging message.
		}

	} // End of if (empty($errors)) IF.

	pg_close(); // Close the database connection.

} else { // Form has not been submitted.

	$errors = NULL;

} // End of the main Submit conditional.


include ('sp_header.php');
?>

<link rel="stylesheet" href="css/global.css" />
<!--[if lt IE 9]>
	<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->


</head>


<body>

<div class="container">

	<div id="content">
		<div class="header">
			<p>
			<a href="https://seagrant.mit.edu/ecosys_health.php"><img src="https://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

	<!--<div id="textcontainer" style="border:1px solid blue;">-->
	<table width="98%" border="0" cellspacing="5" cellpadding="0" style="margin-bottom:100px;">
	  <tr valign="top" align="left">
		<td id="nav_col_td">

			<?php include ('navcol.php'); ?>

		</td>
		<td class="td_border_pad" style="border:none;">
		<div id="text1" style="padding-left:80px; padding-top:20px;  font-size:1em;">
		<h3>Login</h3>
		<form action="login.php" method="post">
			<p>Login ID<br />
		   <input type="text" name="login" size="20" maxlength="40" value="<? echo $login_id ?>"/> </p>
			<p>Password <br />
		   <input type="password" name="password" size="20" maxlength="40" /></p>
			<p style="margin-top:20px;"><input type="submit" name="submit" value="Login" /></p>
		   <input type="hidden" name="dest" value="<? echo $_GET['dest']; ?>">
		</form>

		<!--<a href="retrieve_pwd.php">Retrieve Password</a>-->

		<p style="font-size:.9em; margin-top:40px;"><span style="font-size:1.2em;"><font color="blue"><b>Don't have an account?</font></b></p>
		<p>If you are a manager of an organized educational or scientific monitoring program, please <a href="https://seagrant.mit.edu/directory.php?t=staff&mode=th">contact Julie Simpson</a> to formalize your project's metadata and quality assurance plan.<br><br>Otherwise: <u><a href="quick_account.php">Create one</a></u>. It's easy!</b></span></p>

		<?php

			if (!empty($errors)) { // Report the errors.
				echo '<div class="registered" style="text-align:left; color:#990000; font-size:.9em;">';
				foreach ($errors as $msg) { echo "$msg<br />\n"; }
				echo '</p><p>Please try again.</p></div>';
			}

		?></div>
		</td>
		<td width="37%">
		</td>
	  </tr>
	</table>
	</div>
</div>
</body>
</html>
