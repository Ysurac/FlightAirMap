#!/usr/bin/php
<?php
    require_once('../require/settings.php');
    if ($globalInstalled) {
        //echo '$globalInstalled must be set to FALSE in require/settings.php';
        echo "Use install/index.php instead. You really don't want to use this.";
        exit;
    }
    require('class.create_db.php');
    echo "Create and import all tables...";
    create_db::import_all_db('../db/');
    echo "Done !\n";

    require('class.update_db.php');
    echo "Populate all tables...\n";
    update_db::update_all();
    echo "\nInstall waypoints...(VERY slow!)";
    update_db::update_waypoints();
    echo "Done !\n";
    echo "Install airspace...";
    update_db::update_airspace();
    echo "Done !\n";
    echo 'All is now installed ! Thanks'."\n";
    if ($globalSBS1) {
            echo 'You need to run cron-sbs.php as a daemon. You can use init script in the install/init directory.'."\n";
    }
    if ($globalACARS) {
            echo 'You need to run cron-acars.php as a daemon. You can use init script in the install/init directory.'."\n";
    }
    
?>