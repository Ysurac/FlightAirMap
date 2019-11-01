<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php'); 
setcookie("MapFormat",'2d');
header('Content-Type: text/javascript');
?>
/**
 * This javascript is part of FlightAirmap.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
"use strict";
<?php

if (!isset($globalOpenWeatherMapKey)) $globalOpenWeatherMapKey = '';
// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;

/*
if (isset($_COOKIE['archive_begin']) && $_COOKIE['archive_begin'] != '') {
	echo "console.log('Archive mode');";
	$archive = true;
}
*/
if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
?>
	var unitspeedvalue = 'mph';
<?php
} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
?>
	var unitspeedvalue = 'knots';
<?php
} else {
?>
	var unitspeedvalue = 'kmh';
<?php
}
if ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dm') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dm')) {
?>
	var unitcoordinatevalue = 'dm';
<?php
} elseif ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dms') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dms')) {
?>
	var unitcoordinatevalue = 'dms';
<?php
} else {
?>
	var unitcoordinatevalue = 'dd';
<?php
}
if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
?>
	var unitaltitudevalue = 'feet';
<?php
} else {
?>
	var unitaltitudevalue = 'm';
<?php
}
?>
if (getCookie('archive_begin') != '') {
	var archive = true;
} else {
	var archive = false;
}
var map;
var user = new L.FeatureGroup();
var weatherprecipitation;
var weatherprecipitationrefresh;
var weatherrain;
var weatherrainrefresh;
var weatherclouds;
var weathercloudsrefresh;

var weatherradar;
var waypoints = '';
var weatherradarrefresh;
var weathersatellite;
var weathersatelliterefresh; 
var noTimeout = true;
var locationsLayer;
var genLayer;
var archiveplayback;
var santaLayer;
var airportsLayer;
<?php
	if (isset($globalMapIdleTimeout) && $globalMapIdleTimeout > 0) {
?>
if (archive === false) {
	$(document).idle({
		onIdle: function(){
			noTimeout = false;
			$( "#dialog" ).dialog({
				modal: true,
				buttons: {
					Close: function() {
						//noTimeout = true;
						$( this ).dialog( "close" );
					}
				},
				 close: function() {
					noTimeout = true;
				}
			});
		},
		idle: <?php print $globalMapIdleTimeout*60000; ?>
	})
}
<?php
	}
	if (isset($_GET['ident'])) {
		$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	}
	if (isset($_GET['flightaware_id'])) {
		$flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
	}
	if (isset($_GET['latitude'])) {
		$latitude = filter_input(INPUT_GET,'latitude',FILTER_SANITIZE_STRING);
	}
	if (isset($_GET['longitude'])) {
		$longitude = filter_input(INPUT_GET,'longitude',FILTER_SANITIZE_STRING);
	}
?>

