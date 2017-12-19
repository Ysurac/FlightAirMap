<?php
@session_start();
//header('Content-Encoding: none;');
require_once(dirname(__FILE__).'/class.create_db.php');
require_once(dirname(__FILE__).'/class.update_schema.php');
require_once(dirname(__FILE__).'/class.settings.php');
require(dirname(__FILE__).'/../require/settings.php');
set_time_limit(0);
ini_set('max_execution_time', 6000);
/*
if ($globalInstalled && !isset($_SESSION['install'])) {
	print '<div class="info column"><p>You need to change $globalInstalled in settings.php to FALSE if you want to access setup again.</p></div>';
	require('../footer.php');
	exit;
}
 */
/*
$writable = false;
if (!is_writable('../require/settings.php')) {
	print '<div class="info column"><p><strong>The file <i>require/settings</i> must be writable.</strong></p></div>';
	require('../footer.php');
	exit;
}
if (!set_time_limit(0)) {
	print '<div class="info column"><p><strong>You may need to update the maximum execution time.</strong></p></div>';
}

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
	print '<div class="info column"><ul>';
	foreach ($error as $err) {
		print '<li>'.$err.'</li>';
	}
	print '</ul>You <strong>must</strong> add these modules.</div>';
	require('../footer.php');
        exit;
}
*/
$settings = array();
$error = '';

