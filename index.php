<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

$title = "Home";
require_once('header.php');
?>

<div id="live-map"></div>
<div id="aircraft_ident"></div>

<div id="dialog" title="Session has timed-out">
  <p>In order to save data consumption web page times out after 30 minutes. Close this dialog to continue.</p>
</div>

<div id="sidebar" class="sidebar collapsed">
    <!-- Nav tabs -->
    <ul class="sidebar-tabs" role="tablist">
	<li><a href="#" onclick="zoomInMap(); return false;" title="Zoom in"><i class="fa fa-plus"></i></a></li>
	<li><a href="#" onclick="zoomOutMap(); return false;" title="Zoom out"><i class="fa fa-minus"></i></a></li>
	<li><a href="#" onclick="getUserLocation(); return false;" title="Plot your Location"><i class="fa fa-map-marker"></i></a></li>
	<li><a href="#" onclick="getCompassDirection(); return false;" title="Compass Mode"><i class="fa fa-compass"></i></a></li>

	<li><a href="#home" role="tab" title="Layers"><i class="fa fa-map"></i></a></li>
	<li><a href="#settings" role="tab" title="Settings"><i class="fa fa-gears"></i></a></li>
    </ul>

    <!-- Tab panes -->
    <div class="sidebar-content active">
	<div class="sidebar-pane" id="home">
	    <h1>Weather</h1>
		<ul>
		<li><a class="button weatherprecipitation" onclick="showWeatherPrecipitation(); return false;" title="Weather Precipitation">Weather Precipitation</a></li>
		<li><a class="button weatherrain" onclick="showWeatherRain(); return false;" title="Weather Rain">Weather Rain</a></li>
		<li><a class="button weatherclouds" onclick="showWeatherClouds(); return false;" title="Weather Clouds">Weather Clouds</a></li>
                </ul>
                <br />
		<h1>Others Layers</h1>
		<ul><li><a class="button waypoints" onclick="showWaypoints(); return false;" title="Waypoints">Waypoints</a></li></ul>
		<ul><li><a class="button airspace" onclick="showAirspace(); return false;" title="Airspace">Airspace</a></li></ul>
<?php
    if (isset($globalNOTAM) && $globalNOTAM) {
?>
		<ul><li><a class="button notam" onclick="showNotam(); return false;" title="NOTAM">NOTAM</a></li></ul>
<?php
    }
