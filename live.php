<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Live Map";
require('header.php');
?>

  <h1>Barrie Spotter LIVE Map (ALPHA version)</h1>

  <p>Note: The data on the map can be 2-5 minutes behind the current time.</p>

  <div id="map"></div>
  
<?php
require('footer.php');
?>
