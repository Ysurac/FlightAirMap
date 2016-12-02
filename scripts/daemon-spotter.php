#!/usr/bin/php
<?php
/**
* This script is used to retrieve message from SBS source like Dump1090, Radarcape,.. or from phpvms, wazzup files,...
* This script can be used as cron job with $globalDaemon = FALSE
*/

require_once(dirname(__FILE__).'/../require/class.SpotterImport.php');
require_once(dirname(__FILE__).'/../require/class.SpotterServer.php');
//require_once(dirname(__FILE__).'/../require/class.APRS.php');
require_once(dirname(__FILE__).'/../require/class.ATC.php');
require_once(dirname(__FILE__).'/../require/class.ACARS.php');
require_once(dirname(__FILE__).'/../require/class.SBS.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');

if (!isset($globalDebug)) $globalDebug = FALSE;

// Check if schema is at latest version
$Connection = new Connection();
if ($Connection->latest() === false) {
    echo "You MUST update to latest schema. Run install/index.php";
    exit();
}
if (PHP_SAPI != 'cli') {
    echo "This script MUST be called from console, not a web browser.";
//    exit();
}

// This is to be compatible with old version of settings.php
if (!isset($globalSources)) {
    if (isset($globalSBS1Hosts)) {
        //$hosts = $globalSBS1Hosts;
        foreach ($globalSBS1Hosts as $host) {
	    $globalSources[] = array('host' => $host);
    	}
    } else {
        if (!isset($globalSBS1Host)) {
	    echo '$globalSources MUST be defined !';
	    die;
	}
	//$hosts = array($globalSBS1Host.':'.$globalSBS1Port);
	$globalSources[] = array('host' => $globalSBS1Host,'port' => $globalSBS1Port);
    }
}

$options = getopt('s::',array('source::','server','idsource::'));
//if (isset($options['s'])) $hosts = array($options['s']);
//elseif (isset($options['source'])) $hosts = array($options['source']);
if (isset($options['s'])) {
    $globalSources = array();
    $globalSources[] = array('host' => $options['s']);
} elseif (isset($options['source'])) {
    $globalSources = array();
    $globalSources[] = array('host' => $options['source']);
}
if (isset($options['server'])) $globalServer = TRUE;
if (isset($options['idsource'])) $id_source = $options['idsource'];
else $id_source = 1;
if (isset($globalServer) && $globalServer) {
    if ($globalDebug) echo "Using Server Mode\n";
    $SI=new SpotterServer();
} else $SI=new SpotterImport($Connection->db);
//$APRS=new APRS($Connection->db);
$SBS=new SBS();
$ACARS=new ACARS($Connection->db);
$Common=new Common();
date_default_timezone_set('UTC');
//$servertz = system('date +%Z');
// signal handler - playing nice with sockets and dump1090
if (function_exists('pcntl_fork')) {
    pcntl_signal(SIGINT,  function() {
        global $sockets;
        echo "\n\nctrl-c or kill signal received. Tidying up ... ";
        die("Bye!\n");
    });
    pcntl_signal_dispatch();
}

// let's try and connect
if ($globalDebug) echo "Connecting...\n";
$use_aprs = false;
$aprs_full = false;

function create_socket($host, $port, &$errno, &$errstr) {
    $ip = gethostbyname($host);
    $s = socket_create(AF_INET, SOCK_STREAM, 0);
    $r = @socket_connect($s, $ip, $port);
    if (!socket_set_nonblock($s)) echo "Unable to set nonblock on socket\n";
    if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
        return $s;
    }
    $errno = socket_last_error($s);
    $errstr = socket_strerror($errno);
    socket_close($s);
    return false;
}

function create_socket_udp($host, $port, &$errno, &$errstr) {
    echo "UDP !!";
    $ip = gethostbyname($host);
    $s = socket_create(AF_INET, SOCK_DGRAM, 0);
    $r = @socket_bind($s, $ip, $port);
    if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
        return $s;
    }
    $errno = socket_last_error($s);
    $errstr = socket_strerror($errno);
    socket_close($s);
    return false;
}

