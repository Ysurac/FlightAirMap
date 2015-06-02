<?php
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.SpotterLive.php');
require_once('class.SpotterArchive.php');
require_once('class.Scheduler.php');
require_once('class.Translation.php');

class SBS {
    static $all_flights = array();

    static function get_Schedule($id,$ident) {
	global $globalDebug;
	// Get schedule here, so it's done only one time
	$operator = Spotter::getOperator($ident);
	if (Schedule::checkSchedule($operator) == 0) {
	    $operator = Translation::checkTranslation($ident);
	    if (Schedule::checkSchedule($operator) == 0) {
		$schedule = Schedule::fetchSchedule($operator);
		if (count($schedule) > 0) {
		    if ($globalDebug) echo "-> Schedule info for ".$operator." (".$ident.")\n";
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport_time' => $schedule['DepartureTime']));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('arrival_airport_time' => $schedule['ArrivalTime']));
		    // FIXME : Check if route schedule = route from DB
		    if ($schedule['DepartureAirportIATA'] != '') {
			if (self::$all_flights[$id]['departure_airport'] != Spotter::getAirportIcao($schedule['DepartureAirportIATA'])) {
			    $airport_icao = Spotter::getAirportIcao($schedule['DepartureAirportIATA']);
			    if ($airport_icao != '') {
				self::$all_flights[$id]['departure_airport'] = $airport_icao;
				if ($globalDebug) echo "-> Change departure airport to ".$airport_icao." for ".$ident."\n";
			    }
			}
		    }
		    if ($schedule['ArrivalAirportIATA'] != '') {
			if (self::$all_flights[$id]['arrival_airport'] != Spotter::getAirportIcao($schedule['ArrivalAirportIATA'])) {
			    $airport_icao = Spotter::getAirportIcao($schedule['ArrivalAirportIATA']);
			    if ($airport_icao != '') {
				self::$all_flights[$id]['arrival_airport'] = $airport_icao;
				if ($globalDebug) echo "-> Change arrival airport to ".$airport_icao." for ".$ident."\n";
			    }
			}
		    }
		    Schedule::addSchedule($operator,self::$all_flights[$id]['departure_airport'],self::$all_flights[$id]['departure_airport_time'],self::$all_flights[$id]['arrival_airport'],self::$all_flights[$id]['arrival_airport_time'],$schedule['Source']);
		}
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
	global $globalAirportIgnore, $globalFork, $globalDistanceIgnore, $globalDaemon, $globalSBSupdate, $globalDebug;
	date_default_timezone_set('UTC');
	// signal handler - playing nice with sockets and dump1090
	// pcntl_signal_dispatch();

	// get the time (so we can figure the timeout)
	$time = time();

	//pcntl_signal_dispatch();
	$dataFound = false;
	$putinarchive = false;
	
	// SBS format is CSV format
	if(is_array($line) && isset($line['hex'])) {
	    //print_r($line);
  	    if ($line['hex'] != '' && $line['hex'] != '00000' && $line['hex'] != '000000' && $line['hex'] != '111111' && ctype_xdigit($line['hex']) && strlen($line['hex']) == 6) {
		$hex = trim($line['hex']);
	        $id = trim($line['hex']);
		
		//print_r(self::$all_flights);
		if (!isset(self::$all_flights[$id]['hex'])) {
		    self::$all_flights[$id] = array('hex' => $hex,'datetime' => $line['datetime']);
		    if (!isset($line['aircraft_icao'])) self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('aircraft_icao' => Spotter::getAllAircraftType($hex)));
		    else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('aircraft_icao' => $line['aircraft_icao']));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => '','departure_airport' => '', 'arrival_airport' => '','latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'heading' => '','departure_airport_time' => '','arrival_airport_time' => '','squawk' => '','route_stop' => ''));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('lastupdate' => time()));
		    if ($globalDebug) echo "*********** New aircraft hex : ".$hex." ***********\n";
		}
 
		if (isset($line['ident']) && $line['ident'] != '' && $line['ident'] != '????????' && (self::$all_flights[$id]['ident'] != trim($line['ident']))) {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => trim($line['ident'])));
		    if (!isset($line['id'])) {
			if (!isset($globalDaemon)) $globalDaemon = TRUE;
			if (isset($line['format_source']) && $line['format_source'] == 'sbs' && $globalDaemon) self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident'].'-'.date('YmdGi')));
		        else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident']));
		     } else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => $line['id']));

