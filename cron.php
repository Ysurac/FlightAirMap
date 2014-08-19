<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');

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