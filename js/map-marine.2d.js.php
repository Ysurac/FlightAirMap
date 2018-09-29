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

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;
if (isset($_COOKIE['MarineIconColor'])) $MarineIconColor = $_COOKIE['MarineIconColor'];
elseif (isset($globalMarineIconColor)) $MarineIconColor = $globalMarineIconColor;
else $MarineIconColor = '43d1d8';

if (isset($globalVM) && $globalVM) {
?>
var globalVM = true;
<?php
} else {
?>
var globalVM = false;
<?php
}
?>

//var map;
var geojsonMarineLayer;
var openseamap;
var layer_marine_data = L.layerGroup();

<?php
	if (isset($_GET['fammarine_id'])) {
		$fammarine_id = filter_input(INPUT_GET,'fammarine_id',FILTER_SANITIZE_STRING);
	}
	if (isset($_GET['ident'])) {
		$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	}
	if (!isset($ident) && !isset($fammarine_id)) {
?>
	function info_marine_update (props) {
		$("#ibxmarine").html('<h4><?php echo _("Vessels detected"); ?></h4>' +  '<b>' + props + '</b>');
	}
<?php
	}
?>

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#pointident").attr('class','');
	getMarineLiveData(1);
	return false;
})


$("#pointident").attr('class','');
var MapTrackMarine = getCookie('MapTrackMarine');
if (MapTrackMarine != '') {
	$("#pointident").attr('class',MapTrackMarine);
	$("#pointtype").attr('class','marine');
	$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+MapTrackMarine);
	delCookie('MapTrackMarine');
}

