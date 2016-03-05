<?php require_once('../require/settings.php'); ?>

var map;
var user = new L.FeatureGroup();
var weatherprecipitation;
var weatherprecipitationrefresh;
var weatherrain;
var weatherrainrefresh;
var weatherclouds;
var weathercloudsrefresh;

var geojsonLayer;

var weatherradar;
waypoints = '';
var weatherradarrefresh;
var weathersatellite;
var weathersatelliterefresh; 
var noTimeout = true;

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
    if (isset($ident) || isset($flightaware_id)) {
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
<?php
	} else {
?>
	map = L.map('live-map', { zoomControl:false }).setView([0,0], zoom);
<?php
        }
    }
?>
  //initialize the layer group for the aircrft markers
  var layer_data = L.layerGroup();

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
  L.tileLayer('https://otile{s}-s.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', {
    maxZoom: 18,
    subdomains: "1234",
    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>'
  }).addTo(map);
<?php
    } elseif ($MapType == 'MapQuest-Aerial') {
?>
  L.tileLayer('https://otile{s}-s.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png', {
    maxZoom: 18,
    subdomains: "1234",
    noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>, Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency"'
  }).addTo(map);
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
			output += '<span>City</span>';
			output += feature.properties.city;
		    output += '</div>';
		    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
		    {
			output += '<div>';
			    output += '<span>Altitude</span>';
			    output += Math.round(feature.properties.altitude*3,2809)+' feet - '+feature.properties.altitude+' m';
			output += '</div>';
		    }
		    output += '<div>';
			output += '<span>Country</span>';
			output += feature.properties.country;
		    output += '</div>';
		    if (feature.properties.homepage != "") {
			output += '<div>';
			    output += '<span>Links</span>';
			    output += '<a href="'+feature.properties.homepage+'">Homepage</a>';
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
			    output += '<span>City</span>';
			    output += feature.properties.city;
			output += '</div>';
		    }
		    if (feature.properties.altitude != "" || feature.properties.altitude != 0)
		    {
			output += '<div>';
			    output += '<span>Altitude</span>';
			    output += Math.round(feature.properties.altitude*3,2809)+' feet - '+feature.properties.altitude+' m';
			output += '</div>';
		    }
		    if (feature.properties.country != "")
		    {
			output += '<div>';
			    output += '<span>Country</span>';
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
		if ($(".airspace").hasClass("active"))
		{
		    map.removeLayer(airspaceLayer);
		    update_airspaceLayer();
		}
		if ($(".waypoints").hasClass("active"))
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
		if ($(".airspace").hasClass("active"))
		{
		    map.removeLayer(airspaceLayer);
		}
		if ($(".waypoints").hasClass("active"))
		{
		    map.removeLayer(waypointsLayer);
		}
	    }
	    getLiveData();
	});

	
	//update_waypointsLayer();
	update_airportsLayer();
	update_locationsLayer();
	
	<?php
	    if (!isset($ident) && !isset($flightaware_id)) {
	?>
	
	var info = L.control();
	info.onAdd = function (map) {
		this._div = L.DomUtil.create('div', 'infobox'); // create a div with a class "info"
		this.update();
		return this._div;
	};
	info.update = function (props) {
		if (typeof props != 'undefined') {
			this._div.innerHTML = '<h4>Aircrafts detected</h4>' +  '<b>' + props.flight_cnt + '</b>';
		} else {
			this._div.innerHTML = '<h4>Aircrafts detected</h4>' +  '<b>0</b>';
		}

	};
	info.addTo(map);


	<?php
	    }
	?>

	<?php
	    //if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (isset($globalMapPopup) && !$globalMapPopup)) {
	?>
	var showdetails = L.control();
	showdetails.onAdd = function (map) {
		this._div = L.DomUtil.create('div', 'showdetails'); // create a div with a class "info"
		//L.DomEvent.addListener(this._div,'dblclick',this.hide, this);
		return this._div;
	};
	showdetails.addTo(map);

	$(".showdetails").on("click",".close",function(){
    	    $(".showdetails").empty();
	    $("#aircraft_ident").attr('class','');
	    getLiveData();
            return false;
	})
	<?php
	   // }
	?>
    
	<?php
	if (!isset($ident) && !isset($flightaware_id)) {
	?>
	var sidebar = L.control.sidebar('sidebar').addTo(map);
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

function getLiveData()
{
	var bbox = map.getBounds().toBBoxString();
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
		    if (feature.properties.callsign != ""){ markerLabel += feature.properties.callsign; }
		    if (feature.properties.departure_airport_code != "" && feature.properties.arrival_airport_code != "" && feature.properties.departure_airport_code != "NA" && feature.properties.arrival_airport_code != "NA"){ markerLabel += ' ( '+feature.properties.departure_airport_code+' - '+feature.properties.arrival_airport_code+' )'; }
		    <?php
			if (isset($_COOKIE['IconColor'])) $IconColor = $_COOKIE['IconColor'];
			elseif (isset($globalAircraftIconColor)) $IconColor = $globalAircraftIconColor;
			else $IconColor = '1a3151';
			if (!isset($ident) && !isset($flightaware_id)) {
		    ?>
		    info.update(feature.properties);

				//console.log(document.getElementById('aircraft_ident').className);
			if (document.getElementById('aircraft_ident').className == feature.properties.callsign || document.getElementById('aircraft_ident').className == feature.properties.flightaware_id) {
				//var iconURLpath = '<?php print $globalURL; ?>/images/aircrafts/selected/'+feature.properties.aircraft_shadow;
				var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=FF0000&filename='+feature.properties.aircraft_shadow;
				var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+feature.properties.aircraft_shadow;
			} else if ( feature.properties.squawk == "7700" || feature.properties.squawk == "7600" || feature.properties.squawk == "7500" ) {
				//var iconURLpath = '<?php print $globalURL; ?>/images/aircrafts/selected/'+feature.properties.aircraft_shadow;
				var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=FF8C00&filename='+feature.properties.aircraft_shadow;
				var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+feature.properties.aircraft_shadow;
			} else {
				//var iconURLpath = '<?php print $globalURL; ?>/images/aircrafts/'+feature.properties.aircraft_shadow;
				<?php
				    if ((!isset($globalAircraftIconAltitudeColor) || !$globalAircraftIconAltitudeColor) && (!isset($_COOKIE['IconColorAltitude']) || $_COOKIE['IconColorAltitude'] == 'false')) {
				?>
				var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+feature.properties.aircraft_shadow;
				<?php
				    } else {
				?>
				var altcolor = getAltitudeColor(feature.properties.altitude);
				var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color='+altcolor.substr(1)+'&filename='+feature.properties.aircraft_shadow;
				<?php
				    }
				?>
				var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+feature.properties.aircraft_shadow;
			}
		    <?php
			} else {
		    ?>
			//var iconURLpath = '<?php print $globalURL; ?>/images/aircrafts/'+feature.properties.aircraft_shadow;
			var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+feature.properties.aircraft_shadow;
			var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?color=8D93B9&filename='+feature.properties.aircraft_shadow;
		    
		    <?php
			}
			
			if (isset($globalAircraftSize) && $globalAircraftSize != '') {
		    ?>
		    return new L.Marker(latLng, {
			iconAngle: feature.properties.heading,
			title: markerLabel,
			alt: feature.properties.callsign,
			icon: L.icon({
			    iconUrl: iconURLpath,
			    iconSize: [<?php print $globalAircraftSize; ?>, <?php print $globalAircraftSize; ?>],
			    iconAnchor: [<?php print $globalAircraftSize/2; ?>, <?php print $globalAircraftSize; ?>],
			    shadowUrl: iconURLShadowpath,
			    shadowSize: [<?php print $globalAircraftSize; ?>, <?php print $globalAircraftSize; ?>],
			    shadowAnchor: [<?php print ($globalAircraftSize/2)+1; ?>, <?php print $globalAircraftSize; ?>]
			})
		    })
		    <?php
			if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
		    ?>
		    .on('click', function() {
				if (feature.properties.callsign == "NA") {
				    $("#aircraft_ident").attr('class',feature.properties.flightaware_id);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&flightaware_id="+feature.properties.flightaware_id);
				} else {
				    $("#aircraft_ident").attr('class',feature.properties.callsign);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&ident="+feature.properties.callsign);
				}
				getLiveData();
			});
		    <?php
		      }
		    ?>

		    <?php
			} else {
		    ?>
		    if (map.getZoom() > 7) {
			return new L.Marker(latLng, {
			    iconAngle: feature.properties.heading,
			    title: markerLabel,
			    alt: feature.properties.callsign,
			    icon: L.icon({
				iconUrl: iconURLpath,
				shadowUrl: iconURLShadowpath,
				iconSize: [30, 30],
				shadowSize: [30,30],
				iconAnchor: [15, 30],
				shadowAnchor: [16,30]
			    })
			})
		    <?php
			if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
		    ?>

			.on('click', function() {
				$("#aircraft_ident").attr('class',feature.properties.callsign);
				if (feature.properties.callsign == "NA") {
				    $("#aircraft_ident").attr('class',feature.properties.flightaware_id);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&flightaware_id="+feature.properties.flightaware_id);
				} else {
				    $("#aircraft_ident").attr('class',feature.properties.callsign);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&ident="+feature.properties.callsign);
				}
				getLiveData();
			});
		    <?php
		      }
		    ?>
		    } else {
			return new L.Marker(latLng, {
			    iconAngle: feature.properties.heading,
			    title: markerLabel,
			    alt: feature.properties.callsign,
			    icon: L.icon({
				iconUrl: iconURLpath,
				shadowUrl: iconURLShadowpath,
				shadowSize: [15,15],
				shadowAnchor: [8,15],
				iconSize: [15, 15],
				iconAnchor: [7, 15]
			    })
			})
		    <?php
			if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
		    ?>
			.on('click', function() {
				if (feature.properties.callsign == "NA") {
				    $("#aircraft_ident").attr('class',feature.properties.flightaware_id);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&flightaware_id="+feature.properties.flightaware_id);
				} else {
				    $("#aircraft_ident").attr('class',feature.properties.callsign);
				    $(".showdetails").load("aircraft-data.php?"+Math.random()+"&ident="+feature.properties.callsign);
				}
				getLiveData();
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
              var output = '';
		
              //individual aircraft
		if (feature.minimal == "false" && feature.properties.type == "aircraft"){
		    output += '<div class="top">';
                    if (typeof feature.properties.image_source_website != 'undefined') {
                	if (typeof feature.properties.image_copyright != 'undefined') {
                	    output += '<div class="left"><a href="'+feature.properties.image_source_website+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a>Image &copy; '+ feature.properties.image_copyright+'</div>';
            		} else {
                	    output += '<div class="left"><a href="'+feature.properties.image_source_website+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a></div>';
			}
		    } else {
			if (typeof feature.properties.image_copyright != 'undefined') {
			    output += '<div class="left"><a href="/redirect/'+feature.properties.flightaware_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a>Image &copy; '+ feature.properties.image_copyright+'</div>';
			} else {
			    output += '<div class="left"><a href="/redirect/'+feature.properties.flightaware_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a></div>';
			}
		    }
		    output += '<div class="right">';
                    output += '<div class="callsign-details">';
                    output += '<div class="callsign"><a href="/redirect/'+feature.properties.flightaware_id+'" target="_blank">'+feature.properties.callsign+'</a></div>';
                    output += '<div class="airline">'+feature.properties.airline_name+'</div>';
                    output += '</div>';
                    output += '<div class="nomobile airports">';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span>'+feature.properties.departure_airport;
		    if (typeof feature.properties.departure_airport_time != 'undefined') {
			output += '<br /><span class="time">'+feature.properties.departure_airport_time+'</span>';
		    }
		    output += '</div>';
		    output += '<i class="fa fa-long-arrow-right"></i>';
		    output += '<div class="airport">';
                    output += '<span class="code"><a href="/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span>'+feature.properties.arrival_airport;
		    if (typeof feature.properties.arrival_airport_time != 'undefined') {
			output += '<br /><span class="time">'+feature.properties.arrival_airport_time+'</span>';
		    }
		    output += '</div>';
                    output += '</div>';
                    if (typeof feature.properties.route_stop != 'undefined') {
                	output += 'Route stop : '+feature.properties.route_stop;
                    }
                    output += '</div>';
                    output += '</div>';
                    output += '<div class="details">';
                    output += '<div class="mobile airports">';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="/airport/'+feature.properties.departure_airport_code+'" target="_blank">'+feature.properties.departure_airport_code+'</a></span>'+feature.properties.departure_airport;
                    output += '</div>';
                    output += '<i class="fa fa-long-arrow-right"></i>';
                    output += '<div class="airport">';
                    output += '<span class="code"><a href="/airport/'+feature.properties.arrival_airport_code+'" target="_blank">'+feature.properties.arrival_airport_code+'</a></span>'+feature.properties.arrival_airport;
                    output += '</div>';
                    output += '</div>';
                    output += '<div>';
                    output += '<span>Aircraft</span>';
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
                	output += '<span>Altitude</span>';
                        output += feature.properties.altitude+'00 feet - '+Math.round(feature.properties.altitude*30.48)+' m (FL'+feature.properties.altitude+')';
                        output += '</div>';
                    }
                    if (feature.properties.registration != "")
                    {
                	output += '<div>';
                        output += '<span>Registration</span>';
                        output += '<a href="/registration/'+feature.properties.registration+'" target="_blank">'+feature.properties.registration+'</a>';
                        output += '</div>';
                    }
                    output += '<div>';
                    output += '<span>Speed</span>';
                    output += feature.properties.ground_speed+' knots - '+Math.round(feature.properties.ground_speed*1.852)+' km/h';
                    output += '</div>';
                    output += '<div>';
                    output += '<span>Coordinates</span>';
                    output += feature.properties.latitude+", "+feature.properties.longitude;
                    output += '</div>';
                    output += '<div>';
                    output += '<span>Heading</span>';
                    output += feature.properties.heading;
                    output += '</div>';
            	    if (typeof feature.properties.pilot_name != 'undefined') {
                	output += '<div>';
                        output += '<span>Pilot</span>';
            		if (typeof feature.properties.pilot_id != 'undefined') {
                    	    output += feature.properties.pilot_name+" ("+feature.properties.pilot_id+")";
                        } else {
                    	    output += feature.properties.pilot_name;
                        }
                	output += '</div>';
                    }
            	    output += '</div>';
            	    if (typeof feature.properties.waypoints != 'undefined') {
            		output += '<div class="waypoints"><span>Route</span>';
            		output += feature.properties.waypoints;
            		output += '</div>';
            	    }
                    if (typeof feature.properties.acars != 'undefined') {
            		output += '<div class="acars"><span>Latest ACARS message</span>';
            		output += feature.properties.acars;
            		output += '</div>';
            	    }
            	    if (typeof feature.properties.squawk != 'undefined') {
                	output += '<div class="bottom">';
                	output += 'Squawk : ';
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

                if (feature.properties.type == "route"){
            	    var style = {
		    	"color": "#c74343",
		    	"weight": 2,
		    	"opacity": 0.5
		    };
		    layer.setStyle(style);
		    layer_data.addLayer(layer);
		}


                //aircraft history position as a line
                if (feature.properties.type == "history"){
		    <?php if (!isset($ident) && !isset($flightaware_id)) { ?>
		    if (document.getElementById('aircraft_ident').className == feature.properties.callsign) {
			if (map.getZoom() > 7) {
                	    var style = {
				<?php
				    if (isset($globalMapAltitudeColor) && !$globalMapAltitudeColor) {
				?>
				"color": "#1a3151",
				<?php
				    } else {
				?>
				"color": getAltitudeColor(feature.properties.altitude),
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
				"color": getAltitudeColor(feature.properties.altitude),
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
				"color": getAltitudeColor(feature.properties.altitude),
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
                    		"color": getAltitudeColor(feature.properties.altitude),
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
                    	    "color": getAltitudeColor(feature.properties.altitude),
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
			    "color": getAltitudeColor(feature.properties.altitude),
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
}).error(function() {
    map.removeLayer(layer_data);
    //info.update();
    });
}

  //load the function on startup
  getLiveData();

  //then load it again every 30 seconds
  setInterval(function(){if (noTimeout) getLiveData()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);

  //adds the bootstrap hover to the map buttons
  $('.button').tooltip({ placement: 'right' });

<?php
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



  
});

//adds the bootstrap tooltip to the map icons
function showBootstrapTooltip(){
    $('.leaflet-marker-icon').tooltip('destroy');
    $('.leaflet-marker-icon').tooltip({ html: true });
}

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
        title: "Your location",
        alt: "Your location",
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
      alert("Compass is not supported by this browser.");
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

function waypointsPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	    if (typeof feature.properties.segment_name != 'undefined') {
		output += '&nbsp;Segment name : '+feature.properties.segment_name+'<br /> ';
		output += '&nbsp;From : '+feature.properties.name_begin+' To : '+feature.properties.name_end+'<br /> ';
	    }
	    if (typeof feature.properties.ident != 'undefined') {
		output += '&nbsp;Ident : '+feature.properties.ident+'<br /> ';
	    }
	    if (typeof feature.properties.alt != 'undefined') {
		output += '&nbsp;Altitude : '+feature.properties.alt*100+' feet - ';
		output += Math.round(feature.properties.alt*30,48)+' m (FL'+feature.properties.alt+')<br />';

	    }
	    if (typeof feature.properties.base != 'undefined') {
		output += '&nbsp;Base Altitude: '+feature.properties.base*100+' feet - ';
		output += Math.round(feature.properties.base*30,48)+' m (FL'+feature.properties.base+')<br />';
		output += '&nbsp;Top Altitude: '+feature.properties.top*100+' feet - ';
		output += Math.round(feature.properties.top*30,48)+' m (FL'+feature.properties.top+')<br />';
	    }
//	    output += '&nbsp;Control : '+feature.properties.control+'<br />&nbsp;Usage : '+feature.properties.usage;
	output += '</div>';
	layer.bindPopup(output);
};

var lineStyle = {
	"color": "#ff7800",
	"weight": 1,
	"opacity": 0.65
};

function update_waypointsLayer() {
    var bbox = map.getBounds().toBBoxString();
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
    if (!$(".waypoints").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_waypointsLayer();
	//add the active class
	$(".waypoints").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(waypointsLayer);
	//remove the active class
	$(".waypoints").removeClass("active");
     }
}


function airspacePopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	    if (typeof feature.properties.title != 'undefined') {
		output += '&nbsp;Title : '+feature.properties.title+'<br /> ';
	    }
	    if (typeof feature.properties.type != 'undefined') {
		output += '&nbsp;Type : '+feature.properties.type+'<br /> ';
	    }
	    if (typeof feature.properties.tops != 'undefined') {
		output += '&nbsp;Tops : '+feature.properties.tops+'<br /> ';
	    }
	    if (typeof feature.properties.base != 'undefined') {
		output += '&nbsp;Base : '+feature.properties.base+'<br /> ';
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
	    if (feature.properties.type == 'RESTRICTED' || feature.properties.type == 'CLASS D') {
		return {
		    "color": '#ff5100',
		    "weight": 1,
		    "opacity": 0.55
		};
	    } else if (feature.properties.type == 'GSEC' || feature.properties.type == 'CLASS C') {
		return {
		    "color": '#fff000',
		    "weight": 1,
		    "opacity": 0.55
		};
	    } else if (feature.properties.type == 'PROHIBITED') {
		return {
		    "color": '#ff0000',
		    "weight": 1,
		    "opacity": 0.55
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
	}
    }).addTo(map);
};

function showAirspace() {
    if (!$(".airspace").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_airspaceLayer();
	//add the active class
	$(".airspace").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(airspaceLayer);
	//remove the active class
	$(".airspace").removeClass("active");
     }
}

function genLayerPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	if (typeof feature.properties.text != 'undefined') output += '&nbsp;'+feature.properties.text+'<br /> ';
	output += '</div>';
	layer.bindPopup(output);
};

function update_genLayer(url) {
//    var bbox = map.getBounds().toBBoxString();
//    notamLayer = new L.GeoJSON.AJAX(url+"?coord="+bbox,{
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
    notamLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox,{
    onEachFeature: notamPopup,
	pointToLayer: function (feature, latlng) {
	    return L.circle(latlng, feature.properties.radius, {
                    fillColor: feature.properties.color,
                    color: feature.properties.color,
                    weight: 1,
                    opacity: 0.3,
                    fillOpacity: 0.3
            });
	}
    }).addTo(map);
};

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

function showNotam() {
    if (!$(".notam").hasClass("active"))
    {
	//loads the function to load the waypoints
	update_notamLayer();
	//add the active class
	$(".notam").addClass("active");
    } else {
	//remove the waypoints layer
	map.removeLayer(notamLayer);
	//remove the active class
	$(".notam").removeClass("active");
     }
}


function flightPopup() {
    if (!$(".flightpopup").hasClass("active"))
    {
	document.cookie =  'flightpopup=true; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//add the active class
	$(".flightpopup").addClass("active");
	// FIXME : Don't reload page (for now not working without reload)
	window.location.reload();
    } else {
	document.cookie =  'flightpopup=false; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//remove the active class
	$(".flightpopup").removeClass("active");
	window.location.reload();
     }
}

function flightPath() {
    if (!$(".flightpath").hasClass("active"))
    {
	document.cookie =  'flightpath=true; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//add the active class
	$(".flightpath").addClass("active");
	window.location.reload();
    } else {
	document.cookie =  'flightpath=false; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//remove the active class
	$(".flightpath").removeClass("active");
	window.location.reload();
     }
}
function flightRoute() {
    if (!$(".flightroute").hasClass("active"))
    {
	document.cookie =  'MapRoute=true; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//add the active class
	$(".flightroute").addClass("active");
    } else {
	document.cookie =  'MapRoute=false; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	//remove the active class
	$(".flightroute").removeClass("active");
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
    document.cookie =  'Airlines='+airs.join()+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
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

function clickVATSIM(cb) {
    document.cookie =  'ShowVATSIM='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
function clickIVAO(cb) {
    document.cookie =  'ShowIVAO='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}