<?php
	//if ((isset($ident) || isset($flightaware_id)) && ($latitude != 0 && $longitude != 0)) {
	if (isset($latitude) && isset($longitude) && $latitude != 0 && $longitude != 0) {
?>
$( document ).ready(function() {
	//setting the zoom functionality for either mobile or desktop
	if( navigator.userAgent.match(/Android/i)
	     || navigator.userAgent.match(/webOS/i)
	     || navigator.userAgent.match(/iPhone/i)
	     || navigator.userAgent.match(/iPod/i)
	     || navigator.userAgent.match(/BlackBerry/i)
	     || navigator.userAgent.match(/Windows Phone/i))
	{
		var zoom = 8;
	} else {
		var zoom = 8;
	}

	//create the map
	map = L.map('archive-map', { zoomControl:false }).setView([<?php if (isset($latitude)) print $latitude; else print $globalCenterLatitude; ?>,<?php if (isset($longitude)) print $longitude; else print $globalCenterLongitude; ?>], zoom);
<?php
	} else {
		if ((isset($globalCenterLatitude) && $globalCenterLatitude != '' && isset($globalCenterLongitude) && $globalCenterLongitude != '') || isset($_COOKIE['lastcentercoord'])) {
			if (isset($_COOKIE['lastcentercoord'])) {
				$lastcentercoord = explode(',',$_COOKIE['lastcentercoord']);
				$viewcenterlatitude = $lastcentercoord[0];
				$viewcenterlongitude = $lastcentercoord[1];
				$viewzoom = $lastcentercoord[2];
			} else {
				$viewcenterlatitude = $globalCenterLatitude;
				$viewcenterlongitude = $globalCenterLongitude;
				$viewzoom = $globalLiveZoom;
			}
		}

?>
$( document ).ready(function() {
	//setting the zoom functionality for either mobile or desktop
	if( navigator.userAgent.match(/Android/i)
	     || navigator.userAgent.match(/webOS/i)
	     || navigator.userAgent.match(/iPhone/i)
	     || navigator.userAgent.match(/iPod/i)
	     || navigator.userAgent.match(/BlackBerry/i)
	     || navigator.userAgent.match(/Windows Phone/i))
	{
		var zoom = <?php if (isset($viewzoom) && $viewzoom == $globalLiveZoom) print $viewzoom-1; elseif (isset($viewzoom)) print $viewzoom; else print '8'; ?>;
	} else {
		var zoom = <?php if (isset($viewzoom)) print $viewzoom; else print '9'; ?>;
	}

	//create the map
<?php
		if (isset($viewcenterlatitude) && isset($viewcenterlongitude)) {
?>
	map = L.map('live-map', { zoomControl:false, preferCanvas: true }).setView([<?php print $viewcenterlatitude; ?>,<?php print $viewcenterlongitude; ?>], zoom);
	//map = L.map('live-map', { crs : L.CRS.EPSG4326, zoomControl:false }).setView([<?php print $viewcenterlatitude; ?>,<?php print $viewcenterlongitude; ?>], zoom);
	//map = WE.map('live-map');
<?php
		} else {
?>
	map = L.map('live-map', { zoomControl:false,preferCanvas: true }).setView([0,0], zoom);
<?php
		}
	}
?>
	//initialize the layer group for the aircrft markers
	//var layer_data = L.layerGroup();

	var southWest = L.latLng(-90,-180),
	    northEast = L.latLng(90,180);
	var bounds = L.latLngBounds(southWest,northEast);
	//a few title layers
<?php
	if (isset($_COOKIE['MapType'])) $MapType = $_COOKIE['MapType'];
	else $MapType = $globalMapProvider;

	if ($MapType == 'Mapbox') {
		if ($_COOKIE['MapTypeId'] == 'default') $MapBoxId = $globalMapboxId;
		else $MapBoxId = $_COOKIE['MapTypeId'];
?>
	L.tileLayer('https://{s}.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={token}', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> <strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>',
	    id: '<?php print $MapBoxId; ?>',
	    token: '<?php print $globalMapboxToken; ?>'
	}).addTo(map);
<?php
	} elseif ($MapType == 'MapboxGL') {
?>
	L.mapboxGL({
	    style: 'https://data.osmbuildings.org/0.2/rkc8ywdl/style.json',
	    accessToken: '<?php print $globalMapboxToken; ?>',
	    attribution: '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> <strong><a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a></strong>' +
	      '<a href="https://osmbuildings.org/copyright">&copy; OSM Buildings</a>',
	}).addTo(map);
<?php
	} elseif ($MapType == 'OpenStreetMap') {
?>
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
	      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>'
	}).addTo(map);
<?php
	} elseif ($MapType == 'OpenSeaMap') {
?>
	L.tileLayer('https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
		'&copy; <a href="http://openseamap.org">OpenSeaMap</a> contributors, ' +
	      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>'
	}).addTo(map);
<?php
	} elseif ($MapType == 'ArcGIS-Streetmap') {
?>
	L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Tiles &copy; Esri &mdash; Sources: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012'
	}).addTo(map);
<?php
	} elseif ($MapType == 'ArcGIS-Satellite') {
?>
	L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Tiles &copy; Esri &mdash; Sources: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
	}).addTo(map);
<?php
	} elseif ($MapType == 'ArcGIS-Ocean') {
?>
	L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/{z}/{y}/{x}', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Tiles &copy; Esri &mdash; Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri'
	}).addTo(map);
<?php
	} elseif ($MapType == 'NatGeo-Street') {
?>
	L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}', {
	    maxZoom: 18,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Tiles &copy; Esri &mdash; Sources: National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC'
	}).addTo(map);
