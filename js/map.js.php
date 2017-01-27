<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php'); 

setcookie("MapFormat",'2d');

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;
if (isset($_GET['archive'])) {
	//$archiveupdatetime = 50;
	$archiveupdatetime = $globalMapRefresh;
	date_default_timezone_set('UTC');
	$archivespeed = $_GET['archivespeed'];
	$begindate = $_GET['begindate'];
	//$lastupd = round(($_GET['enddate']-$_GET['begindate'])/(($_GET['during']*60)/10));
	//$lastupd = 20;
	$lastupd = $_GET['archivespeed']*$archiveupdatetime;
	if (isset($_GET['enddate']) && $_GET['enddate'] != '') $enddate = $_GET['enddate'];
	else $enddate = time();
	setcookie("archive_begin",$begindate);
	setcookie("archive_end",$enddate);
	setcookie("archive_update",$lastupd);
	setcookie("archive_speed",$archivespeed);
?>
document.cookie =  'archive_begin=<?php print $begindate; ?>; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
document.cookie =  'archive_end=<?php print $enddate; ?>; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
document.cookie =  'archive_update=<?php print $lastupd; ?>; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
document.cookie =  'archive_speed=<?php print $archivespeed; ?>; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
<?php
	}
?>


var map;
var user = new L.FeatureGroup();
var weatherprecipitation;
var weatherprecipitationrefresh;
var weatherrain;
var weatherrainrefresh;
var weatherclouds;
var weathercloudsrefresh;

var geojsonLayer;
var atcLayer;
var polarLayer;
var santaLayer;
var notamLayer;
var weatherradar;
waypoints = '';
var weatherradarrefresh;
var weathersatellite;
var weathersatelliterefresh; 
var noTimeout = true;

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}

function delCookie(cname) {
    document.cookie = cname + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

<?php
	if (isset($globalMapIdleTimeout) && $globalMapIdleTimeout > 0) {
?>
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
	if ((isset($ident) || isset($flightaware_id)) && ($latitude != 0 && $longitude != 0)) {
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
		var zoom = <?php if (isset($globalLiveZoom)) print $globalLiveZoom-1; else print '8'; ?>;
	} else {
		var zoom = <?php if (isset($globalLiveZoom)) print $globalLiveZoom; else print '9'; ?>;
	}

	//create the map
<?php
		if (isset($globalCenterLatitude) && $globalCenterLatitude != '' && isset($globalCenterLongitude) && $globalCenterLongitude != '') {
?>
	map = L.map('live-map', { zoomControl:false }).setView([<?php print $globalCenterLatitude; ?>,<?php print $globalCenterLongitude; ?>], zoom);
	//map = WE.map('live-map');
<?php
		} else {
?>
	map = L.map('live-map', { zoomControl:false }).setView([0,0], zoom);
<?php
		}
	}
?>
	//initialize the layer group for the aircrft markers
	layer_data = L.layerGroup();

	var southWest = L.latLng(-90,-180),
	    northEast = L.latLng(90,180);
	bounds = L.latLngBounds(southWest,northEast);
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
	    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
	      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
	      'Imagery © <a href="http://mapbox.com">Mapbox</a>',
	    id: '<?php print $MapBoxId; ?>',
	    token: '<?php print $globalMapboxToken; ?>'
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
	} elseif ($MapType == 'Here-Roadmap') {
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
	// Show airports on map
	function airportPopup (feature, layer) {
		var output = '';
		output += '<div class="top">';
		    output += '<div class="left">';
			if (typeof feature.properties.image_thumb != 'undefined' && feature.properties.image_thumb != '') {
			    output += '<img src="'+feature.properties.image_thumb+'" /></a>';
			}
		    output += '</div>';
		    output += '<div class="right">';
			output += '<div class="callsign-details">';
			    output += '<div class="callsign">'+feature.properties.name+'</div>';
			output += '</div>';
			output += '<div class="nomobile airports">';
			    output += '<div class="airport">';
				output += '<span class="code"><a href="/airport/'+feature.properties.icao+'" target="_blank">'+feature.properties.icao+'</a></span>';
			    output += '</div>';
			output += '</div>';
		     output += '</div>';
		output += '</div>';
		output += '<div class="details">';
		    output += '<div>';
			output += '<span><?php echo _("City"); ?></span>';
			output += feature.properties.city;
		    output += '</div>';
		    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
		    {
			output += '<div>';
			    output += '<span><?php echo _("Altitude"); ?></span>';
			    output += Math.round(feature.properties.altitude*3,2809)+' feet - '+feature.properties.altitude+' m';
			output += '</div>';
		    }
		    output += '<div>';
			output += '<span><?php echo _("Country"); ?></span>';
			output += feature.properties.country;
		    output += '</div>';
		    if (feature.properties.homepage != "") {
			output += '<div>';
			    output += '<span><?php echo _("Links"); ?></span>';
			    output += '<a href="'+feature.properties.homepage+'"><?php echo _("Homepage"); ?></a>';
			output += '</div>';
		    }
		output += '</div>';
		output += '</div>';
		layer.bindPopup(output);
	};


	function update_airportsLayer() {
	    <?php
		if (isset($_COOKIE['AirportZoom'])) $getZoom = $_COOKIE['AirportZoom'];
		else $getZoom = '7';
	    ?>
	    //if (map.getZoom() <= <?php print $getZoom; ?>) {
		if (typeof airportsLayer != 'undefined') {
			if (map.hasLayer(airportsLayer) == true) {
				map.removeLayer(airportsLayer);
			}
		}
	    //}
		if (map.getZoom() > <?php print $getZoom; ?>) {
			//if (typeof airportsLayer == 'undefined' || map.hasLayer(airportsLayer) == false) {
			var bbox = map.getBounds().toBBoxString();
			airportsLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/airport-geojson.php?coord="+bbox,{
<?php
		if (isset($globalAirportPopup) && $globalAirportPopup) {
?>
				onEachFeature: airportPopup,
<?php
		}
?>
				pointToLayer: function (feature, latlng) {
					return L.marker(latlng, {
						icon: L.icon({
							iconUrl: feature.properties.icon,
							iconSize: [16, 18]
							//popupAnchor: [0, -28]
						})
<?php
		if (!isset($globalAirportPopup) || $globalAirportPopup == FALSE) {
?>
					}).on('click', function() {
						$(".showdetails").load("airport-data.php?"+Math.random()+"&airport_icao="+feature.properties.icao);
					});
				}
<?php
		} else {
?>
					})
				}
<?php
		}
