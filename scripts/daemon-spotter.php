#!/usr/bin/php
<?php
/**
* This script is used to retrieve message from SBS source like Dump1090, Radarcape,.. or from phpvms, wazzup files,...
* If not used for SBS TCP source, this script can be used as cron job with $globalDaemon = FALSE
*/


require_once(dirname(__FILE__).'/../require/class.SBS.php');
require_once(dirname(__FILE__).'/../require/class.APRS.php');
require_once(dirname(__FILE__).'/../require/class.ATC.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');

if (!isset($globalDebug)) $globalDebug = FALSE;

// Check if schema is at latest version
$schema = new Connection();
if ($schema::latest() === false) {
    echo "You MUST update to latest schema. Run install/index.php";
    exit();
}

$SBS=new SBS();
$APRS=new APRS();

date_default_timezone_set('UTC');
// signal handler - playing nice with sockets and dump1090
if (function_exists('pcntl_fork')) {
    pcntl_signal(SIGINT,  function($signo) {
        global $sockets;
        echo "\n\nctrl-c or kill signal received. Tidying up ... ";
        die("Bye!\n");
    });
    pcntl_signal_dispatch();
}

// let's try and connect
if ($globalDebug) echo "Connecting...\n";

function create_socket($host, $port, &$errno, &$errstr) {
    $ip = gethostbyname($host);
    $s = socket_create(AF_INET, SOCK_STREAM, 0);
    if (socket_set_nonblock($s)) {
        $r = @socket_connect($s, $ip, $port);
        if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
            return $s;
        }
    }
    $errno = socket_last_error($s);
    $errstr = socket_strerror($errno);
    socket_close($s);
    return false;
}

function connect_all($hosts) {
    global $sockets, $formats, $globalDebug,$aprs_connect;
    foreach ($hosts as $id => $host) {
	if (filter_var($host,FILTER_VALIDATE_URL)) {
            if (preg_match('/deltadb.txt$/',$host)) {
        	$formats[$id] = 'deltadbtxt';
        	if ($globalDebug) echo "Connect to deltadb source...\n";
    	    } else if (preg_match('/aircraftlist.json$/',$host)) {
        	$formats[$id] = 'aircraftlistjson';
        	if ($globalDebug) echo "Connect to aircraftlist.json source...\n";
            } else if (preg_match('/\/action.php\/acars\/data$/',$host)) {
        	$formats[$id] = 'phpvmacars';
        	if ($globalDebug) echo "Connect to phpvmacars source...\n";
            } else if (preg_match('/whazzup/',$host)) {
        	$formats[$id] = 'whazzup';
        	if ($globalDebug) echo "Connect to whazzup source...\n";
            } else if (preg_match('/recentpireps/',$host)) {
        	$formats[$id] = 'pirepsjson';
        	if ($globalDebug) echo "Connect to pirepsjson source...\n";
            } else if (preg_match(':data.fr24.com/zones/fcgi/feed.js:',$host)) {
        	// Desactivated. Here only because it's possible. Do not use without fr24 rights.
        	//$formats[$id] = 'fr24json';
        	//if ($globalDebug) echo "Connect to fr24 source...\n";
            } else if (preg_match('/10001/',$host)) {
        	$formats[$id] = 'tsv';
        	if ($globalDebug) echo "Connect to tsv source...\n";
            }
        } else {
	    $hostport = explode(':',$host);
    	    $s = create_socket($hostport[0],$hostport[1], $errno, $errstr);
	    if ($s) {
    	        $sockets[$id] = $s;
		if (preg_match('/aprs/',$hostport[0])) {
			$formats[$id] = 'aprs';
			$aprs_connect = 0;
    	        } elseif ($hostport[1] == '10001') {
        	    $formats[$id] = 'tsv';
		} elseif ($hostport[1] == '30002') {
        	    $formats[$id] = 'raw';
		} else $formats[$id] = 'sbs';
		if ($globalDebug) echo 'Connection in progress to '.$host.'('.$formats[$id].')....'."\n";
            } else {
		if ($globalDebug) echo 'Connection failed to '.$host.' : '.$errno.' '.$errstr."\n";
    	    }
        }
    }
}

if (isset($globalSBS1Hosts)) {
    $hosts = $globalSBS1Hosts;
} else {
    if (!isset($globalSBS1Host)) {
        echo '$globalSBS1Host MUST be defined !';
        die;
    }
    $hosts = array($globalSBS1Host.':'.$globalSBS1Port);
}
$status = array();
$sockets = array();
$formats = array();
$time = time();
$timeout = $globalSBS1TimeOut;
$errno = '';
$errstr='';

