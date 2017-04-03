<!DOCTYPE html>

<html lang="en">

<head>
<title>MIT Sea Grant Water Quality Data Service</title>
<meta charset="utf-8" />
<META HTTP-EQUIV="expires" CONTENT="Fri, 26 Feb 2001 09:23:47 GMT">
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">

<?php

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

if (isset($_POST['submit'])) {

	require_once ('/home/mapserver/connects/mitsg_connect.php');

	if (!empty($_POST['prog_loc_id'])) {
		$prog_loc_id=$_POST['prog_loc_id'];
		} else {
		$prog_loc_id="";
		}
	$source_latitude = $_POST['source_latitude'];
	$source_longitude = $_POST['source_longitude'];
	$latitude = $_POST['latitude'];
	$longitude = $_POST['longitude'];
	if (empty($latitude)) {
		$errors[] = 'You forgot to select a point on the map.';
	}
	if (empty($_POST['location_name'])) {
		$errors[] = 'You forgot to enter a Sitename.';
	} else {
		$location_name = escape_data($_POST['location_name']);
	}

/*	if (empty($_POST['sitetype'])) {
		$errors[] = 'You forgot to enter a Site type.';
	} else {
		$st = escape_data($_POST['sitetype']);
	}
*/
/*	if (empty($_POST['program_id'])) {
		$errors[] = 'You forgot to enter a survey initiative.';
	} else {
		$program_id = $_POST['program_id'];
	}

	$ini = strtolower($program_id);
	$program_locations = $ini.'_locations';
*/
//	$ini = strtolower($program_id);
	$program_id = 'SPX'; //this is the one to use but leaving others to cover legacy -- for now...
	$project = 'SPX';
	$initiative = 'SPX';
	$program = 'spx'; //this is the one to use but leaving ini to cover legacy -- for now...
	$ini = 'spx';

//to create site ids, explode on '.' and substring the decimals, then paste together again
	$date = date('ymd');

	if (($latitude==0) && ($longitude==0)) {
		$quad='0';
		} else if (($latitude>0) && ($longitude>0)) {
		$quad='1';
		} elseif (($latitude>0) && ($longitude<0)) {
		$quad='2';
		} elseif (($latitude<0) && ($longitude<0)) {
		$quad='3';
		} else {
		$quad='4';
		}

	$temp=explode('.',$latitude);
	$latdeg=$temp[0];
	$latdeg=str_ireplace("-","",$latdeg);
	if (strlen($latdeg)==2) {
		$latdeg=$latdeg;
		} else {
		$latdeg='0'.$latdeg;
		}

	$temp=explode('.',$longitude);
	$londeg=$temp[0];
	$londeg=str_ireplace("-","",$londeg);
	if (strlen($londeg)==2) {
		$londeg='0'.$londeg;
		} else if (strlen($londeg)==1) {
		$londeg='00'.$londeg;
		} else {
		$londeg=$londeg;
		}

	$temp=explode('.',$latitude);
	$latdec=$temp[1];
	if (strlen($latdec)==1) {
		$latdec=$latdec.'0';
		}
	if (empty($latdec)) {
		$latdec='00';
		}
	$temp=explode('.',$longitude);
	$londec=$temp[1];
	if (strlen($londec)==1) {
		$londec=$londec.'0';
		}
	if (empty($londec)) {
		$londec='00';
		}

	$latstr=$latdeg.substr($latdec,0,2);
	$lonstr=$londeg.substr($londec,0,2);

	$counter = 1;
	$goflag = 1;
	while ($goflag == 1) {
		$thecount = 1000 + $counter;
		$countstring = substr($thecount,-3);
		$location_id = $program_id.$quad.$latstr.$lonstr.$countstring;
		$result = pg_query($con,"select * from locations where location_id = '$location_id';");
		if (pg_num_rows($result) == 0) {
			$goflag = 0;
			} else {
			$counter = $counter + 1;
		}
	}

	$source_longlat_srs = "EPSG:3857";

	$denom = escape_data($_POST['mapscale']);
	if ($denom > 27000) { $errors[] = 'You are trying to define a point at too small a map scale. Please zoom-in to a scale of 1:27000 or larger and click on your site again.'; }
	$xy_scale = '1:'.$denom;

	$wxResult = str_replace( "\r\n", '<br />', $_POST['loc_desc'] );
	$wxResult2 = escape_data("$wxResult");
	$loc_desc = str_replace( '<br />', "\r", "$wxResult2" );

	if (empty($errors)) {

		$query_locations = "INSERT INTO locations (

location_id,
geog_description,
location_comments,
georef_source,
source_longitude,
source_latitude,
source_longlat_srs,
source_mapscale,
georef_process,
location_geom,
create_user,
mod_user,
georeferenced_by

		) VALUES (

'$location_id',
'$loc_desc',
'$loc_desc',
'Google Maps API v2 G_HYBRID_MAP',
$source_longitude,
$source_latitude,
'$source_longlat_srs',
'$xy_scale',
'Heads-up digitized in OpenLayers using the Google Maps G_PHYSICAL_MAP type as a base layer. Coordinates are obtained in the default SRS (EPSG:3857) and projected to EPSG:4326 using the proj4js javascript library on the client prior to writing to the database (PostGIS.)',
ST_GeomFromText('POINT($longitude $latitude)',4326),
'$user_id',
'$user_id',
'$user_id'

		);";	//sitetype,'$st',

	$result_locations = pg_query($con, $query_locations);
if (!($result_locations)) { $errors[] = 'The locations query did not work'; }
		$query_locs = "INSERT INTO location_instances (

location_id,
organization_id,
program_id,
prog_loc_id,
location_name,
geog_description,
create_user,
mod_user

		) VALUES (

'$location_id',
'MITSG',
'SPX',
'$prog_loc_id',
'$location_name',
'$loc_desc',
'$user_id',
'$user_id'

		);";	//default_habitat,'$st',

	$result_locs = pg_query($con, $query_locs);
