<?php
//ini_set('display_errors', '1');
session_name ('YourVisitID');
session_start(); // Start the session.

require_once('/home/mapserver/connects/mitsg_connect.php');
$login_id = $_SESSION['login_id'];

include ('wq_header.php');
include ('date_time_arrays.php');

if ($_SESSION['prev_page'] == "https://mit.sea-grant.net/waterquality/") {
$_POST=NULL;
$_SESSION['mapextent']=NULL;
$_SESSION['sites_selected']=NULL;
$_SESSION['polysites']=NULL;
}
if ($_POST) {

	//if form submitted, assign selected variables and open data window
	if (isset($_POST['submit'])) { //get
		$_SESSION['posted_keys'] = array();
		foreach($_POST as $key => $value) {

			if ($key != "submit") {
				$_SESSION[$key] = NULL;
				$_SESSION[$key] = $value;
				$_SESSION['posted_keys'][] = $key;
			}

		}

		echo "<script type=\"text/javascript\">
		window.open('data.php?start={$_SESSION['min_date']}&end={$_SESSION['max_date']}', 'sitedata_window', 'width=1024,status=no,resizable=yes,scrollbars=yes')
		</script>";
		$site_id_sel_string = $_SESSION['sites_selected'];

	} else {
		//otherwise it's refreshing, so just set the selected sites
		$site_id_sel_string = $_SESSION['sites_selected'];

	} // it's refresh
} else { // there's no post

	if (!isset($_GET['gid'])) {

		//get all sites with obs and set map extent
		$site_id_sel_query = "
						select distinct gid,initiative
						from (
						select
						program_id as initiative,
						(select distinct gid from locations where locations.location_id = events.location_id) as gid
						from events
						where event_id in (select distinct event_id from wq_obs)
						) as foo
						;
						";

		$site_id_sel_array = pg_fetch_all_columns(pg_query($site_id_sel_query));
		$site_id_sel_string = implode(",",$site_id_sel_array);
		$_SESSION['site_id_all_string'] = $site_id_sel_string;
		$_SESSION['sites_selected'] = $_SESSION['site_id_all_string'];

		$mapextent_query = "
						select
						ST_XMin(ST_extent(ST_transform(location_geom,900913))) as min_x,
						ST_YMin(ST_extent(ST_transform(location_geom,900913))) as min_y,
						ST_XMax(ST_extent(ST_transform(location_geom,900913))) as max_x,
						ST_YMax(ST_extent(ST_transform(location_geom,900913))) as max_y
						from locations
						where gid in ($site_id_sel_string);
						";

		$result = pg_query($con,$mapextent_query);

		$row = pg_fetch_array($result);

		$min_x=$row['min_x'];
		$min_y=$row['min_y'];
		$max_x=$row['max_x'];
		$max_y=$row['max_y'];

		$extent_init = $min_x.','.$min_y.','.$max_x.','.$max_y;

		$_SESSION['mapextent'] = $extent_init;
	} else {
		//if a particular site selected, specify that site
		$site_id_sel_string = $_GET['gid'];
		$_SESSION['sites_selected'] = $site_id_sel_string;

	}//gid not set if/else
}// end no post