// APRS Configuration
$aprs_connect = 0;
$aprs_keep = 240;
$aprs_last_tx = time();
if (isset($globalAPRSversion)) $aprs_version = $globalAPRSversion;
else $aprs_version = $globalName.' using FlightAirMap';
//else $aprs_version = 'Perl Example App';
if (isset($globalAPRSssid)) $aprs_ssid = $globalAPRSssid;
else $aprs_ssid = 'FAM';
//else $aprs_ssid = 'PerlEx';
if (isset($globalAPRSfilter)) $aprs_filter = $globalAPRSfilter;
else $aprs_filter =  'r/'.$globalCenterLatitude.'/'.$globalCenterLongitude.'/250.0';
$aprs_login = "user {$aprs_ssid} appid {$aprs_version} filter {$aprs_filter}\n";
echo $aprs_login;
$_ = $_SERVER['_'];
if (!isset($globalDaemon)) $globalDaemon = TRUE;
/* Initiate connections to all the hosts simultaneously */
connect_all($hosts);
// connected - lets do some work
if ($globalDebug) echo "Connected!\n";
sleep(1);
if ($globalDebug) echo "SCAN MODE \n\n";
if (!isset($globalCronEnd)) $globalCronEnd = 60;
$endtime = time()+$globalCronEnd;
$i = 1;
$tt = 0;

