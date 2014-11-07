<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "About";
$top_header = "about.jpg";
require('header.php');
?>

<div class="info column">
    <h1>About <?php print $globalName; ?></h1>

    <p>This is an open source project displaying <u>most</u> (mostly <a href="http://en.wikipedia.org/wiki/Instrument_flight_rules" target="_blank">IFR</a>) flights that have flown near this site area.
    <?php if ($globalFlightAware) { ?> The data is provided by <a href="http://flightaware.com/" target="_blank">FlightAware</a>.<?php } ?> This project was created by (<a href="http://www.mariotrunz.com" target="_blank">Mario Trunz</a>) as part of his passion of aviation and web design and forked by Ycarus from <a href="http://www.zugaina.com" target="_blank">Zugaina</a>.</p>
    <a name="history"></a>
		<h3>History</h3>

		<p>The project started in	the summer of 2013 that captured only Airbus planes flying near Barrie, with data coming from <a href="http://flightradar24.com/" target="_blank">FlightRadar24</a>. It was setup so that FlightRadar24 would email me, and then from there posted to a Twitter account, and from there eventually to a database. Over time I have managed to find better sources and an additional need for data to become what it is today.</p>

    <p>Currently this website hosts a large number of interesting statistics. The <a href="<?php print $globalURL; ?>/statistics">statistic pages</a> and any of the individual pages (such as aircraft, airlines, airports, routes etc.) have been designed based on what I actually wanted to see. It kind of served my needs to see the information in this database and I hope it will be useful for you too. And if you ever feel like you just can't find anything in particular you can always play to your heart's content in the <a href="<?php print $globalURL; ?>/search">search page</a> and combine any parameter to your liking.</p>

    <p>I continue to make this database as useful as possible and evolve it over time. If you find any issues, data discrepancy or just want to give your feedback &amp; suggestions <a href="<?php print $globalURL; ?>/contact">contact me</a>. I'll be very happy to assist anybody with any questions about the data or how to find it.</p>

    <br /><br />

    <a name="source"></a>
    <h3>Source &amp; Credits</h3>

    <?php
	if ($globalFlightAware) { 
    ?>
    <p>The data from FlightAware is coming from multiple sources. Not every aircraft is tracked on FlightAware, especially not older aircrafts as well as government aircrafts, however most modern airliners will work. You can learn more about how it works on <a href="http://flightaware.com/adsb/" target="_blank">FlightAware's ADS-B</a> page. Also, not every aircraft is shown to have flown exactly at that minute as seen on this site (aka real-time). There is a 5 minute delay on some of the sources.</p>
    <?php } ?>
    <p>None of this project would have been possible without the help and contributions of these organizations and people:</p>

    <ul>
    	<li><a href="http://flightaware.com" target="_blank">FlightAware</a> - With their API to access the data which allows me to get additional information. Data sources come from live ADS-B, FAA and other government agencies.</li>
    	<li><a href="http://sonicgoose.com" target="_blank">Sonic Goose (Rob Jones)</a> - Contributes ADS-B data within the Greater Toronto Area and South-central Ontario to numerous flight tracking sites, including FlightAware. A very big thank you to him! :)</li>
    </ul>
    
    <p>Airspaces come from <a href="http://soaringweb.org/TP">Worlwide Soaring Turnpoint Exchange</a></p>

    <h3>Data License</h3>

    <p>The data published by <?php print $globalName; ?> is made available under the Open Database License: <a href="http://opendatacommons.org/licenses/odbl/1.0/" target="_blank">http://opendatacommons.org/licenses/odbl/1.0/</a>. Any rights in individual contents of the database are licensed under the Database Contents License: <a href="http://opendatacommons.org/licenses/dbcl/1.0/" target="_blank">http://opendatacommons.org/licenses/dbcl/1.0/</a> - See more at: <a href="http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf" target="_blank">http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf</a></p>
    
    <h3>Image Credits</h3>
    <p>Airports icons from <a href="http://mapicons.nicolasmollet.com/">http://mapicons.nicolasmollet.com/</a> under <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons 3.0 BY-SA</a>.</p>
    <p>Waypoints icons from <a href="http://www.fatcow.com/free-icons/">http://www.fatcow.com/free-icons/</a> under <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons 3.0 BY-SA</a>.</p>
</div>

<?php
require('footer.php');
?>
