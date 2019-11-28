<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
if (isset($_GET['marine'])) {
	$type = 'marine';
	require_once('require/class.Marine.php');
	$Marine = new Marine();
} elseif (isset($_GET['tracker'])) {
	$type = 'tracker';
	require_once('require/class.Tracker.php');
	$Tracker = new Tracker();
} else {
	require_once('require/class.Spotter.php');
	require_once('require/class.SpotterArchive.php');
	$Spotter = new Spotter();
	$orderby = $Spotter->getOrderBy();
	$type = 'aircraft';
}
$title = _("Search");

//$page_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$page_url = $_SERVER['REQUEST_URI'];

//$title = "Search";
require_once('header.php');
$sql_date = '';
if (isset($_GET['start_date'])) {
	//for the date manipulation into the query
	if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
		if (strtotime($_GET['start_date']) !== false && strtotime($_GET['end_date']) !== false) {
			//$start_date = $_GET['start_date']." 00:00:00";
			$start_date = date("Y-m-d",strtotime($_GET['start_date']))." 00:00:00";
			//$end_date = $_GET['end_date']." 00:00:00";
			$end_date = date("Y-m-d",strtotime($_GET['end_date']))." 00:00:00";
			$sql_date = $start_date.",".$end_date;
		}
	} else if($_GET['start_date'] != ""){
		if (strtotime($_GET['start_date']) !== false) {
			//$start_date = $_GET['start_date']." 00:00:00";
			$start_date = date("Y-m-d",strtotime($_GET['start_date']))." 00:00:00";
			$sql_date = $start_date;
		}
	} else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
		if (strtotime($_GET['end_date']) !== false) {
			//$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date']." 00:00:00";
			$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".date("Y-m-d",strtotime($_GET['end_date']))." 00:00:00";
			$sql_date = $end_date;
		}
	} else $sql_date = '';
}