function connect_all($hosts) {
    //global $sockets, $formats, $globalDebug,$aprs_connect,$last_exec, $globalSourcesRights, $use_aprs;
    global $sockets, $globalSources, $globalDebug,$aprs_connect,$last_exec, $globalSourcesRights, $use_aprs;
    if ($globalDebug) echo 'Connect to all...'."\n";
    foreach ($hosts as $id => $value) {
	$host = $value['host'];
	$globalSources[$id]['last_exec'] = 0;
	// Here we check type of source(s)
	if (filter_var($host,FILTER_VALIDATE_URL) && (!isset($globalSources[$id]['format']) || strtolower($globalSources[$id]['format']) == 'auto')) {
            if (preg_match('/deltadb.txt$/i',$host)) {
        	//$formats[$id] = 'deltadbtxt';
        	$globalSources[$id]['format'] = 'deltadbtxt';
        	//$last_exec['deltadbtxt'] = 0;
        	if ($globalDebug) echo "Connect to deltadb source (".$host.")...\n";
            } else if (preg_match('/vatsim-data.txt$/i',$host)) {
        	//$formats[$id] = 'vatsimtxt';
        	$globalSources[$id]['format'] = 'vatsimtxt';
        	//$last_exec['vatsimtxt'] = 0;
        	if ($globalDebug) echo "Connect to vatsim source (".$host.")...\n";
    	    } else if (preg_match('/aircraftlist.json$/i',$host)) {
        	//$formats[$id] = 'aircraftlistjson';
        	$globalSources[$id]['format'] = 'aircraftlistjson';
        	//$last_exec['aircraftlistjson'] = 0;
        	if ($globalDebug) echo "Connect to aircraftlist.json source (".$host.")...\n";
    	    } else if (preg_match('/opensky/i',$host)) {
        	//$formats[$id] = 'aircraftlistjson';
        	$globalSources[$id]['format'] = 'opensky';
        	//$last_exec['aircraftlistjson'] = 0;
        	if ($globalDebug) echo "Connect to opensky source (".$host.")...\n";
    	    } else if (preg_match('/radarvirtuel.com\/file.json$/i',$host)) {
        	//$formats[$id] = 'radarvirtueljson';
        	$globalSources[$id]['format'] = 'radarvirtueljson';
        	//$last_exec['radarvirtueljson'] = 0;
        	if ($globalDebug) echo "Connect to radarvirtuel.com/file.json source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
    	    } else if (preg_match('/planeUpdateFAA.php$/i',$host)) {
        	//$formats[$id] = 'planeupdatefaa';
        	$globalSources[$id]['format'] = 'planeupdatefaa';
        	//$last_exec['planeupdatefaa'] = 0;
        	if ($globalDebug) echo "Connect to planeUpdateFAA.php source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
            } else if (preg_match('/\/action.php\/acars\/data$/i',$host)) {
        	//$formats[$id] = 'phpvmacars';
        	$globalSources[$id]['format'] = 'phpvmacars';
        	//$last_exec['phpvmacars'] = 0;
        	if ($globalDebug) echo "Connect to phpvmacars source (".$host.")...\n";
            } else if (preg_match('/VAM-json.php$/i',$host)) {
        	//$formats[$id] = 'phpvmacars';
        	$globalSources[$id]['format'] = 'vam';
        	if ($globalDebug) echo "Connect to Vam source (".$host.")...\n";
            } else if (preg_match('/whazzup/i',$host)) {
        	//$formats[$id] = 'whazzup';
        	$globalSources[$id]['format'] = 'whazzup';
        	//$last_exec['whazzup'] = 0;
        	if ($globalDebug) echo "Connect to whazzup source (".$host.")...\n";
            } else if (preg_match('/recentpireps/i',$host)) {
        	//$formats[$id] = 'pirepsjson';
        	$globalSources[$id]['format'] = 'pirepsjson';
        	//$last_exec['pirepsjson'] = 0;
        	if ($globalDebug) echo "Connect to pirepsjson source (".$host.")...\n";
            } else if (preg_match(':data.fr24.com/zones/fcgi/feed.js:i',$host)) {
        	//$formats[$id] = 'fr24json';
        	$globalSources[$id]['format'] = 'fr24json';
        	//$last_exec['fr24json'] = 0;
        	if ($globalDebug) echo "Connect to fr24 source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
            //} else if (preg_match('/10001/',$host)) {
            } else if (preg_match('/10001/',$host) || (isset($globalSources[$id]['port']) && $globalSources[$id]['port'] == '10001')) {
        	//$formats[$id] = 'tsv';
        	$globalSources[$id]['format'] = 'tsv';
        	if ($globalDebug) echo "Connect to tsv source (".$host.")...\n";
            }
        } elseif (filter_var($host,FILTER_VALIDATE_URL)) {
        	if ($globalDebug) echo "Connect to ".$globalSources[$id]['format']." source (".$host.")...\n";
        } elseif (!filter_var($host,FILTER_VALIDATE_URL)) {
	    $hostport = explode(':',$host);
	    if (isset($hostport[1])) {
		$port = $hostport[1];
		$hostn = $hostport[0];
	    } else {
		$port = $globalSources[$id]['port'];
		$hostn = $globalSources[$id]['host'];
	    }
	    if (!isset($globalSources[$id]['format']) || ($globalSources[$id]['format'] != 'acars' && $globalSources[$id]['format'] != 'flightgearsp')) {
        	$s = create_socket($hostn,$port, $errno, $errstr);
    	    } else {
        	$s = create_socket_udp($hostn,$port, $errno, $errstr);
	    }
	    if ($s) {
    	        $sockets[$id] = $s;
    	        if (!isset($globalSources[$id]['format']) || strtolower($globalSources[$id]['format']) == 'auto') {
		    if (preg_match('/aprs/',$hostn)) {
			//$formats[$id] = 'aprs';
			$globalSources[$id]['format'] = 'aprs';
			//$aprs_connect = 0;
			//$use_aprs = true;
    		    } elseif ($port == '10001') {
        		//$formats[$id] = 'tsv';
        		$globalSources[$id]['format'] = 'tsv';
		    } elseif ($port == '30002') {
        		//$formats[$id] = 'raw';
        		$globalSources[$id]['format'] = 'raw';
		    } elseif ($port == '5001') {
        		//$formats[$id] = 'raw';
        		$globalSources[$id]['format'] = 'flightgearmp';
		    } elseif ($port == '30005') {
			// Not yet supported
        		//$formats[$id] = 'beast';
        		$globalSources[$id]['format'] = 'beast';
		    //} else $formats[$id] = 'sbs';
		    } else $globalSources[$id]['format'] = 'sbs';
		    //if ($globalDebug) echo 'Connection in progress to '.$host.'('.$formats[$id].')....'."\n";
		}
		if ($globalDebug) echo 'Connection in progress to '.$hostn.':'.$port.' ('.$globalSources[$id]['format'].')....'."\n";
            } else {
		if ($globalDebug) echo 'Connection failed to '.$hostn.':'.$port.' : '.$errno.' '.$errstr."\n";
    	    }
        }
    }
}
if (!isset($globalMinFetch)) $globalMinFetch = 15;

// Initialize all
$status = array();
$sockets = array();
$formats = array();
$last_exec = array();
$time = time();
if (isset($globalSourcesTimeout)) $timeout = $globalSourcesTimeOut;
else if (isset($globalSBS1TimeOut)) $timeout = $globalSBS1TimeOut;
else $timeout = 20;
$errno = '';
$errstr='';