?>

	<link rel="stylesheet" href="css/explore.css">

	<script src="https://maps.google.com/maps/api/js?v=3&amp;sensor=false" type="text/javascript"></script>

     <script src="/scripts/OpenLayers-2.13.1/lib/OpenLayers.js"></script>
     <script src="/scripts/OpenLayers-2.13.1/lib/deprecated.js"></script>
     <script src="/scripts/proj4js/lib/proj4js-compressed.js"></script>
     <script src="/scripts/proj4js/lib/defs/EPSG4326.js"></script>
     <script src="/scripts/proj4js/lib/defs/EPSG3395.js"></script>
     <script src="/scripts/proj4js/lib/defs/EPSG900913.js"></script>

	<!--styles for openlayers map controls-->
     <style type="text/css">

	.olControlPanel {
		   margin: 0px;
		   padding: 0px;
		   background-color: #fff;
		   border-right: solid 1px #999;
		   border-bottom: solid 1px #999;
		}
    .olControlPanel div {
      display:block;
      width:  24px;
      height: 24px;
      float: left; /* remove this if you want a vertical toolbar  */
      margin: 2px;
    }
    .olControlPanel .olControlPanMapItemActive  {
      background-image: url("/scripts/OpenLayers-2.9.1/img/pan-on.png");
    }
    .olControlPanel .olControlPanMapItemInactive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/pan-off.png");
    }
    .olControlPanel .olControlZoomBoxItemInactive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/drag-rectangle-off.png");
    }
    .olControlPanel .olControlZoomBoxItemActive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/drag-rectangle-on.png");
    }
    .olControlPanel .olControlZoomToMaxExtentItemInactive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/zoom-world-mini.png");
    }
    .olControlPanel .olControlZoomOutBoxItemActive {
        background-image: url("/scripts/OpenLayers-2.9.1/img/zoom_out_on.png");
    }
    .olControlPanel .olControlZoomOutBoxItemInactive {
        background-image: url("/scripts/OpenLayers-2.9.1/img/zoom_out_off.png");
    }

	.olControlEditingToolbar {
		   width: 58px;
		   margin: 0px;
		   padding: 0px;
		   background-color: #fff;
		   border-left: solid 1px #999;
		   border-bottom: solid 1px #999;
		}
    .olControlEditingToolbar div {
      display:block;
	  background-image: url("/scripts/OpenLayers-2.9.1/theme/default/img/blank.gif");
      width:  58px;
      height: 24px;
      margin: 2px;
    }
        .olControlDrawFeaturePolygonItemActive {
            background-image: url("/scripts/OpenLayers-2.9.1/theme/default/img/draw_polygon_on.png");
            background-repeat: no-repeat;
        }
        .olControlDrawFeaturePolygonItemInactive {
            background-image: url("/scripts/OpenLayers-2.9.1/theme/default/img/draw_polygon_off.png");
            background-repeat: no-repeat;
        }
    .olControlEditingToolbar .olControlFeatureInfoItemActive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/query_mode_on.png");
      background-repeat: no-repeat;
    }
    .olControlEditingToolbar .olControlFeatureInfoItemInactive {
      background-image: url("/scripts/OpenLayers-2.9.1/img/query_mode_off.png");
      background-repeat: no-repeat;
    }

     </style>

<!-- construct the map -->
     <script type="text/javascript">
     	 var map,nav,navpanel,selectpanel,queryEventHandler;
     	 var idstring="<?php echo $_SESSION['sites_selected']; ?>";
     	 var site_ids = idstring.split(','); //
     	 var proj4326 = new OpenLayers.Projection("EPSG:4326");
     	 var proj3395 = new OpenLayers.Projection("EPSG:3395");
         var projGoogle = new OpenLayers.Projection("EPSG:900913");

         <? echo 'var extent_current = new OpenLayers.Bounds('.$_SESSION['mapextent'].');'; ?>
         //gmaps-specific max extent
         var maxextent = new OpenLayers.Bounds(-20037508.34, -20037508.34, 20037508.34, 20037508.34);
         var controls = [new OpenLayers.Control.ScaleLine()];//, new OpenLayers.Control.LayerSwitcher()

