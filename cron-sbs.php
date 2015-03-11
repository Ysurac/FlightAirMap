#!/usr/bin/php
<?php
// This is not a cron job... Use it like a daemon
require_once('require/class.SBS.php');

$debug = true;

$SBS=new SBS();

date_default_timezone_set('UTC');
// signal handler - playing nice with sockets and dump1090
if (function_exists('pcntl_fork')) {
    pcntl_signal(SIGINT,  function($signo) {
        global $sock, $db;
        echo "\n\nctrl-c or kill signal received. Tidying up ... ";
        socket_shutdown($sock, 0);
        socket_close($sock);
        $db = null;
        die("Bye!\n");
    });
    pcntl_signal_dispatch();
}

// let's try and connect
echo "Connecting to dump1090 ... ";
// create our socket and set it to non-blocking
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Unable to create socket\n");

while (!@socket_connect($sock, $globalSBS1Host, $globalSBS1Port))
{
      $err = socket_last_error($sock);
      if ($err == 115 || $err == 114)
      {
        if ((time() - $time) >= $globalSBS1TimeOut)
        {
          socket_close($sock);
          die("Connection timed out.\n");
        }
      }
      die(socket_strerror($err) . "\n");
}

// connected - lets do some work
echo "Connected!\n";
sleep(1);
echo "SCAN MODE \n\n";
while($buffer = socket_read($sock, 3000, PHP_NORMAL_READ)) {

    // lets play nice and handle signals such as ctrl-c/kill properly
    if (function_exists('pcntl_fork')) pcntl_signal_dispatch();
    $dataFound = false;

    $SBS::del();
    // SBS format is CSV format
    $line = explode(',', $buffer);
    $SBS::add($line);
}
if (function_exists('pcntl_fork')) pcntl_exec($_,$argv);
?>
