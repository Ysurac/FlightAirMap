#!/usr/bin/php
<?php
/**
* This script is used to retrieve message from SBS source like Dump1090, Radarcape,.. or from phpvms, wazzup files,AIS, APRS,...
* This script can be used as cron job with $globalDaemon = FALSE
*/

require_once(dirname(__FILE__).'/../require/class.SpotterImport.php');
require_once(dirname(__FILE__).'/../require/class.SpotterServer.php');
//require_once(dirname(__FILE__).'/../require/class.APRS.php');
require_once(dirname(__FILE__).'/../require/class.ATC.php');
require_once(dirname(__FILE__).'/../require/class.ACARS.php');
require_once(dirname(__FILE__).'/../require/class.SBS.php');
require_once(dirname(__FILE__).'/../require/class.Source.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');
if (isset($globalTracker) && $globalTracker) require_once(dirname(__FILE__).'/../require/class.TrackerImport.php');
if (isset($globalMarine) && $globalMarine) {
    require_once(dirname(__FILE__).'/../require/class.AIS.php');
    require_once(dirname(__FILE__).'/../require/class.MarineImport.php');
}

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

$options = getopt('s::',array('source::','server','nodaemon','idsource::','aprsserverssid::','aprsserverpass::','aprsserverhost::','aprsserverport::','format::','noaprsserver'));
//if (isset($options['s'])) $hosts = array($options['s']);
//elseif (isset($options['source'])) $hosts = array($options['source']);
if (isset($options['s'])) {
    $globalSources = array();
    if (isset($options['format'])) $globalSources[] = array('host' => $options['s'],'format' => $options['format']);
    else $globalSources[] = array('host' => $options['s']);
} elseif (isset($options['source'])) {
    $globalSources = array();
    if (isset($options['format'])) $globalSources[] = array('host' => $options['source'],'format' => $options['format']);
    else $globalSources[] = array('host' => $options['source']);
}
if (isset($options['aprsserverhost'])) {
	$globalServerAPRS = TRUE;
	$globalServerAPRShost = $options['aprsserverhost'];
}
if (isset($options['aprsserverport'])) $globalServerAPRSport = $options['aprsserverport'];
if (isset($options['aprsserverssid'])) $globalServerAPRSssid = $options['aprsserverssid'];
if (isset($options['aprsserverpass'])) $globalServerAPRSpass = $options['aprsserverpass'];
if (isset($options['noaprsserver'])) $globalServerAPRS = FALSE; 
if (isset($options['nodaemon'])) $globalDaemon = FALSE;
if (isset($options['server'])) $globalServer = TRUE;
if (isset($options['idsource'])) $id_source = $options['idsource'];
else $id_source = 1;
if (isset($globalServer) && $globalServer) {
    if ($globalDebug) echo "Using Server Mode\n";
    $SI=new SpotterServer();
/*
    require_once(dirname(__FILE__).'/../require/class.APRS.php');
    $SI = new adsb2aprs();
    $SI->connect();
*/
} else $SI=new SpotterImport($Connection->db);
if (isset($globalTracker) && $globalTracker) $TI = new TrackerImport($Connection->db);
if (isset($globalMarine) && $globalMarine) {
    $AIS = new AIS();
    $MI = new MarineImport($Connection->db);
}
//$APRS=new APRS($Connection->db);
$SBS=new SBS();
$ACARS=new ACARS($Connection->db);
$Source=new Source($Connection->db);
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
$reset = 0;