function init(){

             map = new OpenLayers.Map('map',{
                            displayProjection: proj4326,
                           'numZoomLevels':20,
                           maxExtent: maxextent,
                           restrictedExtent: maxextent,//extent_init,
                           controls: [] });//,

             map.addControls(controls);

             map.addControl(new OpenLayers.Control.MousePosition({ div: document.getElementById('mapMousePosition') }));
             map.addControl(new OpenLayers.Control.Scale('mapScale'));
             //document.getElementById('mapProjection').innerHTML = 'Coordinate system = '+projGoogle;

             // NAV TOOLS
             nav = new OpenLayers.Control.NavigationHistory();
             nav.previous.title = "Previous view";
             nav.next.title = "Next view";
             map.addControl(nav);

             zoomin = new OpenLayers.Control.ZoomBox({ title: "Drag box or click to zoom in" });
             zoomout = new OpenLayers.Control.ZoomBox({title: "Zoom out",displayClass: 'olControlZoomOutBox',out: true});
             pan = new OpenLayers.Control.DragPan({title:'Pan map', displayClass: 'olControlPanMap'});
             maxzoom = new OpenLayers.Control.ZoomToMaxExtent({title: "Zoom to max extent"});
             nav_prev = nav.previous;
             nav_next = nav.next;

             navpanel = new OpenLayers.Control.Panel({ defaultControl: zoomin });//
             nav_controls = [zoomin,zoomout,pan,maxzoom,nav_prev,nav_next];
             navpanel.addControls(nav_controls);

			//SELECT TOOLS
			// build the featureInfo control (query tool/button)
            queryEventHandler = new OpenLayers.Handler.Click({ 'map': map }, { 'click': function(e) { doGetFeatureInfo(e); } });

			featureinfo = new OpenLayers.Control({
				displayClass: "olControlFeatureInfo",
				title: "Select sites near a point"
				});

			// register events to the featureInfo control
			featureinfo.events.register("activate", featureinfo, function() {
				toggleQueryMode(); });
			featureinfo.events.register("deactivate", featureinfo, function() {
				toggleQueryMode(); });

			//initialize poly layer and control
			selectpoly = new OpenLayers.Layer.Vector("selectpoly", {onFeatureInsert: getPolyGeom});

			polycontrol = new OpenLayers.Control.DrawFeature(selectpoly,OpenLayers.Handler.Polygon,{title:'Select sites within a polygon',displayClass: 'olControlDrawFeaturePolygon'});//,multi: true

			//initialize select control panel
			selectpanel = new OpenLayers.Control.Panel({ displayClass: 'olControlEditingToolbar' });
			select_controls = [polycontrol,featureinfo];
			selectpanel.addControls(select_controls);

			//ensure only one control active at a time
			 for (var i in nav_controls) {
			 	nav_controls[i].events.register("activate",nav_controls[i],function(){deactivateControls(select_controls);})
			 }

			 for (var i in select_controls) {
			 	select_controls[i].events.register("activate",select_controls[i],function(){deactivateControls(nav_controls);})
			 }

             map.addControls(controls);
             map.addControl(navpanel);
             map.addControl(selectpanel);

	//set the map layers

             var wfsurl = "/cgi-bin/sp.cgi?";

    var gphy = new OpenLayers.Layer.Google(
        "Google Physical",
        {type: google.maps.MapTypeId.TERRAIN}
    );

    var roadmap = new OpenLayers.Layer.Google(
        "Google Roads",
        {type: google.maps.MapTypeId.ROADMAP}
    );
	map.addLayers([gphy,roadmap]);

	//google terrain doesn't go to as small a scale so switch if necessary
	assignBaseLayer = function() {
		mapscale = map.getScale();
		if (mapscale < 14000) {
			roadmap.setVisibility(true);
			map.setBaseLayer(roadmap);
			gphy.setVisibility(false);
		} else {
			gphy.setVisibility(true);
			map.setBaseLayer(gphy);
			roadmap.setVisibility(false);
		}
	}

/*	     var gmrturl = "https://gmrt.marine-geo.org/cgi-bin/mapserv?map=/public/mgg/web/gmrt.marine-geo.org/htdocs/services/map/wms_merc.map&";

             var gmrt = new OpenLayers.Layer.WMS("LDEO GMRT Topo",
                 gmrturl,
                 {version:"1.0.0",layers:"GMRT",srs:'EPSG:3395',format:'image/png'},
                 {attribution: "Topography: <a href=\"http://www.marine-geo.org/about/terms_of_use.php\" target=\"new\">MGDS<\/a>"},//, 'singleTile':true
                 {'isBaseLayer':true}
                 );
*/
	//function to reproject points before rendering on map
	preFeatureInsert = function(feature) {
		var src = proj4326;
		var dest = projGoogle;
		feature.geometry.transform(src,dest);
	};

<?

	//get the sites to render -- only those in current selection...

	$site_query = "
					select distinct gid,initiative
					from (
					select
					program_id as initiative,
					(select distinct gid from locations where locations.location_id = events.location_id) as gid
					from events
					where event_id in (select distinct event_id from wq_obs)
					) as foo
					where gid in ($site_id_sel_string)
					;
					";

	$result= pg_query($con,$site_query);

	//build the filter to use in the server call

	$types = array();
	$gids = array();
	$fids = array();

	while ($row = pg_fetch_array($result)) {
		$gids[] = $row['gid'];
		$types[] = strtolower($row['initiative']);
		$fids[] = strtolower($row['initiative']).".".$row['gid'];
	}

	pg_free_result($result);

	unset($_SESSION['sites_selected']);
	unset($site_id_sel_string);
	$site_id_sel_string = implode(",",$gids);
	$_SESSION['sites_selected'] = $site_id_sel_string;

	$types = array_unique($types);
	$ftypes = implode(",",$types);
	//can use next if text ids
	//$ftypes = implode("','",$types);
	//$ftypes = "'".$ftypes."'";

	foreach ($types as $type) {
		$filtarray = $type.'_filt_array';
		${$filtarray} = array();
		foreach ($fids as $fid) {
			$ini = explode(".",$fid);
			if ($ini[0] == $type) {
				array_push(${$filtarray},$fid);
			}
		}
		$filtstring = $type.'_filt_string';
		${$filtstring} = implode("','",${$filtarray});
		${$filtstring} = "'".${$filtstring}."'";

		echo 'var '.$type.'_filter = new OpenLayers.Filter.FeatureId({ fids: ['.${$filtstring}.'] });';
		echo "\n";
		echo 'var '.$type.' = new OpenLayers.Layer.Vector(\''.$type.'\',
     {
     strategies: [new OpenLayers.Strategy.Fixed()],
     protocol: new OpenLayers.Protocol.WFS({
                    version: \'1.0.0\',
                    srsName: \'EPSG:4326\',
                    url: wfsurl,
                    featureNS :  \'http://mapserver.gis.umn.edu/mapserver\',
                    featureType: \''.$type.'\',
                    featurePrefix: \'ms\',
                    extractAttributes: true,
                    defaultFilter: '.$type.'_filter
                }),
     projection: proj4326,
     isBaseLayer: false
     },
     {preFeatureInsert: preFeatureInsert}
     );';
		echo "\n\n";
	} //foreach type

	//add the layers to the map
	if (!empty($ftypes)) {
		echo "map.addLayers([".$ftypes.",selectpoly]);";
	} else {
		echo "map.addLayers([selectpoly]);";
	}