if (!($result_locs)) { $errors[] = 'The location_instances query did not work'; }
	if ($result_locations && $result_locs) {
	            $url = 'https://mit.sea-grant.net/waterquality/event_params.php';
				header("Location: $url");
				//exit();
	   }

	} // end errors IF

} // end submitted IF
?>

<? $_SESSION['prev_page']= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];  ?>

<script type="text/javascript">
    var errorString = "<?php if (!empty($errors)) { echo 'ERROR \\n\n'; foreach ($errors as $msg) { echo "$msg \\n"; } } ?>";
    if (errorString != "") { alert(errorString); }
</script>

<link rel="stylesheet" href="css/global.css" />
<!--[if lt IE 9]>
	<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<style type="text/css">
 #map {
	 width: 500px;
	 height: 500px;
	 border: 1px solid gray;
 }

 #data {
 padding:10px;
  font-size:.75em;
 }

</style>

<!-- HERE IS THE MAP ######################################################################## -->

<?php

require_once ('/home/mapserver/connects/mitsg_connect.php');

	$query = "SELECT ST_X(ST_AsText(ST_Transform(ST_Centroid(ST_Collect(location_geom)),3857))) as cent_x,
			ST_Y(ST_AsText(ST_Transform(ST_Centroid(ST_Collect(location_geom)),3857))) as cent_y
			FROM locations where st_y(location_geom) !=0 and st_y(location_geom) is not null;";

	$result = pg_query($query);

	$row = pg_fetch_array($result);
	$cent_x=$row['cent_x'];
	$cent_y=$row['cent_y'];

	$center = $cent_x.','.$cent_y;

	pg_free_result ($result); // Free up the resources.


?>

