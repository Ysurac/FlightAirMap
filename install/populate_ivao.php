#!/usr/bin/php
<?php
    require_once('../require/settings.php');
    if ($globalInstalled) {
        echo '$globalInstalled must be set to FALSE in require/settings.php';
        exit;
    }
    require('class.update_db.php');
    if (isset($globalVATSIM) && $globalVATSIM) {
	echo "Install VATSIM airlines...";
	update_db::update_vatsim();
	echo "Done !\n";
    }
    if (isset($globalIVAO) && $globalIVAO) {
	if (!file_exists('tmp/ivae_feb2013.zip')) {
		echo "You have to download the file ivae_feb2013.zip from https://www.ivao.aero/softdev/mirrors.asp?software=IvAeDataUp and put it in install/tmp directory";
	} else {
	        echo "Install IVAO airlines and logos...";
	        update_db::update_IVAO();
		echo "Done !\n";
	}
    }
?>