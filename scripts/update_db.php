#!/usr/bin/php
<?php
/**
* This script is used to update databases with external data.
* Should be run as cronjob no more than every 2 weeks if NOTAM is not activated, else once a day.
*/

    require_once(dirname(__FILE__).'/../require/settings.php');
    require(dirname(__FILE__).'/../install/class.update_db.php');
    if (isset($globalNOTAM) && $globalNOTAM && update_db::check_last_notam_update()) {
	echo "update NOTAM";
	update_db::update_notam();
	update_db::insert_last_notam_update();
    } elseif (isset($globalDebug) && $globalDebug && isset($globalNOTAM) && $globalNOTAM) echo "NOTAM are only updated once a day.\n";

    if (update_db::check_last_update()) {
        update_db::update_all();
#    require_once('../require/class.Spotter.php');
#    Spotter::updateFieldsFromOtherTables();
	update_db::insert_last_update();
    } elseif (isset($globalDebug) && $globalDebug) echo "DB are populated with external data only every 15 days ! Files are not updated more often.\n";
?>