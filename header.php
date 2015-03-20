<?php
//gets the page file and stores it in a variable
$file_path = pathinfo($_SERVER['SCRIPT_NAME']);
$current_page = $file_path['filename'];
date_default_timezone_set($globalTimezone);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=10" />
<title><?php print $title; ?> | <?php print $globalName; ?></title>
<meta name="keywords" content="<?php print $title; ?> spotter live flight tracking tracker map aircraft airline airport history database ads-b acars" />
<meta name="description" content="<?php print $title; ?> | <?php print $globalName; ?> is an open source project documenting most of the aircrafts that have flown." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" href="<?php print $globalURL; ?>/js/bootstrap-3.3.4-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/jquery-ui.min.css">
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-ui.min.js"></script>
<script src="<?php print $globalURL; ?>/js/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<!--<script type="text/javascript" src="https://www.google.com/jsapi"></script>-->
<script src="<?php print $globalURL; ?>/js/bootstrap-select.min.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/jquery-ui-timepicker-addon.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/script.js?<?php print time(); ?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-select.min.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/print.css?<?php print time(); ?>" />
<?php
if (strtolower($current_page) == "about")
{
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<?php
}
?>
<?php
if (strtolower($current_page) == "index")
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print time(); ?>" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/leaflet.ajax.min.js"></script>
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/map.js.php?<?php print time(); ?>"></script>
<?php
}
?>
<?php
/*
if ($facebook_meta_image != "")
{
?>
<meta property="og:image" content="<?php print $facebook_meta_image; ?>"/>
<?php
} else {
?>
<meta property="og:image" content="<?php print $globalURL; ?>/images/touch-icon.png"/>
<?php
}
*/
?>
<meta property="og:title" content="<?php print $title; ?> | <?php print $globalName; ?>"/>
<meta property="og:url" content="<?php print $globalURL.$_SERVER['REQUEST_URI']; ?>"/>
<meta property="og:site_name" content="<?php print $globalName; ?>"/>
</head>

<body class="page-<?php print strtolower($current_page); ?>">
<div class="navbar navbar-fixed-top" role="navigation">
  <div class="container">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="<?php print $globalURL; ?>/search" class="navbar-toggle navbar-toggle-search"><i class="fa fa-search"></i></a>
      <a class="navbar-brand" href="<?php print $globalURL; ?>"><img src="<?php print $globalURL.$logoURL; ?>" height="30px" /></a>
    </div>
    <div class="collapse navbar-collapse">
      <ul class="nav navbar-nav">
      	<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Explore <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/aircraft">Aircraft Types</a></li>
			<li><a href="<?php print $globalURL; ?>/airline">Airlines</a></li>
			<li><a href="<?php print $globalURL; ?>/airport">Airports</a></li>
			<li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/latest">Latest Activity</a></li>
            <li><a href="<?php print $globalURL; ?>/date/<?php print date("Y-m-d"); ?>">Today's Activity</a></li>
            <li><a href="<?php print $globalURL; ?>/newest">Newest by Category</a></li>
            <?php
        	if ($globalACARS) {
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/acars-latest">Latest ACARS messages</a></li>
            <li><a href="<?php print $globalURL; ?>/acars-archive">Archive ACARS messages</a></li>
            <?php
        	}
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/highlights">Special Highlights</a></li>
            <li><a href="<?php print $globalURL; ?>/upcoming">Upcoming Flights</a></li>
          </ul>
        </li>
      	<li><a href="<?php print $globalURL; ?>/search">Search</a></li>
      	<li><a href="<?php print $globalURL; ?>/statistics">Statistics</a></li>
        <li class="dropdown">
          <a href="<?php print $globalURL; ?>/about" class="dropdown-toggle" data-toggle="dropdown">About <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/about">About The Project</a></li>
          	<li><a href="<?php print $globalURL; ?>/about/export">Exporting Data</a></li>
            <li><hr /></li>
			<li><a href="<?php print $globalURL; ?>/about/tv">Spotter TV</a></li>
            <li><hr /></li>
            
            <?php if ($globalURL == "http://barriespotter.com") { ?>
          	<li><a href="https://github.com/barriespotter/Web_App/issues" target="_blank">Report any Issues</a></li>
          	<li><a href="https://www.facebook.com/barriespotter" target="_blank">Contact</a></li>
            <?php } else { ?>
        	<li><a href="https://github.com/Ysurac/AirRadar/issues" target="_blank">Report any Issues</a></li>
            <?php } ?>
          </ul>
        </li>
      </ul>
      <form action="<?php print $globalURL; ?>/search" method="get">
  			<input type="text" name="q" value="<?php if (isset($GET['q'])) { if ($_GET['q'] != ""){ print $_GET['q']; } else { print 'search'; } } else { print 'search'; } ?>" onfocus="if (this.value=='search'){this.value='';}" /><button type="submit"><i class="fa fa-search"></i></button>
  		</form>
  		<div class="social">
            <?php if ($globalURL == "http://barriespotter.com") { ?>
  			<a href="http://www.facebook.com/barriespotter" target="_blank" title="Like us on Facebook"><i class="fa fa-facebook"></i></a>
  			<a href="http://www.twitter.com/barriespotter" target="_blank" title="Follow us on Twitter"><i class="fa fa-twitter"></i></a>
  			<a href="http://barriespotter.github.io" target="_blank" title="Fork us on Github"><i class="fa fa-github"></i></a>
  		<?php } ?>
  		</div>
    </div><!--/.nav-collapse -->
  </div>