?>
	//ensure current map extent set, and base layer if needed
	map.events.register('zoomend',this,function() { extent_current = map.getExtent().toBBOX(); assignBaseLayer(); OpenLayers.Request.GET({url: "getsites.php",params: {mapextent: extent_current}}); });
	map.events.register('moveend',this,function() { extent_current = map.getExtent().toBBOX(); assignBaseLayer(); OpenLayers.Request.GET({url: "getsites.php",params: {mapextent: extent_current}}); });

	map.zoomToExtent(extent_current);

	//zoom-out a bit if too close
	if (map.getScale() < 6000) {
		map.zoomToScale(6000);
	}

	// MAP INTERACTION FUNCTIONS
	function toggleQueryMode()
	{
		if (featureinfo.active) {
			selectpoly.destroyFeatures();
			queryEventHandler.activate();
		}
		else {
			queryEventHandler.deactivate();
		}
	}

	function deactivateControls(controlset)
	{
		for (var i in controlset) {
			if (controlset[i].active) {
				controlset[i].deactivate();
			}
		}
	}

	function handler(request) {
		 OpenLayers.Util.getElement('nodeList').innerHTML = request.responseText;
	}

	function getPolyGeom(feature) {
						 var site_ids = Array();
		polyarray = Array();
		polygeom = Array();
		var vertarray = Array();
		vertices = feature.geometry.getVertices();
		areatest = feature.geometry.getArea();
		nverts = vertices.length;
		close = vertices[0].toShortString().replace(","," ");

		for (var i in vertices) {
			var vert = vertices[i].toShortString().replace(","," ");
			vertarray[vertarray.length] = vert;
		}
		vertarray[nverts] = close;
		var vertstring = vertarray.toString();
		var poly = '('+vertstring+')';
		polyarray[polyarray.length] = poly;
		polystring = polyarray.toString();
		polygeom = 'MULTIPOLYGON(('+polystring+'))';

		OpenLayers.Request.GET({url: "getsites.php",params: {site_ids: site_ids, polygeom: polygeom, mapextent: extent_current},callback: handler});
	}

	 function doGetFeatureInfo(evt) {

		OpenLayers.Util.getElement('nodeList').innerHTML="Loading...please wait...";
		// get reference lonlat to pass to results div
		 var click_pix = new OpenLayers.Pixel(evt.xy.x, evt.xy.y);
		 var click_lonlat = map.getLonLatFromPixel(click_pix);
			 click_lonlat.transform(projGoogle,proj4326);
			 click_lon = click_lonlat.lon.toPrecision(4); // no "var" = global
			 click_lat = click_lonlat.lat.toPrecision(4);

		 // now bbox for query
		 var tolerance = new OpenLayers.Pixel(6, 6);
		 var min_px = new OpenLayers.Pixel(evt.xy.x - tolerance.x, evt.xy.y + tolerance.y);
		 var max_px = new OpenLayers.Pixel(evt.xy.x + tolerance.x, evt.xy.y - tolerance.y);
		 var min_ll = map.getLonLatFromPixel(min_px);
			 min_ll.transform(projGoogle,proj4326);
		 var max_ll = map.getLonLatFromPixel(max_px);
			 max_ll.transform(projGoogle,proj4326);

		 var url =  wfsurl + '&MAXFEATURES=5&SERVICE=WFS&VERSION=1.0.0&REQUEST=GetFeature&TYPENAME=<? echo $ftypes; ?>&SRS=' + map.projection + '&BBOX='+min_ll.lon+','+min_ll.lat+','+max_ll.lon+','+max_ll.lat;

		 OpenLayers.loadURL(url, '', this, setHTML);
		 OpenLayers.Event.stop(evt);
	 }

	function setHTML(response){
		 var doc = response.responseXML;
		 var nodes =  OpenLayers.Util.getNodes(doc, "gml:featureMember");
		 //var content = '';
		 var content = "<br /><b>Sites near "+click_lon+", "+click_lat+":<\/b><br /><br />";
		 var ft = new Array('spx');//
			 site_ids = Array();
			 extent_current = map.getExtent().toBBOX();

		if (nodes.length > 0 ){
			 site_ids=Array();
			 for (var i = 0; i < nodes.length; i++) {
				var nd = null;
				for (j = 0; j < ft.length; j++){
					nd = OpenLayers.Util.getNodes(nodes[i],"ms:" + ft[j])[0];
					if (nd != null){
						break;
					}
				}

				var gid = OpenLayers.Util.getTagText(nd, "ms:gid");
				var sitenum = OpenLayers.Util.getTagText(nd, "ms:gid");
				var o_site_id = OpenLayers.Util.getTagText(nd, "ms:prog_loc_id");
				if ( typeof(o_site_id) == 'undefined' ) { o_site_id = 'No site code'; }
				var mission_id = OpenLayers.Util.getTagText(nd, "ms:mission_id"); //new
				if (sitenum != "") {//new
				site_ids[site_ids.length] = gid;
				}//new
			 }
			 site_ids.join();

		 }
		 OpenLayers.Request.GET({url: "getsites.php",params: {site_ids: site_ids, polygeom: "", mapextent: extent_current},callback: handler});
	} //function sethtml

} //function init