if (isset($_GET['reset'])) {
	echo 'Last session : '.$_SESSION['install']."\n";
	print_r($_SESSION['done']);
	unset($_SESSION['install']);
	echo 'Reset session !!';
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'database_create') {
	$globalDebug = FALSE;
	$dbroot = $_SESSION['database_root'];
	$dbrootpass = $_SESSION['database_rootpass'];
	$error .= create_db::create_database($dbroot,$dbrootpass,$globalDBuser,$globalDBpass,$globalDBname,$globalDBdriver,$globalDBhost);
	sleep(5);
	if ($error != '') {
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Create database'));
	} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Create database'));
	$_SESSION['install'] = 'database_import';
	$_SESSION['next'] = 'Create and import tables';
	$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	print json_encode($result);
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'database_import') {
	$globalDebug = FALSE;
	$check_version = update_schema::check_version(false);
	if ($check_version == '0') {
		
		if ($globalDBdriver == 'mysql') {
		    $error .= create_db::import_all_db('../db/');
		} elseif ($globalDBdriver == 'pgsql') {
		    $error .= create_db::import_all_db('../db/pgsql/');
		}
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Create and import tables'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Create and import tables'));
		if ((!isset($globalAircraft) || $globalAircraft === TRUE) && (!isset($globalVA) || $globalVA === FALSE)) {
			$_SESSION['install'] = 'populate';
			$_SESSION['next'] = 'Populate aircraft_modes table with externals data for ADS-B';
		} else {
		    $_SESSION['install'] = 'sources';
		    $_SESSION['next'] = 'Insert data in source table';
		}
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} elseif (!is_numeric($check_version)) {
		$error .= $check_version;
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Create and import tables'));
		if (!isset($_SESSION['next'])) $_SESSION['next'] = '';
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		$error .= update_schema::check_version(true);
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Update schema if needed'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Update schema if needed'));
		$_SESSION['install'] = 'sources';
		$_SESSION['next'] = 'Insert data in source table';
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'waypoints') {
	include_once('class.update_db.php');
	$globalDebug = FALSE;
	$error .= update_db::update_waypoints();
	if ($error != '') {
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate waypoints database'));
	} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate waypoints database'));
/*
	$_SESSION['install'] = 'airspace';
	$_SESSION['next'] = 'Populate airspace table';
	$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	print json_encode($result);
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'airspace') {
	include_once('class.update_db.php');
	$globalDebug = FALSE;
	$error .= update_db::update_airspace_fam();
	if ($error != '') {
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate airspace database'));
	} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate airspace database'));
*/
	$_SESSION['install'] = 'countries';
	$_SESSION['next'] = 'Populate countries table';
	$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	print json_encode($result);
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'countries') {
	include_once('class.update_db.php');
	$globalDebug = FALSE;
	$error .= update_db::update_countries();
	if ($error != '') {
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate countries database'));
	} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate countries database'));
	if (isset($globalNOTAM) && $globalNOTAM && isset($globalNOTAMSource) && $globalNOTAMSource != '') {
	    $_SESSION['install'] = 'notam';
	    $_SESSION['next'] = 'Populate NOTAM table with externals data';
	    $result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	    print json_encode($result);
	/*
	} elseif (isset($_SESSION['owner']) && $_SESSION['owner'] == 1) {
	    $_SESSION['install'] = 'owner';
	    $_SESSION['next'] = 'Populate owner table with externals data';
	    unset($_SESSION['owner']);
	    $result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	    print json_encode($result);
	*/
	} else {
	    $_SESSION['install'] = 'sources';
	    $_SESSION['next'] = 'Insert data in source table';
	    $result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	    print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'populate') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate aircraft_modes table with externals data for ADS-B'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		$error .= update_db::update_ModeS_fam();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate aircraft_modes table with externals data for ADS-B'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate aircraft_modes table with externals data for ADS-B'));
		$_SESSION['install'] = 'populate_flarm';
		$_SESSION['next'] = 'Populate aircraft_modes table with externals data for FLARM';
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'populate_flarm') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate aircraft_modes table with externals data for FLARM'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		//$error .= update_db::update_ModeS_flarm();
		$error .= update_db::update_ModeS_ogn();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate aircraft_modes table with externals data for FLARM'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate aircraft_modes table with externals data for FLARM'));
		if ((isset($globalVATSIM) && $globalVATSIM) && (isset($globalIVAO) && $globalIVAO)) {
			$_SESSION['install'] = 'vatsim';
			if (file_exists('tmp/ivae_feb2013.zip')) $_SESSION['next'] = 'Insert IVAO data';
			else $_SESSION['next'] = 'Insert VATSIM data';
		} elseif (isset($globalVATSIM) && $globalVATSIM) {
			$_SESSION['install'] = 'vatsim';
			$_SESSION['next'] = 'Insert VATSIM data';
		} elseif (isset($globalIVAO) && $globalIVAO) {
			$_SESSION['install'] = 'vatsim';
			if (file_exists('tmp/ivae_feb2013.zip')) $_SESSION['next'] = 'Insert IVAO data';
			else $_SESSION['next'] = 'Insert VATSIM data (IVAO not found)';
		} elseif (isset($globalphpVMS) && $globalphpVMS) {
			$_SESSION['install'] = 'vatsim';
			$_SESSION['next'] = 'Insert phpVMS data';
		} else {
			$_SESSION['install'] = 'routes';
			$_SESSION['next'] = 'Populate routes table with externals data';
		}
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'routes') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate aircraft_modes table with externals data for ADS-B'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		$error .= update_db::update_routes_fam();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate routes table with externals data'));
		} else 	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate routes table with externals data'));
		$_SESSION['install'] = 'translation';
		$_SESSION['next'] = 'Populate translation table with externals data';
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'translation') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate translation table with externals data'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		$error .= update_db::update_translation_fam();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate translation table with externals data'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate translation table with externals data'));
		if ($_SESSION['waypoints'] == 1) {
			$_SESSION['install'] = 'waypoints';
			$_SESSION['next'] = 'Populate waypoints table';
			unset($_SESSION['waypoints']);
		} elseif (isset($globalNOTAM) && $globalNOTAM && isset($globalNOTAMSource) && $globalNOTAMSource != '') {
			$_SESSION['install'] = 'notam';
			$_SESSION['next'] = 'Populate NOTAM table with externals data';
		/*
		} elseif ($_SESSION['owner'] == 1) {
			$_SESSION['install'] = 'owner';
			$_SESSION['next'] = 'Populate owner table with externals data';
			unset($_SESSION['owner']);
		*/
		} else {
			$_SESSION['install'] = 'sources';
			$_SESSION['next'] = 'Insert data in source table';
		}
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'owner') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate owner table with externals data'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		$error = update_db::update_owner_fam();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate owner table with externals data'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate owner table with externals data'));
		$_SESSION['install'] = 'sources';
		$_SESSION['next'] = 'Insert data in source table';
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'notam') {
	if (!is_writable('tmp')) {
		$error = 'The directory <i>install/tmp</i> must be writable.';
		$_SESSION['error'] = $error;
		$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate notam table with externals data'));
		$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
		print json_encode($result);
	} else {
		include_once('class.update_db.php');
		$globalDebug = FALSE;
		if (isset($globalNOTAMSource) && $globalNOTAMSource != '') {
			$error .= update_db::update_notam();
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate notam table with externals data'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate notam table with externals data'));
		} else {
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Populate notam table with externals data (no source defined)'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Populate notam table with externals data (no source defined)'));
		}
		/*
		if (isset($_SESSION['owner']) && $_SESSION['owner'] == 1) {
			$_SESSION['install'] = 'owner';
			$_SESSION['next'] = 'Populate owner table';
			unset($_SESSION['owner']);
			$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
			print json_encode($result);
		} else {
		*/
			$_SESSION['install'] = 'sources';
			$_SESSION['next'] = 'Insert data in source table';
			$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
			print json_encode($result);
		//}
	}
