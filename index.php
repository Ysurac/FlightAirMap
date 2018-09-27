<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
require_once('require/class.Satellite.php');

$trackident = filter_input(INPUT_GET,'trackid',FILTER_SANITIZE_STRING);
if ($trackident != '') {
	require_once('require/class.SpotterLive.php');
	$SpotterLive = new SpotterLive();
	$resulttrackident = $SpotterLive->getAllLiveSpotterDataById($trackident, true);
	if (empty($resulttrackident)) {
		$Spotter = new Spotter();
		$spotterid = $Spotter->getSpotterIDBasedOnFlightAwareID($trackident);
		header('Location: '.$globalURL.'/flightid/'.$spotterid);
	} else {
		setcookie('MapTrack',$resulttrackident[0]['flightaware_id']);
	}
/*
} else {
	unset($_COOKIE['MapTrack']);
	setcookie('MapTrack', '', time() - 3600);
*/
}

$raceid = filter_input(INPUT_GET,'raceid',FILTER_SANITIZE_NUMBER_INT);
if ($raceid != '') {
	setcookie('filter_race',$raceid);
}

$title = _("Home");
require_once('header.php');
?>
<noscript><div class="alert alert-danger" role="alert"><?php echo _("JavaScript <b>MUST</b> be enabled"); ?></div></noscript>
<div id="live-map"></div>
<br/>
<div id="dialog" title="<?php echo _("Session has timed-out"); ?>">
  <p><?php echo _("In order to save data consumption web page times out after 30 minutes. Close this dialog to continue."); ?></p>
</div>
<!--<div id="loadingOverlay"><h1>Loading...</h1></div>-->
<div id="toolbar"></div>
<div id="pointident"></div>
<div id="pointtype"></div>
<div id="airspace"></div>
<div id="notam"></div>
<div id="waypoints"></div>
<div id="archivebox" class="archivebox"></div>
<div id="showdetails" class="showdetails"></div>
<div class="geocode"></div><div class="compass"></div>
<div class="weatherrain"></div><div class="weatherprecipitation"></div><div class="weatherclouds"></div><div class="weatherradar"></div>
<div id="infobox" class="infobox"><table><tr>
    <?php if ((isset($globalAircraft) && $globalAircraft) || !isset($globalAircraft)) { ?><td><div id="ibxaircraft"><h4><?php echo _("Aircraft Detected"); ?></h4><br /><i class="fa fa-spinner fa-pulse fa-fw"></i></div></td>
    <?php }; if (isset($globalMarine) && $globalMarine) { ?><td><div id="ibxmarine"><h4><?php echo _("Vessels Detected"); ?></h4><br /><i class="fa fa-spinner fa-pulse fa-fw"></i></div></td>
    <?php }; if (isset($globalTracker) && $globalTracker) { ?><td><div id="ibxtracker"><h4><?php echo _("Trackers Detected"); ?></h4><br /><i class="fa fa-spinner fa-pulse fa-fw"></i></div></td>
    <?php }; if (isset($globalSatellite) && $globalSatellite) { ?><td><div id="ibxsatellite"><h4><?php echo _("Satellites Displayed"); ?></h4><br /><i class="fa fa-spinner fa-pulse fa-fw"></i></div></td><?php } ?>
</tr></table></div>
<?php
    if ((!isset($_COOKIE['MapFormat']) && isset($globalMap3Ddefault) && $globalMap3Ddefault) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d')) {
?>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map.3d.js.php<?php if (isset($tsk)) print '?tsk='.$tsk; ?>"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/meuusjs.1.0.3.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map.3d.weather.js"></script>
<?php
	if (!isset($globalAircraft) || $globalAircraft) {
?>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map-aircraft.3d.js.php"></script>
<?php
	}
	if (!isset($globalSatellite) || $globalSatellite) {
?>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map-satellite.3d.js.php"></script>
<?php
	}
	if (isset($globalTracker) && $globalTracker) {
?>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map-tracker.3d.js.php"></script>
<?php
	}
	if (isset($globalMarine) && $globalMarine) {
?>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/map-marine.3d.js.php"></script>
<?php
	}
    }
?>

<div id="sidebar" class="sidebar collapsed">
    <!-- Nav tabs -->
    <ul class="sidebar-tabs" role="tablist">
	<li><a href="" onclick="zoomInMap(); return false;" title="<?php echo _("Zoom in"); ?>"><i class="fa fa-plus"></i></a></li>
	<li><a href="" onclick="zoomOutMap(); return false;" title="<?php echo _("Zoom out"); ?>"><i class="fa fa-minus"></i></a></li>
	<li><a href="" onclick="getUserLocation(); return false;" title="<?php echo _("Plot your Location"); ?>"><i class="fa fa-map-marker"></i></a></li>
	<li><a href="" onclick="getCompassDirection(); return false;" title="<?php echo _("Compass Mode"); ?>"><i class="fa fa-compass"></i></a></li>
<?php
    //if ((isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') || (isset($globalBeta) && $globalBeta === TRUE)) {
	if (isset($globalArchive) && $globalArchive == TRUE && (!isset($globalAircraft) || $globalAircraft === TRUE)) {
?>
	<li><a href="#archive" role="tab" title="<?php echo _("Archive"); ?>"><i class="fa fa-archive"></i></a></li>
<?php
	}
    //}
?>
	<li><a href="#home" role="tab" title="<?php echo _("Layers"); ?>"><i class="fa fa-map"></i></a></li>
	<li><a href="#filters" role="tab" title="<?php echo _("Filters"); ?>"><i class="fa fa-filter"></i></a></li>
	<li><a href="#settings" role="tab" title="<?php echo _("Settings"); ?>"><i class="fa fa-gears"></i></a></li>
<?php
	if (isset($globalSatellite) && $globalSatellite) {
?>
	<li><a href="#satellites" role="tab" title="<?php echo _("Satellites"); ?>"><i class="satellite"></i></a></li>
<?php
	}
?>

<?php
    if (isset($globalMap3D) && $globalMap3D) {
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
	<li><a href="" onclick="show3D(); return false;" role="tab" title="3D"><b>3D</b></a></li>
<?php
	} else {
?>
	<li><a href="" onclick="show2D(); return false;" role="tab" title="2D"><b>2D</b></a></li>
<?php
	}
    }
?>
    </ul>

    <!-- Tab panes -->
    <div class="sidebar-content active">
	<div class="sidebar-pane" id="home">
	    <h1 class="sidebar-header">Layers<span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
		<form>
