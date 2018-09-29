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
<?php

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;
$compress = false;
?>


//var map;
var geojsonSatelliteLayer;
var noTimeout = true;
var layer_satellite_data = L.layerGroup();

<?php
	if (isset($_GET['famsatid'])) {
		$famsatid = filter_input(INPUT_GET,'famsatid',FILTER_SANITIZE_STRING);
	}
	if (isset($_GET['ident'])) {
		$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	}
	if (!isset($ident) && !isset($famsatid)) {
?>
	function info_satellite_update (props) {
		$("#ibxsatellite").html('<h4><?php echo _("Satellites displayed"); ?></h4>' +  '<b>' + props + '</b>');
	}
<?php
	}
?>

	<?php
	/*
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
	    */
	?>

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#pointident").attr('class','');
	getSatelliteLiveData(1);
	return false;
})


$("#pointident").attr('class','');
var MapTrackSatellite = getCookie('MapTrackSatellite');
if (MapTrackSatellite != '') {
	$("#pointident").attr('class',MapTrackSatellite);
	$("#pointtype").attr('class','satellite');
	$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat="+MapTrackSatellite);
	delCookie('MapTrackSatellite');
}

function updateSat(click)
{
	var bbox = map.getBounds().toBBoxString();
	//layer_data_p = L.layerGroup();
	$.ajax({
	    dataType: "json",
	    //      url: "live/geojson?"+Math.random(),
<?php
	if (isset($ident)) {
?>
	    url: "<?php print $globalURL; ?>/live-sat-geojson.php?"+Math.random()+"&ident="+encodeURI(<?php print $ident; ?>)+"&history",
<?php
	} elseif (isset($famsatid)) {
?>
	    url: "<?php print $globalURL; ?>/live-sat-geojson.php?"+Math.random()+"&famsatid="+encodeURI(<?php print $famsatid; ?>)+"&history",
<?php
	} elseif (isset($archive) && $archive) {
?>
            url: "<?php print $globalURL; ?>/live-sat-geojson.php?"+Math.random()+"&history="+encodeURI(document.getElementById('pointident').className)+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed=<?php print $archivespeed; ?>",
<?php
	} else {
?>
	    url: "<?php print $globalURL; ?>/live-sat-geojson.php?"+Math.random()+"&history="+encodeURI(document.getElementById('pointident').className),
<?php 
	}
?>
	    success: function(data) {
		map.removeLayer(layer_satellite_data);
		if (document.getElementById('pointident').className != "") {
			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat="+encodeURI(document.getElementById('pointident').className));
		}
		layer_satellite_data = L.layerGroup();
		var nbsat = 0;
		var live_satellite_data = L.geoJson(data, {
		    pointToLayer: function (feature, latLng) {
		    var markerSatelliteLabel = "";
		    //if (feature.properties.callsign != ""){ markerSatelliteLabel += feature.properties.callsign+'<br />'; }
		    //if (feature.properties.departure_airport_code != "" || feature.properties.arrival_airport_code != ""){ markerSatelliteLabel += '<span class="nomobile">'+feature.properties.departure_airport_code+' - '+feature.properties.arrival_airport_code+'</span>'; }
<?php
	if ($compress) {
?>
		    var callsign = feature.properties.c;
		    var famsatid = encodeURI(feature.properties.fti);
		    var aircraft_shadow = feature.properties.as;
		    var altitude = feature.properties.a;
		    var heading = feature.properties.h;
		    var type = feature.properties.t;
<?php
	} else {
?>
		    var callsign = feature.properties.callsign;
		    var famsatid = encodeURI(feature.properties.famsatid);
		    var aircraft_shadow = feature.properties.aircraft_shadow;
		    var altitude = feature.properties.altitude;
		    var heading = feature.properties.heading;
		    var type = feature.properties.type;
<?php
	}
?>
		    if (type == "satellite"){ nbsat = nbsat +1; }
		    if (callsign != ""){ markerSatelliteLabel += callsign; }
		    if (type != ""){ markerSatelliteLabel += ' - '+type; }
<?php
	if (isset($_COOKIE['SatelliteIconColor'])) $IconColor = $_COOKIE['SatelliteIconColor'];
	elseif (isset($globalSatelliteIconColor)) $IconColor = $globalSatelliteIconColor;
	else $IconColor = '1a3151';
	if (!isset($ident) && !isset($famsatid)) {
?>
		    info_satellite_update(feature.properties.fc);
		    if (document.getElementById('pointident').className == callsign || document.getElementById('pointident').className == famsatid) {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=FF0000&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=8D93B9&filename='+aircraft_shadow;
		    } else {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=<?php print $IconColor; ?>&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=8D93B9&filename='+aircraft_shadow;
		    }
<?php
	} else {
?>
		    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=<?php print $IconColor; ?>&filename='+aircraft_shadow;
		    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?satellite&color=8D93B9&filename='+aircraft_shadow;
<?php
	}
	if (isset($globalAircraftSize) && $globalAircraftSize != '') {
?>
<?php
		if ((!isset($_COOKIE['satelliteestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['satelliteestimation']) && $_COOKIE['satelliteestimation'] == 'false')) {
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
			//rotationAngle: heading,
			//iconAngle: heading,
			title: markerSatelliteLabel,
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
		if (isset($globalMapPermanentTooltip) && $globalMapPermanentTooltip) {
?>
		    .bindTooltip(callsign, {permanent: true, className: "maptooltip", direction: "bottom"})
<?php
		}
?>
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
		    .on('click', function() {
				//if (callsign == "NA") {
				    $("#pointident").attr('class',famsatid);
				    $("#pointtype").attr('class','satellite');
				    $(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat="+famsatid);
				/*
				} else {
				    $("#pointident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/satellite-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				updateSat(1);
			});
<?php
		}
?>
<?php
	} else {
?>
		    if (map.getZoom() > 7) {
<?php
		if ((!isset($_COOKIE['satelliteestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['satelliteestimation']) && $_COOKIE['satelliteestimation'] == 'false')) {
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
				//rotationAngle: heading,
				autostart: true,
			        //iconAngle: heading,
				title: markerSatelliteLabel,
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
		if (isset($globalMapPermanentTooltip) && $globalMapPermanentTooltip) {
?>
			    .bindTooltip(callsign, {permanent: true, className: "maptooltip", direction: "bottom"})
<?php
		}
?>
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
			    .on('click', function() {
				//$("#pointident").attr('class',callsign);
				//if (callsign == "NA") {
					$("#pointident").attr('class',famsatid);
					$("#pointtype").attr('class','satellite');
					$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat="+famsatid);
				/*
				} else {
					$("#pointident").attr('class',callsign);
					$(".showdetails").load("<?php print $globalURL; ?>/satellite-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				updateSat(1);
			});
<?php
		}
?>
		    } else {
<?php
		if ((!isset($_COOKIE['satelliteestimation']) && isset($globalMapEstimation) && $globalMapEstimation == FALSE) || (isset($_COOKIE['satelliteestimation']) && $_COOKIE['satelliteestimation'] == 'false')) {
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
				//rotationAngle: heading,
				autostart: true,
				//iconAngle: heading,
				title: markerSatelliteLabel,
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
		if (isset($globalMapPermanentTooltip) && $globalMapPermanentTooltip) {
?>
			    .bindTooltip(callsign, {permanent: true, className: "maptooltip", direction: "bottom"})
<?php
		}
?>
<?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
			    .on('click', function() {
				//if (callsign == "NA") {
				    $("#pointident").attr('class',famsatid);
				    $("#pointtype").attr('class','satellite');
				    $(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&sat="+famsatid);
				/*
				} else {
				    $("#pointident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/satellite-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				updateSat(1);
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
		layer_satellite_data.addLayer(layer);
		if (type == "route"){
		    var style = {
		    	"color": "#c74343",
		    	"weight": 2,
		    	"opacity": 0.5
		    };
		    layer.setStyle(style);
		    layer_satellite_data.addLayer(layer);
		}


                //aircraft history position as a line
                if (type == "history"){
		    <?php if (!isset($ident) && !isset($famsatid)) { ?>
		    if (document.getElementById('pointident').className == callsign) {
			if (map.getZoom() > 7) {
                	    var style = {
				"color": "#1a3151",
				"weight": 3,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_satellite_data.addLayer(layer);
			} else {
			    var style = {
				"color": "#1a3151",
				"weight": 2,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_satellite_data.addLayer(layer);
			}
            	    } else {
			if (map.getZoom() > 7) {
                	    var style = {
                    		"color": "#1a3151",
				"weight": 3,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_satellite_data.addLayer(layer);
			} else {
                	    var style = {
                    		"color": "#1a3151",
				"weight": 2,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_satellite_data.addLayer(layer);
			}
                    }
		    <?php
            		} else {
            	    ?>
		    if (map.getZoom() > 7) {
                	var style = {
                    	    "color": "#1a3151",
                    	    "weight": 3,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_satellite_data.addLayer(layer);
		    } else {
                	var style = {
			    "color": "#1a3151",
                    	    "weight": 2,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_satellite_data.addLayer(layer);
		    }
<?php
            		}
?>
				}
			    }
			});
			layer_satellite_data.addTo(map);
			//re-create the bootstrap tooltips on the marker 
			//showBootstrapTooltip();
			//console.log(nbsat);
			info_satellite_update(nbsat);
		}
//		console.log(nb);
//		info_satellite_update(nb);
	});
//		console.log(nb);
	//  updateSat(0);
}

$( document ).ready(function() {
 //load the function on startup
updateSat(0);


<?php
	if (isset($archive) && $archive) {
?>
//then load it again every 30 seconds
//  var reload = setInterval(function(){if (noTimeout) updateSat(0)},<?php if (isset($globalMapRefresh)) print ($globalMapRefresh*1000)/2; else print '15000'; ?>);
var reloadSatellitePage = setInterval(function(){if (noTimeout) updateSat(0)},<?php print $archiveupdatetime*1000; ?>);
<?php
	} else {
?>
//then load it again every 30 seconds
var reloadSatellitePage = setInterval(
    function(){if (noTimeout) updateSat(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
<?php
	}
?>
function SatelliteiconColor(color) {
    document.cookie =  'SatelliteIconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
});
function clickSatelliteEstimation(cb) {
    document.cookie =  'satelliteestimation='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
