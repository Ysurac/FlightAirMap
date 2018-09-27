<?php
if (isset($globalProtect) && $globalProtect) {
	@session_start();
	$_SESSION['protect'] = 'protect';
}
//gets the page file and stores it in a variable
$file_path = pathinfo($_SERVER['SCRIPT_NAME']);
$current_page = $file_path['filename'];
if ($globalTimezone == '') $globalTimezone = 'UTC';
date_default_timezone_set($globalTimezone);
if (isset($_COOKIE['MapType']) && $_COOKIE['MapType'] != '') $MapType = $_COOKIE['MapType'];
else $MapType = $globalMapProvider;

if (isset($globalMapOffline) && $globalMapOffline) $MapType = 'offline';

if (isset($_GET['3d'])) {
	setcookie('MapFormat','3d');
} else if (isset($_GET['2d'])) {
	setcookie('MapFormat','2d');
}

if (isset($globalTSK) && $globalTSK && isset($_GET['tsk'])) {
	$tsk = filter_input(INPUT_GET,'tsk',FILTER_SANITIZE_URL);
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
<meta name="description" content="<?php print $title; ?> | <?php print $globalName; ?> use FlightAirMap" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="<?php print $globalURL; ?>/favicon.ico">
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<!--[if lt IE 9]>
  <script type="text/javascript" src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script type="text/javascript" src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/js/bootstrap-3.3.7-dist/css/bootstrap.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/jquery-ui.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-datetimepicker.min.css" />  
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/font-awesome-4.7.0/css/font-awesome.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-select.min.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/print.css" />

<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-ui.min.js"></script>

<script type="text/javascript" src="<?php print $globalURL; ?>/js/moment.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/moment-timezone-with-data.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/bootstrap-select.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/script.js"></script>

<?php
if (strtolower($current_page) == "about")
{
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<?php
}
?>
<?php
if (strtolower($current_page) == "search")
{
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/search.js"></script>
<?php
}
?>
<?php
if (strtolower($current_page) == "index")
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print date("H"); ?>" />
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-sidebar.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery-sidebar.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.common.js"></script>
<?php 
	if ((!isset($_COOKIE['MapFormat']) && isset($globalMap3Ddefault) && $globalMap3Ddefault) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d')) {
?>
<?php 
		if (file_exists(dirname(__FILE__).'/js/Cesium/Cesium.js')) {
		// || isset($globalOffline) && $globalOffline) {
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/js/Cesium/Widgets/widgets.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/Cesium/Cesium.js"></script>
<?php
		} else {
?>
<link rel="stylesheet" href="https://cesiumjs.org/releases/1.47/Build/Cesium/Widgets/widgets.css" />
<script type="text/javascript" src="https://cesiumjs.org/releases/1.47/Build/Cesium/Cesium.js"></script>
<?php
		}
?>
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/cesium-minimap.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/cesium-minimap.js"></script>
<?php
	} else {
?>

<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-velocity.min.js"></script>
<link rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-velocity.min.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.textpath.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-terminator.js"></script>

<?php
		if (isset($_COOKIE['Map2DBuildings']) && $_COOKIE['Map2DBuildings'] == 'true') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/OSMBuildings-Leaflet.js"></script>
<?php
		}
?>
<?php
		if ($globalMapProvider == 'MapboxGL' || (isset($_COOKIE['MapType']) && $_COOKIE['MapType'] == 'MapboxGL')) {
?>
<link href="https://cdn.osmbuildings.org/mapbox-gl/0.40.0/mapbox-gl.css" rel='stylesheet' />
<script type="text/javascript" src="https://cdn.osmbuildings.org/mapbox-gl/0.40.0/mapbox-gl.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-mapbox-gl.js"></script>
<?php
		}
?>

<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<!--<script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script type="text/javascript" src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script type="text/javascript" src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script src="https://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
<?php
		}
	}
