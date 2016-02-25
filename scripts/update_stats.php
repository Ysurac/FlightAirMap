#!/usr/bin/php
<?php
/**
* This script is used to update databases with external data.
* Should be run as cronjob no more than every 2 weeks if NOTAM is not activated, once a day if NOTAM is activated and every hour if METAR is activated.
*/

require_once(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Spotter.php');
require_once(dirname(__FILE__).'/../require/class.Stats.php');
$Spotter = new Spotter();
$Stats = new Stats();
$Stats->addOldStats();
?>