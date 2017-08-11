<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php'); 

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;

if (isset($_GET['ident'])) $ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
if (isset($_GET['flightaware_id'])) $flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
?>


var user = new L.FeatureGroup();

var geojsonLayer;
var atcLayer;
var polarLayer;
var santaLayer;
var notamLayer;
waypoints = '';

//initialize the layer group for the aircrft markers
layer_data = L.layerGroup();

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
						$("#aircraft_ident").attr('class','');
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
	map.on('moveend', function() {
		if (map.getZoom() > 7) {
			if (getCookie("displayairports") == 'true') update_airportsLayer();
			if ($("#airspace").hasClass("active"))
			{
				map.removeLayer(airspaceLayer);
				update_airspaceLayer();
			}
			if ($("#waypoints").hasClass("active"))
			{
				map.removeLayer(waypointsLayer);
				update_waypointsLayer();
			}
		} else {
			if (getCookie("displayairports") == 'true') update_airportsLayer();
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
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
		getLiveData(1);
<?php
	}
?>
	});
	map.on('zoomend', function() {
<?php
	if (!isset($globalMapUseBbox) || $globalMapUseBbox === FALSE) {
?>
		if ((map.getZoom() > 7 && zoom < 7) || (map.getZoom() < 7 && zoom > 7)) {
			zoom = map.getZoom();
			getLiveData(1);
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
		$("#ibxaircraft").html('<h4><?php echo _("Aircrafts detected"); ?></h4>' +  '<b>' + props + '</b>');
	}

<?php
	}
?>
<?php
	if (isset($archive) && $archive) {
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

	$(".showdetails").on("click",".close",function(){
		$(".showdetails").empty();
		$("#aircraft_ident").attr('class','');
		getLiveData(1);
		return false;
	})

<?php
	if (!isset($ident) && !isset($flightaware_id)) {
?>
	//var sidebar = L.control.sidebar('sidebar').addTo(map);
<?php
	}
?>


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
	if (isset($archive) && $archive) {
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
	} elseif (isset($archive) && $archive) {
?>
	var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed=<?php print $archivespeed; ?>";
<?php
	} else {
?>
	if (click == 1) {
		var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className+"&currenttime="+now.getTime();
	} else {
		var defurl = "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className;
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
<?php
	if (!isset($archive) || !$archive) {
?>
			if (document.getElementById('aircraft_ident') && document.getElementById('aircraft_ident').className != "") {
				$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+document.getElementById('aircraft_ident').className);
			}
<?php
	}
?>
			layer_data = L.layerGroup();
			var nbaircraft = 0;
			var flightcount = 0;
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
	if (isset($_COOKIE['IconColor'])) $IconColor = $_COOKIE['IconColor'];
	elseif (isset($globalAircraftIconColor)) $IconColor = $globalAircraftIconColor;
	else $IconColor = '1a3151';
	if (!isset($ident) && !isset($flightaware_id)) {
?>
					//info_update(feature.properties.fc);
<?php
		if (isset($archive) && $archive) {
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
						$("#aircraft_ident").attr('class',flightaware_id);
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
							$("#aircraft_ident").attr('class',flightaware_id);
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
								$("#aircraft_ident").attr('class',flightaware_id);
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
		});
		//  getLiveData(0);
	}
 //load the function on startup
	getLiveData(0);


<?php
	if (isset($archive) && $archive) {
?>
	//then load it again every 30 seconds
	//  var reload = setInterval(function(){if (noTimeout) getLiveData(0)},<?php if (isset($globalMapRefresh)) print ($globalMapRefresh*1000)/2; else print '15000'; ?>);
	reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},<?php print $archiveupdatetime*1000; ?>);
<?php
	} else {
?>
	//then load it again every 30 seconds
	reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
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




function update_santaLayer(nows) {
	if (nows) var url = "<?php print $globalURL; ?>/live-santa-geojson.php?now";
	else var url = "<?php print $globalURL; ?>/live-santa-geojson.php";
	var santageoJSONQuery = $.getJSON(url, function(data) {
		santageoJSON = L.geoJson(data, {
			onEachFeature: function(feature,layer) {
				var playbackOptions = {
					orientIcons: true,
					clickCallback: function() { 
						$("#aircraft_ident").attr('class','');
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
function clickFlightRemainingRoute(cb) {
    document.cookie =  'MapRemainingRoute='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickFlightEstimation(cb) {
    document.cookie =  'flightestimation='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
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

function archivePause() {
    clearInterval(reloadPage);
    console.log('Pause');
}
function archivePlay() {
    reloadPage = setInterval(function(){if (noTimeout) getLiveData(0)},10000);
    console.log('Play');
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
					$("#aircraft_ident").attr('class','');
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