<?php
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
			<h1>Weather</h1>
			<ul>
			
				<li><div class="checkbox"><label><input type="checkbox" name="wind" value="1" onclick="clickWind(this);" <?php if (isset($_COOKIE['weather_wind']) && $_COOKIE['weather_wind'] == 'true') print 'checked'; ?> /><?php echo _("Weather Winds"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="wave" value="1" onclick="clickWave(this);" <?php if (isset($_COOKIE['weather_wave']) && $_COOKIE['weather_wave'] == 'true') print 'checked'; ?> /><?php echo _("Ocean surface currents"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="fire" value="1" onclick="clickFire(this);" <?php if (isset($_COOKIE['weather_fire']) && $_COOKIE['weather_fire'] == 'true') print 'checked'; ?> /><?php echo _("NASA Fire Hotspots"); ?></label></div></li>
				<!-- <li><div class="checkbox"><label><input type="checkbox" name="backwave" value="1" onclick="clickBackWave(this);" <?php if (isset($_COOKIE['weather_backwave']) && $_COOKIE['weather_backwave'] == 'true') print 'checked'; ?> /><?php echo _("Weather Waves height background"); ?></label></div></li> -->
			
<?php
		if (isset($globalOpenWeatherMapKey) && $globalOpenWeatherMapKey != '') {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="weatherprecipitation" value="1" onclick="showWeatherPrecipitation();" /><?php echo _("Weather Precipitation"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="weatherclouds" value="1" onclick="showWeatherClouds();" /><?php echo _("Weather Clouds"); ?></label></div></li>
<?php
		}
?>
			</ul>
<?php
	}
?>
<?php
	if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
			<h1>Weather</h1>
			<ul>
<?php
		if (isset($globalMETAR) && isset($globalMETARcycle) && $globalMETAR && $globalMETARcycle) {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="displayweather" value="1" onclick="clickDisplayWeather(this)" <?php if ((isset($_COOKIE['show_Weather']) && $_COOKIE['show_Weather'] == 'true') || (!isset($_COOKIE['show_Weather']) && (isset($globalMapWeather) && $globalMapWeather === TRUE))) print 'checked'; ?> ><?php echo _("Display 3D weather"); ?></label></div></li>
			<!--	<li><div class="checkbox"><label><input type="checkbox" name="displayrain" value="1" onclick="clickDisplayRain(this)" ><?php echo _("Display rain on 3D map"); ?></label></div></li>-->
<?php
		}
?>
				<li><div class="checkbox"><label><input type="checkbox" name="fire" value="1" onclick="clickFire(this);" <?php if (isset($_COOKIE['weather_fire']) && $_COOKIE['weather_fire'] == 'true') print 'checked'; ?> /><?php echo _("NASA Fire Hotspots"); ?></label></div></li>
			</ul>
<?php
	}
?>
                </form>
                <br />
		<h1>Others Layers</h1>
<?php
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
		<form>
			<ul>
<?php
		if (!isset($globalAircraft) || $globalAircraft) {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="waypoints" value="1" onclick="showWaypoints(this);" <?php if (isset($_COOKIE['waypoints']) && $_COOKIE['waypoints'] == 'true') print 'checked'; ?> /><?php echo _("Display waypoints"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="airspace" value="1" onclick="showAirspace(this);" <?php if (isset($_COOKIE['airspace']) && $_COOKIE['airspace'] == 'true') print 'checked'; ?> /><?php echo _("Display airspace"); ?></label></div></li>
<?php
		}
		if (isset($globalMarine) && $globalMarine) {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="openseamap" value="1" onclick="clickOpenSeaMap(this);" <?php if (isset($_COOKIE['openseamap']) && $_COOKIE['openseamap'] == 'true') print 'checked'; ?> /><?php echo _("Display OpenSeaMap"); ?></label></div></li>
<?php
		}
?>

			</ul>
		</form>
<?php
	} else {
?>
		<form>
			<ul>
<?php
		if (!isset($globalAircraft) || $globalAircraft) {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="waypoints" value="1" onclick="showWaypoints(this);" <?php if (isset($_COOKIE['waypoints']) && $_COOKIE['waypoints'] == 'true') print 'checked'; ?> /><?php echo _("Display waypoints"); ?> Beta</label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="airspace" value="1" onclick="showAirspace(this);" <?php if (isset($_COOKIE['airspace']) && $_COOKIE['airspace'] == 'true') print 'checked'; ?> /><?php echo _("Display airspace"); ?> Beta</label></div></li>
<?php
		}
		if (isset($globalMarine) && $globalMarine) {
?>
				<li><div class="checkbox"><label><input type="checkbox" name="openseamap" value="1" onclick="clickOpenSeaMap(this);" <?php if (isset($_COOKIE['openseamap']) && $_COOKIE['openseamap'] == 'true') print 'checked'; ?> /><?php echo _("Display OpenSeaMap"); ?></label></div></li>
<?php
		}
?>

			</ul>
			<p>These layers are in Beta, this can and will crash.</p>
		</form>
<?php
	}
	if (isset($globalNOTAM) && $globalNOTAM && (!isset($globalAircraft) || $globalAircraft === TRUE)) {
?>
		<h1>NOTAM</h1>
		<form>
			<ul>
				<li><div class="checkbox"><label><input type="checkbox" name="notamcb" value="1" onclick="showNotam(this);" <?php if (isset($_COOKIE['notam']) && $_COOKIE['notam'] == 'true') print 'checked'; ?> /><?php echo _("Display NOTAM"); ?></label></div></li>
				<li><?php echo _("NOTAM scope:"); ?>
					<select class="selectpicker" onchange="notamscope(this);">
						<option<?php if (!isset($_COOKIE['notamscope']) || $_COOKIE['notamscope'] == 'All') print ' selected'; ?>>All</option>
						<option<?php if (isset($_COOKIE['notamscope']) && $_COOKIE['notamscope'] == 'Airport/Enroute warning') print ' selected'; ?>>Airport/Enroute warning</option>
						<option<?php if (isset($_COOKIE['notamscope']) && $_COOKIE['notamscope'] == 'Airport warning') print ' selected'; ?>>Airport warning</option>
						<option<?php if (isset($_COOKIE['notamscope']) && $_COOKIE['notamscope'] == 'Navigation warning') print ' selected'; ?>>Navigation warning</option>
						<option<?php if (isset($_COOKIE['notamscope']) && $_COOKIE['notamscope'] == 'Enroute warning') print ' selected'; ?>>Enroute warning</option>
					</select
				</li>
			</ul>
<?php
		if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
			<p class="help-block">When enabled, NOTAM will be displayed if you zoom enough on map</p>
<?php
		}
?>
		</form>
<?php
	}
?>
        </div>