<?php
	} elseif ($MapType == 'MapQuest-OSM') {
?>
	var mapquestLayer = new MQ.mapLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'MapQuest-Aerial') {
?>
	var mapquestLayer = new MQ.satelliteLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'MapQuest-Hybrid') {
?>
	var mapquestLayer = new MQ.hybridLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'Google-Roadmap') {
?>
	var googleLayer = new L.Google('ROADMAP');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Satellite') {
?>
	var googleLayer = new L.Google('SATELLITE');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Hybrid') {
?>
	var googleLayer = new L.Google('HYBRID');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Terrain') {
?>
	var googleLayer = new L.Google('TERRAIN');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Yandex') {
?>
	var yandexLayer = new L.Yandex();
	map.addLayer(yandexLayer);
<?php
	} elseif ($MapType == 'Bing-Aerial') {
		if (!isset($globalBingMapKey) || $globalBingMapKey == '') setcookie('MapType','OpenStreetMap');
?>
	var bingLayer = new L.tileLayer.bing({bingMapsKey: '<?php print $globalBingMapKey; ?>',imagerySet: 'Aerial'});
	map.addLayer(bingLayer);
<?php
	} elseif ($MapType == 'Bing-Hybrid') {
		if (!isset($globalBingMapKey) || $globalBingMapKey == '') setcookie('MapType','OpenStreetMap');
?>
	var bingLayer = new L.tileLayer.bing({bingMapsKey: '<?php print $globalBingMapKey; ?>',imagerySet: 'AerialWithLabels'});
	map.addLayer(bingLayer);
<?php
	} elseif ($MapType == 'Bing-Road') {
		if (!isset($globalBingMapKey) || $globalBingMapKey == '') setcookie('MapType','OpenStreetMap');
?>
	var bingLayer = new L.tileLayer.bing({bingMapsKey: '<?php print $globalBingMapKey; ?>',imagerySet: 'Road'});
	map.addLayer(bingLayer);
<?php
	} elseif ($MapType == 'Here-Road') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'normal.day'});
	map.addLayer(hereLayer);
<?php
	} elseif ($MapType == 'Here-Aerial') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'satellite.day'});
	map.addLayer(hereLayer);
<?php
	} elseif ($MapType == 'Here-Hybrid') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'hybrid.day'});
	map.addLayer(hereLayer);
<?php
	} elseif ($MapType == 'offline') {
?>	var center = map.getCenter();
	map.options.crs = L.CRS.EPSG4326;
	map.setView(center);
	map._resetView(map.getCenter(), map.getZoom(), true);
	L.tileLayer('<?php print $globalURL; ?>/js/Cesium/Assets/Textures/NaturalEarthII/{z}/{x}/{y}.jpg', {
	    minZoom: 0,
	    maxZoom: 5,
	    tms : true,
	    zindex : 3,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: 'Natural Earth'
	}).addTo(map);
<?php
	} elseif (isset($globalMapCustomLayer[$MapType])) {
		$customid = $MapType;
?>
	L.tileLayer('<?php print $globalMapCustomLayer[$customid]['url']; ?>/{z}/{x}/{y}.png', {
	    maxZoom: <?php if (isset($globalMapCustomLayer[$customid]['maxZoom'])) print $globalMapCustomLayer[$customid]['maxZoom']; else print '18'; ?>,
	    minZoom: <?php if (isset($globalMapCustomLayer[$customid]['minZoom'])) print $globalMapCustomLayer[$customid]['minZoom']; else print '0'; ?>,
	    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
	    attribution: '<?php print $globalMapCustomLayer[$customid]['attribution']; ?>'
	}).addTo(map);

<?php
	}
?>
<?php
	if (isset($_COOKIE['Map2DBuildings']) && $_COOKIE['Map2DBuildings'] == 'true') {
?>
new OSMBuildings(map).load();
<?php
	}
?>
<?php
	if (!isset($globalBounding) || $globalBounding == 'polygon') {
		if ($globalLatitudeMin != '' && $globalLatitudeMax != '' && $globalLongitudeMin != '' && $globalLongitudeMax != '') 
		{ 
?>

	//create the bounding box to show the coverage area
	var polygon = L.polygon(
	   [ [[90, -180],
	    [90, 180],
	    [-90, 180],
	    [-90, -180]], // outer ring
	    [[<?php print $globalLatitudeMin; ?>, <?php print $globalLongitudeMax; ?>],
	    [<?php print $globalLatitudeMin; ?>, <?php print $globalLongitudeMin; ?>],
	    [<?php print $globalLatitudeMax; ?>, <?php print $globalLongitudeMin; ?>],
	    [<?php print $globalLatitudeMax; ?>, <?php print $globalLongitudeMax; ?>]] // actual cutout polygon
        ],{
	    color: '#000',
	    fillColor: '#000',
	    fillOpacity: 0.1,
	    stroke: false
	}).addTo(map);
<?php
		}
	} elseif ($globalBounding == 'circle') {
?>
	var circle = L.circle([<?php print $globalCenterLatitude; ?>, <?php print $globalCenterLongitude; ?>],<?php if (isset($globalBoundingCircleSize)) print $globalBoundingCircleSize; else print '70000'; ?>,{
	    color: '#92C7D1',
	    fillColor: '#92C7D1',
	    fillOpacity: 0.3,
	    stroke: false
	}).addTo(map);
<?php
	}
