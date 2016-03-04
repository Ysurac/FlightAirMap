<?php
/**
* This script is used to import data from FlightAware. Not tested anymore, deprecated because no account available.
*/

require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Spotter.php');
require_once(dirname(__FILE__).'/../require/class.SpotterLive.php');
require_once(dirname(__FILE__).'/../require/settings.php');

//checks to see if FlightAware import is set
if ($globalFlightAware == TRUE)
{
    $SpotterLive = new SpotterLive();
    $Spotter = new Spotter();
    //deletes the spotter LIVE data
    $SpotterLive->deleteLiveSpotterData();
    
    //imports the new data from FlightAware
    $Spotter->importFromFlightAware();
}
?>