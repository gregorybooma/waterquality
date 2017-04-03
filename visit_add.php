<?php $nav_display=5;
//ini_set('display_errors',1);

include ('sess_start.php');
include ('sp_header_secure.php');

$success_message='';
$errors = array(); // Initialize error array.
$err_flag = array(); // Initialize error array.

function dollar_grooming($input) {  // number grooming - NO ROUNDING
$input=str_ireplace("$","",$input);
$input=str_ireplace(",","",$input);
$input=str_ireplace(" ","",$input);
$output=$input;
return $output;
}

require_once ('/home/mapserver/connects/mitsg_connect.php'); // /home/mapserver/connects/sp_connect.php


if (isset($_POST['submit'])) {

		if (empty($_POST['location'])) {
		$errors[] = 'You forgot to indicate a Site.';
	} else {
		$location=$_POST['location'];
		$_SESSION['location']=$_POST['location'];
	}

	if (empty($_POST['startmonth'])) {
		$errors[] = 'You forgot to indicate a Start Month.';
	} else {
		$startmm=$_POST['startmonth'];
	}

	if (empty($_POST['startday'])) {
		$errors[] = 'You forgot to indicate a Start Day.';
	} else {
		$startdd=$_POST['startday'];
	}

	if (empty($_POST['startyear'])) {
		$errors[] = 'You forgot to indicate a Start Year.';
	} else {
		$startyy=$_POST['startyear'];
	}

	if (empty($_POST['starthour'])) {
		$errors[] = 'You forgot to indicate a Start Hour.';
	} else {
		$starthr=$_POST['starthour'];
	}

	$amount_groomed = dollar_grooming($_POST['startminute']);
	if (is_numeric($amount_groomed) && $amount_groomed <= 60) { $startmin = $amount_groomed; } else { $errors[] = 'Start Minute is invalid.'; $err_flag[]='startminute';}
/*
	if (empty($_POST['stophour'])) {
		$errors[] = 'You forgot to indicate a Stop Hour.';
	} else {
		$stophr=$_POST['stophour'];
	}

	$amount_groomed = dollar_grooming($_POST['stopminute']);
	if (is_numeric($amount_groomed)) { $stopmin = $amount_groomed; } else { $errors[] = 'Stop Minute is invalid.';  $err_flag[]='stopminute';}
*/

	$visnotesResult = str_replace( "\r\n", '<br />', $_POST['visnotes'] );
	$visnotesResult2 = escape_data("$visnotesResult");
	$visnotes = str_replace( '<br />', "\r", "$visnotesResult2" );

	$localtime=$startyy.'-'.$startmm.'-'.$startdd.' '.$starthr.':'.$startmin.':00';
/*
	$visenddatetime=$startyy.'-'.$startmm.'-'.$startdd.' '.$stophr.':'.$stopmin.':00';

	if ($localtime > $visenddatetime) {
		$errors[] = 'The Start Time indicated is later than the Stop Time.';
	}
*/

	///CREATE VISIT_ID
	$counter = 1;
	$goflag = 1;
	while ($goflag == 1) {
		$thecount = 1000 + $counter;
		$countstring = substr($thecount,-3);
		$visit_id = 'ESPX'.$affiliation.$startyy.$startmm.$startdd.$starthr.$startmin.'00'.$countstring;
		$result = pg_query($con,"select * from events where event_id = '$visit_id';");
		if (pg_num_rows($result) == 0) {
			$goflag = 0;
			} else {
			$counter = $counter + 1;
		}
	}
	$_SESSION['event_id']=$visit_id;
	///END VISIT_ID CREATION

	// get the local tz for location

	$query = "select st_x(location_geom) as longitude, st_y(location_geom) as latitude from locations where location_id = '$location';";
	$result = pg_query($con,$query);
	$result_array = pg_fetch_array($result);
	$longitude = $result_array[0];
	$latitude = $result_array[1];

	//this gets json and seems ok:
	$request = 'https://maps.googleapis.com/maps/api/timezone/json?location='.$latitude.','.$longitude.'&timestamp='.time().'&sensor=false';
	$response = @file_get_contents($request);
	if (empty($response)) { $errors[] = 'An error occurred (API error). Please try again and contact MIT Sea Grant if the problem persists.'; exit; }

	$response_array = json_decode($response,TRUE);

	$local_tz_name = $response_array['timeZoneId'];

	// Need two "at time zone" conditions. The first gives an epoch relative to the system's locale, so
	// the second is needed to ensure the epoch is in the locale of the reference datum.
	$epochresult = pg_query($con,"select extract(epoch from timestamp '$localtime' at time zone '$local_tz_name' at time zone 'UTC');");
	$utctime_array = pg_fetch_all_columns($epochresult);
	$utctime = $utctime_array[0];

	if (empty($errors)) {

		$query_visits = "INSERT INTO events (


event_id,
location_id,
organization_id,
program_id,
start_time_utc,
start_timestamp,
local_timezone,
event_comments,
create_date,
create_user

		) VALUES (

'$visit_id',
'$location',
'MITSG',
'SPX',
'$utctime',
'$localtime',
'$local_tz_name',
'$visnotes',
NOW(),
'$login_id'

		);";	//pulled these out: verbatimeventdateend, '$visenddatetime',

	//$_SESSION['q'] = $query_visits;

	$result_visits = pg_query($con, $query_visits);
	if ($result_visits) {
		        $_SESSION['visit']=$localtime;
				$_SESSION['vtid'] = $_POST['vtid'];
				$url = 'https://mit.sea-grant.net/waterquality/event_params';
				header("Location: $url");
				exit();
	}

	} // end errors IF

} // end submitted IF

