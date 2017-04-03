<?php $nav_display=5;
include ('sess_start.php');
include ('sp_header_secure.php');

require_once ('/home/mapserver/connects/mitsg_connect.php');

//$_SESSION['writequery'] = NULL;

$success_message='';
$errors = array(); // Initialize error array.
$err_flag = array(); // Initialize error array.


if (isset($_POST['submit'])) {


	if (!empty($_POST['event_id'])) { $event_id = $_POST['event_id']; } else { $errors[] = 'No event/deployment was selected.';}

/* THIS SEEMS TO BE UNUSED:
	$query_events = "SELECT start_timestamp FROM events WHERE event_id='$event_id';";
	$result_events = pg_query($con, $query_events);
	$event_date_time = pg_fetch_array($result_events);
	$event_date = substr($event_date_time['start_timestamp'],0,10);
*/
	foreach ($_POST['included'] as $k => $v) {
		$unitsname = $v.'_units';
		if ($_POST["$unitsname"] == 'Unspecified' && $v != 'time') {
			$errors[] = 'Measurement units for '.$v.' were not specified.';
		}
	}

	//this check looks to see if units were set for a param that was not indicated as included. we require specificity in this case:
	foreach ($_SESSION['allunits'] as $k => $v) {
		$string = explode('_units',$v);
		$varname = $string[0];
		if ($_POST[$v] != 'Unspecified' && !in_array($varname,$_POST['included'])) {
			$errors[] = 'You indicated measurement units for '.$varname.' but did not indicate that '.$varname.' is included. Please clarify.';
		}
	}

	// THESE VARS ARE VITAL
	$_SESSION['posted'] = $_POST;
	$_SESSION['included'] = $_POST['included'];
	$_SESSION['event_id'] = $_POST['event_id'];

	if (empty($errors)) {
		header("Location: https://mit.sea-grant.net/waterquality/data_upload");
	}

}

include ('date_time_arrays.php');
include ('sp_header.php');

?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n"; } if (!empty($_FILES['upload']['tmp_name'])) { echo '\\n\nRE-SELECT YOUR LOGGED DATAFILE.\\n\n'; $file_upload_reminder_styles='style="color:red; font-weight:bold;"'; } } ?>";
    if (errorString != "") { alert(errorString); }
</script>

<? $_SESSION['prev_page']= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //THIS PAGE  ?>

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
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	  <tr valign="top" align="left">
		<td id="nav_col_td">
		<?php include ('navcol.php');?>
		</td>
		<td class="td_border_pad" style="border:none; padding-left:0px; padding-right:0px;">
		<div id="text1">

		<? $data_page_name=$_SERVER['REQUEST_URI']; //str_ireplace('/','',$_SERVER['REQUEST_URI']); ?>


	<form enctype="multipart/form-data" action="<? echo $data_page_name; ?>" method="post">
		<h3 style="margin-top:0px;">Submit Data</h3>
	<p style="padding-top:0px;"><font color="red">IMPORTANT REQUIREMENTS:</font>
	<ol>
	<li>We currently accept data as flat, plain text comma-delimited tables only (use a .csv or .txt file extension.) Columns may be in any order, and inclusion of a header row is optional.</li>
	<li>One table column must contain measurement timestamps. Our preferred format is as follows: %Y-%m-%d %H:%M:%S (for example, 2012-01-31 23:59:01.) Other valid formats can be found on our <a href="timestamp_format.html" target="_blank">page outlining valid date and time types</a>.</li>
	<li>Note that it is possible that the time zone corresponding to the timestamp in the data file is not the same as the local time zone where the sampling took place. Some instruments, for example, are set to UTC by default. Please confirm before upload.</li>
	<li>The measurement units, instruments, and reference datums for parameters selected below must apply to every corresponding record in the uploaded file. If this is not the case, the data should be subsetted accordingly.</li>
	<li>Only numeric data is accepted, though presence-absence variables with binary coding (1 or 0) may be included. At this time, any fields with no data must be left completely blank (i.e. they may not contain a no data identifier).</li>
	</ol>
	</p>

		<p style="padding-left:15px;"><b>Event/Deployment :</b>
		  <select name="event_id">
		  <option value="">Select Event/Deployment</option>
		  <?

