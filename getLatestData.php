<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

header('Content-Type: text/javascript');
$Spotter = new Spotter();
$spotter_array = $Spotter->getRealTimeData();

print '{';
	print '"airline_logo": "';
		if (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
		{
			print 'http://'.$_SERVER['REMOTE_ADDR'].'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png';
		}
	print '",';
	print '"body": "'.$spotter_array[0]['ident'].' - '.$spotter_array[0]['airline_name'].' | '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].') | '.$spotter_array[0]['departure_airport'].' - '.$spotter_array[0]['arrival_airport'].'",';
	print '"url": "http://'.$_SERVER['REMOTE_ADDR'].'/flightid/'.$spotter_array[0]['spotter_id'].'",';
	print '"html": "';
	
	if (!empty($spotter_array))
	{
		print '<a href=\"http://'.$_SERVER['REMOTE_ADDR'].'/'.$spotter_array[0]['spotter_id'].'\">';
		if (@getimagesize('http://'.$_SERVER['REMOTE_ADDR'].'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
		{
			print '<img src=\"http://'.$_SERVER['REMOTE_ADDR'].'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png\" width=\"50px\" /> ';
		}
		print $spotter_array[0]['ident'].' - '.$spotter_array[0]['airline_name'].' | '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].') | '.$spotter_array[0]['departure_airport'].' - '.$spotter_array[0]['arrival_airport'].'</a>';
	}
	
	print '"';
print '}';


?>