?>
	function update_locationsLayer() {
		var bbox = map.getBounds().toBBoxString();
		//var locationsLayerQuery = $.getJSON("<?php print $globalURL; ?>/location-geojson.php",function (data) {
		var locationsLayerQuery = $.getJSON("<?php print $globalURL; ?>/location-geojson.php?coord="+bbox,function (data) {
		    locationsLayer = L.geoJson(data,{
			pointToLayer: function (feature, latlng) {
			    if (feature.properties.type == 'wx' && typeof feature.properties.temp != 'undefined') {
				return L.marker(latlng, {
				    icon: new L.DivIcon({
					className: 'map-temp',
					html: feature.properties.temp+'°C'
					//html: '<img class="my-div-image" src="http://png-3.vector.me/files/images/4/0/402272/aiga_air_transportation_bg_thumb"/>'+
					//	'<span class="my-div-span">RAF Banff Airfield</span>'
				    })
				}).on('click', function() {
				    $(".showdetails").load("location-data.php?"+Math.random()+"&sourceid="+encodeURI(feature.properties.id));
				});
			    } else {
				return L.marker(latlng, {
				    icon: L.icon({
					iconUrl: feature.properties.icon,
					iconSize: [16, 18]
					//iconAnchor: [0, 0],
					//popupAnchor: [0, -28]
				    })
				}).on('click', function() {
				    $(".showdetails").load("location-data.php?"+Math.random()+"&sourceid="+encodeURI(feature.properties.id));
				});
			    }
			}
		    }).addTo(map);
		});
	};

<?php
    if (isset($globalTSK) && $globalTSK && isset($_GET['tsk'])) {
?>
	function tskPopup (feature, layer) {
		var output = '';
		output += '<div class="top">';
		if (typeof feature.properties.type != 'undefined') output += '&nbsp;<b>Type:</b>&nbsp;'+feature.properties.type+'<br /> ';
		if (typeof feature.properties.name != 'undefined') output += '&nbsp;<b>Name:</b>&nbsp;'+feature.properties.name+'<br /> ';
		if (typeof feature.properties.altitude != 'undefined') output += '&nbsp;<b>Altitude:</b>&nbsp;'+feature.properties.altitude+'<br /> ';
		output += '</div>';
		layer.bindPopup(output);
	};

	function update_tsk() {
		var bbox = map.getBounds().toBBoxString();
		var tskLayerQuery = $.getJSON("<?php print $globalURL; ?>/tsk-geojson.php?tsk=<?php echo filter_input(INPUT_GET,'tsk',FILTER_SANITIZE_URL); ?>",function (data) {
		    tskLayer = L.geoJson(data,{
			onEachFeature: function (feature, layer) {
			    tskPopup(feature, layer);
			    if (feature.geometry.type == 'LineString') layer.setText('     ►     ', {repeat: true,offset: 4,attributes: {fill: 'blue'}});
			},
			pointToLayer: function (feature, latlng) {
				return L.marker(latlng, {
				    icon: L.icon({
					iconUrl: feature.properties.icon,
					iconSize: [16, 18]
				    })
				}).on('click', function() {
				    //$(".showdetails").load("location-data.php?"+Math.random()+"&sourceid="+encodeURI(feature.properties.id));
				});
			}
		    }).addTo(map);
		});
	};
	update_tsk();
<?php
    }