?>              
			}).addTo(map);
	    //}
		}
	};

	// Show airports on map
	function locationPopup (feature, layer) {
		var output = '';
		output += '<div class="top">';
		    output += '<div class="left">';
			if (typeof feature.properties.image_thumb != 'undefined' && feature.properties.image_thumb != '') {
			    output += '<img src="'+feature.properties.image_thumb+'" /></a>';
			}
		    output += '</div>';
		    output += '<div class="right">';
			output += '<div class="callsign-details">';
			    output += '<div class="callsign">'+feature.properties.name+'</div>';
			output += '</div>';
		     output += '</div>';
		output += '</div>';
		output += '<div class="details">';
		    if (feature.properties.city != "")
		    {
			output += '<div>';
			    output += '<span><?php echo _("City"); ?></span>';
			    output += feature.properties.city;
			output += '</div>';
		    }
		    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
		    {
			output += '<div>';
			    output += '<span><?php echo _("Altitude"); ?></span>';
			    output += Math.round(feature.properties.altitude*3,2809)+' feet - '+feature.properties.altitude+' m';
			output += '</div>';
		    }
		    if (feature.properties.country != "")
		    {
			output += '<div>';
			    output += '<span><?php echo _("Country"); ?></span>';
			    output += feature.properties.country;
			output += '</div>';
		    }
		output += '</div>';
		output += '</div>';
		layer.bindPopup(output);
	};

	function update_locationsLayer() {
		//var bbox = map.getBounds().toBBoxString();
		//locationsLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/location-geojson.php?coord="+bbox,{
		locationsLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/location-geojson.php",{
		onEachFeature: locationPopup,
		    pointToLayer: function (feature, latlng) {
			return L.marker(latlng, {
			    icon: L.icon({
				iconUrl: feature.properties.icon,
				iconSize: [16, 18]
				//iconAnchor: [0, 0],
				//popupAnchor: [0, -28]
			    })
			});
		    }
		}).addTo(map);
	};

	map.on('moveend', function() {
		if (map.getZoom() > 7) {
			//if (typeof airportsLayer != 'undefined') {
			//    if (map.hasLayer(airportsLayer) == true) {
			//	map.removeLayer(airportsLayer);
			//    }
			//}
			update_airportsLayer();
			map.removeLayer(locationsLayer);
			update_locationsLayer();
			if ($("#airspace").hasClass("active"))
			{
				map.removeLayer(airspaceLayer);
				update_airspaceLayer();
			}
			if ($("#waypoints").hasClass("active"))
			{
				map.removeLayer(waypointsLayer);
				update_waypointsLayer();
				//map.removeLayer(waypointsLayer);
			}
		} else {
			//if (typeof airportsLayer != 'undefined') {
			//    if (map.hasLayer(airportsLayer) == true) {
			//	map.removeLayer(airportsLayer);
			//    }
			//}
			update_airportsLayer();
			map.removeLayer(locationsLayer);
			update_locationsLayer();
			if ($("#airspace").hasClass("active"))
			{
				map.removeLayer(airspaceLayer);
			}
			if ($("#waypoints").hasClass("active"))
			{
				map.removeLayer(waypointsLayer);
			}
		}
		if ($("#notam").hasClass("active"))
		{
			map.removeLayer(notamLayer);
			update_notamLayer();
		}
	});
	map.on('zoomend', function() {
		getLiveData(1);
	});

	//update_waypointsLayer();
	update_airportsLayer();
	update_locationsLayer();
	
<?php
	    if (!isset($ident) && !isset($flightaware_id)) {
?>
	
	function info_update (props) {
		$(".infobox").html('<h4><?php echo _("Aircrafts detected"); ?></h4>' +  '<b>' + props + '</b>');
	}

	<?php
	    }
	?>

	<?php
	    if (isset($_GET['archive'])) {
	?>
	var archive = L.control();
	archive.onAdd = function (map) {
		this._div = L.DomUtil.create('div', 'archivebox'); // create a div with a class "info"
		this.update();
		return this._div;
	};
	archive.update = function (props) {
		if (typeof props != 'undefined') {
			//this._div.innerHTML = '<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b>' + props.archive_date + ' UTC </b>' + '<br/><i class="fa fa-fast-backward" aria-hidden="true"></i> <i class="fa fa-backward" aria-hidden="true"></i>  <a href="#" onClick="archivePause();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="archivePlay();"><i class="fa fa-play" aria-hidden="true"></i></a>  <i class="fa fa-forward" aria-hidden="true"></i> <i class="fa fa-fast-forward" aria-hidden="true"></i>';
			this._div.innerHTML = '<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b>' + props.archive_date + ' UTC </b>' + '<br/><a href="#" onClick="archivePause();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="archivePlay();"><i class="fa fa-play" aria-hidden="true"></i></a>';
		} else {
			this._div.innerHTML = '<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b><i class="fa fa-spinner fa-pulse fa-2x fa-fw margin-bottom"></i></b>';
		}

	};
	archive.addTo(map);
	<?php
	    }
	?>


	<?php
	    //if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (isset($globalMapPopup) && !$globalMapPopup)) {
	?>

	$(".showdetails").on("click",".close",function(){
    	    $(".showdetails").empty();
	    $("#aircraft_ident").attr('class','');
	    getLiveData(1);
            return false;
	})
	<?php
	   // }
	?>
    
	<?php
	if (!isset($ident) && !isset($flightaware_id)) {
	?>
	//var sidebar = L.control.sidebar('sidebar').addTo(map);
	<?php
	}
	?>