<?php
	if (isset($globalArchive) && $globalArchive == TRUE && (!isset($globalAircraft) || $globalAircraft === TRUE)) {
		date_default_timezone_set('UTC');
?>
	<div class="sidebar-pane" id="archive">
	    <h1 class="sidebar-header"><?php echo _("Playback"); ?> <i>Bêta</i><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <p>This feature is not finished yet.</p>
	    <ul>
		<li>
		    <div class="form-group">
			<label><?php echo _("From:"); ?></label>
			<div class='input-group date' id='datetimepicker1'>
			    <input type='text' id="start_date" name="start_date" class="form-control" autocomplete="off" value="<?php if (isset($_COOKIE['archive_begin']) && $_COOKIE['archive_begin'] != '') print date("Y-m-d H:i",$_COOKIE['archive_begin']).' UTC'; ?>" required />
			    <span class="input-group-addon">
				<span class="glyphicon glyphicon-calendar"></span>
			    </span>
			</div>
		    </div>
		    <div class="form-group">
			<label><?php echo _("To:"); ?></label>
			<div class='input-group date' id='datetimepicker2'>
			    <input type='text' id="end_date" name="end_date" class="form-control" autocomplete="off" value="<?php if (isset($_COOKIE['archive_end']) && $_COOKIE['archive_end'] != '') print date("Y-m-d H:i",$_COOKIE['archive_end']).' UTC'; ?>" />
			    <span class="input-group-addon">
				<span class="glyphicon glyphicon-calendar"></span>
			    </span>
			</div>
		    </div>
		    <script type="text/javascript">
			var begindate = getCookie('archive_begin');
			var enddate = getCookie('archive_end');
			$(function () {
			    moment.tz.setDefault("UTC");
			    $('#datetimepicker1').datetimepicker({
			        format: 'YYYY-MM-DD HH:mm z',
			        timeZone: 'UTC'
			    });
			    $('#datetimepicker2').datetimepicker({
			        format: 'YYYY-MM-DD HH:mm z',
			        timeZone: 'UTC',
			        useCurrent: false
			    });
			    $("#datetimepicker1").on("dp.change", function (e) {
			        $('#datetimepicker2').data("DateTimePicker").minDate(e.date);
			        begindate = e.date.unix();
			    });
			    $("#datetimepicker2").on("dp.change", function (e) {
			        $('#datetimepicker1').data("DateTimePicker").maxDate(e.date);
			        enddate = e.date.unix();
			    });
			});
		    </script>
		<li><?php echo _("Playback speed:"); ?>
		    <div class="range">
			<input type="range" min="0" max="50" step="1" id="archivespeed" name="archivespeed" onChange="archivespeedrange.value=value;" value="<?php  if (isset($_POST['archivespeed'])) print $_POST['archivespeed']; elseif (isset($_COOKIE['archive_speed'])) print $_COOKIE['archive_speed']; else print '1'; ?>">
			<output id="archivespeedrange"><?php  if (isset($_COOKIE['archive_speed'])) print $_COOKIE['archive_speed']; else print '1'; ?></output>
		    </div>
		</li>
		<?php
		    if (isset($globalDemo) && $globalDemo) {
		?>
		<li><button type="button" class="btn btn-primary disabled"><?php echo _("Show archive"); ?></button> Disabled in Demo mode</li>
		<?php
		    } else {
		?>
		<li><button type="button" onclick="addarchive(begindate,enddate);" class="btn btn-primary"><?php echo _("Show archive"); ?></button></li>
		<?php
		    }
		?>
	    </ul>
	    <ul>
		<li><button type="button" onclick="noarchive();" class="btn btn-primary"><?php echo _("Back from archive view"); ?></button></li>
	    </ul>
	</div>
<?php
	}
?>
        <div class="sidebar-pane" id="settings">
	    <h1 class="sidebar-header"><?php echo _("Settings"); ?><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <form>
		<ul>
		    <li><?php echo _("Type of Map:"); ?>
			    <?php
				if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
					if (!isset($_COOKIE['MapType']) || $_COOKIE['MapType'] == '') $MapType = $globalMapProvider;
					else $MapType = $_COOKIE['MapType'];
			    ?>
			<select  class="selectpicker" onchange="mapType(this);">
			    <?php
				} else {
					if (!isset($_COOKIE['MapType3D']) || $_COOKIE['MapType3D'] == '') $MapType = $globalMapProvider;
					else $MapType = $_COOKIE['MapType3D'];
			    ?>
			<select  class="selectpicker" onchange="mapType3D(this);">
			    <?php
				}
			    ?>
			    <?php
				if (isset($globalMapOffline) && $globalMapOffline === TRUE) {
			    ?>
			    <option value="offline"<?php if ($MapType == 'offline') print ' selected'; ?>>Natural Earth (local)</option>
			    <?php
				} else {
				    if (file_exists(dirname(__FILE__).'/js/Cesium/Assets/Textures/NaturalEarthII/tilemapresource.xml')) {
			    ?>
			    <option value="offline"<?php if ($MapType == 'offline') print ' selected'; ?>>Natural Earth (local)</option>
			    <?php
				    }
			    ?>
			    <option value="ArcGIS-Streetmap"<?php if ($MapType == 'ArcGIS-Streetmap') print ' selected'; ?>>ArcGIS Streetmap</option>
			    <option value="ArcGIS-Satellite"<?php if ($MapType == 'ArcGIS-Satellite') print ' selected'; ?>>ArcGIS Satellite</option>
			    <option value="ArcGIS-Satellite"<?php if ($MapType == 'ArcGIS-Ocean') print ' selected'; ?>>ArcGIS Ocean</option>
			    <?php
				    if (isset($globalBingMapKey) && $globalBingMapKey != '') {
			    ?>
			    <option value="Bing-Aerial"<?php if ($MapType == 'Bing-Aerial') print ' selected'; ?>>Bing-Aerial</option>
			    <option value="Bing-Hybrid"<?php if ($MapType == 'Bing-Hybrid') print ' selected'; ?>>Bing-Hybrid</option>
			    <option value="Bing-Road"<?php if ($MapType == 'Bing-Road') print ' selected'; ?>>Bing-Road</option>
			    <?php
				    }
			    ?>
			    <?php
				    if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
			    ?>
			    <?php
					if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
			    ?>
			    <option value="Here-Aerial"<?php if ($MapType == 'Here-Aerial') print ' selected'; ?>>Here-Aerial</option>
			    <option value="Here-Hybrid"<?php if ($MapType == 'Here-Hybrid') print ' selected'; ?>>Here-Hybrid</option>
			    <option value="Here-Road"<?php if ($MapType == 'Here-Road') print ' selected'; ?>>Here-Road</option>
			    <?php
					}
			    ?>
			    <?php
					if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '') {
			    ?>
			    <option value="Google-Roadmap"<?php if ($MapType == 'Google-Roadmap') print ' selected'; ?>>Google Roadmap</option>
			    <option value="Google-Satellite"<?php if ($MapType == 'Google-Satellite') print ' selected'; ?>>Google Satellite</option>
			    <option value="Google-Hybrid"<?php if ($MapType == 'Google-Hybrid') print ' selected'; ?>>Google Hybrid</option>
			    <option value="Google-Terrain"<?php if ($MapType == 'Google-Terrain') print ' selected'; ?>>Google Terrain</option>
			    <?php
					}
			    ?>
			    <?php
					if (isset($globalMapQuestKey) && $globalMapQuestKey != '') {
			    ?>
			    <option value="MapQuest-OSM"<?php if ($MapType == 'MapQuest-OSM') print ' selected'; ?>>MapQuest-OSM</option>
			    <option value="MapQuest-Aerial"<?php if ($MapType == 'MapQuest-Aerial') print ' selected'; ?>>MapQuest-Aerial</option>
			    <option value="MapQuest-Hybrid"<?php if ($MapType == 'MapQuest-Hybrid') print ' selected'; ?>>MapQuest-Hybrid</option>
			    <?php
					}
			    ?>
			    <option value="offline"<?php if ($MapType == 'offline') print ' selected'; ?>>Natural Earth</option>
			    <?php
				    }
			    ?>
			    <option value="NatGeo-Street"<?php if ($MapType == 'NatGeo-Street') print ' selected'; ?>>National Geographic Street</option>
			    <?php
				    if (isset($globalMapboxToken) && $globalMapboxToken != '') {
					if (!isset($_COOKIE['MapTypeId'])) $MapBoxId = 'default';
					else $MapBoxId = $_COOKIE['MapTypeId'];
			    ?>
			    <option value="MapboxGL"<?php if ($MapType == 'MapboxGL') print ' selected'; ?>>Mapbox GL</option>
			    <option value="Mapbox-default"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'default') print ' selected'; ?>>Mapbox default</option>
			    <option value="Mapbox-mapbox.streets"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.streets') print ' selected'; ?>>Mapbox streets</option>
			    <option value="Mapbox-mapbox.light"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.light') print ' selected'; ?>>Mapbox light</option>
			    <option value="Mapbox-mapbox.dark"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.dark') print ' selected'; ?>>Mapbox dark</option>
			    <option value="Mapbox-mapbox.satellite"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.satellite') print ' selected'; ?>>Mapbox satellite</option>
			    <option value="Mapbox-mapbox.streets-satellite"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.streets-satellite') print ' selected'; ?>>Mapbox streets-satellite</option>
			    <option value="Mapbox-mapbox.streets-basic"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.streets-basic') print ' selected'; ?>>Mapbox streets-basic</option>
			    <option value="Mapbox-mapbox.comic"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.comic') print ' selected'; ?>>Mapbox comic</option>
			    <option value="Mapbox-mapbox.outdoors"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.outdoors') print ' selected'; ?>>Mapbox outdoors</option>
			    <option value="Mapbox-mapbox.pencil"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.pencil') print ' selected'; ?>>Mapbox pencil</option>
			    <option value="Mapbox-mapbox.pirates"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.pirates') print ' selected'; ?>>Mapbox pirates</option>
			    <option value="Mapbox-mapbox.emerald"<?php if ($MapType == 'Mapbox' && $MapBoxId == 'mapbox.emerald') print ' selected'; ?>>Mapbox emerald</option>
			    <?php
				    }
			    ?>
			    <option value="OpenStreetMap"<?php if ($MapType == 'OpenStreetMap') print ' selected'; ?>>OpenStreetMap</option>
			    <option value="Yandex"<?php if ($MapType == 'Yandex') print ' selected'; ?>>Yandex</option>
			    <?php
				}
			    ?>
			</select>
		    </li>
