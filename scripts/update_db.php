#!/usr/bin/php
<?php
/**
* This script is used to update databases with external data and archive old data
* Should be run as cronjob no more than every 2 weeks if NOTAM is not activated, once a day if NOTAM is activated and every hour if METAR is activated.
*/
$runningUpdateScript = TRUE;
require_once(dirname(__FILE__).'/../require/settings.php');
if ($globalInstalled === FALSE) {
	echo "Install script MUST be run before this script. Use your web browser to run install/index.php";
	die();
}
// Check if script is not already running... (dirty)
if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN' && (!isset($globalDisableUpdateCheck) || $globalDisableUpdateCheck === FALSE)) {
	if(function_exists('exec')) {
		exec("ps ux", $output, $result);
		$j = 0;
		foreach ($output as $line) if(strpos($line, dirname(__FILE__)."/update_db.php") && !strpos($line, "sh ") && !strpos($line, "sudo ")) $j++;
		if ($j > 1) {
			echo "Script is already runnning...";
			die();
		}
	}
}
require(dirname(__FILE__).'/../install/class.update_db.php');
$update_db = new update_db();

if ($update_db->check() === false) die();

if ((!isset($globalMasterServer) || !$globalMasterServer) && (!isset($globalOffline) || $globalOffline === FALSE)) {
	if (isset($globalNOTAM) && $globalNOTAM && $update_db->check_last_notam_update()) {
		echo "updating NOTAM...";
		if (!isset($globalNOTAMSource) || $globalNOTAMSource == '') {
			$update_db->update_notam_fam();
		} else {
			$update_db->update_notam();
		}
		$update_db->insert_last_notam_update();
	} elseif (isset($globalDebug) && $globalDebug && isset($globalNOTAM) && $globalNOTAM) echo "NOTAM are only updated once a day.\n";
	if ((!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) && ($update_db->check_last_update() && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS))) {
		$update_db->update_all();
	//	require_once(dirname(__FILE__).'/../require/class.Spotter.php');
	//	$Spotter = new Spotter();
	//	$Spotter->updateFieldsFromOtherTables();
		$update_db->insert_last_update();
	} elseif (isset($globalDebug) && $globalDebug && (!isset($globalVA) || !$globalVA) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM)) echo "DB are populated with external data only every 15 days ! Files are not updated more often.\n";
	if (isset($globalWaypoints) && $globalWaypoints && $update_db->check_last_airspace_update()) {
		echo "Check if new airspace version exist...";
		echo $update_db->update_airspace_fam();
		$update_db->insert_last_airspace_update();
	}
	if (isset($globalGeoid) && $globalGeoid && $update_db->check_last_geoid_update()) {
		echo "Check if new geoid version exist...";
		$error = $update_db->update_geoid_fam();
		if ($error == '') $update_db->insert_last_geoid_update();
		else echo $error;
	}
	if (isset($globalMarine) && $globalMarine && (!isset($globalVM) || $globalVM === FALSE) && $update_db->check_last_marine_identity_update()) {
		echo "Check if new marine identity version exist...";
		echo $update_db->update_marine_identity_fam();
		$update_db->insert_last_marine_identity_update();
	}
	if ((!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) && ($update_db->check_last_owner_update() && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS))) {
		echo "Updating aircraft's owners...\n";
		if (isset($globalMasterSource) && $globalMasterSource) {
			$update_db->update_owner();
		} else {
			$update_db->update_owner_fam();
			//echo "Delete duplicate owner...";
			//$update_db->delete_duplicateowner();
			//echo "Done";
		}
		$update_db->insert_last_owner_update();
	} elseif (isset($globalDebug) && $globalDebug && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) echo "Owner are only updated every 15 days.\n";

	if ((!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) && ($update_db->check_last_airlines_update() && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS))) {
		echo "Updating airlines...\n";
		echo $update_db->update_airlines_fam();
		$update_db->insert_last_airlines_update();
	} elseif (isset($globalDebug) && $globalDebug && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) echo "Airlines are only updated every 15 days.\n";

	if ((!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) && (isset($globalAccidents) && $globalAccidents && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS))) {
		require_once(dirname(__FILE__).'/../require/class.Accident.php');
		$Accident = new Accident();
		echo "Updating accidents...";
		if ($Accident->check_last_accidents_update()) {
			$Accident->download_update();
			$Accident->insert_last_accidents_update();
		} else echo "Accidents are updated once a day.\n";
	}
  
}

if (!isset($globalOffline) || $globalOffline === FALSE) {
	if (isset($globalMETAR) && isset($globalMETARcycle) && $globalMETAR && $globalMETARcycle) {
		echo "updating METAR...";
		require_once(dirname(__FILE__).'/../require/class.METAR.php');
		$METAR = new METAR();
		if ($METAR->check_last_update()) {
			$METAR->addMETARCycle();
			$METAR->insert_last_update();
		} else echo "METAR are only updated every 30 minutes.\n";
	}

	if ((!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) && (isset($globalSchedules) && $globalSchedules && $update_db->check_last_schedules_update() && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS))) {
		echo "Updating schedules...";
		//$update_db->update_oneworld();
		$update_db->update_skyteam();
		$update_db->insert_last_schedules_update();
	} elseif (isset($globalDebug) && $globalDebug && isset($globalOwner) && $globalOwner && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) echo "Schedules are only updated every 15 days.\n";
}

