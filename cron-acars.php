#!/usr/bin/php
<?php
// This is not a cron job... Use it like a daemon
require_once('require/class.ACARS.php');

$debug = true;

$ACARS=new ACARS();
date_default_timezone_set('UTC');
// signal handler - playing nice with sockets and dump1090
pcntl_signal(SIGINT,  function($signo) {
    global $sock, $db;
    echo "\n\nctrl-c or kill signal received. Tidying up ... ";
    socket_shutdown($sock, 0);
    socket_close($sock);
    $db = null;
    die("Bye!\n");
});
pcntl_signal_dispatch();


// let's try and connect
echo "Listen to acarsdec ... ";
// create our socket and set it to non-blocking
$sock = socket_create(AF_INET, SOCK_DGRAM, 0) or die("Unable to create socket\n");

// Bind the source address
if( !socket_bind($sock, $globalACARSHost , $globalACARSPort) )
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
    //  (null) 2 23/02/2015 14:46:06 0 -16 X .D-AIPW ! 1L 7 M82A LH077P 010952342854:VP-MIBI+W+0)-V+(),GB1
    $ACARS::add(trim($buffer));
    socket_sendto($sock, "OK " . $buffer , 100 , 0 , $remote_ip , $remote_port);
    $ACARS::deleteLiveAcarsData();
}
pcntl_exec($_,$argv);
?>