include ('date_time_arrays.php');
include ('sp_header.php');

?>


<script type="text/javascript">
	<? $prev_page=$_SESSION['prev_page'];  $vtid=$_GET['vtid']; ?>
	function forward(){ location.href="<? echo $prev_page; ?>"; }
    var errorString_2 = "<?php if (($_SESSION['vtid']==$vtid)) { echo 'WARNING : BACK / FORWARD browser buttons are not usable in the request form (Add a Visit)';  } ?>";
    if (errorString_2 != "") { alert(errorString_2); forward(); }
</script>
<? $_SESSION['prev_page']= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];  ?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n"; } } ?>";
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

	<table width="98%" border="0" cellspacing="5" cellpadding="0">
	  <tr valign="top" align="left">
		<td id="nav_col_td">
		<?php include ('navcol.php');?>
		</td>
		<td class="td_border_pad">
		<h3>Add a Sampling Event or Deployment</h3>

<? $data_page_name=$_SERVER['REQUEST_URI']; //str_ireplace('/','',$_SERVER['REQUEST_URI']); ?>

		<form action="<? echo $data_page_name; ?>" method="post">


<table width="98%" border="0" cellspacing="3" cellpadding="0">
  <tr>
    <td valign="top"><b>Event/deployment site : </b><select name="location">
      <option value="">Select Site</option>
      <?php
		  $organization_id = $_SESSION['organization_id'];
		  $query_locations = "SELECT * FROM location_instances WHERE organization_id='$organization_id' ORDER BY location_name;"; //
		  $result_locations = pg_query($con, $query_locations);
		  while ($locations = pg_fetch_array($result_locations)) {
			echo '<option value="'.$locations['location_id'].'"'; if ($locations['location_name']==$_SESSION['location_name'] || $locations['location_id']==$_POST['location']) { echo ' selected="selected"'; } echo '>'.$locations['location_name'].' ('.$locations['program_id'].')'; echo '</option>"';
		  }
		?>

		<?
						function random($type,$length) {

					if($type == "1") {
					$random = "0123456789";
					$rnd = 9;
					}

					for($i = 0; $i < $length; $i++) {
					$info .= $random { rand(0,$rnd) };
					}
					return $info;
				}

				$tid = random(1,4);
		?>
    </select> or <a href="site_add.php?vtid=<? echo $vtid; ?>&tid=<? echo $tid; ?>">add a new site</a></td>
  </tr>
  <tr>
    <td valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td width="67%" valign="top" align="center">
		<fieldset style="border:1px solid #CCCCCC;">
		<table width="100%" border="0" cellspacing="3" cellpadding="0">

			  <tr>
				<td colspan="1" align="left"><b>Local date and time at start of event :</b></td>
				</tr>
			  <tr>
				<td colspan="1" align="left">Start Date</td>
				</tr>
			  <tr>
				<td colspan="1" align="left"><select name="startmonth">
				  <option value="">Month</option>
				  <?php 	foreach ($months as $k => $startm) {
					echo '<option value="'.$k.'"'; if ($k==$startmm) { echo ' selected="selected"'; } echo '>'.$startm.'</option>"';
				  } ?>
				</select>
				  <select name="startday">
					<option value="">Day</option>
					<?php foreach ($days as $startd) {
					echo "<option value=\"$startd\""; if ($startd==$startdd) { echo ' selected="selected"'; } echo ">$startd</option>";
					} ?>
				  </select>
				  <select name="startyear">
					<option value="">Year</option>
					<?php foreach ($years as $starty) {
					echo "<option value=\"$starty\""; if ($starty==$startyy) { echo ' selected="selected"'; } echo ">$starty</option>";
					} ?>
				  </select>
				</td>
			</tr>
			<tr>
			<td align="left">Start Time (HH:MI)</td>
			</tr>
			<tr>
			<td align="left">
			  <div align="left">
				<select name="starthour">
				  <option value="">select</option>
				  <?php foreach ($hours as $starth) {
					echo "<option value=\"$starth\""; if ($starth==$starthr) { echo ' selected="selected"'; } echo ">$starth</option>";
					} ?>
				</select>
				: <input type="text" name="startminute" style="width:25px;<?php if (in_array("startminute", $err_flag)) { echo ' color:red; font-weight:bold;'; }?>" value="<?php echo $startmin; ?>"/>
			  </div>
			</td>
			</tr>

		</table>
		</fieldset>	</td>
  </tr>
  <tr>
    <td colspan="2" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" valign="top"><b>Notes</b> (optional)<br />
      <textarea name="visnotes" rows="6" style="width:98%;"><?php echo $_POST['visnotes']; ?></textarea></td>
    </tr>
</table>

<input name="vtid" type="hidden" value="<? echo $vtid; ?>">
	<input type="submit" name="submit" value="Submit Form">

	</form>
<? echo $success_message; ?>

<!-- end Add Sites Table-->

		</td>
		<td width="25%"></td>
	  </tr>
	</table>
	</div>
</div>
</body>
</html>