function getAltitudeColor(x) {
	return x < 10     ?    '#ea0000':
         x < 30     ?   '#ea3a00':
         x < 60     ?   '#ea6500':
         x < 80     ?   '#ea8500':
         x < 100     ?   '#eab800':
         x < 120     ?   '#eae300':
         x < 140     ?   '#d3ea00':
         x < 160     ?   '#b0ea00':
         x < 180     ?   '#9cea00':
         x < 200     ?   '#8cea00':
         x < 220     ?   '#46ea00':
         x < 240     ?   '#00ea4a':
         x < 260     ?   '#00eac7':
         x < 280     ?   '#00cfea':
         x < 300     ?   '#009cea':
         x < 320     ?   '#0065ea':
         x < 340     ?   '#001bea':
         x < 360     ?   '#3e00ea':
         x < 380     ?   '#6900ea':
         x < 400     ?   '#a400ea':
         x < 500     ?   '#cb00ea':
         x < 600     ?   '#ea00db':
                          '#3e00ea' ;

//	return '#' + ('00000' + (x*2347 | 0).toString(16)).substr(-6);
};

$("#aircraft_ident").attr('class','');
var MapTrack = getCookie('MapTrack');
if (MapTrack != '') {
	$("#aircraft_ident").attr('class',MapTrack);
	$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+MapTrack);
	delCookie('MapTrack');
}

