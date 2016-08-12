#!/usr/bin/php
<?php
/**
* This daemon is used to receive flightgear messages
* This script display flight from a FlightGear installation. You need to copy install/flightairmap.xml to /usr/share/flightgear/Protocol/flightairmap.xml
* and run fgfs --generic=socket,out,10,localhost,93745,udp,flightairmap
*/

// This is not a cron job... Use it like a daemon
// Check if schema is at latest version
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.SpotterImport.php');
$schema = new Connection();
$SI = new SpotterImport();
if ($schema->latest() === false) {
    echo "You MUST update to latest schema. Run install/index.php";
    exit();
}

$debug = true;

//$ACARS=new ACARS();
date_default_timezone_set('UTC');
// signal handler - playing nice with sockets and dump1090
pcntl_signal(SIGINT,  function($signo) {
    global $sock;
    echo "\n\nctrl-c or kill signal received. Tidying up ... ";
    socket_shutdown($sock, 0);
    socket_close($sock);
    die("Bye!\n");
});
pcntl_signal_dispatch();


// let's try and connect
echo "Listen to flightgear ... ";
// create our socket
$sock = socket_create(AF_INET, SOCK_DGRAM, 0) or die("Unable to create socket\n");
if (!isset($globalFlightGearHost)) $globalFlightGearHost = '0.0.0.0';
if (!isset($globalFlightGearPort)) $globalFlightGearPort = '93745';
// Bind the source address
if( !socket_bind($sock, $globalFlightGearHost , $globalFlightGearPort) )
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Could not bind socket : [$errorcode] $errormsg \n");
}

echo "LISTEN UDP MODE \n\n";
while(1) {
    $r = socket_recvfrom($sock, $buffer, 512, 0, $remote_ip, $remote_port);

    // lets play nice and handle signals such as ctrl-c/kill properly
    pcntl_signal_dispatch();
    $dataFound = false;
    if (strlen($buffer) > 5) {
        $buffer=trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$buffer));
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
	$data['format_source'] = 'flightgear';
	$SI->add($data);
    }
    
    socket_sendto($sock, "OK " . $buffer , 100 , 0 , $remote_ip , $remote_port);
}
pcntl_exec($_,$argv);
?>
