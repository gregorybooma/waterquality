<?php
//ini_set('display_errors',1);

$nav_display=5;
include ('sess_start.php');
$user_id=$_SESSION['user_id'];
include ('sp_header.php');
include ('date_time_arrays.php');
?>

<link rel="stylesheet" href="css/global.css" />

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
//ACTIVATE BELOW WHEN SCHOOLS ACTUALLY HAVE SITES IN - SEE ALSO MAP.ZOOMTOSCALE BELOW
require_once ('/home/mapserver/connects/mitsg_connect.php');

	$program_id = 'SPX';
	if (!isset($_SESSION['sch_id'])) {
		$school_id = 'INDY';
		} else {
		$school_id = $_SESSION['sch_id'];
		}

/*	$site_check = pg_num_rows(pg_query($con,"select gid from locations where location_id like 'SPX%';"));

	if ($site_check==0) {
		$school_id='INDY';
		}
*/
/*	$query = "SELECT ST_XMin(ST_extent(location_geom)) as min_x,
				ST_YMin(ST_extent(location_geom)) as min_y,
				ST_XMax(ST_extent(location_geom)) as max_x,
				ST_YMax(ST_extent(location_geom)) as max_y
				FROM locations where location_id like 'SPX%';"; //FROM sites where sch_id = '$school_id';";
*/
	$query = "select st_xmin(st_extent(location_geom)) as min_x,
				st_ymin(st_extent(location_geom)) as min_y,
				st_xmax(st_extent(location_geom)) as max_x,
				st_ymax(st_extent(location_geom)) as max_y
				from locations
				where location_id in (
					select distinct location_id from events
					 where event_id in (select distinct event_id from wq_obs where wq_obs.event_id = events.event_id)
					 );";

	$result = pg_query($con,$query);

	$row = pg_fetch_array($result);
	$min_x=$row['min_x'];
	$min_y=$row['min_y'];
	$max_x=$row['max_x'];
	$max_y=$row['max_y'];

	if (isset($_GET['location_gid'])) {
		$location_id = $_GET['location_id'];
		$extent = $_GET['minx'].','.$_GET['miny'].','.$_GET['maxx'].','.$_GET['maxy'];
		} else {
		$extent = $min_x.','.$min_y.','.$max_x.','.$max_y;
		}