function getLiveData(click)
{
	var bbox = map.getBounds().toBBoxString();
<?php
	if (isset($_GET['archive'])) {
?>
	var begindate = parseInt(getCookie("archive_begin"));
	var enddate = begindate+parseInt(getCookie("archive_update"));
	if (enddate > getCookie("archive_end")) {
		enddate = parseInt(getCookie("archive_end"));
		clearInterval(reloadPage);
	} else {
		if (click != 1) {
			document.cookie =  'archive_begin='+enddate+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
		}
	}
<?php
	}
?>
	layer_data_p = L.layerGroup();
	$.ajax({
	    dataType: "json",
	    //      url: "live/geojson?"+Math.random(),
<?php
	if (isset($ident)) {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&ident=<?php print $ident; ?>&history",
<?php
	} elseif (isset($flightaware_id)) {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&flightaware_id=<?php print $flightaware_id; ?>&history",
<?php
	} elseif (isset($_GET['archive'])) {
?>
            url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed=<?php print $archivespeed; ?>",
<?php
	} else {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className,
<?php 
	}
?>
	    success: function(data) {
		map.removeLayer(layer_data);
		layer_data = L.layerGroup();
		var live_data = L.geoJson(data, {
		    pointToLayer: function (feature, latLng) {
		    var markerLabel = "";
		    //if (feature.properties.callsign != ""){ markerLabel += feature.properties.callsign+'<br />'; }
		    //if (feature.properties.departure_airport_code != "" || feature.properties.arrival_airport_code != ""){ markerLabel += '<span class="nomobile">'+feature.properties.departure_airport_code+' - '+feature.properties.arrival_airport_code+'</span>'; }
<?php
	if ($compress) {
?>
		    var callsign = feature.properties.c;
		    var departure_airport_code = feature.properties.dac;
		    var arrival_airport_code = feature.properties.aac;
		    var flightaware_id = feature.properties.fi;
		    var aircraft_shadow = feature.properties.as;
		    var squawk = feature.properties.sq;
		    var altitude = feature.properties.a;
		    var heading = feature.properties.h;
		    var type = feature.properties.t;

<?php
	} else {
?>
		    var callsign = feature.properties.callsign;
		    var departure_airport_code = feature.properties.departure_airport_code;
		    var arrival_airport_code = feature.properties.arrival_airport_code;
		    var flightaware_id = feature.properties.flightaware_id;
		    var aircraft_shadow = feature.properties.aircraft_shadow;
		    var squawk = feature.properties.squawk;
		    var altitude = feature.properties.altitude;
		    var heading = feature.properties.heading;
		    var type = feature.properties.type;
<?php
	}
?>
		    if (callsign != ""){ markerLabel += callsign; }
		    if (departure_airport_code != "" && arrival_airport_code != "" && departure_airport_code != "NA" && arrival_airport_code != "NA"){ markerLabel += ' ( '+departure_airport_code+' - '+arrival_airport_code+' )'; }
<?php
	if (isset($_COOKIE['IconColor'])) $IconColor = $_COOKIE['IconColor'];
	elseif (isset($globalAircraftIconColor)) $IconColor = $globalAircraftIconColor;
	else $IconColor = '1a3151';
	if (!isset($ident) && !isset($flightaware_id)) {
?>
		    info_update(feature.properties.fc);
<?php
		if (isset($_GET['archive'])) {
?>
		    archive.update(feature.properties);
<?php
		}
?>
		    if (document.getElementById('aircraft_ident').className == callsign || document.getElementById('aircraft_ident').className == flightaware_id) {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=FF0000&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+aircraft_shadow;
		    } else if ( squawk == "7700" || squawk == "7600" || squawk == "7500" ) {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=FF8C00&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+aircraft_shadow;
		    } else {
<?php
		if ((!isset($globalAircraftIconAltitudeColor) || !$globalAircraftIconAltitudeColor) && (!isset($_COOKIE['IconColorAltitude']) || $_COOKIE['IconColorAltitude'] == 'false')) {
?>
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+aircraft_shadow;
<?php
		} else {
?>
			    var altcolor = getAltitudeColor(altitude);
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color='+altcolor.substr(1)+'&filename='+aircraft_shadow;
<?php
		}
?>
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+aircraft_shadow;
		    }
<?php
	} else {
?>
		    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+aircraft_shadow;
		    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+aircraft_shadow;
<?php
	}
	if (isset($globalAircraftSize) && $globalAircraftSize != '') {
?>
<?php
		if ((!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'false')) {
?>
		    return new L.Marker(latLng, {
<?php
		} else {
?>
		    var movingtime = Math.round(<?php if (isset($archiveupdatetime)) print $archiveupdatetime*1000; else print $globalMapRefresh*1000+20000; ?>+feature.properties.sqt*1000);
		    return new L.Marker.movingMarker([latLng, feature.properties.nextlatlon],[movingtime],{
<?php
		}
?>
			rotationAngle: heading,
			iconAngle: heading,
			title: markerLabel,
			alt: callsign,
			icon: L.icon({
			    iconUrl: iconURLpath,
			    iconSize: [<?php print $globalAircraftSize; ?>, <?php print $globalAircraftSize; ?>],
			    iconAnchor: [<?php print $globalAircraftSize/2; ?>, <?php print $globalAircraftSize; ?>]
			    /*
			    shadowUrl: iconURLShadowpath,
			    shadowSize: [<?php print $globalAircraftSize; ?>, <?php print $globalAircraftSize; ?>],
			    shadowAnchor: [<?php print ($globalAircraftSize/2)+1; ?>, <?php print $globalAircraftSize; ?>]
			    */
			})
		    })
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
		    .on('click', function() {
				//if (callsign == "NA") {
				    $("#aircraft_ident").attr('class',flightaware_id);
				    $(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
				/*
				} else {
				    $("#aircraft_ident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveData(1);
			});
<?php
		}
?>
<?php
	} else {
?>
		    if (map.getZoom() > 7) {
<?php
		if ((!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'false')) {
?>
			    return new L.Marker(latLng, {
<?php
		} else {
?>
			    var movingtime = Math.round(<?php if (isset($archiveupdatetime)) print $archiveupdatetime*1000; else print $globalMapRefresh*1000+20000; ?>+feature.properties.sqt*1000);
			    return new L.Marker.movingMarker([latLng, feature.properties.nextlatlon],[movingtime],{
<?php
		}
?>
				rotationAngle: heading,
				autostart: true,
			        iconAngle: heading,
				title: markerLabel,
				alt: callsign,
				icon: L.icon({
				    iconUrl: iconURLpath,
				    iconSize: [30, 30],
				    iconAnchor: [15, 30]
				    /*
				    shadowUrl: iconURLShadowpath,
				    shadowSize: [30,30],
				    shadowAnchor: [16,30]
				    */
				})
			    })
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
			    .on('click', function() {
				//$("#aircraft_ident").attr('class',callsign);
				//if (callsign == "NA") {
					$("#aircraft_ident").attr('class',flightaware_id);
					$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
				/*
				} else {
					$("#aircraft_ident").attr('class',callsign);
					$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveData(1);
			});
<?php
		}
?>
		    } else {
<?php
		if ((!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'false')) {
?>
			    return new L.Marker(latLng, {
<?php
		} else {
?>
			    var movingtime = Math.round(<?php if (isset($archiveupdatetime)) print $archiveupdatetime*1000; else print $globalMapRefresh*1000+20000; ?>+feature.properties.sqt*1000);
			    return new L.Marker.movingMarker([latLng, feature.properties.nextlatlon],[movingtime],{
<?php
		}
?>
				rotationAngle: heading,
				autostart: true,
				iconAngle: heading,
				title: markerLabel,
				alt: callsign,
				icon: L.icon({
				    iconUrl: iconURLpath,
				    iconSize: [15, 15],
				    iconAnchor: [7, 15]
				    /*
				    shadowUrl: iconURLShadowpath,
				    shadowSize: [15,15],
				    shadowAnchor: [8,15]
				    */
				})
			    })
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
			    .on('click', function() {
				//if (callsign == "NA") {
				    $("#aircraft_ident").attr('class',flightaware_id);
				    $(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
				/*
				} else {
				    $("#aircraft_ident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveData(1);
			    });
<?php
		}
?>
		    }
<?php
	}
?>
		},
            onEachFeature: function (feature, layer) {
<?php
	if ($compress) {
?>
		var altitude = feature.properties.a;
		var type = feature.properties.t;
		var callsign = feature.properties.c;
<?php
	} else {
?>
		var altitude = feature.properties.altitude;
		var type = feature.properties.type;
		var callsign = feature.properties.callsign;
<?php
	}
?>
                var output = '';
		
              //individual aircraft
		if (feature.minimal == "false" && type == "aircraft"){
		    output += '<div class="top">';
                    if (typeof feature.properties.image_source_website != 'undefined') {
                	if (typeof feature.properties.image_copyright != 'undefined') {
                	    output += '<div class="left"><a href="'+feature.properties.image_source_website+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a>Image &copy; '+ feature.properties.image_copyright+'</div>';
            		} else {
                	    output += '<div class="left"><a href="'+feature.properties.image_source_website+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a></div>';
			}
		    } else {
			if (typeof feature.properties.image_copyright != 'undefined') {
			    output += '<div class="left"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.flightaware_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a>Image &copy; '+ feature.properties.image_copyright+'</div>';
			} else {
			    output += '<div class="left"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.flightaware_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a></div>';
			}
		    }
		    output += '<div class="right">';
                    output += '<div class="callsign-details">';
                    output += '<div class="callsign"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.flightaware_id+'" target="_blank">'+feature.properties.callsign+'</a></div>';
                    output += '<div class="airline">'+feature.properties.airline_name+'</div>';
                    output += '</div>';
                    output += '<div class="nomobile airports">';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="<?php print $globalURL; ?>/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span>'+feature.properties.departure_airport;
		    if (typeof feature.properties.departure_airport_time != 'undefined') {
			output += '<br /><span class="time">'+feature.properties.departure_airport_time+'</span>';
		    }
		    output += '</div>';
		    output += '<i class="fa fa-long-arrow-right"></i>';
		    output += '<div class="airport">';
                    output += '<span class="code"><a href="<?php print $globalURL; ?>/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span>'+feature.properties.arrival_airport;
		    if (typeof feature.properties.arrival_airport_time != 'undefined') {
			output += '<br /><span class="time">'+feature.properties.arrival_airport_time+'</span>';
		    }
		    output += '</div>';
                    output += '</div>';
                    if (typeof feature.properties.route_stop != 'undefined') {
                	output += '<?php echo _("Route stop:"); ?> '+feature.properties.route_stop;
                    }
                    output += '</div>';
                    output += '</div>';
                    output += '<div class="details">';
                    output += '<div class="mobile airports">';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="<?php print $globalURL; ?>/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span>'+feature.properties.departure_airport;
                    output += '</div>';
                    output += '<i class="fa fa-long-arrow-right"></i>';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="<?php print $globalURL; ?>/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span>'+feature.properties.arrival_airport;
                    output += '</div>';
                    output += '</div>';
                    output += '<div>';
                    output += '<span><?php echo _("Aircraft"); ?></span>';
                    if (feature.properties.aircraft_wiki != 'undefined') {
                        output += '<a href="'+feature.properties.aircraft_wiki+'">';
                        output += feature.properties.aircraft_name;
                        output += '</a>';
                    } else {
                        output += feature.properties.aircraft_name;
                    }
                    output += '</div>';
                    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
                    {
                        output += '<div>';
                	output += '<span><?php echo _("Altitude"); ?></span>';
                        output += feature.properties.altitude+'00 feet - '+Math.round(feature.properties.altitude*30.48)+' m (FL'+feature.properties.altitude+')';
                        output += '</div>';
                    }
                    if (feature.properties.registration != "")
                    {
                	output += '<div>';
                        output += '<span><?php echo _("Registration"); ?></span>';
                        output += '<a href="<?php print $globalURL; ?>/registration/'+feature.properties.registration+'" target="_blank">'+feature.properties.registration+'</a>';
                        output += '</div>';
                    }
                    output += '<div>';
                    output += '<span><?php echo _("Speed"); ?></span>';
                    output += feature.properties.ground_speed+' knots - '+Math.round(feature.properties.ground_speed*1.852)+' km/h';
                    output += '</div>';
                    output += '<div>';
                    output += '<span><?php echo _("Coordinates"); ?></span>';
                    output += feature.properties.latitude+", "+feature.properties.longitude;
                    output += '</div>';
                    output += '<div>';
                    output += '<span><?php echo _("Heading"); ?></span>';
                    output += feature.properties.heading;
                    output += '</div>';
            	    if (typeof feature.properties.pilot_name != 'undefined') {
                	output += '<div>';
                        output += '<span><?php echo _("Pilot"); ?></span>';
            		if (typeof feature.properties.pilot_id != 'undefined') {
                    	    output += feature.properties.pilot_name+" ("+feature.properties.pilot_id+")";
                        } else {
                    	    output += feature.properties.pilot_name;
                        }
                	output += '</div>';
                    }
            	    output += '</div>';
            	    if (typeof feature.properties.waypoints != 'undefined') {
            		output += '<div class="waypoints"><span><?php echo _("Route"); ?></span>';
            		output += feature.properties.waypoints;
            		output += '</div>';
            	    }
                    if (typeof feature.properties.acars != 'undefined') {
            		output += '<div class="acars"><span><?php echo _("Latest ACARS message"); ?></span>';
            		output += feature.properties.acars;
            		output += '</div>';
            	    }
            	    if (typeof feature.properties.squawk != 'undefined') {
                	output += '<div class="bottom">';
                	output += '<?php echo _("Squawk:"); ?> ';
			output += feature.properties.squawk;
            		if (typeof feature.properties.squawk_usage != 'undefined') {
            			output += ' - '+feature.properties.squawk_usage;
            		}
			output += '</div>';
            	    }
            	    output += '</div>';
                
            	    <?php if (!isset($ident) && !isset($flightaware_id)) { ?>
            	    layer.bindPopup(output);
		    <?php } ?>
            	    layer_data.addLayer(layer);
                } else {
            	    layer_data.addLayer(layer);
                }

                if (type == "route"){
            	    var style = {
		    	"color": "#c74343",
		    	"weight": 2,
		    	"opacity": 0.5
		    };
		    layer.setStyle(style);
		    layer_data.addLayer(layer);
		}


                //aircraft history position as a line
                if (type == "history"){
		    <?php if (!isset($ident) && !isset($flightaware_id)) { ?>
		    if (document.getElementById('aircraft_ident').className == callsign) {
			if (map.getZoom() > 7) {
                	    var style = {
				<?php
				    if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
				?>
				"color": "#1a3151",
				<?php
				    } else {
				?>
				"color": getAltitudeColor(altitude),
				<?php
				    }
				?>
				"weight": 3,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_data.addLayer(layer);
			} else {
			    var style = {
				<?php
				    if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
				?>
				"color": "#1a3151",
				<?php
				    } else {
				?>
				"color": getAltitudeColor(altitude),
				<?php
				    }
				?>
				"weight": 2,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_data.addLayer(layer);
			}
            	    } else {
			if (map.getZoom() > 7) {
                	    var style = {
				<?php
				    if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
				?>
                    		"color": "#1a3151",
				<?php
				    } else {
				?>
				"color": getAltitudeColor(altitude),
				<?php
				    }
				?>
				"weight": 3,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_data.addLayer(layer);
			} else {
                	    var style = {
				<?php
				    if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
				?>
                    		"color": "#1a3151",
				<?php
				    } else {
				?>
                    		"color": getAltitudeColor(altitude),
				<?php
				    }
				?>
				"weight": 2,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_data.addLayer(layer);
			}
                    }
		    <?php
            		} else {
            	    ?>
		    if (map.getZoom() > 7) {
                	var style = {
			    <?php
				if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
			    ?>
                    	    "color": "#1a3151",
			    <?php
				} else {
			    ?>
                    	    "color": getAltitudeColor(altitude),
			    <?php
				}
			    ?>
                    	    "weight": 3,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_data.addLayer(layer);
		    } else {
                	var style = {
			    <?php
				if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
			    ?>
			    "color": "#1a3151",
			    <?php
				} else {
			    ?>
			    "color": getAltitudeColor(altitude),
			    <?php
				}
			    ?>
                    	    "weight": 2,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_data.addLayer(layer);
		    }
<?php
            		}
?>
				}
			    }
			});
			layer_data.addTo(map);
			//re-create the bootstrap tooltips on the marker 
			//showBootstrapTooltip();
		}
	});
	//  getLiveData(0);
}


 //load the function on startup
getLiveData(0);


<?php
	if (isset($_GET['archive'])) {
?>
//then load it again every 30 seconds
//  var reload = setInterval(function(){if (noTimeout) getLiveData(0)},<?php if (isset($globalMapRefresh)) print ($globalMapRefresh*1000)/2; else print '15000'; ?>);
reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},<?php print $archiveupdatetime*1000; ?>);
<?php
	} else {
?>
//then load it again every 30 seconds
reloadPage = setInterval(
    function(){if (noTimeout) getLiveData(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
var currentdate = new Date();
var currentyear = new Date().getFullYear();
var begindate = new Date(Date.UTC(currentyear,11,24,2,0,0,0));
var enddate = new Date(Date.UTC(currentyear,11,25,2,0,0,0));
if (currentdate.getTime() > begindate.getTime() && currentdate.getTime() < enddate.getTime()) {
	update_santaLayer(false);
}
<?php
		if (!((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) && (isset($_COOKIE['polar']) && $_COOKIE['polar'] == 'true')) {
?>
update_polarLayer();
setInterval(function(){map.removeLayer(polarLayer);update_polarLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
<?php
		}
?>

<?php
	}
?>
//adds the bootstrap hover to the map buttons
$('.button').tooltip({ placement: 'right' });

<?php
//	if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) ) {
	if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
?>
update_atcLayer();
setInterval(function(){map.removeLayer(atcLayer);update_atcLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
<?php
	}
?>
//update_airspaceLayer();


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
setInterval(function(){update_genLayer('<?php print $json['url']; ?>')}, <?php print $json['refresh']; ?>);
<?php
		}
	    }
	}
    }

?>



  
//});

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
/*
function update_genLayer(url) {
    genLayer = new L.GeoJSON.AJAX(url,{
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
};
*/

function atcPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	output += '&nbsp;'+feature.properties.ident+'<br /> ';
	output += '&nbsp;'+feature.properties.info+'<br /> ';
	output += '</div>';
	layer.bindPopup(output);
};


function update_atcLayer() {
    var bbox = map.getBounds().toBBoxString();
    atcLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/atc-geojson.php?coord="+bbox,{
    onEachFeature: atcPopup,
	pointToLayer: function (feature, latlng) {
	    if (feature.properties.atc_range > 0) {
        	if (feature.properties.type == 'Delivery') {
        	    var atccolor = '#781212';
        	} else if (feature.properties.type == 'Ground') {
        	    var atccolor = '#682213';
        	} else if (feature.properties.type == 'Tower') {
        	    var atccolor = '#583214';
        	} else if (feature.properties.type == 'Approach') {
        	    var atccolor = '#484215';
        	} else if (feature.properties.type == 'Departure') {
        	    var atccolor = '#385216';
        	} else if (feature.properties.type == 'Observer') {
        	    var atccolor = '#286217';
        	} else if (feature.properties.type == 'Control Radar or Centre') {
        	    var atccolor = '#187218';
        	} else {
        	    var atccolor = '#888219';
		}
		return L.circle(latlng, feature.properties.atc_range*1, {
            	    fillColor: atccolor,
            	    color: atccolor,
            	    weight: 1,
            	    opacity: 0.3,
            	    fillOpacity: 0.3
		});
            } else {
        	if (feature.properties.type == 'Delivery') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_del.png',
			    iconSize: [15, 15],
			    iconAnchor: [7, 7]
			})
		    });
		} else if (feature.properties.type == 'Ground') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_gnd.png',
			    iconSize: [20, 20],
			    iconAnchor: [10, 10]
			})
		    });
		} else if (feature.properties.type == 'Tower') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_twr.png',
			    iconSize: [25, 25],
			    iconAnchor: [12, 12]
			})
		    });
		} else if (feature.properties.type == 'Approach') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_app.png',
			    iconSize: [30, 30],
			    iconAnchor: [15, 15]
			})
		    });
		} else if (feature.properties.type == 'Departure') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_dep.png',
			    iconSize: [35, 35],
			    iconAnchor: [17, 17]
			})
		    });
		} else if (feature.properties.type == 'Control Radar or Centre') {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc_ctr.png',
			    iconSize: [40, 40],
			    iconAnchor: [20, 20]
			})
		    });
		} else {
		    return L.marker(latlng, {icon: L.icon({
			    iconUrl: '<?php print $globalURL; ?>/images/atc.png',
			    iconSize: [30, 30],
			    iconAnchor: [15, 30]
			})
		    });
		}
            }
	}
    }).addTo(map);
};

