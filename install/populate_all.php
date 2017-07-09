#!/usr/bin/php
<?php
    require_once('../require/settings.php');
    if ($globalInstalled) {
        //echo '$globalInstalled must be set to FALSE in require/settings.php';
        echo "Use install/index.php instead. You really don't want to use this.";
        exit;
    }
    require('class.update_db.php');
    echo "Populate all tables...\n";
    update_db::update_all();
    echo "\nInstall waypoints...(VERY slow!)";
    update_db::update_waypoints();
    echo "Done !\n";
    echo "Install airspace...";
    update_db::update_airspace();
    echo "Done !\n";
    echo "Install countries...";
    update_db::update_countries();
    echo "Done !\n";
    if (isset($globalOwner) && $globalOwner) {
	echo "Install private owners...";
	update_db::update_owner_fam();
        echo "Done !\n";
    }
    /*
    if (isset($globalIVAO) && $globalIVAO) {
        echo "Install IVAO airlines and logos...";
        update_db::update_IVAO();
	echo "Done !\n";
    }
    */
    if (isset($globalNOTAM) && $globalNOTAM && isset($globalNOTAMSource) && $globalNOTAMSource != '') {
	echo "Install NOTAM from notaminfo.com...";
        update_db:update_notam();
        echo "Done !\n";
    }
    if (isset($globalMap3D) && $globalMap3D) {
	echo "Install 3D models...";
	update_db::update_models();
	echo "Done !\n";
	if (isset($globalMapSatellites) && $globalMapSatellites) {
	    echo "Install Space 3D models...";
	    update_db::update_space_models();
	    echo "Done !\n";
        }
    }
?>