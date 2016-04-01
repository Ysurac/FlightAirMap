<?php
class aprs {
    private function urshift($n, $s) {
	return ($n >= 0) ? ($n >> $s) :
    	    (($n & 0x7fffffff) >> $s) | 
        	(0x40000000 >> ($s - 1));
    }

    public function parse($input) {
	$debug = false;
	$result = array();
	$input_len = strlen($input);
	$split_input = str_split($input);

	/* Find the end of header checking for NULL bytes while doing it. */
	$splitpos = strpos($input,':');
	
	/* Check that end was found and body has at least one byte. */
	if ($splitpos == 0 || $splitpos + 1 == $input_len || $splitpos === FALSE) {
	    echo 'invalid';
	    return false;
	}
	
	/* Save header and body. */
	$body = substr($input,$splitpos+1,$input_len);
	$body_len = strlen($body);
	$header = substr($input,0,$splitpos);
	$header_len = strlen($header);
	if ($debug) echo 'header : '.$header."\n";
	
	/* Parse source, target and path. */
	//FLRDF0A52>APRS,qAS,LSTB
	if (preg_match('/^([A-Z0-9\\-]{1,9})>(.*)$/',$header,$matches)) {
	    $ident = $matches[1];
	    $all_elements = $matches[2];
	    if ($debug) echo 'ident : '.$ident."\n";
	    $result['ident'] = $ident;
	} else return false;
	$elements = explode(',',$all_elements);
	$source = end($elements);
	$result['source'] = $source;
	foreach ($elements as $element) {
	    if (preg_match('/^([a-zA-Z0-9-]{1,9})([*]?)$/',$element)) {
	        //echo "ok";
	        if ($element == 'TCPIP*') return false;
	    } elseif (preg_match('/^([0-9A-F]{32})$/',$element)) {
		//echo "ok";
	    } else {
	        return false;
	    }
	}
	// Check for Timestamp
	$body_parse = substr($body,1);
	if (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})([zh\\/])/',$body_parse,$matches)) {
	    $timestamp = $matches[0];
	    if ($matches[4] == 'h') {
		$timestamp = strtotime($matches[1].':'.$matches[2].':'.$matches[3]);
		//echo 'timestamp : '.$timestamp.' - now : '.time()."\n";
		/*
		if (time() + 3900 < $timestamp) $timestamp -= 86400;
		elseif (time() - 82500 > $timestamp) $timestamp += 86400;
		*/
	    } elseif ($matches[4] == 'z' || $matches[4] == '/') {
		// FIXME : fix this...
		$timestamp = strtotime($matches[1].' '.$matches[2].':'.$matches[3]);
	    }
	    $body_parse = substr($body_parse,7);
	    $result['timestamp'] = $timestamp;
	}
	if (strlen($body_parse) > 19) {
	    if (preg_match('/^([0-9]{2})([0-7 ][0-9 ]\\.[0-9 ]{2})([NnSs])(.)([0-9]{3})([0-7 ][0-9 ]\\.[0-9 ]{2})([EeWw])(.)/',$body_parse,$matches)) {
		// 4658.70N/00707.78Ez
		//print_r(str_split($body_parse));
		$latlon = $matches[0];
		$sind = strtoupper($matches[3]);
		$wind = strtoupper($matches[7]);
		$lat_deg = $matches[1];
		$lat_min = $matches[2];
		$lon_deg = $matches[5];
		$lon_min = $matches[6];
	    
		$symbol_table = $matches[4];
		$lat = intval($lat_deg);
		$lon = intval($lon_deg);
		if ($lat > 89 || $lon > 179) return false;
	    
	    /*
	    $tmp_5b = str_replace('.','',$lat_min);
	    if (preg_match('/^([0-9]{0,4})( {0,4})$/',$tmp_5b,$matches)) {
	        print_r($matches);
	    }
	    */
		$latitude = $lat + floatval($lat_min)/60;
		$longitude = $lon + floatval($lon_min)/60;
		if ($sind == 'S') $latitude = 0-$latitude;
		if ($wind == 'W') $longitude = 0-$longitude;
		$result['latitude'] = $latitude;
		$result['longitude'] = $longitude;
	    }
	    if ($body_len > 19) {
		$body_split = str_split($body_parse);
		$symbol_code = $body_split[18];
		if ($symbol_code != '_') {
		    $body_parse = substr($body_parse,19);
		    $body_parse_len = strlen($body_parse);
		    if ($body_parse_len >= 7) {
			
		        if (preg_match('/^([0-9\\. ]{3})\\/([0-9\\. ]{3})/',$body_parse)) {
		    	    $course = substr($body_parse,0,3);
		    	    $tmp_s = intval($course);
		    	    if ($tmp_s >= 1 && $tmp_s <= 360) $result['course'] = intval($course);
		    	    $speed = substr($body_parse,4,3);
		    	    $result['speed'] = round($speed*1.852);
		    	    $body_parse = substr($body_parse,7);
		        }
		        // Check PHGR, PHG, RNG
		    } else if ($body_parse_len > 0) {
			$rest = $body_parse;
		    }
		    if (strlen($body_parse) > 0) {
		        if (preg_match('/\\/A=(-[0-9]{5}|[0-9]{6})/',$body_parse,$matches)) {
		            $altitude = intval($matches[1]);
		            $result['altitude'] = round($altitude*0.3048);
		            $body_parse = substr($body_parse,strlen($matches[0])+1);
		        }
		    }
		    
		    // Telemetry
		    if (preg_match('/^([0-9]+),(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,([01]{0,8})/',$body_parse,$matches)) {
		        // Nothing yet...
		    }
		    // DAO
		    if (preg_match('/^!([0-9A-Z]{3})/',$body_parse,$matches)) {
			    $dao = $matches[1];
			    $dao_split = str_split($dao);
			    if (preg_match('/^([A-Z])([0-9]{2})/',$dao)) {
			        $lat_off = (($dao[1])-48.0)*0.001/60.0;
			        $lon_off = (($dao[2])-48.0)*0.001/60.0;
			    
			    }
			    if ($result['latitude'] < 0) $result['latitude'] -= $lat_off;
			    else $result['latitude'] += $lat_off;
			    if ($result['longitude'] < 0) $result['longitude'] -= $lon_off;
			    else $result['longitude'] += $lon_off;
		            $body_parse = substr($body_parse,6);
		    }
		    
		    // OGN comment
		   // echo "Before OGN : ".$body_parse."\n";
		    if (preg_match('/^id([0-9A-F]{8}) ([+-])([0-9]{3,4})fpm ([+-])([0-9.]{3,4})rot (.*)$/',$body_parse,$matches)) {
			$id = $matches[1];
			$mode = substr($id,0,2);
			$address = substr($id,2);
			//print_r($matches);
			$addressType = (intval(substr($id,0,2),16))&3;
			if ($addressType == 0) $result['addresstype'] = "RANDOM";
			elseif ($addressType == 1) $result['addresstype'] = "ICAO";
			elseif ($addressType == 2) $result['addresstype'] = "FLARM";
			elseif ($addressType == 3) $result['addresstype'] = "OGN";
			$aircraftType = $this->urshift(((intval(substr($id,0,2),16)) & 0b1111100),2);
			$result['aircrafttype_code'] = $aircraftType;
			if ($aircraftType == 0) $result['aircrafttype'] = "UNKNOWN";
			elseif ($aircraftType == 1) $result['aircrafttype'] = "GLIDER";
			elseif ($aircraftType == 2) $result['aircrafttype'] = "TOW_PLANE";
			elseif ($aircraftType == 3) $result['aircrafttype'] = "HELICOPTER_ROTORCRAFT";
			elseif ($aircraftType == 4) $result['aircrafttype'] = "PARACHUTE";
			elseif ($aircraftType == 5) $result['aircrafttype'] = "DROP_PLANE";
			elseif ($aircraftType == 6) $result['aircrafttype'] = "HANG_GLIDER";
			elseif ($aircraftType == 7) $result['aircrafttype'] = "PARA_GLIDER";
			elseif ($aircraftType == 8) $result['aircrafttype'] = "POWERED_AIRCRAFT";
			elseif ($aircraftType == 9) $result['aircrafttype'] = "JET_AIRCRAFT";
			elseif ($aircraftType == 10) $result['aircrafttype'] = "UFO";
			elseif ($aircraftType == 11) $result['aircrafttype'] = "BALLOON";
			elseif ($aircraftType == 12) $result['aircrafttype'] = "AIRSHIP";
			elseif ($aircraftType == 13) $result['aircrafttype'] = "UAV";
			elseif ($aircraftType == 15) $result['aircrafttype'] = "STATIC_OBJECT";
			$stealth = (intval(substr($id,0,2), 16) & 0b10000000);
			$result['stealth'] = $stealth;
			$result['address'] = $address;
		    }
		    
		    //Comment
		    $result['comment'] = $body_parse;
		} else {
		    // parse weather
		    $body_parse = substr($body_parse,19);
		    $body_parse_len = strlen($body_parse);

		    if (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})g([0-9 \\.]+)t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
			$result['wind_dir'] = $matches[1];
			$result['wind_speed'] = round($matches[2]*1.60934,1);
			$result['wind_gust'] = round($matches[3]*1.60934,1);
			$result['temp'] = round(5/9*(($matches[4])-32),1);
		        $body_parse = substr($body_parse,strlen($matches[0])+1);
		    } elseif (preg_match('/^_{0,1}c([0-9 \\.\\-]{3})s([0-9 \\.]{3})g([0-9 \\.]+)t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
			$result['wind_dir'] = $matches[1];
			$result['wind_speed'] = round($matches[2]*1.60934,1);
			$result['wind_gust'] = round($matches[3]*1.60934,1);
			$result['temp'] = round(5/9*(($matches[4])-32),1);
		        $body_parse = substr($body_parse,strlen($matches[0])+1);
		    } elseif (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
			$result['wind_dir'] = $matches[1];
			$result['wind_speed'] = round($matches[2]*1.60934,1);
			$result['wind_gust'] = round($matches[3]*1.60934,1);
		        $body_parse = substr($body_parse,strlen($matches[0])+1);
		    } elseif (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})g([0-9 \\.]+)/',$body_parse,$matches)) {
			$result['wind_dir'] = $matches[1];
			$result['wind_speed'] = round($matches[2]*1.60934,1);
			$result['wind_gust'] = round($matches[3]*1.60934,1);
		        $body_parse = substr($body_parse,strlen($matches[0])+1);
		    }
		    if (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9]+)t(-?[0-9 \\.]{1,3})/',$body_parse,$matches)) {
			$result['temp'] = round(5/9*(($matches[1])-32),1);
		    }
		}
	    }
	}
	if (isset($result['latitude'])) $result['latitude'] = round($result['latitude'],4);
	if (isset($result['longitude'])) $result['longitude'] = round($result['longitude'],4);
	//print_r($result);
	return $result;
    }
}
/*
$aprs = new aprs();
print_r($aprs->parse('ICA400EE9>APRS,qAS,UKHUN:/083216h5138.51N\00121.61W^279/050/A=003949 !W25! id21400EE9 -8988fpm -10.2rot 10.8dB 0e -6.9kHz gps5x7'));
  */
?>