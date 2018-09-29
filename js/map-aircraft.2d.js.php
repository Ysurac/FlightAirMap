<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php'); 
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

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;

if (isset($_GET['ident'])) $ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
if (isset($_GET['flightaware_id'])) $flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
?>
<?php
if (isset($_COOKIE['IconColor'])) $IconColor = $_COOKIE['IconColor'];
elseif (isset($globalAircraftIconColor)) $IconColor = $globalAircraftIconColor;
else $IconColor = '1a3151';
?>
<?php
if (isset($globalDebug) && $globalDebug === TRUE) {
?>
var globaldebug = true;
<?php
} else {
?>
var globaldebug = false;
<?php
}
?>


var user = new L.FeatureGroup();

var geojsonLayer;
var atcLayer;
var polarLayer;
var notamLayer;
var airspaceLayer;
//var archiveplayback;
waypoints = '';

//initialize the layer group for the aircrft markers
var layer_data = L.layerGroup();

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
	if (typeof airportsLayer != 'undefined') {
		if (map.hasLayer(airportsLayer) == true) {
			map.removeLayer(airportsLayer);
		}
	}
	if (map.getZoom() > <?php print $getZoom; ?>) {
		var bbox = map.getBounds().toBBoxString();
		var airportsLayerQuery = $.getJSON("<?php print $globalURL; ?>/airport-geojson.php?coord="+bbox,function(data) {
			airportsLayer = L.geoJson(data,{
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
						$("#pointident").attr('class','');
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
		});
	}
};


$( document ).ready(function() {
	var zoom = map.getZoom();
	if (map.getZoom() > 7) {
		if (getCookie("airspace") == 'true')
		{
			update_airspaceLayer();
		}
		if (getCookie("waypoints") == 'true')
		{
			update_waypointsLayer();
		}
		if (getCookie("notam") == 'true')
		{
			update_notamLayer();
		}
	}
	map.on('moveend', function() {
		if (map.getZoom() > 7) {
			if (getCookie("displayairports") == 'true') update_airportsLayer();
			if (getCookie("airspace") == 'true')
			{
				if (typeof airspaceLayer != 'undefined') map.removeLayer(airspaceLayer);
				update_airspaceLayer();
			}
			if (getCookie("waypoints") == 'true')
			{
				if (typeof waypointsLayer != 'undefined') map.removeLayer(waypointsLayer);
				update_waypointsLayer();
			}
		} else {
			if (getCookie("displayairports") == 'true') update_airportsLayer();
			if (getCookie("airspace") == 'true')
			{
				if (typeof airspaceLayer != 'undefined') map.removeLayer(airspaceLayer);
			}
			if (getCookie("waypoints") == 'true')
			{
				if (typeof waypointsLayer != 'undefined') map.removeLayer(waypointsLayer);
			}
		}
		if (getCookie("notam") == 'true')
		{
			if (typeof notamLayer != 'undefined') map.removeLayer(notamLayer);
			update_notamLayer();
		}
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
		if (archive === false) {
			getLiveData(1);
		}
<?php
	}
?>
	});
	map.on('zoomend', function() {
<?php
	if (!isset($globalMapUseBbox) || $globalMapUseBbox === FALSE) {
?>
		if (archive === false) {
			if ((map.getZoom() > 7 && zoom < 7) || (map.getZoom() < 7 && zoom > 7)) {
				zoom = map.getZoom();
				getLiveData(1);
			}
		}
<?php
	}
?>
	});

	//update_waypointsLayer();
	if (getCookie("displayairports") == 'true') update_airportsLayer();
<?php
	if (!isset($ident) && !isset($flightaware_id)) {
?>
	function info_update (props) {
		$("#ibxaircraft").html('<h4><?php echo _("Aircraft detected"); ?></h4>' +  '<b>' + props + '</b>');
	}

<?php
	}
?>
	if (archive === true) {
		function archive_update (props) {
			document.getElementById('archivebox').style.display = "block";
			if (typeof props != 'undefined') {
				var thedate = new Date(props);
				$("#thedate").html(thedate.toUTCString());
			//	$("#archivebox").html('<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b><i class="fa fa-spinner fa-pulse fa-2x fa-fw margin-bottom"></i></b>');
			}
		}
	}

	$(".showdetails").on("click",".close",function(){
		$(".showdetails").empty();
		$("#pointident").attr('class','');
		if (archive === false) {
			getLiveData(1);
		}
		return false;
	});
<?php
	if (!isset($ident) && !isset($flightaware_id)) {
?>
	//var sidebar = L.control.sidebar('sidebar').addTo(map);
<?php
	}
?>

$("#pointident").attr('class','');
var MapTrack = getCookie('MapTrack');
if (MapTrack != '') {
	$("#pointident").attr('class',MapTrack);
	$("#pointtype").attr('class','aircraft');
	$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+MapTrack);
	delCookie('MapTrack');
}

function getLiveData(click)
{
	var bbox = map.getBounds().toBBoxString();
	//var layer_data_p = L.layerGroup();
	var now = new Date();
<?php
	if (isset($ident)) {
?>
	var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&ident=<?php print $ident; ?>&history";
<?php
	} elseif (isset($flightaware_id)) {
?>
	var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&flightaware_id=<?php print $flightaware_id; ?>&history";
<?php
	} else {
?>
	if (click == 1) {
		var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('pointident').className+"&currenttime="+now.getTime();
	} else {
		var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('pointident').className;
	}
<?php 
	}
?>


	$.ajax({
		dataType: "json",
		//      url: "live/geojson?"+Math.random(),
		url: defurl,
		success: function(data) {
			map.removeLayer(layer_data);
			if (document.getElementById('pointident') && document.getElementById('pointident').className != "") {
				$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+document.getElementById('pointident').className);
			}
			layer_data = L.layerGroup();
			var nbaircraft = 0;
			var flightcount = 0;
			var datatable = '';
			var live_data = L.geoJson(data, {
				pointToLayer: function (feature, latLng) {
					var markerLabel = "";
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
					flightcount = feature.properties.fc;
					if (typeof feature.properties.empty != 'undefined') {
						return;
					}
					if (type != "history") nbaircraft = nbaircraft+1;
					if (callsign != ""){ markerLabel += callsign; }
					if (departure_airport_code != "" && arrival_airport_code != "" && departure_airport_code != "NA" && arrival_airport_code != "NA"){ markerLabel += ' ( '+departure_airport_code+' - '+arrival_airport_code+' )'; }
<?php
	if (!isset($ident) && !isset($flightaware_id)) {
?>
					//info_update(feature.properties.fc);
					if (document.getElementById('pointident').className == callsign || document.getElementById('pointident').className == flightaware_id) {
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
						autostart: true,
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
						$("#pointident").attr('class',flightaware_id);
						$("#pointtype").attr('class','aircraft');
						$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
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
							$("#pointident").attr('class',flightaware_id);
							$("#pointtype").attr('class','aircraft');
							$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
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
								$("#pointident").attr('class',flightaware_id);
								$("#pointtype").attr('class','aircraft');
								$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id);
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
						var id = feature.properties.fi;
						var altitude = feature.properties.a;
						var type = feature.properties.t;
						var callsign = feature.properties.c;
						var dairport = feature.properties.dac;
						var aairport = feature.properties.aac;
						var squawk = feature.properties.sq;
						var coord = feature.geometry.coordinates;
						var lastupdate = feature.properties.lu;
						var aircraft_icao = feature.properties.ai;
						var registration = feature.properties.reg;
<?php
	} else {
?>
						var altitude = feature.properties.altitude;
						var type = feature.properties.type;
						var callsign = feature.properties.callsign;
<?php
	}
?>
						if (type == 'aircraft') {
							if (unitaltitudevalue == 'm') {
								var txtaltitude = Math.round(altitude*30.48)+' m (FL'+Math.round(altitude)+')';
							} else {
								var txtaltitude = altitude+' feet (FL'+Math.round(altitude)+')';
							}
							if (unitcoordinatevalue == 'dms') {
								var latitude = convertDMS(coord[1],'latitude');
								var longitude = convertDMS(coord[0],'longitude');
							} else if (unitcoordinatevalue == 'dm') {
								var latitude = convertDM(coord[1],'latitude');
								var longitude = convertDM(coord[0],'longitude');
							} else {
								var latitude = coord[1];
								var longitude = coord[0];
							}
							if (typeof squawk == 'undefined') squawk = 'NA';
							var lastupdatedate = new moment.tz(lastupdate*1000,moment.tz.guess()).format("HH:mm:ss");
							datatable += '<tr class="table-row" data-id="'+id+'" data-latitude="'+coord[1]+'" data-longitude="'+coord[0]+'"><td>'+callsign+'</td><td>'+registration+'</td><td>'+aircraft_icao+'</td><td>'+txtaltitude+'</td><td>'+dairport+'</td><td>'+aairport+'</td><td>'+squawk+'</td><td>'+latitude+'</td><td>'+longitude+'</td><td>'+lastupdatedate+'</td></tr>';
						}
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
<?php 
	if (!isset($ident) && !isset($flightaware_id)) { 
?>
							layer.bindPopup(output);
<?php 
	}
?>
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
						if (type == "routedest"){
							var styled = {
								"color": "#945add",
								"weight": 2,
								"opacity": 1.0,
								"dashArray": "6"
							};
							layer.setStyle(styled);
							layer_data.addLayer(layer);
						}

						//aircraft history position as a line
						if (type == "history"){
<?php 
	if (!isset($ident) && !isset($flightaware_id)) { 
?>
							if (document.getElementById('pointident').className == callsign) {
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
				
				if (datatable != '') {
					$('#datatable').css('height','20em');
					$('#datatable').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th>Callsign</th><th>Registration</th><th>Aircraft ICAO</th><th>Altitude</th><th>Departure airport</th><th>Arrival airport</th><th>Squawk</th><th>Latitude</th><th>Longitude</th><th>Last update</th></tr></thead><tbody>'+datatable+'</tbody></table></div>');
					$(".table-row").click(function () {
						$("#pointident").attr('class',$(this).data('id'));
						$("#pointtype").attr('class','aircraft');
						$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+$(this).data('id'));
						getLiveData(1);
						map.panTo([$(this).data('latitude'),$(this).data('longitude')]);
					});

				}
				
				layer_data.addTo(map);
				//re-create the bootstrap tooltips on the marker 
				//showBootstrapTooltip();
				if (typeof info_update != "undefined") {
					if (typeof flightcount != "undefined" && flightcount != 0) {
						if (flightcount != nbaircraft) {
							info_update(nbaircraft+'/'+flightcount);
						} else {
							info_update(nbaircraft);
						}
					} else {
						info_update(nbaircraft);
					}
				}
			}
		});
		//  getLiveData(0);
	}


function update_archiveLayer(click) {
	$("#infobox").html('<?php echo _("Loading archive"); ?> <i class="fa fa-spinner fa-pulse fa-rw"></i>');
	var bbox = map.getBounds().toBBoxString();
	var begindate = parseInt(getCookie("archive_begin"));
	var enddate = parseInt(getCookie("archive_end"));
	//var finaldate = parseInt(getCookie("archive_end"))*1000;
	//var enddate = begindate+parseInt(getCookie("archive_update"));
	//var enddate = begindate+3600;
	var finaldate = enddate*1000;
	//console.log(finaldate);
	/*
	if (enddate > getCookie("archive_end")) {
		enddate = parseInt(getCookie("archive_end"));
		//clearInterval(reloadPage);
	} else {
		if (click != 1) {
			document.cookie =  'archive_begin='+enddate+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
		}
	}
	*/
	var archivespeed = parseInt(getCookie("archive_speed"));
	var lasticon;
	var playbackOptions = {
		orientIcons: true,
		clickCallback: function(event) { 
			var flightaware_id = event.target.feature.properties.fi;
			var currentdate = (begindate + event.originalEvent.timeStamp)*1000;
			$("#pointident").attr('class',flightaware_id);
			$("#pointtype").attr('class','aircraft');
			$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id+"&currenttime="+currentdate);
			var aircraft_shadow = event.target.feature.properties.as;
			if (typeof lasticon != 'undefined') {
				lasticon.target._icon.src = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+lasticon.target.feature.properties.as;
			}
			lasticon = event;
			event.target._icon.src = '<?php print $globalURL; ?>/getImages.php?color=FF0000&filename='+aircraft_shadow;
			/*
			archiveplayback._tracksLayer.addLayer(event.target.feature);
			console.log(event);
			console.log(archiveplayback);
			*/
		},
		marker: function(feature){
			var aircraft_shadow = feature.properties.as;
			var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $IconColor; ?>&filename='+aircraft_shadow;
			return {
				icon: L.icon({
					iconUrl: iconURLpath,
					iconSize: [30, 30],
					iconAnchor: [15, 30]
				})
			}
		},
		layer: {
			onEachFeature : function (feature, layer) {
				var style = {
				    "color": "#1a3151",
				    "weight": 2,
				    "opacity": 1
				};
				layer.setStyle(style);
			}
		},
		fadeMarkersWhenStale: true,
		finalTime: finaldate,
		staleTime: 60,
		speed: archivespeed,
		orientIcons: true,
		maxInterpolationTime: 30*60*1000,
		tracksLayer: false,
		playControl: false,
		layerControl: false,
		sliderControl: false
	};
	var alldata = [];
	var part = 0;
	//do {
	//part += 1;
	//console.log('part: '+part);
	var url = "<?php print $globalURL; ?>/archive-geojson.php?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('pointident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed="+archivespeed+"&part="+part;
	var archivegeoJSONQuery = $.getJSON(url, function(data) {
		alldata = [];
		var archiveLayerGroup = L.layerGroup();
		var archivegeoJSON = L.geoJson(data, {
			onEachFeature: function(feature,layer) {
				alldata.push(feature);
			}
		});
		if (typeof archiveplayback == 'undefined') {
			$("#infobox").remove();
			document.getElementById('archivebox').style.display = "block";
			$("#archivebox").html('<h4><?php echo _("Archive"); ?></h4>' +  '<b><span id="thedate"></span></b>' + '<br/><a href="#" onClick="noarchive();"><i class="fa fa-eject" aria-hidden="true"></i></a> <a href="#" onClick="archivePause();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="archivePlay();"><i class="fa fa-play" aria-hidden="true"></i></a><br/><div class="range archive"><input type="range" min="1" id="archiveboxspeed" max="50" size="10" step="1" onInput="archiveboxspeedrange.value=value;" onChange="archiveboxspeedrange.value=value;archiveplayback.setSpeed(value);" value="'+getCookie('archive_speed')+'"/><output id="archiveboxspeedrange">'+getCookie('archive_speed')+'</output></div>');
			archiveplayback = new L.Playback(map,alldata,archive_update,playbackOptions);
			archiveplayback.setCursor(begindate*1000);
			archiveplayback.start();
		} else {
			archiveplayback.addData(alldata);
		}
	}).fail(function(jqxhr, textStatus, error) {
		if (globaldebug) {
			var err = textStatus + ", " + error;
			console.log("Can't load archive json: "+err+"\nURL: "+url);
			msgbox("Can't load archive json: <i>"+err+'</i><br><b>URL:</b> <a href="'+location.href.substring(0, location.href.lastIndexOf('/'))+url+'">'+location.href.substring(0, location.href.lastIndexOf('/'))+url+'</a>');
		}
	});
	//} while (alldata.length > 0);
};


	if (archive === false) {
		//load the function on startup
		getLiveData(0);
	}
	if (archive === true) {
		console.log('Load Archive geoJson');
		var archiveupdatetime = parseInt(getCookie('archive_update'));
		//reloadPage = setInterval(function(){if (noTimeout) update_archiveLayer(0)},archiveupdatetime*1000);
		update_archiveLayer(0);
	} else {
		//then load it again every 30 seconds
		var reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
<?php
		if (!((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) && (isset($_COOKIE['polar']) && $_COOKIE['polar'] == 'true')) {
?>
		update_polarLayer();
		setInterval(function(){map.removeLayer(polarLayer);update_polarLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
<?php
		}
?>
	}
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


});

function atcPopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	output += '<div class="atcname">'+feature.properties.ident+'</div>';
	output += '&nbsp;'+feature.properties.info+'<br /> ';
	output += '</div>';
	layer.bindPopup(output);
};


function update_atcLayer() {
	var bbox = map.getBounds().toBBoxString();
	var atcLayerQuery = $.getJSON("<?php print $globalURL; ?>/atc-geojson.php?coord="+bbox,function(data) {
		atcLayer = L.geoJson(data, {
			onEachFeature: atcPopup,
			pointToLayer: function (feature, latlng) {
				if (feature.properties.atc_range > 0) {
					var atccolor = feature.properties.atccolor;
					return L.circle(latlng, feature.properties.atc_range*1, {
						fillColor: atccolor,
						color: atccolor,
						weight: 1,
						opacity: 0.3,
						fillOpacity: 0.3
					});
				} else {
					if (feature.properties.type == 'Delivery') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_del.png',
								iconSize: [15, 15],
								iconAnchor: [7, 7]
							})
						});
					} else if (feature.properties.type == 'Ground') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_gnd.png',
								iconSize: [20, 20],
								iconAnchor: [10, 10]
							})
						});
					} else if (feature.properties.type == 'Tower') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_twr.png',
								iconSize: [25, 25],
								iconAnchor: [12, 12]
							})
						});
					} else if (feature.properties.type == 'Approach') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_app.png',
								iconSize: [30, 30],
								iconAnchor: [15, 15]
							})
						});
					} else if (feature.properties.type == 'Departure') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_dep.png',
								iconSize: [35, 35],
								iconAnchor: [17, 17]
							})
						});
					} else if (feature.properties.type == 'Control Radar or Centre') {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc_ctr.png',
								iconSize: [40, 40],
								iconAnchor: [20, 20]
							})
						});
					} else {
						return L.marker(latlng, {
							icon: L.icon({
								iconUrl: '<?php print $globalURL; ?>/images/atc.png',
								iconSize: [30, 30],
								iconAnchor: [15, 30]
							})
						});
					}
				}
			}
		}).addTo(map);
	});
};