function connect_all($hosts) {
    //global $sockets, $formats, $globalDebug,$aprs_connect,$last_exec, $globalSourcesRights, $use_aprs;
    global $sockets,$httpfeeds, $globalSources, $globalDebug,$aprs_connect,$last_exec, $globalSourcesRights, $use_aprs, $reset,$context;
    $reset++;
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
    	    /*
    	    // Disabled for now, site change source format
    	    } else if (preg_match('/radarvirtuel.com\/list_aircrafts$/i',$host)) {
        	//$formats[$id] = 'radarvirtueljson';
        	$globalSources[$id]['format'] = 'radarvirtueljson';
        	//$last_exec['radarvirtueljson'] = 0;
        	if ($globalDebug) echo "Connect to radarvirtuel.com/file.json source (".$host.")...\n";
        	if (!isset($globalSourcesRights) || (isset($globalSourcesRights) && !$globalSourcesRights)) {
        	    echo '!!! You MUST set $globalSourcesRights = TRUE in settings.php if you have the right to use this feed !!!'."\n";
        	    exit(0);
        	}
    	    */
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
            } else if (preg_match('/airwhere/i',$host)) {
        	$globalSources[$id]['format'] = 'airwhere';
        	if ($globalDebug) echo "Connect to airwhere source (".$host.")...\n";
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
            } else if (preg_match(':myshiptracking.com/:i',$host)) {
        	//$formats[$id] = 'fr24json';
        	$globalSources[$id]['format'] = 'myshiptracking';
        	//$last_exec['fr24json'] = 0;
        	if ($globalDebug) echo "Connect to myshiptracking source (".$host.")...\n";
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
    		if ($globalSources[$id]['format'] == 'aisnmeahttp') {
    		    $idf = fopen($globalSources[$id]['host'],'r',false,$context);
    		    if ($idf !== false) {
    			$httpfeeds[$id] = $idf;
        		if ($globalDebug) echo "Connected to ".$globalSources[$id]['format']." source (".$host.")...\n";
    		    }
    		    elseif ($globalDebug) echo "Can't connect to ".$globalSources[$id]['host']."\n";
    		} elseif ($globalDebug) echo "Connect to ".$globalSources[$id]['format']." source (".$host.")...\n";
        } elseif (!filter_var($host,FILTER_VALIDATE_URL)) {
	    $hostport = explode(':',$host);
	    if (isset($hostport[1])) {
		$port = $hostport[1];
		$hostn = $hostport[0];
	    } else {
		$port = $globalSources[$id]['port'];
		$hostn = $globalSources[$id]['host'];
	    }
	    $Common = new Common();
	    if (!isset($globalSources[$id]['format']) || ($globalSources[$id]['format'] != 'acars' && $globalSources[$id]['format'] != 'flightgearsp')) {
        	$s = $Common->create_socket($hostn,$port, $errno, $errstr);
    	    } else {
        	$s = $Common->create_socket_udp($hostn,$port, $errno, $errstr);
	    }
	    if ($s) {
    	        $sockets[$id] = $s;
    	        if (!isset($globalSources[$id]['format']) || strtolower($globalSources[$id]['format']) == 'auto') {
		    if (preg_match('/aprs/',$hostn) || $port == '10152' || $port == '14580') {
			//$formats[$id] = 'aprs';
			$globalSources[$id]['format'] = 'aprs';
			//$aprs_connect = 0;
			//$use_aprs = true;
		    } elseif (preg_match('/pub-vrs/',$hostn) || $port == '32001' || $port == '32005' || $port == '32010' || $port == '32015' || $port == '32030') {
			$globalSources[$id]['format'] = 'vrstcp';
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
$httpfeeds = array();
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

if (isset($globalProxy) && $globalProxy) {
    $context = stream_context_create(array('http' => array('timeout' => $timeout,'proxy' => $globalProxy,'request_fulluri' => true)));
} else {
    $context = stream_context_create(array('http' => array('timeout' => $timeout)));
}

// APRS Configuration
if (!is_array($globalSources)) {
	echo '$globalSources in require/settings.php MUST be an array';
	die;
}
foreach ($globalSources as $key => $source) {
    if (!isset($source['format'])) {
        $globalSources[$key]['format'] = 'auto';
    }
    if (isset($source['callback']) && $source['callback'] === TRUE) {
        unset($globalSources[$key]);
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
	else $aprs_version = 'FlightAirMap '.str_replace(' ','_',$globalName);
	if (isset($globalAPRSssid)) $aprs_ssid = $globalAPRSssid;
	else $aprs_ssid = substr('FAM'.strtoupper(str_replace(' ','_',$globalName)),0,8);
	if (isset($globalAPRSfilter)) $aprs_filter = $globalAPRSfilter;
	else $aprs_filter =  'r/'.$globalCenterLatitude.'/'.$globalCenterLongitude.'/250.0';
	if ($aprs_full) $aprs_filter = '';
	if (isset($globalAPRSpass)) $aprs_pass = $globalAPRSpass;
	else $aprs_pass = '-1';

	if ($aprs_filter != '') $aprs_login = "user {$aprs_ssid} pass {$aprs_pass} vers {$aprs_version} filter {$aprs_filter}\n";
	else $aprs_login = "user {$aprs_ssid} pass {$aprs_pass} vers {$aprs_version}\n";
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
if ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
	$ATC=new ATC($Connection->db);
}
if (!$globalDaemon && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
	$ATC->deleteAll();
}

// Infinite loop if daemon, else work for time defined in $globalCronEnd or only one time.
while ($i > 0) {
    if (!$globalDaemon) $i = $endtime-time();
    // Delete old ATC
    if ($globalDaemon && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM))) {
	if ($globalDebug) echo 'Delete old ATC...'."\n";
        $ATC->deleteOldATC();
    }
    
    if (count($last_exec) == count($globalSources)) {
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
	date_default_timezone_set('UTC');
	if (!isset($last_exec[$id]['last'])) $last_exec[$id]['last'] = 0;
	if ($value['format'] == 'deltadbtxt' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    //$buffer = $Common->getData($hosts[$id]);
	    $buffer = $Common->getData($value['host']);
	    if ($buffer != '') $reset = 0;
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
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
		    if (isset($value['sourcestats'])) $data['sourcestats'] = $value['sourcestats'];
    		    $SI->add($data);
		    unset($data);
    		}
    	    }
    	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'aisnmeatxt' && (time() - $last_exec[$id]['last'] > $globalMinFetch*3)) {
	    date_default_timezone_set('CET');
	    $buffer = $Common->getData(str_replace('{date}',date('Ymd'),$value['host']));
	    date_default_timezone_set('UTC');
	    if ($buffer != '') $reset = 0;
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
		if ($line != '') {
		    //echo "'".$line."'\n";
		    $add = false;
		    $ais_data = $AIS->parse_line(trim($line));
		    $data = array();
		    if (isset($ais_data['ident'])) $data['ident'] = $ais_data['ident'];
		    if (isset($ais_data['mmsi'])) $data['mmsi'] = $ais_data['mmsi'];
		    if (isset($ais_data['speed'])) $data['speed'] = $ais_data['speed'];
		    if (isset($ais_data['heading'])) $data['heading'] = $ais_data['heading'];
		    if (isset($ais_data['latitude'])) $data['latitude'] = $ais_data['latitude'];
		    if (isset($ais_data['longitude'])) $data['longitude'] = $ais_data['longitude'];
		    if (isset($ais_data['status'])) $data['status'] = $ais_data['status'];
		    if (isset($ais_data['type'])) $data['type'] = $ais_data['type'];
		    if (isset($ais_data['imo'])) $data['imo'] = $ais_data['imo'];
		    if (isset($ais_data['callsign'])) $data['callsign'] = $ais_data['callsign'];
		    if (isset($ais_data['timestamp'])) {
			$data['datetime'] = date('Y-m-d H:i:s',$ais_data['timestamp']);
			if (!isset($last_exec[$id]['timestamp']) || $ais_data['timestamp'] >= $last_exec[$id]['timestamp']) {
			    $last_exec[$id]['timestamp'] = $ais_data['timestamp'];
			    $add = true;
			}
		    } else {
			$data['datetime'] = date('Y-m-d H:i:s');
			$add = true;
		    }
		    $data['format_source'] = 'aisnmeatxt';
    		    $data['id_source'] = $id_source;
		    //print_r($data);
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
		    if ($add && isset($ais_data['mmsi_type']) && $ais_data['mmsi_type'] == 'Ship') $MI->add($data);
		    unset($data);
		}
    	    }
    	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'aisnmeahttp') {
	    $arr = $httpfeeds;
	    $w = $e = null;
	    
	    if (isset($arr[$id])) {
		$nn = stream_select($arr,$w,$e,$timeout);
		if ($nn > 0) {
		    foreach ($httpfeeds as $feed) {
			$buffer = stream_get_line($feed,2000,"\n");
			$buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
			$buffer = explode('\n',$buffer);
			foreach ($buffer as $line) {
			    if ($line != '') {
				$ais_data = $AIS->parse_line(trim($line));
				$data = array();
				if (isset($ais_data['ident'])) $data['ident'] = $ais_data['ident'];
				if (isset($ais_data['mmsi'])) $data['mmsi'] = $ais_data['mmsi'];
				if (isset($ais_data['speed'])) $data['speed'] = $ais_data['speed'];
				if (isset($ais_data['heading'])) $data['heading'] = $ais_data['heading'];
				if (isset($ais_data['latitude'])) $data['latitude'] = $ais_data['latitude'];
				if (isset($ais_data['longitude'])) $data['longitude'] = $ais_data['longitude'];
				if (isset($ais_data['status'])) $data['status'] = $ais_data['status'];
				if (isset($ais_data['statusid'])) $data['status_id'] = $ais_data['statusid'];
				if (isset($ais_data['type'])) $data['type'] = $ais_data['type'];
				if (isset($ais_data['typeid'])) $data['type_id'] = $ais_data['typeid'];
				if (isset($ais_data['imo'])) $data['imo'] = $ais_data['imo'];
				if (isset($ais_data['callsign'])) $data['callsign'] = $ais_data['callsign'];
				if (isset($ais_data['destination'])) $data['arrival_code'] = $ais_data['destination'];
				if (isset($ais_data['eta_ts'])) $data['arrival_date'] = date('Y-m-d H:i:s',$ais_data['eta_ts']);
				if (isset($ais_data['timestamp'])) {
				    $data['datetime'] = date('Y-m-d H:i:s',$ais_data['timestamp']);
				} else {
				    $data['datetime'] = date('Y-m-d H:i:s');
				}
				$data['format_source'] = 'aisnmeahttp';
				$data['id_source'] = $id_source;
				if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
				if (isset($ais_data['mmsi_type']) && $ais_data['mmsi_type'] == 'Ship') $MI->add($data);
				unset($data);
			    }
			}
		    }
		} else {
		    $format = $value['format'];
		    if (isset($tt[$format])) $tt[$format]++;
		    else $tt[$format] = 0;
		    if ($tt[$format] > 30) {
			if ($globalDebug) echo 'Reconnect...'."\n";
			sleep(2);
			$sourceeen[] = $value;
			connect_all($sourceeen);
			$sourceeen = array();
		    }
		}
	    }
	} elseif ($value['format'] == 'myshiptracking' && (time() - $last_exec[$id]['last'] > $globalMinFetch*3)) {
	    $buffer = $Common->getData($value['host'],'get','','','','','20');
	    if ($buffer != '') {
		//echo $buffer;
		$all_data = json_decode($buffer,true);
		//print_r($all_data);
		if (isset($all_data[0]['DATA'])) {
		    foreach ($all_data[0]['DATA'] as $line) {
			if ($line != '') {
			    $data = array();
			    $data['ident'] = $line['NAME'];
			    $data['mmsi'] = $line['MMSI'];
			    $data['speed'] = $line['SOG'];
			    $data['heading'] = $line['COG'];
			    $data['latitude'] = $line['LAT'];
			    $data['longitude'] = $line['LNG'];
			    //    if (isset($ais_data['type'])) $data['type'] = $ais_data['type'];
			    $data['imo'] = $line['IMO'];
			    //$data['arrival_code'] = $ais_data['destination'];
			    $data['datetime'] = date('Y-m-d H:i:s',$line['T']);
			    $data['format_source'] = 'myshiptracking';
			    $data['id_source'] = $id_source;
			    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
			    $MI->add($data);
			    unset($data);
			}
		    }
		}
	    }
	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'boatbeaconapp' && (time() - $last_exec[$id]['last'] > $globalMinFetch*3)) {
	    $buffer = $Common->getData(str_replace('{timestamp}',time(),$value['host']));
	    if ($buffer != '') {
		$all_data = json_decode($buffer,true);
		if (isset($all_data[0]['mmsi'])) {
		    foreach ($all_data as $line) {
			if ($line != '') {
			    $data = array();
			    $data['ident'] = $line['shipname'];
			    $data['callsign'] = $line['callsign'];
			    $data['mmsi'] = $line['mmsi'];
			    $data['speed'] = $line['sog'];
			    if ($line['heading'] != '511') $data['heading'] = $line['heading'];
			    $data['latitude'] = $line['latitude'];
			    $data['longitude'] = $line['longitude'];
			    $data['type_id'] = $line['shiptype'];
			    $data['arrival_code'] = $line['destination'];
			    $data['datetime'] = $line['time'];
			    $data['format_source'] = 'boatbeaconapp';
			    $data['id_source'] = $id_source;
			    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
			    $MI->add($data);
			    unset($data);
			}
		    }
		}
		
	    }
    	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'boatnerd' && (time() - $last_exec[$id]['last'] > $globalMinFetch*3)) {
	    $buffer = $Common->getData($value['host']);
	    if ($buffer != '') {
		$all_data = json_decode($buffer,true);
		if (isset($all_data['features'][0]['id'])) {
		    foreach ($all_data['features'] as $line) {
			$data = array();
			if (isset($line['properties']['name'])) $data['ident'] = $line['properties']['name'];
			if (isset($line['properties']['callsign'])) $data['callsign'] = $line['properties']['callsign'];
			if (isset($line['properties']['mmsi'])) $data['mmsi'] = $line['properties']['mmsi'];
			if (isset($line['properties']['imo'])) $data['mmsi'] = $line['properties']['imo'];
			if (isset($line['properties']['speed'])) $data['speed'] = $line['properties']['speed'];
			if (isset($line['properties']['heading'])) $data['heading'] = $line['properties']['heading'];
			$data['latitude'] = $line['geometry']['coordinates'][1];
			$data['longitude'] = $line['geometry']['coordinates'][0];
			if (isset($line['properties']['vesselType'])) $data['type'] = $line['properties']['vesselType'];
			if (isset($line['properties']['destination'])) $data['arrival_code'] = $line['properties']['destination'];
			if (isset($line['properties']['eta']) && $line['properties']['eta'] != '') $data['arrival_date'] = $line['properties']['eta'];
			$data['format_source'] = 'boatnerd';
			$data['id_source'] = $id_source;
			$data['datetime'] = date('Y-m-d H:i:s');
			if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
			if ($line['properties']['vesselType'] != 'Navigation Aid') $MI->add($data);
			unset($data);
		    }
		}
		
	    }
    	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'shipplotter' && (time() - $last_exec[$id]['last'] > $globalMinFetch*3)) {
	    echo 'download...';
	    $buffer = $Common->getData($value['host'],'post',$value['post'],'','','','','ShipPlotter');
	    echo 'done !'."\n";
	    if ($buffer != '') $reset = 0;
    	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'\n',$buffer));
	    $buffer = explode('\n',$buffer);
	    foreach ($buffer as $line) {
		if ($line != '') {
		    $data = array();
		    $data['mmsi'] = (int)substr($line,0,9);
		    $data['datetime'] = date('Y-m-d H:i:s',substr($line,10,10));
		    //$data['status'] = substr($line,21,2);
		    //$data['type'] = substr($line,24,3);
		    $data['latitude'] = substr($line,29,9);
		    $data['longitude'] = substr($line,41,9);
		    $data['speed'] = round(substr($line,51,5));
		    //$data['course'] = substr($line,57,5);
		    $data['heading'] = round(substr($line,63,3));
		    //$data['draft'] = substr($line,67,4);
		    //$data['length'] = substr($line,72,3);
		    //$data['beam'] = substr($line,76,2);
		    $data['ident'] = trim(utf8_encode(substr($line,79,20)));
		    //$data['callsign'] = trim(substr($line,100,7);
		    //$data['dest'] = substr($line,108,20);
		    //$data['etaDate'] = substr($line,129,5);
		    //$data['etaTime'] = substr($line,135,5);
		    $data['format_source'] = 'shipplotter';
    		    $data['id_source'] = $id_source;
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
		    //print_r($data);
		    echo 'Add...'."\n";
		    $MI->add($data);
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
	    $reset = 0;
	    foreach ($buffer as $line) {
    		if ($line != '') {
    		    $line = explode(':', $line);
    		    if (count($line) > 30 && $line[0] != 'callsign') {
			$data = array();
			if (isset($line[37]) && $line[37] != '') $data['id'] = $value['format'].'-'.$line[1].'-'.$line[0].'-'.$line[37];
			else $data['id'] = $value['format'].'-'.$line[1].'-'.$line[0];
			$data['pilot_id'] = $line[1];
			$data['pilot_name'] = $line[2];
			$data['hex'] = str_pad(dechex($Common->str2int($line[1])),6,'000000',STR_PAD_LEFT);
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
			//if (isset($line[37])) $data['last_update'] = $line[37];
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
			if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
				if (isset($ATC)) {
					if (count($ATC->getByIdent($data['ident'],$data['format_source'])) > 0) echo $ATC->update($data['ident'],$data['frequency'],$data['latitude'],$data['longitude'],$data['range'],$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name'],$data['format_source'],$data['source_name']);
					else echo $ATC->add($data['ident'],$data['frequency'],$data['latitude'],$data['longitude'],$data['range'],$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name'],$data['format_source'],$data['source_name']);
				}
			}
    			unset($data);
    		    }
    		}
    	    }
    	    //if ($value == 'whazzup') $last_exec['whazzup'] = time();
    	    //elseif ($value == 'vatsimtxt') $last_exec['vatsimtxt'] = time();
    	    $last_exec[$id]['last'] = time();
    	} elseif ($value['format'] == 'airwhere' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    $buffer = $Common->getData('http://www.airwhere.co.uk/pilots.php','get','','','','','20');
	    if ($buffer != '') {
		$all_data = simplexml_load_string($buffer);
		foreach($all_data->children() as $childdata) {
			$data = array();
			$line = $childdata;
			//$data['hex'] = str_pad(dechex((int)$line['pktPilotID']),6,'000000',STR_PAD_LEFT);
			$data['id'] = date('Ymd').(int)$line['pktPilotID'];
			$data['datetime'] = date('Y-m-d H:i:s',strtotime((string)$line['entryTime'].' BST'));
			$data['latitude'] = (float)$line['pktLatitude'];
			$data['longitude'] = (float)$line['pktLongitude'];
			if ((float)$line['pktTrack'] != 0) $data['heading'] = (float)$line['pktTrack'];
			if ((int)$line['pktSpeed'] != 0) $data['speed'] = (int)$line['pktSpeed'];
			$data['altitude'] = round((int)$line['pktAltitude']*3.28084);
			$data['altitude_relative'] = 'AMSL';
			$data['pilot_id'] = (int)$line['pktPilotID'];
			$data['aircraft_icao'] = 'PARAGLIDER';
			$pilot_data = explode(',',$Common->getData('http://www.airwhere.co.uk/pilotdetails.php?pilot='.$data['pilot_id']));
			if (isset($pilot_data[4])) $data['pilot_name'] = $pilot_data[4];
			$data['format_source'] = $value['format'];
			$SI->add($data);
			unset($data);
		}
	    }
	    $Source->deleteOldLocationByType('gs');
	    $buffer = $Common->getData('http://www.airwhere.co.uk/gspositions.php','get','','','','','20');
	    if ($buffer != '') {
		$all_data = simplexml_load_string($buffer);
		foreach($all_data->children() as $childdata) {
			$data = array();
			$line = $childdata;
			$data['id'] = (int)$line['gsID'];
			$data['latitude'] = (float)$line['gsLatitude'];
			$data['longitude'] = (float)$line['gsLongitude'];
			$data['altitude'] = round((int)$line['gsHeight']*3.28084);
			$data['altitude_relative'] = 'AMSL';
			$data['datetime'] = date('Y-m-d H:i:s',strtotime((string)$line['gsLastUpdate'].' BST'));
			if (count($Source->getLocationInfoByLocationID($data['id'])) > 0) {
				$Source->updateLocationByLocationID('',$data['latitude'],$data['longitude'],$data['altitude'],'','','airwhere','antenna.png','gs',$id,$data['id'],$data['datetime']);
			} else {
				$Source->addLocation('',$data['latitude'],$data['longitude'],$data['altitude'],'','','airwhere','antenna.png','gs',$id,$data['id'],$data['datetime']);
			}
			unset($data);
		}
	    }
	    $last_exec[$id]['last'] = time();
	} elseif ($value['format'] == 'aircraftlistjson' && (time() - $last_exec[$id]['last'] > $globalMinFetch)) {
	    $buffer = $Common->getData($value['host'],'get','','','','','20');
	    if ($buffer != '') {
	    $all_data = json_decode($buffer,true);
	    if (isset($all_data['acList'])) {
		$reset = 0;
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
		    
		    if (isset($line['PosTime'])) $data['datetime'] = date('Y-m-d H:i:s',round($line['PosTime']/1000));
		    else $data['datetime'] = date('Y-m-d H:i:s');
		    
		    //$data['datetime'] = date('Y-m-d H:i:s');
		    if (isset($line['Type'])) $data['aircraft_icao'] = $line['Type'];
	    	    $data['format_source'] = 'aircraftlistjson';
		    $data['id_source'] = $id_source;
		    if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
		    if (isset($data['latitude'])) $SI->add($data);
		    unset($data);
		}
	    } elseif (is_array($all_data)) {
		$reset = 0;
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
		    if (isset($line['PosTime'])) $data['datetime'] = date('Y-m-d H:i:s',round($line['PosTime']/1000));
		    else $data['datetime'] = date('Y-m-d H:i:s');
	    	    $data['format_source'] = 'aircraftlistjson';
    		    $data['id_source'] = $id_source;
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
		$reset = 0;
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
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
		$reset = 0;
		foreach ($all_data['states'] as $key => $line) {
		    $data = array();
		    $data['hex'] = $line[0]; // hex
		    $data['ident'] = trim($line[1]); // ident
		    $data['altitude'] = round($line[7]*3.28084); // altitude
		    $data['speed'] = round($line[9]*1.94384); // speed
		    $data['heading'] = round($line[10]); // heading
		    $data['latitude'] = $line[6]; // lat
		    $data['longitude'] = $line[5]; // long
		    $data['verticalrate'] = $line[11]; // verticale rate
		    //$data['squawk'] = $line[10]; // squawk
		    //$data['emergency'] = ''; // emergency
		    //$data['registration'] = $line[2];
		    //$data['aircraft_icao'] = $line[0];
		    $data['datetime'] = date('Y-m-d H:i:s',$line[3]);
	    	    $data['format_source'] = 'opensky';
    		    $data['id_source'] = $id_source;
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
	    if (!empty($all_data)) $reset = 0;
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
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
		$reset = 0;
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
			if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
		$reset = 0;
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
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
			if (isset($ATC)) echo $ATC->add($data['ident'],'',$data['latitude'],$data['longitude'],'0',$data['info'],$data['datetime'],$data['type'],$data['pilot_id'],$data['pilot_name'],$data['format_source']);
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
		$reset = 0;
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
	    	    //$data['last_update'] = $line['lastupdate'];
	    	    if (isset($value['timezone'])) {
	    		$datetime = new DateTime($line['lastupdate'],new DateTimeZone($value['timezone']));
	    		$datetime->setTimeZone(new DateTimeZone('UTC'));
	    		$data['datetime'] = $datetime->format('Y-m-d H:i:s');
	    	    } else $data['datetime'] = date('Y-m-d H:i:s');
	    	    $data['departure_airport_icao'] = $line['depicao'];
	    	    $data['departure_airport_time'] = $line['deptime'];
	    	    $data['arrival_airport_icao'] = $line['arricao'];
    		    $data['arrival_airport_time'] = $line['arrtime'];
    		    $data['registration'] = $line['aircraft'];
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
		    if (isset($line['route'])) $data['waypoints'] = $line['route']; // route
		    if (isset($line['aircraftname'])) {
			$line['aircraftname'] = strtoupper($line['aircraftname']);
			$line['aircraftname'] = str_replace('BOEING ','B',$line['aircraftname']);
	    		$aircraft_data = explode('-',$line['aircraftname']);
	    		if (isset($aircraft_data[1]) && strlen($aircraft_data[0]) >= 3 && strlen($aircraft_data[0]) <= 4) $data['aircraft_icao'] = $aircraft_data[0];
	    		elseif (isset($aircraft_data[1]) && strlen($aircraft_data[1]) >= 3 && strlen($aircraft_data[1]) <= 4) $data['aircraft_icao'] = $aircraft_data[1];
	    		else {
	    		    $aircraft_data = explode(' ',$line['aircraftname']);
	    		    if (isset($aircraft_data[1])) $data['aircraft_icao'] = str_replace('-','',$aircraft_data[1]);
	    		    else $data['aircraft_icao'] = str_replace('-','',$line['aircraftname']);
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
		$reset = 0;
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
		    if (isset($value['noarchive']) && $value['noarchive'] === TRUE) $data['noarchive'] = true;
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
	} elseif ($value['format'] == 'sbs' || $value['format'] == 'tsv' || $value['format'] == 'raw' || $value['format'] == 'aprs' || $value['format'] == 'famaprs' || $value['format'] == 'beast' || $value['format'] == 'flightgearmp' || $value['format'] == 'flightgearsp' || $value['format'] == 'acars' || $value['format'] == 'acarssbs3' || $value['format'] == 'ais' || $value['format'] == 'vrstcp') {
	    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
    	    //$last_exec[$id]['last'] = time();

	    //$read = array( $sockets[$id] );
	    $read = $sockets;
	    $write = NULL;
	    $e = NULL;
	    $n = socket_select($read, $write, $e, $timeout);
	    if ($e != NULL) var_dump($e);
	    if ($n > 0) {
		$reset = 0;
		foreach ($read as $nb => $r) {
		    //$value = $formats[$nb];
		    $format = $globalSources[$nb]['format'];
		    if ($format == 'sbs' || $format == 'aprs' || $format == 'famaprs' || $format == 'raw' || $format == 'tsv' || $format == 'acarssbs3') {
			$buffer = @socket_read($r, 6000,PHP_NORMAL_READ);
		    } elseif ($format == 'vrstcp') {
			$buffer = @socket_read($r, 6000);
		    } else {
			$az = socket_recvfrom($r,$buffer,6000,0,$remote_ip,$remote_port);
		    }
		    //$buffer = socket_read($r, 60000,PHP_NORMAL_READ);
		    //echo $buffer."\n";
		    // lets play nice and handle signals such as ctrl-c/kill properly
		    //if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
		    $error = false;
		    //$SI::del();
		    if ($format == 'vrstcp') {
			$buffer = explode('},{',$buffer);
		    } else $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
		    // SBS format is CSV format
		    if ($buffer !== FALSE && $buffer != '') {
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
				if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
				if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
			    }
			} elseif ($format == 'ais') {
			    $ais_data = $AIS->parse_line(trim($buffer));
			    $data = array();
			    if (isset($ais_data['ident'])) $data['ident'] = $ais_data['ident'];
			    if (isset($ais_data['mmsi'])) $data['mmsi'] = $ais_data['mmsi'];
			    if (isset($ais_data['speed'])) $data['speed'] = $ais_data['speed'];
			    if (isset($ais_data['heading'])) $data['heading'] = $ais_data['heading'];
			    if (isset($ais_data['latitude'])) $data['latitude'] = $ais_data['latitude'];
			    if (isset($ais_data['longitude'])) $data['longitude'] = $ais_data['longitude'];
			    if (isset($ais_data['status'])) $data['status'] = $ais_data['status'];
			    if (isset($ais_data['statusid'])) $data['status_id'] = $ais_data['statusid'];
			    if (isset($ais_data['type'])) $data['type'] = $ais_data['type'];
			    if (isset($ais_data['imo'])) $data['imo'] = $ais_data['imo'];
			    if (isset($ais_data['callsign'])) $data['callsign'] = $ais_data['callsign'];
			    if (isset($ais_data['destination'])) $data['arrival_code'] = $ais_data['destination'];
			    if (isset($ais_data['eta_ts'])) $data['arrival_date'] = date('Y-m-d H:i:s',$ais_data['eta_ts']);
			    if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
			    if (isset($globalSources[$nb]['name']) && $globalSources[$nb]['name'] != '') $data['source_name'] = $globalSources[$nb]['name'];
			    if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];

			    if (isset($ais_data['timestamp'])) {
				$data['datetime'] = date('Y-m-d H:i:s',$ais_data['timestamp']);
			    } else {
				$data['datetime'] = date('Y-m-d H:i:s');
			    }
			    $data['format_source'] = 'aisnmea';
    			    $data['id_source'] = $id_source;
			    if (isset($ais_data['mmsi_type']) && $ais_data['mmsi_type'] == 'Ship') $MI->add($data);
			    unset($data);
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
				if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
				if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
				//$send = @ socket_send( $r  , $data_aprs , strlen($data_aprs) , 0 );
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
				    if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
				    if (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude']))) $SI->add($data);
				}
			    }
			} elseif ($format == 'beast') {
			    echo 'Beast Binary format not yet supported. Beast AVR format is supported in alpha state'."\n";
			    die;
			} elseif ($format == 'vrstcp') {
			    foreach($buffer as $all_data) {
				$line = json_decode('{'.$all_data.'}',true);
				$data = array();
				if (isset($line['Icao'])) $data['hex'] = $line['Icao']; // hex
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
		    		$data['format_source'] = 'vrstcp';
				$data['id_source'] = $id_source;
				if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
				if (isset($value['name']) && $value['name'] != '') $data['source_name'] = $value['name'];
				if (isset($data['latitude']) && isset($data['hex'])) $SI->add($data);
				unset($data);
			    }
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
				if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
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
			    //echo 'APRS data : '.$buffer."\n";
			    $buffer = str_replace('APRS <- ','',$buffer);
			    $buffer = str_replace('APRS -> ','',$buffer);
			    //echo $buffer."\n";
			    if (substr($buffer,0,1) != '#' && substr($buffer,0,1) != '@' && substr($buffer,0,5) != 'APRS ') {
				$line = $APRS->parse($buffer);
				//if (is_array($line) && isset($line['address']) && $line['address'] != '' && isset($line['ident'])) {
				if (is_array($line) && isset($line['latitude']) && isset($line['longitude']) && (isset($line['ident']) || isset($line['address']) || isset($line['mmsi']))) {
				    $aprs_last_tx = time();
				    $data = array();
				    //print_r($line);
				    if (isset($line['address'])) $data['hex'] = $line['address'];
				    if (isset($line['mmsi'])) $data['mmsi'] = $line['mmsi'];
				    if (isset($line['imo'])) $data['imo'] = $line['imo'];
				    if (isset($line['squawk'])) $data['squawk'] = $line['squawk'];
				    if (isset($line['arrival_code'])) $data['arrical_code'] = $line['arrival_code'];
				    if (isset($line['arrival_date'])) $data['arrical_date'] = $line['arrival_date'];
				    if (isset($line['type_id'])) $data['type_id'] = $line['typeid'];
				    if (isset($line['status_id'])) $data['status_id'] = $line['statusid'];
				    if (isset($line['timestamp'])) $data['datetime'] = date('Y-m-d H:i:s',$line['timestamp']);
				    else $data['datetime'] = date('Y-m-d H:i:s');
				    //$data['datetime'] = date('Y-m-d H:i:s');
				    if (isset($line['ident'])) $data['ident'] = $line['ident'];
				    $data['latitude'] = $line['latitude'];
				    $data['longitude'] = $line['longitude'];
				    //$data['verticalrate'] = $line[16];
				    if (isset($line['speed'])) $data['speed'] = $line['speed'];
				    //else $data['speed'] = 0;
				    if (isset($line['altitude'])) $data['altitude'] = $line['altitude'];
				    if (isset($line['comment'])) $data['comment'] = $line['comment'];
				    if (isset($line['symbol'])) $data['type'] = $line['symbol'];
				    if (isset($line['heading'])) $data['heading'] = $line['heading'];
				    //else $data['heading'] = 0;
				    if (isset($line['stealth'])) $data['aircraft_type'] = $line['stealth'];
				    //if (!isset($line['source_type']) && (!isset($globalAPRSarchive) || (isset($globalAPRSarchive) && $globalAPRSarchive === FALSE))) $data['noarchive'] = true;
				    if (isset($globalAPRSarchive) && $globalAPRSarchive === FALSE) $data['noarchive'] = true;
				    elseif (isset($globalAPRSarchive) && $globalAPRSarchive === TRUE) $data['noarchive'] = false;
				    if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
				    elseif (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === FALSE) $data['noarchive'] = false;
    				    $data['id_source'] = $id_source;
    				    if (isset($line['format_source'])) $data['format_source'] = $line['format_source'];
				    else $data['format_source'] = 'aprs';
				    $data['source_name'] = $line['source'];
				    if (isset($line['source_type'])) $data['source_type'] = $line['source_type'];
				    else $data['source_type'] = 'flarm';
    				    if (isset($globalSources[$nb]['sourcestats'])) $data['sourcestats'] = $globalSources[$nb]['sourcestats'];
				    $currentdate = date('Y-m-d H:i:s');
				    $aprsdate = strtotime($data['datetime']);
				    if ($data['source_type'] != 'modes' && $data['source_type'] != 'ais') $data['altitude_relative'] = 'AMSL';
				    // Accept data if time <= system time + 20s
				    //if (($data['source_type'] == 'modes') || isset($line['stealth']) && ($line['stealth'] == 0 || $line['stealth'] == '') && (strtotime($data['datetime']) <= strtotime($currentdate)+20) && (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude'])))) {
				    if (($data['source_type'] == 'modes') || isset($line['stealth']) && ($line['stealth'] == 0 || $line['stealth'] == '') && (($data['latitude'] == '' && $data['longitude'] == '') || (is_numeric($data['latitude']) && is_numeric($data['longitude'])))) {
					$send = $SI->add($data);
				    } elseif ($data['source_type'] == 'ais') {
					if (isset($globalMarine) && $globalMarine) $send = $MI->add($data);
				    } elseif (isset($line['stealth'])) {
					if ($line['stealth'] != 0) echo '-------- '.$data['ident'].' : APRS stealth ON => not adding'."\n";
					else echo '--------- '.$data['ident'].' : Date APRS : '.$data['datetime'].' - Current date : '.$currentdate.' => not adding future event'."\n";
				    } elseif (isset($globalAircraft) && $globalAircraft && isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && (
					    //$line['symbol'] == 'Balloon' ||
					    $line['symbol'] == 'Glider' || 
					    $line['symbol'] == 'Aircraft (small)' || $line['symbol'] == 'Helicopter')) {
					    if ($line['symbol'] == 'Ballon') $data['aircraft_icao'] = 'BALL';
					    if ($line['symbol'] == 'Glider') $data['aircraft_icao'] = 'PARAGLIDER';
					    $send = $SI->add($data);
				    } elseif (isset($globalMarine) && $globalMarine && isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && (
					    $line['symbol'] == 'Yacht (Sail)' || 
					    $line['symbol'] == 'Ship (Power Boat)')) {
					    $send = $MI->add($data);
				    } elseif (isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && (
					    $line['symbol'] == 'Car' || 
					    $line['symbol'] == 'Ambulance' || 
					    $line['symbol'] == 'Van' || 
					    $line['symbol'] == 'Truck' || $line['symbol'] == 'Truck (18 Wheeler)' || 
					    $line['symbol'] == 'Motorcycle' || 
					    $line['symbol'] == 'Tractor' || 
					    $line['symbol'] == 'Police' || 
					    $line['symbol'] == 'Bike' || 
					    $line['symbol'] == 'Jogger' || 
					    $line['symbol'] == 'Horse' || 
					    $line['symbol'] == 'Bus' || 
					    $line['symbol'] == 'Jeep' || 
					    $line['symbol'] == 'Recreational Vehicle' || 
					    $line['symbol'] == 'Yacht (Sail)' || 
					    $line['symbol'] == 'Ship (Power Boat)' || 
					    $line['symbol'] == 'Firetruck' || 
					    $line['symbol'] == 'Balloon' || $line['symbol'] == 'Glider' || 
					    $line['symbol'] == 'Aircraft (small)' || $line['symbol'] == 'Helicopter' || 
					    $line['symbol'] == 'SUV' ||
					    $line['symbol'] == 'Snowmobile' ||
					    $line['symbol'] == 'Mobile Satellite Station')) {
				    //} elseif (isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && isset($line['speed']) && $line['symbol'] != 'Weather Station' && $line['symbol'] != 'House QTH (VHF)' && $line['symbol'] != 'Dot' && $line['symbol'] != 'TCP-IP' && $line['symbol'] != 'xAPRS (UNIX)' && $line['symbol'] != 'Antenna' && $line['symbol'] != 'Cloudy' && $line['symbol'] != 'HF Gateway' && $line['symbol'] != 'Yagi At QTH' && $line['symbol'] != 'Digi' && $line['symbol'] != '8' && $line['symbol'] != 'MacAPRS') {
				//    } elseif (isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && $line['symbol'] != 'Weather Station' && $line['symbol'] != 'House QTH (VHF)' && $line['symbol'] != 'Dot' && $line['symbol'] != 'TCP-IP' && $line['symbol'] != 'xAPRS (UNIX)' && $line['symbol'] != 'Antenna' && $line['symbol'] != 'Cloudy' && $line['symbol'] != 'HF Gateway' && $line['symbol'] != 'Yagi At QTH' && $line['symbol'] != 'Digi' && $line['symbol'] != '8' && $line['symbol'] != 'MacAPRS') {
					//echo '!!!!!!!!!!!!!!!! SEND !!!!!!!!!!!!!!!!!!!!'."\n";
					if (isset($globalTracker) && $globalTracker) $send = $TI->add($data);
				    } elseif (!isset($line['stealth']) && is_numeric($data['latitude']) && is_numeric($data['longitude']) && isset($data['ident']) && isset($data['altitude'])) {
					if (!isset($data['altitude'])) $data['altitude'] = 0;
					$Source->deleteOldLocationByType('gs');
					if (count($Source->getLocationInfoByNameType($data['ident'],'gs')) > 0) {
						$Source->updateLocation($data['ident'],$data['latitude'],$data['longitude'],$data['altitude'],'','',$data['source_name'],'antenna.png','gs',$id,0,$data['datetime']);
					} else {
						$Source->addLocation($data['ident'],$data['latitude'],$data['longitude'],$data['altitude'],'','',$data['source_name'],'antenna.png','gs',$id,0,$data['datetime']);
					}
				    } elseif (isset($line['symbol']) && $line['symbol'] == 'Weather Station') {
					//if ($globalDebug) echo '!! Weather Station not yet supported'."\n";
					if ($globalDebug) echo '# Weather Station added'."\n";
					$Source->deleteOldLocationByType('wx');
					$weather_data = json_encode($line);
					if (count($Source->getLocationInfoByNameType($data['ident'],'wx')) > 0) {
						$Source->updateLocation($data['ident'],$data['latitude'],$data['longitude'],0,'','',$data['source_name'],'wx.png','wx',$id,0,$data['datetime'],$weather_data);
					} else {
						$Source->addLocation($data['ident'],$data['latitude'],$data['longitude'],0,'','',$data['source_name'],'wx.png','wx',$id,0,$data['datetime'],$weather_data);
					}
				    } elseif (isset($line['symbol']) && $line['symbol'] == 'Lightning') {
					//if ($globalDebug) echo '!! Weather Station not yet supported'."\n";
					if ($globalDebug) echo '☈ Lightning added'."\n";
					$Source->deleteOldLocationByType('lightning');
					if (count($Source->getLocationInfoByNameType($data['ident'],'lightning')) > 0) {
						$Source->updateLocation($data['ident'],$data['latitude'],$data['longitude'],0,'','',$data['source_name'],'weather/thunderstorm.png','lightning',$id,0,$data['datetime'],$data['comment']);
					} else {
						$Source->addLocation($data['ident'],$data['latitude'],$data['longitude'],0,'','',$data['source_name'],'weather/thunderstorm.png','lightning',$id,0,$data['datetime'],$data['comment']);
					}
				    } elseif ($globalDebug) {
				    	echo '/!\ Not added: '.$buffer."\n";
				    	print_r($line);
				    }
				    unset($data);
				}
				elseif (is_array($line) && isset($line['ident']) && $line['ident'] != '') {
					$Source->updateLocationDescByName($line['ident'],$line['source'],$id,$line['comment']);
				}
				/*
				elseif (is_array($line) && $globalDebug && isset($line['symbol']) && isset($line['latitude']) && isset($line['longitude']) && ($line['symbol'] == 'Car' || $line['symbol'] == 'Ambulance' || $line['symbol'] == 'Van' || $line['symbol'] == 'Truck' || $line['symbol'] == 'Truck (18 Wheeler)' || $line['symbol'] == 'Motorcycle')) {
					echo '!! Car & Trucks not yet supported'."\n";
				}
				*/
				//elseif ($line == false && $globalDebug) echo 'Ignored ('.$buffer.")\n";
				elseif ($line == true && $globalDebug) echo '!! Failed : '.$buffer."!!\n";
				if (isset($globalSources[$nb]['last_weather_clean']) && time()-$globalSources[$nb]['last_weather_clean'] > 60*5) {
					$Source->deleteOldLocationByType('lightning');
					$Source->deleteOldLocationByType('wx');
					$globalSources[$nb]['last_weather_clean'] = time();
				} elseif (!isset($globalSources[$nb]['last_weather_clean'])) {
					$globalSources[$nb]['last_weather_clean'] = time();
				}
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
    				date_default_timezone_set('UTC');
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
				if (isset($globalSources[$nb]['noarchive']) && $globalSources[$nb]['noarchive'] === TRUE) $data['noarchive'] = true;
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
						//print_r($line);
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
		if (($error != SOCKET_EINPROGRESS && $error != SOCKET_EALREADY && $error != 'Success') || (time() - $time >= $timeout && $error != 'Success')) {
			if ($globalDebug) echo "ERROR : socket_select give this error ".$error . "\n";
			if (isset($globalDebug)) echo "Restarting...\n";
			// Restart the script if possible
			if (is_array($sockets)) {
			    if ($globalDebug) echo "Shutdown all sockets...";
			    
			    foreach ($sockets as $sock) {
				@socket_shutdown($sock,2);
				@socket_close($sock);
			    }
			    
			}
			if ($globalDebug) echo "Waiting...";
			sleep(2);
			$time = time();
			//connect_all($hosts);
			$aprs_connect = 0;
			if ($reset%5 == 0) sleep(20);
			if ($reset%10 == 0) sleep(100);
			if ($reset%20 == 0) sleep(200);
			if ($reset > 100) exit('Too many attempts...');
			if ($globalDebug) echo "Restart all connections...";
			connect_all($globalSources);
		}
	    }
	}
	if ($globalDaemon === false) {
	    if ($globalDebug) echo 'Check all...'."\n";
	    if (isset($SI)) $SI->checkAll();
	    if (isset($TI)) $TI->checkAll();
	    if (isset($MI)) $MI->checkAll();
	}
    }
}

?>