/*
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'ivao') {
	unset($_SESSION['install']);
	if (!is_writable('tmp')) {
		print '<p><strong>The directory <i>install/tmp</i> must be writable.</strong></p>';
		require('../footer.php');
		exit;
	}

	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate airlines table and airlines logos with data from ivao.aero....<img src="../images/loading.gif" /></li></ul></div>';
	flush();
	@ob_flush();

	include_once('class.update_db.php');
	$globalDebug = FALSE;
	update_db::update_ivao();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate ivao table with externals data'));

	$_SESSION['install'] = 'finish';
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
*/
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'sources') {
	if (isset($_SESSION['sources']) && count($_SESSION['sources']) > 0) {
		$sources = $_SESSION['sources'];
		include_once('../require/class.Source.php');
		$globalDebug = FALSE;
		$Source = new Source();
		$Source->deleteAllLocation();
		foreach ($sources as $src) {
			if (isset($src['latitude']) && $src['latitude'] != '') $Source->addLocation($src['name'],$src['latitude'],$src['longitude'],$src['altitude'],$src['city'],$src['country'],$src['source'],'antenna.png');
		}
		$_SESSION['done'] = array_merge($_SESSION['done'],array('Insert data in source table'));
		unset($_SESSION['sources']);
	}
	/*
	if (isset($globalIVAO) && $globalIVAO) $_SESSION['install'] = 'ivao';
	else $_SESSION['install'] = 'finish';
	*/
	if ((isset($globalVATSIM) && $globalVATSIM) && (isset($globalIVAO) && $globalIVAO)) {
		$_SESSION['install'] = 'vatsim';
		if (file_exists('tmp/ivae_feb2013.zip')) $_SESSION['next'] = 'Insert IVAO data';
		else $_SESSION['next'] = 'Insert VATSIM data';
	} elseif (isset($globalVATSIM) && $globalVATSIM) {
		$_SESSION['install'] = 'vatsim';
		$_SESSION['next'] = 'Insert VATSIM data';
	} elseif (isset($globalIVAO) && $globalIVAO) {
		$_SESSION['install'] = 'vatsim';
		if (file_exists('tmp/ivae_feb2013.zip')) $_SESSION['next'] = 'Insert IVAO data';
		else $_SESSION['next'] = 'Insert VATSIM data (IVAO not found)';
	} elseif (isset($globalphpVMS) && $globalphpVMS) {
		$_SESSION['install'] = 'vatsim';
		$_SESSION['next'] = 'Insert phpVMS data';
	} else {
		$_SESSION['install'] = 'finish';
		$_SESSION['next'] = 'finish';
	}
	$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	print json_encode($result);
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'vatsim') {
	include_once('../install/class.create_db.php');
	$globalDebug = FALSE;
	include_once('class.update_db.php');

	if ((isset($globalVATSIM) && $globalVATSIM) && (isset($globalIVAO) && $globalIVAO)) {
		if (file_exists('tmp/ivae_feb2013.zip')) {
			$error .= update_db::update_IVAO();
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Insert IVAO data'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Insert IVAO data'));
		} else {
			$error .= update_db::update_vatsim();
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Insert VATSIM data'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Insert VATSIM data'));
		}
	} elseif (isset($globalVATSIM) && $globalVATSIM) {
		$error .= update_db::update_vatsim();
		if ($error != '') {
			$_SESSION['error'] = $error;
			$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Insert VATSIM data'));
		} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Insert VATSIM data'));
	} elseif (isset($globalIVAO) && $globalIVAO) {
		if (file_exists('tmp/ivae_feb2013.zip')) {
			$error .= update_db::update_IVAO();
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Insert IVAO data'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Insert IVAO data'));
		} else {
			$error .= update_db::update_vatsim();
			if ($error != '') {
				$_SESSION['error'] = $error;
				$_SESSION['errorlst'] = array_merge($_SESSION['errorlst'],array('Insert VATSIM data (IVAO not found)'));
			} else $_SESSION['done'] = array_merge($_SESSION['done'],array('Insert VATSIM data (IVAO not found)'));
		}
	} elseif (isset($globalphpVMS) && $globalphpVMS) {
		$_SESSION['done'] = array_merge($_SESSION['done'],array('Insert phpVMS data'));
	}
	//$_SESSION['install'] = 'routes';
	//$_SESSION['next'] = 'Populate routes table with externals data';
	$_SESSION['install'] = 'finish';
	$_SESSION['next'] = 'finish';
	$result = array('error' => $error,'errorlst' => $_SESSION['errorlst'],'done' => $_SESSION['done'],'next' => $_SESSION['next'],'install' => $_SESSION['install']);
	print json_encode($result);
} else {
	//unset($_SESSION['install']);
	$_SESSION['error'] = 'Unknwon task : '.$_SESSION['install'];
	$result = array('error' => 'Unknwon task : '.$_SESSION['install'],'done' => $_SESSION['done'],'next' => 'finish','install' => 'finish');
	print json_encode($result);
}
?>