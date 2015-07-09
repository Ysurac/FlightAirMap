<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Home";
require('header.php');
?>

<div id="live-map"></div>
<div id="aircraft_ident"></div>
<a class="button zoom-in" href="#" onclick="zoomInMap(); return false;" title="Zoom in"><i class="fa fa-plus"></i></a>
<a class="button zoom-out" href="#" onclick="zoomOutMap(); return false;" title="Zoom out"><i class="fa fa-minus"></i></a>
<a class="button geocode" href="#" onclick="getUserLocation(); return false;" title="Plot your Location"><i class="fa fa-map-marker"></i></a>
<a class="button compass" href="#" onclick="getCompassDirection(); return false;" title="Compass Mode"><i class="fa fa-compass"></i></a>
<!--
<a class="button weatherradar" href="#" onclick="showWeatherRadar(); return false;" title="Weather Radar"><i class="fa fa-bullseye"></i></a>
<a class="button weathersatellite" href="#" onclick="showWeatherSatellite(); return false;" title="Weather Satellite"><i class="fa fa-globe"></i></a>
-->
<a class="button weatherprecipitation" href="#" onclick="showWeatherPrecipitation(); return false;" title="Weather Precipitation"><i class="fa fa-cloud-download"></i></a>
<a class="button weatherrain" href="#" onclick="showWeatherRain(); return false;" title="Weather Rain"><i class="fa fa-soundcloud"></i></a>
<a class="button weatherclouds" href="#" onclick="showWeatherClouds(); return false;" title="Weather Clouds"><i class="fa fa-cloud"></i></a>
<a class="button waypoints" href="#" onclick="showWaypoints(); return false;" title="Waypoints"><i class="fa fa-exchange"></i></a>
<a class="button airspace" href="#" onclick="showAirspace(); return false;" title="Airspace"><i class="fa fa-share-alt"></i></a>
<?php
    if (isset($globalNOTAM) && $globalNOTAM) {
?>
<a class="button notam" href="#" onclick="showNotam(); return false;" title="NOTAM"><i class="fa fa-circle-o"></i></a>
<?php
    }
require('footer.php');
?>