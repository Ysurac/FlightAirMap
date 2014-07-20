<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');

$options = array(
	'trace' => true,
	'exceptions' => 0,
	'login' => 'mtrunz',
	'password' => '60c3cc748cb83742310186e3f5ed0e942eb8dcc9',
);
$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);

$params = array('query' => '{range lat 44.067853669357596 44.734052347483086} {range lon -80.22216796875 -79.06036376953125} {true inAir}', 'howMany' => '15', 'offset' => '0');
$result = $client->SearchBirdseyeInFlight($params);

print '<pre>';
print_r($result);
print '</pre>';

$dataFound = false;

//deletes the spotter LIVE data
SpotterLive::deleteLiveSpotterData();

if (isset($result->SearchBirdseyeInFlightResult))
{
    if (is_array($result->SearchBirdseyeInFlightResult->aircraft))
    {
			foreach($result->SearchBirdseyeInFlightResult->aircraft as $aircraft)
			{
				$flightaware_id = $aircraft->faFlightID;
				$ident = $aircraft->ident;
				$aircraft_type = $aircraft->type;
				$departure_airport = $aircraft->origin;
				$arrival_airport = $aircraft->destination;
				$latitude = $aircraft->latitude;
				$longitude = $aircraft->longitude;
				$waypoints = $aircraft->waypoints;
				$altitude = $aircraft->altitude;
				$heading = $aircraft->heading;
				$groundspeed = $aircraft->groundspeed;

				$dataFound = true;

				//gets the callsign from the last hour
				$last_hour_ident = Spotter::getIdentFromLastHour($ident);

				//if there was no aircraft with the same callsign within the last hour and go post it into the archive
				if($last_hour_ident == "")
				{
					if ($departure_airport == "") { $departure_airport = "NA"; }
					if ($arrival_airport == "") { $arrival_airport = "NA"; }


					//adds the spotter data for the archive
					Spotter::addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
				}

				//adds the spotter LIVE data
				SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);

			}
		} else {
			$flightaware_id = $result->SearchBirdseyeInFlightResult->aircraft->faFlightID;
			$ident = $result->SearchBirdseyeInFlightResult->aircraft->ident;
			$aircraft_type = $result->SearchBirdseyeInFlightResult->aircraft->type;
			$departure_airport = $result->SearchBirdseyeInFlightResult->aircraft->origin;
			$arrival_airport = $result->SearchBirdseyeInFlightResult->aircraft->destination;
			$latitude = $result->SearchBirdseyeInFlightResult->aircraft->latitude;
			$longitude = $result->SearchBirdseyeInFlightResult->aircraft->longitude;
			$waypoints = $result->SearchBirdseyeInFlightResult->aircraft->waypoints;
			$altitude = $result->SearchBirdseyeInFlightResult->aircraft->altitude;
			$heading = $result->SearchBirdseyeInFlightResult->aircraft->heading;
			$groundspeed = $result->SearchBirdseyeInFlightResult->aircraft->groundspeed;

			$dataFound = true;

			//gets the callsign from the last hour
			$last_hour_ident = Spotter::getIdentFromLastHour($ident);

			//if there was no aircraft with the same callsign within the last hour and go post it into the archive
			if($last_hour_ident == "")
			{
				if ($departure_airport == "") { $departure_airport = "NA"; }
				if ($arrival_airport == "") { $arrival_airport = "NA"; }


				//adds the spotter data for the archive
				Spotter::addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
			}

			//adds the spotter LIVE data
			SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);

		}
}


?>
