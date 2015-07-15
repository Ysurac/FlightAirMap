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
        	if ($flight['lastupdate'] < (time()-10000)) {
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
	$send = false;
	
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
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => '','departure_airport' => '', 'arrival_airport' => '','latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'heading' => '','departure_airport_time' => '','arrival_airport_time' => '','squawk' => '','route_stop' => '','registration' => '','pilot_id' => '','pilot_name' => '','waypoints' => ''));
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('lastupdate' => time()));
		    if ($globalDebug) echo "*********** New aircraft hex : ".$hex." ***********\n";
		}
		
		if (isset($line['datetime']) && $line['datetime'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('datetime' => $line['datetime']));
		}
		if (isset($line['registration']) && $line['registration'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('registration' => $line['registration']));
		}
		if (isset($line['waypoints']) && $line['waypoints'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('waypoints' => $line['waypoints']));
		}
		if (isset($line['pilot_id']) && $line['pilot_id'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('pilot_id' => $line['pilot_id']));
		}
		if (isset($line['pilot_name']) && $line['pilot_name'] != '') {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('pilot_name' => $line['pilot_name']));
		}
 
		if (isset($line['ident']) && $line['ident'] != '' && $line['ident'] != '????????' && (self::$all_flights[$id]['ident'] != trim($line['ident'])) && preg_match('/^[a-zA-Z0-9]+$/', $line['ident'])) {
		    self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('ident' => trim($line['ident'])));
		    if (!isset($line['id'])) {
			if (!isset($globalDaemon)) $globalDaemon = TRUE;
			if (isset($line['format_source']) && ($line['format_source'] == 'sbs' || $line['format_source'] == 'tsv' || $line['format_source'] == 'raw') && $globalDaemon) self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident'].'-'.date('YmdGi')));
		        else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => self::$all_flights[$id]['hex'].'-'.self::$all_flights[$id]['ident']));
		     } else self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('id' => $line['id']));

		    $putinarchive = true;
		    if (isset($line['departure_airport_icao']) && isset($line['arrival_airport_icao'])) {
		    		self::$all_flights[$id] = array_merge(self::$all_flights[$id],array('departure_airport' => $line['departure_airport_icao'],'arrival_airport' => $line['arrival_airport_icao'],'route_stop' => ''));
		    } elseif (isset($line['departure_airport_iata']) && isset($line['arrival_airport_iata'])) {
				$line['departure_airport_icao'] = Spotter::getAirportIcao($line['departure_airport_iata']);
				$line['arrival_airport_icao'] = Spotter::getAirportIcao($line['arrival_airport_iata']);
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
	        
		if (isset($line['latitude']) && $line['latitude'] != '' && $line['latitude'] != 0 && $line['latitude'] < 91 && $line['latitude'] > -90) {
		    if (!isset(self::$all_flights[$id]['latitude']) || self::$all_flights[$id]['latitude'] == '' || abs(self::$all_flights[$id]['latitude']-$line['latitude']) < 3 || $line['format_source'] != 'sbs' || time() - self::$all_flights[$id]['lastupdate'] > 30) {
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
		if (isset($line['longitude']) && $line['longitude'] != '' && $line['longitude'] != 0 && $line['longitude'] < 360 && $line['longitude'] > -180) {
		    if ($line['longitude'] > 180) $line['longitude'] = $line['longitude'] - 360;
		    if (!isset(self::$all_flights[$id]['longitude']) || self::$all_flights[$id]['longitude'] == ''  || abs(self::$all_flights[$id]['longitude']-$line['longitude']) < 2 || $line['format_source'] != 'sbs' || time() - self::$all_flights[$id]['lastupdate'] > 30) {
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
			    $result = Spotter::addSpotterData(self::$all_flights[$id]['id'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], self::$all_flights[$id]['waypoints'], self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'],'', self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'],self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],$highlight,self::$all_flights[$id]['hex'],self::$all_flights[$id]['registration'],self::$all_flights[$id]['pilot_id'],self::$all_flights[$id]['pilot_name']);
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
		    if ($globalDebug) echo 'DATA : hex : '.self::$all_flights[$id]['hex'].' - ident : '.self::$all_flights[$id]['ident'].' - ICAO : '.self::$all_flights[$id]['aircraft_icao'].' - Departure Airport : '.self::$all_flights[$id]['departure_airport'].' - Arrival Airport : '.self::$all_flights[$id]['arrival_airport'].' - Latitude : '.self::$all_flights[$id]['latitude'].' - Longitude : '.self::$all_flights[$id]['longitude'].' - waypoints : '.self::$all_flights[$id]['waypoints'].' - Altitude : '.self::$all_flights[$id]['altitude'].' - Heading : '.self::$all_flights[$id]['heading'].' - Speed : '.self::$all_flights[$id]['speed'].' - Departure Airport Time : '.self::$all_flights[$id]['departure_airport_time'].' - Arrival Airport time : '.self::$all_flights[$id]['arrival_airport_time']."\n";
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
				$result = SpotterLive::addLiveSpotterData(self::$all_flights[$id]['id'], self::$all_flights[$id]['ident'], self::$all_flights[$id]['aircraft_icao'], self::$all_flights[$id]['departure_airport'], self::$all_flights[$id]['arrival_airport'], self::$all_flights[$id]['latitude'], self::$all_flights[$id]['longitude'], self::$all_flights[$id]['waypoints'], self::$all_flights[$id]['altitude'], self::$all_flights[$id]['heading'], self::$all_flights[$id]['speed'], self::$all_flights[$id]['departure_airport_time'], self::$all_flights[$id]['arrival_airport_time'], self::$all_flights[$id]['squawk'],self::$all_flights[$id]['route_stop'],self::$all_flights[$id]['hex'],$putinarchive,self::$all_flights[$id]['registration'],self::$all_flights[$id]['pilot_id'],self::$all_flights[$id]['pilot_name']);
				self::$all_flights[$id]['lastupdate'] = time();
				if ($putinarchive) $send = true;
				//if ($globalDebug) echo "Distance : ".Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude'])."\n";
				if ($globalDebug) echo $result."\n";
			} elseif (isset(self::$all_flights[$id]['latitude']) && isset($globalDistanceIgnore['latitude']) && $globalDebug) echo "!! Too far -> Distance : ".Common::distance(self::$all_flights[$id]['latitude'],self::$all_flights[$id]['longitude'],$globalDistanceIgnore['latitude'],$globalDistanceIgnore['longitude'])."\n";
			self::del();
		    }
		    $ignoreImport = false;
		}
		if (function_exists('pcntl_fork') && $globalFork) pcntl_signal(SIGCHLD, SIG_IGN);
		if ($send) return self::$all_flights[$id];

    	    }
	}
    }
    
    static function cprNL($lat) {
	//Lookup table to convert the latitude to index.
	if ($lat < 0) $lat = -$lat;             // Table is simmetric about the equator.
	if ($lat < 10.47047130) return 59;
	if ($lat < 14.82817437) return 58;
	if ($lat < 18.18626357) return 57;
	if ($lat < 21.02939493) return 56;
	if ($lat < 23.54504487) return 55;
	if ($lat < 25.82924707) return 54;
	if ($lat < 27.93898710) return 53;
	if ($lat < 29.91135686) return 52;
	if ($lat < 31.77209708) return 51;
	if ($lat < 33.53993436) return 50;
	if ($lat < 35.22899598) return 49;
	if ($lat < 36.85025108) return 48;
	if ($lat < 38.41241892) return 47;
	if ($lat < 39.92256684) return 46;
	if ($lat < 41.38651832) return 45;
	if ($lat < 42.80914012) return 44;
	if ($lat < 44.19454951) return 43;
	if ($lat < 45.54626723) return 42;
	if ($lat < 46.86733252) return 41;
	if ($lat < 48.16039128) return 40;
	if ($lat < 49.42776439) return 39;
	if ($lat < 50.67150166) return 38;
	if ($lat < 51.89342469) return 37;
	if ($lat < 53.09516153) return 36;
	if ($lat < 54.27817472) return 35;
	if ($lat < 55.44378444) return 34;
	if ($lat < 56.59318756) return 33;
	if ($lat < 57.72747354) return 32;
	if ($lat < 58.84763776) return 31;
	if ($lat < 59.95459277) return 30;
	if ($lat < 61.04917774) return 29;
	if ($lat < 62.13216659) return 28;
	if ($lat < 63.20427479) return 27;
	if ($lat < 64.26616523) return 26;
	if ($lat < 65.31845310) return 25;
	if ($lat < 66.36171008) return 24;
	if ($lat < 67.39646774) return 23;
	if ($lat < 68.42322022) return 22;
	if ($lat < 69.44242631) return 21;
	if ($lat < 70.45451075) return 20;
	if ($lat < 71.45986473) return 19;
	if ($lat < 72.45884545) return 18;
	if ($lat < 73.45177442) return 17;
	if ($lat < 74.43893416) return 16;
	if ($lat < 75.42056257) return 15;
	if ($lat < 76.39684391) return 14;
	if ($lat < 77.36789461) return 13;
	if ($lat < 78.33374083) return 12;
	if ($lat < 79.29428225) return 11;
	if ($lat < 80.24923213) return 10;
	if ($lat < 81.19801349) return 9;
	if ($lat < 82.13956981) return 8;
	if ($lat < 83.07199445) return 7;
	if ($lat < 83.99173563) return 6;
	if ($lat < 84.89166191) return 5;
	if ($lat < 85.75541621) return 4;
	if ($lat < 86.53536998) return 3;
	if ($lat < 87.00000000) return 2;
	return 1;
    }
    
    static function cprN($lat,$isodd) {
        $nl = SBS::cprNL($lat) - $isodd;
        if ($nl > 1) return $nl;
        else return 1;
    }
    



    static function parityCheck($msg, $bits=112) {
$modes_checksum_table = array(
0x3935ea, 0x1c9af5, 0xf1b77e, 0x78dbbf, 0xc397db, 0x9e31e9, 0xb0e2f0, 0x587178,
0x2c38bc, 0x161c5e, 0x0b0e2f, 0xfa7d13, 0x82c48d, 0xbe9842, 0x5f4c21, 0xd05c14,
0x682e0a, 0x341705, 0xe5f186, 0x72f8c3, 0xc68665, 0x9cb936, 0x4e5c9b, 0xd8d449,
0x939020, 0x49c810, 0x24e408, 0x127204, 0x093902, 0x049c81, 0xfdb444, 0x7eda22,
0x3f6d11, 0xe04c8c, 0x702646, 0x381323, 0xe3f395, 0x8e03ce, 0x4701e7, 0xdc7af7,
0x91c77f, 0xb719bb, 0xa476d9, 0xadc168, 0x56e0b4, 0x2b705a, 0x15b82d, 0xf52612,
0x7a9309, 0xc2b380, 0x6159c0, 0x30ace0, 0x185670, 0x0c2b38, 0x06159c, 0x030ace,
0x018567, 0xff38b7, 0x80665f, 0xbfc92b, 0xa01e91, 0xaff54c, 0x57faa6, 0x2bfd53,
0xea04ad, 0x8af852, 0x457c29, 0xdd4410, 0x6ea208, 0x375104, 0x1ba882, 0x0dd441,
0xf91024, 0x7c8812, 0x3e4409, 0xe0d800, 0x706c00, 0x383600, 0x1c1b00, 0x0e0d80,
0x0706c0, 0x038360, 0x01c1b0, 0x00e0d8, 0x00706c, 0x003836, 0x001c1b, 0xfff409,
0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000
);

    $crc = 0;
    if ($bits == 112) $offset = 0;
    else $offset = 112-56;

    for($j = 0; $j < $bits; $j++) {
        $byte = intval($j/8,10);
        $bit = $j%8;
        $bitmask = 1 << (7-$bit);

        /* If bit is set, xor with corresponding table entry. */
        if ($msg[$byte] & $bitmask)  $crc = decbin($crc^intval($modes_checksum_table[$j+$offset],0));
//        echo 'msgbyte : '.$msg[$byte].' - bitmask : '.$bitmask."\n";
//        if ($msg[$byte] & $bitmask)  $crc = SBS::_xor($crc,$modes_checksum_table[$j+$offset]);
    }
//    echo 'crc : '.$crc;
    return $crc; /* 24 bit checksum. */
}

    static function crc($data,$bits = 112) {
	    echo 'data : '.$data."\n";
        $bytes = $bits/8;
        return decbin($data[$bytes-3] << 16) | decbin($data[$bytes-2] << 8) | $data[$bytes-1];
    }

static function _xor($text,$key){
    for($i=0; $i<strlen($text); $i++){
        $text[$i] = intval($text[$i])^intval($key[$i]);
    }
    return $text;
}
}
?>