//			  $query_events = "SELECT * FROM events WHERE (create_user='$user_id' or  mod_user='$user_id' or primary_contact='$user_id' or secondary_contact='$user_id') or  event_id in (select distinct visit_id from all_obs where spotter_id='$user_id' or recorder_id = '$user_id' or id_pers_id = '$user_id') ORDER BY start_timestamp DESC;";
			  $query_events = "SELECT distinct on (start_timestamp,event_id) start_timestamp,event_id,location_id,organization_id,program_id FROM events WHERE (create_user='$user_id' or  mod_user='$user_id' or primary_contact=(select distinct user_code from users where user_id = '$user_id') or secondary_contact=(select distinct user_code from users where user_id = '$user_id')) or  (event_id in (select distinct visit_id from all_obs where spotter_id=(select distinct user_code from users where user_id = '$user_id') or recorder_id = (select distinct user_code from users where user_id = '$user_id') or id_pers_id = (select distinct user_code from users where user_id = '$user_id'))) ORDER BY start_timestamp DESC,event_id DESC;";
			  $result_events = pg_query($con, $query_events);
			  while ($events = pg_fetch_array($result_events)) {
				$location_id=$events['location_id'];
				$org_id=$events['organization_id'];
				$program_id=$events['program_id'];
				$query_locations = "SELECT * FROM location_instances WHERE (location_id='$location_id' and organization_id='$org_id' and program_id='$program_id');";
				$result_locations = pg_query($con, $query_locations);
				$locations = pg_fetch_array($result_locations);
				if (empty($locations['location_name'])) {
					$result_locations = pg_query($con, "select * from location_instances where location_id='$location_id' and organization_id='$org_id' limit 1");
					$locations = pg_fetch_array($result_locations);
				}
				echo '<option value="'.$events['event_id'].'"'; if ($events['event_id']==$event_id) { echo ' selected="selected"'; } echo '>'.$org_id.'-'.$program_id.': '.$locations['location_name'].' ['.$events['start_timestamp'].']</option>';
			  }

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

				$vtid = random(1,4);
		?>

		</select> or <a href="visit_add.php?vtid=<? echo $vtid; ?>">Add a New Event/Deployment and/or Site</a></p>

<?
// Build output unit class arrays for constructing drop-down selects
$unit_class_array = pg_fetch_all_columns(pg_query($con,"select distinct unit_class from wq_params order by unit_class;"));

foreach ($unit_class_array as $key => $class) {
//	${$class.'_unit_result'} = pg_query($con,"select distinct unit_code,unit_name from wq_units where unit_class = '$class' order by unit_name;");
	${$class.'_unit_result'} = pg_query($con,"select distinct unit_code,unit_name,unit_abbrev from wq_units where unit_class = '$class' order by unit_name;");
	${$class.'_unit_array'} = array();
	while ($row = pg_fetch_array(${$class.'_unit_result'})) {
//		${$class.'_unit_array'}[$row['unit_code']] = $row['unit_name'];
		${$class.'_name_abbrev'} = $row['unit_name'].' ('.$row['unit_abbrev'].')';
		${$class.'_unit_array'}[$row['unit_code']] = ${$class.'_name_abbrev'};
	}
}