?>
<?php 
	if ((!isset($_COOKIE['MapFormat']) && (!isset($globalMap3Ddefault) || !$globalMap3Ddefault)) || (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] != '3d')) {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-playback.js"></script>
<?php 
		if (isset($_POST['archive'])) {
?>
<!--
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.timedimension.control.min.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/iso8601.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.timedimension.src.js"></script>
-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.2d.js.php?<?php print time(); ?>&archive&begindate=<?php print strtotime($_POST['start_date']); ?>&enddate=<?php print strtotime($_POST['end_date']); ?>&archivespeed=<?php print $_POST['archivespeed']; ?>"></script>
<?php
		} else {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.2d.js.php?<?php print time(); ?><?php if (isset($tsk)) print '&tsk='.$tsk; ?>"></script>
<?php
		}
		if (!isset($globalAircraft) || $globalAircraft) {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-aircraft.2d.js.php?<?php print time(); ?>"></script>
<?php
		}
		if (isset($globalTracker) && $globalTracker) {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-tracker.2d.js.php?<?php print time(); ?>"></script>
<?php
		}
		if (isset($globalMarine) && $globalMarine) {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-marine.2d.js.php?<?php print time(); ?>"></script>
<?php
		}
		if (isset($globalSatellite) && $globalSatellite) {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-satellite.2d.js.php?<?php print time(); ?>"></script>
<?php
		}
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
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.common.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.2d.js.php?ident=<?php print $ident; ?><?php if(isset($latitude)) print '&latitude='.$latitude; ?><?php if(isset($longitude)) print '&longitude='.$longitude; ?>&<?php print time(); ?>"></script>
<?php
		if (!isset($type) || $type == 'aircraft') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-aircraft.2d.js.php?<?php print time(); ?>&ident=<?php print $ident; ?>"></script>
<?php
		} elseif (isset($type) && $type == 'marine') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-marine.2d.js.php?<?php print time(); ?>&ident=<?php print $ident; ?>"></script>
<?php
		} elseif (isset($type) && $type == 'tracker') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-tracker.2d.js.php?<?php print time(); ?>&ident=<?php print $ident; ?>"></script>
<?php
		} elseif (isset($type) && $type == 'satellite') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-satellite.2d.js.php?<?php print time(); ?>&ident=<?php print $ident; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<!--<script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script type="text/javascript" src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script type="text/javascript" src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script type="text/javascript" src="http://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
<?php
		}
?>
<?php
}

if (strtolower($current_page) == "flightid-overview" && isset($globalArchive) && $globalArchive && isset($flightaware_id) && (isset($latitude) && $latitude != 0) && (isset($longitude) && $longitude != 0))
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print date("H"); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet.css" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/leaflet-sidebar.css" />
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/Marker.Rotate.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/MovingMarker.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/jquery.idle.min.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.common.js"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map.2d.js.php?flightaware_id=<?php print $flightaware_id; ?><?php if(isset($latitude)) print '&latitude='.$latitude; ?><?php if(isset($longitude)) print '&longitude='.$longitude; ?>&<?php print time(); ?>"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/map-aircraft.2d.js.php?flightaware_id=<?php print $flightaware_id; ?>&<?php print time(); ?>"></script>
<?php
		if (isset($globalGoogleAPIKey) && $globalGoogleAPIKey != '' && ($MapType == 'Google-Roadmap' || $MapType == 'Google-Satellite' || $MapType == 'Google-Hybrid' || $MapType == 'Google-Terrain')) {
?>
<script type="text/javascript" src="https://maps.google.com/maps/api/js?v=3&key=<?php print $globalGoogleAPIKey; ?>"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Google.js"></script>
<?php
		}
?>
<?php
		if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
<!--<script type="text/javascript" src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise"></script>-->
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Bing.js"></script>
<?php
		}
?>
<?php
		if (isset($globalMapQuestKey) && $globalMapQuestKey != '' && ($MapType == 'MapQuest-OSM' || $MapType == 'MapQuest-Hybrid' || $MapType == 'MapQuest-Aerial')) {
?>
<!--<script type="text/javascript" src="https://www.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>-->
<script type="text/javascript" src="https://open.mapquestapi.com/sdk/leaflet/v2.2/mq-map.js?key=<?php print $globalMapQuestKey; ?>"></script>
<?php
		}
?>
<?php
		if (isset($globalHereappId) && $globalHereappId != '' && isset($globalHereappCode) && $globalHereappCode != '') {
?>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Here.js"></script>
<?php
		}
