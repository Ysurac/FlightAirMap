<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Home";
require('header.php');
?>

  <div id="live-map"></div>

  <a class="button zoom-in" href="#" onclick="zoomInMap(); return false;" title="Zoom in"><i class="fa fa-plus"></i></a>
  <a class="button zoom-out" href="#" onclick="zoomOutMap(); return false;" title="Zomm out"><i class="fa fa-minus"></i></a>
  <a class="button geocode" href="#" onclick="getUserLocation(); return false;" title="Plot your Location"><i class="fa fa-map-marker"></i></a>
  <a class="button compass" href="#" onclick="getCompassDirection(); return false;" title="Compass Mode"><i class="fa fa-compass"></i></a>
    <a class="button weatherradar" href="#" onclick="showWeatherRadar(); return false;" title="Weather Radar"><i class="fa fa-bullseye"></i></a>
    <a class="button weathersatellite" href="#" onclick="showWeatherSatellite(); return false;" title="Weather Satellite"><i class="fa fa-globe"></i></a>

<?php
require('footer.php');
?>