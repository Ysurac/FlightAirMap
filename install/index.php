<?php
//    require_once('../require/class.Connection.php');
    require_once('class.create_db.php');
    require_once('class.settings.php');
    $title="Install";
    require('header.php');
    require('../require/settings.php');
//print_r( get_loaded_extensions());
    if ($globalInstalled && !isset($_POST['populate'])) exit;

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
	$error[] = "Dom is not loaded. Need for aircraft schedule";
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
    
    if (!isset($_POST['dbtype']) && $writable && !isset($_POST['populate']) && (count($error) == 0)) {
  
?>
    <div class="info column">
	<form method="post">
	    <fieldset>
        	<legend>Create database</legend>
        	<p>
        	    <label for="dbtype">Database type</label>
        	    <select name="dbtype" id="dbtype">
        		<option value="mysql">MySQL</option>
        		<option value="pgsql">PostgreSQL (not tested)</option>
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
        	    <input type="text" name="dbhost" id="dbhost" value="localhost" />
        	</p>
        	<p>
        	    <label for="dbname">Database name</label>
        	    <input type="text" name="dbname" id="dbname" value="<?php print $globalDBname; ?>" />
        	</p>
        	<p>
        	    <label for="dbuser">Database user</label>
        	    <input type="text" name="dbuser" id="dbuser" value="<?php print $globalDBuser; ?>" />
        	</p>
        	<p>
        	    <label for="dbuserpass">Database user password</label>
        	    <input type="password" name="dbuserpass" id="dbuserpass" value="<?php print $globalDBpass; ?>" />
        	</p>
            </fieldset>
            <fieldset>
        	<legend>Site configuration</legend>
        	<p>
        	    <label for="sitename">Site name</label>
        	    <input type="text" name="sitename" id="sitename" value="<?php print $globalName; ?>" />
        	</p>
        	<p>
        	    <label for="siteurl">Site URL</label>
        	    <input type="text" name="siteurl" id="siteurl" value="<?php print $globalURL; ?>" />
        	    Can be null.
        	</p>
		<p>
		    <label for="timezone">Timezone</label>
		    <input type="text" name="timezone" id=timezone" value="<?php print $globalTimezone; ?>" />
		    ex : UTC, Europe/Paris,...
		</p>
            </fieldset>
            <fieldset>
        	<legend>Coverage area</legend>
        	<p>
        	    <label for="latitudemax">The maximum latitude (north)</label>
        	    <input type="text" name="latitudemax" id="latitudemax" value="<?php print $globalLatitudeMax; ?>" />
        	</p>
        	<p>
        	    <label for="latitudemin">The minimum latitude (south)</label>
        	    <input type="text" name="latitudemin" id="latitudemin" value="<?php print $globalLatitudeMin; ?>" />
        	</p>
        	<p>
        	    <label for="longitudemax">The maximum longitude (west)</label>
        	    <input type="text" name="longitudemax" id="longitudemax" value="<?php print $globalLongitudeMax; ?>" />
        	</p>
        	<p>
        	    <label for="longitudemin">The minimum longitude (east)</label>
        	    <input type="text" name="longitudemin" id="longitudemin" value="<?php print $globalLongitudeMin; ?>" />
        	</p>
        	<p>
        	    <label for="latitudecenter">The latitude center</label>
        	    <input type="text" name="latitudecenter" id="latitudecenter" value="<?php print $globalCenterLatitude; ?>" />
        	</p>
        	<p>
        	    <label for="longitudecenter">The longitude center</label>
        	    <input type="text" name="longitudecenter" id="longitudecenter" value="<?php print $globalCenterLongitude; ?>" />
        	</p>
            </fieldset>
	    <fieldset>
		<legend>Data source</legend>
		<p>
		    <label>Choose data source</label>
		    <input type="radio" name="datasource" id="flightaware" value="flightaware" <?php if ($globalFlightAware) { ?>checked="checked" <?php } ?>/>
		    <label for="flightaware">FlightAware</label>
		    <input type="radio" name="datasource" id="sbs" value="sbs" <?php if ($globalSBS1) { ?>checked="checked" <?php } ?> />
		    <label for="sbs">ADS-B, SBS-1 format (dump1090 or SBS-1 compatible format)</label>
		</p>
		<p>
		    <label for="flightawareusername">FlightAware username</label>
		    <input type="text" name="flightawareusername" id="flightawareusername" value="<?php print $globalFlightAwareUsername; ?>" />
		</p>
		<p>
		    <label for="flightawarepassword">FlightAware password/API key</label>
		    <input type="text" name="flightawarepassword" id="flightawarepassword" value="<?php print $globalFlightAwarePassword; ?>" />
		</p>
		<p>
		    <label for="sbshost">SBS-1 host</label>
		    <input type="text" name="sbshost" id="sbshost" value="<?php print $globalSBS1Host; ?>" />
		</p>
		<p>
		    <label for="sbsport">SBS-1 port</label>
		    <input type="text" name="sbsport" id="sbsport" value="<?php print $globalSBS1Port; ?>" />
		</p>
		<p>
		    <label for="sbstimeout">SBS-1 timeout</label>
		    <input type="text" name="sbstimeout" id="sbstimeout" value="<?php print $globalSBS1TimeOut; ?>" />
		</p>
	    </fieldset>
	    <fieldset>
		<legend>Optional configuration</legend>
		<p>
		    <label for="bitly">Bit.ly access token api (used in search page)</label>
		    <input type="text" name="bitly" id="bitly" value="<?php print $globalBitlyAccessToken; ?>" />
		</p>
		<p>
		    <label for="britishairways">British Airways API Key</label>
		    <input type="text" name="britishairways" id="britishairways" value="<?php print $globalBritishAirwaysKey; ?>" />
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
    	    $error .= create_db::import_all_db('../db/');
    	    $settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
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
	$settings = array_merge($settings,array('globalName' => $sitename,'globalURL' => $siteurl, 'globalTimezone' => $timezone));
	
	$latitudemax = filter_input(INPUT_POST,'latitudemax',FILTER_SANITIZE_STRING);
	$latitudemin = filter_input(INPUT_POST,'latitudemin',FILTER_SANITIZE_STRING);
	$longitudemax = filter_input(INPUT_POST,'longitudemax',FILTER_SANITIZE_STRING);
	$longitudemin = filter_input(INPUT_POST,'longitudemin',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalLatitudeMax' => $latitudemax,'globalLatitudeMin' => $latitudemin,'globalLongitudeMax' => $longitudemax,'globalLongitudeMin' => $longitudemin));

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
    </div>
<?php
	    }
	    
	}

    }
    if (isset($_POST['populate'])) {
//        require_once('class.update_db.php');
        include_once('class.update_db.php');
        update_db::update_all();
?>
    <div class="info column">
	<p>All is now installed ! Thanks</p>
	<p>You need to run cron-sbs.php as a daemon. You can use init script in the install/init directory.</p>
    </div>
<?php
    }
    require('../footer.php');
?>