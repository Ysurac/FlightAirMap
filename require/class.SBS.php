<?php
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.SpotterLive.php');
require_once('class.Scheduler.php');

class SBS {
    static $debug = true;
    static $all_flights = array();

    static function get_Schedule($id,$ident) {
	// Get schedule here, so it's done only one time
	$operator = Spotter::getOperator($ident);
	if (Schedule::checkSchedule($operator) == 0) {
	    $schedule = Schedule::fetchSchedule($operator);
	    if (count($schedule) > 0) {
		if (self::$debug) echo "-> Schedule info for ".$ident."\n";
		self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport_time' => $schedule['DepartureTime']));
		self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('arrival_airport_time' => $schedule['ArrivalTime']));
		// FIXME : Check if route schedule = route from DB
		if ($schedule['DepartureAirportIATA'] != '') {
		    if (self::$all_flights[$id]['departure_airport'] != Spotter::getAirportIcao($schedule['DepartureAirportIATA'])) {
			$airport_icao = Spotter::getAirportIcao($schedule['DepartureAirportIATA']);
			if ($airport_icao != '') {
			    self::$all_flights[$id]['departure_airport'] = $airport_icao;
			    if (self::$debug) echo "-> Change departure airport to ".$airport_icao." for ".$ident."\n";
			}
		    }
		}
		if ($schedule['ArrivalAirportIATA'] != '') {
		    if (self::$all_flights[$id]['arrival_airport'] != Spotter::getAirportIcao($schedule['ArrivalAirportIATA'])) {
			$airport_icao = Spotter::getAirportIcao($schedule['ArrivalAirportIATA']);
			if ($airport_icao != '') {
			    self::$all_flights[$id]['arrival_airport'] = $airport_icao;
			    if (self::$debug) echo "-> Change arrival airport to ".$airport_icao." for ".$ident."\n";
			}
		    }
		}
		Schedule::addSchedule($operator,self::$all_flights[$id]['departure_airport'],self::$all_flights[$id]['departure_airport_time'],self::$all_flights[$id]['arrival_airport'],self::$all_flights[$id]['arrival_airport_time']);
	    }
	}
    }


    static function del() {
	// Delete old infos
	foreach (self::$all_flights as $key => $flight) {
    	    if (isset($flight['lastupdate'])) {
        	if ($flight['lastupdate'] < (time()-3600)) {
            	    unset(self::$all_flights[$key]);
    	        }
	    }
        }
    }

    static function add($line) {
	global $globalAirportIgnore;
	date_default_timezone_set('UTC');
	// signal handler - playing nice with sockets and dump1090
	// pcntl_signal_dispatch();

	// get the time (so we can figure the timeout)
	$time = time();

	//pcntl_signal_dispatch();
	$dataFound = false;
	
	// SBS format is CSV format
	if(is_array($line) && isset($line[4])) {
  	    if ($line[4] != '' && $line[4] != '00000' && $line[4] != '000000') {
		$hex = trim($line[4]);
	        $id = trim($line[4]);

		if (!isset(self::$all_flights[$id]['hex'])) {
		    self::$all_flights[$id] = array('hex' => $hex,'datetime' => $line[8].' '.$line[7],'aircraft_icao' => Spotter::getAllAircraftType($hex));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => '','departure_airport' => '', 'arrival_airport' => '','latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'heading' => '','departure_airport_time' => '','arrival_airport_time' => '','squawk' => '','route_stop' => ''));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('lastupdate' => time()));
		    if (self::$debug) echo "*********** New aircraft hex : ".$hex." ***********\n";
		}
 
		if ($line[10] != '' && (self::$all_flights[$id]['ident'] != trim($line[10]))) {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => trim($line[10])));
		    $route = Spotter::getRouteInfo(trim($line[10]));
		    if (count($route) > 0) {
			if ($route['FromAirport_ICAO'] != $route['ToAirport_ICAO']) {
		    	    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport' => $route['FromAirport_ICAO'],'arrival_airport' => $route['ToAirport_ICAO'],'route_stop' => $route['RouteStop']));
		        }
		    }
		    if (function_exists('pcntl_fork')) {
			$pids[$id] = pcntl_fork();
			if (!$pids[$id]) {
		    	    $sid = posix_setsid();
		    	    SBS::get_Schedule($id,trim($line[10]));
		    	    exit(0);
			}
		    }
		}
	        
		if ($line[14] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('latitude' => $line[14]));
		    $dataFound = true;
		}
		if ($line[15] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('longitude' => $line[15]));
		    $dataFound = true;
		}
		if ($line[16] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('verticalrate' => $line[16]));
		    //$dataFound = true;
		}
		if ($line[20] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('emergency' => $line[20]));
		    //$dataFound = true;
		}
		if ($line[12] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('speed' => $line[12]));
		    $dataFound = true;
		}
		if ($line[17] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('squawk' => $line[17]));
		    //$dataFound = true;
		}

		$waypoints = '';
		if ($line[11] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('altitude' => $line[11]/100));
		    $dataFound = true;
  		}

		if ($line[13] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('heading' => $line[13]));
		    $dataFound = true;
  		}

		//gets the callsign from the last hour
		if (time()-self::$all_flights[$id]['lastupdate'] > 30 && $dataFound == true && self::$all_flights[$id]['ident'] != '' && self::$all_flights[$id]['latitude'] != '' && self::$all_flights[$id]['longitude'] != '') {
		    self::$all_flights[$id]['lastupdate'] = time();
		    $last_hour_ident = Spotter::getIdentFromLastHour(self::$all_flights[$id]['ident']);
		    //if there was no aircraft with the same callsign within the last hour and go post it into the archive
		    if($last_hour_ident == "")
		    {
			if (self::$debug) echo "\o/ Add in archive DB : ";
			if (self::$all_flights[$id]['departure_airport'] == "") { self::$all_flights[$id]['departure_airport'] = "NA"; }
			if (self::$all_flights[$id]['arrival_airport'] == "") { self::$all_flights[$id]['arrival_airport'] = "NA"; }
			//adds the spotter data for the archive
			$ignoreImport = false;
			foreach($globalAirportIgnore as $airportIgnore) {
			    if ((self::$all_flights[$id]['departure_airport'] != $airportIgnore) && (self::$all_flights[$id]['arrival_airport'] != $airportIgnore)) {
				$ignoreImport = true;
			    }
			}
			if (!$ignoreImport) {
			    $highlight = '';
			    if (self::$all_flights[$id]['squawk'] == '7500' || self::$all_flights[$id]['squawk'] == '7600' || self::$all_flights[$id]['squawk'] == '7700') $highlight = 'true';
			    $result = Spotter::addSpotterData(self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], $waypoints, self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'],'', self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'],self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],$highlight,self::$all_flights[$id]['hex']);
			}
			$ignoreImport = false;
			if (self::$debug) echo $result."\n";
			SpotterLive::deleteLiveSpotterData();
		    }

		    //adds the spotter LIVE data
		    //SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
		    //echo "\nAdd in Live !! \n";
		    //echo "{$line[8]} {$line[7]} - MODES:{$line[4]}  CALLSIGN:{$line[10]}   ALT:{$line[11]}   VEL:{$line[12]}   HDG:{$line[13]}   LAT:{$line[14]}   LON:{$line[15]}   VR:{$line[16]}   SQUAWK:{$line[17]}\n";
		    if (self::$debug) echo 'DATA : hex : '.self::$all_flights[$id]['hex'].' - ident : '.self::$all_flights[$id]['ident'].' - ICAO : '.self::$all_flights[$id]['aircraft_icao'].' - Departure Airport : '.self::$all_flights[$id]['departure_airport'].' - Arrival Airport : '.self::$all_flights[$id]['arrival_airport'].' - Latitude : '.self::$all_flights[$id]['latitude'].' - Longitude : '.self::$all_flights[$id]['longitude'].' - waypoints : '.$waypoints.' - Altitude : '.self::$all_flights[$id]['altitude'].' - Heading : '.self::$all_flights[$id]['heading'].' - Speed : '.self::$all_flights[$id]['speed'].' - Departure Airport Time : '.self::$all_flights[$id]['departure_airport_time'].' - Arrival Airport time : '.self::$all_flights[$id]['arrival_airport_time']."\n";
		    $ignoreImport = false;
		    if (self::$all_flights[$id]['departure_airport'] == "") { self::$all_flights[$id]['departure_airport'] = "NA"; }
		    if (self::$all_flights[$id]['arrival_airport'] == "") { self::$all_flights[$id]['arrival_airport'] = "NA"; }

		    foreach($globalAirportIgnore as $airportIgnore) {
		        if ((self::$all_flights[$id]['departure_airport'] != $airportIgnore) && (self::$all_flights[$id]['arrival_airport'] != $airportIgnore)) {
				$ignoreImport = true;
			}
		    }
		    if (!$ignoreImport) {
			if (self::$debug) echo "\o/ Add in Live DB : ";
			$result = SpotterLive::addLiveSpotterData(self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], $waypoints, self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'], self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'], self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],self::$all_flights[$id]['hex']);
		    }
		    $ignoreImport = false;
		    if (self::$debug) echo $result."\n";
		}
		if (function_exists('pcntl_fork')) pcntl_signal(SIGCHLD, SIG_IGN);

    	    }
	}
    }
}
?>