if (isset($globalArchiveMonths) && $globalArchiveMonths > 0) {
	echo "Updating statistics and archive old data...\n";
	require_once(dirname(__FILE__).'/../require/class.Stats.php');
	$Stats = new Stats();
	echo $Stats->addOldStats();
}

if (isset($globalArchive) && $globalArchive) {
	if (isset($globalArchiveKeepMonths) && $globalArchiveKeepMonths > 0) {
		echo "Deleting archive old data...";
		require_once(dirname(__FILE__).'/../require/class.SpotterArchive.php');
		$SpotterArchive = new SpotterArchive();
		$SpotterArchive->deleteSpotterArchiveData();
		require_once(dirname(__FILE__).'/../require/class.TrackerArchive.php');
		$TrackerArchive = new TrackerArchive();
		$TrackerArchive->deleteTrackerArchiveData();
		require_once(dirname(__FILE__).'/../require/class.MarineArchive.php');
		$MarineArchive = new MarineArchive();
		$MarineArchive->deleteMarineArchiveData();
	}
	if (isset($globalArchiveKeepTrackMonths) && $globalArchiveKeepTrackMonths > 0) {
		echo "Deleting archive track old data...";
		require_once(dirname(__FILE__).'/../require/class.SpotterArchive.php');
		$SpotterArchive = new SpotterArchive();
		$SpotterArchive->deleteSpotterArchiveTrackData();
		echo "Done\n";
		echo "Deleting tracker archive track old data...";
		require_once(dirname(__FILE__).'/../require/class.TrackerArchive.php');
		$TrackerArchive = new TrackerArchive();
		$TrackerArchive->deleteTrackerArchiveTrackData();
		echo "Done\n";
		echo "Deleting marine archive track old data...";
		require_once(dirname(__FILE__).'/../require/class.MarineArchive.php');
		$MarineArchive = new MarineArchive();
		$MarineArchive->deleteMarineArchiveTrackData();
		echo "Done\n";
	}
}
if (isset($globalACARSArchiveKeepMonths) && $globalACARSArchiveKeepMonths > 0) {
	echo "Deleting ACARS old data...";
	require_once(dirname(__FILE__).'/../require/class.ACARS.php');
	$ACARS = new ACARS();
	$ACARS->deleteArchiveAcarsData();
	echo "Done\n";
}
if (((isset($globalAircraft) && $globalAircraft) || (isset($globalTracker) && $globalTracker)) && isset($globalGroundAltitude) && $globalGroundAltitude && (!isset($globalOffline) || $globalOffline === FALSE)) {
	echo "Adding ground altitude files...\n";
	require_once(dirname(__FILE__).'/../require/class.Elevation.php');
	$Elevation = new Elevation();
	$Elevation->downloadNeeded();
	//echo "Done\n";
}

if (isset($globalFires) && $globalFires && $update_db->check_last_fires_update() && (!isset($globalOffline) || $globalOffline === FALSE)) {
	echo "Update fires data...";
	echo $update_db->update_fires();
	$update_db->insert_last_fires_update();
	echo "Done\n";
}


if (isset($globalMap3D) && $globalMap3D && (!isset($globalOffline) || $globalOffline === FALSE)) {
	if (isset($globalSatellite) && $globalSatellite && $update_db->check_last_tle_update()) {
		echo "Updating tle for satellites position...";
		$update_db->update_tle();
		$update_db->insert_last_tle_update();
		echo "Done\n";
	}
	if (!isset($globalMasterServer) || !$globalMasterServer) {
		if (isset($globalSatellite) && $globalSatellite && $update_db->check_last_satellite_update()) {
			echo $update_db->update_satellite_fam();
			$update_db->insert_last_satellite_update();
		}
		if (!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft)) {
			$update_db->update_models();
			if (isset($globalMap3DLiveries) && $globalMap3DLiveries) {
				$update_db->update_liveries();
			}
		}
		if (isset($globalSatellite) && $globalSatellite) {
			$update_db->update_space_models();
		}
		if ((isset($globalTracker) && $globalTracker) || (isset($globalMarine) && $globalMarine)) {
			$update_db->update_vehicules_models();
		}
		$update_db->update_weather_models();
	} elseif (isset($globalMasterServer) && $globalMasterServer) {
		if ($update_db->check_last_satellite_update()) {
			echo "Updating satellite data...";
			echo $update_db->update_celestrak();
			$update_db->insert_last_satellite_update();
			echo "Done\n";
		}
	}
}
?>
