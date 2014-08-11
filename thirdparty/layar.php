<?php
require('../require/class.Connection.php');
require('../require/class.Spotter.php');
require('../require/class.SpotterLive.php');

header('Content-Type: text/javascript');

/* CUSTOM PARAMETERS:
RADIOLIST - Time in history. 1m | 15m | 30m | 1h | 3h | 6h | 24h | 7d | 30d
*/

//convert radius (getting it in meters...need to convert to km)
$_GET['radius'] = $_GET['radius'] / 1000;

if ($_GET['RADIOLIST'] == "1m" || $_GET['RADIOLIST'] == "15m")
{
    $spotter_array = SpotterLive::getLatestSpotterForLayar($_GET['lat'],$_GET['lon'],$_GET['radius'],$_GET['RADIOLIST']);
} else {
    $spotter_array = Spotter::getLatestSpotterForLayar($_GET['lat'],$_GET['lon'],$_GET['radius'],$_GET['RADIOLIST']);   
}

$layarid = "barriespottei0eg";


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
     $output .= '"errorString": "No aircrafts found nearby. Please increase the search range or change time history to try again.", ';
     $output .= '"errorCode": 20';
    $output .= '}';
}
   
    
print $output;

?>