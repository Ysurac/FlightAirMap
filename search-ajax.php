<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$Spotter = new Spotter();
$orderby = $Spotter->getOrderBy();

$ask = filter_input(INPUT_GET,'ask',FILTER_SANITIZE_STRING);
if ($ask == 'aircraftsdetected') {
	require_once('require/class.SpotterLive.php');
	$SpotterLive = new SpotterLive();
	$flightcnt = $SpotterLive->getLiveSpotterCount();
	echo json_encode($flightcnt);
} elseif ($ask == 'trackersdetected') {
	require_once('require/class.TrackerLive.php');
	$TrackerLive = new TrackerLive();
	$trackercnt = $TrackerLive->getLiveTrackerCount();
	echo json_encode($trackercnt);
} elseif ($ask == 'marinesdetected') {
	require_once('require/class.MarineLive.php');
	$MarineLive = new MarineLive();
	$marinecnt = $MarineLive->getLiveMarineCount();
	echo json_encode($marinecnt);
} elseif ($ask == 'manufacturer') {
	$manufacturers = $Stats->getAllManufacturers();
	if (empty($manufacturers)) $manufacturers = $Spotter->getAllManufacturers();
	$all_manufacturers = array();
	foreach($manufacturers as $manufacturer)
	{
		$all_manufacturers[] = array('value' => $manufacturer['aircraft_manufacturer'], 'id' => strtolower(str_replace(' ','-',$manufacturer['aircraft_manufacturer'])));
	}
	echo json_encode($all_manufacturers);
} elseif ($ask == 'aircrafttypes') {
	$aircraft_types = $Stats->getAllAircraftTypes();
	if (empty($aircraft_types)) $aircraft_types = $Spotter->getAllAircraftTypes();
	$all_aircraft_types = array();
	foreach($aircraft_types as $aircraft_type)
	{
		$all_aircraft_types[] = array('id' => $aircraft_type['aircraft_icao'], 'value' => $aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')');
	}
	echo json_encode($all_aircraft_types);
} elseif ($ask == 'airlinenames') {
	$airline_names = $Stats->getAllAirlineNames();
	$all_airline_names = array();
	foreach($airline_names as $airline_name)
	{
		$all_airline_names[] = array('id' => $airline_name['airline_icao'], 'value' => $airline_name['airline_name'].' ('.$airline_name['airline_icao'].')');
	}
	echo json_encode($all_airline_names);
} elseif ($ask == 'airlinecountries') {
	$airline_countries = $Spotter->getAllAirlineCountries();
	$all_airline_countries = array();
	foreach($airline_countries as $airline_country)
	{
		$all_airline_countries[] = array('id' => $airline_country['airline_country'], 'value' => $airline_country['airline_country']);
	}
	echo json_encode($all_airline_countries);
} elseif ($ask == 'airportnames' || $ask == 'departureairportnames' || $ask == 'arrivalairportnames') {
	$airport_names = $Stats->getAllAirportNames();
	if (empty($airport_names)) $airport_names = $Spotter->getAllAirportNames();
	ksort($airport_names);
	$all_airport_names = array();
	foreach($airport_names as $airport_name)
	{
		$all_airport_names[] = array('id' => $airport_name['airport_icao'], 'value' => $airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')');
	}
	echo json_encode($all_airport_names);
} elseif ($ask == 'airportcountries') {
	$airport_countries = $Spotter->getAllAirportCountries();
	$all_airport_countries = array();
	foreach($airport_countries as $airport_country)
	{
		$all_airport_countries[] = array('id' => $airport_country['airport_country'], 'value' => $airport_country['airport_country']);
	}
	echo json_encode($all_airport_countries);
}
?>