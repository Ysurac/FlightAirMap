<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (isset($_GET['start_date'])) {
        //for the date manipulation into the query
        if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
                $start_date = $_GET['start_date'].":00";
                $end_date = $_GET['end_date'].":00";
                $sql_date = $start_date.",".$end_date;
        } else if($_GET['start_date'] != ""){
                $start_date = $_GET['start_date'].":00";
                $sql_date = $start_date;
        } else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
                $end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date'].":00";
                $sql_date = $end_date;
        } else $sql_date = '';
} else $sql_date = '';

if (isset($_GET['highest_altitude'])) {
        //for altitude manipulation
        if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
                $end_altitude = $_GET['highest_altitude'];
                $start_altitude = $_GET['lowest_altitude'];
                $sql_altitude = $start_altitude.",".$end_altitude;
        } else if($_GET['highest_altitude'] != ""){
                $end_altitude = $_GET['highest_altitude'];
                $sql_altitude = $end_altitude;
        } else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
                $start_altitude = $_GET['lowest_altitude'].",60000";
                $sql_altitude = $start_altitude;
        } else $sql_altitude = '';
} else $sql_altitude = '';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
        if (!isset($_GET['number_results']))
        {
                $limit_start = 0;
                $limit_end = 25;
                $absolute_difference = 25;
        } else {
                if ($_GET['number_results'] > 1000){
                        $_GET['number_results'] = 1000;
                }
                $limit_start = 0;
                $limit_end = $_GET['number_results'];
                $absolute_difference = $_GET['number_results'];
        }
}  else {
        $limit_explode = explode(",", $_GET['limit']);
        $limit_start = $limit_explode[0];
        $limit_end = $limit_explode[1];
}

$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if ($_GET['download'] == "true")
{
	header('Content-disposition: attachment; filename="flightairmap.rss"');
}

header('Content-Type: application/rss+xml; charset=utf-8');


$date = date("c", time());

if (isset($_GET['sort'])) $sort = $_GET['sort'];
else $sort = $_GET['sort'];
$spotter_array = Spotter::searchSpotterData($_GET['q'],$_GET['registration'],$_GET['aircraft'],strtolower(str_replace("-", " ", $_GET['manufacturer'])),$_GET['highlights'],$_GET['airline'],$_GET['airline_country'],$_GET['airline_type'],$_GET['airport'],$_GET['airport_country'],$_GET['callsign'],$_GET['departure_airport_route'],$_GET['arrival_airport_route'],$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');

print '<?xml version="1.0" encoding="UTF-8" ?>';
print '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:gml="http://www.opengis.net/gml">';

	print '<title>GeoRSS Feed</title>';
	print '<link href="http://www/flightairmap.fr/"/>';
	print '<subtitle>The latest airplanes</subtitle>';
	print '<updated>'.$date.'</updated>';
	print '<author>';
    	print '<name>FlightAirMap</name>';
    	print '<email>no@no.com</email>';
	print '</author>';
	print '<id>FlightAirMap</id>';
	
	if (!empty($spotter_array))
	{
	foreach($spotter_array as $spotter_item)
	{
	
	      
		  print '<entry>';
		    print '<title>'.$spotter_item['ident'].' '.$spotter_item['airline_name'].' | '.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | '.$spotter_item['departure_airport'].' - '.$spotter_item['arrival_airport'].'</title>';
		    print '<link href="http://www.flightairmap.fr/flightid/'.$spotter_item['spotter_id'].'"/>';
		    print '<id>http://www.flightairmap.fr/flightid/'.$spotter_item['spotter_id'].'</id>';
		    print '<content>Ident: '.$spotter_item['ident'].' | Registration: '.$spotter_item['registration'].' | Aircraft: '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | Airline: '.$spotter_item['airline_name'].' | Coming From: '.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].') | Flying to: '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].') | Flew nearby on: '.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'</content>';
		    print '<updated>'.$date.'</updated>';
		    if ($spotter_item['waypoints'] != "")
		    {
			    print '<georss:where>';
					print '<gml:LineString>';
						print '<gml:posList>';
							$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
							$waypoint_pieces = array_chunk($waypoint_pieces, 2);
							    
							foreach ($waypoint_pieces as $waypoint_coordinate)
							{
							        print $waypoint_coordinate[0].' '.$waypoint_coordinate[1].' ';
							
							}
						print '</gml:posList>';
					print '</gml:LineString>';
				print '</georss:where>';
			}
			if ($spotter_item['latitude'] != "0" || $spotter_item['longitude'] != "0")
			{
				print '<georss:where>';
					print '<gml:Point>';
						print '<gml:pos>'.$spotter_item['latitude'].' '.$spotter_item['longitude'].'</gml:pos>';
					print '</gml:Point>';
				print '</georss:where>';
			}
		  print '</entry>';
		    
		 }
	}

print '</feed>';

?>