?>
	map.on('moveend', function() {
		if (map.hasLayer(locationsLayer) == true) {
			map.removeLayer(locationsLayer);
			update_locationsLayer();
		}
		createCookie('lastcentercoord',map.getCenter().lat+','+map.getCenter().lng+','+map.getZoom(),2);
	});
	if (archive === false) {
		update_locationsLayer();
		setInterval(function(){if (noTimeout) { map.removeLayer(locationsLayer); update_locationsLayer();} },<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
	}
	var currentdate = new Date();
	var currentyear = new Date().getFullYear();
	var begindate = new Date(Date.UTC(currentyear,11,24,2,0,0,0));
	var enddate = new Date(Date.UTC(currentyear,11,25,2,0,0,0));
	if (currentdate.getTime() > begindate.getTime() && currentdate.getTime() < enddate.getTime()) {
		update_santaLayer(false);
	}
<?php
    // Add support for custom json via $globalMapJson
    if (isset($globalMapJson) && is_array($globalMapJson)) {
	foreach ($globalMapJson as $json) {
	    if (isset($json['url'])) {
?>
update_genLayer('<?php print $json['url']; ?>');
<?php
		if (isset($json['refresh']) && $json['refresh'] > 0) {
?>
setInterval(function(){if (noTimeout) update_genLayer('<?php print $json['url']; ?>')}, <?php print $json['refresh']; ?>);
<?php
		}
	    }
	}
    }

?>
//adds the bootstrap tooltip to the map icons
function showBootstrapTooltip(){
    $('.leaflet-marker-icon').tooltip('destroy');
    $('.leaflet-marker-icon').tooltip({ html: true });
}

function genLayerPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	if (typeof feature.properties.text != 'undefined') output += '&nbsp;'+feature.properties.text+'<br /> ';
	output += '</div>';
	layer.bindPopup(output);
};
if (getCookie('weather_wind') == 'true') loadWind(getCookie('weather_wind'));
if (getCookie('weather_wave') == 'true') loadWave(getCookie('weather_wave'));
if (getCookie('weather_fire') == 'true') loadFire(getCookie('weather_fire'));
if (getCookie('truelight') == 'true' && typeof L.terminator != "undefined") loadTrueLight(getCookie('truelight'));

});

//zooms in the map
function zoomInMap(){
  var zoom = map.getZoom();
  map.setZoom(zoom + 1);
}

//zooms in the map
function zoomOutMap(){
  var zoom = map.getZoom();
  map.setZoom(zoom - 1);
}

//figures out the user's location
function getUserLocation(){
  //if the geocode is currently active then disable it, otherwise enable it
  if (!$(".geocode").hasClass("active"))
  {
    //add the active class
    $(".geocode").addClass("active");
    //check to see if geolocation is possible in the browser
    if (navigator.geolocation) {
        //gets the current position and calls a function to make use of it
        navigator.geolocation.getCurrentPosition(showPosition);
    } else {
        //if the geolocation is not supported by the browser let the user know
        alert("Geolocation is not supported by this browser.");
        //remove the active class
        $(".geocode").removeClass("active");
    }
  } else {
    //remove the user location marker
    removeUserPosition();
  }
}

//plots the users location on the map
function showPosition(position) {
    //creates a leaflet marker based on the coordinates we got from the browser and add it to the map
    var markerUser = L.marker([position.coords.latitude, position.coords.longitude], {
        title: "<?php echo _("Your location"); ?>",
        alt: "<?php echo _("Your location"); ?>",
        icon: L.icon({
          iconUrl: '<?php print $globalURL; ?>/images/map-user.png',
          iconRetinaUrl: '<?php print $globalURL; ?>/images/map-user@2x.png',
          iconSize: [40, 40],
          iconAnchor: [20, 40]
        })
    });
    user.addLayer(markerUser);
    map.addLayer(user);
    //pan the map to the users location
    map.panTo([position.coords.latitude, position.coords.longitude]);
}

//removes the user postion off the map
function removeUserPosition(){
  //remove the marker off the map
  map.removeLayer(user);
  //remove the active class
  $(".geocode").removeClass("active");
}

//determines the users heading based on the iphone
function getCompassDirection(){

  //if the compass is currently active then disable it, otherwise enable it
  if (!$(".compass").hasClass("active"))
  {
    //add the active class
    $(".compass").addClass("active");
    //check to see if the device orietntation event is possible on the browser
    if (window.DeviceOrientationEvent) {
      //first lets get the user location to mak it more user friendly
      getUserLocation();
      //disable dragging the map
      map.dragging.disable();
      //disable double click zoom
      map.doubleClickZoom.disable();
      //disable touch zoom
      map.touchZoom.disable();
      //add event listener for device orientation and call the function to actually get the values
      window.addEventListener('deviceorientation', capture_orientation, false);
    } else {
      //if the browser is not capable for device orientation let the user know
      alert("<?php echo _("Compass is not supported by this browser."); ?>");
      //remove the active class
      $(".compass").removeClass("active");
    }
  } else {
    //remove the event listener to disable the device orientation
    window.removeEventListener('deviceorientation', capture_orientation, false);
    //reset the orientation to be again north to south
    $("#live-map").css({ WebkitTransform: 'rotate(360deg)'});
    $("#live-map").css({'-moz-transform': 'rotate(360deg)'});
    $("#live-map").css({'-ms-transform': 'rotate(360deg)'});
    //remove the active class
    $(".compass").removeClass("active");
    //remove the user location marker
    removeUserPosition();
    //enable dragging the map
    map.dragging.enable();
    //enable double click zoom
    map.doubleClickZoom.enable();
    //enable touch zoom
    map.touchZoom.enable();
  }

}

