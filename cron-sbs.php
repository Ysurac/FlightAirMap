<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');

$dump1090_host = "127.0.0.1";
$dump1090_port = "30003";
$dump1090_timeout = 15;
$debug = true;

date_default_timezone_set('UTC');
$of_interest = false;
$_ = $_SERVER['_'];
$monitored_squawks = array();
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
while (!@socket_connect($sock, $dump1090_host, $dump1090_port))
{
      $err = socket_last_error($sock);
      if ($err == 115 || $err == 114)
      {
        if ((time() - $time) >= $dump1090_timeout)
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
        	echo "{$line[8]} {$line[7]} - ICAO:{$line[4]}  CALLSIGN:{$line[10]}   ALT:{$line[11]}   VEL:{$line[12]}   HDG:{$line[13]}   LAT:{$line[14]}   LON:{$line[15]}   VR:{$line[16]}   SQUAWK:{$line[17]}\n";
		//print_r($line);
		$hex = trim($line[4]);
	        $id = trim($line[4]);

		if (!isset($all_flights[$id]['hex'])) {
		    $all_flights[$id] = array('hex' => $hex,'datetime' => $line[8].' '.$line[7],'aircraft_icao' => Spotter::getAllAircraftType($hex));
		    $all_flights[$id] = array_merge($all_flights[$id],array('ident' => '','departure_airport' => '', 'arrival_airport' => '','latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'heading' => ''));
		    $all_flights[$id] = array_merge($all_flights[$id],array('lastupdate' => time()));
		}
 
		if ($all_flights[$id]['ident'] == '' && $line[10] != '') {
			$all_flights[$id] = array_merge($all_flights[$id],array('ident' => trim($line[10])));
			$route = Spotter::getRouteInfo($line[10]);
			if (count($route) > 0) {
			    if ($route['FromAirport_ICAO'] != $route['ToAirport_ICAO']) {
				$all_flights[$id] = array_merge($all_flights[$id],array('departure_airport' => $route['FromAirport_ICAO'],'arrival_airport' => $route['ToAirport_ICAO']));
			    }
			}
		}
	//        $aircraft_type = '';
	        
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

		//    echo $all_flights[$id]['ident'];
		//    print_r($all_flights[$id]);
		    if($last_hour_ident == "")
		    {
			if ($all_flights[$id]['departure_airport'] == "") { $all_flights[$id]['departure_airport'] = "NA"; }
			if ($all_flights[$id]['arrival_airport'] == "") { $all_flights[$id]['arrival_airport'] = "NA"; }
    		    //adds the spotter data for the archive
			Spotter::addSpotterData($all_flights[$id]['hex'].'-'.$all_flights[$id]['ident'], $all_flights[$id]['ident'], $all_flights[$id]['aircraft_icao'], $all_flights[$id]['departure_airport'], $all_flights[$id]['arrival_airport'], $all_flights[$id]['latitude'], $all_flights[$id]['longitude'], $waypoints, $all_flights[$id]['altitude'], $all_flights[$id]['heading'], $all_flights[$id]['speed']);
		    }

			SpotterLive::deleteLiveSpotterData();
		    //adds the spotter LIVE data
			//SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
			echo "\nAjout dans Live !! \n";
			SpotterLive::addLiveSpotterData($all_flights[$id]['hex'].'-'.$all_flights[$id]['ident'], $all_flights[$id]['ident'], $all_flights[$id]['aircraft_icao'], $all_flights[$id]['departure_airport'], $all_flights[$id]['arrival_airport'], $all_flights[$id]['latitude'], $all_flights[$id]['longitude'], $waypoints, $all_flights[$id]['altitude'], $all_flights[$id]['heading'], $all_flights[$id]['speed']);
		}
    	    }
    }
}
pcntl_exec($_,$argv);
?>
