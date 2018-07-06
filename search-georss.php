<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterArchive.php');
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
		$end_altitude = filter_input(INPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $start_altitude.",".$end_altitude;
	} else if($_GET['highest_altitude'] != ""){
		$end_altitude = filter_input(iNPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $end_altitude;
	} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT).",60000";
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
	header('Content-disposition: attachment; filename="flightairmap.rss"');
}

header('Content-Type: application/rss+xml; charset=utf-8');


$date = date("c", time());

if (isset($_GET['sort'])) $sort = $_GET['sort'];
else $sort = '';
$q = filter_input(INPUT_GET,'q',FILTER_SANITIZE_STRING);
$id = filter_input(INPUT_GET,'id',FILTER_SANITIZE_STRING);
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
$pilot_name = filter_input(INPUT_GET,'pilot_name',FILTER_SANITIZE_STRING);
$pilot_id = filter_input(INPUT_GET,'pilot_id',FILTER_SANITIZE_STRING);
$departure_airport_route = filter_input(INPUT_GET,'departure_airport_route',FILTER_SANITIZE_STRING);
$arrival_airport_route = filter_input(INPUT_GET,'arrival_airport_route',FILTER_SANITIZE_STRING);
if ($id != '') {
	$spotter_array = $Spotter->getSpotterDataByID($id);
} else {
	$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
}
 
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
if (!empty($spotter_array)) {
	foreach($spotter_array as $spotter_item) {
		print '<entry>';
		print '<title>'.$spotter_item['ident'].' '.$spotter_item['airline_name'].' | '.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | '.$spotter_item['departure_airport'].' - '.$spotter_item['arrival_airport'].'</title>';
		print '<link href="http://www.flightairmap.fr/flightid/'.$spotter_item['spotter_id'].'"/>';
		print '<id>http://www.flightairmap.com/flightid/'.$spotter_item['spotter_id'].'</id>';
		print '<content>Ident: '.$spotter_item['ident'].' | Registration: '.$spotter_item['registration'].' | Aircraft: '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | Airline: '.$spotter_item['airline_name'].' | Coming From: '.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].') | Flying to: '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].') | Flew nearby on: '.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'</content>';
		print '<updated>'.$date.'</updated>';
		$SpotterArchive = new SpotterArchive();
		$archive_data = $SpotterArchive->getAllArchiveSpotterDataById($spotter_item['flightaware_id']);
		if (!empty($archive_data)) {
			print '<georss:where>';
			print '<gml:LineString>';
			print '<gml:posList>';
			foreach ($archive_data as $coord_data) {
				print $coord_data['latitude'].' '.$coord_data['longitude'].' ';
			}
			print '</gml:posList>';
			print '</gml:LineString>';
			print '</georss:where>';
		}
		/*
		if ($spotter_item['waypoints'] != "") {
			print '<georss:where>';
			print '<gml:LineString>';
			print '<gml:posList>';
			$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
			$waypoint_pieces = array_chunk($waypoint_pieces, 2);
			foreach ($waypoint_pieces as $waypoint_coordinate) {
				print $waypoint_coordinate[0].' '.$waypoint_coordinate[1].' ';
			}
			print '</gml:posList>';
			print '</gml:LineString>';
			print '</georss:where>';
		}
		*/
		/*
		if ($spotter_item['latitude'] != "0" || $spotter_item['longitude'] != "0") {
			print '<georss:where>';
			print '<gml:Point>';
			print '<gml:pos>'.$spotter_item['latitude'].' '.$spotter_item['longitude'].'</gml:pos>';
			print '</gml:Point>';
			print '</georss:where>';
		}
		*/
		print '</entry>';
	}
}
print '</feed>';
?>