//*/
?>
<script src="http://maps.google.com/maps?file=api&v=2&key=ABQIAAAAq7VWGZUygnzwIzY-mJTQ2RQbUDuIRq5_4iBwPowyQsaHlwElShT4NSjR2Cu2Oabz4FneOXqoYZ6Ykg" type="text/javascript"></script>

     <script src="./scripts/OpenLayers-2.9.1/OpenLayers.js"></script>
     <script src="./scripts/proj4js/lib/proj4js-compressed.js"></script>
     <script src="./scripts/proj4js/lib/defs/EPSG4326.js"></script>
     <script src="./scripts/proj4js/lib/defs/EPSG3395.js"></script>
     <script src="./scripts/proj4js/lib/defs/EPSG900913.js"></script>

     <script type="text/javascript">


         var map;
         var proj4326 = new OpenLayers.Projection("EPSG:4326");
         var proj3395 = new OpenLayers.Projection("EPSG:3395");
         var projGoogle = new OpenLayers.Projection("EPSG:900913");
         var extent = new OpenLayers.Bounds(<?php echo $extent; ?>);
         //extent.transform(proj4326, proj3395);
         extent.transform(proj4326, projGoogle);
         //var maxextent = new OpenLayers.Bounds(-20037508.3428, -15496570.7397, 20037508.3428, 18764656.2314);
         var maxextent = new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34);

         function init(){

		var options = {
				projection: projGoogle,
				displayProjection: proj4326,
				units: "m",
				maxExtent: maxextent,
				controls: [new OpenLayers.Control.Navigation(),
                            new OpenLayers.Control.MousePosition(),
                            new OpenLayers.Control.Scale(),
                            new OpenLayers.Control.ScaleLine(),
                            new OpenLayers.Control.PanZoom(),
                            new OpenLayers.Control.LayerSwitcher(),
                            new OpenLayers.Control.Attribution()]
				};
				//maxResolution: null,fractionalZoom: true,
         var width = document.getElementById('map').clientWidth;
         //options.maxResolution = (options.maxExtent.right - options.maxExtent.left)/width;
         //options.maxResolution = 156543.0339;

             map = new OpenLayers.Map('map',options);

             var wmsurl = "https://mit.sea-grant.net/cgi-bin/sp.cgi?";

             var googlephys = new OpenLayers.Layer.Google("Google Terrain" ,
                 {type: G_PHYSICAL_MAP,sphericalMercator:true,maxZoomLevel:17});//, 'maxZoomLevel':17

			//disabling gmrt until time available for better cartography
			 /*var gmrturl = "http://gmrt.marine-geo.org/cgi-bin/mapserv?map=/public/mgg/web/gmrt.marine-geo.org/htdocs/services/map/wms_merc.map&";

             var gmrt = new OpenLayers.Layer.WMS("LDEO GMRT Topo",
                 gmrturl,
                 {layers:"GMRT",format:'image/png',srs:"EPSG:3395"},//
                 {attribution: "Topography: <a href=\"http://www.marine-geo.org/about/terms_of_use.php\" target=\"new\">MGDS<\/a>"},//, 'singleTile':true
                 {'isBaseLayer':true}
                 );
			*/
// THIS BLOCK GOOD FOR FILTERING BY SCHOOL, USER, ETC.; OR ON DATA PAGE TO SHOW ONLY SITE SELECTED ON MAP
             var filter = new OpenLayers.Filter.Comparison(
             		{
             		type: OpenLayers.Filter.Comparison.EQUAL_TO,
//             		property: "sch_id",
             		property: "location_id",
//             		value: "<?php echo $school_id; ?>"//"MITX"
             		value: "<?php echo $location_id; ?>"//"MITX"
             		});


preFeatureInsert = function(feature) {
    var src = new OpenLayers.Projection('EPSG:4326');
    //var dest = new OpenLayers.Projection('EPSG:3395');
    var dest = new OpenLayers.Projection('EPSG:900913');
    feature.geometry.transform(src, dest);
};

// ,new OpenLayers.Strategy.Cluster() -- save cluster for when numbers can appear in markers (see vector mayhem presentation)
var sp = new OpenLayers.Layer.Vector('WQ Locations',
     {
     strategies: [new OpenLayers.Strategy.Fixed()],
     protocol: new OpenLayers.Protocol.WFS({
                    url: wmsurl,
                    featureNS :  'http://mapserver.gis.umn.edu/mapserver',
                    version: '1.0.0',
                    srsName: 'EPSG:4326',
                    featureType: 'sp',
                    featurePrefix: 'ms',
                    extractAttributes: true
                    //filter: filter
                }),
     projection: proj4326
     //styleMap:new OpenLayers.StyleMap(site_style)
     },
     {preFeatureInsert: preFeatureInsert}
     );
			 map.addLayers([googlephys,sp]);//,gmrt

// TO IMPLEMENT: FOR SCALE, WHEN DYNAMIC EXTENT ACTIVATED ABOVE, CHECK TO SEE IF LESS THAN 7M. IF SO, DO ZOOM, ELSE DO NOTHING
             map.zoomToExtent(extent,false);
             //map.zoomToScale(2000000);

			 map.events.register('click', map, function (e) {

				OpenLayers.Util.getElement('nodeList').innerHTML="Loading...please wait...";
				// get reference lonlat to pass to results div
				 var click_pix = new OpenLayers.Pixel(e.xy.x, e.xy.y);
				 //var click_lonlat = map.getLonLatFromPixel(click_pix).transform(proj3395,proj4326);
				 var click_lonlat = map.getLonLatFromPixel(click_pix).transform(projGoogle,proj4326);
				     click_lon = click_lonlat.lon.toFixed(5); // no "var" = global
				     click_lat = click_lonlat.lat.toFixed(5);

				 // establish bbox for search tolerance
				 var tolerance = new OpenLayers.Pixel(5, 5);
				 var min_px = new OpenLayers.Pixel(e.xy.x - tolerance.x, e.xy.y + tolerance.y);
				 var max_px = new OpenLayers.Pixel(e.xy.x + tolerance.x, e.xy.y - tolerance.y);
				 var min_ll = map.getLonLatFromPixel(min_px);
				 var max_ll = map.getLonLatFromPixel(max_px);
				 //min_ll.transform(proj3395,proj4326);
				 //max_ll.transform(proj3395,proj4326);
				 min_ll.transform(projGoogle,proj4326);
				 max_ll.transform(projGoogle,proj4326);


				 var url =  wmsurl + 'SERVICE=WFS&VERSION=1.0.0&REQUEST=GetFeature&MAXFEATURES=999&TYPENAME=sp&SRS=' + map.projection + '&BBOX='+min_ll.lon+','+min_ll.lat+','+max_ll.lon+','+max_ll.lat;
				//$('nodeList').innerHTML = url;

				 OpenLayers.loadURL(url, '', this, setHTML);
				 OpenLayers.Event.stop(e);
			});

			function setHTML(response){
				 var doc = response.responseXML;
				 var nodes =  OpenLayers.Util.getNodes(doc, "gml:featureMember");
				 var content = "<b>Sites near "+click_lon+", "+click_lat+":<\/b><hr />";
				 var ft = new Array('sp');

				 if (nodes.length > 0 ){
					 for (var i = 0; i < nodes.length; i++) {
						var nd = null;
						for (j = 0; j < ft.length; j++){
							nd = OpenLayers.Util.getNodes(nodes[i],"ms:" + ft[j])[0];
							if (nd != null){
								break;
							}
						}

						var location_id = OpenLayers.Util.getTagText(nd, "ms:location_id");
						var location_name = OpenLayers.Util.getTagText(nd, "ms:location_name");
						var location_gid = OpenLayers.Util.getTagText(nd, "ms:location_gid");
						var sitex = OpenLayers.Util.getTagText(nd, "ms:longitude");
						var sitey = OpenLayers.Util.getTagText(nd, "ms:latitude");

						var bbox_minx = parseFloat(sitex) - 0.01;//6;
						var bbox_miny = parseFloat(sitey) - 0.01;//4;
						var bbox_maxx = parseFloat(sitex) + 0.01;//6;
						var bbox_maxy = parseFloat(sitey) + 0.01;//4;

//						var schimg = "navy_logo.jpg";

						<? if (!isset($_GET['location_id'])) { echo 'content += "<a href=\"https://mit.sea-grant.net/waterquality/data_view.php?location_id="+location_id+"&location_gid="+location_gid+"&minx="+bbox_minx+"&miny="+bbox_miny+"&maxx="+bbox_maxx+"&maxy="+bbox_maxy+"\" >"+location_name+"</a> ["+location_name+"] <br />";'; }?>
					 }
					 OpenLayers.Util.getElement('nodeList').innerHTML = content;
				 }
				 else {OpenLayers.Util.getElement('nodeList').innerHTML = "No results found";}
			}
         }

     </script>