		    $putinarchive = true;
		    if (isset($line['departure_airport_icao']) && isset($line['arrival_airport_icao'])) {
		    		self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport' => $line['departure_airport_icao'],'arrival_airport' => $line['arrival_airport_icao'],'route_stop' => ''));
		    } else {
			$route = Spotter::getRouteInfo(trim($line['ident']));
			if (count($route) > 0) {
			    //if ($route['FromAirport_ICAO'] != $route['ToAirport_ICAO']) {
			    if ($route['fromairport_icao'] != $route['toairport_icao']) {
				//    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport' => $route['FromAirport_ICAO'],'arrival_airport' => $route['ToAirport_ICAO'],'route_stop' => $route['RouteStop']));
		    		self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport' => $route['fromairport_icao'],'arrival_airport' => $route['toairport_icao'],'route_stop' => $route['routestop']));
		    	    }
			}
			if (!isset($globalFork)) $globalFork = TRUE;
			if (function_exists('pcntl_fork') && $globalFork) {
			    $pids[$id] = pcntl_fork();
			    if (!$pids[$id]) {
				$sid = posix_setsid();
				SBS::get_Schedule($id,trim($line['ident']));
		    		exit(0);
			    }
			}
		    }
		}
	        
		if (isset($line['latitude']) && $line['latitude'] != '' && $line['latitude'] != 0 && $line['latitude'] < 91) {
		    if (!isset(self::$all_flights[$id]['latitude']) || self::$all_flights[$id]['latitude'] == '' || abs(self::$all_flights[$id]['latitude']-$line['latitude']) < 3 || $line['format_source'] != 'sbs') {
			if (!isset(self::$all_flights[$id]['archive_latitude'])) self::$all_flights[$id]['archive_latitude'] = $line['latitude'];
			if (!isset(self::$all_flights[$id]['livedb_latitude']) || abs(self::$all_flights[$id]['livedb_latitude']-$line['latitude']) > 0.02) {
			    self::$all_flights[$id]['livedb_latitude'] = $line['latitude'];
			    $dataFound = true;
			}
			// elseif ($globalDebug) echo '!*!*! Ignore data, too close to previous one'."\n";
			self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('latitude' => $line['latitude']));
			if (abs(self::$all_flights[$id]['archive_latitude']-self::$all_flights[$id]['latitude']) > 0.3) {
			    self::$all_flights[$id]['archive_latitude'] = $line['latitude'];
			    $putinarchive = true;
			}
		    } elseif (isset(self::$all_flights[$id]['latitude'])) {
			if ($globalDebug) echo '!!! Strange latitude value - diff : '.abs(self::$all_flights[$id]['latitude']-$line['latitude']).'- previous lat : '.self::$all_flights[$id]['latitude'].'- new lat : '.$line['latitude']."\n";
		    }
		}
		if (isset($line['longitude']) && $line['longitude'] != '' && $line['longitude'] != 0 && $line['longitude'] < 181) {
		    if (!isset(self::$all_flights[$id]['longitude']) || self::$all_flights[$id]['longitude'] == ''  || abs(self::$all_flights[$id]['longitude']-$line['longitude']) < 2 || $line['format_source'] != 'sbs') {
			if (!isset(self::$all_flights[$id]['archive_longitude'])) self::$all_flights[$id]['archive_longitude'] = $line['longitude'];
			if (!isset(self::$all_flights[$id]['livedb_longitude']) || abs(self::$all_flights[$id]['livedb_longitude']-$line['longitude']) > 0.02) {
			    self::$all_flights[$id]['livedb_longitude'] = $line['longitude'];
			    $dataFound = true;
			}
			// elseif ($globalDebug) echo '!*!*! Ignore data, too close to previous one'."\n";
			self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('longitude' => $line['longitude']));
			if (abs(self::$all_flights[$id]['archive_longitude']-self::$all_flights[$id]['longitude']) > 0.3) {
			    self::$all_flights[$id]['archive_longitude'] = $line['longitude'];
			    $putinarchive = true;
			}
		    } elseif (isset(self::$all_flights[$id]['longitude'])) {
			if ($globalDebug) echo '!!! Strange longitude value - diff : '.abs(self::$all_flights[$id]['longitude']-$line['longitude']).'- previous lat : '.self::$all_flights[$id]['longitude'].'- new lat : '.$line['longitude']."\n";
		    }

		}
		if (isset($line['verticalrate']) && $line['verticalrate'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('verticalrate' => $line['verticalrate']));
		    //$dataFound = true;
		}
		if (isset($line['emergency']) && $line['emergency'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('emergency' => $line['emergency']));
		    //$dataFound = true;
		}
		if (isset($line['speed']) && $line['speed'] != '') {
		//    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('speed' => $line[12]));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('speed' => round($line['speed'])));
		    //$dataFound = true;
		}
		if (isset($line['squawk']) && $line['squawk'] != '') {
		    if (isset(self::$all_flights[$id]['squawk']) && self::$all_flights[$id]['squawk'] != '7500' && self::$all_flights[$id]['squawk'] != '7600' && self::$all_flights[$id]['squawk'] != '7700' && isset(self::$all_flights[$id]['id'])) {
			    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('squawk' => $line['squawk']));
			    $highlight = '';
			    if (self::$all_flights[$id]['squawk'] == '7500') $highlight = 'Squawk 7500 : Hijack at '.date('G:i').' UTC';
			    if (self::$all_flights[$id]['squawk'] == '7600') $highlight = 'Squawk 7600 : Lost Comm (radio failure) at '.date('G:i').' UTC';
			    if (self::$all_flights[$id]['squawk'] == '7700') $highlight = 'Squawk 7700 : Emergency at '.date('G:i').' UTC';
			    if ($highlight != '') {
				Spotter::setHighlightFlight(self::$all_flights[$id]['id'],$highlight);
				$putinarchive = true;
				$highlight = '';
			    }
			    
		    } else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('squawk' => $line['squawk']));
		    //$dataFound = true;
		}

		$waypoints = '';
		if (isset($line['altitude']) && $line['altitude'] != '') {
		    //if (!isset(self::$all_flights[$id]['altitude']) || self::$all_flights[$id]['altitude'] == '' || (self::$all_flights[$id]['altitude'] > 0 && $line['altitude'] != 0)) {
			if (abs(round($line['altitude']/100)-self::$all_flights[$id]['altitude']) > 2) $putinarchive = true;
			self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('altitude' => round($line['altitude']/100)));
			//$dataFound = true;
		    //} elseif ($globalDebug) echo "!!! Strange altitude data... not added.\n";
  		}

		if (isset($line['heading']) && $line['heading'] != '') {
		    if (abs(self::$all_flights[$id]['heading']-round($line['heading'])) > 2) $putinarchive = true;
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('heading' => round($line['heading'])));
		    //$dataFound = true;
  		}
		if (isset($globalSBS1update) && $globalSBS1update != '' && isset(self::$all_flights[$id]['lastupdate']) && time()-self::$all_flights[$id]['lastupdate'] < $globalSBS1update) $dataFound = false;

