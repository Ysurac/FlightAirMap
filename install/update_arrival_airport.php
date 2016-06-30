#!/usr/bin/php
<?php
/*
 *    This Script will try to find all real arrival airports for all flights in DB
 *
*/
    require_once('../require/settings.php');
    if ($globalInstalled) {
        echo '$globalInstalled must be set to FALSE in require/settings.php';
        exit;
    }
    require('../require/class.Spotter.php');
    $Spotter = new Spotter();
    $Spotter->updateArrivalAirports();

?>