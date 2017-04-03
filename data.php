<!DOCTYPE html>

<html lang="en">

<head>
	<title>Observation Data</title>

	<link rel="stylesheet" href="css/global.css" />

	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
<?php
//ini_set('display_errors',1);
session_name ('YourVisitID');
session_start(); // Start the session.

require_once ('/home/mapserver/connects/mitsg_connect.php');

//actual data time range
$mindate=$_GET['start'];
$maxdate=$_GET['end'];


//user selections
/*$location_id=$_SESSION['location_id'];
$location_name=$_SESSION['location_name'];
$site_x=$_SESSION['site_x'];
$site_y=$_SESSION['site_y'];
$startmonth=$_SESSION['startmonth'];
$stopmonth=$_SESSION['stopmonth'];
$startday=$_SESSION['startday'];
$stopday=$_SESSION['stopday'];
$startyear=$_SESSION['startyear'];
$stopyear=$_SESSION['stopyear'];
$varmenu=$_SESSION['varmenu'];
*/
foreach ($_SESSION['posted_keys'] as $key => $value) {
	${$value} = NULL;
	${$value} = $_SESSION[$value];
}

$startdatetime = $startyear.'-'.$startmonth.'-'.$startday.' '.'00:00:00';
$enddatetime = $stopyear.'-'.$stopmonth.'-'.$stopday.' '.'23:59:59';
if ($startdatetime < $mindate) {
	$startdatetime=$mindate;
	}
if ($enddatetime > $maxdate) {
	$enddatetime=$maxdate;
	}
$timepair = $startdatetime.','.$enddatetime;

if (isset($_SESSION['posted_keys'])) {

$errors = array();
$num = NULL;
$ors_array = array();

$varnames = array();
$vars_array = array();

	if (!empty($varmenu)) {

		foreach ($varmenu as $v) {

			$vars_units = explode('@',$v);
			$var = $vars_units[0];
			if ($var != 'time') {
				$units = $vars_units[1];
				$var_where = "(param_short_name='".$var."' and (select unit_code from wq_obs_meta where param_short_name ='".$var."' and wq_obs_meta.upload_id=wq_obs.upload_id)='".$units."')";
							  //(param_short_name = 'PAR' and (select unit_code from wq_obs_meta where param_short_name ='PAR' and wq_obs_meta.upload_id=wq_obs.upload_id)='U00B5-mol-U002F-s-U002F-m-U00B2')
							  //(param_short_name = 'sound_velocity' and (select unit_code from wq_obs_meta where param_short_name ='sound_velocity' and wq_obs_meta.upload_id=wq_obs.upload_id)='m-U002F-s')

		// get the local tz for location

		$query = "select st_x(location_geom) as longitude, st_y(location_geom) as latitude from locations where location_id = '$location_id';";
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

		pg_free_result($result);

	/*
	(select local_timezone from events where events.event_id = wq_obs.event_id) as local_timezone,
	(select reference_datum from wq_obs_meta where wq_obs_meta.upload_id = wq_obs.upload_id and wq_obs_meta.param_short_name = wq_obs.param_short_name) as reference_datum,
	*/
				$timequery = "select
	(to_timestamp(value)::timestamp(0) without time zone at time zone 'UTC' at time zone '$local_tz_name') as local_timestamp
	from
	(select
	(select location_id from events where events.event_id = wq_obs.event_id) as location_id,
	value
	from wq_obs
	where param_short_name = 'time') foo where location_id = '$location_id' and value is not null;";

				$timecheck = pg_query($con,$timequery);
				$timechecknum = pg_num_rows($timecheck);

				if ($timechecknum == 0) {
					$min = 'No data';
					$max = 'No data';
				} else {
					$timecheck_array = pg_fetch_all_columns($timecheck);
					$min = min($timecheck_array);
					$max = max($timecheck_array);
				}

				pg_free_result($timecheck);

	/*
	(select local_timezone from events where events.event_id = wq_obs.event_id) as local_timezone,
	(select reference_datum from wq_obs_meta where wq_obs_meta.upload_id = wq_obs.upload_id and wq_obs_meta.param_short_name = wq_obs.param_short_name) as reference_datum,
	*/
				$dataquery = "select location_id, local_timestamp, param_short_name, value
	from (
	select * from
	(select
	rowgroup_id,
	(to_timestamp(value)::timestamp(0) without time zone at time zone 'UTC' at time zone '$local_tz_name') as local_timestamp
	from
	(select
	rowgroup_id,
	value
	from wq_obs
	where param_short_name = 'time') a ) b
	full join
	(select * from
	(select
	rowgroup_id,
	event_id,
	upload_id,
	(select location_id from events where events.event_id = wq_obs.event_id) as location_id,
	param_short_name,
	value,
	(select distinct unit_code from wq_obs_meta where wq_obs_meta.upload_id = wq_obs.upload_id and wq_obs_meta.param_short_name = wq_obs.param_short_name) as unit_code
	from wq_obs
	where $var_where) c ) d
	using (rowgroup_id)
	) e where location_id = '$location_id' and (local_timestamp >= '$startdatetime' and local_timestamp <= '$enddatetime') order by local_timestamp;";

		$_SESSION['dataquery'] = $dataquery;

				$datacheck = pg_query($con,$dataquery);
				$num = pg_num_rows($datacheck);

				$test = pg_fetch_all($datacheck);
				$data_array = pg_fetch_all($datacheck);
				$filtered = array_filter($data_array,function($row) { return $row['value'] != NULL; });

				if ( empty($filtered) ) {//$num==0
					array_push($errors,"Range for $var is $min to $max."); //for debugging: $var_where $startdatetime $enddatetime $locationid
				} else {
					$idx = 0;
					while ($row = pg_fetch_array($datacheck)) {
						$vars_array[$idx]['local_timestamp'] = $row['local_timestamp'];
						if (empty($row['value']) && $row['value'] != 0 && $row['value'] != "0") {
							$row['value'] = 'NULL';
						}
						$vars_array[$idx][$var] = $row['value'];
						$idx++;
					} //while datacheck
				} //if else no rows returned

				pg_free_result($datacheck);

			} // end if not time
		} // for every var
	} else { //else varmenu was empty
		$errors[] = 'No variables were selected.';
	}
} //if submitted

