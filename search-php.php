<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
if (isset($_GET['start_date'])) {
	//for the date manipulation into the query
	if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
		$start_date = date("Y-m-d",strtotime($_GET['start_date']))." 00:00:00";
		$end_date = date("Y-m-d", strtotime($_GET['end_date']))." 00:00:00";
		$sql_date = $start_date.",".$end_date;
	} else if($_GET['start_date'] != ""){
		$start_date = date("Y-m-d",strtotime($_GET['start_date']))." 00:00:00";
		$sql_date = $start_date;
	} else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
		$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".date("Y-m-d",strtotime($_GET['end_date']))." 00:00:00";
		$sql_date = $end_date;
	} else $sql_date = '';
} else $sql_date = '';

if (isset($_GET['highest_altitude'])) {
	//for altitude manipulation
	if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
		$end_altitude = filter_input(INPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $start_altitude.",".$end_altitude;
	} else if($_GET['highest_altitude'] != ""){
		$end_altitude = filter_input(INPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $end_altitude;
	} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT).",60000";
		$sql_altitude = $start_altitude;
	} else $sql_altitude = '';
} else $sql_altitude = '';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
	if (!isset($_GET['number_results'])) {
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		if ($_GET['number_results'] > 1000){
			$_GET['number_results'] = 1000;
		}
		$limit_start = 0;
		$limit_end = filter_input(INPUT_GET,'number_results',FILTER_SANITIZE_NUMBER_INT);
		$absolute_difference = filter_input(INPUT_GET,'number_results',FILTER_SANITIZE_NUMBER_INT);
	}
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = filter_var($limit_explode[0],FILTER_SANITIZE_NUMBER_INT);
	$limit_end = filter_var($limit_explode[1],FILTER_SANITIZE_NUMBER_INT);
}

$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if ($_GET['download'] == "true")
{
	header('Content-disposition: attachment; filename="flightairmap.txt"');
}

header("Content-type: text/plain");

if (isset($_GET['sort'])) $sort = $_GET['sort'];
else $sort = '';
$q = filter_input(INPUT_GET,'q',FILTER_SANITIZE_STRING);
$id = filter_input(INPUT_GET,'id',FILTER_SANITIZE_NUMBER_INT);
$registration = filter_input(INPUT_GET,'registratrion',FILTER_SANITIZE_STRING);
$aircraft = filter_input(INPUT_GET,'aircraft',FILTER_SANITIZE_STRING);
$manufacturer = filter_input(INPUT_GET,'manufacturer',FILTER_SANITIZE_STRING);
$highlights = filter_input(INPUT_GET,'highlights',FILTER_SANITIZE_STRING);
$airline = filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
$airline_country = filter_input(INPUT_GET,'airline_country',FILTER_SANITIZE_STRING);
$airline_type = filter_input(INPUT_GET,'airline_type',FILTER_SANITIZE_STRING);
$airport = filter_input(INPUT_GET,'airport',FILTER_SANITIZE_STRING);
$airport_country = filter_input(INPUT_GET,'airport_country',FILTER_SANITIZE_STRING);
$callsign = filter_input(INPUT_GET,'callsign',FILTER_SANITIZE_STRING);
$owner = filter_input(INPUT_GET,'owner',FILTER_SANITIZE_STRING);
$pilot_id = filter_input(INPUT_GET,'pilot_id',FILTER_SANITIZE_STRING);
$pilot_name = filter_input(INPUT_GET,'pilot_name',FILTER_SANITIZE_STRING);
$departure_airport_route = filter_input(INPUT_GET,'departure_airport_route',FILTER_SANITIZE_STRING);
$arrival_airport_route = filter_input(INPUT_GET,'arrival_airport_route',FILTER_SANITIZE_STRING);
if ($id != '') {
	$spotter_array = $Spotter->getSpotterDataByID($id);
} else {
	$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
}

$flights = array();
if (!empty($spotter_array)) {
	foreach($spotter_array as $spotter_item) {
		array_push($flights, array(
		    "id" => $spotter_item['spotter_id'], 
		    "ident" => $spotter_item['ident'], 
		    "registration" => $spotter_item['registration'], 
		    "aircraft_icao" => $spotter_item['aircraft_type'], 
		    "aircraft_name" => $spotter_item['aircraft_name'], 
		    "aircraft_manufacturer" => $spotter_item['aircraft_manufacturer'],
		    "airline_name" => $spotter_item['airline_name'], 
		    "airline_icao" => $spotter_item['airline_icao'], 
		    "airline_iata" => $spotter_item['airline_iata'], 
		    "airline_country" => $spotter_item['airline_country'], 
		    "airline_callsign" => $spotter_item['airline_callsign'], 
		    "airline_type" => $spotter_item['airline_type'],
		    "departure_airport_city" => $spotter_item['departure_airport_city'], 
		    "departure_airport_country" => $spotter_item['departure_airport_country'], 
		    "departure_airport_iata" => $spotter_item['departure_airport_iata'], 
		    "departure_airport_icao" => $spotter_item['departure_airport_icao'], 
		    "departure_airport_latitude" => $spotter_item['departure_airport_latitude'], 
		    "departure_airport_longitude" => $spotter_item['departure_airport_longitude'], 
		    "departure_airport_altitude" => $spotter_item['departure_airport_altitude'], 
		    "arrival_airport_city" => $spotter_item['arrival_airport_city'], 
		    "arrival_airport_country" => $spotter_item['arrival_airport_country'], 
		    "arrival_airport_iata" => $spotter_item['arrival_airport_iata'], 
		    "arrival_airport_icao" => $spotter_item['arrival_airport_icao'], 
		    "arrival_airport_latitude" => $spotter_item['arrival_airport_latitude'], 
		    "arrival_airport_longitude" => $spotter_item['arrival_airport_longitude'], 
		    "arrival_airport_altitude" => $spotter_item['arrival_airport_altitude'],
		    "latitude" => $spotter_item['latitude'], 
		    "longitude" => $spotter_item['longitude'], 
		    "altitude" => $spotter_item['altitude'], 
		    "ground_speed" => $spotter_item['ground_speed'], 
		    "heading" => $spotter_item['heading'], 
		    "heading_name" => $spotter_item['heading_name'], 
		    "waypoints" => $spotter_item['waypoints'],
		    "date" => date("c", strtotime($spotter_item['date_iso_8601']))
		));
	}
}
$flights = serialize($flights);
print $flights;
?>