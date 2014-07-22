<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=10" />
<meta name="twitter:site" content="@barriespotter" />
<meta name="google-site-verification" content="nToRHEjzFuGDMKUbOlVzgIWSkymvy5u4m96fMRxgNFs" />
<title>Live Map | Barrie Spotter</title>
<meta name="keywords" content="Home barrie ontario canada spotter live flight tracking tracker map aircraft airline airport history database" />
<meta name="description" content="Home | Barrie Spotter is an open source project documenting most of the aircrafts that have flown near the Barrie area." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="http://www.barriespotter.com/images/touch-icon.png">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="<?php print $globalURL; ?>/js/map.js?<?php print time(); ?>"></script>
<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print time(); ?>" />
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/map.js?<?php print time(); ?>"></script>
<meta property="og:image" content="http://www.barriespotter.com/images/touch-icon.png"/>
<meta property="og:title" content="Home | Barrie Spotter"/>
<meta property="og:url" content="http://www.barriespotter.com/live"/>
<meta property="og:site_name" content="Barrie Spotter"/>
</head>

<body class="page-live">

  <div id="map"></div>

  <a class="button zoom-in" href="#" onclick="zoomInMap(); return false;"><i class="fa fa-plus"></i></a>
  <a class="button zoom-out" href="#" onclick="zoomOutMap(); return false;"><i class="fa fa-minus"></i></a>
  <a class="button geocode" href="#" onclick="getUserLocation(); return false;"><i class="fa fa-map-marker"></i></a>
  <a class="button compass" href="#" onclick="getCompassDirection(); return false;"><i class="fa fa-compass"></i></a>

</body>
</html>