<script src="https://maps.google.com/maps/api/js?v=3&amp;sensor=false" type="text/javascript"></script>

     <script src="/scripts/OpenLayers-2.13.1/lib/OpenLayers.js"></script>
     <script src="/scripts/OpenLayers-2.13.1/lib/deprecated.js"></script>
     <script src="/scripts/proj4js/lib/proj4js-compressed.js"></script>
     <script src="/waterquality/scripts/proj4js/lib/defs/EPSG4326.js"></script>
     <script src="/waterquality/scripts/proj4js/lib/defs/EPSG3395.js"></script>
     <script src="/waterquality/scripts/proj4js/lib/defs/EPSG900913.js"></script>

     <script type="text/javascript">
         //OpenLayers.ProxyHost = "/home/www/cgi-bin/proxyhost.cgi?url=";

            OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
                defaultHandlerOptions: {
                    'single': true,
                    'double': false,
                    'pixelTolerance': 0,
                    'stopSingle': false,
                    'stopDouble': false
                },

                initialize: function(options) {
                    this.handlerOptions = OpenLayers.Util.extend(
                        {}, this.defaultHandlerOptions
                    );
                    OpenLayers.Control.prototype.initialize.apply(
                        this, arguments
                    );
                    this.handler = new OpenLayers.Handler.Click(
                        this, {
                            'click': this.trigger
                        }, this.handlerOptions
                    );
                },

                trigger: function(e) {

                    var mapscale = Math.round(map.getScale());
                    var lonlat = map.getLonLatFromPixel(e.xy);
                    var source_lon = lonlat.lon;
                    var source_lat = lonlat.lat;
                    lonlat.transform(projGoogle,proj4326);
                    var content = '<p><strong>Latitude: </strong>'+lonlat.lat.toFixed(6)+'<input type="hidden" name="source_latitude" value="'+source_lat.toFixed(6)+'"></input><input type="hidden" name="latitude" value="'+lonlat.lat.toFixed(6)+'"></input><br><strong>Longitude: </strong>'+lonlat.lon.toFixed(6)+'<input type="hidden" name="source_longitude" value="'+source_lon.toFixed(6)+'"></input><input type="hidden" name="longitude" value="'+lonlat.lon.toFixed(6)+
                    				'"></input><input type="hidden" name="mapscale" value="'+mapscale+'"><br><strong>Map scale:</strong> 1:'+mapscale+'</input><br></p>';
                    OpenLayers.Util.getElement('sidediv').innerHTML = content;
                }

            });
         var map;
         var proj4326 = new OpenLayers.Projection("EPSG:4326");
         var projGoogle = new OpenLayers.Projection("EPSG:900913");
         //var extent = new OpenLayers.Bounds(<?php echo $extent; ?>);
         var center = new OpenLayers.LonLat(<?php echo $center; ?>);
         //extent.transform(proj4326,projGoogle);
         var maxextent = new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34);
         function init(){

             map = new OpenLayers.Map('map',{
							controls: [new OpenLayers.Control.Navigation(),
                            			new OpenLayers.Control.MousePosition(),
                            			new OpenLayers.Control.Scale(),
                            			new OpenLayers.Control.ScaleLine(),
                            			new OpenLayers.Control.PanZoomBar(),
                            			new OpenLayers.Control.LayerSwitcher(),
                            			new OpenLayers.Control.Attribution()],
                            displayProjection: proj4326,
							numZoomLevels:17,
							restrictedExtent: maxextent,
							maxExtent: maxextent
							});

             var wfs_url = "https://mit.sea-grant.net/cgi-bin/sp.cgi?";//"https://nereides.mit.edu/cgi-bin/mitis.cgi?";

    var gphy = new OpenLayers.Layer.Google(
        "Google Physical",
        {type: google.maps.MapTypeId.TERRAIN}
    );
             map.addLayers([gphy]);

             var affil_sites_filt = new OpenLayers.Filter.Comparison(
             		{
             		type: OpenLayers.Filter.Comparison.EQUAL_TO,
             		property: "organization_id",
             		value: "MITSG"
             		});

preFeatureInsert = function(feature) {
    var src = new OpenLayers.Projection('EPSG:4326');
    var dest = new OpenLayers.Projection('EPSG:900913');
    feature.geometry.transform(src, dest);
};

var affil_sites = new OpenLayers.Layer.Vector('Existing sites',
     {
     strategies: [new OpenLayers.Strategy.Fixed(),new OpenLayers.Strategy.Cluster()],
     protocol: new OpenLayers.Protocol.WFS({
                    url: wfs_url,
                    featureNS :  'http://mapserver.gis.umn.edu/mapserver',
                    version: '1.1.0',
                    srsName: 'EPSG:4326',
                    featureType: 'sp',
                    featurePrefix: 'ms',
                    extractAttributes: true,
                    defaultFilter: affil_sites_filt
                }),
     projection: proj4326
     //styleMap:new OpenLayers.StyleMap(site_style)
     },
     {preFeatureInsert: preFeatureInsert}
     );

			 map.addLayers([affil_sites]);

             map.setCenter(center,6);

                var click = new OpenLayers.Control.Click();
                map.addControl(click);
                click.activate();

         }

     </script>

<!-- END OF MAP ############################################################# -->

</head>

<body onLoad="init()">

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

		<table width="98%" border="0" cellspacing="5" cellpadding="0">
		  <tr valign="top" align="left">
			<td width="500"><div id="map" style="color:#333333; font-size:.85em;"></div><p style="font-size:.85em; text-align:center;">Click and drag while holding shift to zoom.</p></td>
			<td ><div id="data">

		<form action="site_add.php" method="post">

        		<h1 style="margin-top:0px; padding-top:0px;">Add a site</h1> ... or return to [<a href="event_params.php">Data entry page</a>]
        		<p>To add a site, pan and zoom the map, and click once with the mouse pointer at the site's location to set latitude and longitude. Then select a site name, an optional site-specific code, the survey initiative this site serves, the site substrate type, any notes about the site, and click "submit form" to add it to the database. Upon submission, you will be forwarded to the data entry page. Your organization's existing sites are shown for reference (though aggregated at small map scales). Map scale must be 1:27000 or larger (more zoomed-in) to be accepted. </p>

	   <table width="98%" border="0" align="left" cellpadding="0" cellspacing="5" >
		  <tr>
			<td colspan=2 align="left" valign="top"><div align="left"><p style="margin-top:0px; margin-bottom:10px;"><strong>Site Name</strong> <span style="color:#990000; font-style:italic; font-size:1em;">( required )</span></p></div></td>
		  </tr>
		  <tr>
			<td colspan=2 valign="top" align="left"><input name="location_name"  type="text" class="thin_border" style="width:100%;" value="<?php echo stripslashes($location_name); ?>"/></td>
		  </tr>
		  <tr>
			<td colspan=2 align="left" valign="top"><div align="left"><p style="margin-top:0px; margin-bottom:10px;"><strong>Site Code</strong> <span style="color:#990000; font-style:italic; font-size:1em;">( If you use your own unique codes for sites, enter here )</span></p></div></td>
		  </tr>
		  <tr>
			<td colspan=2 valign="top" align="left"><input name="prog_loc_id"  type="text" class="thin_border" style="width:100%;" value="<?php echo stripslashes($prog_loc_id); ?>"/></td>
		  </tr>
