<?php

//require_once('require/class.Language.php');
//gets the page file and stores it in a variable
$file_path = pathinfo($_SERVER['SCRIPT_NAME']);
$current_page = $file_path['filename'];
date_default_timezone_set($globalTimezone);
if (isset($_COOKIE['MapType']) && $_COOKIE['MapType'] != '') $MapType = $_COOKIE['MapType'];
else $MapType = $globalMapProvider;
if (isset($_GET['3d'])) {
	setcookie('MapFormat','3d');
} else if (isset($_GET['2d'])) {
	setcookie('MapFormat','2d');
}

if (isset($_POST['archive'])) {
	setcookie('archive','true');
	setcookie('archive_begin',strtotime($_POST['start_date']));
	setcookie('archive_end',strtotime($_POST['end_date']));
	setcookie('archive_speed',$_POST['archivespeed']);
}
if (isset($_POST['noarchive'])) {
	setcookie('archive','false',-1);
	setcookie('archive_begin','',-1);
	setcookie('archive_end','',-1);
	setcookie('archive_speed','',-1);
}
// When button "Remove all filters" is clicked
if (isset($_POST['removefilters'])) {
	$allfilters = array_filter(array_keys($_COOKIE),function($key) {
	    return strpos($key,'filter_') === 0;
	});
	foreach ($allfilters as $filt) {
		unset($_COOKIE[$filt]);
		setcookie($filt,null,-1);
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=10" />
<title><?php print $title; ?> | <?php print $globalName; ?></title>
<meta name="keywords" content="<?php print $title; ?> spotter live flight tracking tracker map aircraft airline airport history database ads-b acars" />
<meta name="description" content="<?php print $title; ?> | <?php print $globalName; ?> use FlightAirMap to display flight in his area" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="<?php print $globalURL; ?>/favicon.ico">
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/js/bootstrap-3.3.5-dist/css/bootstrap.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/jquery-ui.min.css" />
<!--<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/jquery-ui-timepicker-addon.min.css" />-->
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-datetimepicker.min.css" />  
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/font-awesome-4.5.0/css/font-awesome.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-select.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/print.css" />

<!--<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-2.2.3.min.js"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-3.1.1.min.js"></script>

<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-ui.min.js"></script>

<script type="text/javascript" src="<?php print $globalURL; ?>/js/moment.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-3.3.5-dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-datetimepicker.min.js"></script>
<!--<script type="text/javascript" src="https://www.google.com/jsapi"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-select.min.js"></script>
<!--<script src="<?php print $globalURL; ?>/js/jquery-ui-timepicker-addon.js"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/script.js"></script>

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
if (strtolower($current_page) == "search")
{
?>
<script src="<?php print $globalURL; ?>/js/search.js"></script>
<?php
}
?>
<?php
if (strtolower($current_page) == "index")
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print date("H"); ?>" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-sidebar.css" />
<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/leaflet.ajax.min.js"></script>
<!--<script src="<?php print $globalURL; ?>/js/leaflet-sidebar.js"></script>-->
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script src="<?php print $globalURL; ?>/js/jquery-sidebar.js"></script>
<?php 
	if ((!isset($_COOKIE['MapFormat']) && isset($globalMap3Ddefault) && $globalMap3Ddefault) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d')) {
?>
<?php 
	if (isset($globalBeta) && $globalBeta) {
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/js/Cesium/Widgets/widgets.css" />
<script src="<?php print $globalURL; ?>/js/Cesium/Cesium.js"></script>
<?php
	} else {
?>
<link rel="stylesheet" href="https://cesiumjs.org/releases/1.28/Build/Cesium/Widgets/widgets.css" />
<script src="https://cesiumjs.org/releases/1.28/Build/Cesium/Cesium.js"></script>
<?php
	}
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/cesium-minimap.css" />
<script src="<?php print $globalURL; ?>/js/cesium-minimap.js"></script>
<?php
	} else {
?>
<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script src="https://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
<?php
		}
	}
?>
<?php 
    if (isset($_POST['archive'])) {
?>
<?php 
	    if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>

<script src="<?php print $globalURL; ?>/js/map.js.php?<?php print time(); ?>&archive&begindate=<?php print strtotime($_POST['start_date']); ?>&enddate=<?php print strtotime($_POST['end_date']); ?>&archivespeed=<?php print $_POST['archivespeed']; ?>"></script>
<?php    
	    }
    } else {
?>
<?php
/*	if (isset($globalBeta) && $globalBeta) {
?>
<script src="<?php print $globalURL; ?>/js/leaflet-realtime.js"></script>
<script src="<?php print $globalURL; ?>/js/map.new.js.php?<?php print time(); ?>"></script>
<?php
	} else {
*/
?>
<?php 
	    if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
<?php
		if (isset($globalBeta) && $globalBeta) {
?>
<script src="<?php print $globalURL; ?>/js/leaflet-playback.js"></script>
<?php
		}
?>
<script src="<?php print $globalURL; ?>/js/map.js.php?<?php print time(); ?>"></script>
<?php
	    }
