<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');
require('require/class.Scheduler.php');

$debug = true;

date_default_timezone_set('UTC');
$_ = $_SERVER['_'];
// signal handler - playing nice with sockets and dump1090
pcntl_signal(SIGINT,  function($signo) {
    global $sock, $db;
    echo "\n\nctrl-c or kill signal received. Tidying up ... ";
    socket_shutdown($sock, 0);
    socket_close($sock);
    $db = null;
    die("Bye!\n");
});
pcntl_signal_dispatch();

// create our socket and set it to non-blocking
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Unable to create socket\n");

// get the time (so we can figure the timeout)
$time = time();

// current packet counter
$current_packets = 0;

// let's try and connect
echo "Connecting to dump1090 ... ";
while (!@socket_connect($sock, $globalSBS1Host, $globalSBS1Port))
{
      $err = socket_last_error($sock);
      if ($err == 115 || $err == 114)
      {
        if ((time() - $time) >= $globalSBS1TimeOut)
        {
          socket_close($sock);
          die("Connection timed out.\n");
        }
      }
      die(socket_strerror($err) . "\n");
}

$all_flights = array();

// connected - lets do some work
echo "Connected!\n";
sleep(1);
echo "SCAN MODE \n\n";
while($buffer = socket_read($sock, 3000, PHP_NORMAL_READ)) {

    // lets play nice and handle signals such as ctrl-c/kill properly
    pcntl_signal_dispatch();
    $dataFound = false;
    // Delete old infos
    foreach ($all_flights as $key => $flight) {
        if (isset($flight['datetime'])) {
            if (strtotime($flight['datetime']) < (time()-3600)) {
                unset($all_flights[$key]);
            }
        }
    }
    // SBS format is CSV format
    $line = explode(',', $buffer);
    if(is_array($line) && isset($line[4])) {
  	    if ($line[4] != '' && $line[4] != '00000' && $line[4] != '000000') {
//        	echo "{$line[8]} {$line[7]} - MODES:{$line[4]}  CALLSIGN:{$line[10]}   ALT:{$line[11]}   VEL:{$line[12]}   HDG:{$line[13]}   LAT:{$line[14]}   LON:{$line[15]}   VR:{$line[16]}   SQUAWK:{$line[17]}\n";
		//print_r($line);
		$hex = trim($line[4]);
	        $id = trim($line[4]);

		if (!isset($all_flights[$id]['hex'])) {
		    $all_flights[$id] = array('hex' => $hex,'datetime' => $line[8].' '.$line[7],'aircraft_icao' => Spotter::getAllAircraftType($hex));
		    $all_flights[$id] = array_merge($all_flights[$id],array('ident' => '','departure_airport' => '', 'arrival_airport' => '','latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'heading' => '','departure_airport_time' => '','arrival_airport_time' => ''));
		    $all_flights[$id] = array_merge($all_flights[$id],array('lastupdate' => time()));
		    if ($debug) echo "New aircraft !!! \n";
		}
 
		if ($line[10] != '' && ($all_flights[$id]['ident'] != trim($line[10]))) {
			$all_flights[$id] = array_merge($all_flights[$id],array('ident' => trim($line[10])));
			$route = Spotter::getRouteInfo(trim($line[10]));
			if (count($route) > 0) {
			    if ($route['FromAirport_ICAO'] != $route['ToAirport_ICAO']) {
				$all_flights[$id] = array_merge($all_flights[$id],array('departure_airport' => $route['FromAirport_ICAO'],'arrival_airport' => $route['ToAirport_ICAO']));
			    }
			}
			// Get schedule here, so it's done only one time
			$schedule = Schedule::getSchedule(trim($line[10]));
			if (count($schedule) > 0) {
				$all_flights[$id] = array_merge($all_flights[$id],array('departure_airport_time' => $schedule['DepartureTime']));
				$all_flights[$id] = array_merge($all_flights[$id],array('arrival_airport_time' => $schedule['ArrivalTime']));
				// FIXME : Check if route schedule = route from DB
				if ($schedule['DepartureAirportIATA'] != '') {
					if ($all_flights[$id]['departure_airport'] != Spotter::getAirportIcao($schedule['DepartureAirportIATA'])) {
						$airport_icao = Spotter::getAirportIcao($schedule['DepartureAirportIATA']);
						if ($airport_icao != '') $all_flights[$id]['departure_airport'] = $airport_icao;
						if ($debug) echo "Change departure airport !!!! \n";
					}
				}
				if ($schedule['ArrivalAirportIATA'] != '') {
					if ($all_flights[$id]['arrival_airport'] != Spotter::getAirportIcao($schedule['ArrivalAirportIATA'])) {
						$airport_icao = Spotter::getAirportIcao($schedule['ArrivalAirportIATA']);
						if ($airport_icao != '') $all_flights[$id]['arrival_airport'] = $airport_icao;
						if ($debug) echo "Change arrival airport !!!! \n";
					}
				}
			}
		}
	        
		if ($line[14] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('latitude' => $line[14]));
			$dataFound = true;
		}
		if ($line[15] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('longitude' => $line[15]));
			$dataFound = true;
		}
		if ($line[16] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('verticalrate' => $line[16]));
			//$dataFound = true;
		}
		if ($line[20] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('emergency' => $line[20]));
			//$dataFound = true;
		}
		if ($line[12] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('speed' => $line[12]));
			$dataFound = true;
		}

		$waypoints = '';
		if ($line[11] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('altitude' => $line[11]/100));
			$dataFound = true;
  		}

		if ($line[13] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('heading' => $line[13]));
			$dataFound = true;
  		}

		//gets the callsign from the last hour
		if (time()-$all_flights[$id]['lastupdate'] > 30 && $dataFound == true && $all_flights[$id]['ident'] != '' && $all_flights[$id]['latitude'] != '' && $all_flights[$id]['longitude'] != '') {
		    $all_flights[$id]['lastupdate'] = time();

		    $last_hour_ident = Spotter::getIdentFromLastHour($all_flights[$id]['ident']);

		//if there was no aircraft with the same callsign within the last hour and go post it into the archive

		    if($last_hour_ident == "")
		    {
			if ($debug) echo "\nAdd in output\n";
			if ($all_flights[$id]['departure_airport'] == "") { $all_flights[$id]['departure_airport'] = "NA"; }
			if ($all_flights[$id]['arrival_airport'] == "") { $all_flights[$id]['arrival_airport'] = "NA"; }
			//adds the spotter data for the archive
			$result = Spotter::addSpotterData($all_flights[$id]['hex'].'-'.$all_flights[$id]['ident'], $all_flights[$id]['ident'], $all_flights[$id]['aircraft_icao'], $all_flights[$id]['departure_airport'], $all_flights[$id]['arrival_airport'], $all_flights[$id]['latitude'], $all_flights[$id]['longitude'], $waypoints, $all_flights[$id]['altitude'], $all_flights[$id]['heading'], $all_flights[$id]['speed'],'', $all_flights[$id]['departure_airport_time'], $all_flights[$id]['arrival_airport_time']);
			if ($debug) echo $result;
		    }

			SpotterLive::deleteLiveSpotterData();
		        //adds the spotter LIVE data
			//SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
			//echo "\nAjout dans Live !! \n";
			//echo "{$line[8]} {$line[7]} - MODES:{$line[4]}  CALLSIGN:{$line[10]}   ALT:{$line[11]}   VEL:{$line[12]}   HDG:{$line[13]}   LAT:{$line[14]}   LON:{$line[15]}   VR:{$line[16]}   SQUAWK:{$line[17]}\n";
			if ($debug) echo 'hex : '.$all_flights[$id]['hex'].' - ident : '.$all_flights[$id]['ident'].' - ICAO : '.$all_flights[$id]['aircraft_icao'].' - Departure Airport : '.$all_flights[$id]['departure_airport'].' - Arrival Airport : '.$all_flights[$id]['arrival_airport'].' - Latitude : '.$all_flights[$id]['latitude'].' - Longitude : '.$all_flights[$id]['longitude'].' - waypoints : '.$waypoints.' - Altitude : '.$all_flights[$id]['altitude'].' - Heading : '.$all_flights[$id]['heading'].' - Speed : '.$all_flights[$id]['speed'].' - Departure Airport Time : '.$all_flights[$id]['departure_airport_time'].' - Arrival Airport time : '.$all_flights[$id]['arrival_airport_time']."\n";

			$result = SpotterLive::addLiveSpotterData($all_flights[$id]['hex'].'-'.$all_flights[$id]['ident'], $all_flights[$id]['ident'], $all_flights[$id]['aircraft_icao'], $all_flights[$id]['departure_airport'], $all_flights[$id]['arrival_airport'], $all_flights[$id]['latitude'], $all_flights[$id]['longitude'], $waypoints, $all_flights[$id]['altitude'], $all_flights[$id]['heading'], $all_flights[$id]['speed'], $all_flights[$id]['departure_airport_time'], $all_flights[$id]['arrival_airport_time']);
			if ($debug) echo $result."\n";
		}
    	    }
    }
}
pcntl_exec($_,$argv);
?>
