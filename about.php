<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Connection = new Connection();
$title = "About";
$top_header = "about.jpg";
require_once('header.php');
?>
<!-- FlightAirMap Schema version: <?php print $Connection->latest_schema; ?> -->
<!-- FlightAirMap GlobalURL value: <?php print $globalURL; ?> -->
<div class="info column">
    <h1>About <?php print $globalName; ?></h1>
<?php
	if ($globalName == 'FlightAirMap') {
?>
    <p>This is an open source project displaying <u>most</u> (mostly <a href="http://en.wikipedia.org/wiki/Instrument_flight_rules" target="_blank">IFR</a>) flights that have flown near this site area.
    <?php if (isset($globalADSBHUB) && $globalADSBHUB) { ?> Some ADS-B sources come from <a href="http://www.adsbhub.net">ADSBHUB.net</a>.<?php } ?>
    <a name="history"></a>
		<h3>History</h3>

	<p>The project started in the summer of 2013 that captured only Airbus planes flying near Barrie, with data coming from <a href="http://flightradar24.com/" target="_blank">FlightRadar24</a>.</p>

    <p>Currently this website hosts a large number of interesting statistics. The statistic pages and any of the individual pages (such as aircraft, airlines, airports, routes etc.) have been designed based on what I actually wanted to see. It kind of served my needs to see the information in this database and I hope it will be useful for you too. And if you ever feel like you just can't find anything in particular you can always play to your heart's content in the search page and combine any parameter to your liking.</p>

    <p>I continue to make this database as useful as possible and evolve it over time. If you find any issues, data discrepancy or just want to give your feedback &amp; suggestions <a href="https://github.com/Ysurac/FlightAirMap/issues">contact me</a>.</p>

    <br /><br />
<?php
	} else {
?>
     <p>This project use <a href="http://www.flightairmap.com/">FlightAirMap</a> (<a href="https://github.com/Ysurac/FlightAirMap/">source</a>) by Ycarus from <a href="http://www.zugaina.com/">Zugaina</a>.</p>
<?php
	}
?>
    <a name="source"></a>
    <h3>Source &amp; Credits</h3>

<?php
	if ($globalFlightAware) { 
?>
    <p>The data from FlightAware is coming from multiple sources. Not every aircraft is tracked on FlightAware, especially not older aircraft as well as government aircraft, however most modern airliners will work. You can learn more about how it works on <a href="http://flightaware.com/adsb/" target="_blank">FlightAware's ADS-B</a> page. Also, not every aircraft is shown to have flown exactly at that minute as seen on this site (aka real-time). There is a 5 minute delay on some of the sources.</p>
<?php
	}
?>
    <p>None of this project would have been possible without the help and contributions of these organizations and people:</p>

    <ul>
    	<li><a href="https://github.com/barriespotter/Web_App">Barrie Spotter</a> for original code, design, idea,...</li>
    	<li><a href="http://flightaware.com" target="_blank">FlightAware</a> - With their API to access the data which allows to get additional information. Data sources come from live ADS-B, FAA and other government agencies.</li>
	<li>Airspaces come from <a href="http://soaringweb.org/TP">Worlwide Soaring Turnpoint Exchange</a></li>
	<li>Font Awesome by Dave Gandy <a href="http://fontawesome.io/">fontawesome.io</a></li>
	<li>Leaflet <a href="http://leafletjs.com/">leafletjs.com</a></li>
	<li>Boostrap <a href="http://getbootstrap.com/">getbootstrap.com</a></li>
	<li>Sidebar v2 <a href="https://github.com/Turbo87/sidebar-v2">https://github.com/Turbo87/sidebar-v2</a></li>
	<li><a href="http://www.adsbhub.net/">ADSBHUB.net</a> to share ADS-B data</li>
	<li><a href="http://www.cesiumjs.org">Cesium</a> used to display flights on a WebGL virtual globe</li>
	<li>Wind layer use <a href="http://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_0p50.pl">NOAA GFS</a> updated every 6 hours and Ocean surface currents use <a href="https://podaac.jpl.nasa.gov/ws/search/granule/?datasetId=PODAAC-OSCAR-03D01&apidoc">OSCAR third degree</a></li>
    </ul>

    <h3>Data License</h3>

    <p>The data published by <?php print $globalName; ?> is made available under the Open Database License: <a href="http://opendatacommons.org/licenses/odbl/1.0/" target="_blank">http://opendatacommons.org/licenses/odbl/1.0/</a>. Any rights in individual contents of the database are licensed under the Database Contents License: <a href="http://opendatacommons.org/licenses/dbcl/1.0/" target="_blank">http://opendatacommons.org/licenses/dbcl/1.0/</a> - See more at: <a href="http://opendatacommons.org/licenses/odbl/" target="_blank">http://opendatacommons.org/licenses/odbl/</a></p>
    
    <h3>Source Code License</h3>
    <img src="images/AGPLv3_Logo.png"/><p>The <a href="https://github.com/Ysurac/FlightAirMap/">source code</a> is available under <a href="https://www.gnu.org/licenses/agpl-3.0.html">GNU Affero General Public License version 3 (AGPL v3)</a></p>
    
    <h3>Image Credits</h3>
    <ul>
	<li>Airports and location icons from <a href="http://mapicons.nicolasmollet.com/">http://mapicons.nicolasmollet.com/</a> under <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons 3.0 BY-SA</a>.</li>
	<li>Waypoints icons from <a href="http://www.fatcow.com/free-icons/">http://www.fatcow.com/free-icons/</a> under <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons 3.0 BY-SA</a>.</li>
	<li><a href="https://www.iconfinder.com/icons/1031459/antenna_communication_gps_satelite_space_icon">Satellite icon</a> by Nazarrudin Ansyari</li>
    </ul>
    
    <h3>Models credits</h3>
    <p>Credits for aircraft models are <a href="models/sources.txt">here</a>, spaces models <a href="models/space/sources.txt">here</a> and vehicules models <a href="models/vehicules/sources.txt">here</a></p>
    <p>Sources of models are available <a href="https://github.com/Ysurac/FlightAirMap-3dmodels">here</a>.</p>
</div>

<?php
require_once('footer.php');
?>