//FUNCTIONS TO RESET TO ALL SITES
function reset_handler(request) {
	 OpenLayers.Util.getElement('nodeList').innerHTML = request.responseText;
}

function sites_reset() {
	OpenLayers.Request.GET({url: "getsites.php",params: {site_ids: "",polygeom: "", mapextent: extent_current},callback: reset_handler});
	selectpoly.destroyFeatures();
}

     </script>

</head>
<body onload="init()">
<div id="container" class="container">

<? //print_r($_SESSION['mapextent']); ?>
	<div id="header" class="header">
			<p>
			<a href="https://seagrant.mit.edu/ecosys_health.php"><img src="https://nereides.mit.edu/html/MASSBAYweb/mitis/header.gif" width="439" height="44" border="0" alt="MIT Sea Grant Center for Coastal Resources"></a>
			</p>
			<p>
				<div id="subHeaderBar" class="subHeaderBar"></div>
			</p>
	</div>
	<?php include ('navcol.php'); //site navigation (left column) ?>
	<div id="mapStack" class="mapStack">
		<div id="map"></div>
		<div id="mapStatus" class="mapStatus">
			<!--<div id="mapProjection" style="border-left: solid 1px #999; border-right: solid 1px #999;"></div>-->
			<div id="mapScale" style="float: left; border-right: solid 1px #999;"></div>
			<div id="mapMousePosition" style="float: right; border-right: solid 1px #999;">-000.00000, -00.00000</div>
			<div id="mapMousePositionLabel" style="float: right; border-left: solid 1px #999;">Cursor position =</div>
		</div>
	</div>
	<div id="queryCol" class="queryCol">
        		<h3 style="margin-top:0px; padding-top:0px;">Locations</h3>
				<? if (!isset($_GET['gid'])) { echo '<p>Use the map controls to navigate and select sites. Click on site name links for data selection options.</p>'; } ?>

<?
	echo "<form id=\"refresh_or_get\" action=\"explore.php\"  method=\"post\">";
//echo $_SESSION['prev_page'];

echo "<div class=\"nodeList\" id=\"nodeList\">";