if (isset($_GET['highest_altitude'])) {
	//for altitude manipulation
	if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
		$end_altitude = filter_input(INPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $start_altitude.",".$end_altitude;
	} else if($_GET['highest_altitude'] != ""){
		$end_altitude = filter_input(INPUT_GET,'highest_altitude',FILTER_SANITIZE_NUMBER_INT);
		$sql_altitude = $end_altitude;
	} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
		$start_altitude = filter_input(INPUT_GET,'lowest_altitude',FILTER_SANITIZE_NUMBER_INT).",60000";
		$sql_altitude = $start_altitude;
	} else $sql_altitude = '';
} else $sql_altitude = '';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
	if (!isset($_GET['number_results']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		if ($_GET['number_results'] > 1000){
			$_GET['number_results'] = 1000;
		}
		$limit_start = 0;
		$limit_end = filter_input(INPUT_GET,'number_results',FILTER_SANITIZE_NUMBER_INT);
		$absolute_difference = filter_input(INPUT_GET,'number_results',FILTER_SANITIZE_NUMBER_INT);
	}
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = filter_var($limit_explode[0],FILTER_SANITIZE_NUMBER_INT);
	$limit_end = filter_var($limit_explode[1],FILTER_SANITIZE_NUMBER_INT);
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if (
    (isset($_GET['q']) && $_GET['q'] != '') || 
    (isset($_GET['registration']) && $_GET['registration'] != '') || 
    (isset($_GET['aircraft']) && $_GET['aircraft'] != '') ||
    (isset($_GET['manufacturer']) && $_GET['manufacturer'] != '') ||
    (isset($_GET['highlights']) && $_GET['highlights'] != '') ||
    (isset($_GET['airline']) && $_GET['airline'] != '') ||
    (isset($_GET['airline_country']) && $_GET['airline_country'] != '') ||
    (isset($_GET['airline_type']) && $_GET['airline_type'] != '') ||
    (isset($_GET['airport']) && $_GET['airport'] != '') ||
    (isset($_GET['airport_country']) && $_GET['airport_country'] != '') ||
    (isset($_GET['callsign']) && $_GET['callsign'] != '') ||
    (isset($_GET['captain_id']) && $_GET['captain_id'] != '') ||
    (isset($_GET['race_id']) && $_GET['race_id'] != '') ||
    (isset($_GET['captain_name']) && $_GET['captain_name'] != '') ||
    (isset($_GET['race_name']) && $_GET['race_name'] != '') ||
    (isset($_GET['owner']) && $_GET['owner'] != '') ||
    (isset($_GET['pilot_name']) && $_GET['pilot_name'] != '') ||
    (isset($_GET['pilot_id']) && $_GET['pilot_id'] != '') ||
    (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] != '') ||
    (isset($_GET['arrival_airport_route']) && $_GET['arrival_airport_route'] != '') ||
    (isset($_GET['mmsi']) && $_GET['mmsi'] != '') ||
    (isset($_GET['imo']) && $_GET['imo'] != '') ||
    ((isset($_GET['origlat']) && $_GET['origlat'] != '') &&
    (isset($_GET['origlon']) && $_GET['origlon'] != '') &&
    (isset($_GET['dist']) && $_GET['dist'] != ''))
    ){  
	$q = filter_input(INPUT_GET, 'q',FILTER_SANITIZE_STRING);
	$registration = filter_input(INPUT_GET, 'registration',FILTER_SANITIZE_STRING);
	$aircraft = filter_input(INPUT_GET, 'aircraft',FILTER_SANITIZE_STRING);
	$manufacturer = filter_input(INPUT_GET, 'manufacturer',FILTER_SANITIZE_STRING);
	$highlights = filter_input(INPUT_GET, 'highlights',FILTER_SANITIZE_STRING);
	$airline = filter_input(INPUT_GET, 'airline',FILTER_SANITIZE_STRING);
	$airline_country = filter_input(INPUT_GET, 'airline_country',FILTER_SANITIZE_STRING);
	$airline_type = filter_input(INPUT_GET, 'airline_type',FILTER_SANITIZE_STRING);
	$airport = filter_input(INPUT_GET, 'airport',FILTER_SANITIZE_STRING);
	$airport_country = filter_input(INPUT_GET, 'airport_country',FILTER_SANITIZE_STRING);
	$callsign = filter_input(INPUT_GET, 'callsign',FILTER_SANITIZE_STRING);
	$owner = filter_input(INPUT_GET, 'owner',FILTER_SANITIZE_STRING);
	$pilot_name = filter_input(INPUT_GET, 'pilot_name',FILTER_SANITIZE_STRING);
	$pilot_id = filter_input(INPUT_GET, 'pilot_id',FILTER_SANITIZE_STRING);
	$mmsi = filter_input(INPUT_GET, 'mmsi',FILTER_SANITIZE_NUMBER_INT);
	$imo = filter_input(INPUT_GET, 'imo',FILTER_SANITIZE_NUMBER_INT);
	$captain_id  = filter_input(INPUT_GET, 'captain_id',FILTER_SANITIZE_NUMBER_INT);
	$race_id  = filter_input(INPUT_GET, 'race_id',FILTER_SANITIZE_NUMBER_INT);
	$captain_name  = filter_input(INPUT_GET, 'captain_name',FILTER_SANITIZE_STRING);
	$race_name  = filter_input(INPUT_GET, 'race_name',FILTER_SANITIZE_STRING);
	$departure_airport_route = filter_input(INPUT_GET, 'departure_airport_route',FILTER_SANITIZE_STRING);
	$arrival_airport_route = filter_input(INPUT_GET, 'arrival_airport_route',FILTER_SANITIZE_STRING);
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$archive = filter_input(INPUT_GET,'archive',FILTER_SANITIZE_NUMBER_INT);
	$origlat = filter_input(INPUT_GET,'origlat',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	$origlon = filter_input(INPUT_GET,'origlon',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	$dist = filter_input(INPUT_GET,'dist',FILTER_SANITIZE_NUMBER_INT);
	$number_results = filter_input(INPUT_GET,'number_results',FILTER_SANITIZE_NUMBER_INT);
	if ($dist != '') {
		if (isset($globalDistanceUnit) && $globalDistanceUnit == 'mi') $dist = $dist*1.60934;
		elseif (isset($globalDistanceUnit) && $globalDistanceUnit == 'nm') $dist = $dist*1.852;
	}
	if (!isset($sql_date)) $sql_date = '';
	if ($archive == 1) {
		if ($type == 'aircraft') {
			$SpotterArchive = new SpotterArchive();
			$spotter_array = $SpotterArchive->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'',$origlat,$origlon,$dist);
		}
	} else {
		if ($type == 'aircraft') {
			$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'',$origlat,$origlon,$dist);
		} elseif ($type == 'tracker') {
			$spotter_array = $Tracker->searchTrackerData($q,$callsign,$sql_date,$limit_start.",".$absolute_difference,$sort,'',$origlat,$origlon,$dist);
		} elseif ($type == 'marine') {
			$spotter_array = $Marine->searchMarineData($q,$callsign,$mmsi,$imo,$sql_date,$limit_start.",".$absolute_difference,$sort,'',$origlat,$origlon,$dist,$captain_id,$captain_name,$race_id,$race_name);
		}
	}
	 
	print '<span class="sub-menu-statistic column mobile">';
	print '<a href="#" onclick="showSubMenu(); return false;">Export <i class="fa fa-plus"></i></a>';
	print '</span>';
	print '<div class="sub-menu sub-menu-container">';
	print '<ul class="nav">';
	if ($type == 'aircraft') {
		print '<li class="dropdown">';
		print '<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="fa fa-download"></i> '._("Download Search Results").' <span class="caret"></span></a>';
		print '<ul class="dropdown-menu">';
		print '<li><a href="'.$globalURL.'/search/csv?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">CSV</a></li>';
		print '<li><a href="'.$globalURL.'/search/rss?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">RSS</a></li>';
		print '<li><hr /></li>';
		print '<li><span>For Advanced Users</strong></li>';
		print '<li><a href="'.$globalURL.'/search/json?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">JSON</a></li>';
		print '<li><a href="'.$globalURL.'/search/xml?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">XML</a></li>';
		print '<li><a href="'.$globalURL.'/search/yaml?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">YAML</a></li>';
		print '<li><a href="'.$globalURL.'/search/php?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">PHP (serialized array)</a></li>';
		print '<li><hr /></li>';
		print '<li><span>For Geo/Map Users</span></li>';
		print '<li><a href="'.$globalURL.'/search/kml?'.htmlentities($_SERVER['QUERY_STRING']).'">KML</a></li>';
		print '<li><a href="'.$globalURL.'/search/geojson?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GeoJSON</a></li>';
		print '<li><a href="'.$globalURL.'/search/georss?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GeoRSS</a></li>';
		print '<li><a href="'.$globalURL.'/search/gpx?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GPX</a></li>';
		print '<li><a href="'.$globalURL.'/search/wkt?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">WKT</a></li>';
		print '<li><hr /></li>';
		print '<li><a href="'.$globalURL.'/about/export" target="_blank" class="export-info">'._("Download Info/Licence").'&raquo;</a></li>';
		print '</ul>';
		print '</li>';
	}
	//remove 3D=true parameter
	$no3D = str_replace("&3D=true", "", $_SERVER['QUERY_STRING']);
	$kmlURL = str_replace("http://", "kml://", $globalURL);
	if (!isset($_GET['3D'])){
		print '<li><a href="'.$globalURL.'/search?'.$no3D.'" class="active"><i class="fa fa-table"></i> '._("Table").'</a></li>';
	} else {
		print '<li><span class="notablet"><a href="'.$globalURL.'/search?'.$no3D.'"><i class="fa fa-table"></i> '._("Table").'</a></span></li>';
	}
	if (isset($_GET['3D'])){
		print '<li><a href="'.$globalURL.'/search?'.$no3D.'&3D=true" class="active"><i class="fa fa-globe"></i> '._("3D Map").'</a></li>';
	} else {
		print '<li ><a href="'.$globalURL.'/search?'.$no3D.'&3D=true" class="notablet nomobile"><i class="fa fa-globe"></i> '._("3D Map").'</a><a href="'.$kmlURL.'/search/kml?'.htmlentities($_SERVER['QUERY_STRING']).'" class="tablet mobile"><i class="fa fa-globe"></i> 3D Map</a></li>';
	}
	//checks to see if the Bit.ly API settings are set
	if ($globalBitlyAccessToken != "")
	{
		print '<li class="short-url">';
		$bitly = $Spotter->getBitlyURL(urlencode('http://'.$_SERVER[HTTP_HOST].''.$_SERVER[REQUEST_URI]));
		print 'Short URL: <input type="text" name="short_url" value="'.$bitly.'" readonly="readonly" />';
		print '</li>';
	}
	print '</ul>';
	print '</div>';
	
	if (!empty($spotter_array))
	{
		print '<div class="column">';
		print '<div class="info">';
		print '<h1>'._("Search Results for").' ';
		if (isset($_GET['q']) && $_GET['q'] != ""){ print _("Keyword:").' <span>'.$q.'</span> '; }
		if (isset($_GET['aircraft']) && $_GET['aircraft'] != ""){ print _("Aircraft:").' <span>'.$aircraft.'</span> '; }
		if (isset($_GET['manufacturer']) && $_GET['manufacturer'] != ""){ print _("Manufacturer:").' <span>'.$manufacturer.'</span> '; }
		if (isset($_GET['registration']) && $_GET['registration'] != ""){ print _("Registration:").' <span>'.$registration.'</span> '; }
		if (isset($_GET['highlights'])) if ($_GET['highlights'] == "true"){ print _("Highlights:").' <span>'.$highlights.'</span> '; }
		if (isset($_GET['airline']) && $_GET['airline'] != ""){ print _("Airline:").' <span>'.$airline.'</span> '; }
		if (isset($_GET['airline_country']) && $_GET['airline_country'] != ""){ print _("Airline country:").' <span>'.$airline_country.'</span> '; }
		if (isset($_GET['airline_type']) && $_GET['airline_type'] != ""){ print _("Airline type:").' <span>'.$airline_type.'</span> '; }
		if (isset($_GET['airport']) && $_GET['airport'] != ""){ print _("Airport:").' <span>'.$airport.'</span> '; }
		if (isset($_GET['airport_country']) && $_GET['airport_country'] != ""){ print _("Airport country:").' <span>'.$airport_country.'</span> '; }
		if (isset($_GET['callsign']) && $_GET['callsign'] != ""){ print _("Callsign:").' <span>'.$callsign.'</span> '; }
		if (isset($_GET['owner']) && $_GET['owner'] != ""){ print _("Owner:").' <span>'.$owner.'</span> '; }
		if (isset($_GET['pilot_id']) && $_GET['pilot_id'] != ""){ print _("Pilot id:").' <span>'.$pilot_id.'</span> '; }
		if (isset($_GET['pilot_name']) && $_GET['pilot_name'] != ""){ print _("Pilot name:").' <span>'.$pilot_name.'</span> '; }
		if (isset($_GET['captain_id']) && $_GET['captain_id'] != ""){ print _("Captain id:").' <span>'.$captain_id.'</span> '; }
		if (isset($_GET['captain_name']) && $_GET['captain_name'] != ""){ print _("Captain name:").' <span>'.$captain_name.'</span> '; }
		if (isset($_GET['race_id']) && $_GET['race_id'] != ""){ print _("Race id:").' <span>'.$race_id.'</span> '; }
		if (isset($_GET['race_name']) && $_GET['race_name'] != ""){ print _("Race name:").' <span>'.$race_name.'</span> '; }
		if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] != "" && (!isset($_GET['arrival_airport_route']) || $_GET['arrival_airport_route'] == "")){ print _("Route out of:").' <span>'.$departure_airport_route.'</span> '; }
		if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] == "" && isset($_GET['arrival_airport_route']) && $_GET['arrival_airport_route'] != ""){ print _("Route into:").' <span>'.$arrival_airport_route.'</span> '; }
		if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] != "" && isset($_GET['arrival_airport_route']) && $_GET['arrival_airport_route'] != ""){ print _("Route between:").' <span>'.$departure_airport_route.'</span> and <span>'.$_GET['arrival_airport_route'].'</span> '; }
		if (isset($_GET['mmsi']) && $_GET['mmsi'] != ""){ print _("MMSI:").' <span>'.$mmsi.'</span> '; }
		if (isset($_GET['imo']) && $_GET['imo'] != ""){ print _("IMO:").' <span>'.$imo.'</span> '; }
		if (isset($_GET['start_date']) && $_GET['start_date'] != "" && isset($_GET['end_date']) && $_GET['end_date'] == ""){ print _("Date starting at:").' <span>'.$start_date.'</span> '; }
		if (isset($_GET['start_date']) && $_GET['start_date'] == "" && isset($_GET['end_date']) && $_GET['end_date'] != ""){ print _("Date ending at:").' <span>'.$end_date.'</span> '; }
		if (isset($_GET['start_date']) && $_GET['start_date'] != "" && isset($_GET['end_date']) && $_GET['end_date'] != ""){ print _("Date between:").' <span>'.$start_date.'</span> and <span>'.$end_date.'</span> '; }
		if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] != "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] == ""){ print _("Altitude starting at:").' <span>'.number_format($lowest_altitude).' feet</span> '; }
		if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] == "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] != ""){ print _("Altitude ending at:").' <span>'.number_format($highest_altitude).' feet</span> '; }
		if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] != "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] != ""){ print _("Altitude between:").' <span>'.number_format($lowest_altitude).' feet</span> '._("and").' <span>'.number_format($highest_altitude).' feet</span> '; }
		if (isset($_GET['number_results']) && $_GET['number_results'] != ""){ print _("limit per page:").' <span>'.$number_results.'</span> '; }
		print '</h1>';
		print '</div>';

		//  if ($_GET['3D'] == "true")
		if (isset($_GET['3D']))
		{
?>
              <script type="text/javascript" src="https://www.google.com/jsapi"> </script>
              <script type="text/javascript">
                  var ge;
                  google.load("earth", "1", {"other_params":"sensor=false"});

                  function init() {
                     google.earth.createInstance('map3d', initCB, failureCB);
                  }

                  function initCB(instance) {
                     ge = instance;
                     ge.getWindow().setVisibility(true);

                     //set default coordinates
                      var lookAt = ge.createLookAt('');
                      lookAt.setLatitude(44.413333);
                      lookAt.setLongitude(-79.68);
                      lookAt.setRange(400000.0);
                      ge.getView().setAbstractView(lookAt);

                      //show navigation control
                      ge.getNavigationControl().setVisibility(ge.VISIBILITY_SHOW);

                      //show bottom status bar
                      ge.getOptions().setStatusBarVisibility(true);
                      
                      //enable the atmosphere
                      ge.getOptions().setAtmosphereVisibility(true);
                      

                     //load the kml file
                     var href = '<?php print $globalURL; ?>/search/kml?<?php print $_SERVER['QUERY_STRING']; ?>';
                     google.earth.fetchKml(ge, href, function(kmlObject) {
                           if (kmlObject)
                              ge.getFeatures().appendChild(kmlObject);
                         
                           if (kmlObject.getAbstractView() !== null)
                              ge.getView().setAbstractView(kmlObject.getAbstractView());
                     });
                  }

                  function failureCB(errorCode) {
                  }

                  google.setOnLoadCallback(init);
               </script>
              <div id="map3d"></div>
<?php
		} else {
			include('table-output.php'); 
			$_SERVER['QUERY_STRING'] = preg_replace('/&?limit=[^&]*/', '', $_SERVER['QUERY_STRING']);
			print '<div class="pagination">';
			if ($limit_previous_1 >= 0)
			{
				if ($type == 'aircraft') {
					print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_previous_1.','.$limit_previous_2.'">&laquo;'._("Previous Page").'</a>';
				} elseif ($type == 'tracker') {
					print '<a href="'.$globalURL.'/tracker/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_previous_1.','.$limit_previous_2.'">&laquo;'._("Previous Page").'</a>';
				} elseif ($type == 'marine') {
					print '<a href="'.$globalURL.'/marine/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_previous_1.','.$limit_previous_2.'">&laquo;'._("Previous Page").'</a>';
				}
			}
			if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
			{
				if ($type == 'aircraft') {
					print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_end.','.$limit_next.'">'._("Next Page").'&raquo;</a>';
				} elseif ($type == 'tracker') {
					print '<a href="'.$globalURL.'/tracker/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_end.','.$limit_next.'">'._("Next Page").'&raquo;</a>';
				} elseif ($type == 'marine') {
					print '<a href="'.$globalURL.'/marine/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_end.','.$limit_next.'">'._("Next Page").'&raquo;</a>';
				}
			}
			print '</div>';
		}
		print '</div>';
	} else {
		print '<div class="column">';
		print '<div class="info">';
		print '<h1>'._("Search").'</h1>';
		print '</div>';
		print '<p>'._("Sorry, your search did not produce any results. :(").'</p>'; 
		print '</div>';
	}
} else {
	print '<div class="info column">';
	print '<h1>'._("Search").'</h1>';
	print '</div>';
}
?>

