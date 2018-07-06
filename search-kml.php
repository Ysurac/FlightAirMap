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

if (!isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="flightairmap.kml"');
}

header('Content-Type: text/xml');

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
$pilot_id = filter_input(INPUT_GET,'pilot_id',FILTER_SANITIZE_STRING);
$pilot_name = filter_input(INPUT_GET,'pilot_name',FILTER_SANITIZE_STRING);
$departure_airport_route = filter_input(INPUT_GET,'departure_airport_route',FILTER_SANITIZE_STRING);
$arrival_airport_route = filter_input(INPUT_GET,'arrival_airport_route',FILTER_SANITIZE_STRING);
if ($id != '') {
	$spotter_array = $Spotter->getSpotterDataByID($id);
} else {
	$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
}
$output = '<?xml version="1.0" encoding="UTF-8"?>';
$output .= '<kml xmlns="http://www.opengis.net/kml/2.2">';
$output .= '<Document>';
$output .= '<Style id="departureAirport">';
$output .= '<IconStyle>';
$output .= '<Icon>';
$output .= '<href>http://real.flightairmap.com/images/kml_departure_airport.png</href>';
$output .= '</Icon>';
$output .= '</IconStyle>';
$output .= '</Style>';
$output .= '<Style id="arrivalAirport">';
$output .= '<IconStyle>';
$output .= '<Icon>';
$output .= '<href>http://real.flightairmap.com/images/kml_arrival_airport.png</href>';
$output .= '</Icon>';
$output .= '</IconStyle>';
$output .= '</Style>';
$output .= '<Style id="route">';
$output .= '<LineStyle>';  
$output .= '<color>7f0000ff</color>';
$output .= '<width>2</width>';
$output .= '<outline>0</outline>';
$output .= '</LineStyle>';
$output .= '</Style>';

