<?php
require_once('require/class.Connection.php');

$title = "Contribute";
$top_header = "contrail.jpg";
require_once('header.php');
?>

<div class="info column">
    <h1>Contribute to <?php print $globalName; ?></h1>
    <p>You can contribute to <?php print $globalName; ?> if you have an ADS-B receiver.</p>
    <h3>You need to : </h3>
    <ul>
	<li>Create an account on <a href="<?php if (isset($globalContributeLogin)) print $globalContributeLogin.'">'.$globalContributeLogin; else print 'https://login.flightairmap.fr">login.flightairmap.fr'; ?></a></li>
	<li>Download dump1090 fork from <a href="https://github.com/Ysurac/dump1090">https://github.com/Ysurac/dump1090</a></li>
	<li>Compile zfamup1090 with <em>make zfamup1090</em> (you need to have libcurl installed)</li>
	<li>Run zfamup1090 : <em>./zfamup1090 --net-zfam-user YourUsername --net-zfam-pass YourPassword<?php if (isset($globalContributeURL)) print ' --net-zfam-addr '.$globalContributeURL; if (isset($globalContributePort)) print ' --net-zfam-port '.$globalContributePort; ?></em> (add <em>--net-bo-ipaddr ipofbeastsource --net-bo-port portofbeastsource</em> if needed, default to 127.0.0.1 and 30005)</li>
    </ul>
</div>
<?php
require_once('footer.php');
?>
