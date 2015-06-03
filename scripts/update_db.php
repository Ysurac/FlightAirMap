#!/usr/bin/php
<?php
/**
* This script is used to update databases with external data.
* Should be run as cronjob no more than every 2 weeks
*/

    require_once(dirname(__FILE__).'/../require/settings.php');
    require(dirname(__FILE__).'/../install/class.update_db.php');
    if (update_db::check_last_update()) {
        update_db::update_all();
#    require_once('../require/class.Spotter.php');
#    Spotter::updateFieldsFromOtherTables();
	update_db::insert_last_update();
    } else echo "You can run this script only every 15 days ! Files are not updated more often."
?>