//gets the users heading information
function capture_orientation (event) {
 //store the values of each of the recorded elements in a variable
   var alpha;
   var css;
    //Check for iOS property
    if(event.webkitCompassHeading) {
      alpha = event.webkitCompassHeading;
      //Rotation is reversed for iOS
      css = 'rotate(-' + alpha + 'deg)';
    }
    //non iOS
    else {
      alpha = event.alpha;
      var webkitAlpha = alpha;
      if(!window.chrome) {
        //Assume Android stock and apply offset
        webkitAlpha = alpha-270;
        css = 'rotate(' + alpha + 'deg)';
      }
    }    
  
  //we use the "alpha" variable for the rotation effect
  $("#live-map").css({ WebkitTransform: css});
  $("#live-map").css({'-moz-transform': css});
  $("#live-map").css({'-ms-transform': css});
}

function update_genLayer(url) {
	var genLayerQuery = $.getJSON(url,function(data) {
		genLayer = L.geoJson(data, {
			onEachFeature: genLayerPopup,
			pointToLayer: function (feature, latlng) {
				return L.circle(latlng, feature.properties.radius, {
					fillColor: feature.properties.fillcolor,
					color: feature.properties.color,
					weight: feature.properties.weight,
					opacity: feature.properties.opacity,
					fillOpacity: feature.properties.fillOpacity
				});
			}
		}).addTo(map);
	});
};

//adds a new weather radar layer on to the map
function showWeatherPrecipitation(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weatherprecipitation").hasClass("active"))
  {
    //loads the function to load the weather radar
    loadWeatherPrecipitation();
    //automatically refresh radar every 2 minutes
    weatherprecipitationrefresh = setInterval(function(){loadWeatherPrecipitation()}, 120000);
    //add the active class
    $(".weatherprecipitation").addClass("active");
  } else {
      //remove the auto refresh
      clearInterval(weatherprecipitationrefresh);
      //remove the weather radar layer
      map.removeLayer(weatherprecipitation);
      //remove the active class
      $(".weatherprecipitation").removeClass("active");
  }       
}
//adds a new weather radar layer on to the map
function showWeatherRain(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weatherrain").hasClass("active"))
  {
    //loads the function to load the weather radar
    loadWeatherRain();
    //automatically refresh radar every 2 minutes
    weatherrainrefresh = setInterval(function(){loadWeatherRain()}, 120000);
    //add the active class
    $(".weatherrain").addClass("active");
  } else {
      //remove the auto refresh
      clearInterval(weatherrainrefresh);
      //remove the weather radar layer
      map.removeLayer(weatherrain);
      //remove the active class
      $(".weatherrain").removeClass("active");
  }
}
//adds a new weather radar layer on to the map
function showWeatherClouds(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weatherclouds").hasClass("active"))
  {
    //loads the function to load the weather radar
    loadWeatherClouds();
    //automatically refresh radar every 2 minutes
    weathercloudsrefresh = setInterval(function(){loadWeatherClouds()}, 120000);
    //add the active class
    $(".weatherclouds").addClass("active");
  } else {
      //remove the auto refresh
      clearInterval(weathercloudsrefresh);
      //remove the weather radar layer
      map.removeLayer(weatherclouds);
      //remove the active class
      $(".weatherclouds").removeClass("active");
  }
}