?>
<script type="text/javascript">
    var errorString = "<? if (!empty($errors)) { echo 'Sorry, either you forgot to select a time range and/or any variables, or at least one variable you selected has no data in the time range you selected. Any variables returning null will be listed below, along with their data time ranges. Note that there may be gaps within these periods. Please adjust your selection and try again.\\n\n'; foreach ($errors as $msg) { echo "$msg \\n\\n"; } echo 'Click OK to return to selection.';} ?>";
    if (errorString != "") { alert(errorString); window.close(); }
</script>

<link rel="stylesheet" href="css/global.css" />
<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

</head>

<body>
<div id="container" class="container">
		<div class="header">
			<p>
			<a href="http://seagrant.mit.edu/ecosys_health.php"><img src="https://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div class="subNav"></div>
			</p>
		</div>

<?
//ini_set('display_errors',1);
//echo $_SESSION['dataquery']; print_r($vars_array); print_r(pg_num_rows($datacheck)); //

$fileprefix = mt_rand();
$filename = $fileprefix.'.csv';
$filepath = '/mnt/var-www/mit/waterquality/tmp/'.$filename;

$handle = fopen($filepath, 'w');

$header_array = array_keys($vars_array[0]);
$header_string = implode(",",$header_array);
$header_line = $header_string."\n";
fwrite($handle,$header_line);

$htmltable = "<table border width=\"98%\" border=\"1\" align=\"center\" cellpadding=\"3\" cellspacing=\"0\" style=\"font-size:.8em;\"><tr>";

foreach ($header_array as $k => $v) {
	$htmltable .= "<td><b>$v</b></td>";
}

$htmltable .= "</tr>";

for ($i = 0; $i <= max(array_keys($vars_array)); $i++) {
	$data_array = NULL;
	$data_array = $vars_array[$i];
	$data_string = implode(",",array_values($data_array));
	if ($i != max(array_keys($vars_array))) {
		$data_line = $data_string."\n";
	} else {
		$data_line = $data_string;
	}
	fwrite($handle,$data_line);

	$htmltable .= "<tr>";
	foreach ($data_array as $k => $v) {
		if (empty($v) && $v != 0 && $v != "0") {
			$htmltable .= "<td>NA</td>";
		} else {
			$htmltable .= "<td>$v</td>";
		}
	}
	$htmltable .= "</tr>";

}

$htmltable .= "</table>";

fclose($handle);

chgrp("$filepath","www-drops");
chmod("$filepath",0775);

$vars_units_string = implode(",",$varmenu);

$plr_query = "select graph_water_vars('$fileprefix','$timepair','$vars_units_string','$location_name');";
$plr_exec = pg_query($con,$plr_query);
//select graph_water_vars('1853212064','2012-10-03 13:29:00,2012-10-12 23:59:59','cond@U00B5-S-U002F-cm,depth@m,lat@U00B0-N,lon@U00B0-E,PAR@U00B5-mol-U002F-s-U002F-m-U00B2','Bumpkin')
echo "<br>";


echo "<table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
<tr><td></td><td align=\"right\"><a href=\"javascript:window.close();\" style=\"font-size:.85em;\">CLOSE WINDOW</a></td></tr>
  <tr>
    <td valign=\"top\" style=\"width:200px;\">
	<p>Click the link below to download the <a href=\"#table\">data table</a>. If your browser displays the data, choose the \"save as\" option from your browser's \"file\" menu. Alternatively, right-click on the link (control-click on Mac) and choose the \"save as\" option in the menu that appears (\"download linked file as\" on Mac). To download each graph, simply click on the graph and,  with the window containing the graph active, click \"file -> save as\" from the browser menu.</p>
	<p><a href=\"tmp/$filename\" target=\"new\">Download Table</a><br /></div></td>

	<td align=\"center\">";

//var_dump($test);

//	$divheight = count($varmenu)*250;
	echo "<div font-size:.75em; border:1px solid #CCCCCC; \">";

	foreach ($varmenu as $v) {
		if ($var != 'time') {
			$vars_units = explode('@',$v);
			$var = $vars_units[0];
			$units = $vars_units[1];
			$pngpath = 'tmp/'.$fileprefix.$var.$units.'.png';
			echo "<a href= \"$pngpath\" target=\"_blank\"><img src=\"$pngpath\" width=\"600\" align=\"middle\" style=\"margin-right:10px;\"></img></a>";//height=\"$divheight\"
		}
	}

	echo "</div>";
	echo '</td>
  </tr>
</table>';

echo "<p style=\"font-size:.85em;\"><b>WATER QUALITY DATA RECORDED AT $location_name: $startdatetime to $enddatetime</b>";
echo "<a id=\"table\" name=\"table\">";
echo $htmltable."</p>";
?>
</div>

</body>

</html>


