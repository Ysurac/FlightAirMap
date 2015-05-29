<?php
/**
* This script is used to import data from FlightAware. Not tested anymore, deprecated because no account available.
*/

require(dirname(__FILE__).'/../require/class.Connection.php');
require(dirname(__FILE__).'/../require/class.Spotter.php');
require(dirname(__FILE__).'/../require/class.SpotterLive.php');

global $globalFlightAware;

//checks to see if FlightAware import is set
if ($globalFlightAware == TRUE)
{
    //deletes the spotter LIVE data
    SpotterLive::deleteLiveSpotterData();
    
    //imports the new data from FlightAware
    Spotter::importFromFlightAware();
}
?>