<?php
    if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d' && (!isset($globalMapOffline) || $globalMapOffline === FALSE)) {
?>
		    <li><?php echo _("Type of Terrain:"); ?>
			<select  class="selectpicker" onchange="terrainType(this);">
			    <option value="world"<?php if (!isset($_COOKIE['MapTerrain']) || $_COOKIE['MapTerrain'] == 'world') print ' selected'; ?>>world terrain</option>
			    <option value="stk"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'stk') print ' selected'; ?>>stk terrain</option>
			    <option value="ellipsoid"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'ellipsoid') print ' selected';?>>ellipsoid</option>
			    <option value="vrterrain"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'vrterrain') print ' selected';?>>vr terrain</option>
			    <option value="articdem"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'articdem') print ' selected';?>>ArticDEM</option>
			</select>
		    </li>
<?php
    }
?>

<?php
    if (isset($globalMap3D) && $globalMap3D) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="synchro2d3d" value="1" onclick="clickSyncMap2D3D(this)" <?php if (isset($_COOKIE['Map2D3DSync']) && $_COOKIE['Map2D3DSync'] == 'true') print 'checked'; ?> ><?php echo _("Use same type of map for 2D & 3D"); ?></label></div></li>
<?php
    }
?>
<?php
    if (!isset($_COOKIE['MapFormat']) || $_COOKIE['MapFormat'] != '3d') {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="display2dbuildings" value="1" onclick="clickDisplay2DBuildings(this)" <?php if (isset($_COOKIE['Map2DBuildings']) && $_COOKIE['Map2DBuildings'] == 'true') print 'checked'; ?> ><?php echo _("Display 2.5D buidings on map"); ?></label></div></li>

<?php
	if (!isset($globalAircraft) || $globalAircraft === TRUE) {
?>
		    <!--<li><div class="checkbox"><label><input type="checkbox" name="flightpopup" value="1" onclick="clickFlightPopup(this)" <?php if (isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true') print 'checked'; ?> ><?php echo _("Display flight info as popup"); ?></label></div></li>-->
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpath" value="1" onclick="clickFlightPath(this)" <?php if ((isset($_COOKIE['flightpath']) && $_COOKIE['flightpath'] == 'true')) print 'checked'; ?> ><?php echo _("Display flight path"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightroute" value="1" onclick="clickFlightRoute(this)" <?php if ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == 'true') || (!isset($_COOKIE['MapRoute']) && isset($globalMapRoute) && $globalMapRoute)) print 'checked'; ?> ><?php echo _("Display flight route on click"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightremainingroute" value="1" onclick="clickFlightRemainingRoute(this)" <?php if ((isset($_COOKIE['MapRemainingRoute']) && $_COOKIE['MapRemainingRoute'] == 'true') || (!isset($_COOKIE['MapRemainingRoute']) && isset($globalMapRemainingRoute) && $globalMapRemainingRoute)) print 'checked'; ?> ><?php echo _("Display flight remaining route on click"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightestimation" value="1" onclick="clickFlightEstimation(this)" <?php if ((isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'true') || (!isset($_COOKIE['flightestimation']) && !isset($globalMapEstimation)) || (!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation)) print 'checked'; ?> ><?php echo _("Planes animate between updates"); ?></label></div></li>
<?php
	} elseif (!isset($globalTracker) || $globalTracker === TRUE) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="mapmatching" value="1" onclick="clickMapMatching(this)" <?php if ((isset($_COOKIE['mapmatching']) && $_COOKIE['mapmatching'] == 'true') || (!isset($_COOKIE['mapmatching']) && isset($globalMapMatching) && $globalMapMatching)) print 'checked'; ?> ><?php echo _("Enable map matching"); ?></label></div></li>
<?php
	}
	if (isset($globalSatellite) && $globalSatellite === TRUE) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="satelliteestimation" value="1" onclick="clickSatelliteEstimation(this)" <?php if ((isset($_COOKIE['satelliteestimation']) && $_COOKIE['satelliteestimation'] == 'true') || (!isset($_COOKIE['satelliteestimation']) && !isset($globalMapEstimation)) || (!isset($_COOKIE['satelliteestimation']) && isset($globalMapEstimation) && $globalMapEstimation)) print 'checked'; ?> ><?php echo _("Satellites animate between updates"); ?></label></div></li>
<?php
	}
    }
    if (!isset($globalAircraft) || $globalAircraft === TRUE) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayairports" value="1" onclick="clickDisplayAirports(this)" <?php if (isset($_COOKIE['displayairports']) && $_COOKIE['displayairports'] == 'true' || !isset($_COOKIE['displayairports'])) print 'checked'; ?> ><?php echo _("Display airports on map"); ?></label></div></li>
