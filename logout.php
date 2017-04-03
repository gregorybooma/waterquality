<?php
# Script 8.2 - view_users.php
# (3rd version after Scripts 7.4 & 7.6)

// This script retrieves all the records from the users table.
// This new version links to edit and delete pages.

include ('sess_start.php');

// If no session value is present, redirect the user.
if (!isset($_SESSION['user_id'])) {

	// Start defining the URL.
	$url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	// Check for a trailing slash.
		if ((substr($url, -1) == '/') OR (substr($url, -1) == '\\') ) {
		$url = substr ($url, 0, -1); // Chop off the slash.
	}
	$url .= '/index.html'; // Add the page.
	header("Location: $url");
    echo "session not set";
	exit(); // Quit the script.

} else {
    $sch_ident=$_SESSION['sch_ident'];
	$_SESSION = array(); // Destroy the variables.
	//session_destroy(); // Destroy the session itself.
	setcookie ('PHPSESSID', '', time()-300, '/', '', 0); // Destroy the cookie.
    $_SESSION['sch_ident']=$sch_ident;
} // End of the main Submit conditional.

include ('sp_header.php');?>

<link rel="stylesheet" href="css/global.css" />
<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

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
				<?php include ('navcol.php');?>
			</td>
			<td class="td_border_pad" style="border:none;"><p style="padding-top:20px; padding-left:80px;">You have successfully logged-out.</p></td>
			<td width="37%"></td>
		  </tr>
		</table>

	</div>
</div>
</body>
</html>