if (!isset($globalDaemon)) $globalDaemon = TRUE;
/* Initiate connections to all the hosts simultaneously */
//connect_all($hosts);
//connect_all($globalSources);

// APRS Configuration
if (!is_array($globalSources)) {
	echo '$globalSources in require/settings.php MUST be an array';
	die;
}
foreach ($globalSources as $key => $source) {
    if (!isset($source['format'])) {
        $globalSources[$key]['format'] = 'auto';
    }
}
connect_all($globalSources);
foreach ($globalSources as $key => $source) {
    if (isset($source['format']) && $source['format'] == 'aprs') {
	$aprs_connect = 0;
	$use_aprs = true;
	if (isset($source['port']) && $source['port'] == '10152') $aprs_full = true;
	break;
    }
}

if ($use_aprs) {
	require_once(dirname(__FILE__).'/../require/class.APRS.php');
	$APRS=new APRS();
	$aprs_connect = 0;
	$aprs_keep = 120;
	$aprs_last_tx = time();
	if (isset($globalAPRSversion)) $aprs_version = $globalAPRSversion;
	else $aprs_version = $globalName.' using FlightAirMap';
	//else $aprs_version = 'Perl Example App';
	if (isset($globalAPRSssid)) $aprs_ssid = $globalAPRSssid;
	else $aprs_ssid = 'FAM';
	//else $aprs_ssid = 'PerlEx';
	if (isset($globalAPRSfilter)) $aprs_filter = $globalAPRSfilter;
	else $aprs_filter =  'r/'.$globalCenterLatitude.'/'.$globalCenterLongitude.'/250.0';
	if ($aprs_full) $aprs_filter = '';
	if ($aprs_filter != '') $aprs_login = "user {$aprs_ssid} appid {$aprs_version} filter {$aprs_filter}\n";
	else $aprs_login = "user {$aprs_ssid} appid {$aprs_version}\n";
}

// connected - lets do some work
if ($globalDebug) echo "Connected!\n";
sleep(1);
if ($globalDebug) echo "SCAN MODE \n\n";
if (!isset($globalCronEnd)) $globalCronEnd = 60;
$endtime = time()+$globalCronEnd;
$i = 1;
$tt = array();
// Delete all ATC
if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
	$ATC=new ATC($Connection->db);
}
if (!$globalDaemon && ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
	$ATC->deleteAll();
}

