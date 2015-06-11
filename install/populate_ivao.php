#!/usr/bin/php
<?php
    require_once('../require/settings.php');
    if ($globalInstalled) {
        echo '$globalInstalled must be set to FALSE in require/settings.php';
        exit;
    }
    require('class.update_db.php');
    if (isset($globalIVAO) && $globalIVAO) {
        echo "Install IVAO airlines and logos...";
        update_db::update_IVAO();
	echo "Done !\n";
    }
?>