function update_polarLayer() {
	var polarLayerQuery = $.getJSON("<?php print $globalURL; ?>/polar-geojson.php", function(data){
		polarLayer = L.geoJson(data, {
			style: function(feature) {
				return feature.properties.style
			}
		}).addTo(map);
	});
};

function showNotam(cb) {
	createCookie('notam',cb.checked,9999);
	if (cb.checked == true) {
		update_notamLayer();
	} else {
		if (typeof notamLayer != 'undefined') map.removeLayer(notamLayer);
	}
}
function notamscope(selectObj) {
	var idx = selectObj.selectedIndex;
	var scope = selectObj.options[idx].value;
	createCookie('notamscope',scope,9999);
	if (getCookie("notam") == 'true')
	{
		if (typeof notamLayer != 'undefined') map.removeLayer(notamLayer);
		update_notamLayer();
	}
}

function iconColor(color) {
	createCookie('IconColor',color.substring(1),9999);
	window.location.reload();
}
function iconColorAltitude(val) {
	createCookie('IconColorAltitude',val.checked,9999);
	window.location.reload();
}

function airportDisplayZoom(zoom) {
	createCookie('AirportZoom',zoom,9999);
	window.location.reload();
}

function clickFlightPopup(cb) {
	createCookie('flightpopup',cb.checked,9999);
	window.location.reload();
}
function clickFlightPath(cb) {
	createCookie('flightpath',cb.checked,9999);
	window.location.reload();
}
function clickFlightRoute(cb) {
	createCookie('MapRoute',cb.checked,9999);
	window.location.reload();
}
function clickFlightRemainingRoute(cb) {
	createCookie('MapRemainingRoute',cb.checked,9999);
	window.location.reload();
}
function clickFlightEstimation(cb) {
	createCookie('flightestimation',cb.checked,9999);
	window.location.reload();
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
		url = "<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox+"&scope="+getCookie("notamscope");
	}
	//notamLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox,{
	var notamLayerQuery = $.getJSON(url,function(data) {
		notamLayer = L.geoJson(data, {
			//	onEachFeature: notamPopup,
			pointToLayer: function (feature, latlng) {
				var circle = L.circle(latlng, feature.properties.radius, {
					fillColor: feature.properties.color,
					color: feature.properties.color,
					weight: 1,
					opacity: 0.3,
					fillOpacity: 0.3
				}).on('click', function() {
					$("#pointident").attr('class','');
					$(".showdetails").load("notam-data.php?"+Math.random()+"&notam="+encodeURI(feature.properties.ref));
				});
				return circle;
			}
		}).addTo(map);
	});
};