<div class="column">
<?php
if ($type == 'aircraft') {
?>
	<form action="<?php print $globalURL; ?>/search" method="get" role="form" class="form-horizontal">
<?php
} elseif ($type == 'marine') {
?>
	<form action="<?php print $globalURL; ?>/marine/search" method="get" role="form" class="form-horizontal">
<?php
} elseif ($type == 'tracker') {
?>
	<form action="<?php print $globalURL; ?>/tracker/search" method="get" role="form" class="form-horizontal">
<?php
}
?>
<!--
		<fieldset>
			<div class="form-group">
				<label class="control-label col-sm-2"><?php echo _("Keywords"); ?></label>
				<div class="col-sm-10">
					<input type="text" class="form-control" id="q" name="q" value="<?php if (isset($_GET['q'])) print $q; ?>" size="10" placeholder="<?php echo _("Keywords"); ?>" />
				</div>
			</div>
		</fieldset>
-->
		<div class="advanced-form">
<?php
if ($type == 'aircraft') {
?>
			<fieldset>
				<legend><?php echo _("Aircraft"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Manufacturer"); ?></label>
					<div class="col-sm-10">
						<select name="manufacturer" class="form-control" id="manufacturer" class="selectpicker" data-live-search="true">
							<option></option>
					    </select>
					</div>
				</div>
				<script type="text/javascript">getSelect('manufacturer','<?php if(isset($_GET['manufacturer'])) print $manufacturer; ?>')</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Type"); ?></label>
						<div class="col-sm-10">
							<select name="aircraft" class="form-control" id="aircrafttypes" class="selectpicker" data-live-search="true">
								<option></option>
							</select>
						</div>
				</div>
				<script type="text/javascript">getSelect('aircrafttypes','<?php if(isset($_GET['aircraft_icao'])) print $aircraft_icao; ?>');</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Registration"); ?></label> 
					<div class="col-sm-10">
						<input type="text" class="form-control" name="registration" value="<?php if (isset($_GET['registration'])) print $registration; ?>" size="8" placeholder="<?php echo _("Registration"); ?>" />
					</div>
				</div>
<?php
	if ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
?>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Pilot id"); ?></label> 
					<div class="col-sm-10">
						<input type="text" class="form-control" name="pilot_id" value="<?php if (isset($_GET['pilot_id'])) print $pilot_id; ?>" size="15" placeholder="<?php echo _("Pilot id"); ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Pilot name"); ?></label> 
					<div class="col-sm-10">
						<input type="text" class="form-control" name="pilot_name" value="<?php if (isset($_GET['pilot_name'])) print $pilot_name; ?>" size="15" placeholder="<?php echo _("Pilot name"); ?>" />
					</div>
				</div>
<?php
	}else {
?>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Owner name"); ?></label> 
					<div class="col-sm-10">
						<input type="text" class="form-control" name="owner" value="<?php if (isset($_GET['owner'])) print $owner; ?>" size="15" placeholder="<?php echo _("Owner name"); ?>" />
					</div>
				</div>
<?php
	}