function update_polarLayer() {
    polarLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/polar-geojson.php",{
	style: function(feature) {
	    return feature.properties.style
	}
    }).addTo(map);
};


});

function update_santaLayer(nows) {
    if (nows) var url = "<?php print $globalURL; ?>/live-santa-geojson.php?now";
    else var url = "<?php print $globalURL; ?>/live-santa-geojson.php";
    var santageoJSON = new L.GeoJSON.AJAX(url,{
	onEachFeature: function(feature,layer) {
	    var playbackOptions = {
		orientIcons: true,
		clickCallback: function() { $(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat=santaclaus"); },
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
};




function showNotam() {
    if (!$("#notam").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_notamLayer();
	//add the active class
	$("#notam").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(notamLayer);
	//remove the active class
	$("#notam").removeClass("active");
     }
}
function notamscope(selectObj) {
    var idx = selectObj.selectedIndex;
    var scope = selectObj.options[idx].value;
    document.cookie = 'notamscope='+scope+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    if ($("#notam").hasClass("active"))
    {
	map.removeLayer(notamLayer);
	update_notamLayer();
     }
}

function mapType(selectObj) {
    var idx = selectObj.selectedIndex;
    var atype = selectObj.options[idx].value;
    var type = atype.split('-');
	document.cookie =  'MapType='+type+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    if (type[0] == 'Mapbox') {
        document.cookie =  'MapType='+type[0]+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'MapTypeId='+type[1]+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    } else {
	document.cookie =  'MapType='+atype+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    }
    window.location.reload();
}

function airlines(selectObj) {
    var airs = [], air;
    for (var i=0, len=selectObj.options.length; i< len;i++) {
	air = selectObj.options[i];
	if (air.selected) {
	    airs.push(air.value);
	}
    }
    document.cookie =  'filter_Airlines='+airs.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))); ?>; path=/'
}
function airlinestype(selectObj) {
    var idx = selectObj.selectedIndex;
    var airtype = selectObj.options[idx].value;
    document.cookie =  'filter_airlinestype='+airtype+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))); ?>; path=/'
}
function alliance(selectObj) {
    var idx = selectObj.selectedIndex;
    var alliance = selectObj.options[idx].value;
    document.cookie =  'filter_alliance='+alliance+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))); ?>; path=/'
}

