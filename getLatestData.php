<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

header('Content-Type: text/javascript');

$spotter_array = Spotter::getRealTimeData();

print '{';
	print '"airline_logo": "';
		if (@getimagesize('http://www.barriespotter.com/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
		{
			print 'http://www.barriespotter.com/images/airlines/'.$spotter_array[0]['airline_icao'].'.png';	
		}
	print '",';
	print '"body": "'.$spotter_array[0]['ident'].' - '.$spotter_array[0]['airline_name'].' | '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].') | '.$spotter_array[0]['departure_airport'].' - '.$spotter_array[0]['arrival_airport'].'",';
	print '"url": "http://www.barriespotter.com/flightid/'.$spotter_array[0]['spotter_id'].'",';
	print '"html": "';
	
	if (!empty($spotter_array))
	{
		print '<a href=\"http://www.barriespotter.com/flightid/'.$spotter_array[0]['spotter_id'].'\">';
		if (@getimagesize('http://www.barriespotter.com/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
		{
			print '<img src=\"http://www.barriespotter.com/images/airlines/'.$spotter_array[0]['airline_icao'].'.png\" width=\"50px\" /> ';
		}
		print $spotter_array[0]['ident'].' - '.$spotter_array[0]['airline_name'].' | '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].') | '.$spotter_array[0]['departure_airport'].' - '.$spotter_array[0]['arrival_airport'].'</a>';
	}
	
	print '"';
print '}';


?>