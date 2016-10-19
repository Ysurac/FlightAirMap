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
} else {
	unset($_COOKIE['MapTrack']);
	setcookie('MapTrack', '', time() - 3600);
}

$title = _("Home");
require_once('header.php');
?>
<noscript><div class="alert alert-danger" role="alert"><?php echo _("JavaScript <b>MUST</b> be enabled"); ?></div></noscript>
<div id="live-map"></div>
<div id="loadingOverlay"><h1>Loading...</h1></div>
<div id="toolbar"></div>
<div id="aircraft_ident"></div>
<div id="airspace"></div>
<div id="notam"></div>
<div id="showdetails" class="showdetails"></div>
<div id="infobox" class="infobox"><h4><?php echo _("Aircrafts detected"); ?></h4><br /><i class="fa fa-spinner fa-pulse fa-fw"></i></div>
<?php
    if ((!isset($_COOKIE['MapFormat']) && isset($globalMap3Ddefault) && $globalMap3Ddefault) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d')) {

?>
<script src="<?php echo $globalURL; ?>/js/map.3d.js.php"></script>
<?php
    }
?>
<div id="dialog" title="<?php echo _("Session has timed-out"); ?>">
  <p><?php echo _("In order to save data consumption web page times out after 30 minutes. Close this dialog to continue."); ?></p>
</div>

<div id="sidebar" class="sidebar collapsed">
    <!-- Nav tabs -->
    <ul class="sidebar-tabs" role="tablist">
	<li><a href="#" onclick="zoomInMap(); return false;" title="<?php echo _("Zoom in"); ?>"><i class="fa fa-plus"></i></a></li>
	<li><a href="#" onclick="zoomOutMap(); return false;" title="<?php echo _("Zoom out"); ?>"><i class="fa fa-minus"></i></a></li>
	<li><a href="#" onclick="getUserLocation(); return false;" title="<?php echo _("Plot your Location"); ?>"><i class="fa fa-map-marker"></i></a></li>
	<li><a href="#" onclick="getCompassDirection(); return false;" title="<?php echo _("Compass Mode"); ?>"><i class="fa fa-compass"></i></a></li>
<?php
    if (isset($globalArchive) && $globalArchive == TRUE && (isset($globalBeta) && $globalBeta == TRUE)) {
?>
	<li><a href="#archive" role="tab" title="<?php echo _("Archive"); ?>"><i class="fa fa-archive"></i></a></li>
<?php
    }
?>
	<li><a href="#home" role="tab" title="<?php echo _("Layers"); ?>"><i class="fa fa-map"></i></a></li>
	<li><a href="#settings" role="tab" title="<?php echo _("Settings"); ?>"><i class="fa fa-gears"></i></a></li>
<?php
    if (isset($globalMap3D) && $globalMap3D) {
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
	<li><a href="#" onclick="show3D(); return false;" role="tab" title="3D"><b>3D</b></a></li>
<?php
        } else {
    	    if (isset($globalMapSatellites) && $globalMapSatellites) {
?>
	<li><a href="#satellites" role="tab" title="<?php echo _("Satellites"); ?>"><i class="satellite"></i></a></li>
<?php
	    }
?>
	<li><a href="#" onclick="show2D(); return false;" role="tab" title="2D"><b>2D</b></a></li>
<?php
	}
    }
?>
    </ul>

    <!-- Tab panes -->
    <div class="sidebar-content active">
	<div class="sidebar-pane" id="home">
	    <h1 class="sidebar-header">Weather<span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
		<form>