<?php
    }
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displaygroundstation" value="1" onclick="clickDisplayGroundStation(this)" <?php if ((isset($_COOKIE['show_GroundStation']) && $_COOKIE['show_GroundStation'] == 'true') || (!isset($_COOKIE['show_GroundStation']) && (isset($globalMapGroundStation) && $globalMapGroundStation === TRUE))) print 'checked'; ?> ><?php echo _("Display ground station on map"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayweatherstation" value="1" onclick="clickDisplayWeatherStation(this)" <?php if ((isset($_COOKIE['show_WeatherStation']) && $_COOKIE['show_WeatherStation'] == 'true') || (!isset($_COOKIE['show_WeatherStation']) && (isset($globalMapWeatherStation) && $globalMapWeatherStation === TRUE))) print 'checked'; ?> ><?php echo _("Display weather station on map"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="displaylightning" value="1" onclick="clickDisplayLightning(this)" <?php if ((isset($_COOKIE['show_Lightning']) && $_COOKIE['show_Lightning'] == 'true') || (!isset($_COOKIE['show_Lightning']) && (isset($globalMapLightning) && $globalMapLightning === TRUE))) print 'checked'; ?> ><?php echo _("Display lightning on map"); ?></label></div></li>
<?php
	if (isset($globalFires) && $globalFires) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayfires" value="1" onclick="clickDisplayFires(this)" <?php if ((isset($_COOKIE['show_Fires']) && $_COOKIE['show_Fires'] == 'true') || (!isset($_COOKIE['show_Fires']) && (isset($globalMapFires) && $globalMapFires === TRUE))) print 'checked'; ?> ><?php echo _("Display fires on map"); ?></label></div></li>
<?php
	}
	if (isset($globalMap3D) && $globalMap3D) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="singlemodel" value="1" onclick="clickSingleModel(this)" <?php if ((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') || (!isset($_COOKIE['singlemodel']) && isset($globalMap3DSelected) && $globalMap3DSelected)) print 'checked'; ?> ><?php echo _("Only display selected flight on 3D mode"); ?></label></div></li>
<?php
	}
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="truelight" value="1" onclick="clickTrueLight(this)" <?php if ((!isset($_COOKIE['truelight']) && (!isset($globalMapTrueLight) || $globalMapTrueLight)) || (isset($_COOKIE['truelight']) && $_COOKIE['truelight'] == 'true')) print 'checked'; ?> ><?php echo _("Enable globe sun lighting"); ?></label></div></li>
<?php
    if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayminimap" value="1" onclick="clickDisplayMinimap(this)" <?php if (!isset($_COOKIE['displayminimap']) || (isset($_COOKIE['displayminimap']) && $_COOKIE['displayminimap'] == 'true')) print 'checked'; ?> ><?php echo _("Show mini-map"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="shadows" value="1" onclick="clickShadows(this)" <?php if ((!isset($_COOKIE['map3dnoshadows']) && (!isset($globalMap3DShadows) || $globalMap3DShadows)) || (isset($_COOKIE['map3dnoshadows']) && $_COOKIE['map3dnoshadows'] == 'false')) print 'checked'; ?> ><?php echo _("Use shadows"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="one3dmodel" value="1" onclick="useOne3Dmodel(this)" <?php if ((isset($_COOKIE['one3dmodel']) && $_COOKIE['one3dmodel'] == 'true') || (!isset($_COOKIE['one3dmodel']) && isset($globalMap3DOneModel) && $globalMap3DOneModel)) print 'checked'; ?> ><?php echo _("Use same 3D model for all aircraft (use fewer resources)"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="updaterealtime" value="1" onclick="clickUpdateRealtime(this)" <?php if ((isset($_COOKIE['updaterealtime']) && $_COOKIE['updaterealtime'] == 'true') || !isset($_COOKIE['updaterealtime'])) print 'checked'; ?> ><?php echo _("Display realtime data in infobox"); ?></label></div></li>
<?php
    }
    if (time() > mktime(0,0,0,12,1,date("Y")) && time() < mktime(0,0,0,12,31,date("Y"))) {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displaysanta" value="1" onclick="clickSanta(this)"><i class="fa fa-snowflake-o" aria-hidden="true"></i> <?php echo _("Show Santa Claus now"); ?> <i class="fa fa-snowflake-o" aria-hidden="true"></i></label></div></li>
<?php
    }
?>
		    <?php
			if (function_exists('array_column')) {
			    if (array_search(TRUE, array_column($globalSources, 'sourcestats')) !== FALSE) {
		    ?>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpolar" value="1" onclick="clickPolar(this)" <?php if ((isset($_COOKIE['polar']) && $_COOKIE['polar'] == 'true')) print 'checked'; ?> ><?php echo _("Display polar on map"); ?></label></div></li>
		    <?php
			    }
			} elseif (isset($globalSources)) {
			    $dispolar = false;
			    foreach ($globalSources as $testsource) {
			        if (isset($globalSources['sourcestats']) && $globalSources['sourcestats'] !== FALSE) $dispolar = true;
			    }
			    if ($dispolar) {
		    ?>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpolar" value="1" onclick="clickPolar(this)" <?php if ((isset($_COOKIE['polar']) && $_COOKIE['polar'] == 'true')) print 'checked'; ?> ><?php echo _("Display polar on map"); ?></label></div></li>
		    <?php
			    }
		        }
		    ?>
