<?php
@session_start();
header('Content-Encoding: none;');
if (isset($_SESSION['error'])) {
	echo 'Error : '.$_SESSION['error'].' - Resetting install... You need to fix the problem and run install again.';
	unset($_SESSION['error']);
	unset($_SESSION['install']);
}
#if (ob_get_level() == 0) ob_start();
#ob_implicit_flush(true);
#ob_end_flush();
require_once('class.create_db.php');
require_once('class.update_schema.php');
require_once('class.settings.php');
$title="Install";
require('header.php');
require('../require/settings.php');

if ($globalInstalled && !isset($_SESSION['install'])) {
	print '<div class="info column"><p>You need to change $globalInstalled in settings.php to FALSE if you want to access setup again.</p></div>';
	require('../footer.php');
	exit;
}

$writable = false;
if (!is_writable('../require/settings.php')) {
	print '<div class="info column"><p><strong>The file <i>require/settings</i> must be writable.</strong></p></div>';
	require('../footer.php');
	exit;
}
if (!is_writable('tmp')) {
	print '<div class="info column"><p><strong>The directory <i>install/tmp</i> must be writable.</strong></p></div>';
	require('../footer.php');
	exit;
}
if (!set_time_limit(0)) {
	print '<div class="info column"><p><strong>You may need to update the maximum execution time.</strong></p></div>';
}
/*
if (!function_exists('pcntl_fork')) {
	print '<div class="info column"><p><strong>pcntl_fork is not available. Schedules will not be fetched.</strong></p></div>';
}
*/
$error = array();
if (!extension_loaded('SimpleXML')) {
	$error[] = "SimpleXML is not loaded.";
}
if (!extension_loaded('dom')) {
	$error[] = "Dom is not loaded. Needed for aircraft schedule";
}
if (!extension_loaded('PDO')) {
	$error[] = "PDO is not loaded.";
}
if (!extension_loaded('pdo_sqlite')) {
	$error[] = "PDO SQLite is not loaded. Needed to populate database for SBS.";
}
if (!extension_loaded('zip')) {
	$error[] = "ZIP is not loaded. Needed to populate database for SBS.";
}
if (!extension_loaded('json')) {
	$error[] = "Json is not loaded. Needed for aircraft schedule and bitly.";
}
if (!extension_loaded('curl')) {
	$error[] = "Curl is not loaded.";
}
if(function_exists('apache_get_modules') ){
	if(!in_array('mod_rewrite',apache_get_modules())) {
		$error[] = "mod_rewrite is not available.";
	}
	if (!isset($_SERVER['HTACCESS'])) {
		$error[] = "htaccess is not interpreted. Check your Apache configuration";
	}
}

if (count($error) > 0) {
	print '<div class="info column"><ul>';
	foreach ($error as $err) {
		print '<li>'.$err.'</li>';
	}
	print '</ul>You <strong>must</strong> add these modules.</div>';
	require('../footer.php');
        exit;
}

