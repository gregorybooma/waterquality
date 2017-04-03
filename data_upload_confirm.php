<?php
//ini_set('display_errors',1);
include ('sess_start.php');
include('sp_header_secure.php');

require_once ('/home/mapserver/connects/mitsg_connect.php');

if ($_SESSION['prev_page'] != 'https://mit.sea-grant.net/waterquality/data_upload') {
	$back='T';
} else {
	$back='F';
}

$message = array();

if (isset($_POST['commit'])) {

/* SEE COMMENTS BELOW ON LOCAL TZ
if (!empty($_POST['local_tz_name'])) { // local tz comes from google api -- see code near bottom
*/
		$csvfile_prefix = $_SESSION['csvfile_prefix'];//
		$fullpath = '/mnt/var-www/mit/waterquality/tmp/'.$csvfile_prefix.'.csv';
//
		// open data file connection and start pg copy
		$fcon = fopen("$fullpath",'r');
		$copycommand = "copy wq_obs (event_id,upload_id, rowgroup_id, param_short_name, value) from STDIN with (format csv);";//, null 'NULL', quote '''');";
		pg_query($con,"$copycommand");

		// read and write the data
		while (!feof($fcon)) {
			$row = trim(fgets($fcon)," ");
			pg_put_line($con,"$row");
			$fcon++;
		}
		pg_put_line($con,"\\.\n"); //end of data
		$copyended = pg_end_copy($con); //end of copy
		fclose($fcon); //close the data file

		if ($copyended) {

			@unlink($fullpath);
			// then:
			//header("Location: https://seaperch.mit.edu/event_params.php");
			$message[]= 'UPLOAD WAS SUCCESSFUL \n\n Click OK to return to the View Data page.';
		} else {
			@unlink($fullpath);
			//chmod("$fullpath",0644); //for debugging
			$message[]= 'ERROR \n\n An error occurred. No records were written to the database. Please try again and contact MIT Sea Grant if the problem persists.';
		}

	/* } SEE COMMENTS BELOW RE LOCAL TZ
	else { // local tz comes from google api -- if fails then api likely offline -- see code near bottom
		$message[]= 'ERROR \n\n An error occurred. The local time zone could not be set. No records were written to the database. Please try again and contact MIT Sea Grant if the problem persists.';
	} */
} //end if commit

?>

<? include ('sp_header.php'); ?>

<script type="text/javascript">
	function forward(){ location.href="https://mit.sea-grant.net/waterquality/explore"; }
    var errorString_2 = "<?php if (empty($message) && $back=='T') { echo 'WARNING : BACK / FORWARD browser buttons are not usable in this form.';  } ?>";
    if (errorString_2 != "") { alert(errorString_2); forward(); }
</script>

<script type="text/javascript">
	function forward(){ location.href="https://mit.sea-grant.net/waterquality/explore"; }
    var errorString = "<?php if (!empty($message)) { foreach ($message as $msg) { echo "$msg \\n"; } } ?>";
    if (errorString != "") { alert(errorString); forward(); }
</script>

<? $_SESSION['prev_page']= 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; // i.e. this page ?>

	<link rel="stylesheet" href="css/global.css" />
	<!--[if lt IE 9]>
		<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>

<body>
<div class="container">

    <div id="content">

<?
//ini_set('display_errors',1);

//echo '<br> '.$_SESSION['insert_queryx'];
// gather vars to verify -- should be session vars

$event_id = $_SESSION['event_id'];

$event_result = pg_query($con,"select
								(select location_name from location_instances,events where events.event_id = '$event_id' and (events.location_id = location_instances.location_id)) as location_name,
								(select st_y(location_geom) from locations,events where events.event_id = '$event_id' and (events.location_id = locations.location_id)) as latitude,
								(select st_x(location_geom) from locations,events where events.event_id = '$event_id' and (events.location_id = locations.location_id)) as longitude,
								start_timestamp
								from events
								where event_id = '$event_id';");

while ($row = pg_fetch_array($event_result)) {
	$location_name = $row['location_name'];
	$latitude = $row['latitude'];
	$longitude = $row['longitude'];
	$event_date = $row['start_timestamp'];
}

		echo '<div class="header">
			<p>
			<img src="https://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources">
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>';

echo '<p><b>You are about to upload data for the event starting <font color="blue">'.$event_date.'</font> at <font color="blue">'.$location_name.'</font>.</b></p>';

$header_row = $_SESSION['header_row'];
if ($header_row == 'header_row') {
	echo '<p><b>The file <font color="blue">has</font> a header row containing column names</b>.</p>';
} else {
	echo '<p><b>The file <font color="blue">does not have</font> a header row containing column names</b>.</p>';
}

$vars = $_SESSION['vars_entered_indexes'];
$var_string = implode(", ",$vars);

echo '<p><b>File columns are in the following order:</b><br><br><font color="blue">'.$var_string.'</font>.</p>';

$instrument_tz = $_SESSION['instrument_tz'];

$tz_array = pg_fetch_all_columns(pg_query($con,"select name from pg_timezone_names where name = '$instrument_tz' and name not like '%GMT%' and name not like 'posix%' order by name;"));
$tz_string = implode(", ",$tz_array);

echo '<p><b>The time zone corresponding to the timestamp column is one of the following (synonyms may be included):</b><br><br><font color="blue">'.$tz_string.'</font>.</p>';

echo '<p><b>Measurement units for parameters are as follows:</b></p>';

for ($i = 0; $i < count($vars); $i++) { //for every column

	$varname = $_SESSION['vars_entered_indexes'][$i];

	if ($varname != 'time') {

		//get instrument and units for writing -- though not writing instrument currently
		$instrumentname = $varname.'_instrument';
		$instrument = $_SESSION['posted']["$instrumentname"];
		$unitsname = $varname.'_units';
		$unit_code = $_SESSION['posted']["$unitsname"];

		$unit_array = pg_fetch_all_columns(pg_query($con,"select unit_name from wq_units where unit_code = '$unit_code';"));
		$units = $unit_array[0];

		echo '<font color="blue">'.$varname.' : '.$units.'</font><br>';

	}//end if not time

} //end for every column


echo "<font face=\"sans-serif\">";

echo "<form method=\"post\" action=\"data_upload_confirm.php\">";

/* THERE DOESN'T SEEM TO BE A NEED FOR LOCAL TZ HERE: IT IS SET ON EVENT CREATION. DELETE IF ALL OK.
// get the local tz for location
	//this gets json and seems ok:
	$request = 'https://maps.googleapis.com/maps/api/timezone/json?location='.$latitude.','.$longitude.'&timestamp='.time().'&sensor=false';
	$response = @file_get_contents($request);

	$response_array = json_decode($response,TRUE);

	$local_tz_name = $response_array['timeZoneId'];

echo '<input name="local_tz_name" type="hidden" value="'.$local_tz_name.'">';
*/
echo "<input name=\"commit\" type=\"hidden\" value=\"commit\">";
echo "<font color=\"red\"><p>Verify that this information is correct before proceeding!</font>";
echo "<p><input type=\"button\" value=\"Go Back To Make Corrections\" onclick=\"history.go(-1);return false;\" />";
echo " or <input type=\"submit\" name=\"commit\" value=\"Commit Data\">";
echo "</form>";

?>
</div>
</div>
<?php
pg_close($con);
//print_r($_SESSION['event']); echo '<br>'; print_r($_SESSION['included']); echo '<br>'; print_r($_SESSION['posted']); echo '<br>'; print_r($_POST);
// footer is blocking form so excluding: include('sp_footer.php');
?>
</body>
</html>
