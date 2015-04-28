#!/usr/bin/php
<?php
// This is not a cron job... Use it like a daemon
require_once('require/class.SBS.php');

// Check if schema is at latest version
require_once('require/class.Connection.php');

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
    global $sockets;
    foreach ($hosts as $id => $host) {
	$hostport = explode(':',$host);
        $s = create_socket($hostport[0],$hostport[1], $errno, $errstr);
	if ($s) {
    	    $sockets[$id] = $s;
	    echo 'Connection in progress to '.$host.'....'."\n";
        } else {
	    echo 'Connection failed to '.$host.' : '.$errno.' '.$errstr."\n";
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
$errno = '';
$errstr='';
/* Initiate connections to all the hosts simultaneously */
connect_all($hosts);
// connected - lets do some work
echo "Connected!\n";
sleep(1);
echo "SCAN MODE \n\n";
while (true) {
    $read = $sockets;
    $n = @socket_select($read, $write = NULL, $e = NULL, $globalSBS1TimeOut);
    if ($n > 0) {
	foreach ($read as $r) {
            $buffer = socket_read($r, 3000);
	    // lets play nice and handle signals such as ctrl-c/kill properly
	    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
	    $dataFound = false;

	    $SBS::del();
	    $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
	    // SBS format is CSV format
	    if ($buffer != '') {
		$line = explode(',', $buffer);
    		if (count($line) > 20) $SBS::add($line);
	    } else connect_all($hosts);
	}
    }
}
//if (function_exists('pcntl_fork')) pcntl_exec($_,$argv);
?>