<?php
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
			<ul>
				<li><div class="checkbox"><label><input type="checkbox" name="weatherprecipitation" value="1" onclick="showWeatherPrecipitation();" /><?php echo _("Weather Precipitation"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="weatherrain" value="1" onclick="showWeatherRain();"  /><?php echo _("Weather Rain"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="weatherclouds" value="1" onclick="showWeatherClouds();" /><?php echo _("Weather Clouds"); ?></label></div></li>
			</ul>
                </form>
                <br />
		<h1>Others Layers</h1>
		<form>
			<ul>
				<li><div class="checkbox"><label><input type="checkbox" name="waypoints" value="1" onclick="showWaypoints();" /><?php echo _("Waypoints"); ?></label></div></li>
				<li><div class="checkbox"><label><input type="checkbox" name="airspace" value="1" onclick="showAirspace();" /><?php echo _("Airspace"); ?></label></div></li>
			</ul>
		</form>
<?php
	}
	if (isset($globalNOTAM) && $globalNOTAM) {
?>
		<form>
			<ul>
				<li><div class="checkbox"><label><input type="checkbox" name="notamcb" value="1" onclick="showNotam();" /><?php echo _("NOTAM"); ?></label></div></li>
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
    if (isset($globalArchive) && $globalArchive == TRUE) {
?>
        <div class="sidebar-pane" id="archive">
	    <h1 class="sidebar-header"><?php echo _("Playback"); ?> <i>Bêta</i><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <p>This feature is not finished yet.</p>
	    <form method="post">
		<ul>
		    <li>
		        <div class="form-group">
			    <label>From (UTC):</label>
		            <div class='input-group date' id='datetimepicker1'>
            			<input type='text' name="start_date" class="form-control" value="<?php if (isset($_COOKIE['archive_begin'])) print date("d/m/Y h:i a",$_COOKIE['archive_begin']); ?>" required />
		                <span class="input-group-addon">
            			    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        <div class="form-group">
			    <label>To (UTC):</label>
		            <div class='input-group date' id='datetimepicker2'>
		                <input type='text' name="end_date" class="form-control" value="<?php if (isset($_COOKIE['archive_end'])) print date("d/m/Y h:i a",$_COOKIE['archive_end']); ?>" />
            			<span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
            			</span>
		            </div>
		        </div>
			<script type="text/javascript">
			    $(function () {
			        $('#datetimepicker1').datetimepicker();
			        $('#datetimepicker2').datetimepicker({
			            useCurrent: false //Important! See issue #1075
			        });
			        $("#datetimepicker1").on("dp.change", function (e) {
			            $('#datetimepicker2').data("DateTimePicker").minDate(e.date);
			        });
			        $("#datetimepicker2").on("dp.change", function (e) {
			            $('#datetimepicker1').data("DateTimePicker").maxDate(e.date);
			        });
			    });
			</script>

		    <li><?php echo _("Playback speed:"); ?>
			<div class="range">
			    <input type="range" min="0" max="50" step="1" name="archivespeed" onChange="archivespeedrange.value=value;" value="<?php  if (isset($_COOKIE['archive_speed'])) print $_COOKIE['archive_speed']; else print '1'; ?>">
			    <output id="archivespeedrange"><?php  if (isset($_COOKIE['archive_speed'])) print $_COOKIE['archive_speed']; else print '1'; ?></output>
			</div>
		    </li>


			<input type="hidden" name="during" value="60" />
		    </li>
		    <li><input type="submit" name="archive" value="Show archive" /></li>
		</ul>
	    </form>
	</div>
<?php
    }
?>
        <div class="sidebar-pane" id="settings">
	    <h1 class="sidebar-header"><?php echo _("Settings"); ?><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <form>
		<ul>
		    <li><?php echo _("Type of Map:"); ?>
			<select  class="selectpicker" onchange="mapType(this);">
			    <?php
				if (!isset($_COOKIE['MapType']) || $_COOKIE['MapType'] == '') $MapType = $globalMapProvider;
				else $MapType = $_COOKIE['MapType'];
			    ?>
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
			    <option value="Here-Aerial"<?php if ($MapType == 'Here') print ' selected'; ?>>Here-Aerial</option>
			    <option value="Here-Hybrid"<?php if ($MapType == 'Here') print ' selected'; ?>>Here-Hybrid</option>
			    <option value="Here-Road"<?php if ($MapType == 'Here') print ' selected'; ?>>Here-Road</option>
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
			    <option value="Yandex"<?php if ($MapType == 'Yandex') print ' selected'; ?>>Yandex</option>
			    <?php
				}
			    ?>
			    <?php
				    if (isset($globalMapboxToken) && $globalMapboxToken != '') {
					if (!isset($_COOKIE['MapTypeId'])) $MapBoxId = 'default';
					else $MapBoxId = $_COOKIE['MapTypeId'];
			    ?>
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
			</select>
		    </li>
<?php
    if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
		    <li><?php echo _("Type of Terrain:"); ?>
			<select  class="selectpicker" onchange="terrainType(this);">
			    <option value="stk"<?php if (!isset($_COOKIE['MapTerrain']) || $_COOKIE['MapTerrain'] == 'stk') print ' selected'; ?>>stk terrain</option>
			    <option value="ellipsoid"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'ellipsoid') print ' selected';?>>ellipsoid</option>
			    <option value="vrterrain"<?php if (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'vrterrain') print ' selected';?>>vr terrain</option>
			</select>
		    </li>
<?php
    }