?>
<?php
		if ($MapType == 'Yandex') {
?>
<script type="text/javascript" src="http://api-maps.yandex.ru/2.0/?load=package.map&lang=en_US" type="text/javascript"></script>
<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet-Yandex.js"></script>
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
<?php
if (isset($globalCustomCSS) && $globalCustomCSS != '') {
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalCustomCSS; ?>" />
<?php
}
?>
<?php
if (isset($globalPubHeader)) print $globalPubHeader;
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
      <a href="<?php print $globalURL; ?>/search" class="navbar-toggle search"><i class="fa fa-search"></i></a>
      <a class="navbar-brand" href="<?php if ($globalURL == '') print '/'; else print $globalURL; ?>"><img src="<?php print $globalURL.$logoURL; ?>" height="30px" /></a>
    </div>
    <div class="collapse navbar-collapse">

      <ul class="nav navbar-nav">
<?php
    if (isset($globalNewsFeeds['global']) && !empty($globalNewsFeeds['global'])) {
?>
    <li><a href="<?php print $globalURL; ?>/news"><?php echo _("News"); ?></a></li>
<?php
    }
?>

<?php 
    $sub = false;
    if (
	(
	    (!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft === TRUE)) && ((isset($globalMarine) && $globalMarine === TRUE) || (isset($globalTracker) && $globalTracker === TRUE) || (isset($globalSatellite) && $globalSatellite === TRUE))
	) || 
	(
	    isset($globalMarine) && $globalMarine === TRUE && ((isset($globalTracker) && $globalTracker === TRUE) || (isset($globalSatellite) && $globalSatellite === TRUE))
	) || 
	(
	    isset($globalTracker) && $globalTracker === TRUE && ((isset($globalMarine) && $globalMarine === TRUE) || (isset($globalSatellite) && $globalSatellite === TRUE))
	) || 
	(
	    isset($globalSatellite) && $globalSatellite === TRUE && ((isset($globalMarine) && $globalMarine === TRUE) || (isset($globalTracker) && $globalTracker === TRUE))
	)
    ) {
	$sub = true;
    }
?>
<?php
    if (!isset($globalAircraft) || $globalAircraft === TRUE) {
?>
    <li class="dropdown">
<?php
	if ($sub) {
?>
      	<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Aircraft"); ?> <b class="caret"></b></a>
	<ul class="dropdown-menu multi-level">
      	<li class="dropdown-submenu">
<?php
        }
?>
<?php
	if (isset($globalNewsFeeds['aircraft']) && !empty($globalNewsFeeds['aircraft'])) {
?>
    <a href="<?php print $globalURL; ?>/news-aircraft"><?php echo _("Aircraft News"); ?></a></li>
    <li>
<?php
	}
?>

          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Explore"); ?> <b class="<?php if ($sub) echo 'right-'; ?>caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/aircraft"><?php echo _("Aircraft Types"); ?></a></li>
<?php
    if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
?>
			<li><a href="<?php print $globalURL; ?>/airline"><?php echo _("Airlines"); ?></a></li>
<?php
    }
?>
			<li><a href="<?php print $globalURL; ?>/airport"><?php echo _("Airports"); ?></a></li>
<?php
    if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
?>
			<li><a href="<?php print $globalURL; ?>/owner"><?php echo _("Owners"); ?></a></li>
<?php
    } 
    if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {

?>
			<li><a href="<?php print $globalURL; ?>/pilot"><?php echo _("Pilots"); ?></a></li>
<?php
    }
?>
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
    	    <?php
    	        if (isset($globalAccidents) && $globalAccidents) {
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/accident-latest"><?php echo _("Latest accident"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/accident/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Accident"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/incident-latest"><?php echo _("Latest incident"); ?></a></li>
            <li><a href="<?php print $globalURL; ?>/incident/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Incident"); ?></a></li>
            <?php
        	}
    	    ?>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/highlights/table"><?php echo _("Special Highlights"); ?></a></li>
            <?php
		if (!isset($globalNoUpcoming) || $globalNoUpcoming === FALSE) {
	    ?>
            <li><a href="<?php print $globalURL; ?>/upcoming"><?php echo _("Upcoming Flights"); ?></a></li>
	    <?php
		}
	    ?>
          </ul>
        </li>
      	<li><a href="<?php print $globalURL; ?>/search"><?php echo _("Search"); ?></a></li>
      	<li><a href="<?php print $globalURL; ?>/statistics"><?php echo _("Statistics"); ?></a></li>
        <li class="dropdown<?php if ($sub) echo '-submenu'; ?>">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Tools"); ?> <b class="<?php if ($sub) echo 'right-'; ?>caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/tools/acars"><?php echo _("ACARS translator"); ?></a></li>
          	<li><a href="<?php print $globalURL; ?>/tools/metar"><?php echo _("METAR translator"); ?></a></li>
          	<li><a href="<?php print $globalURL; ?>/tools/notam"><?php echo _("NOTAM translator"); ?></a></li>
<?php
	if (isset($globalGeoid) && $globalGeoid) {
?>
          	<li><a href="<?php print $globalURL; ?>/tools/geoid"><?php echo _("Geoid Height Calculator"); ?></a></li>
<?php
	}
?>
          </ul>
        </li>
