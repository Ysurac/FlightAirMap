<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
if ($_GET['flightaware_id'] != "")
{
	$spotter_id = $Spotter->getSpotterIDBasedOnFlightAwareID($_GET['flightaware_id']);
    
    if ($spotter_id != "")
    {
        header('Location: '.$globalURL.'/flightid/'.$spotter_id);
    } else {
	   header('Location: '.$globalURL);
    }
}
?>