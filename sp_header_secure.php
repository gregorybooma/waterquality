<?php

// If no session value is present, redirect the user.
if (!isset($_SESSION['user_id'])) {

	// Start defining the URL.
	$url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	// Check for a trailing slash.
		if ((substr($url, -1) == '/') OR (substr($url, -1) == '\\') ) {
		$url = substr ($url, 0, -1); // Chop off the slash.
	}
	$url .= '/login.php'; // Add the page.
	header("Location: $url");
    echo "session not set";
	exit(); // Quit the script.

} else { //need to clean-up the legacy junk below!
	$login_id=$_SESSION['login_id'];
	$user_id = $_SESSION['user_id'];
	$sch_id=$_SESSION['sch_id'];
	$user_affil=$_SESSION['user_affil'];
	$affiliation = $_SESSION['user_affil'];
	$user_group=$_SESSION['user_group'];
	$organization_id = $_SESSION['user_affil'];
	$organization_id = $_SESSION['organization_id'];
}

?>