if (!isset($_SESSION['install']) && !isset($_POST['dbtype']) && (count($error) == 0)) {
	?>
	<div class="info column">
	<form method="post" class="form-horizontal">
		<fieldset>
			<legend>Database configuration</legend>
			<p>
				<label for="dbtype">Database type</label>
				<select name="dbtype" id="dbtype">
					<option value="mysql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'mysql') { ?>selected="selected" <?php } ?>>MySQL</option>
					<option value="pgsql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'pgsql') { ?>selected="selected" <?php } ?>>PostgreSQL (alpha support)</option>
				</select>
			</p>
			<p>
				<label for="createdb">Create database</label>
				<input type="checkbox" name="createdb" id="createdb" value="createdb" onClick="create_database_js()" />
			</p>
			<div id="createdb_data">
				<p>
					<label for="dbroot">Database admin user</label>
					<input type="text" name="dbroot" id="dbroot" />
				</p>
				<p>
					<label for="dbrootpass">Database admin password</label>
					<input type="password" name="dbrootpass" id="dbrootpass" />
				</p>
			</div>
			<p>
				<label for="dbhost">Database hostname</label>
				<input type="text" name="dbhost" id="dbhost" value="<?php if (isset($globalDBhost)) print $globalDBhost; ?>" />
			</p>
			<p>
				<label for="dbname">Database name</label>
				<input type="text" name="dbname" id="dbname" value="<?php if (isset($globalDBname)) print $globalDBname; ?>" />
			</p>
			<p>
				<label for="dbuser">Database user</label>
				<input type="text" name="dbuser" id="dbuser" value="<?php if (isset($globalDBuser)) print $globalDBuser; ?>" />
			</p>
			<p>
				<label for="dbuserpass">Database user password</label>
				<input type="password" name="dbuserpass" id="dbuserpass" value="<?php if (isset($globalDBpass)) print $globalDBpass; ?>" />
			</p>
		</fieldset>
		<fieldset>
			<legend>Site configuration</legend>
			<p>
				<label for="sitename">Site name</label>
				<input type="text" name="sitename" id="sitename" value="<?php if (isset($globalName)) print $globalName; ?>" />
			</p>
			<p>
				<label for="siteurl">Site URL</label>
				<input type="text" name="siteurl" id="siteurl" value="<?php if (isset($globalURL)) print $globalURL; ?>" />
				<p class="help-block">Can be null. ex : <i>flightairmap</i> if complete URL is <i>http://toto/flightairmap</i></p>
			</p>
			<p>
				<label for="timezone">Timezone</label>
				<input type="text" name="timezone" id="timezone" value="<?php if (isset($globalTimezone)) print $globalTimezone; ?>" />
				<p class="help-block">ex : UTC, Europe/Paris,...</p>
			</p>
			<p>
				<label for="language">Language</label>
				<input type="text" name="language" id="language" value="<?php if (isset($globalLanguage)) print $globalLanguage; ?>" />
				<p class="help-block">Used only when link to wikipedia for now. Can be EN,DE,FR,...</p>
			</p>
		</fieldset>
		<fieldset>
			<legend>Map provider</legend>
			<p>
				<label for="mapprovider">map Provider</label>
				<select name="mapprovider" id="mapprovider" onClick="map_provider_js()";>
					<option value="OpenStreetMap" <?php if (isset($globalMapProvider) && $globalMapProvider == 'OpenStreetMap') { ?>selected="selected" <?php } ?>>OpenStreetMap</option>
					<option value="Mapbox" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Mapbox') { ?>selected="selected" <?php } ?>>Mapbox</option>
					<option value="MapQuest-OSM" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-OSM') { ?>selected="selected" <?php } ?>>MapQuest-OSM</option>
					<option value="MapQuest-Aerial" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-Aerial') { ?>selected="selected" <?php } ?>>MapQuest-Aerial</option>
				</select>
			</p>
			<div id="mapbox_data">
				<p>
					<label for="mapboxid">Mapbox id</label>
					<input type="text" name="mapboxid" id="mapboxid" value="<?php if (isset($globalMapboxId)) print $globalMapboxId; ?>" />
				</p>
				<p>
					<label for="mapboxtoken">Mapbox token</label>
					<input type="text" name="mapboxtoken" id="mapboxtoken" value="<?php if (isset($globalMapboxToken)) print $globalMapboxToken; ?>" />
				</p>
			</div>
		</fieldset>
		<fieldset>
			<legend>Coverage area</legend>
			<p>
				<label for="latitudemax">The maximum latitude (north)</label>
				<input type="text" name="latitudemax" id="latitudemax" value="<?php if (isset($globalLatitudeMax)) print $globalLatitudeMax; ?>" />
			</p>
			<p>
				<label for="latitudemin">The minimum latitude (south)</label>
				<input type="text" name="latitudemin" id="latitudemin" value="<?php if (isset($globalLatitudeMin)) print $globalLatitudeMin; ?>" />
			</p>
			<p>
				<label for="longitudemax">The maximum longitude (west)</label>
				<input type="text" name="longitudemax" id="longitudemax" value="<?php if (isset($globalLongitudeMax)) print $globalLongitudeMax; ?>" />
			</p>
			<p>
				<label for="longitudemin">The minimum longitude (east)</label>
				<input type="text" name="longitudemin" id="longitudemin" value="<?php if (isset($globalLongitudeMin)) print $globalLongitudeMin; ?>" />
			</p>
			<p>
				<label for="latitudecenter">The latitude center</label>
				<input type="text" name="latitudecenter" id="latitudecenter" value="<?php if (isset($globalCenterLatitude)) print $globalCenterLatitude; ?>" />
			</p>
			<p>
				<label for="longitudecenter">The longitude center</label>
				<input type="text" name="longitudecenter" id="longitudecenter" value="<?php if (isset($globalCenterLongitude)) print $globalCenterLongitude; ?>" />
			</p>
			<p>
				<label for="livezoom">Default Zoom on live map</label>
				<input type="number" name="livezoom" id="livezoom" value="<?php if (isset($globalLiveZoom)) print $globalLiveZoom; else print '9'; ?>" />
			</p>
			<p>
				<label for="squawk_country">Country for squawk usage</label>
				<input type="text" name="squawk_country" id="squawk_country" value="<?php if (isset($globalSquawkCountry)) print $globalSquawkCountry; ?>" />
				<p class="help-block">UK, FR or let it blank for now</p>
			</p>
		</fieldset>
		<fieldset>
			<legend>Zone of interest</legend>
			<p><i>Only put in DB flights that are inside a circle</i></p>
			<p>
				<label for="latitude">Center latitude</label>
				<input type="text" name="zoilatitude" id="latitude" value="<?php if (isset($globalDistanceIgnore['latitude'])) echo $globalDistanceIgnore['latitude']; ?>" />
			</p>
			<p>
				<label for="longitude">Center longitude</label>
				<input type="text" name="zoilongitude" id="longitude" value="<?php if (isset($globalDistanceIgnore['longitude'])) echo $globalDistanceIgnore['longitude']; ?>" />
			</p>
			<p>
				<label for="Distance">Distance (in km)</label>
				<input type="text" name="zoidistance" id="distance" value="<?php if (isset($globalDistanceIgnore['distance'])) echo $globalDistanceIgnore['distance']; ?>" />
			</p>
		</fieldset>
		<fieldset>
			<legend>Sources location</legend>
			<table class="sources">
				<tr>
					<th>Name</th>
					<th>Latitude</th>
					<th>Longitude</th>
					<th>Altitude</th>
					<th>City</th>
					<th>Country</th>
				</tr>
		<?php
		    require_once('../require/class.Connection.php');
		    $Connection = new Connection();
		    if ($Connection->db != NULL) {
			if ($Connection->tableExists('source_location')) {
			    require_once('../require/class.Source.php');
			    $Source = new Source();
			    $alllocations = $Source->getAllLocationInfo();
			    foreach ($alllocations as $location) {
		?>
				<tr>
	    				<input type="hidden" name="source_id[]" value="<?php print $location['id']; ?>" />
					<td><input type="text" name="source_name[]" value="<?php print $location['name']; ?>" /></td>
					<td><input type="text" name="source_latitude[]" value="<?php print $location['latitude']; ?>" /></td>
					<td><input type="text" name="source_longitude[]" value="<?php print $location['longitude']; ?>" /></td>
					<td><input type="text" name="source_altitude[]" value="<?php print $location['altitude']; ?>" /></td>
					<td><input type="text" name="source_city[]" value="<?php print $location['city']; ?>" /></td>
					<td><input type="text" name="source_country[]" value="<?php print $location['country']; ?>" /></td>
				</tr>
		
		<?php
			    }
			}
		    }
		?>

				<tr>
					<td><input type="text" name="source_name[]" value="" /></td>
					<td><input type="text" name="source_latitude[]" value="" /></td>
					<td><input type="text" name="source_longitude[]" value="" /></td>
					<td><input type="text" name="source_altitude[]" value="" /></td>
					<td><input type="text" name="source_city[]" value="" /></td>
					<td><input type="text" name="source_country[]" value="" /></td>
				</tr>
			</table>
			<center>
				<input type="button" value="Add a row" class="add-row-source" />
				<input type="button" value="Remove last row" class="del-row-source" />
			</center>
		</fieldset>
		<fieldset>
			<legend>Data source</legend>
			<p>
				<p><i>If you choose IVAO, airlines names and logos will come from ivao.aero (you have to run install/populate_ivao.php to populate table with IVAO data)</i></p>
<!--
				<input type="radio" name="datasource" id="flightaware" value="flightaware" onClick="datasource_js()" <?php if (isset($globalFlightAware) && $globalFlightAware) { ?>checked="checked" <?php } ?>/>
				<label for="flightaware">FlightAware (not tested, no more supported no data feed available for test)</label>
-->
				<input type="checkbox" name="globalivao" id="ivao" value="ivao" onClick="datasource_js()" <?php if (isset($globalIVAO) && $globalIVAO) { ?>checked="checked" <?php } ?>/>
				<label for="ivao">IVAO</label>
				<input type="checkbox" name="globalvatsim" id="vatsim" value="vatsim" onClick="datasource_js()" <?php if (isset($globalVATSIM) && $globalVATSIM) { ?>checked="checked" <?php } ?>/>
				<label for="vatsim">VATSIM</label>
				<input type="checkbox" name="globalsbs" id="sbs" value="sbs" onClick="datasource_js()" <?php if (isset($globalSBS1) && $globalSBS1) { ?>checked="checked" <?php } ?> />
				<label for="sbs">ADS-B, SBS-1 format (dump1090 or SBS-1 compatible format), APRS from glidernet,...</label>
				<input type="checkbox" name="acars" id="acars" value="acars" onClick="datasource_js()" <?php if (isset($globalACARS) && $globalACARS) { ?>checked="checked" <?php } ?> />
				<label for="acars">ACARS</label>
			</p>
<!--
			<div id="flightaware_data">
				<p>
					<label for="flightawareusername">FlightAware username</label>
					<input type="text" name="flightawareusername" id="flightawareusername" value="<?php if (isset($globalFlightAwareUsername)) print $globalFlightAwareUsername; ?>" />
				</p>
				<p>
					<label for="flightawarepassword">FlightAware password/API key</label>
					<input type="text" name="flightawarepassword" id="flightawarepassword" value="<?php if (isset($globalFlightAwarePassword)) print $globalFlightAwarePassword; ?>" />
				</p>
			</div>
-->
			<div id="sbs_data">
		<?php
		    $globalSURL = array();
		    $globalIP = array();
		    if (isset($globalSBS1Hosts)) {
			if (! is_array($globalSBS1Hosts)) {
			    if (filter_var($globalSBS1Hosts,FILTER_VALIDATE_URL)) {
                        	$globalSURL[] = $globalSBS1Hosts;
			    } else {
				$hostport = explode(':',$globalSBS1Hosts);
				if (count($hostport) == 2) {
				    $globalIP[] = array('host' => $hostport[0],'port' => $hostport[1]);
				}
			    }
			} else {
			    foreach ($globalSBS1Hosts as $sbshost) {
				if (filter_var($sbshost,FILTER_VALIDATE_URL)) {
			    	    $globalSURL[] = $sbshost;
				} else {
				    $hostport = explode(':',$sbshost);
				    if (count($hostport) == 2) {
					$globalIP[] = array('host' =>  $hostport[0],'port' => $hostport[1]);
				    }
				}
			    }
			}
		    }
		?>
				<fieldset>
					<legend>Source ADS-B</legend>
					<p>In SBS-1 format (dump1090 or SBS-1 compatible format) or APRS (support glidernet)</p>
					<table class="sbsip">
						<tr>
							<th>Host</th>
							<th>Port</th>
						</tr>
		    <?php
			foreach ($globalIP as $hp) {
		    ?>
						<tr>
							<td><input type="text" name="sbshost[]" value="<?php print $hp['host']; ?>" /></td>
							<td><input type="number" name="sbsport[]" value="<?php print $hp['port']; ?>" /></td>
						</tr>
		    <?php
			}
		    ?>
						<tr>
							<td><input type="text" name="sbshost[]" value="" /></td>
							<td><input type="number" name="sbsport[]" value="" /></td>
						</tr>
					</table>
					<center>
						<input type="button" value="Add a row" class="add-row-ip" />
						<input type="button" value="Remove last row" class="del-row-ip" />
					</center>
					<p>
						<label for="sbstimeout">SBS-1 timeout</label>
						<input type="number" name="sbstimeout" id="sbstimeout" value="<?php if (isset($globalSBS1TimeOut)) print $globalSBS1TimeOut; ?>" />
					</p>
				</fieldset>
			</div>
			<div id="sbs_url">
				<br />
				<fieldset>
					<legend>Source URL</legend>
					<p>URL can be deltadb.txt or aircraftlist.json url to Radarcape, or <i>/action.php/acars/data</i> of phpvms, or wazzup file format</p>
					<table class="sbsurl">
						<tr>
							<th>URL</th>
						</tr>
		    <?php
			foreach ($globalSURL as $url) {
		    ?>
						<tr>
							<td><input type="text" name="sbsurl[]" value="<?php print $url; ?>" placeholder="URL can be deltadb.txt or aircraftlist.json url to Radarcape, or <i>/action.php/acars/data</i> of phpvms, or wazzup file format" /></td>
						</tr>
		    <?php
			}
		    ?>
						<tr>
							<td><input type="text" name="sbsurl[]" value="" /></td>
						</tr>
					</table>
					<center>
						<input type="button" value="Add a row" class="add-row-url" />
						<input type="button" value="Remove last row" class="del-row-url" />
					</center>
					<br />
				</div>
			</fieldset>
			<div id="acars_data">
				<fieldset>
					<legend>Source ACARS</legend>
					<p>Listen UDP server for acarsdec/acarsdeco2/...</p>
					<p>
						<label for="acarshost">ACARS UDP host</label>
						<input type="text" name="acarshost" id="acarshost" value="<?php if (isset($globalACARSHost)) print $globalACARSHost; ?>" />
					</p>
					<p>
						<label for="acarsport">ACARS UDP port</label>
						<input type="number" name="acarsport" id="acarsport" value="<?php if (isset($globalACARSPort)) print $globalACARSPort; ?>" />
					</p>
				</fieldset>
			</div>
		</fieldset>
		
		<fieldset>
			<legend>Optional configuration</legend>
			<div id="optional_sbs">
			<p>
				<label for="schedules">Retrieve schedules from external websites</label>
				<input type="checkbox" name="schedules" id="schedules" value="schedules"<?php if (isset($globalSchedulesFetch) && $globalSchedulesFetch || !isset($globalSchedulesFetch)) { ?> checked="checked"<?php } ?> onClick="schedule_js()" />
				<p class="help-block">Not available for IVAO</p>
			</p>
			<div id="schedules_options">
				<p>
					<label for="britishairways">British Airways API Key</label>
					<input type="text" name="britishairways" id="britishairways" value="<?php if (isset($globalBritishAirwaysKey)) print $globalBritishAirwaysKey; ?>" />
					<p class="help-block">Register an account on <a href="https://developer.ba.com/">https://developer.ba.com/</a></p>
				</p>
				<p>
					<label for="transavia">Transavia Test API Consumer Key</label>
					<input type="text" name="transavia" id="transavia" value="<?php if (isset($globalTransaviaKey)) print $globalTransaviaKey; ?>" />
					<p class="help-block">Register an account on <a href="https://developer.transavia.com">https://developer.transavia.com</a></p>
				</p>
				<p>
					<fieldset>
						<b>Lufthansa API Key</b>
						<p>
							<label for="lufthansakey">Key</label>
							<input type="text" name="lufthansakey" id="lufthansakey" value="<?php if (isset($globalLufthansaKey['key'])) print $globalLufthansaKey['key']; ?>" />
						</p><p>
							<label for="lufthansasecret">Secret</label>
							<input type="text" name="lufthansasecret" id="lufthansasecret" value="<?php if (isset($globalLufthansaKey['secret'])) print $globalLufthansaKey['secret']; ?>" />
						</p>
						<p class="help-block">Register an account on <a href="https://developer.lufthansa.com/page">https://developer.lufthansa.com/page</a></p>
					</fieldset>
				</p>
			</div>
			<p>
				<label for="owner">Add private owners of aircrafts</label>
				<input type="checkbox" name="owner" id="owner" value="owner"<?php if (isset($globalOwner) && $globalOwner) { ?> checked="checked"<?php } ?> />
			</p>
			</div>
			<p>
				<label for="notam">Activate NOTAM support</label>
				<input type="checkbox" name="notam" id="notam" value="notam"<?php if (isset($globalNOTAM) && $globalNOTAM) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="notamsource">URL of your feed from notaminfo.com</label>
				<input type="text" name="notamsource" id="notamsource" value="<?php if (isset($globalNOTAMSource)) print $globalNOTAMSource; ?>" />
			</p>
			<p>
				<label for="metar">Activate METAR support</label>
				<input type="checkbox" name="metar" id="metar" value="metar"<?php if (isset($globalMETAR) && $globalMETAR) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="metarcycle">Activate METAR cycle support</label>
				<input type="checkbox" name="metarcycle" id="metarcycle" value="metarcycle"<?php if (isset($globalMETARcycle) && $globalMETARcycle) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Download feed from NOAA every hour. Need <i>scripts/update_db.php</i> in cron</p>
			</p>
			<p>
				<label for="metarsource">URL of your METAR source</label>
				<input type="text" name="metarsource" id="metarsource" value="<?php if (isset($globalMETARurl)) print $globalMETARurl; ?>" />
				<p class="help-block">Use {icao} to specify where we replace by airport icao. ex : http://metar.vatsim.net/metar.php?id={icao}</p>
			</p>
			<p>
				<label for="bitly">Bit.ly access token api (used in search page)</label>
				<input type="text" name="bitly" id="bitly" value="<?php if (isset($globalBitlyAccessToken)) print $globalBitlyAccessToken; ?>" />
			</p>
			<p>
				<label for="waypoints">Add Waypoints, Airspace and countries data (about 45Mio in DB) <i>Not yet available for PostgreSQL</i></label>
				<input type="checkbox" name="waypoints" id="waypoints" value="waypoints" checked="checked" />
			</p>
			<p>
				<label for="archive">Archive all flights data</label>
				<input type="checkbox" name="archive" id="archive" value="archive"<?php if ((isset($globalArchive) && $globalArchive) || !isset($globalArchive)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="archivemonths">Generate statistics, delete or put in archive flights older than xx months</label>
				<input type="number" name="archivemonths" id="archivemonths" value="<?php if (isset($globalArchiveMonths)) print $globalArchiveMonths; else echo '0'; ?>" />
				<p class="help-block">0 to disable, delete old flight if <i>Archive all flights data</i> is disabled</p>
			</p>
			<p>
				<label for="archivekeepmonths">Keep flights data for xx months in archive</label>
				<input type="number" name="archivekeepmonths" id="archivekeepmonths" value="<?php if (isset($globalArchiveKeepMonths)) print $globalArchiveKeepMonths; else echo '0'; ?>" />
				<p class="help-block">0 to disable</p>
			</p>
			<p>
				<label for="archivekeeptrackmonths">Keep flights track data for xx months in archive</label>
				<input type="number" name="archivekeeptrackmonths" id="archivekeeptrackmonths" value="<?php if (isset($globalArchiveKeepTrackMonths)) print $globalArchiveKeepTrackMonths; else echo '0'; ?>" />
				<p class="help-block">0 to disable, should be less or egal to <i>Keep flights data</i> value</p>
			</p>
			<p>
				<label for="daemon">Use daemon-spotter.php as daemon</label>
				<input type="checkbox" name="daemon" id="daemon" value="daemon"<?php if ((isset($globalDaemon) && $globalDaemon) || !isset($globalDaemon)) { ?> checked="checked"<?php } ?> onClick="daemon_js()" />
				<div id="cronends"> 
					<label for="cronend">Run script for xx seconds</label>
					<input type="number" name="cronend" id="cronend" value="<?php if (isset($globalCronEnd)) print $globalCronEnd; else print '0'; ?>" />
					<p class="help-block">Set to 0 to disable. Should be disabled if source is URL.</p>
				</div>
				<p class="help-block">Uncheck if the script is running as cron job</p>
			</p>
			<p>
				<label for="fork">Allow processes fork</label>
				<input type="checkbox" name="fork" id="fork" value="fork"<?php if ((isset($globalFork) && $globalFork) || !isset($globalFork)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Used for schedule</p>
			</p>
			<p>
				<label for="colormap">Show altitudes on map with several colors</label>
				<input type="checkbox" name="colormap" id="colormap" value="colormap"<?php if ((isset($globalMapAltitudeColor) && $globalMapAltitudeColor) || !isset($globalMapAltitudeColor)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="mappopup">Show flights info in popup</label>
				<input type="checkbox" name="mappopup" id="mappopup" value="mappopup"<?php if ((isset($globalMapPopup) && $globalMapPopup)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="airportpopup">Show airport info in popup</label>
				<input type="checkbox" name="airportpopup" id="airportpopup" value="airportpopup"<?php if ((isset($globalAirportPopup) && $globalAirportPopup)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="maphistory">Always show path of flights (else only when flight is selected)</label>
				<input type="checkbox" name="maphistory" id="maphistory" value="maphistory"<?php if ((isset($globalMapHistory) && $globalMapHistory) || !isset($globalMapHistory)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="flightroute">Show route of flights when selected</label>
				<input type="checkbox" name="flightroute" id="flightroute" value="flightroute"<?php if ((isset($globalMapRoute) && $globalMapRoute) || !isset($globalMapRoute)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="refresh">Show flights detected since xxx seconds</label>
				<input type="number" name="refresh" id="refresh" value="<?php if (isset($globalLiveInterval)) echo $globalLiveInterval; else echo '200'; ?>" />
			</p>
			<p>
				<label for="maprefresh">Live map refresh (in seconds)</label>
				<input type="number" name="maprefresh" id="maprefresh" value="<?php if (isset($globalMapRefresh)) echo $globalMapRefresh; else echo '30'; ?>" />
			</p>
			<p>
				<label for="mapidle">Map idle timeout (in minutes)</label>
				<input type="number" name="mapidle" id="mapidle" value="<?php if (isset($globalMapIdleTimeout)) echo $globalMapIdleTimeout; else echo '30'; ?>" />
				<p class="help-block">0 to disable</p>
			</p>
			<p>
				<label for="closestmindist">Distance to airport set as arrival (in km)</label>
				<input type="number" name="closestmindist" id="closestmindist" value="<?php if (isset($globalClosestMinDist)) echo $globalClosestMinDist; else echo '10'; ?>" />
			</p>
			<p>
				<label for="aircraftsize">Size of aircraft icon on map (default to 30px if zoom > 7 else 15px), empty to default</label>
				<input type="number" name="aircraftsize" id="aircraftsize" value="<?php if (isset($globalAircraftSize)) echo $globalAircraftSize;?>" />
			</p>
			<p>
			<?php 
			    if (extension_loaded('gd') && function_exists('gd_info')) {
			?>
				<label for="aircrafticoncolor">Color of aircraft icon on map</label>
				<input type="color" name="aircrafticoncolor" id="aircrafticoncolor" value="#<?php if (isset($globalAircraftIconColor)) echo $globalAircraftIconColor; else echo '1a3151'; ?>" />
			<?php
				if (!is_writable('../cache')) {
			?>
				<b>The directory cache is not writable, aircraft icon will not be cached</b>
			<?php
				}
			    } else {
			?>
				<b>PHP GD is not installed, you can t change color of aircraft icon on map</b>
			<?php
			    }
			?>
			</p>
			<p>
				<label for="airportzoom">Zoom level minimum to see airports icons</label>
				<div class="range">
					<input type="range" name="airportzoom" id="airportzoom" value="<?php if (isset($globalAirportZoom)) echo $globalAirportZoom; else echo '7'; ?>" />
					<output id="range"><?php if (isset($globalAirportZoom)) echo $globalAirportZoom; else echo '7'; ?></output>
				</div>
			</p>
		</fieldset>
		<input type="submit" name="submit" value="Create/Update database & write setup" />
	</form>
	<p>
	    If it fails to populate tables, you can run inside console <i>install/install_db.php</i> or <i>install/install_db.sh</i>.
	</p>
<?php
	require('../footer.php');
        exit;
}
	
$settings = array();
$error = '';

if (isset($_POST['dbtype'])) {
	$dbtype = filter_input(INPUT_POST,'dbtype',FILTER_SANITIZE_STRING);
	$dbroot = filter_input(INPUT_POST,'dbroot',FILTER_SANITIZE_STRING);
	$dbrootpass = filter_input(INPUT_POST,'dbrootpass',FILTER_SANITIZE_STRING);
	$dbname = filter_input(INPUT_POST,'dbname',FILTER_SANITIZE_STRING);
	$dbuser = filter_input(INPUT_POST,'dbuser',FILTER_SANITIZE_STRING);
	$dbuserpass = filter_input(INPUT_POST,'dbuserpass',FILTER_SANITIZE_STRING);
	$dbhost = filter_input(INPUT_POST,'dbhost',FILTER_SANITIZE_STRING);

	if ($dbtype == 'mysql' && !extension_loaded('pdo_mysql')) $error .= 'Mysql driver for PDO must be loaded';
	if ($dbtype == 'pgsql' && !extension_loaded('pdo_pgsql')) $error .= 'PosgreSQL driver for PDO must be loaded';
	
	if ($error == '' && isset($_POST['createdb']) && $dbname != '' && $dbuser != '' && $dbuserpass != '') {
		if ($dbroot != '' && $dbrootpass != '') {
			$result = create_db::create_database($dbroot,$dbrootpass,$dbuser,$dbuserpass,$dbname,$dbtype,$dbhost);
			if ($result != '') $error .= $result;
		}
		if ($error == '') {
			//$error .= create_db::import_all_db('../db/');
			$settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
		}
	} else $settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));

	$sitename = filter_input(INPUT_POST,'sitename',FILTER_SANITIZE_STRING);
	$siteurl = filter_input(INPUT_POST,'siteurl',FILTER_SANITIZE_STRING);
	$timezone = filter_input(INPUT_POST,'timezone',FILTER_SANITIZE_STRING);
	$language = filter_input(INPUT_POST,'language',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalName' => $sitename,'globalURL' => $siteurl, 'globalTimezone' => $timezone,'globalLanguage' => $language));

	$mapprovider = filter_input(INPUT_POST,'mapprovider',FILTER_SANITIZE_STRING);
	$mapboxid = filter_input(INPUT_POST,'mapboxid',FILTER_SANITIZE_STRING);
	$mapboxtoken = filter_input(INPUT_POST,'mapboxtoken',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMapProvider' => $mapprovider,'globalMapboxId' => $mapboxid,'globalMapboxToken' => $mapboxtoken));
	
	$latitudemax = filter_input(INPUT_POST,'latitudemax',FILTER_SANITIZE_STRING);
	$latitudemin = filter_input(INPUT_POST,'latitudemin',FILTER_SANITIZE_STRING);
	$longitudemax = filter_input(INPUT_POST,'longitudemax',FILTER_SANITIZE_STRING);
	$longitudemin = filter_input(INPUT_POST,'longitudemin',FILTER_SANITIZE_STRING);
	$livezoom = filter_input(INPUT_POST,'livezoom',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalLatitudeMax' => $latitudemax,'globalLatitudeMin' => $latitudemin,'globalLongitudeMax' => $longitudemax,'globalLongitudeMin' => $longitudemin,'globalLiveZoom' => $livezoom));

	$squawk_country = filter_input(INPUT_POST,'squawk_country',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalSquawkCountry' => $squawk_country));

	$latitudecenter = filter_input(INPUT_POST,'latitudecenter',FILTER_SANITIZE_STRING);
	$longitudecenter = filter_input(INPUT_POST,'longitudecenter',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalCenterLatitude' => $latitudecenter,'globalCenterLongitude' => $longitudecenter));

	$acars = filter_input(INPUT_POST,'acars',FILTER_SANITIZE_STRING);
	if ($acars == 'acars') {
		$settings = array_merge($settings,array('globalACARS' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalACARS' => 'FALSE'));
	}

	$flightawareusername = filter_input(INPUT_POST,'flightawareusername',FILTER_SANITIZE_STRING);
	$flightawarepassword = filter_input(INPUT_POST,'flightawarepassword',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalFlightAwareUsername' => $flightawareusername,'globalFlightAwarePassword' => $flightawarepassword));
	
	$source_name = $_POST['source_name'];
	$source_latitude = $_POST['source_latitude'];
	$source_longitude = $_POST['source_longitude'];
	$source_altitude = $_POST['source_altitude'];
	$source_city = $_POST['source_city'];
	$source_country = $_POST['source_country'];
	if (isset($source_id)) $source_id = $_POST['source_id'];
	else $source_id = array();
	
	$sources = array();
	foreach ($source_name as $keys => $name) {
	    if (isset($source_id[$keys])) $sources[] = array('name' => $name,'latitude' => $source_latitude[$keys],'longitude' => $source_longitude[$keys],'altitude' => $source_altitude[$keys],'city' => $source_city[$keys],'country' => $source_country[$keys],'id' => $source_id[$keys]);
	    else $sources[] = array('name' => $name,'latitude' => $source_latitude[$keys],'longitude' => $source_longitude[$keys],'altitude' => $source_altitude[$keys],'city' => $source_city[$keys],'country' => $source_country[$keys]);
	}
	if (count($sources) > 0) $_SESSION['sources'] = $sources;

	//$sbshost = filter_input(INPUT_POST,'sbshost',FILTER_SANITIZE_STRING);
	//$sbsport = filter_input(INPUT_POST,'sbsport',FILTER_SANITIZE_NUMBER_INT);
	//$sbsurl = filter_input(INPUT_POST,'sbsurl',FILTER_SANITIZE_URL);
	$sbshost = $_POST['sbshost'];
	$sbsport = $_POST['sbsport'];
	$sbsurl = $_POST['sbsurl'];

	$globalvatsim = filter_input(INPUT_POST,'globalvatsim',FILTER_SANITIZE_STRING);
	$globalivao = filter_input(INPUT_POST,'globalivao',FILTER_SANITIZE_STRING);
	$globalsbs = filter_input(INPUT_POST,'globalsbs',FILTER_SANITIZE_STRING);
	$datasource = filter_input(INPUT_POST,'datasource',FILTER_SANITIZE_STRING);

	
	$globalSBS1Hosts = array();
//	if ($datasource != 'ivao' && $datasource != 'vatsim') {
	if ($globalsbs == 'sbs') {
	    foreach ($sbshost as $key => $host) {
		if ($host != '') $globalSBS1Hosts[] = $host.':'.$sbsport[$key];
	    }
	}
	if (count($sbsurl) > 0 && $sbsurl[0] != '') {
	    $sbsurl = array_filter($sbsurl);
	    $globalSBS1Hosts = array_merge($globalSBS1Hosts,$sbsurl);
	}
	$settings = array_merge($settings,array('globalSBS1Hosts' => $globalSBS1Hosts));

	$sbstimeout = filter_input(INPUT_POST,'sbstimeout',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalSBS1TimeOut' => $sbstimeout));

	$acarshost = filter_input(INPUT_POST,'acarshost',FILTER_SANITIZE_STRING);
	$acarsport = filter_input(INPUT_POST,'acarsport',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalACARSHost' => $acarshost,'globalACARSPort' => $acarsport));

	$bitly = filter_input(INPUT_POST,'bitly',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBitlyAccessToken' => $bitly));

	$notamsource = filter_input(INPUT_POST,'notamsource',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalNOTAMSource' => $notamsource));
	$metarsource = filter_input(INPUT_POST,'metarsource',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMETARurl' => $metarsource));

	$zoilatitude = filter_input(INPUT_POST,'zoilatitude',FILTER_SANITIZE_STRING);
	$zoilongitude = filter_input(INPUT_POST,'zoilongitude',FILTER_SANITIZE_STRING);
	$zoidistance = filter_input(INPUT_POST,'zoidistance',FILTER_SANITIZE_NUMBER_INT);
	if ($zoilatitude != '' && $zoilongitude != '' && $zoidistance != '') {
	$settings = array_merge($settings,array('globalDistanceIgnore' => array('latitude' => $zoilatitude,'longitude' => $zoilongitude,'distance' => $zoidistance)));
	} else $settings = array_merge($settings,array('globalDistanceIgnore' => array()));

	$refresh = filter_input(INPUT_POST,'refresh',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalLiveInterval' => $refresh));
	$maprefresh = filter_input(INPUT_POST,'maprefresh',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalMapRefresh' => $maprefresh));
	$mapidle = filter_input(INPUT_POST,'mapidle',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalMapIdleTimeout' => $mapidle));
	$closestmindist = filter_input(INPUT_POST,'closestmindist',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalClosestMinDist' => $closestmindist));

	$aircraftsize = filter_input(INPUT_POST,'aircraftsize',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalAircraftSize' => $aircraftsize));

	$archivemonths = filter_input(INPUT_POST,'archivemonths',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalArchiveMonths' => $archivemonths));
	$archivekeepmonths = filter_input(INPUT_POST,'archivekeepmonths',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalArchiveKeepMonths' => $archivekeepmonths));
	$archivekeeptrackmonths = filter_input(INPUT_POST,'archivekeeptrackmonths',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalArchiveKeepTrackMonths' => $archivekeeptrackmonths));

	$britishairways = filter_input(INPUT_POST,'britishairways',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBritishAirwaysKey' => $britishairways));
	$transavia = filter_input(INPUT_POST,'transavia',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalTransaviaKey' => $transavia));

	$lufthansakey = filter_input(INPUT_POST,'lufthansakey',FILTER_SANITIZE_STRING);
	$lufthansasecret = filter_input(INPUT_POST,'lufthansasecret',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalLufthansaKey' => array('key' => $lufthansakey,'secret' => $lufthansasecret)));

	// Create in settings.php keys not yet configurable if not already here
	//if (!isset($globalImageBingKey)) $settings = array_merge($settings,array('globalImageBingKey' => ''));
	if (!isset($globalDebug)) $settings = array_merge($settings,array('globalDebug' => 'TRUE'));

	$archive = filter_input(INPUT_POST,'archive',FILTER_SANITIZE_STRING);
	if ($archive == 'archive') {
		$settings = array_merge($settings,array('globalArchive' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalArchive' => 'FALSE'));
	}
	$daemon = filter_input(INPUT_POST,'daemon',FILTER_SANITIZE_STRING);
	if ($daemon == 'daemon') {
		$settings = array_merge($settings,array('globalDaemon' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalDaemon' => 'FALSE'));
	}
	$schedules = filter_input(INPUT_POST,'schedules',FILTER_SANITIZE_STRING);
	if ($schedules == 'schedules') {
		$settings = array_merge($settings,array('globalSchedulesFetch' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalSchedulesFetch' => 'FALSE'));
	}

/*
	$datasource = filter_input(INPUT_POST,'datasource',FILTER_SANITIZE_STRING);
	if ($datasource == 'flightaware') {
		$settings = array_merge($settings,array('globalFlightAware' => 'TRUE','globalSBS1' => 'FALSE'));
	} else {
		$settings = array_merge($settings,array('globalFlightAware' => 'FALSE','globalSBS1' => 'TRUE'));
	}
*/
	$settings = array_merge($settings,array('globalFlightAware' => 'FALSE'));
	if ($globalsbs == 'sbs') $settings = array_merge($settings,array('globalSBS1' => 'TRUE'));
	else $settings = array_merge($settings,array('globalSBS1' => 'FALSE'));
	if ($globalivao == 'ivao') {
		$settings = array_merge($settings,array('globalIVAO' => 'TRUE','globalVATSIM' => 'FALSE'));
	} else $settings = array_merge($settings,array('globalIVAO' => 'FALSE'));
	if ($globalvatsim == 'vatsim') {
		$settings = array_merge($settings,array('globalVATSIM' => 'TRUE','globalIVAO' => 'FALSE'));
	} else $settings = array_merge($settings,array('globalVATSIM' => 'FALSE'));
	if ($globalvatsim == 'vatsim' || $globalivao == 'ivao') {
		$settings = array_merge($settings,array('globalSchedulesFetch' => 'FALSE','globalTranslationFetch' => 'FALSE'));
	} else $settings = array_merge($settings,array('globalSchedulesFetch' => 'TRUE','globalTranslationFetch' => 'TRUE'));
	


	$notam = filter_input(INPUT_POST,'notam',FILTER_SANITIZE_STRING);
	if ($notam == 'notam') {
		$settings = array_merge($settings,array('globalNOTAM' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalNOTAM' => 'FALSE'));
	}
	$owner = filter_input(INPUT_POST,'owner',FILTER_SANITIZE_STRING);
	if ($owner == 'owner') {
		$settings = array_merge($settings,array('globalOwner' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalOwner' => 'FALSE'));
	}
	$metar = filter_input(INPUT_POST,'metar',FILTER_SANITIZE_STRING);
	if ($metar == 'metar') {
		$settings = array_merge($settings,array('globalMETAR' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMETAR' => 'FALSE'));
	}
	$metarcycle = filter_input(INPUT_POST,'metarcycle',FILTER_SANITIZE_STRING);
	if ($metarcycle == 'metarcycle') {
		$settings = array_merge($settings,array('globalMETARcycle' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMETARcycle' => 'FALSE'));
	}
	$fork = filter_input(INPUT_POST,'fork',FILTER_SANITIZE_STRING);
	if ($fork == 'fork') {
		$settings = array_merge($settings,array('globalFork' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalFork' => 'FALSE'));
	}

	$colormap = filter_input(INPUT_POST,'colormap',FILTER_SANITIZE_STRING);
	if ($colormap == 'colormap') {
		$settings = array_merge($settings,array('globalMapAltitudeColor' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapAltitudeColor' => 'FALSE'));
	}
	
	if (isset($_POST['aircrafticoncolor'])) {
		$aircrafticoncolor = filter_input(INPUT_POST,'aircrafticoncolor',FILTER_SANITIZE_STRING);
		$settings = array_merge($settings,array('globalAircraftIconColor' => substr($aircrafticoncolor,1)));
	}

	$airportzoom = filter_input(INPUT_POST,'airportzoom',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalAirportZoom' => $airportzoom));

	$mappopup = filter_input(INPUT_POST,'mappopup',FILTER_SANITIZE_STRING);
	if ($mappopup == 'mappopup') {
		$settings = array_merge($settings,array('globalMapPopup' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapPopup' => 'FALSE'));
	}
	$airportpopup = filter_input(INPUT_POST,'airportpopup',FILTER_SANITIZE_STRING);
	if ($airportpopup == 'airportpopup') {
		$settings = array_merge($settings,array('globalAirportPopup' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalAirportPopup' => 'FALSE'));
	}
	$maphistory = filter_input(INPUT_POST,'maphistory',FILTER_SANITIZE_STRING);
	if ($maphistory == 'maphistory') {
		$settings = array_merge($settings,array('globalMapHistory' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapHistory' => 'FALSE'));
	}
	$flightroute = filter_input(INPUT_POST,'flightroute',FILTER_SANITIZE_STRING);
	if ($flightroute == 'flightroute') {
		$settings = array_merge($settings,array('globalMapRoute' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapRoute' => 'FALSE'));
	}

	if (!isset($globalTransaction)) $settings = array_merge($settings,array('globalTransaction' => 'TRUE'));

	// Set some defaults values...
	if (!isset($globalAircraftImageSources)) {
	    $globalAircraftImageSources = array('ivaomtl','wikimedia','airportdata','deviantart','flickr','bing','jetphotos','planepictures','planespotters');
	    $settings = array_merge($settings,array('globalAircraftImageSources' => $globalAircraftImageSources));
	}

	if (!isset($globalSchedulesSources)) {
	    $globalSchedulesSources = array('flightmapper','costtotravel','flightradar24','flightaware');
    	    $settings = array_merge($settings,array('globalSchedulesSources' => $globalSchedulesSources));
    	}

	$settings = array_merge($settings,array('globalInstalled' => 'TRUE'));

	if ($error == '') settings::modify_settings($settings);
	if ($error != '') {
		print '<div class="info column">'.$error.'</div>';
		require('../footer.php');
		exit;
	} else {
		if (isset($_POST['waypoints']) && $_POST['waypoints'] == 'waypoints') $_SESSION['waypoints'] = 1;
		if (isset($_POST['owner']) && $_POST['owner'] == 'owner') $_SESSION['owner'] = 1;
		$_SESSION['install'] = 'database_import';
		//require('../footer.php');
		print '<div class="info column"><ul>';
		
		if (isset($_POST['createdb'])) {
			$_SESSION['done'] = array('Create database','Write configuration');
			print '<li>Create database....<strong>SUCCESS</strong></li>';
		} else $_SESSION['done'] = array('Write configuration');
		print '<li>Write configuration....<img src="../images/loading.gif" /></li></ul></div>';
#		flush();
#		@ob_flush();
#		sleep(10);
		print "<script>setTimeout(window.location = 'index.php?".rand()."&next=".$_SESSION['install']."',10000)</script>";
//		header("Location: index.php?".rand());
//		require('../footer.php');
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] != 'finish') {
	print '<div class="info column">';
	print '<ul><div id="step">';
	$pop = false;
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	    if ($done == 'Create database') $pop = true;
	}
	if ($pop) {
	    sleep(5);
	    print '<li>Create and import tables....<img src="../images/loading.gif" /></li>';
	} else print '<li>Update schema if needed....<img src="../images/loading.gif" /></li>';
	print '</div></ul>';
	print '<div id="error"></div>';
/*	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>'.$SESSION['next'].'....<img src="../images/loading.gif" /></li>';

	if ($error != '') {
		print '<div class="info column"><span class="error"><strong>Error</strong>'.$error.'</span></div>';
		require('../footer.php');
                exit;
	}
*/
?>
    <script language="JavaScript">
		function installaction() {
		    $.ajax({
			url:'install-action.php',
			dataType: 'json',
			async: true,
			success: function(result) {
			    console.log(result);
			    $('#step').html('');
			    result['done'].forEach(function(done) {
				$('#step').append('<li>'+ done +'....<strong>SUCCESS</strong></li>');
			    });
			    if (result['error'] != '') {
				setTimeout(function(){
				    console.log('error !');
				    $('#error').html('<p><b>Error : </b> ' + result['error'] + '</p>');
				}, 1000);
				loop = false;
			    } else if (result['next'] != 'finish') {
				$('#step').append('<li>'+ result['next'] +'....<img src="../images/loading.gif" /></li>');
				installaction();
			    } else if (result['install'] == 'finish') {
				console.log('finish !!!');
				$('#step').append('<li>Reloading page to check all is now ok....<img src="../images/loading.gif" /></li>');
				$(location).attr('href','index.php?next=finish');
				loop = false;
			    }
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) { 
				console.log('error !');
				$('#error').html('<p><b>Error : </b> ' + textStatus + ' - ' + errorThrown + '</p><p><i>If the error is a time-out, you have to increase PHP script execution time-out</i></p>');
			}
		    });
		}


	$(document).ready(function() {
		installaction();
	});
    </script>
<?php
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'finish') {
	unset($_SESSION['install']);
	unset($_COOKIE['install']);
	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Reloading page to check all is now ok....<strong>SUCCESS</strong></li>';
	print '</ul></div>';
	print '<p>All is now installed ! Thanks</p>';
	if ($globalSBS1) {
		print '<p>You need to run scripts/daemon-spotter.php as a daemon. You can use init script in the install/init directory.</p>';
	}
	if ($globalACARS) {
		print '<p>You need to run scripts/daemon-acars.php as a daemon. You can use init script in the install/init directory.</p>';
	}
	if ($globalFlightAware && ($globalFlightAwareUsername == '' || $globalFlightAwarePassword == '')) {
		print '<p>You <strong>must</strong> have a FlightAware API account to use FlightAware source</p>';
	}
	if (isset($globalVATSIM) && $globalVATSIM) {
		print '<p>Airline table is populated with VATSIM data</p>';
	}
	if (isset($globalIVAO) && $globalIVAO) {
		print '<p>You need to run install/populate_ivao.php if you want to have IVAO airlines</p>';
	}
	print '<p>If you want to keep external data updated, you have to add install/update_db.php in cron.</p>';
	print '</div>';
} else {
	unset($_SESSION['install']);
//	header("Location: index.php");
}
require('../footer.php');
?>