?>
        </div>
        <div class="sidebar-pane" id="settings">
	    <h1>Settings</h1>
	    <form>
		<ul>
		    <li>Type of Map :
			<select onchange="mapType(this);">
			    <?php
				if (!isset($_COOKIE['MapType'])) $MapType = $globalMapProvider;
				else $MapType = $_COOKIE['MapType'];
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
			    <option value="MapQuest-OSM"<?php if ($MapType == 'MapQuest-OSM') print ' selected'; ?>>MapQuest-OSM</option>
			    <option value="MapQuest-Aerial"<?php if ($MapType == 'MapQuest-Aerial') print ' selected'; ?>>MapQuest-Aerial</option>
			</select>
		    </li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpopup" value="1" onclick="clickFlightPopup(this)" <?php if ((isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true') || !isset($_COOKIE['flightpopup'])) print 'checked'; ?> >Display flight info as popup</label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightpath" value="1" onclick="clickFlightPath(this)" <?php if ((isset($_COOKIE['flightpath']) && $_COOKIE['flightpath'] == 'true') || !isset($_COOKIE['flightpath'])) print 'checked'; ?> >Display flight path</label></div></li>
		    <li><div class="checkbox"><label><input type="checkbox" name="flightroute" value="1" onclick="clickFlightRoute(this)" <?php if ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == 'true') || !isset($_COOKIE['MapRoute'])) print 'checked'; ?> >Display flight route on click</label></div></li>

		    <?php
		        if (extension_loaded('gd') && function_exists('gd_info')) {
		    ?>
		    <li><input type="checkbox" name="aircraftcoloraltitude" value="1" onclick="iconColorAltitude(this)" <?php if (isset($_COOKIE['IconColorAltitude']) && $_COOKIE['IconColorAltitude'] == 'true') print 'checked'; ?> >Aircraft icon color based on altitude</li>
		    <?php 
			if (!isset($_COOKIE['IconColorAltitude']) || $_COOKIE['IconColorAltitude'] == 'false') {
		    ?>
		    <li>Aircraft icon color :
			<input type="color" name="aircraftcolor" id="html5colorpicker" onchange="iconColor(aircraftcolor.value);" value="#<?php if (isset($_COOKIE['IconColor'])) print $_COOKIE['IconColor']; elseif (isset($globalAircraftIconColor)) print $globalAircraftIconColor; else print '1a3151'; ?>">
		    </li>
		    <?php
			    }
		        }
		    ?>
		    <li>Show airport icon at zoom level :
			<div class="range">
			    <input type="range" min="0" max="19" step="1" name="airportzoom" onchange="range.value=value;airportDisplayZoom(airportzoom.value);" value="<?php if (isset($_COOKIE['AirportZoom'])) print $_COOKIE['AirportZoom']; elseif (isset($globalAirportZoom)) print $globalAirportZoom; else print '7'; ?>">
			    <output id="range"><?php if (isset($_COOKIE['AirportZoom'])) print $_COOKIE['AirportZoom']; elseif (isset($globalAirportZoom)) print $globalAirportZoom; else print '7'; ?></output>
			</div>
		    </li>
		    <?php
			if (((isset($globalVATSIM) && $globalVATSIM) || isset($globalIVAO) && $globalIVAO || isset($globalphpVMS) && $globalphpVMS) && (!isset($globalMapVAchoose) || $globalMapVAchoose)) {
		    ?>
			<?php if (isset($globalVATSIM) && $globalVATSIM) { ?><li><input type="checkbox" name="vatsim" value="1" onclick="clickVATSIM(this)" <?php if ((isset($_COOKIE['ShowVATSIM']) && $_COOKIE['ShowVATSIM'] == 'true') || !isset($_COOKIE['ShowVATSIM'])) print 'checked'; ?> >Display VATSIM data</li><?php } ?>
			<?php if (isset($globalIVAO) && $globalIVAO) { ?><li><input type="checkbox" name="ivao" value="1" onclick="clickIVAO(this)" <?php if ((isset($_COOKIE['ShowIVAO']) && $_COOKIE['ShowIVAO'] == 'true') || !isset($_COOKIE['ShowIVAO'])) print 'checked'; ?> >Display IVAO data</li><?php } ?>
			<?php if (isset($globalphpVMS) && $globalphpVMS) { ?><li><input type="checkbox" name="phpvms" value="1" onclick="clickphpVMS(this)" <?php if ((isset($_COOKIE['ShowVMS']) && $_COOKIE['ShowVMS'] == 'true') || !isset($_COOKIE['ShowVMS'])) print 'checked'; ?> >Display phpVMS data</li><?php } ?>
		    <?php
			}
		    ?>
		    <?php
			if (!(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalphpVMS) && $globalphpVMS) && isset($globalSBS1) && $globalSBS1 && isset($globalAPRS) && $globalAPRS && (!isset($globalMapchoose) || $globalMapchoose)) {
		    ?>
			<?php if (isset($globalSBS1) && $globalSBS1) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="sbs" value="1" onclick="clickSBS1(this)" <?php if ((isset($_COOKIE['ShowSBS1']) && $_COOKIE['ShowSBS1'] == 'true') || !isset($_COOKIE['ShowSBS1'])) print 'checked'; ?> >Display ADS-B data</label></div></li>
			<?php } ?>
			<?php if (isset($globalAPRS) && $globalAPRS) { ?>
			    <li><div class="checkbox"><label><input type="checkbox" name="aprs" value="1" onclick="clickAPRS(this)" <?php if ((isset($_COOKIE['ShowAPRS']) && $_COOKIE['ShowAPRS'] == 'true') || !isset($_COOKIE['ShowAPRS'])) print 'checked'; ?> >Display APRS data</label></div></li>
			<?php } ?>
		    <?php
			}
		    ?>
		    <li>Display airlines :
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
		    <li>Display APRS sources name :
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
		    <li>Display airlines of type :
			<select class="selectpicker" onchange="airlinestype(this);">
			    <option value="all"<?php if (!isset($_COOKIE['airlinestype']) || $_COOKIE['airlinestype'] == 'all' || $_COOKIE['airlinestype'] == '') echo ' selected'; ?>>All</option>
			    <option value="passenger"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'passenger') echo ' selected'; ?>>Passenger</option>
			    <option value="cargo"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'cargo') echo ' selected'; ?>>Cargo</option>
			    <option value="military"<?php if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] == 'military') echo ' selected'; ?>>Military</option>
			</select>
		    </li>
		    <?php
			}
		    ?>
		    <li>Distance unit :
			<select class="selectpicker" onchange="unitdistance(this);">
			    <option value="km"<?php if ((!isset($_COOKIE['unitdistance']) && (!isset($globalUnitDistance) || (isset($globalUnitDistance) && $globalUnitDistance == 'km'))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) echo ' selected'; ?>>km</option>
			    <option value="nm"<?php if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) echo ' selected'; ?>>nm</option>
			    <option value="mi"<?php if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) echo ' selected'; ?>>mi</option>
		        </select>
		    </li>
		    <li>Altitude unit :
			<select class="selectpicker" onchange="unitaltitude(this);">
			    <option value="m"<?php if ((!isset($_COOKIE['unitaltitude']) && (!isset($globalUnitAltitude) || (isset($globalUnitAltitude) && $globalUnitAltitude == 'm'))) || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'm')) echo ' selected'; ?>>m</option>
			    <option value="feet"<?php if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) echo ' selected'; ?>>feet</option>
		        </select>
		    </li>
		    <li>Speed unit :
			<select class="selectpicker" onchange="unitspeed(this);">
			    <option value="kmh"<?php if ((!isset($_COOKIE['unitspeed']) && (!isset($globalUnitSpeed) || (isset($globalUnitSpeed) && $globalUnitSpeed == 'kmh'))) || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'kmh')) echo ' selected'; ?>>km/h</option>
			    <option value="mph"<?php if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) echo ' selected'; ?>>mph</option>
			    <option value="knots"<?php if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) echo ' selected'; ?>>knots</option>
		        </select>
		    </li>
		</ul>
	    </form>
	    <p>Any change in settings reload page</p>
    	</div>
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
</script>
<?php
require_once('footer.php');
?>