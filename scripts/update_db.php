#!/usr/bin/php
<?php
/**
* This script is used to update databases with external data and archive old data
* Should be run as cronjob no more than every 2 weeks if NOTAM is not activated, once a day if NOTAM is activated and every hour if METAR is activated.
*/

// Check if script is not already running... (dirty)
exec("ps u", $output, $result);
$j = 0;
foreach ($output AS $line) if(strpos($line, "update_db.php")) $j++;
if ($j > 1) {
	echo "Script is already runnning...";
	die();
}

require_once(dirname(__FILE__).'/../require/settings.php');
require(dirname(__FILE__).'/../install/class.update_db.php');
$update_db = new update_db();
if (isset($globalNOTAM) && $globalNOTAM && $globalNOTAMSource != '' && $update_db->check_last_notam_update()) {
	echo "updating NOTAM...";
	$update_db->update_notam();
	$update_db->insert_last_notam_update();
} elseif (isset($globalDebug) && $globalDebug && isset($globalNOTAM) && $globalNOTAM) echo "NOTAM are only updated once a day.\n";

if ($update_db->check_last_update() && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) {
	$update_db->update_all();
//	require_once(dirname(__FILE__).'/../require/class.Spotter.php');
//	$Spotter = new Spotter();
//	$Spotter->updateFieldsFromOtherTables();
	$update_db->insert_last_update();
} elseif (isset($globalDebug) && $globalDebug && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM)) echo "DB are populated with external data only every 15 days ! Files are not updated more often.\n";


if (isset($globalMETAR) && isset($globalMETARcycle) && $globalMETAR && $globalMETARcycle) {
	echo "updating METAR...";
	require_once(dirname(__FILE__).'/../require/class.METAR.php');
	$METAR = new METAR();
	if ($METAR->check_last_update()) {
		$METAR->addMETARCycle();
		$METAR->insert_last_update();
	} else echo "METAR are only updated every hours.\n";
}


if (isset($globalOwner) && $globalOwner && $update_db->check_last_owner_update() && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) {
	echo "Updating private aircraft's owners...";
	$update_db->update_owner();
	$update_db->insert_last_owner_update();
} elseif (isset($globalDebug) && $globalDebug && isset($globalOwner) && $globalOwner && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS)) echo "Owner are only updated every 15 days.\n";

if (isset($globalArchiveMonths) && $globalArchiveMonths > 0) {
	echo "Updating statistics and archive old data...";
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
	}
	if (isset($globalArchiveKeepTrackMonths) && $globalArchiveKeepTrackMonths > 0) {
		echo "Deleting archive track old data...";
		require_once(dirname(__FILE__).'/../require/class.SpotterArchive.php');
		$SpotterArchive = new SpotterArchive();
		$SpotterArchive->deleteSpotterArchiveTrackData();
	}
}
if (isset($globalACARSArchiveKeepMonths) && $globalACARSArchiveKeepMonths > 0) {
	echo "Deleting ACARS old data...";
	require_once(dirname(__FILE__).'/../require/class.ACARS.php');
	$ACARS = new ACARS();
	$ACARS->deleteArchiveAcarsData();
}
?>