//		print_r(self::$all_flights[$id]);
		//gets the callsign from the last hour
		//if (time()-self::$all_flights[$id]['lastupdate'] > 30 && $dataFound == true && self::$all_flights[$id]['ident'] != '' && self::$all_flights[$id]['latitude'] != '' && self::$all_flights[$id]['longitude'] != '') {
		if ($dataFound == true && isset(self::$all_flights[$id]['hex']) && self::$all_flights[$id]['ident'] != '' && self::$all_flights[$id]['latitude'] != '' && self::$all_flights[$id]['longitude'] != '') {
		    if (!isset($globalDistanceIgnore['latitude']) || (isset($globalDistanceIgnore['latitude']) && Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude']) < $globalDistanceIgnore['distance'])) {
		    self::$all_flights[$id]['lastupdate'] = time();
		    //$last_hour_ident = Spotter::getIdentFromLastHour(self::$all_flights[$id]['ident']);
		    $recent_ident = SpotterLive::checkIdentRecent(self::$all_flights[$id]['ident']);
		    //if there was no aircraft with the same callsign within the last hour and go post it into the archive
		    if($recent_ident == "")
		    {
			if ($globalDebug) echo "\o/ Add ".self::$all_flights[$id]['ident']." in archive DB : ";
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
			    if (self::$all_flights[$id]['squawk'] == '7500') $highlight = 'Squawk 7500 : Hijack';
			    if (self::$all_flights[$id]['squawk'] == '7600') $highlight = 'Squawk 7600 : Lost Comm (radio failure)';
			    if (self::$all_flights[$id]['squawk'] == '7700') $highlight = 'Squawk 7700 : Emergency';
			    $result = Spotter::addSpotterData(self::$all_flights[$id]['id'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], $waypoints, self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'],'', self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'],self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],$highlight,self::$all_flights[$id]['hex']);
			}
			$ignoreImport = false;
			if ($globalDebug) echo $result."\n";
			/*
			if (isset($globalArchive) && $globalArchive) {
			    $archives_ident = SpotterLive::getAllLiveSpotterDataByIdent(self::$all_flights[$id]['ident']);
			    foreach ($archives_ident as $archive) {
				SpotterArchive::addSpotterArchiveData($archive['flightaware_id'], $archive['ident'], $archive['registration'],$archive['airline_name'],$archive['airline_icao'],$archive['airline_country'],$archive['airline_type'],$archive['aircraft_icao'],$archive['aircraft_shadow'],$archive['aircraft_name'],$archive['aircraft_manufacturer'], $archive['departure_airport_icao'],$archive['departure_airport_name'],$archive['departure_airport_city'],$archive['departure_airport_country'],$archive['departure_airport_time'],
				$archive['arrival_airport_icao'],$archive['arrival_airport_name'],$archive['arrival_airport_city'],$archive['arrival_airport_country'],$archive['arrival_airport_time'],
				$archive['route_stop'],$archive['date'],$archive['latitude'], $archive['longitude'], $archive['waypoints'], $archive['altitude'], $archive['heading'], $archive['ground_speed'],
				$archive['squawk'],$archive['ModeS']);
			    }
			}
			*/
			//SpotterLive::deleteLiveSpotterDataByIdent(self::$all_flights[$id]['ident']);
			SpotterLive::deleteLiveSpotterData();
			}
		    }

		    //adds the spotter LIVE data
		    //SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
		    //echo "\nAdd in Live !! \n";
		    //echo "{$line[8]} {$line[7]} - MODES:{$line[4]}  CALLSIGN:{$line[10]}   ALT:{$line[11]}   VEL:{$line[12]}   HDG:{$line[13]}   LAT:{$line[14]}   LON:{$line[15]}   VR:{$line[16]}   SQUAWK:{$line[17]}\n";
		    if ($globalDebug) echo 'DATA : hex : '.self::$all_flights[$id]['hex'].' - ident : '.self::$all_flights[$id]['ident'].' - ICAO : '.self::$all_flights[$id]['aircraft_icao'].' - Departure Airport : '.self::$all_flights[$id]['departure_airport'].' - Arrival Airport : '.self::$all_flights[$id]['arrival_airport'].' - Latitude : '.self::$all_flights[$id]['latitude'].' - Longitude : '.self::$all_flights[$id]['longitude'].' - waypoints : '.$waypoints.' - Altitude : '.self::$all_flights[$id]['altitude'].' - Heading : '.self::$all_flights[$id]['heading'].' - Speed : '.self::$all_flights[$id]['speed'].' - Departure Airport Time : '.self::$all_flights[$id]['departure_airport_time'].' - Arrival Airport time : '.self::$all_flights[$id]['arrival_airport_time']."\n";
		    $ignoreImport = false;
		    if (self::$all_flights[$id]['departure_airport'] == "") { self::$all_flights[$id]['departure_airport'] = "NA"; }
		    if (self::$all_flights[$id]['arrival_airport'] == "") { self::$all_flights[$id]['arrival_airport'] = "NA"; }

		    foreach($globalAirportIgnore as $airportIgnore) {
		        if ((self::$all_flights[$id]['departure_airport'] != $airportIgnore) && (self::$all_flights[$id]['arrival_airport'] != $airportIgnore)) {
				$ignoreImport = true;
			}
		    }
		    if (!$ignoreImport) {
			if (!isset($globalDistanceIgnore['latitude']) || (isset($globalDistanceIgnore['latitude']) && Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude']) < $globalDistanceIgnore['distance'])) {
				if ($globalDebug) echo "\o/ Add ".self::$all_flights[$id]['ident']." in Live DB : ";
				$result = SpotterLive::addLiveSpotterData(self::$all_flights[$id]['id'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], $waypoints, self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'], self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'], self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],self::$all_flights[$id]['hex'],$putinarchive);
				//if ($globalDebug) echo "Distance : ".Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude'])."\n";
				if ($globalDebug) echo $result."\n";
			} elseif (isset(self::$all_flights[$id]['latitude']) && isset($globalDistanceIgnore['latitude']) && $globalDebug) echo "!! Too far -> Distance : ".Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude'])."\n";
			self::del();
		    }
		    $ignoreImport = false;
		}
		if (function_exists('pcntl_fork') && $globalFork) pcntl_signal(SIGCHLD, SIG_IGN);

    	    }
	}
    }
}
?>
