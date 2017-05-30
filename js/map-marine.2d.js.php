<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php'); 

setcookie("MapFormat",'2d');

// Compressed GeoJson is used if true
if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;
?>


//var map;
var geojsonMarineLayer;
layer_marine_data = L.layerGroup();

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
	$("#aircraft_ident").attr('class','');
	getMarineLiveData(1);
	return false;
})


$("#aircraft_ident").attr('class','');
var MapTrackMarine = getCookie('MapTrackMarine');
if (MapTrackMarine != '') {
	$("#aircraft_ident").attr('class',MapTrackMarine);
	$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+MapTrack);
	delCookie('MapTrackMarine');
}

function getLiveMarineData(click)
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
            url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className+"&archive&begindate="+begindate+"&enddate="+enddate+"&speed=<?php print $archivespeed; ?>",
<?php
	} else {
?>
	    url: "<?php print $globalURL; ?>/live/geojson?"+Math.random()+"&marine&coord="+bbox+"&history="+document.getElementById('aircraft_ident').className,
<?php 
	}
?>
	    success: function(data) {
		map.removeLayer(layer_marine_data);
<?php
	if (!isset($archive) || !$archive) {
?>
		if (document.getElementById('aircraft_ident').className != "") {
			$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+document.getElementById('aircraft_ident').className);
		}
<?php
	}
?>
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
		    if (callsign != ""){ markerMarineLabel += callsign; }
		    if (type != ""){ markerMarineLabel += ' - '+type; }
<?php
	if (isset($_COOKIE['MarineIconColor'])) $MarineIconColor = $_COOKIE['MarineIconColor'];
	elseif (isset($globalMarineIconColor)) $MarineIconColor = $globalMarineIconColor;
	else $MarineIconColor = '1a3151';
	if (!isset($ident) && !isset($fammarine_id)) {
?>
		    info_marine_update(feature.properties.fc);
<?php
		if (isset($archive) && $archive) {
?>
		    archive.update(feature.properties);
<?php
		}
?>
		    if (document.getElementById('aircraft_ident').className == callsign || document.getElementById('aircraft_ident').className == fammarine_id) {
		    //if (document.getElementById('aircraft_ident').className == fammarine_id) {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?marine&color=FF0000&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?marine&color=8D93B9&filename='+aircraft_shadow;
		    } else {
			    var iconURLpath = '<?php print $globalURL; ?>/getImages.php?marine&color=<?php print $MarineIconColor; ?>&filename='+aircraft_shadow;
			    var iconURLShadowpath = '<?php print $globalURL; ?>/getImages.php?marine&color=8D93B9&filename='+aircraft_shadow;
		    }
<?php
	} else {
?>
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
				    $("#aircraft_ident").attr('class',fammarine_id);
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
				    $("#aircraft_ident").attr('class',callsign);
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
				//$("#aircraft_ident").attr('class',callsign);
				//if (callsign == "NA") {
					$("#aircraft_ident").attr('class',fammarine_id);
					$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
					$("#aircraft_ident").attr('class',callsign);
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
				    $("#aircraft_ident").attr('class',fammarine_id);
				    $(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+fammarine_id);
				/*
				} else {
				    $("#aircraft_ident").attr('class',callsign);
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
		var type = feature.properties.t;
		var callsign = feature.properties.c;
<?php
	} else {
?>
		//var altitude = feature.properties.altitude;
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
		    if (document.getElementById('aircraft_ident').className == callsign) {
			if (map.getZoom() > 7) {
                	    var style = {
				"color": "#1a3151",
				"weight": 3,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_data.addLayer(layer);
			} else {
			    var style = {
				"color": "#1a3151",
				"weight": 2,
				"opacity": 1
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			}
            	    } else {
			if (map.getZoom() > 7) {
                	    var style = {
                    		"color": "#1a3151",
				"weight": 3,
				"opacity": 0.6
			    };
			    layer.setStyle(style);
			    layer_marine_data.addLayer(layer);
			} else {
                	    var style = {
                    		"color": "#1a3151",
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
                    	    "color": "#1a3151",
                    	    "weight": 3,
                    	    "opacity": 0.6
                	};
                	layer.setStyle(style);
                	layer_marine_data.addLayer(layer);
		    } else {
                	var style = {
			    "color": "#1a3151",
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
			layer_marine_data.addTo(map);
			//re-create the bootstrap tooltips on the marker 
			//showBootstrapTooltip();
		}
	});
	//  getLiveMarineData(0);
}

$( document ).ready(function() {
 //load the function on startup
getLiveMarineData(0);


<?php
	if (isset($archive) && $archive) {
?>
//then load it again every 30 seconds
//  var reload = setInterval(function(){if (noTimeout) getLiveMarineData(0)},<?php if (isset($globalMapRefresh)) print ($globalMapRefresh*1000)/2; else print '15000'; ?>);
reloadMarinePage = setInterval(function(){if (noTimeout) getLiveMarineData(0)},<?php print $archiveupdatetime*1000; ?>);
<?php
	} else {
?>
//then load it again every 30 seconds
reloadMarinePage = setInterval(
    function(){if (noTimeout) getLiveMarineData(0)},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
<?php
	}
?>
});