if (!empty($spotter_array)) {
	foreach($spotter_array as $spotter_item) {
		$altitude = $spotter_item['altitude'].'00';
		$SpotterArchive = new SpotterArchive();
		$archive_data = $SpotterArchive->getAllArchiveSpotterDataById($spotter_item['flightaware_id']);
		if (!empty($archive_data)) {
			//waypoint plotting
			$output .= '<Placemark>'; 
			$output .= '<styleUrl>#route</styleUrl>';
			$output .= '<LineString>';
			$output .= '<coordinates>';
			foreach ($archive_data as $coord_data) {
				$output .=  $coord_data['longitude'].','.$coord_data['latitude'].','.$coord_data['real_altitude'].' ';
			}
			$output .= '</coordinates>';
			$output .= '<altitudeMode>absolute</altitudeMode>';
			$output .= '</LineString>';
			$output .= '</Placemark>';
		}

		/*
		if ($spotter_item['waypoints'] != '') {
			//waypoint plotting
			$output .= '<Placemark>'; 
			$output .= '<styleUrl>#route</styleUrl>';
			$output .= '<LineString>';
			$output .= '<coordinates>';
			$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
			$waypoint_pieces = array_chunk($waypoint_pieces, 2);
			foreach ($waypoint_pieces as $waypoint_coordinate) {
				if (isset($waypoint_coordinate[1])) $output .=  $waypoint_coordinate[1].','.$waypoint_coordinate[0].','.$altitude.' ';
			}
			$output .= '</coordinates>';
			$output .= '<altitudeMode>absolute</altitudeMode>';
			$output .= '</LineString>';
			$output .= '</Placemark>';
		}
		*/
		//departure airport 
		$output .= '<Placemark>';  
		$output .= '<name>'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</name>';
		$output .= '<description><![CDATA[ ';
		$output .= '<div class="ge-balloon">';
		$output .= '<div class="ge-row">';
		$output .= '<span>Name</span>';
		$output .= $spotter_item['departure_airport_name'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>City</span>';
		$output .= $spotter_item['departure_airport_city'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Country</span>';
		$output .= $spotter_item['departure_airport_country'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>ICAO</span>';
		$output .= $spotter_item['departure_airport_icao'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>IATA</span>';
		$output .= $spotter_item['departure_airport_iata'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Coordinates</span>';
		$output .= $spotter_item['departure_airport_latitude'].', '.$spotter_item['departure_airport_longitude'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Altitude</span>';
		$output .= $spotter_item['departure_airport_altitude'];
		$output .= '</div>';
		$output .= '</div>';
		$output .= ' ]]></description>';
		$output .= '<styleUrl>#departureAirport</styleUrl>';
		$output .= '<Point>';
		$output .=  '<coordinates>'.$spotter_item['departure_airport_longitude'].', '.$spotter_item['departure_airport_latitude'].', '.$spotter_item['departure_airport_altitude'].'</coordinates>';
		$output .= '<altitudeMode>absolute</altitudeMode>';
		$output .= '</Point>';
		$output .= '</Placemark>'; 
		//arrival airport 
		$output .= '<Placemark>';  
		$output .= '<name>'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</name>';
		$output .= '<description><![CDATA[ ';
		$output .= '<div class="ge-balloon">';
		$output .= '<div class="ge-row">';
		$output .= '<span>Name</span>';
		$output .= $spotter_item['arrival_airport_name'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>City</span>';
		$output .= $spotter_item['arrival_airport_city'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Country</span>';
		$output .= $spotter_item['arrival_airport_country'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>ICAO</span>';
		$output .= $spotter_item['arrival_airport_icao'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>IATA</span>';
		$output .= $spotter_item['arrival_airport_iata'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Coordinates</span>';
		$output .= $spotter_item['arrival_airport_latitude'].', '.$spotter_item['arrival_airport_longitude'];
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Altitude</span>';
		$output .= $spotter_item['arrival_airport_altitude'];
		$output .= '</div>';
		$output .= '</div>';
		$output .= ' ]]></description>';
		$output .= '<styleUrl>#arrivalAirport</styleUrl>';
		$output .= '<Point>';
		$output .=  '<coordinates>'.$spotter_item['arrival_airport_longitude'].', '.$spotter_item['arrival_airport_latitude'].', '.$spotter_item['arrival_airport_altitude'].'</coordinates>';
		$output .= '<altitudeMode>absolute</altitudeMode>';
		$output .= '</Point>';
		$output .= '</Placemark>'; 
		/*
		//location of aircraft
		$output .= '<Placemark>';  
		$output .= '<name>'.$spotter_item['ident'].' - '.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'</name>';
		$output .= '<description><![CDATA[ ';
		$output .= '<div class="ge-balloon">';
		if ($spotter_item['image_thumbnail'] != "") {
			$output .= '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'" target="_blank"><img src="'.$spotter_item['image_thumbnail'].'" alt="Click on image to see Flight profile" title="Click on image to see Flight profile" class="ge-image" /></a>';
		}
		$output .= '<div class="ge-row">';
		$output .= '<span>Ident</span>';
		$output .= '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'" target="_blank">'.$spotter_item['ident'].'</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Registration</span>';
		$output .= '<a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'" target="_blank">'.$spotter_item['registration'].'</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Aircraft</span>';
		$output .= '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'" target="_blank">'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Airline</span>';
		$output .= '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'" target="_blank">'.$spotter_item['airline_name'].'</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Departure Airport</span>';
		$output .= '<a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Arrival Airport</span>';
		$output .= '<a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a>';
		$output .= '</div>';
		$output .= '<div class="ge-row">';
		$output .= '<span>Date</span>';
		$output .= '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'" target="_blank">'.date("D M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= ' ]]></description>';
		$output .= '<styleUrl>#aircraft_'.$spotter_item['spotter_id'].'</styleUrl>';
		$output .= '<Point>';
		$output .=  '<coordinates>'.$spotter_item['longitude'].', '.$spotter_item['latitude'].', '.$altitude.'</coordinates>';
		$output .= '<altitudeMode>absolute</altitudeMode>';
		$output .= '</Point>';
		$output .= '</Placemark>'; 
		*/
		$output .= '<Style id="aircraft_'.$spotter_item['spotter_id'].'">';
		$output .= '<IconStyle>';
		$output .= '<Icon>';
		$output .= '<href>http://real.flightairmap.com/images/kml_aircraft.png</href>';
		$output .= '</Icon>';
		$output .= '<heading>'.$spotter_item['heading'].'</heading>';
		$output .= '</IconStyle>';
		$output .= '</Style>';
	}
}
$output .= '</Document>';
$output .= '</kml>';
print $output;
?>