?>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
					<!--<div><input type="checkbox" class="form-control" name="highlights" value="true" id="highlights" <?php if (isset($_GET['highlights'])) if ($_GET['highlights'] == "true"){ print 'checked="checked"'; } ?>> <label for="highlights"><?php echo _("Include only aircraft with special highlights (unique liveries, destinations etc.)"); ?></label></div>-->
					<label class="checkbox-inline"><input type="checkbox" name="highlights" value="true" id="highlights" <?php if (isset($_GET['highlights'])) if ($_GET['highlights'] == "true"){ print 'checked="checked"'; } ?>> <?php echo _("Include only aircraft with special highlights (unique liveries, destinations etc.)"); ?></label>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo _("Airline"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Name"); ?></label> 
					<div class="col-sm-10">
						<select name="airline" id="airlinenames" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('airlinenames','<?php if(isset($_GET['airline'])) print $airline; ?>');</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Country"); ?></label> 
					<div class="col-sm-10">
						<select name="airline_country" id="airlinecountries" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('airlinecountries','<?php if(isset($_GET['airline_country'])) print $airline_country; ?>');</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Callsign"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="callsign" class="form-control" value="<?php if (isset($_GET['callsign'])) print $callsign; ?>" size="8" placeholder="<?php echo _("Callsign"); ?>" />
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
						<label class="radio-inline"><input type="radio" name="airline_type" value="all" id="airline_type_all" <?php if (!isset($_GET['airline_type']) || $_GET['airline_type'] == "all"){ print 'checked="checked"'; } ?>> <?php echo _("All airlines types"); ?></label>
						<label class="radio-inline"><input type="radio" name="airline_type" value="passenger" id="airline_type_passenger" <?php if (isset($_GET['airline_type'])) if ($_GET['airline_type'] == "passenger"){ print 'checked="checked"'; } ?>> <?php echo _("Only Passenger airlines"); ?></label>
						<label class="radio-inline"><input type="radio" name="airline_type" value="cargo" id="airline_type_cargo" <?php if (isset($_GET['airline_type'])) if ( $_GET['airline_type'] == "cargo"){ print 'checked="checked"'; } ?>> <?php echo _("Only Cargo airlines"); ?></label>
						<label class="radio-inline"><input type="radio" name="airline_type" value="military" id="airline_type_military" <?php if (isset($_GET['airline_type'])) if ( $_GET['airline_type'] == "military"){ print 'checked="checked"'; } ?>> <?php echo _("Only Military airlines"); ?></label>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo _("Airport"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Name"); ?></label> 
					<div class="col-sm-10">
						<select name="airport" id="airportnames" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('airportnames','<?php if(isset($_GET['airport_icao'])) print $airport_icao; ?>');</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Country"); ?></label> 
					<div class="col-sm-10">
						<select name="airport_country" id="airportcountries" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('airportcountries','<?php if(isset($_GET['airport_country'])) print $airport_country; ?>');</script>
			</fieldset>
			<fieldset>
				<legend><?php echo _("Route"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Departure Airport"); ?></label> 
					<div class="col-sm-10">
						<select name="departure_airport_route" id="departureairportnames" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('departureairportnames','<?php if(isset($_GET['departure_airport_route'])) print $departure_airport_route; ?>');</script>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Arrival Airport"); ?></label> 
					<div class="col-sm-10">
						<select name="arrival_airport_route" id="arrivalairportnames" class="form-control selectpicker" data-live-search="true">
							<option></option>
						</select>
					</div>
				</div>
				<script type="text/javascript">getSelect('arrivalairportnames','<?php if(isset($_GET['arrival_airport_route'])) print $arrival_airport_route; ?>');</script>
			</fieldset>
			<fieldset>
				<legend><?php echo _("Altitude"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Lowest Altitude"); ?></label> 
					<div class="col-sm-10">
						<select name="lowest_altitude" class="form-control selectpicker" data-live-search="true">
							<option></option>
<?php
$altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
foreach($altitude_array as $altitude)
{
	if(isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] == $altitude)
	{
		print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
	} else {
		print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
	}
}
?>
					</select>
				</div>
			</div>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Highest Altitude"); ?></label> 
					<div class="col-sm-10">
						<select name="highest_altitude" class="form-control selectpicker" data-live-search="true">
							<option></option>
<?php
	$altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
	foreach($altitude_array as $altitude)
	{
		if(isset($_GET['highest_altitude']) && $_GET['highest_altitude'] == $altitude)
		{
			print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
		} else {
			print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
		}
	}
?>
						</select>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php echo _("Flights near"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Latitude"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="origlat" class="form-control" placeholder="<?php echo _("Center point latitude"); ?>" value="<?php if (isset($_GET['origlat'])) print $origlat; ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Longitude"); ?></label>
					<div class="col-sm-10">
						<input type="text" name="origlon" class="form-control" placeholder="<?php echo _("Center point longitude"); ?>" value="<?php if (isset($_GET['origlon'])) print $origlon; ?>" />
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Distance").' ('; if (isset($globalDistanceUnit)) print $globalDistanceUnit; else print 'km'; print ')'; ?></label>
					<div class="col-sm-10">
						<input type="text" name="dist" class="form-control" placeholder="<?php echo _("Distance from center point"); ?>" value="<?php if (isset($_GET['distance'])) print $distance; ?>" />
					</div>
				</div>
			</fieldset>
<?php
} elseif ($type == 'tracker') {
?>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Callsign"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="callsign" class="form-control" value="<?php if (isset($_GET['callsign'])) print $callsign; ?>" size="8" placeholder="<?php echo _("Callsign"); ?>" />
					</div>
				</div>
			</fieldset>
<?php
} elseif ($type == 'marine') {
?>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Callsign"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="callsign" class="form-control" value="<?php if (isset($_GET['callsign'])) print $callsign; ?>" size="8" placeholder="<?php echo _("Callsign"); ?>" />
					</div>
				</div>
			</fieldset>
<?php
	if (isset($globalVM) && $globalVM) {
?>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Captain id"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="captain_id" class="form-control" value="<?php if (isset($_GET['captain_id'])) print $captain_id; ?>" size="8" placeholder="<?php echo _("Captain id"); ?>" />
					</div>
				</div>
			</fieldset>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Captain name"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="captain_name" class="form-control" value="<?php if (isset($_GET['captain_name'])) print $captain_name; ?>" size="8" placeholder="<?php echo _("Captain name"); ?>" />
					</div>
				</div>
			</fieldset>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Race id"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="race_id" class="form-control" value="<?php if (isset($_GET['race_id'])) print $race_id; ?>" size="8" placeholder="<?php echo _("Race id"); ?>" />
					</div>
				</div>
			</fieldset>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Race name"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="race_name" class="form-control" value="<?php if (isset($_GET['race_name'])) print $race_name; ?>" size="8" placeholder="<?php echo _("Race name"); ?>" />
					</div>
				</div>
			</fieldset>
<?php
	} else {
?>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("MMSI"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="mmsi" class="form-control" value="<?php if (isset($_GET['mmsi'])) print $mmsi; ?>" size="8" placeholder="<?php echo _("MMSI"); ?>" />
					</div>
				</div>
			</fieldset>
			<fieldset>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("IMO"); ?></label> 
					<div class="col-sm-10">
						<input type="text" name="imo" class="form-control" value="<?php if (isset($_GET['imo'])) print $imo; ?>" size="8" placeholder="<?php echo _("IMO"); ?>" />
					</div>
				</div>
			</fieldset>
<?php
	}
}
?>
			<fieldset>
				<legend><?php echo _("Date"); ?></legend>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("Start Date"); ?></label>
					<div class="col-sm-10">
						<div class='input-group date' id='datetimepicker1'>
							<input type='text' name="start_date" class="form-control" value="<?php if (isset($_GET['start_date']) && $_GET['start_date'] != '') print $start_date; ?>" placeholder="<?php echo _("Start Date/Time"); ?>" />
							<span class="input-group-addon">
								<span class="glyphicon glyphicon-calendar"></span>
							</span>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-2"><?php echo _("End Date"); ?></label>
					<div class="col-sm-10">
						<div class='input-group date' id='datetimepicker2'>
						<input type='text' name="end_date" class="form-control" value="<?php if (isset($_GET['end_date']) && $_GET['end_date'] != '') print $end_date; ?>" placeholder="<?php echo _("End Date/Time"); ?>" />
						<span class="input-group-addon">
							<span class="glyphicon glyphicon-calendar"></span>
						</span>
					</div>
				</div>
				<script type="text/javascript">
					$(function () {
						$('#datetimepicker1').datetimepicker({
							format: 'YYYY-MM-DD'
						});
						$('#datetimepicker2').datetimepicker({
							format: 'YYYY-MM-DD',
							useCurrent: false //Important! See issue #1075
						});
						$("#datetimepicker1").on("dp.change", function (e) {
							$('#datetimepicker2').data("DateTimePicker").minDate(e.date);
						});
						$("#datetimepicker2").on("dp.change", function (e) {
							$('#datetimepicker1').data("DateTimePicker").maxDate(e.date);
						});
					});
				</script>
			</fieldset>
		</div>
		<fieldset>
			<legend><?php echo _("Limit per Page"); ?></legend>
			<div class="form-group">
				<label class="control-label col-sm-2"><?php echo _("Number of Results"); ?></label> 
				<div class="col-sm-10">
					<select class="form-control" name="number_results">
<?php
$number_results_array = Array(25, 50, 100, 150, 200, 250, 300, 400, 500,  600, 700, 800, 900, 1000);
foreach($number_results_array as $number)
{
	if(isset($_GET['number_results']) && $_GET['number_results'] == $number)
	{
		print '<option value="'.$number.'" selected="selected">'.$number.'</option>';
	} else {
		print '<option value="'.$number.'">'.$number.'</option>';
	}
}
?>
					</select>
				</div>
			</div>
		</fieldset>
<?php
if (isset($globalArchiveKeepMonths) && $globalArchiveKeepMonths > 0) {
	if (isset($globalDemo) && $globalDemo) {
?>
		<fieldset>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<label class="checkbox-inline"><input type="checkbox" name="archive" value="1" disabled /><?php echo sprintf(_("Search in archive (older than %s months)"),$globalArchiveKeepMonths); ?></label>
					<p class="help-block">Disabled in demo</p>
				</div>
			</div>
		</fieldset>
<?php
	} else {
?>
		<fieldset>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<label class="checkbox-inline"><input type="checkbox" name="archive" value="1" /><?php echo sprintf(_("Search in archive (older than %s months)"),$globalArchiveKeepMonths); ?></label>
				</div>
			</div>
		</fieldset>
<?php
	}
}
?>
		<fieldset>
			<div class="col-sm-offset-2 col-sm-10">
				<input type="submit" class="btn btn-default" value="<?php echo _("Search"); ?>" />
			</div>
		</fieldset>
	 </form>
</div>

<?php
require_once('footer.php');
?>