<?php
    if (!isset($_COOKIE['MapFormat']) || $_COOKIE['MapFormat'] != '3d') {
?>
		    <?php
			if (!isset($globalAircraft) || $globalAircraft === TRUE) {
		    ?>
		    <li><?php echo _("Max number of flights to display in 2D:"); ?> <input type="number" name="2dlimit" value="<?php if (isset($_COOKIE['map_2d_limit'])) print $_COOKIE['map_2d_limit']; elseif (isset($globalMap2DAircraftsLimit)) print $globalMap2DAircraftsLimit; else print 15000; ?>" onchange="map2dlimit(this.value);" /></li>
		    <?php
			}
		    ?>
		    <?php
			if (!isset($globalAircraft) || $globalAircraft === TRUE) {
		    	    if (extension_loaded('gd') && function_exists('gd_info')) {
		    ?>
		    <li><input type="checkbox" name="aircraftcoloraltitude" value="1" onclick="iconColorAltitude(this)" <?php if (isset($_COOKIE['IconColorAltitude']) && $_COOKIE['IconColorAltitude'] == 'true') print 'checked'; ?> ><?php echo _("Aircraft icon color based on altitude"); ?></li>
		    <?php 
				if (!isset($_COOKIE['IconColorAltitude']) || $_COOKIE['IconColorAltitude'] == 'false') {
		    ?>
			<li><?php echo _("Aircraft icon color:"); ?> <input type="color" name="aircraftcolor" id="html5colorpicker" onchange="iconColor(this.value);" value="#<?php if (isset($_COOKIE['IconColor'])) print $_COOKIE['IconColor']; elseif (isset($globalAircraftIconColor)) print $globalAircraftIconColor; else print '1a3151'; ?>"></li>
		    <?php
				}
			    }
		        }
		    ?>
		    <?php
			if (isset($globalMarine) && $globalMarine === TRUE) {
			    if (extension_loaded('gd') && function_exists('gd_info')) {
		    ?>
		    <li><?php echo _("Marine icon color:"); ?>
			<input type="color" name="marinecolor" id="html5colorpicker" onchange="MarineiconColor(marinecolor.value);" value="#<?php if (isset($_COOKIE['MarineIconColor'])) print $_COOKIE['MarineIconColor']; elseif (isset($globalMarineIconColor)) print $globalMarineIconColor; else print '1a3151'; ?>">
		    </li>
		    <?php
			    }
		        }
		    ?>
		    <?php
			if (isset($globalTracker) && $globalTracker === TRUE) {
			    if (extension_loaded('gd') && function_exists('gd_info')) {
		    ?>
		    <li><?php echo _("Tracker icon color:"); ?>
			<input type="color" name="trackercolor" id="html5colorpicker" onchange="TrackericonColor(trackercolor.value);" value="#<?php if (isset($_COOKIE['TrackerIconColor'])) print $_COOKIE['TrackerIconColor']; elseif (isset($globalTrackerIconColor)) print $globalTrackerIconColor; else print '1a3151'; ?>">
		    </li>
		    <?php
			    }
		        }
		    ?>
		    <?php
			if (!isset($globalAircraft) || $globalAircraft === TRUE) {
		    ?>
		    <li><?php echo _("Show airport icon at zoom level:"); ?>
			<div class="range">
			    <input type="range" min="0" max="19" step="1" name="airportzoom" onchange="range.value=value;airportDisplayZoom(airportzoom.value);" value="<?php if (isset($_COOKIE['AirportZoom'])) print $_COOKIE['AirportZoom']; elseif (isset($globalAirportZoom)) print $globalAirportZoom; else print '7'; ?>">
			    <output id="range"><?php if (isset($_COOKIE['AirportZoom'])) print $_COOKIE['AirportZoom']; elseif (isset($globalAirportZoom)) print $globalAirportZoom; else print '7'; ?></output>
			</div>
		    </li>
		    <?php
			}
		    ?>
<?php
    } elseif (isset($_COOKIE['MapFormat']) || $_COOKIE['MapFOrmat'] == '3d') {
?>
		    <li><?php echo _("Set scaling factor for rendering resolution:"); ?>
			<div class="range">
			    <input type="range" min="0.5" max="2" step="0.5" name="resolutionscale" onchange="scale.value=value;resolutionScale(resolutionscale.value);" value="<?php if (isset($_COOKIE['resolutionScale'])) print $_COOKIE['resolutionScale']; else print '1'; ?>">
			    <output id="scale"><?php if (isset($_COOKIE['resolutionScale'])) print $_COOKIE['resolutionScale']; else print '1'; ?></output>
			</div>
		    </li>
<?php
	if (!isset($globalAircraft) || $globalAircraft === TRUE) {
?>
		    <!-- <li><?php echo _("Max number of flights to display in 3D:"); ?> <input type="number" name="3dlimit" value="<?php if (isset($_COOKIE['map_3d_limit'])) print $_COOKIE['map_3d_limit']; elseif (isset($globalMap3DAircraftsLimit)) print $globalMap3DAircraftsLimit; else print 300; ?>" onchange="map3dlimit(this.value);" /></li> -->
		    <li><input type="checkbox" name="useliveries" value="1" onclick="useLiveries(this)" <?php if (isset($_COOKIE['UseLiveries']) && $_COOKIE['UseLiveries'] == 'true') print 'checked'; ?> > <?php echo _("Use airlines liveries"); ?></li>
		    <li><input type="checkbox" name="aircraftcolorforce" value="1" onclick="iconColorForce(this)" <?php if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true') print 'checked'; ?> > <?php echo _("Force Aircraft color"); ?>&nbsp;
		    <!--<li><?php echo _("Aircraft icon color:"); ?>-->
			<input type="color" name="aircraftcolor" id="html5colorpicker" onchange="iconColor(aircraftcolor.value);" value="#<?php if (isset($_COOKIE['IconColor'])) print $_COOKIE['IconColor']; elseif (isset($globalAircraftIconColor)) print $globalAircraftIconColor; else print 'ff0000'; ?>">
		    </li>
<?php
	}
?>
<?php
	if (isset($globalMarine) && $globalMarine === TRUE) {
?>
		    <li><input type="checkbox" name="marinecolorforce" value="1" onclick="MarineiconColorForce(this)" <?php if (isset($_COOKIE['MarineIconColorForce']) && $_COOKIE['MarineIconColorForce'] == 'true') print 'checked'; ?> ><?php echo _("Force Marine color"); ?>&nbsp;
		    <!--<li><?php echo _("Marine icon color:"); ?>-->
			<input type="color" name="marinecolor" id="html5colorpicker" onchange="MarineiconColor(marinecolor.value);" value="#<?php if (isset($_COOKIE['MarineIconColor'])) print $_COOKIE['MarineIconColor']; elseif (isset($globalMarineIconColor)) print $globalMarineIconColor; else print 'ff0000'; ?>">
		    </li>
<?php
	}
?>
<?php
	if (isset($globalTracker) && $globalTracker === TRUE) {
?>
		    <li><input type="checkbox" name="trackercolorforce" value="1" onclick="TrackericonColorForce(this)" <?php if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true') print 'checked'; ?> ><?php echo _("Force Tracker color"); ?>&nbsp;
		    <!--<li><?php echo _("Tracker icon color:"); ?>-->
			<input type="color" name="trackercolor" id="html5colorpicker" onchange="TrackericonColor(trackercolor.value);" value="#<?php if (isset($_COOKIE['TrackerIconColor'])) print $_COOKIE['TrackerIconColor']; elseif (isset($globalTrackerIconColor)) print $globalTrackerIconColor; else print 'ff0000'; ?>">
		    </li>
<?php
	}
    }