<!--		  <tr>
			<td colspan=2 align="left" valign="top"><div align="left"><p style="margin-top:0px; margin-bottom:10px;"><strong>Initiative</strong> <span style="color:#990000; font-style:italic; font-size:1em;">( required )</span></p></div></td>
		  </tr>
		  <tr>
			<td  colspan=2 valign="top"><select name="program_id">
          <option value="" style="color:#990000;">Select Initiative</option>
          <? echo '<option value="CHP"'; if ($program_id=='CHP') { echo 'selected="selected"'; } echo '>CHIMP</option>
          <option value="HHG"'; if ($program_id=='HHG') { echo 'selected="selected"'; } echo '>Hitchhiker\'s Guide</option>
          <option value="MMC"'; if ($program_id=='MMC') { echo 'selected="selected"'; } echo '>MIMIC</option>
          <option value="RDM"'; if ($program_id=='RDM') { echo 'selected="selected"'; } echo '>Recreational Diver Monitoring Program</option>
          <option value="RAS"'; if ($program_id=='RAS') { echo 'selected="selected"'; } echo '>RAS</option>'; ?>
          </select></td>
		  </tr> -->
		  <tr align="left" valign="top">
		    <td colspan="2"></td>
          </tr>
		  <tr valign="top">
		    <td align="left" valign="top" colspan=2><div id="sidediv"><p><strong>Latitude:</strong><br><strong>Longitude:</strong><br><strong>Map scale:</strong></div>
		    </td>
          </tr>
<!--		  <tr>
			<td colspan=2 align="left" valign="top"><div align="left"><p style="margin-top:0px; margin-bottom:10px;"><strong>Site Type</strong> <span style="color:#990000; font-style:italic; font-size:1em;">( required )</span></p></div></td>
		  </tr>
		  <tr>
		  <td  colspan=2 valign="top"><select name="sitetype">
          <option value="" style="color:#990000;">Select type</option>
          <? echo '<option value="cobble"'; if ($st=='cobble') { echo 'selected="selected"'; } echo '>cobble</option>
          <option value="dock"'; if ($st=='dock') { echo 'selected="selected"'; } echo '>dock</option>
          <option value="tidepool"'; if ($st=='tidepool') { echo 'selected="selected"'; } echo '>tidepool</option>
          <option value="other"'; if ($st=='other') { echo 'selected="selected"'; } echo '>other</option>
          '; ?>
          </select></td>
		  </tr>
-->
		  <tr>
		    <td colspan=2 align="left" valign="top"><strong>Site Notes</strong></td>
		    </tr>
		  <tr>
		    <td colspan=2 align="left" valign="top"><textarea name="loc_desc" rows="6" style="width:100%; margin-bottom:10px;"><?php echo $_POST['loc_desc']; ?></textarea></td>
		    </tr>
		</table>

	    <div style="width:100%; display:block; clear:both;"></div>

<!--<input name="tid" type="hidden" value="<? echo $tid; ?>">
<input name="vtid" type="hidden" value="<? echo $_GET['vtid']; ?>">-->

	<p style="margin-top:25px;"><input type="submit" name="submit" value="Submit Form"> ... or return to [<a href="event_params.php">Data entry page</a>]</p>
	</form>

			</div>
			</td>
		  </tr>
		</table>

<?php
//echo $program_locations.' '.$program_id.' '.$organization_id.' '.$prog_loc_id.' '.$location_id.' '.$user_id; if ($result_locations) { print_r($result_locations); }
//echo "<br>";
//echo $query_locations;
//echo "<br>";
//echo $query_locs;
?>
</div>
</div>
</body>
</html>