</div>

<?php
if (isset($top_header) && $top_header != "") 
{
	print '<div class="top-header container clear" role="main">';
		print '<img src="'.$globalURL.'/images/'.$top_header.'" alt="'.$title.'" title="'.$title.'" />';
	print '</div>';
}
if ((strpos(strtolower($current_page),'airport-') !== false && strpos(strtolower($current_page),'statistics-') === false) || (strpos(strtolower($current_page),'route-') !== false && strpos(strtolower($current_page),'statistics-') === false))
{
    ?>
    <div class="top-header clear" role="main">
        <div id="map"></div>
	<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
	<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>

        <script>
        var map;
        var zoom = 13;
//create the map
<?php
    if (strpos(strtolower($current_page),'airport-') !== false && strpos(strtolower($current_page),'statistics-') === false) {
?>
  map = L.map('map', { zoomControl:true }).setView([<?php print $airport_array[0]['latitude']; ?>,<?php print $airport_array[0]['longitude']; ?>], zoom);
<?php
    } elseif (strpos(strtolower($current_page),'route-') !== false && strpos(strtolower($current_page),'statistics-') === false) {
?>
  map = L.map('map', { zoomControl:true }).setView([<?php print $spotter_array[0]['departure_airport_latitude']; ?>,<?php print $spotter_array[0]['arrival_airport_longitude']; ?>]);
    var line = L.polyline([[<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>],[<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>]]).addTo(map);
    map.fitBounds([[<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>],[<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>]]);
    var departure_airport = L.marker([<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>], {icon: L.icon({iconUrl: '/images/departure_airport.png',iconSize: [16,18],iconAnchor: [8,16]})}).addTo(map);
    var arrival_airport = L.marker([<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>], {icon: L.icon({iconUrl: '/images/arrival_airport.png',iconSize: [16,18],iconAnchor: [8,16]})}).addTo(map);
<?php
    }
?>
  //initialize the layer group for the aircrft markers
  var layer_data = L.layerGroup();

  //a few title layers
<?php
    if ($globalMapProvider == 'Mapbox') {
?>
  L.tileLayer('https://{s}.tiles.mapbox.com/v3/{id}/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
      'Imagery © <a href="http://mapbox.com">Mapbox</a>',
    id: '<?php print $globalMapboxId; ?>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'OpenStreetMap') {
?>
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'MapQuest-OSM') {
?>
  L.tileLayer('http://otile1.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'MapQuest-Aerial') {
?>
  L.tileLayer('http://otile1.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>, Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency"'
  }).addTo(map);
<?php
    }
?>
        </script>
    </div>
    <?php
}

?>

<section class="container main-content clear">
