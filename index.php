<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Home";
require('header.php');
?>

  <div id="live-map"></div>

  <a class="button zoom-in" href="#" onclick="zoomInMap(); return false;"><i class="fa fa-plus"></i></a>
  <a class="button zoom-out" href="#" onclick="zoomOutMap(); return false;"><i class="fa fa-minus"></i></a>
  <a class="button geocode" href="#" onclick="getUserLocation(); return false;"><i class="fa fa-map-marker"></i></a>
  <a class="button compass" href="#" onclick="getCompassDirection(); return false;"><i class="fa fa-compass"></i></a>

<?php
require('footer.php');
?>