<?php 
	if ($sub) {
?>
    </li>
    </ul>
<?php
	}
    }
?>
<?php
    if (isset($globalMarine) && $globalMarine) {
?>
    <li class="dropdown">
<?php
        if ($sub) {
?>
    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Marines"); ?> <b class="caret"></b></a>
	<ul class="dropdown-menu multi-level">
	    <li class="dropdown-submenu">
<?php
	}
?>
<?php
	if (isset($globalNewsFeeds['marine']) && !empty($globalNewsFeeds['marine'])) {
?>
    <a href="<?php print $globalURL; ?>/marine/news"><?php echo _("Marines News"); ?></a></li>
    <li>
<?php
	}
?>
		<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Explore"); ?> <b class="<?php if ($sub) echo 'right-'; ?>caret"></b></a>
		<ul class="dropdown-menu">
<?php
	if (isset($globalVM) && $globalVM) {
?>
		    <li><a href="<?php print $globalURL; ?>/marine/captain"><?php echo _("Captains"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/marine/race"><?php echo _("Races"); ?></a></li>
		    <li><hr /></li>
<?php
	}
?>
		    <li><a href="<?php print $globalURL; ?>/marine/currently"><?php echo _("Current Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/marine/latest"><?php echo _("Latest Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/marine/date/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Activity"); ?></a></li>
		</ul>
		<li><a href="<?php print $globalURL; ?>/marine/search"><?php echo _("Search"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/marine/statistics"><?php echo _("Statistics"); ?></a></li>
	    </li>
<?php
	if ($sub) {
?>
	</ul>
    </li>
<?php
	}
?>
<?php
    }
?>
<?php
    if (isset($globalTracker) && $globalTracker) {
?>
    <li class="dropdown">
<?php
        if ($sub) {
?>
    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Trackers"); ?> <b class="caret"></b></a>
	<ul class="dropdown-menu multi-level">
	    <li class="dropdown-submenu">
<?php
	}
?>
<?php
	if (isset($globalNewsFeeds['tracker']) && !empty($globalNewsFeeds['tracker'])) {
?>
    <a href="<?php print $globalURL; ?>/tracker/news"><?php echo _("Trackers News"); ?></a></li>
    <li>
<?php
	}
?>
		<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Explore"); ?> <b class="<?php if ($sub) echo 'right-'; ?>caret"></b></a>
		<ul class="dropdown-menu">
		    <li><a href="<?php print $globalURL; ?>/tracker/currently"><?php echo _("Current Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/tracker/latest"><?php echo _("Latest Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/tracker/date/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Activity"); ?></a></li>
		</ul>
	    </li>
	    <li><a href="<?php print $globalURL; ?>/tracker/search"><?php echo _("Search"); ?></a></li>
	    <li><a href="<?php print $globalURL; ?>/tracker/statistics"><?php echo _("Statistics"); ?></a></li>
<?php
	if ($sub) {
?>
	</ul>
    </li>
<?php
	}
?>
<?php
    }
?>
<?php
    if (isset($globalSatellite) && $globalSatellite) {
?>
    <li class="dropdown">
<?php
        if ($sub) {
?>
    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Satellites"); ?> <b class="caret"></b></a>
	<ul class="dropdown-menu multi-level">
	    <li class="dropdown-submenu">
<?php
	}
?>
<?php
	if (isset($globalNewsFeeds['satellite']) && !empty($globalNewsFeeds['satellite'])) {
?>
    <a href="<?php print $globalURL; ?>/marine/news"><?php echo _("Satellites News"); ?></a></li>
    <li>
<?php
	}
?>

<!--
		<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("Explore"); ?> <b class="<?php if ($sub) echo 'right-'; ?>caret"></b></a>
		<ul class="dropdown-menu">
		    <li><a href="<?php print $globalURL; ?>/satellite/currently"><?php echo _("Current Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/satellite/latest"><?php echo _("Latest Activity"); ?></a></li>
		    <li><a href="<?php print $globalURL; ?>/satellite/date/<?php print date("Y-m-d"); ?>"><?php echo _("Today's Activity"); ?></a></li>
		</ul>
	    </li>
-->
	    <li><a href="<?php print $globalURL; ?>/satellite/statistics"><?php echo _("Statistics"); ?></a></li>
<?php
	if ($sub) {
?>
	</ul>
    </li>
<?php
	}
?>
<?php
    }
?>

        <li class="dropdown">
          <a href="<?php print $globalURL; ?>/about" class="dropdown-toggle" data-toggle="dropdown"><?php echo _("About"); ?> <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/about"><?php echo _("About The Project"); ?></a></li>
<?php
    if (!isset($globalAircraft) || $globalAircraft === TRUE) {
?>
          	<li><a href="<?php print $globalURL; ?>/about/export"><?php echo _("Exporting Data"); ?></a></li>
		<li><hr /></li>
		<li><a href="<?php print $globalURL; ?>/about/tv"><?php echo _("Spotter TV"); ?></a></li>
<?php
    }
?>
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
		$Language = new Language();
  		$alllang = $Language->getLanguages();
		if (count($alllang) > 1) {
?>
  	<div class="language">
  	    <form>
  		<select class="selectpicker" data-width="120px" onchange="language(this);">
  		    <?php
  		        foreach ($alllang as $key => $lang) {
  		            print '<option value="'.$key.'"';
  		            if (isset($_COOKIE['language']) && $_COOKIE['language'] == $key) print ' selected ';
  		            if ($lang[0] == 'Deutsch') print '>'.$lang[0].' (&beta;eta)</option>';
  		            else print '>'.$lang[0].'</option>';
  		        }
  		    ?>
  		</select>
  	    </form>
  	</div>
<?php
		}
	}
?>
      <div class="search">
	<form action="<?php print $globalURL; ?>/search" method="get">
		<!--<input type="text" name="q" value="<?php if (isset($GET['q'])) { if ($_GET['q'] != ""){ print $_GET['q']; } else { print _("Search"); } } else { print _("Search"); } ?>" onfocus="if (this.value=='search'){this.value='';}" /><button type="submit"><i class="fa fa-search"></i></button>-->
		<input type="text" name="callsign" value="<?php if (isset($GET['callsign'])) { if ($_GET['callsign'] != ""){ print $_GET['callsign']; } else { print _("Search"); } } else { print _("Search"); } ?>" onfocus="if (this.value=='search'){this.value='';}" /><button type="submit"><i class="fa fa-search"></i></button>
	</form>
	</div>
  	<div class="social">
  		<!-- I'm not sociable '-->
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
	<script type="text/javascript" src="<?php print $globalURL; ?>/js/leaflet.js"></script>

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
    } elseif (strpos(strtolower($current_page),'route-') !== false && strpos(strtolower($current_page),'statistics-') === false && !isset($spotter_array[0]['departure_airport_latitude']) && isset($spotter_array[0]['latitude'])) {
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
    } elseif ($globalMapProvider == 'Mapbox-GL') {
?>
    L.mapboxGL({
	accessToken: '<?php print $globalMapboxToken; ?>',
	style: 'mapbox://styles/mapbox/bright-v8'
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
    } elseif (isset($globalMapCustomLayer[$globalMapProvider])) {
	$customid = $globalMapProvider;
?>
    L.tileLayer('<?php print $globalMapCustomLayer[$customid]['url']; ?>/{z}/{x}/{y}.png', {
        maxZoom: <?php if (isset($globalMapCustomLayer[$customid]['maxZoom'])) print $globalMapCustomLayer[$customid]['maxZoom']; else print '18'; ?>,
        minZoom: <?php if (isset($globalMapCustomLayer[$customid]['minZoom'])) print $globalMapCustomLayer[$customid]['minZoom']; else print '0'; ?>,
        noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
        attribution: '<?php print $globalMapCustomLayer[$customid]['attribution']; ?>'
    }).addTo(map);
<?php
    } elseif ($globalMapProvider == 'offline' || (isset($globalMapOffline) && $globalMapOffline === TRUE)) {
?>
    var center = map.getCenter();
    map.options.crs = L.CRS.EPSG4326;
    map.setView(center);
    map._resetView(map.getCenter(), map.getZoom(), true);
    L.tileLayer('<?php print $globalURL; ?>/js/Cesium/Assets/Textures/NaturalEarthII/{z}/{x}/{y}.jpg', {
        minZoom: 0,
        maxZoom: 5,
        tms : true,
        zindex : 3,
        noWrap: <?php if (isset($globalMapWrap) && !$globalMapWrap) print 'false'; else print 'true'; ?>,
        attribution: 'Natural Earth'
    }).addTo(map);
<?php
    //} elseif ($globalMapProvider == 'OpenStreetMap') {
    } else {
?>
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://www.openstreetmap.org/copyright">Open Database Licence</a>'
  }).addTo(map);

<?php
    }
?>
        </script>
    </div>
    <?php
}

?>

<section class="container main-content <?php if (strtolower($current_page) == 'index') print 'index '; ?>clear">