function identfilter() {
    var ident = $('#identfilter').val();
    console.log('Filter with '+ident);
    document.cookie =  'filter_ident='+ident+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function removefilters() {
    // Get an array of all cookie names (the regex matches what we don't want)
    var cookieNames = document.cookie.split(/=[^;]*(?:;\s*|$)/);
    // Remove any that match the pattern
    for (var i = 0; i < cookieNames.length; i++) {
	if (/^filter_/.test(cookieNames[i])) {
    	    document.cookie = cookieNames[i] + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        }
    }
    window.location.reload();
}
function sources(selectObj) {
    var sources = [], source;
    for (var i=0, len=selectObj.options.length; i< len;i++) {
	source = selectObj.options[i];
	if (source.selected) {
	    sources.push(source.value);
	}
    }
    //document.cookie =  'Sources='+sources.join()+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_Sources='+sources.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))); ?>; path=/'
}

function show3D() {
    document.cookie =  'MapFormat=3d; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function iconColor(color) {
    document.cookie =  'IconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function iconColorAltitude(val) {
    document.cookie =  'IconColorAltitude='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}

function airportDisplayZoom(zoom) {
    document.cookie =  'AirportZoom='+zoom+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}

function clickVATSIM(cb) {
    //document.cookie =  'ShowVATSIM='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_ShowVATSIM='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function clickIVAO(cb) {
    //document.cookie =  'ShowIVAO='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_ShowIVAO='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function clickphpVMS(cb) {
    //document.cookie =  'ShowVMS='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_ShowVMS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function clickSBS1(cb) {
    //document.cookie =  'ShowSBS1='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_ShowSBS1='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function clickAPRS(cb) {
    //document.cookie =  'ShowAPRS='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    document.cookie =  'filter_ShowAPRS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function clickFlightPopup(cb) {
    document.cookie =  'flightpopup='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickFlightPath(cb) {
    document.cookie =  'flightpath='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickFlightRoute(cb) {
    document.cookie =  'MapRoute='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickFlightEstimation(cb) {
    document.cookie =  'flightestimation='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickPolar(cb) {
    document.cookie =  'polar='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickSanta(cb) {
    if (cb.checked) {
	update_santaLayer(true);
    } else {
	// FIXME : Need to use leafletplayback stop() for example
	window.location.reload();
    }
}
function unitdistance(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    document.cookie =  'unitdistance='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
function unitspeed(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    document.cookie =  'unitspeed='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
function unitaltitude(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    document.cookie =  'unitaltitude='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
function archivePause() {
    clearInterval(reloadPage);
    console.log('Pause');
}
function archivePlay() {
    reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},10000);
    console.log('Play');
}

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
      webkitAlpha = alpha;
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

function notamPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	output += '&nbsp;'+feature.properties.ref+' '+feature.properties.title+'<br /> ';
	output += '&nbsp;'+feature.properties.text+'<br /> ';
	output += '&nbsp;<i>'+feature.properties.latitude+'/'+feature.properties.longitude+' '+feature.properties.radiusnm+'NM/'+feature.properties.radiusm+'m</i><br /> ';
	output += '</div>';
	layer.bindPopup(output);
};


function update_notamLayer() {
	var bbox = map.getBounds().toBBoxString();
	if (getCookie('notamscope') == '' || getCookie('notamscope') == 'All') {
		url = "<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox;
	} else {
		url = "<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox+"&scope="+getCookie(notamscope);
	}
	//notamLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox,{
	notamLayer = new L.GeoJSON.AJAX(url,{
//	onEachFeature: notamPopup,
	pointToLayer: function (feature, latlng) {
	    var circle = L.circle(latlng, feature.properties.radius, {
                    fillColor: feature.properties.color,
                    color: feature.properties.color,
                    weight: 1,
                    opacity: 0.3,
                    fillOpacity: 0.3
		}).on('click', function() {
			$(".showdetails").load("notam-data.php?"+Math.random()+"&notam="+encodeURI(feature.properties.ref));
		});
            return circle;
	}
    }).addTo(map);
};

function update_genLayer(url) {
    genLayer = new L.GeoJSON.AJAX(url,{
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
};

//adds a new weather radar layer on to the map
function showWeatherPrecipitation(){
  //if the weatherradar is currently active then disable it, otherwise enable it
  if (!$(".weatherprecipitation").hasClass("active"))
  {
    //loads the function to load the weather radar
    loadWeatherPrecipitation();
    //automatically refresh radar every 2 minutes
    weatherprecipirationrefresh = setInterval(function(){loadWeatherPrecipitation()}, 120000);
    //add the active class
    $(".weatherprecipitation").addClass("active");
  } else {
      //remove the weather radar layer
      map.removeLayer(weatherprecipitation);
      //remove the active class
      $(".weatherprecipitation").removeClass("active");
      //remove the auto refresh
      clearInterval(weatherprecipitationrefresh);
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
      //remove the weather radar layer
      map.removeLayer(weatherrain);
      //remove the active class
      $(".weatherrain").removeClass("active");
      //remove the auto refresh
      clearInterval(weatherrainrefresh);
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
      //remove the weather radar layer
      map.removeLayer(weatherclouds);
      //remove the active class
      $(".weatherclouds").removeClass("active");
      //remove the auto refresh
      clearInterval(weathercloudsrefresh);
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
      //remove the weather radar layer
      map.removeLayer(weatherradar);
      //remove the active class
      $(".weatherradar").removeClass("active");
      //remove the auto refresh
      clearInterval(weatherradarrefresh);
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
    
    weatherprecipitation = L.tileLayer('http://{s}.tile.openweathermap.org/map/precipitation/{z}/{x}/{y}.png', {
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
    
    weatherrain = L.tileLayer('http://{s}.tile.openweathermap.org/map/rain/{z}/{x}/{y}.png', {
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
    
    weatherclouds = L.tileLayer('http://{s}.tile.openweathermap.org/map/clouds/{z}/{x}/{y}.png', {
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
function update_waypointsLayer() {
    var bbox = map.getBounds().toBBoxString();
    var lineStyle = {
	"color": "#ff7800",
	"weight": 1,
	"opacity": 0.65
    };

    waypointsLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/waypoints-geojson.php?coord="+bbox,{
    onEachFeature: waypointsPopup,
	pointToLayer: function (feature, latlng) {
	    return L.marker(latlng, {icon: L.icon({
		iconUrl: feature.properties.icon,
		iconSize: [12, 13],
		iconAnchor: [2, 13]
		//popupAnchor: [0, -28]
		})
            });
	},
	style: lineStyle
    }).addTo(map);
};

function showWaypoints() {
    if (!$("#waypoints").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_waypointsLayer();
	//add the active class
	$("#waypoints").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(waypointsLayer);
	//remove the active class
	$("#waypoints").removeClass("active");
     }
}

function waypointsPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	    if (typeof feature.properties.segment_name != 'undefined') {
		output += '&nbsp;<?php echo _("Segment name:"); ?> '+feature.properties.segment_name+'<br /> ';
		output += '&nbsp;<?php echo _("From:"); ?> '+feature.properties.name_begin+' To : '+feature.properties.name_end+'<br /> ';
	    }
	    if (typeof feature.properties.ident != 'undefined') {
		output += '&nbsp;<?php echo _("Ident:"); ?> '+feature.properties.ident+'<br /> ';
	    }
	    if (typeof feature.properties.alt != 'undefined') {
		output += '&nbsp;<?php echo _("Altitude:"); ?> '+feature.properties.alt*100+' feet - ';
		output += Math.round(feature.properties.alt*30,48)+' m (FL'+feature.properties.alt+')<br />';

	    }
	    if (typeof feature.properties.base != 'undefined') {
		output += '&nbsp;<?php echo _("Base Altitude:"); ?> '+feature.properties.base*100+' feet - ';
		output += Math.round(feature.properties.base*30,48)+' m (FL'+feature.properties.base+')<br />';
		output += '&nbsp;<?php echo _("Top Altitude:"); ?> '+feature.properties.top*100+' feet - ';
		output += Math.round(feature.properties.top*30,48)+' m (FL'+feature.properties.top+')<br />';
	    }
//	    output += '&nbsp;Control : '+feature.properties.control+'<br />&nbsp;Usage : '+feature.properties.usage;
	output += '</div>';
	layer.bindPopup(output);
};

function showAirspace() {
    if (!$("#airspace").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_airspaceLayer();
	//add the active class
	$("#airspace").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(airspaceLayer);
	//remove the active class
	$("#airspace").removeClass("active");
     }
}

function airspacePopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	    if (typeof feature.properties.title != 'undefined') {
		output += '&nbsp;<?php echo _("Title:"); ?> '+feature.properties.title+'<br /> ';
	    }
	    if (typeof feature.properties.type != 'undefined') {
		output += '&nbsp;<?php echo _("Type:"); ?> '+feature.properties.type+'<br /> ';
	    }
	    if (typeof feature.properties.tops != 'undefined') {
		output += '&nbsp;<?php echo _("Tops:"); ?> '+feature.properties.tops+'<br /> ';
	    }
	    if (typeof feature.properties.base != 'undefined') {
		output += '&nbsp;<?php echo _("Base:"); ?> '+feature.properties.base+'<br /> ';
	    }
	output += '</div>';
	layer.bindPopup(output);
};

function update_airspaceLayer() {
    var bbox = map.getBounds().toBBoxString();
    airspaceLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/airspace-geojson.php?coord="+bbox,{
    onEachFeature: airspacePopup,
	pointToLayer: function (feature, latlng) {
/*	    return L.marker(latlng, {icon: L.icon({
	//	iconUrl: feature.properties.icon,
		iconSize: [12, 13],
		iconAnchor: [2, 13]
//		//popupAnchor: [0, -28]
		})
            });
            */
	},
	style: function(feature) {
		return {
		    "color": feature.properties.color,
		    "weight": 1,
		    "opacity": 0.2
		};
/*		
	    if (feature.properties.type == 'RESTRICTED') {
		return {
		    "color": '#cf2626',
		    "weight": 1,
		    "opacity": 0.2
		};
	    } else if (feature.properties.type == 'CLASS D') {
		return {
		    "color": '#1a74b3',
		    "weight": 1,
		    "opacity": 0.2
		};
	    } else if (feature.properties.type == 'CLASS B') {
		return {
		    "color": '#1a74b3',
		    "weight": 1,
		    "opacity": 0.2
		};
	    } else if (feature.properties.type == 'GSEC') {
		return {
		    "color": '#1b5acf',
		    "weight": 1,
		    "opacity": 0.2
		};
	    } else if (feature.properties.type == 'CLASS C') {
		return {
		    "color": '#9b6c9d',
		    "weight": 1,
		    "opacity": 0.3
		};
	    } else if (feature.properties.type == 'PROHIBITED') {
		return {
		    "color": '#1b5acf',
		    "weight": 1,
		    "opacity": 0.2
		};
	    } else if (feature.properties.type == 'DANGER') {
		return {
		    "color": '#781212',
		    "weight": 1,
		    "opacity": 0.55
		};
	    } else if (feature.properties.type == 'OTHER' || feature.properties.type == 'CLASS A') {
		return {
		    "color": '#ffffff',
		    "weight": 1,
		    "opacity": 0.55
		};
	    } else {
		return {
		    "color": '#afffff',
		    "weight": 1,
		    "opacity": 0.55
		};
	    }
	*/
	}
    }).addTo(map);
};

