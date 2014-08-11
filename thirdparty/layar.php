<?php
require('../require/class.Connection.php');
require('../require/class.Spotter.php');

//calculuation for the pagination
if ($_GET['CUSTOM_SLIDER'] == "")
{
    $limit_start = 0;
    $limit_end = 25;
    $absolute_difference = 25;
} else {
    if ($_GET['CUSTOM_SLIDER'] > 100){
        $_GET['CUSTOM_SLIDER'] = 100;
    }
    $limit_start = 0;
    $limit_end = $_GET['CUSTOM_SLIDER'];
    $absolute_difference = $_GET['CUSTOM_SLIDER'];
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

header('Content-Type: text/javascript');

/* CUSTOM PARAMETERS:
SEARCHBOX = Airline ICAO Code
SEARCHBOX_2 = Aircraft Type ICAO Code
SEARCHBOX_3 = Airport ICAO Code
RADIOLIST = order by: either date | distance
*/

$spotter_array = Spotter::getLatestSpotterForLayar($_GET['lat'],$_GET['lon'],$_GET['radius'],$_GET['SEARCHBOX'],$_GET['SEARCHBOX_2'],$_GET['SEARCHBOX_3'],$_GET['RADIOLIST'],$limit_start.",".$absolute_difference);

$layarid = "barriespottede7a";


if (!empty($spotter_array))
{
    $output .= '{"hotspots": [';
    foreach($spotter_array as $spotter_item)
    {
        $output .= '{';
           $output .= '"id": "'.$spotter_item['spotter_id'].'",';
           $output .= '"anchor": { "geolocation": { "lat": '.$spotter_item['latitude'].', "lon": '.$spotter_item['longitude'].' } },';  
           $output .= '"text": {';
             $output .= '"title": "'.$spotter_item['ident'].' '.$spotter_item['airline_name'].' | '.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | '.$spotter_item['departure_airport'].' - '.$spotter_item['arrival_airport'].'", ';
             $output .= '"description": "Callsign: '.$spotter_item['ident'].' | Registration: '.$spotter_item['registration'].' | Aircraft: '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | Airline: '.$spotter_item['airline_name'].' | Coming From: '.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].') | Flying to: '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].') | Flew nearby on: '.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'", ';
             $output .= '"footnote": "Powered by Barrie Spotter" },';
         if ($spotter_item['image_thumbnail'] != "")
    	 {
            $output .= '"imageURL": "'.$spotter_item['image_thumbnail'].'"';
         } else {
             $output .= '"imageURL": "'.$globalURL.'/images/placeholder_thumb.png"';  
         }
         $output .= '},';
    }
    $output  = substr($output, 0, -1);
     $output .= '], ';
     $output .= '"layer": "'.$layarid.'",';
     $output .= '"errorString": "ok", ';
     $output .= '"errorCode": 0';
    $output .= '}';
} else {
    $output .= '{';
    $output .= '"layer": "'.$layarid.'",';
     $output .= '"errorString": "No aircrafts found. Please increase the search range to try again.", ';
     $output .= '"errorCode": 20';
    $output .= '}';
}
   
    
print $output;

?>