// Infinite loop if daemon, else work for time defined in $globalCronEnd or only one time.
while ($i > 0) {
    if (!$globalDaemon) $i = $endtime-time();
    // Delete old ATC
    if ($globalDaemon && ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
	if ($globalDebug) echo 'Delete old ATC...'."\n";
        $ATC->deleteOldATC();
    }
    
    if (count($last_exec) > 0) {
	$max = $globalMinFetch;
	foreach ($last_exec as $last) {
	    if ((time() - $last['last']) < $max) $max = time() - $last['last'];
	}
	if ($max != $globalMinFetch) {
	    if ($globalDebug) echo 'Sleeping...'."\n";
	    sleep($globalMinFetch-$max+2);
	}
    }

    
    //foreach ($formats as $id => $value) {
    foreach ($globalSources as $id => $value) {
	if (!isset($last_exec[$id]['last'])) $last_exec[$id]['last'] = 0;
	if ($value['format'] == 'deltadbtxt' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    $buffer = $Common->getData($value['host']);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '' && count($line) > 7) {
    		    $line = explode(',', $line);
	            $data = array();
	            $data['hex'] = $line[1]; // hex
	            $data['ident'] = $line[2]; // ident
	            if (isset($line[3])) $data['altitude'] = $line[3]; // altitude
	            if (isset($line[4])) $data['speed'] = $line[4]; // speed
	            if (isset($line[5])) $data['heading'] = $line[5]; // heading
	            if (isset($line[6])) $data['latitude'] = $line[6]; // lat
	            if (isset($line[7])) $data['longitude'] = $line[7]; // long
	            $data['verticalrate'] = ''; // vertical rate
	            //if (isset($line[9])) $data['squawk'] = $line[9]; // squawk
	            $data['emergency'] = ''; // emergency
		    $data['datetime'] = date('Y-m-d H:i:s');
		    $data['format_source'] = 'deltadbtxt';
    		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    if (isset($value['sourcestats'])) $data['sourcestats'] = $value['sourcestats'];
    		    $SI->add($data);
		    unset($data);
    		}
    	    }
    	    $last_exec[$id]['last'] = time();
	//} elseif (($value == 'whazzup' && (time() - $last_exec['whazzup'] > $globalMinFetch)) || ($value == 'vatsimtxt' && (time() - $last_exec['vatsimtxt'] > $globalMinFetch))) {
	} elseif (($value['format'] == 'whazzup' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) || ($value['format'] == 'vatsimtxt' && (time() - $last_exec[$id]['last'] > $globalMinFetch))) {
	    //$buffer = $Common->getData($hosts[$id]);
	    $buffer = $Common->getData($value['host']);
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(':', $line);
    		    if (count($line) > 30 && $line[0] != 'callsign') {
			$data = array();
			$data['id'] = $line[1].'-'.$line[0];
			$data['pilot_id'] = $line[1];
			$data['pilot_name'] = $line[2];
			$data['hex'] = str_pad(dechex($line[1]),6,'000000',STR_PAD_LEFT);
			$data['ident'] = $line[0]; // ident
			if ($line[7] != '' && $line[7] != 0) $data['altitude'] = $line[7]; // altitude
			$data['speed'] = $line[8]; // speed
			if (isset($line[45])) $data['heading'] = $line[45]; // heading
			elseif (isset($line[38])) $data['heading'] = $line[38]; // heading
			$data['latitude'] = $line[5]; // lat
	        	$data['longitude'] = $line[6]; // long
	        	$data['verticalrate'] = ''; // vertical rate
	        	$data['squawk'] = ''; // squawk
	        	$data['emergency'] = ''; // emergency
	        	$data['waypoints'] = $line[30];
			$data['datetime'] = date('Y-m-d H:i:s');
			//$data['datetime'] = date('Y-m-d H:i:s',strtotime($line[37]));
			if (isset($line[37])) $data['last_update'] = $line[37];
		        $data['departure_airport_icao'] = $line[11];
		        $data['departure_airport_time'] = rtrim(chunk_split($line[22],2,':'),':');
		        $data['arrival_airport_icao'] = $line[13];
			$data['frequency'] = $line[4];
			$data['type'] = $line[18];
			$data['range'] = $line[19];
			if (isset($line[35])) $data['info'] = $line[35];
    			$data['id_source'] = $id_source;
	    		//$data['arrival_airport_time'] = ;
	    		if ($line[9] != '') {
	    		    $aircraft_data = explode('/',$line[9]);
	    		    if (isset($aircraft_data[1])) {
	    			$data['aircraft_icao'] = $aircraft_data[1];
	    		    }
        		}
	    		/*
	    		if ($value == 'whazzup') $data['format_source'] = 'whazzup';
	    		elseif ($value == 'vatsimtxt') $data['format_source'] = 'vatsimtxt';
	    		*/
	    		$data['format_source'] = $value['format'];
			if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
    			if ($line[3] == 'PILOT') $SI->add($data);
			elseif ($line[3] == 'ATC') {
				//print_r($data);
				$data['info'] = str_replace('^&sect;','<br />',$data['info']);
				$data['info'] = str_replace('&amp;sect;','',$data['info']);
				$typec = substr($data['ident'],-3);
				if ($typec == 'APP') $data['type'] = 'Approach';
				elseif ($typec == 'TWR') $data['type'] = 'Tower';
				elseif ($typec == 'OBS') $data['type'] = 'Observer';
				elseif ($typec == 'GND') $data['type'] = 'Ground';
				elseif ($typec == 'DEL') $data['type'] = 'Delivery';
				elseif ($typec == 'DEP') $data['type'] = 'Departure';
				elseif ($typec == 'FSS') $data['type'] = 'Flight Service Station';
				elseif ($typec == 'CTR') $data['type'] = 'Control Radar or Centre';
				elseif ($data['type'] == '') $data['type'] = 'Observer';
				if (!isset($data['source_name'])) $data['source_name'] = '';
				echo $ATC->add($data['ident'],$data['frequency'],$data['latitude'],$data['longitude'],$data['range'],$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name'],$data['format_source'],$data['source_name']);
			}
    			unset($data);
    		    }
    		}
    	    }
    	    //if ($value == 'whazzup') $last_exec['whazzup'] = time();
    	    //elseif ($value == 'vatsimtxt') $last_exec['vatsimtxt'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'aircraftlistjson' && (time() - $last_exec['aircraftlistjson'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'aircraftlistjson' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    $buffer = $Common->getData($value['host'],'get','','','','','20');
	    if ($buffer != '') {
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['acList'])) {
		foreach ($all_data['acList'] as $line) {
		    $data = array();
		    $data['hex'] = $line['Icao']; // hex
		    if (isset($line['Call'])) $data['ident'] = $line['Call']; // ident
		    if (isset($line['Alt'])) $data['altitude'] = $line['Alt']; // altitude
		    if (isset($line['Spd'])) $data['speed'] = $line['Spd']; // speed
		    if (isset($line['Trak'])) $data['heading'] = $line['Trak']; // heading
		    if (isset($line['Lat'])) $data['latitude'] = $line['Lat']; // lat
		    if (isset($line['Long'])) $data['longitude'] = $line['Long']; // long
		    //$data['verticalrate'] = $line['']; // verticale rate
		    if (isset($line['Sqk'])) $data['squawk'] = $line['Sqk']; // squawk
		    $data['emergency'] = ''; // emergency
		    if (isset($line['Reg'])) $data['registration'] = $line['Reg'];
		    /*
		    if (isset($line['PosTime'])) $data['datetime'] = date('Y-m-d H:i:s',$line['PosTime']/1000);
		    else $data['datetime'] = date('Y-m-d H:i:s');
		    */
		    $data['datetime'] = date('Y-m-d H:i:s');
		    if (isset($line['Type'])) $data['aircraft_icao'] = $line['Type'];
	    	    $data['format_source'] = 'aircraftlistjson';
		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    if (isset($data['datetime'])) $SI->add($data);
		    unset($data);
		}
	    } else {
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
	    	    $data['format_source'] = 'aircraftlistjson';
    		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    $SI->add($data);
		    unset($data);
		}
	    }
	    }
    	    //$last_exec['aircraftlistjson'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'planeupdatefaa' && (time() - $last_exec['planeupdatefaa'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'planeupdatefaa' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    $buffer = $Common->getData($value['host']);
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['planes'])) {
		foreach ($all_data['planes'] as $key => $line) {
		    $data = array();
		    $data['hex'] = $key; // hex
		    $data['ident'] = $line[3]; // ident
		    $data['altitude'] = $line[6]; // altitude
		    $data['speed'] = $line[8]; // speed
		    $data['heading'] = $line[7]; // heading
		    $data['latitude'] = $line[4]; // lat
		    $data['longitude'] = $line[5]; // long
		    //$data['verticalrate'] = $line[]; // verticale rate
		    $data['squawk'] = $line[10]; // squawk
		    $data['emergency'] = ''; // emergency
		    $data['registration'] = $line[2];
		    $data['aircraft_icao'] = $line[0];
		    $deparr = explode('-',$line[1]);
		    if (count($deparr) == 2) {
			$data['departure_airport_icao'] = $deparr[0];
			$data['arrival_airport_icao'] = $deparr[1];
		    }
		    $data['datetime'] = date('Y-m-d H:i:s',$line[9]);
	    	    $data['format_source'] = 'planeupdatefaa';
    		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    $SI->add($data);
		    unset($data);
		}
	    }
    	    //$last_exec['planeupdatefaa'] = time();
    	    $last_exec[$id]['last'] = time();
    	} elseif ($value['format'] == 'opensky' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    $buffer = $Common->getData($value['host']);
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['states'])) {
		foreach ($all_data['states'] as $key => $line) {
		    $data = array();
		    $data['hex'] = $line[0]; // hex
		    $data['ident'] = trim($line[1]); // ident
		    $data['altitude'] = round($line[7]*3.28084); // altitude
		    $data['speed'] = round($line[9]*1.94384); // speed
		    $data['heading'] = round($line[10]); // heading
		    $data['latitude'] = $line[5]; // lat
		    $data['longitude'] = $line[6]; // long
		    $data['verticalrate'] = $line[11]; // verticale rate
		    //$data['squawk'] = $line[10]; // squawk
		    //$data['emergency'] = ''; // emergency
		    //$data['registration'] = $line[2];
		    //$data['aircraft_icao'] = $line[0];
		    $data['datetime'] = date('Y-m-d H:i:s',$line[3]);
	    	    $data['format_source'] = 'opensky';
    		    $data['id_source'] = $id_source;
		    $SI->add($data);
		    unset($data);
		}
	    }
    	    //$last_exec['planeupdatefaa'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'fr24json' && (time() - $last_exec['fr24json'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'fr24json' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    $buffer = $Common->getData($value['host']);
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
	    	    $data['format_source'] = 'fr24json';
    		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    $SI->add($data);
		    unset($data);
		}
	    }
    	    //$last_exec['fr24json'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'radarvirtueljson' && (time() - $last_exec['radarvirtueljson'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'radarvirtueljson' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id],'get','','','','','150');
	    $buffer = $Common->getData($value['host'],'get','','','','','150');
	    //echo $buffer;
	    $buffer = str_replace(array("\n","\r"),"",$buffer);
	    $buffer = preg_replace('/,"num":(.+)/','}',$buffer);
	    $all_data = json_decode($buffer,true);
	    if (json_last_error() != JSON_ERROR_NONE) {
		die(json_last_error_msg());
	    }
	    if (isset($all_data['mrkrs'])) {
		foreach ($all_data['mrkrs'] as $key => $line) {
		    if (isset($line['inf'])) {
			$data = array();
			$data['hex'] = $line['inf']['ia'];
			if (isset($line['inf']['cs'])) $data['ident'] = $line['inf']['cs']; //$line[13]
	    		$data['altitude'] = round($line['inf']['al']*3.28084); // altitude
	    		if (isset($line['inf']['gs'])) $data['speed'] = round($line['inf']['gs']*0.539957); // speed
	    		if (isset($line['inf']['tr'])) $data['heading'] = $line['inf']['tr']; // heading
	    		$data['latitude'] = $line['pt'][0]; // lat
	    		$data['longitude'] = $line['pt'][1]; // long
	    		//if (isset($line['inf']['vs'])) $data['verticalrate'] = $line['inf']['vs']; // verticale rate
	    		if (isset($line['inf']['sq'])) $data['squawk'] = $line['inf']['sq']; // squawk
	    		//$data['aircraft_icao'] = $line[8];
	    		if (isset($line['inf']['rc'])) $data['registration'] = $line['inf']['rc'];
			//$data['departure_airport_iata'] = $line[11];
			//$data['arrival_airport_iata'] = $line[12];
	    		//$data['emergency'] = ''; // emergency
			$data['datetime'] = date('Y-m-d H:i:s',$line['inf']['dt']); //$line[10]
	    		$data['format_source'] = 'radarvirtueljson';
    			$data['id_source'] = $id_source;
			if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
			$SI->add($data);
			unset($data);
		    }
		}
	    }
    	    //$last_exec['radarvirtueljson'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'pirepsjson' && (time() - $last_exec['pirepsjson'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'pirepsjson' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    $buffer = $Common->getData($value['host'].'?'.time());
	    $all_data = json_decode(utf8_encode($buffer),true);
	    
	    if (isset($all_data['pireps'])) {
	        foreach ($all_data['pireps'] as $line) {
		    $data = array();
		    $data['id'] = $line['id'];
		    $data['hex'] = substr(str_pad(dechex($line['id']),6,'000000',STR_PAD_LEFT),0,6);
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
		    $data['format_source'] = 'pireps';
    		    $data['id_source'] = $id_source;
		    $data['datetime'] = date('Y-m-d H:i:s');
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    if ($line['icon'] == 'plane') {
			$SI->add($data);
		    //    print_r($data);
    		    } elseif ($line['icon'] == 'ct') {
			$data['info'] = str_replace('^&sect;','<br />',$data['info']);
			$data['info'] = str_replace('&amp;sect;','',$data['info']);
			$typec = substr($data['ident'],-3);
			$data['type'] = '';
			if ($typec == 'APP') $data['type'] = 'Approach';
			elseif ($typec == 'TWR') $data['type'] = 'Tower';
			elseif ($typec == 'OBS') $data['type'] = 'Observer';
			elseif ($typec == 'GND') $data['type'] = 'Ground';
			elseif ($typec == 'DEL') $data['type'] = 'Delivery';
			elseif ($typec == 'DEP') $data['type'] = 'Departure';
			elseif ($typec == 'FSS') $data['type'] = 'Flight Service Station';
			elseif ($typec == 'CTR') $data['type'] = 'Control Radar or Centre';
			else $data['type'] = 'Observer';
			echo $ATC->add($data['ident'],'',$data['latitude'],$data['longitude'],'0',$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name'],$data['format_source']);
		    }
		    unset($data);
		}
	    }
    	    //$last_exec['pirepsjson'] = time();
    	    $last_exec[$id]['last'] = time();
    	//} elseif ($value == 'phpvmacars' && (time() - $last_exec['phpvmacars'] > $globalMinFetch)) {
    	} elseif ($value['format'] == 'phpvmacars' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    if ($globalDebug) echo 'Get Data...'."\n";
	    $buffer = $Common->getData($value['host']);
	    $all_data = json_decode($buffer,true);
	    if ($buffer != '' && is_array($all_data)) {
		foreach ($all_data as $line) {
	    	    $data = array();
	    	    //$data['id'] = $line['id']; // id not usable
	    	    if (isset($line['pilotid'])) $data['id'] = $line['pilotid'].$line['flightnum'];
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
	    	    //$data['datetime'] = $line['lastupdate'];
	    	    $data['last_update'] = $line['lastupdate'];
		    $data['datetime'] = date('Y-m-d H:i:s');
	    	    $data['departure_airport_icao'] = $line['depicao'];
	    	    $data['departure_airport_time'] = $line['deptime'];
	    	    $data['arrival_airport_icao'] = $line['arricao'];
    		    $data['arrival_airport_time'] = $line['arrtime'];
    		    $data['registration'] = $line['aircraft'];
		    if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
		    if (isset($line['aircraftname'])) {
			$line['aircraftname'] = strtoupper($line['aircraftname']);
			$line['aircraftname'] = str_replace('BOEING ','B',$line['aircraftname']);
	    		$aircraft_data = explode('-',$line['aircraftname']);
	    		if (isset($aircraft_data[1]) && strlen($aircraft_data[0]) < 5) $data['aircraft_icao'] = $aircraft_data[0];
	    		elseif (isset($aircraft_data[1]) && strlen($aircraft_data[1]) < 5) $data['aircraft_icao'] = $aircraft_data[1];
	    		else {
	    		    $aircraft_data = explode(' ',$line['aircraftname']);
	    		    if (isset($aircraft_data[1])) $data['aircraft_icao'] = $aircraft_data[1];
	    		    else $data['aircraft_icao'] = $line['aircraftname'];
	    		}
	    	    }
    		    if (isset($line['route'])) $data['waypoints'] = $line['route'];
    		    $data['id_source'] = $id_source;
	    	    $data['format_source'] = 'phpvmacars';
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    $SI->add($data);
		    unset($data);
		}
		if ($globalDebug) echo 'No more data...'."\n";
		unset($buffer);
		unset($all_data);
	    }
    	    //$last_exec['phpvmacars'] = time();
    	    $last_exec[$id]['last'] = time();
    	} elseif ($value['format'] == 'vam' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    if ($globalDebug) echo 'Get Data...'."\n";
	    $buffer = $Common->getData($value['host']);
	    $all_data = json_decode($buffer,true);
	    if ($buffer != '' && is_array($all_data)) {
		foreach ($all_data as $line) {
	    	    $data = array();
	    	    //$data['id'] = $line['id']; // id not usable
	    	    $data['id'] = trim($line['flight_id']);
	    	    $data['hex'] = substr(str_pad(bin2hex($line['callsign']),6,'000000',STR_PAD_LEFT),-6); // hex
	    	    $data['pilot_name'] = $line['pilot_name'];
	    	    $data['pilot_id'] = $line['pilot_id'];
	    	    $data['ident'] = trim($line['callsign']); // ident
	    	    $data['altitude'] = $line['altitude']; // altitude
	    	    $data['speed'] = $line['gs']; // speed
	    	    $data['heading'] = $line['heading']; // heading
	    	    $data['latitude'] = $line['latitude']; // lat
	    	    $data['longitude'] = $line['longitude']; // long
	    	    $data['verticalrate'] = ''; // verticale rate
	    	    $data['squawk'] = ''; // squawk
	    	    $data['emergency'] = ''; // emergency
	    	    //$data['datetime'] = $line['lastupdate'];
	    	    $data['last_update'] = $line['last_update'];
		    $data['datetime'] = date('Y-m-d H:i:s');
	    	    $data['departure_airport_icao'] = $line['departure'];
	    	    //$data['departure_airport_time'] = $line['departure_time'];
	    	    $data['arrival_airport_icao'] = $line['arrival'];
    		    //$data['arrival_airport_time'] = $line['arrival_time'];
    		    //$data['registration'] = $line['aircraft'];
		    if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
	    	    $data['aircraft_icao'] = $line['plane_type'];
    		    $data['id_source'] = $id_source;
	    	    $data['format_source'] = 'vam';
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    $SI->add($data);
		    unset($data);
		}
		if ($globalDebug) echo 'No more data...'."\n";
		unset($buffer);
		unset($all_data);
	    }
    	    //$last_exec['phpvmacars'] = time();
    	    $last_exec[$id]['last'] = time();
	//} elseif ($value == 'sbs' || $value == 'tsv' || $value == 'raw' || $value == 'aprs' || $value == 'beast') {
	} elseif ($value['format'] == 'sbs' || $value['format'] == 'tsv' || $value['format'] == 'raw' || $value['format'] == 'aprs' || $value['format'] == 'beast' || $value['format'] == 'flightgearmp' || $value['format'] == 'flightgearsp' || $value['format'] == 'acars' || $value['format'] == 'acarssbs3') {
	    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
    	    //$last_exec[$id]['last'] = time();

	    //$read = array( $sockets[$id] );
	    $read = $sockets;
	    $write = NULL;
	    $e = NULL;
	    $n = socket_select($read, $write, $e, $timeout);
	    if ($e != NULL) var_dump($e);
	    if ($n > 0) {
		foreach ($read as $nb => $r) {
		    //$value = $formats[$nb];
		    $format = $globalSources[$nb]['format'];
        	    if ($format == 'sbs' || $format == 'aprs' || $format == 'raw' || $format == 'tsv' || $format == 'acarssbs3') {
        		$buffer = socket_read($r, 6000,PHP_NORMAL_READ);
        	    } else {
	    	        $az = socket_recvfrom($r,$buffer,6000,0,$remote_ip,$remote_port);
	    	    }
        	    //$buffer = socket_read($r, 60000,PHP_NORMAL_READ);
        	    //echo $buffer."\n";
		    // lets play nice and handle signals such as ctrl-c/kill properly
		    //if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
		    $error = false;
		    //$SI::del();
		    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
		    // SBS format is CSV format
		    if ($buffer != '') {
			$tt[$format] = 0;
			if ($format == 'acarssbs3') {
                    	    if ($globalDebug) echo 'ACARS : '.$buffer."\n";
			    $ACARS->add(trim($buffer));
			    $ACARS->deleteLiveAcarsData();
			} elseif ($format == 'raw') {
			    // AVR format
			    $data = $SBS->parse($buffer);
			    if (is_array($data)) {
				$data['datetime'] = date('Y-m-d H:i:s');
				$data['format_source'] = 'raw';
				if (isset($globalSources[$nb]['name']) && $globalSources[$nb]['name'] != '') $data['source_name'] = $globalSources[$nb]['name'];
    				if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];
                                if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
                            }
                        } elseif ($format == 'flightgearsp') {
                    	    //echo $buffer."\n";
                    	    if (strlen($buffer) > 5) {
				$line = explode(',',$buffer);
				$data = array();
				//XGPS,2.0947,41.3093,-3047.6953,198.930,0.000,callsign,c172p
				$data['hex'] = substr(str_pad(bin2hex($line[6].$line[7]),6,'000000',STR_PAD_LEFT),0,6);
				$data['ident'] = $line[6];
				$data['aircraft_name'] = $line[7];
				$data['longitude'] = $line[1];
				$data['latitude'] = $line[2];
				$data['altitude'] = round($line[3]*3.28084);
				$data['heading'] = round($line[4]);
				$data['speed'] = round($line[5]*1.94384);
				$data['datetime'] = date('Y-m-d H:i:s');
				$data['format_source'] = 'flightgearsp';
				if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
				$send = @ socket_send( $r  , $data_aprs , strlen($data_aprs) , 0 );
			    }
                        } elseif ($format == 'acars') {
                    	    if ($globalDebug) echo 'ACARS : '.$buffer."\n";
			    $ACARS->add(trim($buffer));
			    socket_sendto($r, "OK " . $buffer , 100 , 0 , $remote_ip , $remote_port);
			    $ACARS->deleteLiveAcarsData();
			} elseif ($format == 'flightgearmp') {
			    if (substr($buffer,0,1) != '#') {
				$data = array();
				//echo $buffer."\n";
				$line = explode(' ',$buffer);
				if (count($line) == 11) {
				    $userserver = explode('@',$line[0]);
				    $data['hex'] = substr(str_pad(bin2hex($line[0]),6,'000000',STR_PAD_LEFT),0,6); // hex
				    $data['ident'] = $userserver[0];
				    $data['registration'] = $userserver[0];
				    $data['latitude'] = $line[4];
				    $data['longitude'] = $line[5];
				    $data['altitude'] = $line[6];
				    $data['datetime'] = date('Y-m-d H:i:s');
				    $aircraft_type = $line[10];
				    $aircraft_type = preg_split(':/:',$aircraft_type);
				    $data['aircraft_name'] = substr(end($aircraft_type),0,-4);
				    if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
				}
			    }
			} elseif ($format == 'beast') {
			    echo 'Beast Binary format not yet supported. Beast AVR format is supported in alpha state'."\n";
			    die;
			} elseif ($format == 'tsv' || substr($buffer,0,4) == 'clock') {
			    $line = explode("\t", $buffer);
			    for($k = 0; $k < count($line); $k=$k+2) {
				$key = $line[$k];
			        $lined[$key] = $line[$k+1];
			    }
    			    if (count($lined) > 3) {
    				$data['hex'] = $lined['hexid'];
    				//$data['datetime'] = date('Y-m-d H:i:s',strtotime($lined['clock']));;
    				$data['datetime'] = date('Y-m-d H:i:s');;
    				if (isset($lined['ident'])) $data['ident'] = $lined['ident'];
    				if (isset($lined['lat'])) $data['latitude'] = $lined['lat'];
    				if (isset($lined['lon'])) $data['longitude'] = $lined['lon'];
    				if (isset($lined['speed'])) $data['speed'] = $lined['speed'];
    				if (isset($lined['squawk'])) $data['squawk'] = $lined['squawk'];
    				if (isset($lined['alt'])) $data['altitude'] = $lined['alt'];
    				if (isset($lined['heading'])) $data['heading'] = $lined['heading'];
    				$data['id_source'] = $id_source;
    				$data['format_source'] = 'tsv';
    				if (isset($globalSources[$nb]['name']) && $globalSources[$nb]['name'] != '') $data['source_name'] = $globalSources[$nb]['name'];
    				if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];
    				if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
    				unset($lined);
    				unset($data);
    			    } else $error = true;
			} elseif ($format == 'aprs' && $use_aprs) {
			    if ($aprs_connect == 0) {
				$send = @ socket_send( $r  , $aprs_login , strlen($aprs_login) , 0 );
				$aprs_connect = 1;
			    }
			    if ( $aprs_keep>60 && time() - $aprs_last_tx > $aprs_keep ) {
				$aprs_last_tx = time();
				$data_aprs = "# Keep alive";
				$send = @ socket_send( $r  , $data_aprs , strlen($data_aprs) , 0 );
			    }
			    //echo 'Connect : '.$aprs_connect.' '.$buffer."\n";
			    $buffer = str_replace('APRS <- ','',$buffer);
			    $buffer = str_replace('APRS -> ','',$buffer);
			    if (substr($buffer,0,1) != '#' && substr($buffer,0,1) != '@' && substr($buffer,0,5) != 'APRS ') {
				$line = $APRS->parse($buffer);
				if (is_array($line) && isset($line['address']) && $line['address'] != '' && isset($line['ident'])) {
				    $data = array();
				    //print_r($line);
				    $data['hex'] = $line['address'];
				    $data['datetime'] = date('Y-m-d H:i:s',$line['timestamp']);
				    //$data['datetime'] = date('Y-m-d H:i:s');
				    $data['ident'] = $line['ident'];
				    $data['latitude'] = $line['latitude'];
				    $data['longitude'] = $line['longitude'];
				    //$data['verticalrate'] = $line[16];
				    if (isset($line['speed'])) $data['speed'] = $line['speed'];
				    else $data['speed'] = 0;
				    $data['altitude'] = $line['altitude'];
				    if (isset($line['course'])) $data['heading'] = $line['course'];
				    //else $data['heading'] = 0;
				    $data['aircraft_type'] = $line['stealth'];
				    if (!isset($globalAPRSarchive) || (isset($globalAPRSarchive) && $globalAPRSarchive == FALSE)) $data['noarchive'] = true;
    				    $data['id_source'] = $id_source;
				    $data['format_source'] = 'aprs';
				    $data['source_name'] = $line['source'];
    				    if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];
				    $currentdate = date('Y-m-d H:i:s');
				    $aprsdate = strtotime($data['datetime']);
				    // Accept data if time <= system time + 20s
				    if ($line['stealth'] == 0 && (strtotime($data['datetime']) <= strtotime($currentdate)+20) && (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude'])))) $send = $SI->add($data);
				    else {
					if ($line['stealth'] != 0) echo '-------- '.$data['ident'].' : APRS stealth ON => not adding'."\n";
					else echo '--------- '.$data['ident'].' : Date APRS : '.$data['datetime'].' - Current date : '.$currentdate.' => not adding future event'."\n";
				    }
				    unset($data);
				} 
				//elseif ($line == false && $globalDebug) echo 'Ignored ('.$buffer.")\n";
				elseif ($line == true && $globalDebug) echo '!! Failed : '.$buffer."!!\n";
			    }
			} else {
			    $line = explode(',', $buffer);
    			    if (count($line) > 20) {
    			    	$data['hex'] = $line[4];
    				/*
    				$data['datetime'] = $line[6].' '.$line[7];
    					date_default_timezone_set($globalTimezone);
    					$datetime = new DateTime($data['datetime']);
    					$datetime->setTimezone(new DateTimeZone('UTC'));
    					$data['datetime'] = $datetime->format('Y-m-d H:i:s');
    					date_default_timezone_set('UTC');
    				*/
    				// Force datetime to current UTC datetime
    				$data['datetime'] = date('Y-m-d H:i:s');
    				$data['ident'] = trim($line[10]);
    				$data['latitude'] = $line[14];
    				$data['longitude'] = $line[15];
    				$data['verticalrate'] = $line[16];
    				$data['emergency'] = $line[20];
    				$data['speed'] = $line[12];
    				$data['squawk'] = $line[17];
    				$data['altitude'] = $line[11];
    				$data['heading'] = $line[13];
    				$data['ground'] = $line[21];
    				$data['emergency'] = $line[19];
    				$data['format_source'] = 'sbs';
				if (isset($globalSources[$nb]['name']) && $globalSources[$nb]['name'] != '') $data['source_name'] = $globalSources[$nb]['name'];
    				if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];
    				$data['id_source'] = $id_source;
    				if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $send = $SI->add($data);
    				else $error = true;
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
					//socket_close($r);
					if ($globalDebug) echo "Reconnect after an error...\n";
					if ($format == 'aprs') $aprs_connect = 0;
					$sourceer[$nb] = $globalSources[$nb];
					connect_all($sourceer);
					$sourceer = array();
				}
			    }
			}
			// Sleep for xxx microseconds
			if (isset($globalSBSSleep)) usleep($globalSBSSleep);
		    } else {
			if ($format == 'flightgearmp') {
			    	if ($globalDebug) echo "Reconnect FlightGear MP...";
				//@socket_close($r);
				sleep($globalMinFetch);
				$sourcefg[$nb] = $globalSources[$nb];
				connect_all($sourcefg);
				$sourcefg = array();
				break;
				
			} elseif ($format != 'acars' && $format != 'flightgearsp') {
			    if (isset($tt[$format])) $tt[$format]++;
			    else $tt[$format] = 0;
			    if ($tt[$format] > 30) {
				if ($globalDebug) echo "ERROR : Reconnect ".$format."...";
				//@socket_close($r);
				sleep(2);
				$aprs_connect = 0;
				$sourceee[$nb] = $globalSources[$nb];
				connect_all($sourceee);
				$sourceee = array();
				//connect_all($globalSources);
				$tt[$format]=0;
				break;
			    }
			}
		    }
		}
	    } else {
		$error = socket_strerror(socket_last_error());
		if ($globalDebug) echo "ERROR : socket_select give this error ".$error . "\n";
		if (($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY && $error != 'Success') || time() - $time >= $timeout) {
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
			    //connect_all($hosts);
			    $aprs_connect = 0;
			    connect_all($globalSources);

		}
	    }
	}
	if ($globalDaemon === false) {
	    if ($globalDebug) echo 'Check all...'."\n";
	    $SI->checkAll();
	}
    }
}

?>