function update_waypointsLayer() {
	var bbox = map.getBounds().toBBoxString();
	var lineStyle = {
		"color": "#ff7800",
		"weight": 1,
		"opacity": 0.65
	};

	var waypointsLayerQuery = $.getJSON("<?php print $globalURL; ?>/waypoints-geojson.php?coord="+bbox,function(data) {
		waypointsLayer = L.geoJson(data, {
			onEachFeature: waypointsPopup,
			pointToLayer: function (feature, latlng) {
				return L.marker(latlng, {
					icon: L.icon({
						iconUrl: feature.properties.icon,
						iconSize: [12, 13],
						iconAnchor: [2, 13]
						//popupAnchor: [0, -28]
					})
				});
			},
			style: lineStyle
		}).addTo(map);
	});
};

function showWaypoints(cb) {
	createCookie('waypoints',cb.checked,9999);
	if (cb.checked == true) {
		update_waypointsLayer();
	} else {
		if (typeof waypointsLayer != 'undefined') map.removeLayer(waypointsLayer);
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

function showAirspace(cb) {
	createCoookie('airspace',cb.checked,9999);
	if (cb.checked == true) {
		update_airspaceLayer();
	} else {
		if (typeof airspaceLayer != 'undefined') map.removeLayer(airspaceLayer);
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
	var airspaceLayerQuery = $.getJSON("<?php print $globalURL; ?>/airspace-geojson.php?coord="+bbox,function(data) {
		airspaceLayer = L.geoJson(data,{
			onEachFeature: airspacePopup,
			pointToLayer: function (feature, latlng) {
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
	});
};

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
