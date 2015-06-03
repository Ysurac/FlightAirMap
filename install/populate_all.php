#!/usr/bin/php
<?php
    require_once('../require/settings.php');
    if ($globalInstalled) {
        echo '$globalInstalled must be set to FALSE in require/settings.php';
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
?>