function getLiveMarineData(click)
{
	var bbox = map.getBounds().toBBoxString();
	/*
	if (archive === true) {
		var begindate = parseInt(getCookie("archive_begin"));
		var enddate = begindate+parseInt(getCookie("archive_update"));
		if (enddate > getCookie("archive_end")) {
			enddate = parseInt(getCookie("archive_end"));
			clearInterval(reloadPage);
		} else {
			if (click != 1) {
				createCookie('archive_begin',enddate,9999);
			}
		}
	}
	*/

	//layer_data_p = L.layerGroup();
	$.ajax({
	    dataType: "json",
	    //      url: "live/geojson?"+Math.random(),
<?php
	if (isset($ident)) {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&ident=<?php print $ident; ?>&history",
<?php
	} elseif (isset($fammarine_id)) {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&fammarine_id=<?php print $fammarine_id; ?>&history",
<?php
	} elseif (isset($archive) && $archive) {
?>
            url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&coord="+bbox+"&history="+document.getElementById('pointident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed=<?php print $archivespeed; ?>",
<?php
	} else {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&coord="+bbox+"&history="+document.getElementById('pointident').className,
<?php 
	}
?>
	    success: function(data) {
		map.removeLayer(layer_marine_data);
<?php
	if (!isset($archive) || !$archive) {
?>
		if (document.getElementById('pointident').className != "") {
			$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+document.getElementById('pointident').className);
		}
<?php
	}
?>
		var nbmarine = 0;
		var marinecount = 0;
		var datatablemarine = '';
		layer_marine_data = L.layerGroup();
		var live_marine_data = L.geoJson(data, {
		    pointToLayer: function (feature, latLng) {
		    var markerMarineLabel = "";
		    //if (feature.properties.callsign != ""){ markerMarineLabel += feature.properties.callsign+'<br />'; }
		    //if (feature.properties.departure_airport_code != "" || feature.properties.arrival_airport_code != ""){ markerMarineLabel += '<span class="nomobile">'+feature.properties.departure_airport_code+' - '+feature.properties.arrival_airport_code+'</span>'; }
<?php
	if ($compress) {
?>
		    var callsign = feature.properties.c;
		    var fammarine_id = feature.properties.fmi;
		    var aircraft_shadow = feature.properties.as;
		    //var altitude = feature.properties.a;
		    var heading = feature.properties.h;
		    var type = feature.properties.t;
		    var captain = feature.properties.cap;
		    var raceid = feature.properties.rid;
<?php
	} else {
?>
		    var callsign = feature.properties.callsign;
		    var fammarine_id = feature.properties.fammarine_id;
		    var aircraft_shadow = feature.properties.aircraft_shadow;
		    //var altitude = feature.properties.altitude;
		    var heading = feature.properties.heading;
		    var type = feature.properties.type;
<?php
	}
?>
		    marinecount = feature.properties.fc;
		    if (typeof feature.properties.empty != 'undefined') {
			return;
		    }
		    if (type != "history"){ nbmarine = nbmarine+1; }
		    if (callsign != ""){ markerMarineLabel += callsign; }
		    if (typeof captain != 'undefined') {
			if (captain != ""){ markerMarineLabel += ' - '+captain; }
		    } else {
			if (type != ""){ markerMarineLabel += ' - '+type; }
		    }
<?php
	if (!isset($ident) && !isset($fammarine_id)) {
?>
		    //info_marine_update(feature.properties.fc);
<?php
		if (isset($archive) && $archive) {
?>
		    archive.update(feature.properties);
<?php
		}
?>
		    if (document.getElementById('pointident').className == callsign || document.getElementById('pointident').className == fammarine_id) {
		    //if (document.getElementById('pointident').className == fammarine_id) {
			    if (typeof raceid != 'undefined' && raceid != '') update_raceLayer(raceid);
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?marine&color=FF0000&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?marine&color=8D93B9&filename='+aircraft_shadow;
		    } else {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?marine&color=<?php print $MarineIconColor; ?>&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?marine&color=8D93B9&filename='+aircraft_shadow;
		    }
<?php
	} else {
?>
		    if (typeof raceid != 'undefined' && raceid != '') update_raceLayer(raceid);
		    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?marine&color=<?php print $MarineIconColor; ?>&filename='+aircraft_shadow;
		    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?marine&color=8D93B9&filename='+aircraft_shadow;
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
			title: markerMarineLabel,
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
				    $("#pointident").attr('class',fammarine_id);
				    $("#pointtype").attr('class','marine');
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
				    $("#pointident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveMarineData(1);
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
				title: markerMarineLabel,
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
					$("#pointident").attr('class',fammarine_id);
					$("#pointtype").attr('class','marine');
					$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
					$("#pointident").attr('class',callsign);
					$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveMarineData(1);
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
				title: markerMarineLabel,
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
?><?php
		if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'false') || (!isset($_COOKIE['flightpopup']) && isset($globalMapPopup) && !$globalMapPopup)) {
?>
			    .on('click', function() {
				//if (callsign == "NA") {
				    $("#pointident").attr('class',fammarine_id);
				    $("#pointtype").attr('class','marine');
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
				    $("#pointident").attr('class',callsign);
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&ident="+callsign);
				}
				*/
				getLiveMarineData(1);
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
		//var altitude = feature.properties.a;
		var id = feature.properties.fmi;
		var type = feature.properties.t;
		var callsign = feature.properties.c;
		var lastupdate = feature.properties.lu;
		var coord = feature.geometry.coordinates;
<?php
	} else {
?>
		//var altitude = feature.properties.altitude;
		var type = feature.properties.type;
		var callsign = feature.properties.callsign;
<?php
	}
?>
		if (type != 'history') {
			var lastupdatedate = new moment.tz(lastupdate*1000,moment.tz.guess()).format("HH:mm:ss");
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
			if (globalVM) {
				var rank = feature.properties.rrk;
				var captain = feature.properties.cap;
				var race = feature.properties.rname;
				datatablemarine += '<tr class="table-row" data-id="'+id+'" data-latitude="'+coord[1]+'" data-longitude="'+coord[0]+'"><td>'+rank+'</td><td>'+race+'</td><td>'+captain+'</td><td>'+callsign+'</td><td>'+type+'</td><td>'+latitude+'</td><td>'+longitude+'</td><td>'+lastupdatedate+'</td></tr>';
			} else {
				datatablemarine += '<tr class="table-row" data-id="'+id+'" data-latitude="'+coord[1]+'" data-longitude="'+coord[0]+'"><td>'+callsign+'</td><td>'+type+'</td><td>'+latitude+'</td><td>'+longitude+'</td><td>'+lastupdatedate+'</td></tr>';
			}
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
			    output += '<div class="left"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.fammarine_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a>Image &copy; '+ feature.properties.image_copyright+'</div>';
			} else {
			    output += '<div class="left"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.fammarine_id+'" target="_blank"><img src="'+feature.properties.image+'" alt="'+feature.properties.registration+' '+feature.properties.aircraft_name+'" title="'+feature.properties.registration+' '+feature.properties.aircraft_name+' Image &copy; '+feature.properties.image_copyright+'" /></a></div>';
			}
		    }
		    output += '<div class="right">';
                    output += '<div class="callsign-details">';
                    output += '<div class="callsign"><a href="<?php print $globalURL; ?>/redirect/'+feature.properties.fammarine_id+'" target="_blank">'+feature.properties.callsign+'</a></div>';
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
                
            	    <?php if (!isset($ident) && !isset($fammarine_id)) { ?>
            	    layer.bindPopup(output);
		    <?php } ?>
            	    layer_marine_data.addLayer(layer);
                } else {
            	    layer_marine_data.addLayer(layer);
                }

                if (type == "route"){
            	    var style = {
		    	"color": "#c74343",
		    	"weight": 2,
		    	"opacity": 0.5
		    };
		    layer.setStyle(style);
		    layer_marine_data.addLayer(layer);
		}


                //aircraft history position as a line
                if (type == "history"){
		    <?php if (!isset($ident) && !isset($fammarine_id)) { ?>
		    if (document.getElementById('pointident').className == callsign) {
			if (map.getZoom() > 7) {
                	    var style = {
				"color": "#529dff",
				"weight": 3,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			} else {
			    var style = {
				"color": "#529dff",
				"weight": 2,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			}
            	    } else {
			if (map.getZoom() > 7) {
                	    var style = {
                    		"color": "#529dff",
				"weight": 3,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			} else {
                	    var style = {
                    		"color": "#529dff",
				"weight": 2,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			}
                    }
		    <?php
            		} else {
            	    ?>
		    if (map.getZoom() > 7) {
                	var style = {
                    	    "color": "#529dff",
                    	    "weight": 3,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_marine_data.addLayer(layer);
		    } else {
                	var style = {
			    "color": "#529dff",
                    	    "weight": 2,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_marine_data.addLayer(layer);
		    }
<?php
            		}
?>
				}
			    }
			});
			if (datatablemarine != '') {
				$('#datatablemarine').css('height','20em');
				if (globalVM) {
					$('#datatablemarine').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th><?php echo _("Rank"); ?></th><th><?php echo _("Race"); ?></th><th><?php echo _("Captain"); ?></th><th><?php echo _("Callsign"); ?></th><th><?php echo _("Type"); ?><th><?php echo _("Latitude"); ?></th><th><?php echo _("Longitude"); ?></th><th><?php echo _("Last update"); ?></th></tr></thead><tbody>'+datatablemarine+'</tbody></table></div>');
				} else {
					$('#datatablemarine').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th><?php echo _("Callsign"); ?></th><th><?php echo _("Type"); ?><th><?php echo _("Latitude"); ?></th><th><?php echo _("Longitude"); ?></th><th><?php echo _("Last update"); ?></th></tr></thead><tbody>'+datatablemarine+'</tbody></table></div>');
				}
				$(".table-row").click(function () {
					$("#pointident").attr('class',$(this).data('id'));
					$("#pointtype").attr('class','marine');
					$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+$(this).data('id'));
					getLiveMarineData(1);
					map.panTo([$(this).data('latitude'),$(this).data('longitude')]);
				});
			}
			layer_marine_data.addTo(map);
			//re-create the bootstrap tooltips on the marker 
			//showBootstrapTooltip();
			if (typeof marinecount != "undefined" && marinecount != 0) {
				if (marinecount != nbmarine) {
					info_marine_update(nbmarine+'/'+marinecount);
				} else {
					info_marine_update(nbmarine);
				}
			} else {
				info_marine_update(nbmarine);
			}
		}
	});
	//  getLiveMarineData(0);
}

$( document ).ready(function() {
	map.on('moveend', function() {
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	if (archive === false) {
		getLiveMarineData(1);
	}
<?php
	}
?>
});
if (archive === false) {
	//load the function on startup
	getLiveMarineData(0);
}
if (getCookie('filter_race') != '') {
	update_raceLayer(getCookie('filter_race'));
}
if (archive === true) {
	function update_archiveMarineLayer(click) {
		$("#infobox").html('<?php echo _("Loading archive"); ?> <i class="fa fa-spinner fa-pulse fa-rw"></i>');
		var bbox = map.getBounds().toBBoxString();
		var begindate = parseInt(getCookie("archive_begin"));
		var enddate = parseInt(getCookie("archive_end"));
		var finaldate = enddate*1000;
		var archivespeed = parseInt(getCookie("archive_speed"));
		var lasticon;
		var playbackOptions = {
		    orientIcons: true,
		    clickCallback: function(event) { 
			var flightaware_id = event.target.feature.properties.fi;
			var currentdate = (begindate + event.originalEvent.timeStamp)*1000;
			$("#pointident").attr('class',flightaware_id);
			$("#pointtype").attr('class','marine');
			$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id+"&currenttime="+currentdate);
			var aircraft_shadow = event.target.feature.properties.as;
			if (typeof lasticon != 'undefined') {
			    lasticon.target._icon.src = '<?php print $globalURL; ?>/getImages.php?color=<?php print $MarineIconColor; ?>&filename='+lasticon.target.feature.properties.as;
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
			var iconURLpath = '<?php print $globalURL; ?>/getImages.php?color=<?php print $MarineIconColor; ?>&filename='+aircraft_shadow;
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
		var url = "<?php print $globalURL; ?>/archive-geojson.php?"+Math.random()+"&marine&coord="+bbox+"&history="+document.getElementById('pointident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed="+archivespeed;
		var alldata = [];
		var archivegeoJSONQuery = $.getJSON(url, function(data) {
			$("#infobox").remove();
			document.getElementById('archivebox').style.display = "block";
			$("#archivebox").html('<h4><?php echo _("Archive"); ?></h4>' +  '<b><span id="thedate"></span></b>' + '<br/><a href="#" onClick="noarchive();"><i class="fa fa-eject" aria-hidden="true"></i></a> <a href="#" onClick="archivePause();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="archivePlay();"><i class="fa fa-play" aria-hidden="true"></i></a><br/><div class="range archive"><input type="range" min="1" id="archiveboxspeed" max="50" size="10" step="1" onInput="archiveboxspeedrange.value=value;" onChange="archiveboxspeedrange.value=value;archiveplayback.setSpeed(value);" value="'+getCookie('archive_speed')+'"/><output id="archiveboxspeedrange">'+getCookie('archive_speed')+'</output></div>');
			var archiveLayerGroup = L.layerGroup();
			var archivegeoJSON = L.geoJson(data, {
				onEachFeature: function(feature,layer) {
					alldata.push(feature);
				}
			});
			archiveplayback = new L.Playback(map,alldata,archive_update,playbackOptions);
			archiveplayback.setCursor(begindate*1000);
			archiveplayback.start();
		});
	};

	console.log('Load Marine Archive geoJson');
	update_archiveMarineLayer(0);
} else {
	//then load it again every 30 seconds
	var reloadMarinePage = setInterval(
	function(){if (noTimeout) getLiveMarineData(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
}

if (getCookie('openseamap') == 'true') loadOpenSeaMap(getCookie('openseamap'));
//actually loads openseamap
});
function MarineiconColor(color) {
	createCookie('MarineIconColor',color.substring(1),9999);
	window.location.reload();
}
function clickOpenSeaMap(cb) {
	loadOpenSeaMap(cb.checked);
}
function loadOpenSeaMap(val) {
	var openseamapc = getCookie('openseamap');
	if (openseamapc == 'true' && val != 'true') {
		map.removeLayer(openseamap);
		delCookie('openseamap');
	} else {
		createCookie('openseamap',val,999);
		openseamap = L.tileLayer('http://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', {
		    attribution: 'Map data: &copy; <a href="http://www.openseamap.org">OpenSeaMap</a> contributors',
		    maxZoom: 18,
		    transparent: true,
		    opacity: '0.7'
		}).addTo(map);
	}
}
var raceLayer;
function update_raceLayer(raceid) {
	if (typeof raceLayer != 'undefined') {
		map.removeLayer(raceLayer);
	}
	var lineStyle = {
	    "color": "#ff7800",
	    "weight": 1,
	    "opacity": 0.65
	};

	var raceLayerQuery = $.getJSON("<?php print $globalURL; ?>/races-geojson.php?race_id="+raceid,function(data) {
		raceLayer = L.geoJson(data, {
		onEachFeature: racePopup,
		pointToLayer: function (feature, latlng) {
			return L.marker(latlng, {
				icon: L.icon({
					iconUrl: feature.properties.icon,
					iconSize: [24, 26],
					iconAnchor: [2, 26]
					//popupAnchor: [0, -28]
				})
			});
		},
		style: lineStyle
		}).addTo(map);
	});
};
function racePopup (feature, layer) {
	var output = '';
	output += '<div class="top">';
	if (feature.properties.race != '') {
		output += '<div class="title">';
		output += '<div class="title-details"><a href="<?php print $globalURL; ?>/marine/race/'+feature.properties.raceid+'" target="_blank">'+feature.properties.race+'</a></div>';
		output += '</div>';
	}
	if (feature.properties.name != '') {
		output += '<div class="name"><span><?php echo _("Name:"); ?></span>';
		output += feature.properties.name;
		output += '</div>';
	}
	if (feature.properties.type != '') {
		output += '<div class="type"><span><?php echo _("Type:"); ?> </span>';
		if (feature.properties.type == 1) {
			output += '<?php echo _("port startline buoy"); ?>';
		} else if (feature.properties.type == 1) {
			output += '<?php echo _("starboard startline buoy"); ?>';
		} else if (feature.properties.type == 3) {
			output += '<?php echo _("buoy starboard round (clockwise)"); ?>';
		} else if (feature.properties.type == 4) {
			output += '<?php echo _("buoy port round (counter clockwise)"); ?>';
		} else if (feature.properties.type == 7) {
			output += '<?php echo _("port finishline buoy"); ?>';
		} else if (feature.properties.type == 8) {
			output += '<?php echo _("starboard finishline buoy"); ?>';
		}
		output += '</div>';
	}
	output += '</div>';
	layer.bindPopup(output);
};