<?php
//ini_set('display_errors', '1');
session_name ('YourVisitID');
session_start(); // Start the session.

require_once('/home/mapserver/connects/mitsg_connect.php');

//if no site_ids param, then only the mapextent session var is set and the script exits

$_SESSION['mapextent'] = $_GET['mapextent'];

if (isset($_GET['site_ids'])) {

	$site_id_sel_string = NULL;
	$polygeom = NULL;

	$site_id_sel_string = $_GET['site_ids'];
	$polygeom = $_GET['polygeom'];

	if (empty($site_id_sel_string) && empty($polygeom)) {
		$_POST=NULL;
		$_SESSION['sites_selected']=$_SESSION['site_id_all_string'];//NULL;
		unset($_SESSION['polysites']);

		echo "<p><b>Sites:</b><br>No sites found.";//selected. All sites will be used.";
		echo "<p><input type=\"submit\" name=\"refresh\" value=\"Refresh map\">";// or ";
		//echo "<input type=\"submit\" name=\"get\" value=\"Get selection\"></p></form>";
	} else {

		if (!empty($polygeom)) {
			unset($_SESSION['sites_selected']);
			if (empty($site_id_sel_string)) {
				$site_id_sel_string = $_SESSION['site_id_all_string'];
			}
			$query = "select gid from locations where ST_Within(ST_Transform(locations.location_geom,900913),ST_MPolyFromText('".$polygeom."',900913)) and gid in (".$site_id_sel_string.");";
			$result = pg_fetch_all_columns(pg_query($query));
			$sites_within = implode(",",$result);

			if (!empty($sites_within)) {
				if (!empty($_SESSION['polysites'])) {
					$site_id_sel_string = $_SESSION['polysites'].",".$sites_within;
					$_SESSION['polysites'] = $site_id_sel_string;
				} else {
					$site_id_sel_string = $sites_within;
					$_SESSION['polysites'] = $sites_within;
				}
			} else {
				if (!empty($_SESSION['polysites'])) {
					$site_id_sel_string = $_SESSION['polysites'];
				} else {
					$site_id_sel_string = "";
				}
			}

			//$_SESSION['polysites'] = $site_id_sel_string;
			$_SESSION['sites_selected'] = $site_id_sel_string;

		} else {
			unset($_SESSION['polysites']);
			unset($_SESSION['sites_selected']);
			$_SESSION['sites_selected'] = $site_id_sel_string;
		} //if/else polygeom not empty

		if (empty($site_id_sel_string)) {
			$_POST=NULL;
			unset($_SESSION['sites_selected']);
			$_SESSION['sites_selected']=$_SESSION['site_id_all_string'];//NULL;
			unset($_SESSION['polysites']);

			echo "<p><b>Sites:</b><br>No sites found.";//selected. All sites will be used.";
			echo "<p><input type=\"submit\" name=\"refresh\" value=\"Refresh map\">";// or ";
			//echo "<input type=\"submit\" name=\"get\" value=\"Get selection\"></p></form>";
		} else {

			$extent_query = "SELECT
						ST_XMin(ST_extent(ST_transform(location_geom,900913))) as min_x,
						ST_YMin(ST_extent(ST_transform(location_geom,900913))) as min_y,
						ST_XMax(ST_extent(ST_transform(location_geom,900913))) as max_x,
						ST_YMax(ST_extent(ST_transform(location_geom,900913))) as max_y
						FROM locations
						where gid in ($site_id_sel_string)
						;";//

			$result = pg_query($con,$extent_query);

			$row = pg_fetch_array($result);

			$min_x=$row['min_x'];
			$min_y=$row['min_y'];
			$max_x=$row['max_x'];
			$max_y=$row['max_y'];

			$mapextent = $min_x.','.$min_y.','.$max_x.','.$max_y;

			$_SESSION['mapextent'] = $mapextent;

			pg_free_result($result);

			$query = "select * from (select
			location_id,
			location_name,
			program_id,
			(select distinct gid from locations where locations.location_id = location_instances.location_id) as gid,
			(select distinct maj_admin_region from locations where locations.location_id = location_instances.location_id) as maj_admin_region
			from location_instances) a
			where a.gid in ($site_id_sel_string) order by a.location_name;
		";

			$result = pg_query($query);

			echo "<p><b>Sites:</b><br><br><font face=\"arial\" size=\"-1\">";// (<a href=\"javascript:sites_reset()\">reset to all</a>):</b><br><font face=\"arial\" size=\"-1\">";
			while ($row = pg_fetch_array($result)) {
				echo "<a href=\"explore.php?gid=".$row['gid']."&location_id=".$row['location_id']."\">".$row['location_name']."</a> [".$row['program_id']."]<br><br>";//
			}
			echo "</font>";//</p>";
			echo "<input type=\"submit\" name=\"refresh\" value=\"View Selected\"> or <a href=\"explore\">Reset to All Sites</a>";
			//echo "<input type=\"submit\" name=\"get\" value=\"Get selection\"></form>";

			pg_free_result($result);

		} //if/else siteids empty
	} //if/else siteids and polygeom empty
} else {
	$_SESSION['polysites'] = NULL;
}// end if get site_ids

?>