//adds a new weather radar layer on to the map
function showWeatherRadar(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weatherradar").hasClass("active"))
  {
    //loads the function to load the weather radar
    loadWeatherRadar();
    //automatically refresh radar every 2 minutes
    weatherradarrefresh = setInterval(function(){loadWeatherRadar()}, 120000);
    //add the active class
    $(".weatherradar").addClass("active");
  } else {
      //remove the auto refresh
      clearInterval(weatherradarrefresh);
      //remove the weather radar layer
      map.removeLayer(weatherradar);
      //remove the active class
      $(".weatherradar").removeClass("active");
  }
}
//actually loads the weather radar
function loadWeatherPrecipitation()
{
    if (weatherprecipitation)
    {
      //remove the weather radar layer
      map.removeLayer(weatherprecipitation);  
    }
    
    weatherprecipitation = L.tileLayer('http://{s}.tile.openweathermap.org/map/precipitation/{z}/{x}/{y}.png?appid=<?php print $globalOpenWeatherMapKey; ?>', {
	attribution: 'Map data © OpenWeatherMap',
        maxZoom: 18,
        transparent: true,
        opacity: '0.7'
    }).addTo(map);
}
//actually loads the weather radar
function loadWeatherRain()
{
    if (weatherrain)
    {
      //remove the weather radar layer
      map.removeLayer(weatherrain);
    }
    
    weatherrain = L.tileLayer('http://{s}.tile.openweathermap.org/map/rain/{z}/{x}/{y}.png?appid=<?php print $globalOpenWeatherMapKey; ?>', {
	attribution: 'Map data © OpenWeatherMap',
        maxZoom: 18,
        transparent: true,
        opacity: '0.7'
    }).addTo(map);
}
//actually loads the weather radar
function loadWeatherClouds()
{
    if (weatherclouds)
    {
      //remove the weather radar layer
      map.removeLayer(weatherclouds);
    }
    
    weatherclouds = L.tileLayer('http://{s}.tile.openweathermap.org/map/clouds/{z}/{x}/{y}.png?appid=<?php print $globalOpenWeatherMapKey; ?>', {
	attribution: 'Map data © OpenWeatherMap',
        maxZoom: 18,
        transparent: true,
        opacity: '0.6'
    }).addTo(map);
}
//actually loads the weather radar
function loadWeatherRadar()
{
    if (weatherradar)
    {
      //remove the weather radar layer
      map.removeLayer(weatherradar);  
    }
    
    weatherradar = L.tileLayer('http://mesonet.agron.iastate.edu/cache/tile.py/1.0.0/nexrad-n0q-900913/{z}/{x}/{y}.png?' + parseInt(Math.random()*9999), {
        format: 'image/png',
        transparent: true,
        opacity: '0.5'
    }).addTo(map);
}

//adds a new weather satellite layer on to the map
function showWeatherSatellite(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weathersatellite").hasClass("active"))
  {
    //loads the function to load the weather satellite
    loadWeatherSatellite();
    //automatically refresh satellite every 2 minutes
    weathersatelliterefresh = setInterval(function(){loadWeatherSatellite()}, 120000);
    //add the active class
    $(".weathersatellite").addClass("active");
  } else {
      //removes the weather satellite layer
      map.removeLayer(weathersatellite);
      //remove the active class
      $(".weathersatellite").removeClass("active");
      //remove the auto refresh
      clearInterval(weathersatelliterefresh);
  }       
}

//actually loads the weather satellite
function loadWeatherSatellite()
{
    if (weathersatellite)
    {
      //remove the weather satellite layer
      map.removeLayer(weathersatellite);  
    }
    
    weathersatellite = L.tileLayer('http://mesonet.agron.iastate.edu/cache/tile.py/1.0.0/goes-east-vis-1km-900913/{z}/{x}/{y}.png?' + parseInt(Math.random()*9999), {
        format: 'image/png',
        transparent: true,
        opacity: '0.65'
    }).addTo(map);
}

function archivePause() {
	archiveplayback.stop();
	console.log('Pause');
}
function archivePlay() {
	archiveplayback.start();
	console.log('Play');
}



var windLayer;
function clickWind(cb) {
	loadWind(cb.checked);
}
function loadWind(val) {
	var wind = getCookie('weather_wind');
	if (wind == 'true' && val != 'true') {
		map.removeLayer(windLayer);
		//delCookie('weather_wind');
		createCookie('weather_wind',val,999);
	} else {
		createCookie('weather_wind',val,999);
		if (unitspeedvalue == 'knots') {
			var windspeedunit = 'kt';
		} else if (unitspeedvalue == 'kmh') {
			var windspeedunit = 'k/h';
		} else {
			var windspeedunit = 'm/s';
		}

		//$.getJSON('data/wwinds.json', function (data) {
		$.getJSON('https://data.flightairmap.com/data/weather/winds.json', function (data) {
			windLayer = L.velocityLayer({
			    displayValues: true,
			    displayOptions: {
				velocityType: 'Wind',
				displayPosition: 'bottomleft',
				displayEmptyString: 'No wind data',
				speedUnit: windspeedunit
			    },
			    data: data,
			    maxVelocity: 20,
			    velocityScale: 0.01
			}).addTo(map);
		});
	}
}
var waveLayer;
function clickWave(cb) {
	loadWave(cb.checked);
}
function loadWave(val) {
	var wave = getCookie('weather_wave');
	if (wave == 'true' && val != 'true') {
		map.removeLayer(waveLayer);
		//delCookie('weather_wave');
		createCookie('weather_wave',val,999);
	} else {
		createCookie('weather_wave',val,999);
		if (unitspeedvalue == 'knots') {
			var wavespeedunit = 'kt';
		} else if (unitspeedvalue == 'kmh') {
			var wavespeedunit = 'k/h';
		} else {
			var wavespeedunit = 'm/s';
		}

		//$.getJSON('data/waves.json', function (data) {
		$.getJSON('https://data.flightairmap.com/data/weather/waves.json', function (data) {
			waveLayer = L.velocityLayer({
			    displayValues: false,
			    displayOptions: {
				velocityType: 'Wave',
				displayPosition: 'bottomleft',
				displayEmptyString: 'No wave data',
				speedUnit: wavespeedunit
			    },
			    data: data,
			    maxVelocity: 40,
			    lineWidth: 6,
			    velocityScale: 0.03
			}).addTo(map);
		});
	}
}

