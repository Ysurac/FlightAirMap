#!/usr/bin/php
<?php
// This is not a cron job... Use it like a daemon
require_once('require/class.SBS.php');

// Check if schema is at latest version
require_once('require/class.Connection.php');
require_once('require/class.Common.php');

$schema = new Connection();
if ($schema::latest() === false) {
    echo "You MUST update to latest schema. Run install/index.php";
    exit();
}

$SBS=new SBS();

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
echo "Connecting to SBS ...\n";


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
    global $sockets, $formats;
    foreach ($hosts as $id => $host) {
	if (filter_var($host,FILTER_VALIDATE_URL)) {
            if (preg_match('/deltadb.txt$/',$host)) {
        	$formats[$id] = 'deltadbtxt';
            } else if (preg_match('/aircraftlist.json$/',$host)) {
        	$formats[$id] = 'aircraftlistjson';
            } else if (preg_match('/\/action.php\/acars\/data$/',$host)) {
        	$formats[$id] = 'phpvmacars';
            } else if (preg_match('/whazzup/',$host)) {
        	$formats[$id] = 'whazzup';
            }
        } else {
	    $hostport = explode(':',$host);
    	    $s = create_socket($hostport[0],$hostport[1], $errno, $errstr);
	    if ($s) {
    	        $sockets[$id] = $s;
        	$formats[$id] = 'sbs';
		echo 'Connection in progress to '.$host.'....'."\n";
            } else {
		echo 'Connection failed to '.$host.' : '.$errno.' '.$errstr."\n";
    	    }
        }
    }
}


if (isset($globalSBS1Hosts)) {
    $hosts = $globalSBS1Hosts;
} else {
    $hosts = array($globalSBS1Host.':'.$globalSBS1Port);
}
$status = array();
$sockets = array();
$formats = array();
$errno = '';
$errstr='';
//$globalDaemon = FALSE;
if (!isset($globalDaemon)) $globalDaemon = TRUE;
/* Initiate connections to all the hosts simultaneously */
connect_all($hosts);
// connected - lets do some work
echo "Connected!\n";
sleep(1);
echo "SCAN MODE \n\n";
$i = 1;
while ($i > 0) {
    if (!$globalDaemon) $i = 0;
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
		    $data['datetime'] = date('Y-m-d h:i:s');
    		    $SBS::add($data);
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
			$data['hex'] = str_pad(dechex($line[1]),6,'000000',STR_PAD_LEFT);
			$data['ident'] = $line[0]; // ident
			if ($line[7] != '' && $line[7] != 0) $data['altitude'] = $line[7]*100; // altitude
			$data['speed'] = $line[8]; // speed
			$data['heading'] = $line[45]; // heading
			$data['latitude'] = $line[5]; // lat
	        	$data['longitude'] = $line[6]; // long
	        	$data['verticalrate'] = ''; // vertical rate
	        	$data['squawk'] = ''; // squawk
	        	$data['emergency'] = ''; // emergency
			//$data['datetime'] = date('Y-m-d h:i:s');
			$data['datetime'] = date('Y-m-d h:i:s',strtotime($line[37])); // FIXME convert to correct format
		        $data['departure_airport_icao'] = $line[11];
		        $data['departure_airport_time'] = $line[22]; // FIXME put a :
		        $data['arrival_airport_icao'] = $line[13];
	    		//$data['arrival_airport_time'] = ;
	    		if ($line[9] != '') {
	    		    $aircraft_data = explode('/',$line[9]);
	    		    $data['aircraft_icao'] = $aircraft_data[1];
        		}
    			$SBS::add($data);
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
		$data['datetime'] = date('Y-m-d h:i:s');
		$SBS::add($data);
	    }
    	} elseif ($value == 'phpvmacars') {
	    $buffer = Common::getData($hosts[$id]);
	    $all_data = json_decode($buffer,true);
	    foreach ($all_data as $line) {
	        $data = array();
	        $data['hex'] = str_pad(dechex($line['id']),6,'000000',STR_PAD_LEFT); // hex
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
	        $data['format_source'] = 'phpvmacars';
		$SBS::add($data);
	    }
	} elseif ($value == 'sbs') {
	    $read = $sockets;
	    $n = @socket_select($read, $write = NULL, $e = NULL, $globalSBS1TimeOut);
	    if ($n > 0) {
		$tt = 0;
		foreach ($read as $r) {
        	    $buffer = socket_read($r, 3000);
		    // lets play nice and handle signals such as ctrl-c/kill properly
		    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
		    $dataFound = false;
		    $SBS::del();
		    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
		    // SBS format is CSV format
		    if ($buffer != '') {
			$tt = 0;
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
    				
    				$SBS::add($data);
    			}
		    } else {
			$tt ++;
			if ($tt == 5) {
			    connect_all($hosts);
			    $tt = 0;
			}
		    }
		}
	    }
	}
    }
}
//if (function_exists('pcntl_fork')) pcntl_exec($_,$argv);
?>
