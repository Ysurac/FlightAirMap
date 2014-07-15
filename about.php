<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "About";
$top_header = "about.jpg";
require('header.php');
?>

<div class="info column">
    <h1>About Barrie Spotter</h1>  
    
    <p>This is an open source project displaying <u>most</u> (mostly <a href="http://en.wikipedia.org/wiki/Instrument_flight_rules" target="_blank">IFR</a>) flights that have flown near the <a href="http://en.wikipedia.org/wiki/Barrie" target="_blank">Barrie, Ontario, Canada</a> area. The data is provided by <a href="http://flightaware.com/" target="_blank">FlightAware</a>. This project was created by me (<a href="http://www.mariotrunz.com" target="_blank">Mario Trunz</a>) as part of my passion of aviation and web design.</p>
    <a name="history"></a>
		<h3>History</h3>
		
		<p>The project started in	the summer of 2013 that captured only Airbus planes flying near Barrie, with data coming from <a href="http://flightradar24.com/" target="_blank">FlightRadar24</a>. It was setup so that FlightRadar24 would email me, and then from there posted to a Twitter account, and from there eventually to a database. Over time I have managed to find better sources and an additional need for data to become what it is today.</p>
    
    <p>Currently this website hosts a large number of interesting statistics. The <a href="<?php print $globalURL; ?>/statistics">statistic pages</a> and any of the individual pages (such as aircraft, airlines, airports, routes etc.) have been designed based on what I actually wanted to see. It kind of served my needs to see the information in this database and I hope it will be useful for you too. And if you ever feel like you just can't find anything in particular you can always play to your heart's content in the <a href="<?php print $globalURL; ?>/search">search page</a> and combine any parameter to your liking.</p>
    
    <p>I continue to make this database as useful as possible and evolve it over time. If you find any issues, data discrepancy or just want to give your feedback &amp; suggestions <a href="<?php print $globalURL; ?>/contact">contact me</a>. I'll be very happy to assist anybody with any questions about the data or how to find it.</p>
    
    <a name="coverage"></a>    
    <h3>Coverage</h3>
    
    <p>I have set up a geofence of roughly 40-50km around Barrie, from the edge of Southern Georgian Bay to southern part of Innisfil and from around Collingwood eastward to just east of Lake Simcoe. The coverage area was designed for planes that are visible within the Barrie area. All airplanes flying above 30,000 feet are visible throughout the entire area from Barrie, as long as the weather cooperates off course. The map below points out the coverage area:</p>
    
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <script>
		function initialize() {

		  var barrie = new google.maps.LatLng(44.413333,-79.68);
		  var mapOptions = {
		    zoom: 8,
		    center: barrie,
		     streetViewControl: false,
		     panControl: false,
		     styles: [
							    {
							        "featureType": "water",
							        "elementType": "all",
							        "stylers": [
							            {
							                "hue": "#bbbbbb"
							            },
							            {
							                "saturation": -100
							            },
							            {
							                "lightness": -4
							            },
							            {
							                "visibility": "on"
							            }
							        ]
							    },
							    {
							        "featureType": "landscape",
							        "elementType": "all",
							        "stylers": [
							            {
							                "hue": "#999999"
							            },
							            {
							                "saturation": -100
							            },
							            {
							                "lightness": -33
							            },
							            {
							                "visibility": "on"
							            }
							        ]
							    },
							    {
							        "featureType": "road",
							        "elementType": "all",
							        "stylers": [
							            {
							                "hue": "#999999"
							            },
							            {
							                "saturation": -100
							            },
							            {
							                "lightness": -6
							            },
							            {
							                "visibility": "on"
							            }
							        ]
							    },
							    {
							        "featureType": "poi",
							        "elementType": "all",
							        "stylers": [
							            {
							                "hue": "#aaaaaa"
							            },
							            {
							                "saturation": -100
							            },
							            {
							                "lightness": -15
							            },
							            {
							                "visibility": "on"
							            }
							        ]
							    }
							]
		  }

		  var map = new google.maps.Map(document.getElementById("map"), mapOptions);
		  
		  var coverageCoords = [
		    new google.maps.LatLng(44.067853669357596, -80.22216796875),
		    new google.maps.LatLng(44.067853669357596, -79.06036376953125),
				new google.maps.LatLng(44.734052347483086, -79.06036376953125),
		    new google.maps.LatLng(44.734052347483086, -80.22216796875)
		  ];
		
		  // Construct the polygon.
		  var coverageArea = new google.maps.Polygon({
		    paths: coverageCoords,
		    strokeColor: '#1a3151',
		    strokeOpacity: 0.75,
		    strokeWeight: 1,
		    fillColor: '#1a3151',
		    fillOpacity: 0.20
		  });
		
		  coverageArea.setMap(map);
		}
		
		google.maps.event.addDomListener(window, "load", initialize);
    </script>
    <div id="map"></div>
    
    <br /><br />
    
    <a name="source"></a>
    <h3>Source &amp; Credits</h3>
    
     <p>The data from FlightAware is coming from multiple sources. Not every aircraft is tracked on FlightAware, especially not older aircrafts as well as government aircrafts, however most modern airliners will work. You can learn more about how it works on <a href="http://flightaware.com/adsb/" target="_blank">FlightAware's ADS-B</a> page. Also, not every aircraft is shown to have flown exactly at that minute as seen on this site (aka real-time). There is a 5 minute delay on some of the sources.</p>
    
    <p>However, none of this project would have been possible without the help and contributions of these organizations and people:</p>
    
    <ul>
    	<li><a href="http://flightaware.com" target="_blank">FlightAware</a> - I use their API to access the data which allows me to get additional information. Data sources come from live ADS-B, FAA and other government agencies.</li>
    	<li><a href="http://sonicgoose.com" target="_blank">Sonic Goose (Rob Jones)</a> - Contributes ADS-B data within the Greater Toronto Area and South-central Ontario to numerous flight tracking sites, including FlightAware. A very big thank you to him! :)</li>
    </ul>
    
    <h3>Data License</h3>
    
    <p>The data published by Barrie Spotter is made available under the Open Database License: <a href="http://opendatacommons.org/licenses/odbl/1.0/" target="_blank">http://opendatacommons.org/licenses/odbl/1.0/</a>. Any rights in individual contents of the database are licensed under the Database Contents License: <a href="http://opendatacommons.org/licenses/dbcl/1.0/" target="_blank">http://opendatacommons.org/licenses/dbcl/1.0/</a> - See more at: <a href="http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf" target="_blank">http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf</a></p>

</div>

<?php
require('footer.php');
?>