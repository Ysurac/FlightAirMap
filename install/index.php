<?php
@session_start();
//print_r($_SESSION);
if (isset($_SESSION['error'])) {
	header('Content-Encoding: none;');
	echo 'Error : '.$_SESSION['error'].' - Resetting install... You need to fix the problem and run install again.';
	if (isset($_SESSION['error'])) unset($_SESSION['error']);
	if (isset($_SESSION['errorlst'])) unset($_SESSION['errorlst']);
	if (isset($_SESSION['next'])) unset($_SESSION['next']);
	if (isset($_SESSION['install'])) unset($_SESSION['install']);
	if (isset($_SESSION['identitied'])) unset($_SESSION['identified']);
}
/*
if (isset($_SESSION['errorlst'])) {
	header('Content-Encoding: none;');
	echo 'Error : Resetting install... You need to fix the problem and run install again.';
	if (isset($_SESSION['error'])) unset($_SESSION['error']);
	if (isset($_SESSION['errorlst'])) unset($_SESSION['errorlst']);
	if (isset($_SESSION['next'])) unset($_SESSION['next']);
	if (isset($_SESSION['install'])) unset($_SESSION['install']);
}
*/
require_once(dirname(__FILE__).'/class.create_db.php');
require_once(dirname(__FILE__).'/class.update_schema.php');
require_once(dirname(__FILE__).'/class.settings.php');
$title="Install";
require(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');
require(dirname(__FILE__).'/header.php');

if (!isset($_SESSION['install']) && !isset($_SESSION['identified'])) {
	$password = filter_input(INPUT_POST,'password',FILTER_SANITIZE_STRING);
	if ($password == '') {
		if ($globalInstalled === TRUE && (!isset($globalInstallPassword) || $globalInstallPassword == '')) {
			print '<div class="alert alert-danger">You need to change $globalInstalled in settings.php to FALSE if you want to access setup again.</div>';
			require('../footer.php');
			exit;
		} elseif (isset($globalInstallPassword) && $globalInstallPassword != '') {
			print '<div class="col-md-6 col-md-offset-3"><form method="post" class="form-horizontal"><fieldset id="askpass"><legend>Install script access</legend><div class="form-group"><label for="password" class="col-sm-2 control-label"><b>Password</b></label><div class="col-sm-10"><input type="password" name="password" id="password" class="form-control" placeholder="Password" value="" /></div></div></fieldset><div class="form-group"><div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-default">Submit</button></div></div></form></div>';
			require('../footer.php');
			exit;
		}
	} elseif (!isset($globalInstallPassword) || $globalInstallPassword == '' || $password != $globalInstallPassword) {
			print '<div class="alert alert-danger">Wrong password.</div>';
			require('../footer.php');
			exit;
	}
}
$_SESSION['identified'] = true;
$writable = false;
$error = array();
if (!isset($_SESSION['install']) && !isset($_POST['dbtype'])) {
	if (!is_writable('../require/settings.php')) {
		print '<div class="alert alert-danger"><strong>Error</strong> The file <i>require/settings.php</i> must be writable.</div>';
		require('../footer.php');
		exit;
	}
	$Common = new Common();
	if (!$Common->is__writable('tmp/')) {
		print '<div class="alert alert-danger"><strong>Error</strong> The directory <i>install/tmp</i> must be writable to the current user.</div>';
		require('../footer.php');
		exit;
	}
	if (!$Common->is__writable('../data/')) {
		print '<div class="alert alert-danger"><strong>Error</strong> The directory <i>data</i> must be writable from this page or at least to <i>scripts/update_db.php</i> user.</div>';
	}
	if (!$Common->is__writable('../images/airlines')) {
		print '<div class="alert alert-warning">The directory <i>images/airlines</i> must be writable for virtual airlines IVAO (else you can ignore this warning).</div>';
	}
	if (!set_time_limit(0)) {
		print '<div class="alert alert-info">You may need to update the maximum execution time.</div>';
	}
	/*
	if (!function_exists('pcntl_fork')) {
		print '<div class="info column"><p><strong>pcntl_fork is not available. Schedules will not be fetched.</strong></p></div>';
	}
	*/
	/*
	if (!extension_loaded('SimpleXML')) {
		$error[] = "SimpleXML is not loaded.";
	}
	if (!extension_loaded('dom')) {
		$error[] = "Dom is not loaded. Needed for aircraft schedule";
	}
	*/
	if (!extension_loaded('PDO')) {
		$error[] = "PDO is not loaded.";
	}
	/*
	if (!extension_loaded('pdo_sqlite')) {
		$error[] = "PDO SQLite is not loaded. Needed to populate database for SBS.";
	}
	*/
	if (!extension_loaded('zip')) {
		//$error[] = "ZIP is not loaded. Needed to populate database for SBS.";
		print '<div class="alert alert-info">ZIP is not loaded. Needed to populate database for IVAO.</div>';
	}
	if (!extension_loaded('xml') && !extension_loaded('xmlreader')) {
		print '<div class="alert alert-warning"><strong>Alert</strong> XML is not loaded. Needed to parse RSS for News pages and if you want tsk files support.</div>';
	}
	if (!extension_loaded('json')) {
		$error[] = "Json is not loaded. Needed for aircraft schedule and bitly.";
	}
	if (!extension_loaded('sockets')) {
		$error[] = "Sockets is not loaded. Needed to populate DB from spotter_daemon.php script.";
	}
	if (!extension_loaded('curl')) {
		$error[] = "Curl is not loaded.";
	}
	if (!file_exists(dirname(__FILE__).'/../.htaccess')) {
		$error[] = dirname(__FILE__).'/../.htaccess'." doesn't exist. The provided .htaccess must exist if you use Apache.";
	}
	if(function_exists('apache_get_modules') ){
		if(!in_array('mod_rewrite',apache_get_modules())) {
			$error[] = "mod_rewrite is not available.";
		}
	/*
		if (!isset($_SERVER['HTTP_FAMHTACCESS'])) {
			$error[] = "htaccess is not interpreted. Check your Apache configuration";
		}
	*/
	}

	if (!function_exists("gettext")) {
		print '<div class="alert alert-warning"><strong>Alert</strong> gettext doesn\'t exist. Site translation not available.</div>';
	} else {
		require_once(dirname(__FILE__).'/../require/class.Language.php');
		$Language = new Language();
		$availablelng = $Language->getLanguages();
		$alllng = $Language->listLocaleDir();
		if (count($alllng) != count($availablelng)) {
			$notavailable = array();
			foreach($alllng as $lng) {
				if (!isset($availablelng[$lng])) $notavailable[] = $lng;
			}
			print '<div class="alert alert-warning">The following translation can\'t be used on your system: '.implode(', ',$notavailable).'. You need to add the system locales: <a href="https://github.com/Ysurac/FlightAirMap/wiki/Translation">documentation</a>.</div>';
		}
	}
	print '<div class="alert alert-info">If you use MySQL or MariaDB, check that <i>max_allowed_packet</i> >= 8M, else import of some tables can fail.</div>';
	if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SERVER_PORT']) && isset($_SERVER['REQUEST_URI'])) {
		if (function_exists('get_headers')) {
			//$check_header = @get_headers($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"].str_replace(array('install/','install'),'search',str_replace('index.php','',$_SERVER["REQUEST_URI"])));
			$check_header = @get_headers($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"].str_replace(array('install/','install'),'live/geojson?test',str_replace('index.php','',$_SERVER["REQUEST_URI"])));
			if (isset($check_header[0]) && !stripos($check_header[0],"200 OK")) {
				print '<div class="alert alert-danger"><strong>Error</strong> Check your configuration, rewrite don\'t seems to work well. If using Apache, you need to desactivate MultiViews <a href="https://github.com/Ysurac/FlightAirMap/wiki/Apache-configuration">https://github.com/Ysurac/FlightAirMap/wiki/Apache-configuration</a></div>';
			} else {
				$check_header = @get_headers($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"].str_replace(array('install/','install'),'search',str_replace('index.php','',$_SERVER["REQUEST_URI"])));
				if (isset($check_header[0]) && !stripos($check_header[0],"200 OK")) {
					print '<div class="alert alert-danger"><strong>Error</strong> Check your configuration, rewrite don\'t seems to work well. If using Apache, you need to desactivate MultiViews <a href="https://github.com/Ysurac/FlightAirMap/wiki/Apache-configuration">https://github.com/Ysurac/FlightAirMap/wiki/Apache-configuration</a></div>';
				}
			}
		}
	}
	if (count($error) > 0) {
		print '<div class="alert alert-danger"><ul>';
		foreach ($error as $err) {
			print '<li>'.$err.'</li>';
		}
		print '</ul>You <strong>must</strong> add these modules/fix errors.</div>';
	//	require('../footer.php');
	//	exit;
	}
}
//if (isset($_SESSION['install'])) echo 'My session';
if (!isset($_SESSION['install']) && !isset($_POST['dbtype']) && (count($error) == 0)) {
	?>
	<div class="info column install">
	<form method="post" class="form-horizontal">
		<fieldset id="install">
			<legend>Install script configuration</legend>
			<p>
				<label for="installpass">Install password</label>
				<input type="password" name="installpass" id="installpass" value="<?php if (isset($globalInstallPassword)) print $globalInstallPassword; ?>" />
			</p>
			<p class="help-block">Password needed to access this install script. If empty, to access this script,  you will need to change the $globalInstalled setting in require/settings.php to FALSE</p>
		</fieldset>
		<fieldset id="database">
			<legend>Database configuration</legend>
			<p>
				<label for="dbtype">Database type</label>
				<select name="dbtype" id="dbtype">
					<option value="mysql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'mysql') { ?>selected="selected" <?php } ?>>MySQL</option>
					<option value="pgsql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'pgsql') { ?>selected="selected" <?php } ?>>PostgreSQL</option>
				</select>
			</p>
			<p>
				<label for="createdb">Create database</label>
				<input type="checkbox" name="createdb" id="createdb" value="createdb" onClick="create_database_js()" />
				<p class="help-block">Create database will not work for MySQL >= 5.7 and MariaDB >= 10.1, you need to create DB and user manually</p>
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
				<label for="dbport">Database port</label>
				<input type="text" name="dbport" id="dbport" value="<?php if (isset($globalDBport)) print $globalDBport; ?>" />
				<p class="help-block">Default is 3306 for MariaDB/MySQL, 5432 for PostgreSQL</p>
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
		<fieldset id="site">
			<legend>Site configuration</legend>
			<p>
				<label for="sitename">Site name</label>
				<input type="text" name="sitename" id="sitename" value="<?php if (isset($globalName)) print $globalName; ?>" />
			</p>
			<p>
				<label for="siteurl">Site directory</label>
				<?php
				    // Try to detect site directory
				    if ((!isset($globalURL) || $globalURL == '') && (!isset($globalDBuser) || $globalDBuser == '')) {
					if (isset($_SERVER['REQUEST_URI'])) {
						$URL = $_SERVER['REQUEST_URI'];
						$globalURL = str_replace('/install','',str_replace('/install/','',str_replace('/install/index.php','',$URL)));
					}
				    }
				?>
				<input type="text" name="siteurl" id="siteurl" value="<?php if (isset($globalURL)) print $globalURL; ?>" />
				<p class="help-block">ex : <i>/flightairmap</i> if complete URL is <i>http://toto.com/flightairmap</i></p>
				<p class="help-block">Can be empty</p>
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
		<fieldset id="mapprov">
			<legend>Map provider</legend>
			<p>
				<label for="mapprovider">Default map Provider</label>
				<select name="mapprovider" id="mapprovider">
					<option value="OpenStreetMap" <?php if (isset($globalMapProvider) && $globalMapProvider == 'OpenStreetMap') { ?>selected="selected" <?php } ?>>OpenStreetMap</option>
					<option value="Mapbox" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Mapbox') { ?>selected="selected" <?php } ?>>Mapbox</option>
					<option value="MapQuest-OSM" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-OSM') { ?>selected="selected" <?php } ?>>MapQuest-OSM</option>
					<option value="MapQuest-Aerial" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-Aerial') { ?>selected="selected" <?php } ?>>MapQuest-Aerial</option>
					<option value="Bing-Hybrid" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Bing-Hybrid') { ?>selected="selected" <?php } ?>>Bing Hybrid</option>
					<option value="Yandex" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Yandex') { ?>selected="selected" <?php } ?>>Yandex</option>
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
				<p class="help-block">Get a key <a href="https://www.mapbox.com/developers/">here</a></p>
			</div>
			<br />
			<div id="google_data">
				<p>
					<label for="googlekey">Google API key</label>
					<input type="text" name="googlekey" id="googlekey" value="<?php if (isset($globalGoogleAPIKey)) print $globalGoogleAPIKey; ?>" />
					<p class="help-block">Get a key <a href="https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key">here</a></p>
				</p>
			</div>
			<br />
			<div id="bing_data">
				<p>
					<label for="bingkey">Bing Map key</label>
					<input type="text" name="bingkey" id="bingkey" value="<?php if (isset($globalBingMapKey)) print $globalBingMapKey; ?>" />
					<p class="help-block">Get a key <a href="https://www.bingmapsportal.com/">here</a></p>
				</p>
			</div>
			<br />
			<div id="mapquest_data">
				<p>
					<label for="mapquestkey">MapQuest key</label>
					<input type="text" name="mapquestkey" id="mapquestkey" value="<?php if (isset($globalMapQuestKey)) print $globalMapQuestKey; ?>" />
					<p class="help-block">Get a key <a href="https://developer.mapquest.com/user/me/apps">here</a></p>
				</p>
			</div>
			<br />
			<div id="here_data">
				<p>
					<label for="hereappid">Here App_Id</label>
					<input type="text" name="hereappid" id="hereappid" value="<?php if (isset($globalHereappId)) print $globalHereappId; ?>" />
				</p>
				<p>
					<label for="hereappcode">Here App_Code</label>
					<input type="text" name="hereappcode" id="hereappcode" value="<?php if (isset($globalHereappCode)) print $globalHereappCode; ?>" />
				</p>
				<p class="help-block">Get a key <a href="https://developer.here.com/rest-apis/documentation/enterprise-map-tile/topics/quick-start.html">here</a></p>
			</div>
			<br />
			<div id="openweathermap_data">
				<p>
					<label for="openweathermapkey">OpenWeatherMap key (weather layer)</label>
					<input type="text" name="openweathermapkey" id="openweathermapkey" value="<?php if (isset($globalOpenWeatherMapKey)) print $globalOpenWeatherMapKey; ?>" />
					<p class="help-block">Get a key <a href="https://openweathermap.org/">here</a></p>
				</p>
			</div>
			<br />
		</fieldset>
		<fieldset id="offline">
			<legend>Offline mode</legend>
		<?php
			if (file_exists(dirname(__FILE__).'/../js/Cesium/Cesium.js')) {
		?>
			<p>
				<input type="checkbox" name="mapoffline" id="mapoffline" value="mapoffline" <?php if (isset($globalMapOffline) && $globalMapOffline) { ?>checked="checked" <?php } ?>/>
				<label for="mapoffline">Map offline mode</label>
				<p class="help-block">Map offline mode will not use network to display map but Natural Earth</p>
			</p>
		<?php
			}
		?>
			<p>
				<input type="checkbox" name="globaloffline" id="globaloffline" value="globaloffline" <?php if (isset($globalOffline) && $globalOffline) { ?>checked="checked" <?php } ?>/>
				<label for="globaloffline">Offline mode</label>
				<p class="help-block">Backend will not use network</p>
			</p>
		</fieldset>
		<fieldset id="coverage">
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
				<select name="squawk_country" id="squawk_country">
					<option value="UK"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'UK') print ' selected '; ?>>UK</option>
					<option value="NZ"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'NZ') print ' selected '; ?>>NZ</option>
					<option value="US"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'US') print ' selected '; ?>>US</option>
					<option value="AU"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'AU') print ' selected '; ?>>AU</option>
					<option value="NL"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'NL') print ' selected '; ?>>NL</option>
					<option value="FR"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'FR') print ' selected '; ?>>FR</option>
					<option value="TR"<?php if (isset($globalSquawkCountry) && $globalSquawkCountry == 'TR') print ' selected '; ?>>TR</option>
				</select>
			</p>
		</fieldset>
		<fieldset id="zone">
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
		<fieldset id="sourceloc">
			<legend>Sources location</legend>
			<table class="sources">
				<thead>
				<tr>
					<th>Name</th>
					<th>Latitude</th>
					<th>Longitude</th>
					<th>Altitude (in m)</th>
					<th>City</th>
					<th>Country</th>
					<th>Source name</th>
				</tr>
				</thead>
				<tbody>
		<?php
		    if (isset($globalDBuser) && isset($globalDBpass) && $globalDBuser != '' && $globalDBpass != '') {
		?>
		<!--
		<?php
			    require_once(dirname(__FILE__).'/../require/class.Connection.php');
			    $Connection = new Connection();
		?>
		-->
		<?php
			if ($Connection->db != NULL) {
			    if ($Connection->tableExists('source_location')) {
				require_once(dirname(__FILE__).'/../require/class.Source.php');
				$Source = new Source();
				//$alllocations = $Source->getAllLocationInfo();
				$alllocations = $Source->getLocationInfobyType('');
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
					<td><input type="text" name="source_ref[]" value="<?php print $location['source']; ?>" /></td>
				</tr>
		
		<?php
				}
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
					<td><input type="text" name="source_ref[]" value="" /></td>
				</tr>
				</tbody>
			</table>
			<center>
				<input type="button" value="Add a row" class="add-row-source" />
				<input type="button" value="Remove last row" class="del-row-source" />
			</center>
		</fieldset>
		<fieldset>
			<legend>Source Type</legend>
			<p>
				<input type="checkbox" name="globalaircraft" id="aircraft" value="aircraft" <?php if (!isset($globalAircraft) || $globalAircraft) { ?>checked="checked" <?php } ?>/>
				<label for="aircraft">Aircrafts</label>
				<input type="checkbox" name="globaltracker" id="tracker" value="tracker" <?php if (isset($globalTracker) && $globalTracker) { ?>checked="checked" <?php } ?>/>
				<label for="tracker">Trackers</label>
				<input type="checkbox" name="globalmarine" id="marine" value="marine" <?php if (isset($globalMarine) && $globalMarine) { ?>checked="checked" <?php } ?>/>
				<label for="marine">Ships/Vessels</label>
				<input type="checkbox" name="globalsatellite" id="satellite" value="satellite" <?php if (isset($globalSatellite) && $globalSatellite) { ?>checked="checked" <?php } ?>/>
				<label for="satellite">Satellites</label>
			</p>
		</fieldset>
		<fieldset id="datasource">
			<legend>Data source</legend>
			<p>
				<b>Virtual flights</b>
				<p>
				<p><i>If you choose IVAO, airlines names and logos will come from ivao.aero (you have to run install/populate_ivao.php to populate table with IVAO data)</i></p>
				<input type="checkbox" name="globalva" id="va" value="va" onClick="datasource_js()" <?php if (isset($globalVA) && $globalVA) { ?>checked="checked" <?php } ?>/>
				<label for="va">Virtual Airlines</label>
				<input type="checkbox" name="globalivao" id="ivao" value="ivao" onClick="datasource_js()" <?php if (isset($globalIVAO) && $globalIVAO) { ?>checked="checked" <?php } ?>/>
				<label for="ivao">IVAO</label>
				<input type="checkbox" name="globalvatsim" id="vatsim" value="vatsim" onClick="datasource_js()" <?php if (isset($globalVATSIM) && $globalVATSIM) { ?>checked="checked" <?php } ?>/>
				<label for="vatsim">VATSIM</label>
				<input type="checkbox" name="globalphpvms" id="phpvms" value="phpvms" onClick="datasource_js()" <?php if (isset($globalphpVMS) && $globalphpVMS) { ?>checked="checked" <?php } ?>/>
				<label for="phpvms">phpVMS</label>
				<input type="checkbox" name="globalvam" id="vam" value="vam" onClick="datasource_js()" <?php if (isset($globalVAM) && $globalVAM) { ?>checked="checked" <?php } ?>/>
				<label for="vam">Virtual Airline Manager</label>
				</p>
			</p><p>
				<b>Real flights</b>
				<p>
<!--
				<input type="radio" name="datasource" id="flightaware" value="flightaware" onClick="datasource_js()" <?php if (isset($globalFlightAware) && $globalFlightAware) { ?>checked="checked" <?php } ?>/>
				<label for="flightaware">FlightAware (not tested, no more supported no data feed available for test)</label>
-->
				<input type="checkbox" name="globalsbs" id="sbs" value="sbs" onClick="datasource_js()" <?php if (isset($globalSBS1) && $globalSBS1) { ?>checked="checked" <?php } ?> />
				<label for="sbs">ADS-B, SBS-1 format (dump1090 or SBS-1 compatible format)</label>
				<input type="checkbox" name="globalaprs" id="aprs" value="aprs" onClick="datasource_js()" <?php if (isset($globalAPRS) && $globalAPRS) { ?>checked="checked" <?php } ?> />
				<label for="sbs">APRS from glidernet</label>
				<input type="checkbox" name="acars" id="acars" value="acars" onClick="datasource_js()" <?php if (isset($globalACARS) && $globalACARS) { ?>checked="checked" <?php } ?> />
				<label for="acars">ACARS</label>
				</p>
			</p>
			<p>
				<b>Virtual marine</b>
				<p>
				<input type="checkbox" name="globalvm" id="globalvm" value="vm" onClick="datasource_js()" <?php if (isset($globalVM) && $globalVM) { ?>checked="checked" <?php } ?>/>
				<label for="globalvm">Virtual Marine</label>
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
			<div id="sailaway_data">
				<p>
					<label for="sailawayemail">Sailaway email</label>
					<input type="text" name="sailawayemail" id="sailawayemail" value="<?php if (isset($globalSailaway['email'])) print $globalSailaway['email']; ?>" />
					<p class="help-block">Only needed for Sailaway full format</p>
				</p>
				<p>
					<label for="sailawaypassword">Sailaway password</label>
					<input type="text" name="sailawaypassword" id="sailawaypassword" value="<?php if (isset($globalSailaway['password'])) print $globalSailaway['password']; ?>" />
					<p class="help-block">Only needed for Sailaway full format</p>
				</p>
				<p>
					<label for="sailawaykey">Sailaway API key</label>
					<input type="text" name="sailawaykey" id="sailawaykey" value="<?php if (isset($globalSailaway['key'])) print $globalSailaway['key']; ?>" />
				</p>
			</div>

<!--			<div id="sbs_data">
-->
				<fieldset id="sources">
					<legend>Sources</legend>
					<table id="SourceTable" class="table">
						<thead>
							<tr>
								<th>Host/URL</th>
								<th>Port/Callback pass</th>
								<th>Format</th>
								<th>Name</th>
								<th>Source Stats</th>
								<th>No archive</th>
								<th>Source TimeZone</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
<?php

		if (!isset($globalSources) && isset($globalSBS1Hosts)) {
			if (!is_array($globalSBS1Hosts)) {
				$globalSources[] = array('host' => $globalSBS1Hosts);
			} else {
				foreach ($globalSBS1Hosts as $host) {
					$globalSources[] = array('host' => $host);
				}
			}
		}
		$i = 0;
		if (isset($globalSources)) {
			foreach ($globalSources as $source) {
?>
							<tr>
								<?php
								    if (filter_var($source['host'],FILTER_VALIDATE_URL)) {
								?>
								<td><input type="text" name="host[]" value="<?php print $source['host']; ?>" /></td>
								<td><input type="text" name="port[]" class="col-xs-2" value="<?php if (isset($source['port'])) print $source['port']; ?>" /></td>
								<?php
								    } else {
									$hostport = explode(':',$source['host']);
									if (isset($hostport[1])) {
										$host = $hostport[0];
										$port = $hostport[1];
									} else {
										$host = $source['host'];
										$port = $source['port'];
									}
								?>
								<td><input type="text" name="host[]" value="<?php print $host; ?>" /></td>
								<td><input type="text" name="port[]" class="col-xs-2" value="<?php print $port; ?>" /></td>
								<?php
								    }
								?>
								<td>
									<select name="format[]">
										<option value="auto" <?php if (!isset($source['format'])) print 'selected'; ?>>Auto</option>
										<option value="sbs" <?php if (isset($source['format']) && $source['format'] == 'sbs') print 'selected'; ?>>SBS</option>
										<option value="tsv" <?php if (isset($source['format']) && $source['format'] == 'tsv') print 'selected'; ?>>TSV</option>
										<option value="raw" <?php if (isset($source['format']) && $source['format'] == 'raw') print 'selected'; ?>>Raw</option>
										<option value="aircraftjson" <?php if (isset($source['format']) && $source['format'] == 'aircraftjson') print 'selected'; ?>>Dump1090 aircraft.json</option>
										<option value="planefinderclient" <?php if (isset($source['format']) && $source['format'] == 'planefinderclient') print 'selected'; ?>>Planefinder client</option>
										<option value="aprs" <?php if (isset($source['format']) && $source['format'] == 'aprs') print 'selected'; ?>>APRS</option>
										<option value="deltadbtxt" <?php if (isset($source['format']) && $source['format'] == 'deltadbtxt') print 'selected'; ?>>Radarcape deltadb.txt</option>
                                        <option value="radarcapejson" <?php if (isset($source['format']) && $source['format'] == 'radarcapejson') print 'selected'; ?>>Radarcape json</option>
										<option value="vatsimtxt" <?php if (isset($source['format']) && $source['format'] == 'vatsimtxt') print 'selected'; ?>>Vatsim</option>
										<option value="aircraftlistjson" <?php if (isset($source['format']) && $source['format'] == 'aircraftlistjson') print 'selected'; ?>>Virtual Radar Server AircraftList.json</option>
										<option value="vrstcp" <?php if (isset($source['format']) && $source['format'] == 'vrstcp') print 'selected'; ?>>Virtual Radar Server TCP</option>
										<option value="phpvmacars" <?php if (isset($source['format']) && $source['format'] == 'phpvmacars') print 'selected'; ?>>phpVMS</option>
										<option value="vaos" <?php if (isset($source['format']) && $source['format'] == 'phpvmacars') print 'selected'; ?>>Virtual Airline Operations System (VAOS)</option>
										<option value="vam" <?php if (isset($source['format']) && $source['format'] == 'vam') print 'selected'; ?>>Virtual Airlines Manager</option>
										<option value="whazzup" <?php if (isset($source['format']) && $source['format'] == 'whazzup') print 'selected'; ?>>IVAO</option>
										<option value="flightgearmp" <?php if (isset($source['format']) && $source['format'] == 'flightgearmp') print 'selected'; ?>>FlightGear Multiplayer</option>
										<option value="flightgearsp" <?php if (isset($source['format']) && $source['format'] == 'flightgearsp') print 'selected'; ?>>FlightGear Singleplayer</option>
										<option value="acars" <?php if (isset($source['format']) && $source['format'] == 'acars') print 'selected'; ?>>ACARS from acarsdec/acarsdeco2 over UDP</option>
										<option value="acarssbs3" <?php if (isset($source['format']) && $source['format'] == 'acarssbs3') print 'selected'; ?>>ACARS SBS-3 over TCP</option>
										<option value="acarsjson" <?php if (isset($source['format']) && $source['format'] == 'acarsjson') print 'selected'; ?>>ACARS from acarsdec json and vdlm2dec</option>
										<option value="acarsjsonudp" <?php if (isset($source['format']) && $source['format'] == 'acarsjsonudp') print 'selected'; ?>>ACARS from acarsdec json and vdlm2dec over UDP</option>
										<option value="ais" <?php if (isset($source['format']) && $source['format'] == 'ais') print 'selected'; ?>>NMEA AIS over TCP</option>
										<option value="airwhere" <?php if (isset($source['format']) && $source['format'] == 'airwhere') print 'selected'; ?>>AirWhere website</option>
										<option value="hidnseek_callback" <?php if (isset($source['format']) && $source['format'] == 'hidnseek_callback') print 'selected'; ?>>HidnSeek Callback</option>
										<option value="blitzortung" <?php if (isset($source['format']) && $source['format'] == 'blitzortung') print 'selected'; ?>>Blitzortung</option>
										<option value="sailaway" <?php if (isset($source['format']) && $source['format'] == 'sailaway') print 'selected'; ?>>Sailaway</option>
										<option value="sailawayfull" <?php if (isset($source['format']) && $source['format'] == 'sailawayfull') print 'selected'; ?>>Sailaway with missions, races,...</option>
									</select>
								</td>
								<td>
									<input type="text" name="name[]" value="<?php if (isset($source['name'])) print $source['name']; ?>" />
								</td>
								<td><input type="checkbox" name="sourcestats[]" title="Create statistics for the source like number of messages, distance,..." value="1" <?php if (isset($source['sourcestats']) && $source['sourcestats']) print 'checked'; ?> /></td>
								<td><input type="checkbox" name="noarchive[]" title="Don't archive this source" value="1" <?php if (isset($source['noarchive']) && $source['noarchive']) print 'checked'; ?> /></td>
								<td>
									<select name="timezones[]">
								<?php
									$timezonelist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
									foreach($timezonelist as $timezones){
										if (isset($source['timezone']) && $source['timezone'] == $timezones) {
											print '<option selected>'.$timezones.'</option>';
										} elseif (!isset($source['timezone']) && $timezones == 'UTC') {
											print '<option selected>'.$timezones.'</option>';
										} else print '<option>'.$timezones.'</option>';
									}
								?>
									</select>
								</td>
								<td><input type="button" value="Delete" onclick="deleteRow(this)" /> <input type="button" value="Add" onclick="insRow()" /></td>
							</tr>
<?php
			}
		}
?>
							<tr>
								<td><input type="text" name="host[]" value="" /></td>
								<td><input type="text" name="port[]" class="col-xs-2" value="" /></td>
								<td>
									<select name="format[]">
										<option value="auto">Auto</option>
										<option value="sbs">SBS</option>
										<option value="tsv">TSV</option>
										<option value="raw">Raw</option>
										<option value="aircraftjson">Dump1090 aircraft.json</option>
										<option value="planefinderclient">Planefinder client</option>
										<option value="aprs">APRS</option>
										<option value="deltadbtxt">Radarcape deltadb.txt</option>
										<option value="radarcapejson">Radarcape json</option>
										<option value="vatsimtxt">Vatsim</option>
										<option value="aircraftlistjson">Virtual Radar Server AircraftList.json</option>
										<option value="vrstcp">Virtual Radar Server TCP</option>
										<option value="phpvmacars">phpVMS</option>
										<option value="vaos">Virtual Airline Operations System (VAOS)</option>
										<option value="vam">Virtual Airlines Manager</option>
										<option value="whazzup">IVAO</option>
										<option value="flightgearmp">FlightGear Multiplayer</option>
										<option value="flightgearsp">FlightGear Singleplayer</option>
										<option value="acars">ACARS from acarsdec/acarsdeco2 over UDP</option>
										<option value="acarssbs3">ACARS SBS-3 over TCP</option>
										<option value="acarsjson">ACARS from acarsdec json and vdlm2dec</option>
										<option value="acarsjsonudp">ACARS from acarsdec json and vdlm2dec over UDP</option>
										<option value="ais">NMEA AIS over TCP</option>
										<option value="airwhere">AirWhere website</option>
										<option value="hidnseek_callback">HidnSeek Callback</option>
										<option value="blitzortung">Blitzortung</option>
										<option value="sailaway">Sailaway</option>
										<option value="sailawayfull">Sailaway with missions, races,...</option>
									</select>
								</td>
								<td>
									<input type="text" name="name[]" value="" id="name" />
								</td>
								<td><input type="checkbox" name="sourcestats[]" id="sourcestats" title="Create statistics for the source like number of messages, distance,..." value="1" /></td>
								<td><input type="checkbox" name="noarchive[]" id="noarchive" title="Don't archive this source" value="1" /></td>
								<td>
									<select name="timezones[]" id="timezones">
								<?php
									$timezonelist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
									foreach($timezonelist as $timezones){
										if ($timezones == 'UTC') {
											print '<option selected>'.$timezones.'</option>';
										} else print '<option>'.$timezones.'</option>';
									}
								?>
									</select>
								</td>
								<td><input type="button" id="delhosti" value="Delete" onclick="deleteRow(this)" /> <input type="button" id="addhosti" value="Add" onclick="insRow()" /></td>
							</tr>
						</tbody>
					</table>
					<p class="help-block">Source timezone is to define timezone used by a source if not UTC (not your timezone).</p>
					<p class="help-block">For working source statistics, the name of the source <b>MUST</b> be the same as the source name of a source location, else center coverage latitude and longitude is used as source position. This is not available/usable with virtual airlines.</p>
					<p class="help-block">FlightGear Singleplayer open an UDP server, the host should be <i>0.0.0.0</i>.</p>
					<p class="help-block">Virtual Airlines Manager need to use the file <i>install/vAM/VAM-json.php</i> and the url <i>http://yourvaminstall/VAM-json.php</i>.</p>
					<p class="help-block">For a local file, you should use file:// before the path.</p>
					<p class="help-block">URL and TCP sources can't be used at the same time.</p>
					<!-- ' -->
					<p class="help-block">Callback script is in <i>import/callback.php</i>. In host you can restrict access to some IP, Callback pass to restrict by a pass using <i>import/callback.php?pass=yourpass</i>.</p>
				</fieldset>
			</fieldset>
			<div id="acars_data">
				<fieldset>
					<legend>Source ACARS</legend>
					<p>Listen UDP server for acarsdec/acarsdeco2/... with <i>daemon-acars.php</i> script</p>
					<p>
						<label for="acarshost">ACARS UDP host</label>
						<input type="text" name="acarshost" id="acarshost" value="<?php if (isset($globalACARSHost)) print $globalACARSHost; ?>" />
					</p>
					<p>
						<label for="acarsport">ACARS UDP port</label>
						<input type="number" name="acarsport" id="acarsport" value="<?php if (isset($globalACARSPort)) print $globalACARSPort; ?>" />
					</p>
					<p class="help-block"><i>daemon-acars.php</i> can only be run as daemon. It's an alternate script for ACARS data, <i>daemon-spotter.php</i> may be better.</p>
				</fieldset>
			</div>
		</fieldset>
		<fieldset id="newsi">
			<legend>News</legend>
			<table class="newsi table" id="NewsTable">
			    <thead>
				<tr>
				    <td>RSS/Atom URL</td>
				    <td>Language</td>
				    <td>Type</td>
				    <td>Action</td>
			    </thead>
			    <tbody>
				<?php
				    if (isset($globalNewsFeeds) && !empty($globalNewsFeeds)) {
					foreach ($globalNewsFeeds as $type => $feedslng) {
					    foreach ($feedslng as $lng => $feeds) {
						foreach ($feeds as $feed) {
				?>
				<tr>
				    <td><input type="url" name="newsurl[]" value="<?php print $feed; ?>"/></td>
				    <td>
					<select name="newslang[]">
					    <option value="en"<?php if ($lng == 'en') print ' selected'; ?>>English</option>
					    <option value="fr"<?php if ($lng == 'fr') print ' selected'; ?>>French</option>
					</select>
				    </td>
				    <td>
					<select name="newstype[]">
					    <option value="global"<?php if ($type == 'global') print ' selected'; ?>>Global</option>
					    <option value="aircraft"<?php if ($type == 'aircraft') print ' selected'; ?>>Aircraft</option>
					    <option value="marine"<?php if ($type == 'marine') print ' selected'; ?>>Marine</option>
					    <option value="tracker"<?php if ($type == 'tracker') print ' selected'; ?>>Tracker</option>
					    <option value="satellite"<?php if ($type == 'Satellite') print ' selected'; ?>>Satellite</option>
					</select>
				    </td>
				    <td><input type="button" value="Delete" onclick="deleteRowNews(this)" /> <input type="button" value="Add" onclick="insRowNews()" /></td>
				</tr>
				
				<?php
						}
					    }
					}
				    }
				?>
				<tr>
				    <td><input type="url" name="newsurl[]" /></td>
				    <td>
					<select name="newslang[]">
					    <option value="en">English</option>
					    <option value="fr">French</option>
					</select>
				    </td>
				    <td>
					<select name="newstype[]">
					    <option value="global">Global</option>
					    <option value="aircraft">Aircraft</option>
					    <option value="marine">Marine</option>
					    <option value="tracker">Tracker</option>
					    <option value="satellite">Satellite</option>
					</select>
				    </td>
				    <td><input type="button" value="Delete" onclick="deleteRowNews(this)" /> <input type="button" value="Add" onclick="insRowNews()" /></td>
				</tr>
			    </tbody>
			</table>
			<p class="help-block"><i>News</i> page syndicate RSS/Atom feeds. If you only use one mode, use type global.</p>
		</fieldset>
		
		<fieldset id="optional">
			<legend>Optional configuration</legend>
			<p>
				<label for="crash">Add accident/incident support for real flights</label>
				<input type="checkbox" name="crash" id="crash" value="crash"<?php if ((isset($globalAccidents) && $globalAccidents) || !isset($globalAccidents)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="firessupport">Add fires support</label>
				<input type="checkbox" name="firessupport" id="firessupport" value="firessupport"<?php if (isset($globalFires) && $globalFires) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Fires are updated via <i>update_db.php</i> script.</p>
			</p>
			<p>
				<label for="fires">Display fires on map</label>
				<input type="checkbox" name="fires" id="fires" value="fires"<?php if (isset($globalMapFires) && $globalMapFires) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Display all fires on map by default.</p>
			</p>
			<br />
			<p>
				<label for="map3d">Enable map in 3D</label>
				<input type="checkbox" name="map3d" id="map3d" value="map3d"<?php if ((isset($globalMap3D) && $globalMap3D) || !isset($globalMap3D)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Bing map key is needed. <i>scripts/update_db.php</i> will download 3d models needed, about 400Mo is needed for all models.</p>
			</p>
			<p>
				<label for="map3ddefault">Default to map in 3D</label>
				<input type="checkbox" name="map3ddefault" id="map3ddefault" value="map3ddefault"<?php if (isset($globalMap3Ddefault) && $globalMap3Ddefault) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="one3dmodel">Use same 3D model for all aircraft</label>
				<input type="checkbox" name="one3dmodel" id="one3dmodel" value="one3dmodel"<?php if (isset($globalMap3DOneModel) && $globalMap3DOneModel) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Use less resources</p>
			</p>
			<p>
				<label for="map3dliveries">Display real liveries</label>
				<input type="checkbox" name="map3dliveries" id="map3dliveries" value="map3dliveries"<?php if (isset($globalMap3DLiveries) && $globalMap3DLiveries) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Liveries will be loaded when you click on a flight (about 300Mo is needed for all liveries)</p>
			</p>
			<p>
				<label for="map3dtileset">3D Tiles</label>
				<input type="text" name="map3dtileset" id="map3dtileset" value="<?php if (isset($globalMap3DTiles) && $globalMap3DTiles) { print $globalMap3DTiles; } ?>" />
				<p class="help-block">Set the url of your 3D Tiles</p>
			</p>
			<p>
				<label for="map3dshadows">Use sun shadows on 3D models</label>
				<input type="checkbox" name="map3dshadows" id="map3dshadows" value="map3dshadows"<?php if (!isset($globalMap3DShadows) || (isset($globalMap3DShadows) && $globalMap3DShadows)) { ?> checked="checked" <?php } ?> />
			</p>
			<p>
				<label for="corsproxy">CORS proxy</label>
				<input type="text" name="corsproxy" id="corsproxy" value="<?php if (isset($globalCORSproxy)) print $globalCORSproxy; else print 'https://galvanize-cors-proxy.herokuapp.com/' ?>" />
				<p class="help-block">CORS proxy used for some WMS servers</p>
			</p>
<!--
			<p>
				<label for="mapsatellites">Enable satellites in 3D map</label>
				<input type="checkbox" name="mapsatellites" id="mapsatellites" value="mapsatellites"<?php if ((isset($globalMapSatellites) && $globalMapSatellites) || !isset($globalMapSatellites)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Bing map key is needed.</p>
			</p>
-->
			<br />
			<p>
				<label for="translate">Allow site translation</label>
				<input type="checkbox" name="translate" id="translate" value="translate"<?php if (isset($globalTranslate) && $globalTranslate) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Display language available, else the site is only available in english.</p>
			</p>
			<br />
			<p>
				<label for="realairlines">Always use real airlines</label>
				<input type="checkbox" name="realairlines" id="realairlines" value="realairlines"<?php if (isset($globalUseRealAirlines) && $globalUseRealAirlines) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Use real airlines for IVAO or VATSIM.</p>
			</p>
			<br />
			<p>
				<label for="estimation">Planes animate between updates</label>
				<input type="checkbox" name="estimation" id="estimation" value="estimation"<?php if (isset($globalMapEstimation) && $globalMapEstimation) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Estimate plane track between flights refresh.</p>
			</p>
			<br />
			<p>
				<label for="unitdistance">Unit for distance</label>
				<select name="unitdistance" id="unitdistance">
					<option value="km" <?php if (isset($globalUnitDistance) && $globalUnitDistance == 'km') { ?>selected="selected" <?php } ?>>Kilometres</option>
					<option value="nm" <?php if (isset($globalUnitDistance) && $globalUnitDistance == 'nm') { ?>selected="selected" <?php } ?>>Nautical Miles</option>
					<option value="mi" <?php if (isset($globalUnitDistance) && $globalUnitDistance == 'mi') { ?>selected="selected" <?php } ?>>Statute Miles</option>
				</select>
			</p>
			<p>
				<label for="unitaltitude">Unit for altitude</label>
				<select name="unitaltitude" id="unitaltitude">
					<option value="m" <?php if (isset($globalUnitAltitude) && $globalUnitAltitude == 'm') { ?>selected="selected" <?php } ?>>Metres</option>
					<option value="feet" <?php if (isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') { ?>selected="selected" <?php } ?>>Feet</option>
				</select>
			</p>
			<p>
				<label for="unitspeed">Unit for speed</label>
				<select name="unitspeed" id="unitspeed">
					<option value="kmh" <?php if (isset($globalUnitSpeed) && $globalUnitSpeed == 'kmh') { ?>selected="selected" <?php } ?>>Kilometres/Hour</option>
					<option value="mph" <?php if (isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') { ?>selected="selected" <?php } ?>>Miles/Hour</option>
					<option value="knots" <?php if (isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') { ?>selected="selected" <?php } ?>>Knots</option>
				</select>
			</p>
			<br />
			<div id="optional_sbs">
			<p>
				<label for="schedules">Retrieve schedules from external websites</label>
				<input type="checkbox" name="schedules" id="schedules" value="schedules"<?php if (isset($globalSchedulesFetch) && $globalSchedulesFetch || !isset($globalSchedulesFetch)) { ?> checked="checked"<?php } ?> onClick="schedule_js()" />
				<p class="help-block">Not available for IVAO</p>
			</p>
			<br />
			<div id="schedules_options">
				<p>
					<label for="britishairways">British Airways API Key</label>
					<input type="text" name="britishairways" id="britishairways" value="<?php if (isset($globalBritishAirwaysKey)) print $globalBritishAirwaysKey; ?>" />
					<p class="help-block">Register an account on <a href="https://developer.ba.com/">https://developer.ba.com/</a></p>
				</p>
				<!--
				<p>
					<label for="transavia">Transavia Test API Consumer Key</label>
					<input type="text" name="transavia" id="transavia" value="<?php if (isset($globalTransaviaKey)) print $globalTransaviaKey; ?>" />
					<p class="help-block">Register an account on <a href="https://developer.transavia.com">https://developer.transavia.com</a></p>
				</p>
				-->
				<p>
					<div class="form-group">
						<b>Lufthansa API Key</b>
						<p>
							<label for="lufthansakey">Key</label>
							<input type="text" name="lufthansakey" id="lufthansakey" value="<?php if (isset($globalLufthansaKey['key'])) print $globalLufthansaKey['key']; ?>" />
						</p><p>
							<label for="lufthansasecret">Secret</label>
							<input type="text" name="lufthansasecret" id="lufthansasecret" value="<?php if (isset($globalLufthansaKey['secret'])) print $globalLufthansaKey['secret']; ?>" />
						</p>
					</div>
					<p class="help-block">Register an account on <a href="https://developer.lufthansa.com/page">https://developer.lufthansa.com/page</a></p>
				</p>
				<p>
					<div class="form-group">
						<b>FlightAware API Key</b>
						<p>
							<label for="flightawareusername">Username</label>
							<input type="text" name="flightawareusername" id="flightawareusername" value="<?php if (isset($globalFlightAwareUsername)) print $globalFlightAwareUsername; ?>" />
						</p>
						<p>
							<label for="flightawarepassword">API key</label>
							<input type="text" name="flightawarepassword" id="flightawarepassword" value="<?php if (isset($globalFlightAwarePassword)) print $globalFlightAwarePassword; ?>" />
						</p>
					</div>
					<p class="help-block">Register an account on <a href="https://www.flightaware.com/">https://www.flightaware.com/</a></p>
				</p>
			</div>
			<br />
			<p>
				<label for="mapmatching">Map Matching</label>
				<input type="checkbox" name="mapmatching" id="mapmatching" value="mapmatching"<?php if (isset($globalMapMatching) && $globalMapMatching) { ?> checked="checked"<?php } ?> onClick="mapmatching_js()" />
				<p class="help-block">Only for Tracker mode</p>
			</p>
			<br />
			<div id="mapmatching_options">
				<p>
					<label for="mapmatchingsource">Map Matching source</label>
					<select name="mapmatchingsource" id="mapmatchingsource">
						<option value="fam" <?php if ((isset($globalMapMatchingSource) && $globalMapMatchingSource == 'fam') || !isset($globalMatchingSource)) print 'selected="selected" '; ?>>FlightAirMap Map Matching</option>
						<option value="graphhopper" <?php if (isset($globalMapMatchingSource) && $globalMapMatchingSource == 'graphhopper') print 'selected="selected" '; ?>>GraphHopper</option>
						<option value="osmr" <?php if (isset($globalMapMatchingSource) && $globalMapMatchingSource == 'osmr') print 'selected="selected" '; ?>>OSMR</option>
						<option value="mapbox" <?php if (isset($globalMapMatchingSource) && $globalMapMatchingSource == 'mapbox') print 'selected="selected" '; ?>>Mapbox</option>
					</select>
					<p class="help-block">Mapbox need the API Key defined in map section.</p>
					<p class="help-block">FlightAirMap Map Matching is free, without API key but limited to about 100 input points to keep fast results.</p>
				</p>
				<br />
				<p>
					<label for="graphhopper">GraphHopper API Key</label>
					<input type="text" name="graphhopper" id="graphhopper" value="<?php if (isset($globalGraphHopperKey)) print $globalGraphHopperKey; ?>" />
					<p class="help-block">Register an account on <a href="https://www.graphhopper.com/">https://www.graphhopper.com/</a></p>
				</p>
			</div>
			<br />
			<p>
				<label for="owner">Add private owners of aircrafts</label>
				<input type="checkbox" name="owner" id="owner" value="owner"<?php if (isset($globalOwner) && $globalOwner) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Display also private owners of aircrafts, else only commercial owners are available</p>
			</p>
			</div>
			<br />
			<p>
				<label for="notam">Activate NOTAM support</label>
				<input type="checkbox" name="notam" id="notam" value="notam"<?php if (isset($globalNOTAM) && $globalNOTAM) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="notamsource">URL of your feed from notaminfo.com</label>
				<input type="text" name="notamsource" id="notamsource" value="<?php if (isset($globalNOTAMSource)) print $globalNOTAMSource; ?>" />
				<p class="help-block">If you want to use world NOTAM from FlightAirMap website, leave it blank</p>
			</p>
			<br />
			<p>
				<label for="metar">Activate METAR support</label>
				<input type="checkbox" name="metar" id="metar" value="metar"<?php if (isset($globalMETAR) && $globalMETAR) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="metarcycle">Activate METAR cycle support</label>
				<input type="checkbox" name="metarcycle" id="metarcycle" onClick="metarcycle_js()" value="metarcycle"<?php if (isset($globalMETARcycle) && $globalMETARcycle) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Download feed from NOAA every hour. Need <i>scripts/update_db.php</i> in cron</p>
			</p>
			<div id="metarsrc">
				<p>
					<label for="metarsource">URL of your METAR source</label>
					<input type="text" name="metarsource" id="metarsource" value="<?php if (isset($globalMETARurl)) print $globalMETARurl; ?>" />
					<p class="help-block">Use {icao} to specify where we replace by airport icao. ex : http://metar.vatsim.net/metar.php?id={icao}</p>
				</p>
			</div>
			<br />
			<!--
			<div id="podaac">
				<p>
					<label for="podaccuser">PO.DAAC username (used for waves)</label>
					<input type="text" name="podaccuser" id="podaccuser" value="<?php if (isset($globalPODAACuser)) print $globalPODAACuser; ?>" />
				</p>
				<p>
					<label for="podaccpass">PO.DAAC password</label>
					<input type="text" name="podaccpass" id="podaccpass" value="<?php if (isset($globalPODAACpass)) print $globalPODAACpass; ?>" />
				</p>
				<p class="help-block">Register an account on <a href="https://podaac-tools.jpl.nasa.gov/drive/">https://podaac-tools.jpl.nasa.gov/drive/</a>, an encoded password is available on this page after registration (not the same as the one used for registration).</p>
			</div>
			<br />
			-->
			<p>
				<label for="bitly">Bit.ly access token api (used in search page)</label>
				<input type="text" name="bitly" id="bitly" value="<?php if (isset($globalBitlyAccessToken)) print $globalBitlyAccessToken; ?>" />
			</p>
			<br />
			<p>
				<label for="waypoints">Add Waypoints, Airspace and countries data (about 45Mio in DB) <i>Need PostGIS if you use PostgreSQL</i></label>
				<input type="checkbox" name="waypoints" id="waypoints" value="waypoints"<?php if (!isset($globalWaypoints) || (isset($globalWaypoints) && $globalWaypoints)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="geoid">Geoid support</label>
				<input type="checkbox" name="geoid" id="geoid" value="geoid"<?php if (!isset($globalGeoid) || (isset($globalGeoid) && $globalGeoid)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Calculate the height of the geoid above WGS84 ellipsoid. Needed when source give altitute based on above mean sea level.</p>
			</p>
			<p>
				<label for="geoid_source">Geoid Source</label>
				<select name="geoid_source" id="geoid_source">
					<option value="egm96-15"<?php if (isset($globalGeoidSource) && $globalGeoidSource == 'egm96-15') print ' selected="selected"'; ?>>EGM96 15' (2.1MB)</option>
					<option value="egm96-5"<?php if (isset($globalGeoidSource) && $globalGeoidSource == 'egm96-5') print ' selected="selected"'; ?>>EGM96 5' (19MB)</option>
					<option value="egm2008-5"<?php if (isset($globalGeoidSource) && $globalGeoidSource == 'egm2008-5') print ' selected="selected"'; ?>>EGM2008 5' (19MB)</option>
					<option value="egm2008-2_5"<?php if (isset($globalGeoidSource) && $globalGeoidSource == 'egm2008-2_5') print ' selected="selected"'; ?>>EGM2008 2.5' (75MB)</option>
					<option value="egm2008-1"<?php if (isset($globalGeoidSource) && $globalGeoidSource == 'egm2008-1') print ' selected="selected"'; ?>>EGM2008 1' (470MB)</option>
				</select>
				<p class="help-block">The geoid is approximated by an "earth gravity model" (EGM).</p>
			</p>
			<br />
			<p>
				<label for="resetyearstats">Reset stats every years</label>
				<input type="checkbox" name="resetyearstats" id="resetyearsats" value="1"<?php if ((isset($globalDeleteLastYearStats) && $globalDeleteLastYearStats) || !isset($globalDeleteLastYearStats)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Reset count of aircraft types, airlines, registrations, callsigns, owners, pilots, departure and arrival airports</p>
			</p>
			<br />
			<p>
				<label for="archive">Archive all flights data</label>
				<input type="checkbox" name="archive" id="archive" value="archive"<?php if ((isset($globalArchive) && $globalArchive) || !isset($globalArchive)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">You will need to put <i>update_db.php</i> in cron. But all should be faster when archive is enabled.</p>
			</p>
			<p>
				<label for="archiveresults">Use archive to display results</label>
				<input type="checkbox" name="archiveresults" id="archiveresults" value="archiveresults"<?php if ((isset($globalArchiveResults) && $globalArchiveResults) || !isset($globalArchiveResults)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="archivemonths">Generate statistics, delete or put in archive flights older than xx months</label>
				<input type="number" name="archivemonths" id="archivemonths" value="<?php if (isset($globalArchiveMonths)) print $globalArchiveMonths; else echo '1'; ?>" />
				<p class="help-block">0 to disable, delete old flight if <i>Archive all flights data</i> is disabled</p>
			</p>
			<p>
				<label for="archiveyear">Generate statistics, delete or put in archive flights from previous year</label>
				<input type="checkbox" name="archiveyear" id="archiveyear" value="archiveyear"<?php if (isset($globalArchiveYear) && $globalArchiveYear) { ?> checked="checked"<?php } ?> />
				<p class="help-block">delete old flight if <i>Archive all flights data</i> is disabled</p>
			</p>
			<p>
				<label for="archivekeepmonths">Keep flights data for xx months in archive</label>
				<input type="number" name="archivekeepmonths" id="archivekeepmonths" value="<?php if (isset($globalArchiveKeepMonths)) print $globalArchiveKeepMonths; else echo '1'; ?>" />
				<p class="help-block">0 to disable</p>
			</p>
			<p>
				<label for="archivekeeptrackmonths">Keep flights track data for xx months in archive</label>
				<input type="number" name="archivekeeptrackmonths" id="archivekeeptrackmonths" value="<?php if (isset($globalArchiveKeepTrackMonths)) print $globalArchiveKeepTrackMonths; else echo '1'; ?>" />
				<p class="help-block">0 to disable, should be less or egal to <i>Keep flights data</i> value</p>
			</p>
			<br />
			<p>
				<label for="daemon">Use daemon-spotter.php as daemon</label>
				<input type="checkbox" name="daemon" id="daemon" value="daemon"<?php if ((isset($globalDaemon) && $globalDaemon) || !isset($globalDaemon)) { ?> checked="checked"<?php } ?> onClick="daemon_js()" />
				<p class="help-block">Uncheck if the script is running as cron job. You should always run it as daemon when it's possible.</p>
				<div id="cronends"> 
					<label for="cronend">Run script for xx seconds</label>
					<input type="number" name="cronend" id="cronend" value="<?php if (isset($globalCronEnd)) print $globalCronEnd; else print '0'; ?>" />
					<p class="help-block">Set to 0 to disable. Should be disabled if source is URL.</p>
				</div>
			</p>
			<br />
			<p>
				<label for="updatecheck">Disable update started check</label>
				<input type="checkbox" name="updatecheck" id="updatecheck" value="updatecheck"<?php if (isset($globalDisableUpdateCheck) && $globalDisableUpdateCheck) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Disable check if <i>scripts/update_db.php</i> is already running</p>
			</p>
			<br />
<!--
			<p>
				<label for="fork">Allow processes fork</label>
				<input type="checkbox" name="fork" id="fork" value="fork"<?php if ((isset($globalFork) && $globalFork) || !isset($globalFork)) { ?> checked="checked"<?php } ?> />
				<p class="help-block">Used for schedule</p>
			</p>
			<br />
-->
			<p>
				<label for="colormap">Show altitudes on map with several colors</label>
				<input type="checkbox" name="colormap" id="colormap" value="colormap"<?php if ((isset($globalMapAltitudeColor) && $globalMapAltitudeColor) || !isset($globalMapAltitudeColor)) { ?> checked="checked"<?php } ?> />
			</p>
<!--
			<p>
				<label for="mappopup">Show flights info in popup</label>
				<input type="checkbox" name="mappopup" id="mappopup" value="mappopup"<?php if ((isset($globalMapPopup) && $globalMapPopup)) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="airportpopup">Show airport info in popup</label>
				<input type="checkbox" name="airportpopup" id="airportpopup" value="airportpopup"<?php if ((isset($globalAirportPopup) && $globalAirportPopup)) { ?> checked="checked"<?php } ?> />
			</p>
-->
			<p>
				<label for="maptooltip">Always display callsign (only in 2D and can be slow)</label>
				<input type="checkbox" name="maptooltip" id="maptooltip" value="maptooltip"<?php if ((isset($globalMapPermanentTooltip) && $globalMapPermanentTooltip)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="maphistory">Always show path of flights (else only when flight is selected)</label>
				<input type="checkbox" name="maphistory" id="maphistory" value="maphistory"<?php if ((isset($globalMapHistory) && $globalMapHistory) || !isset($globalMapHistory)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="flightroute">Show route of flights when selected</label>
				<input type="checkbox" name="flightroute" id="flightroute" value="flightroute"<?php if (isset($globalMapRoute) && $globalMapRoute) { ?> checked="checked"<?php } ?> />
			</p>
			<p>
				<label for="flightremainingroute">Show remaining route of flights when selected</label>
				<input type="checkbox" name="flightremainingroute" id="flightremainingroute" value="flightremainingroute"<?php if ((isset($globalMapRemainingRoute) && $globalMapRemainingRoute) || !isset($globalMapRemainingRoute)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="allflights">Put all flights in DB even without coordinates</label>
				<input type="checkbox" name="allflights" id="allflights" value="allflights"<?php if ((isset($globalAllFlights) && $globalAllFlights) || !isset($globalAllFlights)) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
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
				<label for="minfetch">HTTP/file source fetch every xxx seconds</label>
				<input type="number" name="minfetch" id="minfetch" value="<?php if (isset($globalMinFetch)) echo $globalMinFetch; else echo '20'; ?>" />
			</p>
			<p>
				<label for="bbox">Only display flights that we can see on screen (bounding box)</label>
				<input type="checkbox" name="bbox" id="bbox" value="bbox"<?php if (isset($globalMapUseBbox) && $globalMapUseBbox) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="singlemodel">By default, only display selected model on 3D mode</label>
				<input type="checkbox" name="singlemodel" id="singlemodel" value="singlemodel"<?php if (isset($globalMap3DSelected) && $globalMap3DSelected) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="groundaltitude">Display and calculate ground altitude (can take lot of disk space)</label>
				<input type="checkbox" name="groundaltitude" id="groundaltitude" value="groundaltitude"<?php if (isset($globalGroundAltitude) && $globalGroundAltitude) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="closestmindist">Distance to airport set as arrival (in km)</label>
				<input type="number" name="closestmindist" id="closestmindist" value="<?php if (isset($globalClosestMinDist)) echo $globalClosestMinDist; else echo '50'; ?>" />
			</p>
			<br />
			<p>
				<label for="aircraftsize">Size of aircraft icon on map (default to 30px if zoom > 7 else 15px), empty to default</label>
				<input type="number" name="aircraftsize" id="aircraftsize" value="<?php if (isset($globalAircraftSize)) echo $globalAircraftSize;?>" />
			</p>
			<br />
			<p>
				<label for="noairlines">No airlines check and display (can be used for OGN or Virtual Flights without airlines)</label>
				<input type="checkbox" name="noairlines" id="noairlines" value="noairlines"<?php if (isset($globalNoAirlines) && $globalNoAirlines) { ?> checked="checked"<?php } ?> />
			</p>
			<br />
			<p>
				<label for="tsk">Enable support of XCSoar task (.tsk) files</label>
				<input type="checkbox" name="tsk" id="tsk" value="tsk"<?php if (isset($globalTSK) && $globalTSK) { ?> checked="checked"<?php } ?> />
				<p class="help-block">tsk file can be loaded using http://yourflightairmap/tsk=http://yourtskfile</p>
			</p>
			<?php 
			    if (extension_loaded('gd') && function_exists('gd_info')) {
			?>
			<br />
			<p>
				<label for="aircrafticoncolor">Color of aircraft icon on map</label>
				<input type="color" name="aircrafticoncolor" id="aircrafticoncolor" value="#<?php if (isset($globalAircraftIconColor)) echo $globalAircraftIconColor; else echo '1a3151'; ?>" />
			</p>
			<br />
			<p>
				<label for="marineiconcolor">Color of marine icon on map</label>
				<input type="color" name="marineiconcolor" id="marineiconcolor" value="#<?php if (isset($globalMarineIconColor)) echo $globalMarineIconColor; else echo '43d1d8'; ?>" />
			</p>
			<br />
			<p>
				<label for="trackericoncolor">Color of tracker icon on map</label>
				<input type="color" name="trackericoncolor" id="trackericoncolor" value="#<?php if (isset($globalTrackerIconColor)) echo $globalTrackerIconColor; else echo '1a3151'; ?>" />
			</p>
			<br />
			<p>
				<label for="satelliteiconcolor">Color of satellite icon on map</label>
				<input type="color" name="satelliteiconcolor" id="satelliteiconcolor" value="#<?php if (isset($globalSatelliteIconColor)) echo $globalSatelliteIconColor; else echo '1a3151'; ?>" />
			</p>
			<?php
				if (!is_writable('../cache')) {
			?>
			<br />
			<p>
				<b>The directory cache is not writable, aircraft icon will not be cached</b>
			</p>
			<?php
				}
			    } else {
			?>
			<br />
			<p>
				<b>PHP GD is not installed, you can't change color of aircraft icon on map</b>
			</p>
			<?php
			    }
			?>
			<br />
			<p>
				<label for="airportzoom">Zoom level minimum to see airports icons</label>
				<div class="range">
					<input type="range" name="airportzoom" id="airportzoom" value="<?php if (isset($globalAirportZoom)) echo $globalAirportZoom; else echo '7'; ?>" />
					<output id="range"><?php if (isset($globalAirportZoom)) echo $globalAirportZoom; else echo '7'; ?></output>
				</div>
			</p>
			<br />
			<p>
				<label for="customcss">Custom CSS web path</label>
				<input type="text" name="customcss" id="customcss" value="<?php if (isset($globalCustomCSS)) echo $globalCustomCSS; ?>" />
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
// '	
$settings = array();
$settings_comment = array();
$error = '';

if (isset($_POST['dbtype'])) {
	$installpass = filter_input(INPUT_POST,'installpass',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalInstallPassword' => $installpass));

	$dbtype = filter_input(INPUT_POST,'dbtype',FILTER_SANITIZE_STRING);
	$dbroot = filter_input(INPUT_POST,'dbroot',FILTER_SANITIZE_STRING);
	$dbrootpass = filter_input(INPUT_POST,'dbrootpass',FILTER_SANITIZE_STRING);
	$dbname = filter_input(INPUT_POST,'dbname',FILTER_SANITIZE_STRING);
	$dbuser = filter_input(INPUT_POST,'dbuser',FILTER_SANITIZE_STRING);
	$dbuserpass = filter_input(INPUT_POST,'dbuserpass',FILTER_SANITIZE_STRING);
	$dbhost = filter_input(INPUT_POST,'dbhost',FILTER_SANITIZE_STRING);
	$dbport = filter_input(INPUT_POST,'dbport',FILTER_SANITIZE_STRING);

	if ($dbtype == 'mysql' && !extension_loaded('pdo_mysql')) $error .= 'Mysql driver for PDO must be loaded';
	if ($dbtype == 'pgsql' && !extension_loaded('pdo_pgsql')) $error .= 'PosgreSQL driver for PDO must be loaded';
	
	$_SESSION['database_root'] = $dbroot;
	$_SESSION['database_rootpass'] = $dbrootpass;
	/*
	if ($error == '' && isset($_POST['createdb']) && $dbname != '' && $dbuser != '' && $dbuserpass != '') {
		if ($dbroot != '' && $dbrootpass != '') {
			$result = create_db::create_database($dbroot,$dbrootpass,$dbuser,$dbuserpass,$dbname,$dbtype,$dbhost);
			if ($result != '') $error .= $result;
		}
		if ($error == '') {
			//$error .= create_db::import_all_db('../db/');
			$settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBport' => $dbport,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
		}
	} else $settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBport' => $dbport,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
	*/
	
	$settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBport' => $dbport,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));

	$sitename = filter_input(INPUT_POST,'sitename',FILTER_SANITIZE_STRING);
	$siteurl = filter_input(INPUT_POST,'siteurl',FILTER_SANITIZE_STRING);
	$timezone = filter_input(INPUT_POST,'timezone',FILTER_SANITIZE_STRING);
	$language = filter_input(INPUT_POST,'language',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalName' => $sitename,'globalURL' => $siteurl, 'globalTimezone' => $timezone,'globalLanguage' => $language));

	$mapprovider = filter_input(INPUT_POST,'mapprovider',FILTER_SANITIZE_STRING);
	$mapboxid = filter_input(INPUT_POST,'mapboxid',FILTER_SANITIZE_STRING);
	$mapboxtoken = filter_input(INPUT_POST,'mapboxtoken',FILTER_SANITIZE_STRING);
	$googlekey = filter_input(INPUT_POST,'googlekey',FILTER_SANITIZE_STRING);
	$bingkey = filter_input(INPUT_POST,'bingkey',FILTER_SANITIZE_STRING);
	$openweathermapkey = filter_input(INPUT_POST,'openweathermapkey',FILTER_SANITIZE_STRING);
	$mapquestkey = filter_input(INPUT_POST,'mapquestkey',FILTER_SANITIZE_STRING);
	$hereappid = filter_input(INPUT_POST,'hereappid',FILTER_SANITIZE_STRING);
	$hereappcode = filter_input(INPUT_POST,'hereappcode',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMapProvider' => $mapprovider,'globalMapboxId' => $mapboxid,'globalMapboxToken' => $mapboxtoken,'globalGoogleAPIKey' => $googlekey,'globalBingMapKey' => $bingkey,'globalHereappID' => $hereappid,'globalHereappCode' => $hereappcode,'globalMapQuestKey' => $mapquestkey,'globalOpenWeatherMapKey' => $openweathermapkey));
	
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
	$updatecheck = filter_input(INPUT_POST,'updatecheck',FILTER_SANITIZE_STRING);
	if ($updatecheck == 'updatecheck') {
		$settings = array_merge($settings,array('globalDisableUpdateCheck' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalDisableUpdateCheck' => 'FALSE'));
	}

	$flightawareusername = filter_input(INPUT_POST,'flightawareusername',FILTER_SANITIZE_STRING);
	$flightawarepassword = filter_input(INPUT_POST,'flightawarepassword',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalFlightAwareUsername' => $flightawareusername,'globalFlightAwarePassword' => $flightawarepassword));
	
	$sailawayemail = filter_input(INPUT_POST,'sailawayemail',FILTER_SANITIZE_STRING);
	$sailawaypass = filter_input(INPUT_POST,'sailawaypassword',FILTER_SANITIZE_STRING);
	$sailawaykey = filter_input(INPUT_POST,'sailawaykey',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalSailaway' => array('email' => $sailawayemail,'password' => $sailawaypass,'key' => $sailawaykey)));
	
	$source_name = $_POST['source_name'];
	$source_latitude = $_POST['source_latitude'];
	$source_longitude = $_POST['source_longitude'];
	$source_altitude = $_POST['source_altitude'];
	$source_city = $_POST['source_city'];
	$source_country = $_POST['source_country'];
	$source_ref = $_POST['source_ref'];
	if (isset($source_id)) $source_id = $_POST['source_id'];
	else $source_id = array();
	
	$sources = array();
	foreach ($source_name as $keys => $name) {
	    if (isset($source_id[$keys])) $sources[] = array('name' => $name,'latitude' => $source_latitude[$keys],'longitude' => $source_longitude[$keys],'altitude' => $source_altitude[$keys],'city' => $source_city[$keys],'country' => $source_country[$keys],'id' => $source_id[$keys],'source' => $source_ref[$keys]);
	    else $sources[] = array('name' => $name,'latitude' => $source_latitude[$keys],'longitude' => $source_longitude[$keys],'altitude' => $source_altitude[$keys],'city' => $source_city[$keys],'country' => $source_country[$keys],'source' => $source_ref[$keys]);
	}
	if (count($sources) > 0) $_SESSION['sources'] = $sources;

	$newsurl = $_POST['newsurl'];
	$newslng = $_POST['newslang'];
	$newstype = $_POST['newstype'];
	
	$newsfeeds = array();
	foreach($newsurl as $newskey => $url) {
	    if ($url != '') {
		$type = $newstype[$newskey];
		$lng = $newslng[$newskey];
		if (isset($newsfeeds[$type][$lng])) {
		    $newsfeeds[$type][$lng] = array_merge($newsfeeds[$type][$lng],array($url));
		} else $newsfeeds[$type][$lng] = array($url);
	    }
	}
	$settings = array_merge($settings,array('globalNewsFeeds' => $newsfeeds));

	//$sbshost = filter_input(INPUT_POST,'sbshost',FILTER_SANITIZE_STRING);
	//$sbsport = filter_input(INPUT_POST,'sbsport',FILTER_SANITIZE_NUMBER_INT);
	//$sbsurl = filter_input(INPUT_POST,'sbsurl',FILTER_SANITIZE_URL);
	/*
	$sbshost = $_POST['sbshost'];
	$sbsport = $_POST['sbsport'];
	$sbsurl = $_POST['sbsurl'];
	*/

	$globalvatsim = filter_input(INPUT_POST,'globalvatsim',FILTER_SANITIZE_STRING);
	$globalva = filter_input(INPUT_POST,'globalva',FILTER_SANITIZE_STRING);
	$globalvm = filter_input(INPUT_POST,'globalvm',FILTER_SANITIZE_STRING);
	$globalivao = filter_input(INPUT_POST,'globalivao',FILTER_SANITIZE_STRING);
	$globalphpvms = filter_input(INPUT_POST,'globalphpvms',FILTER_SANITIZE_STRING);
	$globalvam = filter_input(INPUT_POST,'globalvam',FILTER_SANITIZE_STRING);
	$globalsbs = filter_input(INPUT_POST,'globalsbs',FILTER_SANITIZE_STRING);
	$globalaprs = filter_input(INPUT_POST,'globalaprs',FILTER_SANITIZE_STRING);
	$datasource = filter_input(INPUT_POST,'datasource',FILTER_SANITIZE_STRING);

	$globalaircraft = filter_input(INPUT_POST,'globalaircraft',FILTER_SANITIZE_STRING);
	if ($globalaircraft == 'aircraft') $settings = array_merge($settings,array('globalAircraft' => 'TRUE'));
	else $settings = array_merge($settings,array('globalAircraft' => 'FALSE'));
	$globaltracker = filter_input(INPUT_POST,'globaltracker',FILTER_SANITIZE_STRING);
	if ($globaltracker == 'tracker') $settings = array_merge($settings,array('globalTracker' => 'TRUE'));
	else $settings = array_merge($settings,array('globalTracker' => 'FALSE'));
	$globalmarine = filter_input(INPUT_POST,'globalmarine',FILTER_SANITIZE_STRING);
	if ($globalmarine == 'marine') $settings = array_merge($settings,array('globalMarine' => 'TRUE'));
	else $settings = array_merge($settings,array('globalMarine' => 'FALSE'));
	$globalsatellite = filter_input(INPUT_POST,'globalsatellite',FILTER_SANITIZE_STRING);
	if ($globalsatellite == 'satellite') $settings = array_merge($settings,array('globalSatellite' => 'TRUE'));
	else $settings = array_merge($settings,array('globalSatellite' => 'FALSE'));

/*	
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
*/
	$settings_comment = array_merge($settings_comment,array('globalSBS1Hosts'));
	$host = $_POST['host'];
	$port = $_POST['port'];
	$name = $_POST['name'];
	$format = $_POST['format'];
	$timezones = $_POST['timezones'];
	if (isset($_POST['sourcestats'])) $sourcestats = $_POST['sourcestats'];
	else $sourcestats = array();
	if (isset($_POST['noarchive'])) $noarchive = $_POST['noarchive'];
	else $noarchive = array();
	$gSources = array();
	$forcepilots = false;
	foreach ($host as $key => $h) {
		if (isset($sourcestats[$key]) && $sourcestats[$key] == 1) $cov = 'TRUE';
		else $cov = 'FALSE';
		if (isset($noarchive[$key]) && $noarchive[$key] == 1) $arch = 'TRUE';
		else $arch = 'FALSE';
		if (strpos($format[$key],'_callback')) {
			$gSources[] = array('host' => $h, 'pass' => $port[$key],'name' => $name[$key],'format' => $format[$key],'sourcestats' => $cov,'noarchive' => $arch,'timezone' => $timezones[$key],'callback' => 'TRUE');
		} elseif ($format[$key] != 'auto' || ($h != '' || $name[$key] != '')) {
			$gSources[] = array('host' => $h, 'port' => $port[$key],'name' => $name[$key],'format' => $format[$key],'sourcestats' => $cov,'noarchive' => $arch,'timezone' => $timezones[$key],'callback' => 'FALSE');
		}
		if ($format[$key] == 'airwhere') $forcepilots = true;
	}
	$settings = array_merge($settings,array('globalSources' => $gSources));

/*
	$sbstimeout = filter_input(INPUT_POST,'sbstimeout',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalSourcesTimeOut' => $sbstimeout));
*/
	$acarshost = filter_input(INPUT_POST,'acarshost',FILTER_SANITIZE_STRING);
	$acarsport = filter_input(INPUT_POST,'acarsport',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalACARSHost' => $acarshost,'globalACARSPort' => $acarsport));

	$bitly = filter_input(INPUT_POST,'bitly',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBitlyAccessToken' => $bitly));

	$podaccuser = filter_input(INPUT_POST,'podaccuser',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalPODACCuser' => $podaccuser));
	$podaccpass = filter_input(INPUT_POST,'podaccpass',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalPODACCpass' => $podaccpass));

	$customcss = filter_input(INPUT_POST,'customcss',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalCustomCSS' => $customcss));

	$map3dtile = filter_input(INPUT_POST,'map3dtileset',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMap3DTiles' => $map3dtile));

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
	$minfetch = filter_input(INPUT_POST,'minfetch',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalMinFetch' => $minfetch));
	$closestmindist = filter_input(INPUT_POST,'closestmindist',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalClosestMinDist' => $closestmindist));

	$aircraftsize = filter_input(INPUT_POST,'aircraftsize',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalAircraftSize' => $aircraftsize));

	$archivemonths = filter_input(INPUT_POST,'archivemonths',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalArchiveMonths' => $archivemonths));
	
	$archiveyear = filter_input(INPUT_POST,'archiveyear',FILTER_SANITIZE_STRING);
	if ($archiveyear == "archiveyear") {
		$settings = array_merge($settings,array('globalArchiveYear' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalArchiveYear' => 'FALSE'));
	}
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

	$resetyearstats = filter_input(INPUT_POST,'resetyearstats',FILTER_SANITIZE_STRING);
	if ($resetyearstats == 'resetyearstats') {
		$settings = array_merge($settings,array('globalDeleteLastYearStats' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalDeleteLastYearStats' => 'FALSE'));
	}

	$archive = filter_input(INPUT_POST,'archive',FILTER_SANITIZE_STRING);
	if ($archive == 'archive') {
		$settings = array_merge($settings,array('globalArchive' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalArchive' => 'FALSE'));
	}
	$archiveresults = filter_input(INPUT_POST,'archiveresults',FILTER_SANITIZE_STRING);
	if ($archiveresults == 'archiveresults') {
		$settings = array_merge($settings,array('globalArchiveResults' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalArchiveResults' => 'FALSE'));
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
	if ($globalaprs == 'aprs') $settings = array_merge($settings,array('globalAPRS' => 'TRUE'));
	else $settings = array_merge($settings,array('globalAPRS' => 'FALSE'));
	$va = false;
	if ($globalivao == 'ivao') {
		$settings = array_merge($settings,array('globalIVAO' => 'TRUE'));
		$va = true;
	} else $settings = array_merge($settings,array('globalIVAO' => 'FALSE'));
	if ($globalvatsim == 'vatsim') {
		$settings = array_merge($settings,array('globalVATSIM' => 'TRUE'));
		$va = true;
	} else $settings = array_merge($settings,array('globalVATSIM' => 'FALSE'));
	if ($globalphpvms == 'phpvms') {
		$settings = array_merge($settings,array('globalphpVMS' => 'TRUE'));
		$va = true;
	} else $settings = array_merge($settings,array('globalphpVMS' => 'FALSE'));
	if ($globalvam == 'vam') {
		$settings = array_merge($settings,array('globalVAM' => 'TRUE'));
		$va = true;
	} else $settings = array_merge($settings,array('globalVAM' => 'FALSE'));
	if ($va) {
		$settings = array_merge($settings,array('globalSchedulesFetch' => 'FALSE','globalTranslationFetch' => 'FALSE'));
	} else $settings = array_merge($settings,array('globalSchedulesFetch' => 'TRUE','globalTranslationFetch' => 'TRUE'));
	if ($globalva == 'va' || $va) {
		$settings = array_merge($settings,array('globalVA' => 'TRUE'));
		$settings = array_merge($settings,array('globalUsePilot' => 'TRUE','globalUseOwner' => 'FALSE'));
	} else {
		$settings = array_merge($settings,array('globalVA' => 'FALSE'));
		if ($forcepilots) $settings = array_merge($settings,array('globalUsePilot' => 'TRUE','globalUseOwner' => 'FALSE'));
		else $settings = array_merge($settings,array('globalUsePilot' => 'FALSE','globalUseOwner' => 'TRUE'));
	}
	if ($globalvm == 'vm') {
		$settings = array_merge($settings,array('globalVM' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalVM' => 'FALSE'));
	}
	
	$mapoffline = filter_input(INPUT_POST,'mapoffline',FILTER_SANITIZE_STRING);
	if ($mapoffline == 'mapoffline') {
		$settings = array_merge($settings,array('globalMapOffline' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapOffline' => 'FALSE'));
	}
	$globaloffline = filter_input(INPUT_POST,'globaloffline',FILTER_SANITIZE_STRING);
	if ($globaloffline == 'globaloffline') {
		$settings = array_merge($settings,array('globalOffline' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalOffline' => 'FALSE'));
	}

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
	$map3d = filter_input(INPUT_POST,'map3d',FILTER_SANITIZE_STRING);
	if ($map3d == 'map3d') {
		$settings = array_merge($settings,array('globalMap3D' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3D' => 'FALSE'));
	}
	$crash = filter_input(INPUT_POST,'crash',FILTER_SANITIZE_STRING);
	if ($crash == 'crash') {
		$settings = array_merge($settings,array('globalAccidents' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalAccidents' => 'FALSE'));
	}
	$fires = filter_input(INPUT_POST,'fires',FILTER_SANITIZE_STRING);
	if ($fires == 'fires') {
		$settings = array_merge($settings,array('globalMapFires' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapFires' => 'FALSE'));
	}
	$firessupport = filter_input(INPUT_POST,'firessupport',FILTER_SANITIZE_STRING);
	if ($firessupport == 'firessupport') {
		$settings = array_merge($settings,array('globalFires' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalFires' => 'FALSE'));
	}
	$mapsatellites = filter_input(INPUT_POST,'mapsatellites',FILTER_SANITIZE_STRING);
	if ($mapsatellites == 'mapsatellites') {
		$settings = array_merge($settings,array('globalMapSatellites' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapSatellites' => 'FALSE'));
	}
	$map3ddefault = filter_input(INPUT_POST,'map3ddefault',FILTER_SANITIZE_STRING);
	if ($map3ddefault == 'map3ddefault') {
		$settings = array_merge($settings,array('globalMap3Ddefault' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3Ddefault' => 'FALSE'));
	}
	$one3dmodel = filter_input(INPUT_POST,'one3dmodel',FILTER_SANITIZE_STRING);
	if ($one3dmodel == 'one3dmodel') {
		$settings = array_merge($settings,array('globalMap3DOneModel' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3DOneModel' => 'FALSE'));
	}
	$map3dliveries = filter_input(INPUT_POST,'map3dliveries',FILTER_SANITIZE_STRING);
	if ($map3dliveries == 'map3dliveries') {
		$settings = array_merge($settings,array('globalMap3DLiveries' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3DLiveries' => 'FALSE'));
	}
	$map3dshadows = filter_input(INPUT_POST,'map3dshadows',FILTER_SANITIZE_STRING);
	if ($map3dshadows == 'map3dshadows') {
		$settings = array_merge($settings,array('globalMap3DShadows' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3DShadows' => 'FALSE'));
	}
	$translate = filter_input(INPUT_POST,'translate',FILTER_SANITIZE_STRING);
	if ($translate == 'translate') {
		$settings = array_merge($settings,array('globalTranslate' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalTranslate' => 'FALSE'));
	}
	$realairlines = filter_input(INPUT_POST,'realairlines',FILTER_SANITIZE_STRING);
	if ($realairlines == 'realairlines') {
		$settings = array_merge($settings,array('globalUseRealAirlines' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalUseRealAirlines' => 'FALSE'));
	}
	$estimation = filter_input(INPUT_POST,'estimation',FILTER_SANITIZE_STRING);
	if ($estimation == 'estimation') {
		$settings = array_merge($settings,array('globalMapEstimation' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapEstimation' => 'FALSE'));
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
	if (isset($_POST['marineiconcolor'])) {
		$marineiconcolor = filter_input(INPUT_POST,'marineiconcolor',FILTER_SANITIZE_STRING);
		$settings = array_merge($settings,array('globalMarineIconColor' => substr($marineiconcolor,1)));
	}
	if (isset($_POST['trackericoncolor'])) {
		$trackericoncolor = filter_input(INPUT_POST,'trackericoncolor',FILTER_SANITIZE_STRING);
		$settings = array_merge($settings,array('globalTrackerIconColor' => substr($trackericoncolor,1)));
	}
	if (isset($_POST['satelliteiconcolor'])) {
		$satelliteiconcolor = filter_input(INPUT_POST,'satelliteiconcolor',FILTER_SANITIZE_STRING);
		$settings = array_merge($settings,array('globalSatelliteIconColor' => substr($satelliteiconcolor,1)));
	}

	$corsproxy = filter_input(INPUT_POST,'corsproxy',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalCORSproxy' => $corsproxy));

	$airportzoom = filter_input(INPUT_POST,'airportzoom',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalAirportZoom' => $airportzoom));

	$unitdistance = filter_input(INPUT_POST,'unitdistance',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalUnitDistance' => $unitdistance));
	$unitaltitude = filter_input(INPUT_POST,'unitaltitude',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalUnitAltitude' => $unitaltitude));
	$unitspeed = filter_input(INPUT_POST,'unitspeed',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalUnitSpeed' => $unitspeed));

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
	$maptooltip = filter_input(INPUT_POST,'maptooltip',FILTER_SANITIZE_STRING);
	if ($maptooltip == 'maptooltip') {
		$settings = array_merge($settings,array('globalMapTooltip' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapTooltip' => 'FALSE'));
	}
	$flightroute = filter_input(INPUT_POST,'flightroute',FILTER_SANITIZE_STRING);
	if ($flightroute == 'flightroute') {
		$settings = array_merge($settings,array('globalMapRoute' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapRoute' => 'FALSE'));
	}
	$flightremainingroute = filter_input(INPUT_POST,'flightremainingroute',FILTER_SANITIZE_STRING);
	if ($flightremainingroute == 'flightremainingroute') {
		$settings = array_merge($settings,array('globalMapRemainingRoute' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapRemainingRoute' => 'FALSE'));
	}
	$allflights = filter_input(INPUT_POST,'allflights',FILTER_SANITIZE_STRING);
	if ($allflights == 'allflights') {
		$settings = array_merge($settings,array('globalAllFlights' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalAllFlights' => 'FALSE'));
	}
	$bbox = filter_input(INPUT_POST,'bbox',FILTER_SANITIZE_STRING);
	if ($bbox == 'bbox') {
		$settings = array_merge($settings,array('globalMapUseBbox' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapUseBbox' => 'FALSE'));
	}
	$singlemodel = filter_input(INPUT_POST,'singlemodel',FILTER_SANITIZE_STRING);
	if ($singlemodel == 'singlemodel') {
		$settings = array_merge($settings,array('globalMap3DSelected' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMap3DSelected' => 'FALSE'));
	}
	$groundaltitude = filter_input(INPUT_POST,'groundaltitude',FILTER_SANITIZE_STRING);
	if ($groundaltitude == 'groundaltitude') {
		$settings = array_merge($settings,array('globalGroundAltitude' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalGroundAltitude' => 'FALSE'));
	}
	$waypoints = filter_input(INPUT_POST,'waypoints',FILTER_SANITIZE_STRING);
	if ($waypoints == 'waypoints') {
		$settings = array_merge($settings,array('globalWaypoints' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalWaypoints' => 'FALSE'));
	}
	$geoid = filter_input(INPUT_POST,'geoid',FILTER_SANITIZE_STRING);
	if ($geoid == 'geoid') {
		$settings = array_merge($settings,array('globalGeoid' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalGeoid' => 'FALSE'));
	}
	$geoid_source = filter_input(INPUT_POST,'geoid_source',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalGeoidSource' => $geoid_source));

	$noairlines = filter_input(INPUT_POST,'noairlines',FILTER_SANITIZE_STRING);
	if ($noairlines == 'noairlines') {
		$settings = array_merge($settings,array('globalNoAirlines' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalNoAirlines' => 'FALSE'));
	}

	$tsk = filter_input(INPUT_POST,'tsk',FILTER_SANITIZE_STRING);
	if ($tsk == 'tsk') {
		$settings = array_merge($settings,array('globalTSK' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalTSK' => 'FALSE'));
	}
	$mapmatching = filter_input(INPUT_POST,'mapmatching',FILTER_SANITIZE_STRING);
	if ($mapmatching == 'mapmatching') {
		$settings = array_merge($settings,array('globalMapMatching' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalMapMatching' => 'FALSE'));
	}
	$mapmatchingsource = filter_input(INPUT_POST,'mapmatchingsource',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMapMatchingSource' => $mapmatchingsource));
	$graphhopper = filter_input(INPUT_POST,'graphhopper',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalGraphHopperKey' => $graphhopper));

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
	if ($error == '') settings::comment_settings($settings_comment);
	if ($error != '') {
		print '<div class="info column">'.$error.'</div>';
		require('../footer.php');
		exit;
	} else {
		if (isset($_POST['waypoints']) && $_POST['waypoints'] == 'waypoints') $_SESSION['waypoints'] = 1;
		if (isset($_POST['owner']) && $_POST['owner'] == 'owner') $_SESSION['owner'] = 1;
		if (isset($_POST['createdb'])) {
			$_SESSION['install'] = 'database_create';
		} else {
			require_once(dirname(__FILE__).'/../require/class.Connection.php');
			$Connection = new Connection();
			if ($Connection->latest() && isset($_POST['waypoints']) && $_POST['waypoints'] == 'waypoints') {
				if ($Connection->tableExists('airspace') === false) {
					$_SESSION['install'] = 'waypoints';
				} else {
					$_SESSION['install'] = 'database_import';
				}
			} else {
				$_SESSION['install'] = 'database_import';
			}
		}
		//require('../footer.php');
		print '<div class="info column"><ul>';
		 /*
		if (isset($_POST['createdb'])) {
			$_SESSION['done'] = array('Create database','Write configuration');
			print '<li>Create database....<strong>SUCCESS</strong></li>';
		} else $_SESSION['done'] = array('Write configuration');
		*/
		$_SESSION['done'] = array('Write configuration');
		$_SESSION['errorlst'] = array();
		print '<li>Write configuration....<img src="../images/loading.gif" /></li></ul></div>';
		print "<script>console.log('Configuration writed...');setTimeout(window.location = 'index.php?".rand()."&next=".$_SESSION['install']."',10000);</script>";
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] != 'finish') {
	print '<div class="info column">';
	print '<ul><div id="step">';
	$pop = false;
	$popi = false;
	$popw = false;
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	    if ($done == 'Create database') $pop = true;
	    if ($_SESSION['install'] == 'database_create') $pop = true;
	    if ($_SESSION['install'] == 'database_import') $popi = true;
	    if ($_SESSION['install'] == 'waypoints') $popw = true;
	}
	if ($pop) {
	    sleep(5);
	    print '<li>Create database....<img src="../images/loading.gif" /></li>';
	} else if ($popi) {
	    sleep(5);
	    print '<li>Create and import tables....<img src="../images/loading.gif" /></li>';
	} else if ($popw) {
	    sleep(5);
	    print '<li>Populate waypoints database....<img src="../images/loading.gif" /></li>';
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
			    result['errorlst'].forEach(function(done) {
				$('#step').append('<li>'+ done +'....<strong>FAILED</strong></li>');
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
				console.log(XMLHttpRequest);
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
	unset($_SESSION['identified']);
	unset($_COOKIE['install']);
	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Reloading page to check all is now ok....<strong>SUCCESS</strong></li>';
	print '</ul></div>';
	print '<br /><p>All is now installed ! Thanks</p>';
	if ($globalSBS1) {
		print '<p>You need to run <b>scripts/daemon-spotter.php</b> as a daemon. You can use init script in the install/init directory.</p>';
	} else {
		print '<p>You need to run <b>scripts/daemon-spotter.php</b>. You can use init script in the install/init directory to run it as daemon.</p>';
	}
	if ($globalACARS) {
		print '<p>You need to run <b>scripts/daemon-acars.php</b> as a daemon. You can use init script in the install/init directory.</p>';
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
	if (isset($globalMap3D) && $globalMap3D) {
		print '<p>You need to run <b>scripts/update_db.php</b> first time manually, this will update all and download 3D models.</p>';
	}
	if (isset($globalVAM) && $globalVAM) {
		print '<p>You need to copy <b>install/VAM/VAM-json.php</b> to your Virtual Airline Manager directory and use this URL as source.</p>';
	}
	if (isset($globalGeoid) && $globalGeoid) {
		print '<p>You need to run <b>scripts/update_db.php</b> to update Geoid file if needed (or first time).</p>';
	}
	print '<p>If you want to keep external data updated, you have to add <b>scripts/update_db.php</b> in cron (every hour or 30 minutes if computer is fast enough).</p>';
	print '<p>If <b>scripts/daemon-spotter.php</b> is already running, you have to restart it.</p>';
	print '</div>';
} else {
	unset($_SESSION['install']);
	unset($_SESSION['identified']);
//	header("Location: index.php");
}
require('../footer.php');
?>