var waveBackLayer;
function clickBackWave(cb) {
	loadBackWave(cb.checked);
}
function loadBackWave(val) {
	var wave = getCookie('weather_backwave');
	if (wave == 'true' && val != 'true') {
		map.removeLayer(waveBackLayer);
		//delCookie('weather_backwave');
		createCookie('weather_backwave',val,999);
	} else {
		createCookie('weather_backwave',val,999);
		waveBackLayer = L.tileLayer.wms('http://thredds.ucar.edu/thredds/wms/grib/NCEP/WW3/Global/Best?', {
		    layers: 'Significant_height_of_combined_wind_waves_and_swell_surface',
		    format: 'image/png',
		    opacity: 62,
		    transparent: true,
		    colorscalerange: '0.28%2C5.53',
		}).addTo(map);
	}
}

var fireLayer;
function clickFire(cb) {
	loadFire(cb.checked);
}
function loadFire(val) {
	var fire = getCookie('weather_fire');
	if (fire == 'true' && val != 'true') {
		map.removeLayer(fireLayer);
		//delCookie('weather_fire');
		createCookie('weather_fire',val,999);
	} else {
		createCookie('weather_fire',val,999);
		fireLayer = L.tileLayer.wms('https://firms.modaps.eosdis.nasa.gov/wms/map/', {
		    layers: 'NASA FIRMS',
		    format: 'image/png',
		    opacity: 62,
		    transparent: true,
		    time: 24,
		    instr: 'modis',
		    sat: 'T'
		}).addTo(map);
	}
}

var truelightLayer;
var truelightinterval;
function clickTrueLight(cb) {
	loadTrueLight(cb.checked);
}
function loadTrueLight(val) {
	var truelight = getCookie('truelight');
	if (truelight == 'true' && val != 'true') {
		//delCookie('truelight');
		clearInterval(truelightinterval);
		map.removeLayer(truelightLayer);
		createCookie('truelight',val,999);
	} else {
		createCookie('truelight',val,999);
		truelightLayer = L.terminator();
		truelightLayer.addTo(map);
		truelightinterval = setInterval(function(){
		    var t = L.terminator();
		    truelightLayer.setLatLngs(t.getLatLngs());
		    truelightLayer.redraw();
		},500);
	}
}
function update_santaLayer(nows) {
	if (nows) var url = "<?php print $globalURL; ?>/live-santa-geojson.php?now";
	else var url = "<?php print $globalURL; ?>/live-santa-geojson.php";
	var santageoJSONQuery = $.getJSON(url, function(data) {
	    var santageoJSON = L.geoJson(data, {
		onEachFeature: function(feature,layer) {
		    var playbackOptions = {
			orientIcons: true,
			clickCallback: function() { 
			    $("#pointident").attr('class','');
			    $(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat=santaclaus"); 
			},
			marker: function(){
			    return {
				icon: L.icon({
				    iconUrl: '<?php print $globalURL; ?>/images/santa.png',
				    iconSize: [60, 60],
				    iconAnchor: [30, 30]
				})
			    }
			}
		    };
		    var santaplayback = new L.Playback(map,feature,null,playbackOptions);
		    santaplayback.start();
		    var now = new Date(); 
		    if (nows == false) santaplayback.setCursor(now.getTime());
		}
	    });
	});
};
function clickSanta(cb) {
	if (cb.checked) {
		update_santaLayer(true);
	} else {
		// FIXME : Need to use leafletplayback stop() for example
		window.location.reload();
	}
}