?>
<?php
//	}
?>
<?php
    }
}
?>
<?php
//if ((strtolower($current_page) == "ident-detailed" && isset($ident)) || strtolower($current_page) == "flightid-overview")
//if ((strtolower($current_page) == "ident-detailed" && isset($ident) && isset($globalArchive) && $globalArchive))
if ((strtolower($current_page) == "ident-detailed" && isset($ident) && isset($globalArchive) && $globalArchive))
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print date("H"); ?>" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-sidebar.css" />
<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/leaflet.ajax.min.js"></script>
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script src="<?php print $globalURL; ?>/js/map.js.php?ident=<?php print $ident; ?><?php if(isset($latitude)) print '&latitude='.$latitude; ?><?php if(isset($longitude)) print '&longitude='.$longitude; ?>&<?php print time(); ?>"></script>
<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script src="http://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
<?php
		}
?>
<?php
}

if (strtolower($current_page) == "flightid-overview" && isset($globalArchive) && $globalArchive && isset($flightaware_id))
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print date("H"); ?>" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-sidebar.css" />
<script src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/leaflet.ajax.min.js"></script>
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script src="<?php print $globalURL; ?>/js/map.js.php?flightaware_id=<?php print $flightaware_id; ?><?php if(isset($latitude)) print '&latitude='.$latitude; ?><?php if(isset($longitude)) print '&longitude='.$longitude; ?>&<?php print time(); ?>"></script>
<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script src="http://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
<?php
		}