<!-- END OF MAP ############################################################# -->

</head>

<body onLoad="init()">
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
				<?php include ('nav_col.php');?>
			</td>
			<td width="500"><div id="map" style="color:#333333; font-size:.85em;"></div><p style="font-size:.85em; text-align:center;">Click and drag while holding shift to zoom.</p></td>
			<td ><div id="data">
        		<h3 style="margin-top:0px; padding-top:0px;">Locations</h3>
				<? if (!isset($_GET['location_gid'])) { echo '<p>Click on points for site-specific information. Zoom-in for better accuracy.</p>'; } ?>
        		<div id="nodeList"></div>

			<? if (isset($_GET['location_gid'])) {
				require_once ('/home/mapserver/connects/mitsg_connect.php');

$location_gid = $_GET['location_gid'];
$location_id = $_GET['location_id'];

	$locationquery = "select distinct
location_id,
organization_id,
location_name,
(select st_x(location_geom) from locations where locations.location_id = location_instances.location_id) as longitude,
(select st_y(location_geom) from locations where locations.location_id = location_instances.location_id) as latitude
from location_instances
where location_id = '$location_id';";

	$row = pg_fetch_array(pg_query($locationquery));
	$location_name=$row['location_name'];
	$longitude=$row['longitude'];
	$latitude=$row['latitude'];
	$organization_id=$row['organization_id'];

	//this gets json and seems ok:
	$request = 'https://maps.googleapis.com/maps/api/timezone/json?location='.$latitude.','.$longitude.'&timestamp='.time().'&sensor=false';
	$response = @file_get_contents($request);
	if (empty($response)) { $errors[] = 'An error occurred (API error). Please try again and contact MIT Sea Grant if the problem persists.'; exit; }

	$response_array = json_decode($response,TRUE);

	$local_tz_name = $response_array['timeZoneId'];
/*
(select local_timezone from events where events.event_id = wq_obs.event_id) as local_timezone,
(select reference_datum from wq_obs_meta where wq_obs_meta.upload_id = wq_obs.upload_id and wq_obs_meta.param_short_name = wq_obs.param_short_name) as reference_datum,
*/
	$timequery = "select
min(to_timestamp(value)::timestamp(0) without time zone at time zone 'UTC' at time zone '$local_tz_name') as min,
max(to_timestamp(value)::timestamp(0) without time zone at time zone 'UTC' at time zone '$local_tz_name') as max
from
(select
value,
(select distinct location_id from events where events.event_id = wq_obs.event_id) as location_id
from wq_obs
where param_short_name = 'time') foo where location_id = '$location_id';";

	$timecheck = pg_query($con,$timequery);
	$timechecknum = pg_num_rows($timecheck);

	if ($timechecknum == 0) {
		$min_date = 'No data';
		$max_date = 'No data';
	} else {
		$min_date = pg_fetch_all_columns($timecheck,0);
		$max_date = pg_fetch_all_columns($timecheck,1);
	}

echo "<hr /><p>Selected site:</p>
	<p>Site name:<br>$location_name<br>
	Lon: {$longitude}<br />
    Lat: {$latitude}<br />
	Affiliation: <br>$organization_id</p>

Available data period:<br>
<table width=\"200\" border=\"0\" cellspacing=\"3\" cellpadding=\"0\">
<tr><td align=\"right\">Start:</td><td width=\"80%\"> {$min_date[0]}</td></tr>
<tr><td align=\"right\">End:</td><td width=\"80%\"> {$max_date[0]}</td></tr>
</table>";

$param_array = array();
$param_result = pg_query($con,"select * from (select distinct param_short_name,
(select param_long_name from wq_params where wq_obs_meta.param_short_name = wq_params.param_short_name) as param_long_name,
unit_code,
(select unit_abbrev from wq_units where wq_obs_meta.unit_code = wq_units.unit_code) as unit_abbrev,
(select distinct event_id from wq_obs where wq_obs_meta.upload_id = wq_obs.upload_id) as event_id
from wq_obs_meta) foo
where
foo.event_id in (select event_id from events where location_id = '$location_id') order by param_long_name;");

while($row = pg_fetch_array($param_result)) {
	if ($row['param_short_name'] != 'time') {

		// need to check if param really exists -- might not if null column uploaded, or values all deleted
		$nullparamcheckquery = "select * from (
select
event_id,
upload_id,
param_short_name,
value,
(select unit_code from wq_obs_meta where wq_obs_meta.upload_id = wq_obs.upload_id and wq_obs_meta.param_short_name = wq_obs.param_short_name) as unit_code
from wq_obs) foo
where
foo.param_short_name = '".$row['param_short_name']."'
and foo.unit_code = '".$row['unit_code']."'
and foo.event_id in (select event_id from events where location_id = '".$location_id."')
and foo.value is not null;";

		$nullparamcheckresult = pg_query($con,$nullparamcheckquery);
		if (pg_num_rows($nullparamcheckresult) != 0) {
			$key = $row['param_short_name'].'@'.$row['unit_code'];
			$value = $row['param_long_name'].' ('.$row['unit_abbrev'].')';
			$param_array[$key] = $value;
		}
	} // if not time
} //while param_result

		echo "<form action=\"sitedata.php?start=$min_date[0]&end=$max_date[0]\"  method=\"post\" target=\"sitedata_window\" onsubmit=\"window.open('', 'sitedata_window', 'width=1024,status=no,resizable=yes,scrollbars=yes')\" >";

		echo "Select a start date:";
						echo "<table width=\"200\" border=\"0\" cellspacing=\"3\" cellpadding=\"0\">
						<tr>
						<td><select name=\"startmonth\">
						  <option value=\"\">Month</option>";
						  foreach ($months as $k => $startm) {
							echo "<option value=\"$k\">$startm</option>\n";
						  }
						echo "</select>
						  <select name=\"startday\">
							<option value=\"\">Date</option>";
							foreach ($days as $startd) {
							echo "<option value=\"$startd\">$startd</option>\n";
							}
						echo "</select>
						  <select name=\"startyear\">
							<option value=\"\">Year</option>";
							foreach ($years as $starty) {
							echo "<option value=\"$starty\">$starty</option>\n";
							}
						  echo "</select></td>
						</tr>
				</table>";
		 echo "Select an end date:";
						echo "<table width=\"200\" border=\"0\" cellspacing=\"3\" cellpadding=\"0\">
						<tr>
						<td><select name=\"stopmonth\">
						  <option value=\"\">Month</option>";
						  foreach ($months as $k => $stopm) {
							echo "<option value=\"$k\">$stopm</option>\n";
						  }
						echo "</select>
						  <select name=\"stopday\">
							<option value=\"\">Date</option>";
							foreach ($days as $stopd) {
							echo "<option value=\"$stopd\">$stopd</option>\n";
							}
						echo "</select>
						  <select name=\"stopyear\">
							<option value=\"\">Year</option>";
							foreach ($years as $stopy) {
							echo "<option value=\"$stopy\">$stopy</option>\n";
							}
						  echo "</select></td>
						</tr>
				</table>";

		echo "<input type=\"hidden\" name=\"location_id\" value=\"$location_id\">
		<input type=\"hidden\" name=\"location_name\" value=\"$location_name\">
		<input type=\"hidden\" name=\"site_x\" value=\"$longitude\">
		<input type=\"hidden\" name=\"site_y\" value=\"$latitude\">
		<input type=\"hidden\" name=\"organization_id\" value=\"$organization_id\">";

		echo "Select available variables:<br>";
		echo "<select name=\"varmenu[]\" multiple size=5 style=\"width:200px;\">";

			foreach ($param_array as $k => $v) { echo "<option value=\"$k\">$v</option>\n"; }

		echo "</select><br />";

		echo "<input type=\"submit\" name=\"submit\" value=\"Get Data\">";
		echo "</form>";


echo "<hr /><p><a href=\"data_view.php\">Reset Map</a></p>";



			 }
pg_close();
			 ?>

		</div>
		</td>
		  </tr>
		</table>
	</div>
</div>
</body>
</html>