?>
		    <li><?php echo _("Distance unit:"); ?>
			<select class="selectpicker" onchange="unitdistance(this);">
			    <option value="km"<?php if ((!isset($_COOKIE['unitdistance']) && (!isset($globalUnitDistance) || (isset($globalUnitDistance) && $globalUnitDistance == 'km'))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) echo ' selected'; ?>>km</option>
			    <option value="nm"<?php if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) echo ' selected'; ?>>nm</option>
			    <option value="mi"<?php if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) echo ' selected'; ?>>mi</option>
		        </select>
		    </li>
		    <li><?php echo _("Altitude unit:"); ?>
			<select class="selectpicker" onchange="unitaltitude(this);">
			    <option value="m"<?php if ((!isset($_COOKIE['unitaltitude']) && (!isset($globalUnitAltitude) || (isset($globalUnitAltitude) && $globalUnitAltitude == 'm'))) || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'm')) echo ' selected'; ?>>m</option>
			    <option value="feet"<?php if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) echo ' selected'; ?>>feet</option>
		        </select>
		    </li>
		    <li><?php echo _("Speed unit:"); ?>
			<select class="selectpicker" onchange="unitspeed(this);">
			    <option value="kmh"<?php if ((!isset($_COOKIE['unitspeed']) && (!isset($globalUnitSpeed) || (isset($globalUnitSpeed) && $globalUnitSpeed == 'kmh'))) || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'kmh')) echo ' selected'; ?>>km/h</option>
			    <option value="mph"<?php if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) echo ' selected'; ?>>mph</option>
			    <option value="knots"<?php if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) echo ' selected'; ?>>knots</option>
		        </select>
		    </li>
		    <li><?php echo _("Coordinate unit:"); ?>
			<select class="selectpicker" onchange="unitcoordinate(this);">
			    <option value="dd"<?php if ((!isset($_COOKIE['unitcoordinate']) && (!isset($globalUnitCoordinate) || (isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dd'))) || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dd')) echo ' selected'; ?>>DD</option>
			    <option value="dms"<?php if ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dms') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dms')) echo ' selected'; ?>>DMS</option>
			    <option value="dm"<?php if ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dm') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dm')) echo ' selected'; ?>>DM</option>
		        </select>
		    </li>

		</ul>
	    </form>
	    <p><?php echo _("Any change in settings reload page"); ?></p>
	</div>
        <div class="sidebar-pane" id="filters">
	    <h1 class="sidebar-header"><?php echo _("Filters"); ?><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
		<form>
		    <ul>
		    <?php
			if (!isset($globalAircraft) || $globalAircraft) {
		    ?>
		    <?php
			if (((isset($globalVATSIM) && $globalVATSIM) || isset($globalIVAO) && $globalIVAO || isset($globalphpVMS) && $globalphpVMS) && (!isset($globalMapVAchoose) || $globalMapVAchoose)) {
		    ?>
			<?php if (isset($globalVATSIM) && $globalVATSIM) { ?><li><input type="checkbox" name="vatsim" value="1" onclick="clickVATSIM(this)" <?php if ((isset($_COOKIE['filter_ShowVATSIM']) && $_COOKIE['filter_ShowVATSIM'] == 'true') || !isset($_COOKIE['filter_ShowVATSIM'])) print 'checked'; ?> ><?php echo _("Display VATSIM data"); ?></li><?php } ?>
			<?php if (isset($globalIVAO) && $globalIVAO) { ?><li><input type="checkbox" name="ivao" value="1" onclick="clickIVAO(this)" <?php if ((isset($_COOKIE['filter_ShowIVAO']) && $_COOKIE['filter_ShowIVAO'] == 'true') || !isset($_COOKIE['filter_ShowIVAO'])) print 'checked'; ?> ><?php echo _("Display IVAO data"); ?></li><?php } ?>
			<?php if (isset($globalphpVMS) && $globalphpVMS) { ?><li><input type="checkbox" name="phpvms" value="1" onclick="clickphpVMS(this)" <?php if ((isset($_COOKIE['filter_ShowVMS']) && $_COOKIE['filter_ShowVMS'] == 'true') || !isset($_COOKIE['filter_ShowVMS'])) print 'checked'; ?> ><?php echo _("Display phpVMS data"); ?></li><?php } ?>
		    <?php
			}
		    ?>
		    <?php
			if (!(isset($globalVA) && $globalVA) && !(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalphpVMS) && $globalphpVMS) && isset($globalSBS1) && $globalSBS1 && isset($globalAPRS) && $globalAPRS && (!isset($globalMapchoose) || $globalMapchoose)) {
		    ?>
			<?php if (isset($globalSBS1) && $globalSBS1) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="sbs" value="1" onclick="clickSBS1(this)" <?php if ((isset($_COOKIE['filter_ShowSBS1']) && $_COOKIE['filter_ShowSBS1'] == 'true') || !isset($_COOKIE['filter_ShowSBS1'])) print 'checked'; ?> ><?php echo _("Display ADS-B data"); ?></label></div></li>
			<?php } ?>
			<?php if (isset($globalAPRS) && $globalAPRS) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="aprs" value="1" onclick="clickAPRS(this)" <?php if ((isset($_COOKIE['filter_ShowAPRS']) && $_COOKIE['filter_ShowAPRS'] == 'true') || !isset($_COOKIE['filter_ShowAPRS'])) print 'checked'; ?> ><?php echo _("Display APRS data"); ?></label></div></li>
			<?php } ?>
			<li><div class="checkbox"><label><input type="checkbox" name="blocked" value="1" onclick="clickBlocked(this)" <?php if (isset($_COOKIE['filter_blocked']) && $_COOKIE['filter_blocked'] == 'true') print 'checked'; ?> ><?php echo _("Only display FAA ASDI blocked aircrafts"); ?></label></div></li>
		    <?php
			}
		    ?>
		    <li><?php echo _("Display airlines:"); ?>
		    <br/>
			<select class="selectpicker" multiple onchange="airlines(this);" id="display_airlines">
			    <?php
				$Stats = new Stats();
				$allairlinenames = $Stats->getAllAirlineNames();
				if (empty($allairlinenames)) {
					$Spotter = new Spotter();
					$allairlinenames = $Spotter->getAllAirlineNames();
				}
				foreach($allairlinenames as $airline) {
					$airline_name = $airline['airline_name'];
					if (strlen($airline_name) > 30) $airline_name = substr($airline_name,0,30).'...';
					if (isset($_COOKIE['filter_Airlines']) && in_array($airline['airline_icao'],explode(',',$_COOKIE['filter_Airlines']))) {
						echo '<option value="'.$airline['airline_icao'].'" selected>'.$airline_name.'</option>';
					} else {
						echo '<option value="'.$airline['airline_icao'].'">'.$airline_name.'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		    <?php
			$Spotter = new Spotter();
			$allalliancenames = $Spotter->getAllAllianceNames();
			if (!empty($allalliancenames)) {
		    ?>
		    <li><?php echo _("Display alliance:"); ?>
		    <br/>
			<select class="selectpicker" onchange="alliance(this);" id="display_alliance">
			    <option value="all"<?php if (!isset($_COOKIE['filter_alliance']) || $_COOKIE['filter_alliance'] == 'all' || $_COOKIE['filter_alliance'] == '') echo ' selected'; ?>><?php echo _("All"); ?></option>
			    <?php
				foreach($allalliancenames as $alliance) {
					$alliance_name = $alliance['alliance'];
					if (isset($_COOKIE['filter_alliance']) && $_COOKIE['filter_alliance'] == $alliance_name) {
						echo '<option value="'.$alliance_name.'" selected>'.$alliance_name.'</option>';
					} else {
						echo '<option value="'.$alliance_name.'">'.$alliance_name.'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		    <?php
			}
		    ?>
		    <?php
			}
		    ?>
		    <?php
			if (isset($globalAPRS) && $globalAPRS) {
		    ?>
		    <li><?php echo _("Display APRS sources name:"); ?>
			<select class="selectpicker" multiple onchange="sources(this);">
			    <?php
				/*
				$Spotter = new Spotter();
				$datasource = $Spotter->getAllSourceName('aprs');
				foreach($datasource as $source) {
					if (isset($_COOKIE['filter_Sources']) && in_array($source['source_name'],explode(',',$_COOKIE['filter_Sources']))) {
						echo '<option value="'.$source['source_name'].'" selected>'.$source['source_name'].'</option>';
					} else {
						echo '<option value="'.$source['source_name'].'">'.$source['source_name'].'</option>';
					}
				}
				*/
				$Source = new Source();
				$datasource = $Source->getLocationInfoByType('gs');
				foreach($datasource as $src) {
					if (isset($_COOKIE['filter_Sources']) && in_array($src['name'],explode(',',$_COOKIE['filter_Sources']))) {
						echo '<option value="'.$src['name'].'" selected>'.$src['name'].'</option>';
					} else {
						echo '<option value="'.$src['name'].'">'.$src['name'].'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		    <?php
			}
		    ?>
		    <?php
			if (!isset($globalAircraft) || $globalAircraft) {
		    ?>
		    <?php
			    if (!(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalphpVMS) && $globalphpVMS)) {
		    ?>
		    <li><?php echo _("Display airlines of type:"); ?><br/>
			<select class="selectpicker" onchange="airlinestype(this);">
			    <option value="all"<?php if (!isset($_COOKIE['filter_airlinestype']) || $_COOKIE['filter_airlinestype'] == 'all' || $_COOKIE['filter_airlinestype'] == '') echo ' selected'; ?>><?php echo _("All"); ?></option>
			    <option value="passenger"<?php if (isset($_COOKIE['filter_airlinestype']) && $_COOKIE['filter_airlinestype'] == 'passenger') echo ' selected'; ?>><?php echo _("Passenger"); ?></option>
			    <option value="cargo"<?php if (isset($_COOKIE['filter_airlinestype']) && $_COOKIE['filter_airlinestype'] == 'cargo') echo ' selected'; ?>><?php echo _("Cargo"); ?></option>
			    <option value="military"<?php if (isset($_COOKIE['filter_airlinestype']) && $_COOKIE['filter_airlinestype'] == 'military') echo ' selected'; ?>><?php echo _("Military"); ?></option>
			</select>
		    </li>
		    <?php
			    }
		    ?>
		    <?php
			}
		    ?>
		    <?php
			if (isset($globalMarine) && $globalMarine) {
		    ?>
		    <li>
			<?php echo _("Display vessels with MMSI:"); ?>
			<input type="text" name="mmsifilter" onchange="mmsifilter();" id="mmsifilter" value="<?php if (isset($_COOKIE['filter_mmsi'])) print $_COOKIE['filter_mmsi']; ?>" />
		    </li>
			<?php
				if (isset($globalVM) && $globalVM) {
					require_once('require/class.MarineLive.php');
					$MarineLive = new MarineLive();
					$races = $MarineLive->getAllRaces();
					if (!empty($races)) {
			?>
		    <li><?php echo _("Display race:"); ?><br/>
			<select class="selectpicker" onchange="racefilter(this);">
			    <option value="all"><?php echo _("All"); ?></option>
			    <?php
						foreach ($races as $race) {
							if (isset($_COOKIE['filter_race']) && $_COOKIE['filter_race'] == $race['race_id']) {
								print '<option value="'.$race['race_id'].'" selected>'.$race['race_name'].'</option>';
							} else {
								print '<option value="'.$race['race_id'].'">'.$race['race_name'].'</option>';
							}
						}
			    ?>
			</select>
		    </li>

		    <?php
					}
				}
			}
		    ?>
		    <li>
			<?php echo _("Display with ident:"); ?>
			<input type="text" name="identfilter" onchange="identfilter();" id="identfilter" value="<?php if (isset($_COOKIE['filter_ident'])) print $_COOKIE['filter_ident']; ?>" />
		    </li>
		</ul>
	    </form>
	    <form method="post">
		<!-- <center><input type="submit" name="removefilters" value="<?php echo _("Remove all filters"); ?>" class="btn btn-primary" /></center> -->
		<center><button type="button" class="btn btn-primary" onclick="removefilters();"><?php echo _("Remove all filters"); ?></button></center>
	    </form>
    	</div>
<?php
    if (isset($globalSatellite) && $globalSatellite) {
?>
        <div class="sidebar-pane" id="satellites">
	    <h1 class="sidebar-header"><?php echo _("Satellites"); ?><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <form>
		<ul>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayiss" value="1" onclick="clickDisplayISS(this)" <?php if ((isset($_COOKIE['displayiss']) && $_COOKIE['displayiss'] == 'true') || !isset($_COOKIE['displayiss'])) print 'checked'; ?> ><?php echo _("Show ISS, Tiangong-1 and Tiangong-2 on map"); ?></label></div></li>
		    <li><?php echo _("Type:"); ?>
			<select class="selectpicker" multiple onchange="sattypes(this);">
			    <?php
				$Satellite = new Satellite();
				$types = $Satellite->get_tle_types();
				foreach ($types as $type) {
					$type_name = $type['tle_type'];
					if ($type_name == 'musson') $type_name = 'Russian LEO Navigation';
					else if ($type_name == 'nnss') $type_name = 'Navi Navigation Satellite System';
					else if ($type_name == 'sbas') $type_name = 'Satellite-Based Augmentation System';
					else if ($type_name == 'glo-ops') $type_name = 'Glonass Operational';
					else if ($type_name == 'gps-ops') $type_name = 'GPS Operational';
					else if ($type_name == 'argos') $type_name = 'ARGOS Data Collection System';
					else if ($type_name == 'tdrss') $type_name = 'Tracking and Data Relay Satellite System';
					else if ($type_name == 'sarsat') $type_name = 'Search & Rescue';
					else if ($type_name == 'dmc') $type_name = 'Disaster Monitoring';
					else if ($type_name == 'resource') $type_name = 'Earth Resources';
					else if ($type_name == 'stations') $type_name = 'Space Stations';
					else if ($type_name == 'geo') $type_name = 'Geostationary';
					else if ($type_name == 'amateur') $type_name = 'Amateur Radio';
					else if ($type_name == 'x-comm') $type_name = 'Experimental';
					else if ($type_name == 'other-comm') $type_name = 'Other Comm';
					else if ($type_name == 'science') $type_name = 'Space & Earth Science';
					else if ($type_name == 'military') $type_name = 'Miscellaneous Military';
					else if ($type_name == 'radar') $type_name = 'Radar Calibration';
					else if ($type_name == 'tle-new') $type_name = 'Last 30 days launches';
					
					if (isset($_COOKIE['sattypes']) && in_array($type['tle_type'],explode(',',$_COOKIE['sattypes']))) {
						print '<option value="'.$type['tle_type'].'" selected>'.$type_name.'</option>';
					} else {
						print '<option value="'.$type['tle_type'].'">'.$type_name.'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		</ul>
	    </form>
	</div>
<?php
    }
?>
    </div>
</div>
<!--
<a class="button weatherradar" href="#" onclick="showWeatherRadar(); return false;" title="Weather Radar"><i class="fa fa-bullseye"></i></a>
<a class="button weathersatellite" href="#" onclick="showWeatherSatellite(); return false;" title="Weather Satellite"><i class="fa fa-globe"></i></a>
-->
<script>
    if (getCookie('flightpath') == 'true') $(".flightpath").addClass("active");
    if (getCookie('flightpopup') == 'true') $(".flightpopup").addClass("active");
    if (getCookie('maproute') == 'true') $(".flightroute").addClass("active");
    var sidebar = $('#sidebar').sidebar();
//    $(document).ready(function(){
//    	populate($("#display_airlines"),'airlinenames',getCookie('airline'));
//    });
    var typingTimer;
    var doneTypingInterval = 4000;
    $("#identfilter").on('input', function() {
	clearTimeout(typingTimer);
	if (this.value) {
	    typingTimer = setTimeout(identfilter,doneTypingInterval);
	}
    });
</script>
</section>
<section>
<div id="datatable"></div>
<div id="datatablemarine"></div>
<div id="datatabletracker"></div>
<?php
require_once('footer.php');
?>