//if not a data info request and fewer than all sites selected, list the sites
if (!isset($_GET['gid']) && (count(explode(",",$_SESSION['sites_selected']))!=count(explode(",",$_SESSION['site_id_all_string']))) && !empty($site_id_sel_string)) { //
//echo count(explode(",",$_SESSION['sites_selected']));
echo "<br>";
//echo count(explode(",",$_SESSION['site_id_all_string']));
echo "<br>";
	echo "<b>Sites:</b><br><br><font face=\"arial\" size=\"-1\">";// (<a href=\"javascript:sites_reset()\">reset to all</a>):</b><p><font face=\"arial\" size=\"-1\">";

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
	while ($row = pg_fetch_array($result)) {
		echo "<a href=\"explore.php?gid=".$row['gid']."&location_id=".$row['location_id']."\">".$row['location_name']."</a> [".$row['program_id']."]<br><br>";//
	}
	echo "</font>";//</p>";
	echo "<input type=\"submit\" name=\"refresh\" value=\"View Selected\"> or <a href=\"explore\">Reset to All Sites</a>";

	pg_free_result($result);

} elseif (isset($_GET['gid'])) { //otherwise it's a data info request for a specific site

$gid = $_GET['gid'];
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

	//get local time zone for site from google
	//this gets json and seems ok:
	$request = 'https://maps.googleapis.com/maps/api/timezone/json?location='.$latitude.','.$longitude.'&timestamp='.time().'&sensor=false';
	$response = @file_get_contents($request);
	if (empty($response)) { $errors[] = 'An error occurred (API error). Please try again and contact MIT Sea Grant if the problem persists.'; exit; }

	$response_array = json_decode($response,TRUE);

	$local_tz_name = $response_array['timeZoneId'];

	//get data period for site
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
/*		if ($min_date == $max_date) {
			$new = $max_date[0]." + 1 hour";
			$max_date = array(date('Y-m-d H:i:s', strtotime($new)));
		}*/
	}

//give site info and build query form
echo "<div class=\"nodeList\" id=\"queryform\">
	<p>Selected site:</p>
	<p>Site name:<br>$location_name<br>
	Lon: {$longitude}<br />
    Lat: {$latitude}<br />
	Affiliation: <br>$organization_id</p>

Available data period:<br>
<table width=\"200\" border=\"0\" cellspacing=\"3\" cellpadding=\"0\">
<tr><td align=\"right\">Start:</td><td width=\"80%\"> {$min_date[0]}</td></tr>
<tr><td align=\"right\">End:</td><td width=\"80%\"> {$max_date[0]}</td></tr>
</table>";
//print_r($max_date);

//get available wq params and units for site
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

		// need to check if param really exists -- it might not if null column uploaded, or values all deleted
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

		//vars for params and units available
		$nullparamcheckresult = pg_query($con,$nullparamcheckquery);
		if (pg_num_rows($nullparamcheckresult) != 0) {
			$key = $row['param_short_name'].'@'.$row['unit_code'];
			$value = $row['param_long_name'].' ('.$row['unit_abbrev'].')';
			$param_array[$key] = $value;
		}
		pg_free_result($nullparamcheckresult);
	} // if not time
} //while param_result

pg_free_result($param_result);

	// user selections
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
		<input type=\"hidden\" name=\"organization_id\" value=\"$organization_id\">

		<input type=\"hidden\" name=\"min_date\" value=\"$min_date[0]\">
		<input type=\"hidden\" name=\"max_date\" value=\"$max_date[0]\">
		";

		echo "Select available variables:<br>";
		echo "<select name=\"varmenu[]\" multiple size=5 style=\"width:200px;\">";

			foreach ($param_array as $k => $v) { echo "<option value=\"$k\">$v</option>\n"; }

		echo "</select><br /><br />";

		echo "<input type=\"submit\" name=\"submit\" value=\"Get Data\"> or <a href=\"explore\">Reset Map</a>";//
		echo "</div><!-- </form>queryform -->";

		} else { //if gid is set

	echo "<p><b>Sites:</b><br>All sites</p>";

	$_SESSION['sites_selected'] = $_SESSION['site_id_all_string'];


}//end if site_id_get_string else

	echo "</form>";

echo "</div>"; //nodeList div
echo "</div>"; //queryCol div
?>
<br>
<hr id="superFooterLine" class="superFooterLine" />
			<div id="footer" class="footer">
				<p>Copyright 2000-<? echo date('Y'); ?> MIT Sea Grant College Program.
				<? if (!empty($login_id)) { echo "<br>You are logged-in as ".$login_id; } ?>
				</p>
			</div> <!--footer-->
</div> <!-- container -->
</body>
</html>