?>
<?php
    if (!isset($_COOKIE['MapFormat']) || $_COOKIE['MapFormat'] != '3d') {
?>
		    
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpopup" value="1" onclick="clickFlightPopup(this)" <?php if (isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true') print 'checked'; ?> ><?php echo _("Display flight info as popup"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpath" value="1" onclick="clickFlightPath(this)" <?php if ((isset($_COOKIE['flightpath']) && $_COOKIE['flightpath'] == 'true') || !isset($_COOKIE['flightpath'])) print 'checked'; ?> ><?php echo _("Display flight path"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightroute" value="1" onclick="clickFlightRoute(this)" <?php if ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == 'true') || !isset($_COOKIE['MapRoute'])) print 'checked'; ?> ><?php echo _("Display flight route on click"); ?></label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightestimation" value="1" onclick="clickFlightEstimation(this)" <?php if ((isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'true') || (!isset($_COOKIE['flightestimation']) && !isset($globalMapEstimation)) || (!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation)) print 'checked'; ?> ><?php echo _("Planes animate between updates"); ?></label></div></li>
<?php
    }
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayairports" value="1" onclick="clickDisplayAirports(this)" <?php if (isset($_COOKIE['displayairports']) && $_COOKIE['displayairports'] == 'true') print 'checked'; ?> ><?php echo _("Display airports on map"); ?></label></div></li>
<?php
    if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayminimap" value="1" onclick="clickDisplayMinimap(this)" <?php if (!isset($_COOKIE['displayminimap']) || (isset($_COOKIE['displayminimap']) && $_COOKIE['displayminimap'] == 'true')) print 'checked'; ?> ><?php echo _("Show mini-map"); ?></label></div></li>
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
		        if (extension_loaded('gd') && function_exists('gd_info')) {
		    ?>
		    <li><input type="checkbox" name="aircraftcoloraltitude" value="1" onclick="iconColorAltitude(this)" <?php if (isset($_COOKIE['IconColorAltitude']) && $_COOKIE['IconColorAltitude'] == 'true') print 'checked'; ?> ><?php echo _("Aircraft icon color based on altitude"); ?></li>
		    <?php 
			if (!isset($_COOKIE['IconColorAltitude']) || $_COOKIE['IconColorAltitude'] == 'false') {
		    ?>
		    <li><?php echo _("Aircraft icon color:"); ?>
			<input type="color" name="aircraftcolor" id="html5colorpicker" onchange="iconColor(aircraftcolor.value);" value="#<?php if (isset($_COOKIE['IconColor'])) print $_COOKIE['IconColor']; elseif (isset($globalAircraftIconColor)) print $globalAircraftIconColor; else print '1a3151'; ?>">
		    </li>
		    <?php
			    }
		        }
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
			if (((isset($globalVATSIM) && $globalVATSIM) || isset($globalIVAO) && $globalIVAO || isset($globalphpVMS) && $globalphpVMS) && (!isset($globalMapVAchoose) || $globalMapVAchoose)) {
		    ?>
			<?php if (isset($globalVATSIM) && $globalVATSIM) { ?><li><input type="checkbox" name="vatsim" value="1" onclick="clickVATSIM(this)" <?php if ((isset($_COOKIE['ShowVATSIM']) && $_COOKIE['ShowVATSIM'] == 'true') || !isset($_COOKIE['ShowVATSIM'])) print 'checked'; ?> ><?php echo _("Display VATSIM data"); ?></li><?php } ?>
			<?php if (isset($globalIVAO) && $globalIVAO) { ?><li><input type="checkbox" name="ivao" value="1" onclick="clickIVAO(this)" <?php if ((isset($_COOKIE['ShowIVAO']) && $_COOKIE['ShowIVAO'] == 'true') || !isset($_COOKIE['ShowIVAO'])) print 'checked'; ?> ><?php echo _("Display IVAO data"); ?></li><?php } ?>
			<?php if (isset($globalphpVMS) && $globalphpVMS) { ?><li><input type="checkbox" name="phpvms" value="1" onclick="clickphpVMS(this)" <?php if ((isset($_COOKIE['ShowVMS']) && $_COOKIE['ShowVMS'] == 'true') || !isset($_COOKIE['ShowVMS'])) print 'checked'; ?> ><?php echo _("Display phpVMS data"); ?></li><?php } ?>
		    <?php
			}
		    ?>
		    <?php
			if (!(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalphpVMS) && $globalphpVMS) && isset($globalSBS1) && $globalSBS1 && isset($globalAPRS) && $globalAPRS && (!isset($globalMapchoose) || $globalMapchoose)) {
		    ?>
			<?php if (isset($globalSBS1) && $globalSBS1) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="sbs" value="1" onclick="clickSBS1(this)" <?php if ((isset($_COOKIE['ShowSBS1']) && $_COOKIE['ShowSBS1'] == 'true') || !isset($_COOKIE['ShowSBS1'])) print 'checked'; ?> ><?php echo _("Display ADS-B data"); ?></label></div></li>
			<?php } ?>
			<?php if (isset($globalAPRS) && $globalAPRS) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="aprs" value="1" onclick="clickAPRS(this)" <?php if ((isset($_COOKIE['ShowAPRS']) && $_COOKIE['ShowAPRS'] == 'true') || !isset($_COOKIE['ShowAPRS'])) print 'checked'; ?> ><?php echo _("Display APRS data"); ?></label></div></li>
			<?php } ?>
		    <?php
			}
		    ?>
		    <li><?php echo _("Display airlines:"); ?>
			<select class="selectpicker" multiple onchange="airlines(this);">
			    <?php
				$Spotter = new Spotter();
				foreach($Spotter->getAllAirlineNames() as $airline) {
					$airline_name = $airline['airline_name'];
					if (strlen($airline_name) > 30) $airline_name = substr($airline_name,0,30).'...';
					if (isset($_COOKIE['Airlines']) && in_array($airline['airline_icao'],explode(',',$_COOKIE['Airlines']))) {
						echo '<option value="'.$airline['airline_icao'].'" selected>'.$airline_name.'</option>';
					} else {
						echo '<option value="'.$airline['airline_icao'].'">'.$airline_name.'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		    <?php
			if (isset($globalAPRS) && $globalAPRS) {
		    ?>
		    <li><?php echo _("Display APRS sources name:"); ?>
			<select class="selectpicker" multiple onchange="sources(this);">
			    <?php
				$Spotter = new Spotter();
				foreach($Spotter->getAllSourceName('aprs') as $source) {
					if (isset($_COOKIE['Sources']) && in_array($source['source_name'],explode(',',$_COOKIE['Sources']))) {
						echo '<option value="'.$source['source_name'].'" selected>'.$source['source_name'].'</option>';
					} else {
						echo '<option value="'.$source['source_name'].'">'.$source['source_name'].'</option>';
					}
				}
			    ?>
			</select>
		    </li>
		    <?php
			}
		    ?>
		    <?php
			if (!(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalphpVMS) && $globalphpVMS)) {
		    ?>
		    <li><?php echo _("Display airlines of type:"); ?>
			<select class="selectpicker" onchange="airlinestype(this);">
			    <option value="all"<?php if (!isset($_COOKIE['airlinestype']) || $_COOKIE['airlinestype'] == 'all' || $_COOKIE['airlinestype'] == '') echo ' selected'; ?>><?php echo _("All"); ?></option>
			    <option value="passenger"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'passenger') echo ' selected'; ?>><?php echo _("Passenger"); ?></option>
			    <option value="cargo"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'cargo') echo ' selected'; ?>><?php echo _("Cargo"); ?></option>
			    <option value="military"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'military') echo ' selected'; ?>><?php echo _("Military"); ?></option>
			</select>
		    </li>
		    <?php
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
		</ul>
	    </form>
	    <p><?php echo _("Any change in settings reload page"); ?></p>
    	</div>
<?php
    if (isset($globalMapSatellites) && $globalMapSatellites && isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
?>
        <div class="sidebar-pane" id="satellites">
	    <h1 class="sidebar-header"><?php echo _("Satellites"); ?><span class="sidebar-close"><i class="fa fa-caret-left"></i></span></h1>
	    <form>
		<ul>
		    <li><div class="checkbox"><label><input type="checkbox" name="displayiss" value="1" onclick="clickDisplayISS(this)" <?php if (isset($_COOKIE['displayiss']) && $_COOKIE['displayiss'] == 'true') print 'checked'; ?> ><?php echo _("Show ISS, Tiangong-1 and Tiangong-2 on map"); ?></label></div></li>
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
</script>
<?php
require_once('footer.php');
?>