// Delete old ATC
if (!$globalDaemon && $globalIVAO) ATC::deleteAll();
while ($i > 0) {
    if (!$globalDaemon) $i = $endtime-time();
    if ($globalDaemon && $globalIVAO) ATC::deleteOldAtc();
    foreach ($formats as $id => $value) {
	if ($value == 'deltadbtxt') {
	    $buffer = Common::getData($hosts[$id]);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(',', $line);
	            $data = array();
	            $data['hex'] = $line[1]; // hex
	            $data['ident'] = $line[2]; // ident
	            $data['altitude'] = $line[3]; // altitude
	            $data['speed'] = $line[4]; // speed
	            $data['heading'] = $line[5]; // heading
	            $data['latitude'] = $line[6]; // lat
	            $data['longitude'] = $line[7]; // long
	            $data['verticalrate'] = ''; // vertical rate
	            $data['squawk'] = ''; // squawk
	            $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s');
		    $data['format_source'] = 'deltadbtxt';
    		    $SBS::add($data);
		    unset($data);
    		}
    	    }
	} elseif ($value == 'whazzup') {
	    $buffer = Common::getData($hosts[$id]);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(':', $line);
    		    if (count($line) > 43) {
			$data = array();
			$data['id'] = $line[1].'-'.$line[0];
			$data['pilot_id'] = $line[1];
			$data['pilot_name'] = $line[2];
			$data['hex'] = str_pad(dechex($line[1]),6,'000000',STR_PAD_LEFT);
			$data['ident'] = $line[0]; // ident
			if ($line[7] != '' && $line[7] != 0) $data['altitude'] = $line[7]; // altitude
			$data['speed'] = $line[8]; // speed
			$data['heading'] = $line[45]; // heading
			$data['latitude'] = $line[5]; // lat
	        	$data['longitude'] = $line[6]; // long
	        	$data['verticalrate'] = ''; // vertical rate
	        	$data['squawk'] = ''; // squawk
	        	$data['emergency'] = ''; // emergency
	        	$data['waypoints'] = $line[30];
			//$data['datetime'] = date('Y-m-d h:i:s');
			$data['datetime'] = date('Y-m-d H:i:s',strtotime($line[37])); // FIXME convert to correct format
		        $data['departure_airport_icao'] = $line[11];
		        $data['departure_airport_time'] = $line[22]; // FIXME put a :
		        $data['arrival_airport_icao'] = $line[13];
			$data['frequency'] = $line[4];
			$data['type'] = $line[18];
			$data['range'] = $line[19];
			$data['info'] = $line[35];
	    		//$data['arrival_airport_time'] = ;
	    		if ($line[9] != '') {
	    		    $aircraft_data = explode('/',$line[9]);
	    		    if (isset($aircraft_data[1])) {
	    			$data['aircraft_icao'] = $aircraft_data[1];
	    		    }
        		}
	    		$data['format_source'] = 'whazzup';
    			if ($line[3] == 'PILOT') $SBS::add($data);
			elseif ($line[3] == 'ATC') {
				//print_r($data);
				$data['info'] = str_replace('^&sect;','<br />',$data['info']);
				echo ATC::add($data['ident'],$data['frequency'],$data['latitude'],$data['longitude'],$data['range'],$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name']);
			}
    			unset($data);
    		    }
    		}
    	    }
    	} elseif ($value == 'aircraftlistjson') {
	    $buffer = Common::getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $line) {
	        $data = array();
	        $data['hex'] = $line['hex']; // hex
	        $data['ident'] = $line['flight']; // ident
	        $data['altitude'] = $line['altitude']; // altitude
	        $data['speed'] = $line['speed']; // speed
	        $data['heading'] = $line['track']; // heading
	        $data['latitude'] = $line['lat']; // lat
	        $data['longitude'] = $line['lon']; // long
	        $data['verticalrate'] = $line['vrt']; // verticale rate
	        $data['squawk'] = $line['squawk']; // squawk
	        $data['emergency'] = ''; // emergency
		$data['datetime'] = date('Y-m-d H:i:s');
		$SBS::add($data);
	    }
    	} elseif ($value == 'fr24json') {
	    $buffer = Common::getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $key => $line) {
		if ($key != 'full_count' && $key != 'version' && $key != 'stats') {
		    $data = array();
		    $data['hex'] = $line[0];
		    $data['ident'] = $line[16]; //$line[13]
	    	    $data['altitude'] = $line[4]; // altitude
	    	    $data['speed'] = $line[5]; // speed
	    	    $data['heading'] = $line[3]; // heading
	    	    $data['latitude'] = $line[1]; // lat
	    	    $data['longitude'] = $line[2]; // long
	    	    $data['verticalrate'] = $line[15]; // verticale rate
	    	    $data['squawk'] = $line[6]; // squawk
	    	    $data['aircraft_icao'] = $line[8];
	    	    $data['registration'] = $line[9];
		    $data['departure_airport_iata'] = $line[11];
		    $data['arrival_airport_iata'] = $line[12];
	    	    $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s'); //$line[10]
		    $SBS::add($data);
		}
	    }
    	} elseif ($value == 'pirepsjson') {
	    $buffer = Common::getData($hosts[$id]);
	    $all_data = json_decode(utf8_encode($buffer),true);
	    if (isset($all_data['pireps'])) {
	    foreach ($all_data['pireps'] as $line) {
	        $data = array();
	        $data['hex'] = str_pad(dechex($line['id']),6,'000000',STR_PAD_LEFT);
	        $data['ident'] = $line['callsign']; // ident
	        if (isset($line['pilotid'])) $data['pilot_id'] = $line['pilotid']; // pilot id
	        if (isset($line['name'])) $data['pilot_name'] = $line['name']; // pilot name
	        if (isset($line['alt'])) $data['altitude'] = $line['alt']; // altitude
	        if (isset($line['gs'])) $data['speed'] = $line['gs']; // speed
	        if (isset($line['heading'])) $data['heading'] = $line['heading']; // heading
	        if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
	        $data['latitude'] = $line['lat']; // lat
	        $data['longitude'] = $line['lon']; // long
	        //$data['verticalrate'] = $line['vrt']; // verticale rate
	        //$data['squawk'] = $line['squawk']; // squawk
	        //$data['emergency'] = ''; // emergency
	        if (isset($line['depicao'])) $data['departure_airport_icao'] = $line['depicao'];
	        if (isset($line['deptime'])) $data['departure_airport_time'] = $line['deptime'];
	        if (isset($line['arricao'])) $data['arrival_airport_icao'] = $line['arricao'];
    		//$data['arrival_airport_time'] = $line['arrtime'];
	    	if (isset($line['aircraft'])) $data['aircraft_icao'] = $line['aircraft'];
	    	if (isset($line['transponder'])) $data['squawk'] = $line['transponder'];
	    	if (isset($line['atis'])) $data['info'] = $line['atis'];
	    	else $data['info'] = '';
		$data['datetime'] = date('Y-m-d H:i:s');
    		if ($line['icon'] == 'plane') $SBS::add($data);
		elseif ($line['icon'] == 'ct') {
			$data['info'] = str_replace('^&sect;','<br />',$data['info']);
			echo ATC::add($data['ident'],'',$data['latitude'],$data['longitude'],'0',$data['info'],$data['datetime'],'',$data['pilot_id'],$data['pilot_name']);
		}

		unset($data);
	    }
	    }
    	} elseif ($value == 'phpvmacars') {
	    $buffer = Common::getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $line) {
	        $data = array();
	        //$data['id'] = $line['id']; // id not usable
	        $data['hex'] = substr(str_pad(bin2hex($line['flightnum']),6,'000000',STR_PAD_LEFT),-6); // hex
	        if (isset($line['pilotname'])) $data['pilot_name'] = $line['pilotname'];
	        if (isset($line['pilotid'])) $data['pilot_id'] = $line['pilotid'];
	        $data['ident'] = $line['flightnum']; // ident
	        $data['altitude'] = $line['alt']; // altitude
	        $data['speed'] = $line['gs']; // speed
	        $data['heading'] = $line['heading']; // heading
	        $data['latitude'] = $line['lat']; // lat
	        $data['longitude'] = $line['lng']; // long
	        $data['verticalrate'] = ''; // verticale rate
	        $data['squawk'] = ''; // squawk
	        $data['emergency'] = ''; // emergency
	        $data['datetime'] = $line['lastupdate'];
	        $data['departure_airport_icao'] = $line['depicao'];
	        $data['departure_airport_time'] = $line['deptime'];
	        $data['arrival_airport_icao'] = $line['arricao'];
    		$data['arrival_airport_time'] = $line['arrtime'];
    		$data['aircraft_icao'] = $line['aircraft'];
    		if (isset($line['route'])) $data['route'] = $line['route'];
	        $data['format_source'] = 'phpvmacars';
		$SBS::add($data);
		unset($data);
	    }
	} elseif ($value == 'sbs' || $value == 'tsv' || $value == 'raw' || $value == 'aprs') {
	    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();

	    //$read = array( $sockets[$id] );
	    $read = $sockets;
	    $n = @socket_select($read, $write = NULL, $e = NULL, $globalSBS1TimeOut);
	    if ($n > 0) {
		foreach ($read as $nb => $r) {
			$value = $formats[$nb];
        	    $buffer = socket_read($r, 3000,PHP_NORMAL_READ);
		    // lets play nice and handle signals such as ctrl-c/kill properly
		    //if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
		    $dataFound = false;
		    $error = false;
		    //$SBS::del();
		    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
		    // SBS format is CSV format
		    if ($buffer != '') {
			$tt = 0;
			if ($value == 'raw') {
			    // Not yet finished, no CRC checks
			    $hex = substr($buffer,1,-1);
			    $bin = gmp_strval( gmp_init($hex,16), 2);
			    //if (strlen($hex) == 28 && SBS::parityCheck(substr($bin,0,-24)) == substr($bin,-24)) {
			    if (strlen($hex) == 28) {
				$df = intval(substr($bin,0,5),2);
				$ca = intval(substr($bin,5,3),2);
				// Only support DF17 for now
				//if ($df == 17 || ($df == 18 && ($ca == 0 || $ca == 1 || $ca == 6))) {
				if ($df == 17) {
				    $icao = substr($hex,2,6);
				    $data['hex'] = $icao;
				    $tc = intval(substr($bin,32,5),2);
				    if ($tc >= 1 && $tc <= 4) {
					//callsign
					$csbin = substr($bin,40,56);
					$charset = str_split('#ABCDEFGHIJKLMNOPQRSTUVWXYZ#####_###############0123456789######');
					$cs = '';
					$cs .= $charset[intval(substr($csbin,0,6),2)];
					$cs .= $charset[intval(substr($csbin,6,6),2)];
					$cs .= $charset[intval(substr($csbin,12,6),2)];
					$cs .= $charset[intval(substr($csbin,18,6),2)];
					$cs .= $charset[intval(substr($csbin,24,6),2)];
					$cs .= $charset[intval(substr($csbin,30,6),2)];
					$cs .= $charset[intval(substr($csbin,36,6),2)];
					$cs .= $charset[intval(substr($csbin,42,6),2)];
					$cs = str_replace('_','',$cs);
					$cs = str_replace('#','',$cs);
					$callsign = $cs;
					$data['ident'] = $callsign;
				    } elseif ($tc >= 9 && $tc <= 18) {
					// Check Q-bit
					$q = substr($bin,47,1);
					if ($q) {
					    $n = intval(substr($bin,40,7).substr($bin,48,4),2);
					    $alt = $n*25-1000;
					    $data['altitude'] = $alt;
					}
					// Check odd/even flag
					$oe = substr($bin,53,1);
					//if ($oe) => odd else even
					//  131072 is 2^17 since CPR latitude and longitude are encoded in 17 bits.
					$cprlat = intval(substr($bin,54,17),2)/131072.0;
					$cprlon = intval(substr($bin,71,17),2)/131072.0;
					if ($oe == 0) $latlon[$icao] = array('latitude' => $cprlat,'longitude' => $cprlon,'created' => time());
					elseif (isset($latlon[$icao]) && (time() - $latlon[$icao]['created']) < 10) {
					    $cprlat_odd = $cprlat;
					    $cprlon_odd = $cprlon;
					    $cprlat_even = $latlon[$icao]['latitude'];
					    $cprlon_even = $latlon[$icao]['longitude'];
					
					    $j = 59*$cprlat_even-60*$cprlat_odd+0.5;
					    $lat_even = (360.0/60)*($j%60+$cprlat_even);
					    $lat_odd = (360.0/59)*($j%59+$cprlat_odd);
					    if ($lat_even >= 270) $lat_even = $lat_even - 360;
					    if ($lat_odd >= 270) $lat_odd = $lat_odd - 360;
					    // check latitude zone
					    if (SBS::cprNL($lat_even) == SBS::cprNL($lat_odd)) {
						if ($latlon[$icao]['created'] > time()) {
						    $ni = SBS::cprN($lat_even,0);
						    $m = floor($cprlon_even*(SBS::cprNL($lat_even)-1) - $cprlon_odd * SBS::cprNL($lat_even)+0.5);
						    $lon = (360.0/$ni)*($m%$ni+$cprlon_even);
						    $lat = $lat_even;
						    if ($lon > 180) $lon = $lon -360;
						    if ($lat > -91 && $lat < 91 && $lon > -181 && $lon < 181) {
							//if ($globalDebug) echo 'cs : '.$cs.' - hex : '.$hex.' - lat : '.$lat.' - lon : '.$lon;
							$data['latitude'] = $lat;
							$data['longitude'] = $lon;
						    }
						} else {
						    $ni = SBS::cprN($lat_odd,1);
						    $m = floor($cprlon_even*(SBS::cprNL($lat_odd)-1) - $cprlon_odd * SBS::cprNL($lat_odd)+0.5);
						    $lon = (360.0/$ni)*($m%$ni+$cprlon_odd);
						    $lat = $lat_odd;
						    if ($lon > 180) $lon = $lon -360;
						    if ($lat > -91 && $lat < 91 && $lon > -181 && $lon < 181) {
							//if ($globalDebug) echo 'icao : '.$icao.' - hex : '.$hex.' - lat : '.$lat.' - lon : '.$lon.' second'."\n";
							$data['latitude'] = $lat;
							$data['longitude'] = $lon;
						    }
						}
					    }
					    unset($latlon[$icao]);
					}
				    } elseif ($tc == 19) {
					// speed & heading
					$v_ew_dir = intval(substr($bin,45,1));
					$v_ew = intval(substr($bin,46,10),2);
					$v_ns_dir = intval(substr($bin,56,1));
					$v_ns = intval(substr($bin,57,10),2);
					if ($v_ew_dir) $v_ew = -1*$v_ew;
					if ($v_ns_dir) $v_ns = -1*$v_ns;
					$speed = sqrt($v_ns*$v_ns+$v_ew*$v_ew);
					$heading = atan2($v_ew,$v_ns)*360.0/(2*pi());
					if ($heading <0) $heading = $heading+360;
					$data['speed'] = $speed;
					$data['heading'] = $heading;
				    }
				    if (isset($data)) {
				        $data['datetime'] = date('Y-m-d H:i:s');
				        $data['format_source'] = 'raw';
    				        $SBS::add($data);
    				        unset($data);
				    }
				}
			    }
			} elseif ($value == 'tsv' || substr($buffer,0,4) == 'clock') {
			    $line = explode("\t", $buffer);
			    for($k = 0; $k < count($line); $k=$k+2) {
				$key = $line[$k];
			        $lined[$key] = $line[$k+1];
			    }
    			    if (count($lined) > 3) {
    				$data['hex'] = $lined['hexid'];
    				$data['datetime'] = date('Y-m-d H:i:s',strtotime($lined['clock']));;
    				if (isset($lined['ident'])) $data['ident'] = $lined['ident'];
    				if (isset($lined['lat']))$data['latitude'] = $lined['lat'];
    				if (isset($lined['lon']))$data['longitude'] = $lined['lon'];
    				if (isset($lined['speed']))$data['speed'] = $lined['speed'];
    				if (isset($lined['squawk']))$data['squawk'] = $lined['squawk'];
    				if (isset($lined['alt']))$data['altitude'] = $lined['alt'];
    				if (isset($lined['heading']))$data['heading'] = $lined['heading'];
    				$data['format_source'] = 'tsv';
    				$SBS::add($data);
    				unset($lined);
    				unset($data);
    			    } else $error = true;
			} elseif ($value == 'aprs') {
				if ($aprs_connect == 0) {
					$send = @ socket_send( $r  , $aprs_login , strlen($aprs_login) , 0 );
					$aprs_connect = 1;
				}
				if ( $aprs_keep>60 && time() - $aprs_last_tx > $aprs_keep ) {
					$aprs_last_tx = time();
					$data = "# Keep alive";
					$send = @ socket_send( $r  , $data , strlen($data) , 0 );
				}
				if (substr($buffer,0,1) != '#') {
					$line = $APRS::parse($buffer);
					if (is_array($line) && isset($line['address']) && $line['address'] != '' && isset($line['ident'])) {
						$data = array();
						$data['hex'] = $line['address'];
						$data['datetime'] = date('Y-m-d H:i:s',$line['timestamp']);
						$data['ident'] = $line['ident'];
						$data['latitude'] = $line['latitude'];
						$data['longitude'] = $line['longitude'];
						//$data['verticalrate'] = $line[16];
						if (isset($line['speed'])) $data['speed'] = $line['speed'];
						else $data['speed'] = 0;
						$data['altitude'] = $line['altitude'];
						if (isset($line['course'])) $data['heading'] = $line['course'];
						else $data['heading'] = 0;
						$data['format_source'] = 'aprs';
						//print_r($data);
						$send = $SBS::add($data);
						unset($data);
					} elseif ($line == false && $globalDebug) echo 'Ignored ('.$buffer.")\n";
					elseif ($globalDebug) echo '!! Failed : '.$buffer."!!\n";
				}
			} else {
			    $line = explode(',', $buffer);
    			    if (count($line) > 20) {
    			    	$data['hex'] = $line[4];
    				$data['datetime'] = $line[8].' '.$line[7];
    				$data['ident'] = trim($line[10]);
    				$data['latitude'] = $line[14];
    				$data['longitude'] = $line[15];
    				$data['verticalrate'] = $line[16];
    				$data['emergency'] = $line[20];
    				$data['speed'] = $line[12];
    				$data['squawk'] = $line[17];
    				$data['altitude'] = $line[11];
    				$data['heading'] = $line[13];
    				$data['format_source'] = 'sbs';
    				$send = $SBS::add($data);
				//$send = $data;
    				unset($data);
    			    } else $error = true;
    			
				if ($error) {
				if (count($line) > 1 && ($line[0] == 'STA' || $line[0] == 'AIR' || $line[0] == 'SEL' || $line[0] == 'ID' || $line[0] == 'CLK')) { 
					if ($globalDebug) echo "Not a message. Ignoring... \n";
				} else {
					if ($globalDebug) echo "Wrong line format. Ignoring... \n";
					if ($globalDebug) {
						echo $buffer;
						print_r($line);
					}
					socket_close($r);
					if ($globalDebug) echo "Reconnect after an error...\n";
					connect_all($hosts);
				}
				}
			
			}
			// Sleep for xxx microseconds
			if (isset($globalSBSSleep)) usleep($globalSBSSleep);
		    } else {
			$tt++;
			if ($tt > 5) {
			    if ($globalDebug)echo "ERROR : Reconnect...";
			    @socket_close($r);
			    sleep(2);
			    connect_all($hosts);
			    break;
			    $tt = 0;
			}
		    }
		}
	    } else {
		$error = socket_strerror(socket_last_error());
		if ($globalDebug) echo "ERROR : socket_select give this error ".$error . "\n";
		if (($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY) || time() - $time >= $timeout) {
			if (isset($globalDebug)) echo "Restarting...\n";
			// Restart the script if possible
			if (is_array($sockets)) {
			    if ($globalDebug) echo "Shutdown all sockets...";
			    foreach ($sockets as $sock) {
				@socket_shutdown($sock,2);
				@socket_close($sock);
			    }
			}
			    if ($globalDebug) echo "Restart all connections...";
			    sleep(2);
			    $time = time();
			    connect_all($hosts);

		}
	    }
	}
    }
}

?>
