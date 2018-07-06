<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();

if (isset($_GET['start_date'])) {
	//for the date manipulation into the query
	if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
		$start_date = date("Y-m-d",strtotime($_GET['start_date']))." 00:00:00";
		$end_date = date("Y-m-d",strtotime($_GET['end_date']))." 00:00:00";
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
	header('Content-disposition: attachment; filename="flightairmap.csv"');
}

header("Content-type: text/csv");

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
$pilot_id = filter_input(INPUT_GET,'pilot_id',FILTER_SANITIZE_STRING);
$pilot_name = filter_input(INPUT_GET,'pilot_name',FILTER_SANITIZE_STRING);
$owner = filter_input(INPUT_GET,'owner',FILTER_SANITIZE_STRING);
$departure_airport_route = filter_input(INPUT_GET,'departure_airport_route',FILTER_SANITIZE_STRING);
$arrival_airport_route = filter_input(INPUT_GET,'arrival_airport_route',FILTER_SANITIZE_STRING);
if ($id != '') {
	$spotter_array = $Spotter->getSpotterDataByID($id);
} else {
	$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
} 

$output = "id,ident,registration,aircraft_icao,aircraft_name,aircraft_manufacturer,airline,airline_icao,airline_iata,airline_country,airline_callsign,airline_type,departure_airport_city,departure_airport_country,departure_airport_iata,departure_airport_icao,departure_airport_latitude,departure_airport_longitude,departure_airport_altitude,arrival_airport_city,arrival_airport_country,arrival_airport_iata,arrival_airport_icao,arrival_airport_latitude,arrival_airport_longitude,arrival_airport_altitude,latitude,longitude,altitude,ground_speed,heading,heading_name,waypoints,date\n";

if (!empty($spotter_array)) {
	foreach($spotter_array as $spotter_item) {
		$output .= $spotter_item['spotter_id'].',';
		$output .= $spotter_item['ident'].',';
		$output .= $spotter_item['registration'].',';
		$output .= $spotter_item['aircraft_type'].',';
		$output .= $spotter_item['aircraft_name'].',';
		$output .= $spotter_item['aircraft_manufacturer'].',';
		$output .= $spotter_item['airline_name'].',';
		$output .= $spotter_item['airline_icao'].',';
		$output .= $spotter_item['airline_iata'].',';
		$output .= $spotter_item['airline_country'].',';
		$output .= $spotter_item['airline_callsign'].',';
		$output .= $spotter_item['airline_type'].',';
		$output .= $spotter_item['departure_airport_city'].',';
		$output .= $spotter_item['departure_airport_country'].',';
		$output .= $spotter_item['departure_airport_iata'].',';
		$output .= $spotter_item['departure_airport_icao'].',';
		$output .= $spotter_item['departure_airport_latitude'].',';
		$output .= $spotter_item['departure_airport_longitude'].',';
		$output .= $spotter_item['departure_airport_altitude'].',';
		$output .= $spotter_item['arrival_airport_city'].',';
		$output .= $spotter_item['arrival_airport_country'].',';
		$output .= $spotter_item['arrival_airport_iata'].',';
		$output .= $spotter_item['arrival_airport_icao'].',';
		$output .= $spotter_item['arrival_airport_latitude'].',';
		$output .= $spotter_item['arrival_airport_longitude'].',';
		$output .= $spotter_item['arrival_airport_altitude'].',';
		$output .= $spotter_item['latitude'].',';
		$output .= $spotter_item['longitude'].',';
		$output .= $spotter_item['altitude'].',';
		$output .= $spotter_item['ground_speed'].',';
		$output .= $spotter_item['heading'].',';
		$output .= $spotter_item['heading_name'].',';
		$output .= $spotter_item['waypoints'].',';
		$output .= date("c", strtotime($spotter_item['date_iso_8601']));
		$output .= "\n";
	}
}
print $output;
?>