?>
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
      <a class="navbar-brand" href="<?php if ($globalURL == '') print '/'; else print $globalURL; ?>"><img src="<?php print $globalURL.$logoURL; ?>" height="30px" /></a>
    </div>
    <div class="collapse navbar-collapse">
      <ul class="nav navbar-nav">
      	<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Explore"); ?> <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/aircraft"><?php echo _("Aircrafts Types"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/airline"><?php echo _("Airlines"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/airport"><?php echo _("Airports"); ?></a></li>
			<li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/currently"><?php echo _("Current Activity"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/latest"><?php echo _("Latest Activity"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/date/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Activity"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/newest"><?php echo _("Newest by Category"); ?></a></li>
            <?php
        	if ($globalACARS) {
        	    if (isset($globalDemo) && $globalDemo) {
    	    ?>
            <li><hr /></li>
            <li><i><?php echo _('ACARS data not available publicly'); ?></i></li>
            <li><a href=""><?php echo _('Latest ACARS messages'); ?></a></li>
            <li><a href=""><?php echo _('Archive ACARS messages'); ?></a></li>
            <?php
        	    } else {
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/acars-latest"><?php echo _("Latest ACARS messages"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/acars-archive"><?php echo _("Archive ACARS messages"); ?></a></li>
            <?php
        	    }
        	}
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/highlights/table"><?php echo _("Special Highlights"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/upcoming"><?php echo _("Upcoming Flights"); ?></a></li>
          </ul>
        </li>
      	<li><a href="<?php print $globalURL; ?>/search"><?php echo _("Search"); ?></a></li>
      	<li><a href="<?php print $globalURL; ?>/statistics"><?php echo _("Statistics"); ?></a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Tools"); ?> <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/tools/acars"><?php echo _("ACARS translator"); ?></a></li>
          	<li><a href="<?php print $globalURL; ?>/tools/metar"><?php echo _("METAR translator"); ?></a></li>
          	<li><a href="<?php print $globalURL; ?>/tools/notam"><?php echo _("NOTAM translator"); ?></a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="<?php print $globalURL; ?>/about" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("About"); ?> <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/about"><?php echo _("About The Project"); ?></a></li>
          	<li><a href="<?php print $globalURL; ?>/about/export"><?php echo _("Exporting Data"); ?></a></li>
            <li><hr /></li>
			<li><a href="<?php print $globalURL; ?>/about/tv"><?php echo _("Spotter TV"); ?></a></li>
	    <?php if (isset($globalContribute) && $globalContribute) { ?>
                <li><hr /></li>
                <li><a href="<?php print $globalURL; ?>/contribute"><?php echo _("Contribute"); ?></a></li>
                <li><hr /></li>
	    <?php } ?>
            <?php if ($globalName == 'FlightAirMap') { ?>
                <li><hr /></li>
        	<li><a href="https://github.com/Ysurac/FlightAirMap/issues" target="_blank"><?php echo _("Report any Issues"); ?></a></li>
            <?php } ?>
          </ul>
        </li>
      </ul>
<?php
	if (isset($globalTranslate) && $globalTranslate) {
?>
  	<div class="language">
  	    <form>
  		<select class="selectpicker" data-width="120px" onchange="language(this);">
  		    <?php
  		        $Language = new Language();
  		        $alllang = $Language->getLanguages();
  		        foreach ($alllang as $key => $lang) {
  		            print '<option value="'.$key.'"';
  		            if (isset($_COOKIE['language']) && $_COOKIE['language'] == $key) print ' selected ';
  		            print '>'.$lang[0].'</option>';
  		        }
  		    ?>
  		</select>
  	    </form>
  	</div>
<?php
	}
?>
      <div class="search">
      <form action="<?php print $globalURL; ?>/search" method="get">
		<input type="text" name="q" value="<?php if (isset($GET['q'])) { if ($_GET['q'] != ""){ print $_GET['q']; } else { print _("Search"); } } else { print _("Search"); } ?>" onfocus="if (this.value=='search'){this.value='';}" /><button type="submit"><i class="fa fa-search"></i></button>
	</form>
	</div>
  	<div class="social">
  		<!-- I'm not sociable -->
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

if (strtolower($current_page) =='ident-detailed' || strtolower($current_page) == 'flightid-overview') {
?>
    <div class="top-header clear" role="main">
<?php
    if (isset($longitude) && isset($latitude) && $longitude != 0 && $latitude != 0) {
?>
    <div id="archive-map"></div>
<?php
    }
?>
    </div>
<?php
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
    if (strpos(strtolower($current_page),'airport-') !== false && strpos(strtolower($current_page),'statistics-') === false && isset($airport_array[0]['latitude'])) {
?>
  map = L.map('map', { zoomControl:true }).setView([<?php print $airport_array[0]['latitude']; ?>,<?php print $airport_array[0]['longitude']; ?>], zoom);
<?php
    } elseif (strpos(strtolower($current_page),'airport-') !== false && strpos(strtolower($current_page),'statistics-') === false) {
?>
  map = L.map('map', { zoomControl:true });
<?php
    } elseif (strpos(strtolower($current_page),'route-') !== false && strpos(strtolower($current_page),'statistics-') === false && isset($spotter_array[0]['departure_airport_latitude'])) {
?>
  map = L.map('map', { zoomControl:true }).setView([<?php print $spotter_array[0]['departure_airport_latitude']; ?>,<?php print $spotter_array[0]['arrival_airport_longitude']; ?>]);
    var line = L.polyline([[<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>],[<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>]]).addTo(map);
    map.fitBounds([[<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>],[<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>]]);
    var departure_airport = L.marker([<?php print $spotter_array[0]['departure_airport_latitude']; ?>, <?php print $spotter_array[0]['departure_airport_longitude']; ?>], {icon: L.icon({iconUrl: '<?php print $globalURL; ?>/images/departure_airport.png',iconSize: [16,18],iconAnchor: [8,16]})}).addTo(map);
    var arrival_airport = L.marker([<?php print $spotter_array[0]['arrival_airport_latitude']; ?>, <?php print $spotter_array[0]['arrival_airport_longitude']; ?>], {icon: L.icon({iconUrl: '<?php print $globalURL; ?>/images/arrival_airport.png',iconSize: [16,18],iconAnchor: [8,16]})}).addTo(map);
<?php
    } elseif (strpos(strtolower($current_page),'route-') !== false && strpos(strtolower($current_page),'statistics-') === false && !isset($spotter_array[0]['departure_airport_latitude'])) {
?>
  map = L.map('map', { zoomControl:true }).setView([<?php print $spotter_array[0]['latitude']; ?>,<?php print $spotter_array[0]['longitude']; ?>]);
<?php
    } elseif (!isset($spotter_array[0]['latitude']) && !isset($spotter_array[0]['longitude'])) {
?>
  map = L.map('map', { zoomControl:true });
<?php
    }
?>
  //initialize the layer group for the aircrft markers
  var layer_data = L.layerGroup();

  //a few title layers
<?php
    if ($globalMapProvider == 'Mapbox') {
?>
  L.tileLayer('https://{s}.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={token}', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
      'Imagery © <a href="http://mapbox.com">Mapbox</a>',
    id: '<?php print $globalMapboxId; ?>',
    token : '<?php print $globalMapboxToken; ?>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'OpenStreetMap') {
?>
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://www.openstreetmap.org/copyright">Open Database Licence</a>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'MapQuest-OSM') {
?>
  L.tileLayer('http://otile1.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'MapQuest-Aerial') {
?>
  L.tileLayer('http://otile1.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://www.openstreetmap.org/copyright">Open Database Licence</a>, ' +
      'Tiles Courtesy of <a href="http://www.mapquest.com">MapQuest</a>, Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency"'
  }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'Google-Roadmap') {
?>
    var googleLayer = new L.Google('ROADMAP');
    map.addLayer(googleLayer);
<?php
    } elseif ($globalMapProvider == 'Google-Satellite') {
?>
    var googleLayer = new L.Google('SATELLITE');
    map.addLayer(googleLayer);
<?php
    } elseif ($globalMapProvider == 'Google-Hybrid') {
?>
    var googleLayer = new L.Google('HYBRID');
    map.addLayer(googleLayer);
<?php
    } elseif ($globalMapProvider == 'Google-Terrain') {
?>
    var googleLayer = new L.Google('Terrain');
    map.addLayer(googleLayer);
<?php
    }
?>
        </script>
    </div>
    <?php
}

?>

<section class="container main-content <?php if (strtolower($current_page) == 'index') print 'index '; ?>clear">
