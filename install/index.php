<?php
//    require_once('../require/class.Connection.php');
    require_once('class.create_db.php');
    require_once('class.settings.php');
    $title="Install";
    require('header.php');
    require('../require/settings.php');
//print_r( get_loaded_extensions());
    if ($globalInstalled && !isset($_POST['populate']) && !isset($_POST['waypoints']) && !isset($_POST['airspace'])) exit;

    $writable = false;
    if (!is_writable('../require/settings.php')) {
    ?>
	<div class="info column">
	    <p><strong>Le fichier <i>require/settings</i> doit être accessible en écriture pour la configuration.</strong></p>
	</div>
      <?php
    } else $writable = true;
    
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
    
    if (count($error) > 0) {
    ?>
	<div class="info column">
	    <ul>
	    <?php
	      foreach ($error as $err) {
	        ?>
	         <li><?php print $err; ?></li>
	      <?php
	      }
	    ?>
	    </ul>
	    You <strong>must</strong> add these modules.
	</div>
    <?php
    }
    
    if (!isset($_POST['dbtype']) && $writable && !isset($_POST['populate']) && !isset($_POST['waypoints']) && !isset($_POST['airspace']) && (count($error) == 0)) {
  
?>
    <div class="info column">
	<form method="post">
	    <fieldset>
        	<legend>Create database</legend>
        	<p>
        	    <label for="dbtype">Database type</label>
        	    <select name="dbtype" id="dbtype">
        		<option value="mysql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'mysql') { ?>selected="selected" <?php } ?>>MySQL</option>
        		<option value="pgsql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'pgsql') { ?>selected="selected" <?php } ?>>PostgreSQL (not tested)</option>
        	    </select>
        	</p>
        	<p>
        	    <label for="dbroot">Database admin user</label>
        	    <input type="text" name="dbroot" id="dbroot" />
        	</p>
        	<p>
        	    <label for="dbrootpass">Database admin password</label>
        	    <input type="password" name="dbrootpass" id="dbrootpass" />
        	</p>
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
        	    Can be null.
        	</p>
		<p>
		    <label for="timezone">Timezone</label>
		    <input type="text" name="timezone" id="timezone" value="<?php if (isset($globalTimezone)) print $globalTimezone; ?>" />
		    ex : UTC, Europe/Paris,...
		</p>
		<p>
		    <label for="language">Language</label>
		    <input type="text" name="language" id="language" value="<?php if (isset($globalLanguage)) print $globalLanguage; ?>" />
		    Used only when link to wikipedia for now. Can be EN,DE,FR,...
		</p>
            </fieldset>
            <fieldset>
        	<legend>Map provider</legend>
        	<p>
        	    <label for="mapprovider">map Provider</label>
        	    <select name="mapprovider" id="mapprovider">
        		<option value="OpenStreetMap" <?php if (isset($globalMapProvider) && $globalMapProvider == 'OpenStreetMap') { ?>selected="selected" <?php } ?>>OpenStreetMap</option>
        		<option value="Mapbox" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Mapbox') { ?>selected="selected" <?php } ?>>Mapbox</option>
        		<option value="MapQuest-OSM" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-OSM') { ?>selected="selected" <?php } ?>>MapQuest-OSM</option>
        		<option value="MapQuest-Aerial" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-Aerial') { ?>selected="selected" <?php } ?>>MapQuest-Aerial</option>
        	    </select>
        	</p>
        	<p>
        	    <label for="mapboxid">Mapbox id</label>
        	    <input type="text" name="mapboxid" id="mapboxid" value="<?php if (isset($globalMapboxId)) print $globalMapboxId; ?>" />
        	</p>
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
        	    <label for="squawk_country">Country for squawk usage</label>
        	    <input type="text" name="squawk_country" id="squawk_country" value="<?php if (isset($globalSquawkCountry)) print $globalSquawkCountry; ?>" />
        	    UK, FR or let it blank for now
        	</p>
            </fieldset>
	    <fieldset>
		<legend>Data source</legend>
		<p>
		    <label>Choose data source</label>
		    <input type="radio" name="datasource" id="flightaware" value="flightaware" <?php if (isset($globalFlightAware) && $globalFlightAware) { ?>checked="checked" <?php } ?>/>
		    <label for="flightaware">FlightAware</label>
		    <input type="radio" name="datasource" id="sbs" value="sbs" <?php if (isset($globalSBS1) && $globalSBS1) { ?>checked="checked" <?php } ?> />
		    <label for="sbs">ADS-B, SBS-1 format (dump1090 or SBS-1 compatible format)</label>
		</p>
		<p>
		    <label for="flightawareusername">FlightAware username</label>
		    <input type="text" name="flightawareusername" id="flightawareusername" value="<?php if (isset($globalFlightAwareUsername)) print $globalFlightAwareUsername; ?>" />
		</p>
		<p>
		    <label for="flightawarepassword">FlightAware password/API key</label>
		    <input type="text" name="flightawarepassword" id="flightawarepassword" value="<?php if (isset($globalFlightAwarePassword)) print $globalFlightAwarePassword; ?>" />
		</p>
		<p>
		    <label for="sbshost">SBS-1 host</label>
		    <input type="text" name="sbshost" id="sbshost" value="<?php if (isset($globalSBS1Host)) print $globalSBS1Host; ?>" />
		</p>
		<p>
		    <label for="sbsport">SBS-1 port</label>
		    <input type="text" name="sbsport" id="sbsport" value="<?php if (isset($globalSBS1Port)) print $globalSBS1Port; ?>" />
		</p>
		<p>
		    <label for="sbstimeout">SBS-1 timeout</label>
		    <input type="text" name="sbstimeout" id="sbstimeout" value="<?php if (isset($globalSBS1TimeOut)) print $globalSBS1TimeOut; ?>" />
		</p>
	    </fieldset>
	    <fieldset>
		<legend>Optional configuration</legend>
		<p>
		    <label for="bitly">Bit.ly access token api (used in search page)</label>
		    <input type="text" name="bitly" id="bitly" value="<?php if (isset($globalBitlyAccessToken)) print $globalBitlyAccessToken; ?>" />
		</p>
		<p>
		    <label for="britishairways">British Airways API Key</label>
		    <input type="text" name="britishairways" id="britishairways" value="<?php if (isset($globalBritishAirwaysKey)) print $globalBritishAirwaysKey; ?>" />
		</p>
	    </fieldset>
            <input type="submit" name="submit" value="Create database & write setup" />
          </form>
<?php
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
        
        if ($error != '') {
    	    ?>
    	    <div class="info column">
    		<?php print $error; ?>
    	    </div>
    	    <?php
	    require('../footer.php');
            exit;
        }
        
        if ($dbname != '' && $dbuser != '' && $dbuserpass != '') {
    	    if ($dbroot != '' && $dbrootpass != '') {
    		$result = create_db::create_database($dbroot,$dbrootpass,$dbuser,$dbuserpass,$dbname,$dbtype,$dbhost);
    		if ($result != true) $error .= $result;
    	    }
    	    if ($error == '') {
    		$error .= create_db::import_all_db('../db/');
    		$settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
    	    }
        }
	if ($error != '') {
	?>
    	    <div class="info column">
    		<?php print $error; ?>
    	    </div>
    	    <?php
	    require('../footer.php');
            exit;
	}
	
	$sitename = filter_input(INPUT_POST,'sitename',FILTER_SANITIZE_STRING);
	$siteurl = filter_input(INPUT_POST,'siteurl',FILTER_SANITIZE_STRING);
	$timezone = filter_input(INPUT_POST,'timezone',FILTER_SANITIZE_STRING);
	$language = filter_input(INPUT_POST,'language',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalName' => $sitename,'globalURL' => $siteurl, 'globalTimezone' => $timezone,'globalLanguage' => $language));
	
	$mapprovider = filter_input(INPUT_POST,'mapprovider',FILTER_SANITIZE_STRING);
	$mapboxid = filter_input(INPUT_POST,'mapboxid',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMapProvider' => $mapprovider,'globalMapboxId' => $mapboxid));
	
	$latitudemax = filter_input(INPUT_POST,'latitudemax',FILTER_SANITIZE_STRING);
	$latitudemin = filter_input(INPUT_POST,'latitudemin',FILTER_SANITIZE_STRING);
	$longitudemax = filter_input(INPUT_POST,'longitudemax',FILTER_SANITIZE_STRING);
	$longitudemin = filter_input(INPUT_POST,'longitudemin',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalLatitudeMax' => $latitudemax,'globalLatitudeMin' => $latitudemin,'globalLongitudeMax' => $longitudemax,'globalLongitudeMin' => $longitudemin));

	$squawk_country = filter_input(INPUT_POST,'squawk_country',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalSquawkCountry' => $squawk_country));

	$latitudecenter = filter_input(INPUT_POST,'latitudecenter',FILTER_SANITIZE_STRING);
	$longitudecenter = filter_input(INPUT_POST,'longitudecenter',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalCenterLatitude' => $latitudecenter,'globalCenterLongitude' => $longitudecenter));
	
	$datasource = filter_input(INPUT_POST,'datasource',FILTER_SANITIZE_STRING);
	if ($datasource == 'flightaware') {
	    $settings = array_merge($settings,array('globalFlightAware' => 'TRUE','globalSBS1' => 'FALSE'));
	} else {
	    $settings = array_merge($settings,array('globalFlightAware' => 'FALSE','globalSBS1' => 'TRUE'));
	}
	
	$flightawareusername = filter_input(INPUT_POST,'flightawareusername',FILTER_SANITIZE_STRING);
	$flightawarepassword = filter_input(INPUT_POST,'flightawarepassword',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalFlightAwareUsername' => $flightawareusername,'globalFlightAwarePassword' => $flightawarepassword));
	
	$sbshost = filter_input(INPUT_POST,'sbshost',FILTER_SANITIZE_STRING);
	$sbsport = filter_input(INPUT_POST,'sbsport',FILTER_SANITIZE_NUMBER_INT);
	$sbstimeout = filter_input(INPUT_POST,'sbstimeout',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalSBS1Host' => $sbshost,'globalSBS1Port' => $sbsport,'globalSBS1TimeOut' => $sbstimeout));
	
	$bitly = filter_input(INPUT_POST,'bitly',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBitlyAccessToken' => $bitly));
	
	$britishairways = filter_input(INPUT_POST,'britishairways',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBritishAirwaysKey' => $britishairways));
	
	$settings = array_merge($settings,array('globalInstalled' => 'TRUE'));
	if ($error == '') settings::modify_settings($settings);
	
	if ($error != '') {
?>
    <div class="info column">
	<span class="error"><strong>Error</strong><?php print $error; ?></span>
    </div>

<?php
	} else {
	    if ($datasource == 'sbs') {
?>
    <div class="info column">
	<?php
	    if (!is_writable('tmp/test.txt')) {
	?>    
		<p><strong>The directory <i>install/tmp</i> must be writable.</strong></p>
	<?php
	    }
	?>
	<form method="post">
	    <p>You use SBS as datasource, you need to populate the database with data from external sources.</p>
	    <input type="submit" name="populate" value="Populate database" />
	</form>
	<p>
	    You can also use <i>install/update_db.sh</i>.
	</p>
    </div>
<?php
	    } else {
?>
    <div class="info column">
	<p>All is now installed ! Thanks</p>
	<p>You need to put cron.php in your crontab to run it every minutes.</p>
	<p>
	    <form method="post">
		<label for="waypoints">You can populate waypoints with data for your country if you want to see them on map</label>
		<input type="submit" id="waypoints" name="waypoints" value="populate waypoints database" />
	    </form>
	</p>
    </div>
<?php
	    }
	    
	}

    }
    if (isset($_POST['waypoints'])) {
//        require_once('class.update_db.php');
        include_once('class.update_db.php');
        update_db::update_waypoints();
?>
    <div class="info column">
	<p>waypoints database populated.</p>
	<p>
	    <form method="post">
		<label for="airspace">You can populate airspace if you want to see them on map (need at least MySQL 5.6 or MariaDB 5.3+)</label>
		<input type="submit" id="airspace" name="airspace" value="populate airspace database" />
	    </form>
	</p>
    </div>
<?php
    }
    if (isset($_POST['airspace'])) {
        include_once('class.update_db.php');
        update_db::update_airspace();
?>
    <div class="info column">
	<p>airspace database populated.</p>
    </div>
<?php
    }
    if (isset($_POST['populate'])) {
//        require_once('class.update_db.php');
        include_once('class.update_db.php');
        update_db::update_all();
?>
    <div class="info column">
	<p>All is now installed ! Thanks</p>
	<p>You need to run cron-sbs.php as a daemon. You can use init script in the install/init directory.</p>
	<p>
	    <form method="post">
		<label for="waypoints">You can populate waypoints with data for your country if you want to see them on map</label>
		<input type="submit" id="waypoints" name="waypoints" value="populate waypoints database" />
	    </form>
	</p>
    </div>
<?php
    }
    require('../footer.php');
?>