echo '<p style="padding-left:15px;"><b>Select parameters and units included in the file to be uploaded:</b></p>';
// Table header and timestamp row.
echo '<table align="left" cellspacing="0" cellpadding="5" style="padding-left:15px; margin-top:5px; width:800px;">
    <tr>
	<td align="left" valign="top"><b>Parameter Name</b></td>
	<td align="left" valign="top"><b>Included</b></td>
	<td align="left" valign="top"><b>Measurement Units</b></td>
	<td align="left" valign="top"><b>Instrument Make and Model</b></td>
	<td align="left" valign="top"><b>Reference datum (surface, mllw, etc.)</b></td>
	</tr>
	<tr bgcolor="#CCCCCC">
	<td align="left" valign="top">timestamp</td>
	<td align="left" valign="top"><input type="hidden" name="included[]" value="time">&nbsp; &#10003;</td>
	<td align="left" valign="top"><input type="hidden" name="time_units" value="s">See <a href="timestamp_format.html" target="_blank">valid types</a>.</td>
	<td align="left" valign="top"><input type="text" name="time_instrument" value="Unspecified"></td>
	<td align="left" valign="top"><input type="hidden" name="time_reference_datum" value="Unspecified">Indicate timezone on next screen</td>
	</tr>';

// Fetch and print all the records.
$bg = '#FFFFFF';
$record = 1;
$param_result = pg_query($con,"select distinct param_short_name,param_long_name,unit_class from wq_params order by param_long_name;");

while ($row = pg_fetch_array($param_result)) {

	${$pshort.'_unit_result'} = NULL;
	$pshort = $row['param_short_name'];
	$pclass = $row['unit_class'];
	$pshortunits = $pshort.'_units';
	$allunits[] = $pshortunits;
	$_SESSION['allunits'] = $allunits; //for verification upon post
	${$pshort.'_unit_array'} = ${$pclass.'_unit_array'};

	if ($pshort != 'time') { //we're forcing it on line 1
		echo '<tr bgcolor="' . $bg . '">
			<td align="left" valign="top">'.$row['param_long_name'].'</td>

			<td align="left" valign="top"><input type="checkbox" name="included[]" value="'.$pshort.'"'; if ($_POST["$pshort"]==$pshort) { echo ' checked';}
		echo'></td>

			<td align="left" valign="top">
			<select name="'.$pshort.'_units">';
		echo '<option value="Unspecified" style="color:#990000;">Select Units</option>';
			foreach (${$pshort.'_unit_array'} as $k => $v) {
				echo '<option value="'.$k.'"'; if ($pshort.'_units'==$_POST["$pshortunits"]) { echo ' selected="selected"'; } echo '>'.$v; echo '</option>"';
			}
		echo '</select>';

		$instrument_result = pg_query($con,"select distinct on (brand,model,instrument_id,instrument_type) brand, model, instrument_id, instrument_type, (brand ||':'||model) as instrument_name from wq_instruments where instrument_id in (select instrument_id from wq_instrument_params where param_short_name = '$pshort') order by brand,model;");
		echo '
			</td>
			<td align="left" valign="top">
			<select name="'.$pshort.'_instrument">';
		echo '<option value="Unspecified" style="color:#990000;">Unspecified</option>';
		while ($row = pg_fetch_array($instrument_result)) {
			$pshortinstrument = $pshort.'_instrument';
			echo '<option value="'.$row['instrument_id'].'"'; if ($pshort.'_instrument'==$_POST["$pshortinstrument"]) { echo ' selected="selected"'; } echo '>'.$row['instrument_name']; echo '</option>"';
		}
		echo '</select>
			</td>
			</td>';
		if ($pclass == 'coordinate') {
			echo '<td align="left" valign="top"><input type="text" name="'.$pshort.'_reference_datum'.'" value="Unspecified"></td>';
		} else { //waffling on how to handle
//			echo '<td align="left" valign="top"><input type="text" name="'.$pshort.'_reference_datum'.'" value="Unspecified"></td>';
			echo '<td align="left" valign="top"><input type="hidden" name="'.$pshort.'_reference_datum'.'" value="NULL"></td>';
		}
		echo '</tr>';
		$bg = ($bg=='#FFFFFF' ? '#CCCCCC' : '#FFFFFF');
	} //end write table row if not time
	//I think this is not necessary (only one in page): $record++;
}

//echo '</table>'; ?>




	</form>

		<tr><td colspan=4 align="center"><input type="submit" name="submit" value="Proceed"></td></tr>
		</table>

		</div>
</td>
	  </tr>
	</table>
</body>
</html>
