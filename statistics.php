<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$beginpage = microtime(true);
$Stats = new Stats();

$type = 'aircraft';
if (isset($_GET['tracker'])) {
	require_once('require/class.Tracker.php');
	$Tracker = new Tracker();
	$type = 'tracker';
} elseif (isset($_GET['marine'])) {
	require_once('require/class.Marine.php');
	$Marine = new Marine();
	$type = 'marine';
} elseif (isset($_GET['satellite'])) {
	require_once('require/class.Satellite.php');
	$Satellite = new Satellite();
	$type = 'satellite';
} else {
	require_once('require/class.Spotter.php');
}

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($type == 'aircraft' && $airline_icao == '' && isset($globalFilter)) {
	if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
if ($type == 'aircraft' && $airline_icao != '' && $airline_icao != 'all') {
	$Spotter = new Spotter();
	$airline_info = $Spotter->getAllAirlineInfo($airline_icao);
	if (isset($airline_info[0]['name'])) {
		$airline_name = $airline_info[0]['name'];
	} elseif (strpos($airline_icao,'alliance_') !== FALSE) {
		$alliance_name = $airline_icao;
	}
}
if ($type == 'aircraft' && isset($airline_name)) {
	$title = _("Statistics").' - '.$airline_name;
} else {
	$title = _("Statistics");
}

$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);

require_once('header.php');

?>
<link href="<?php echo $globalURL; ?>/css/c3.min.css" rel="stylesheet" type="text/css">
<!--<script type="text/javascript" src="https://d3js.org/d3.v4.min.js"></script>-->
<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>-->
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/d3.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/c3.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/d3pie.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/radarChart.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/raphael-2.1.4.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/justgage.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/topojson.v2.min.js"></script>
<script type="text/javascript" src="<?php echo $globalURL; ?>/js/datamaps.world.min.js"></script>
<div class="column">
    <div class="info">
            <h1><?php if (isset($airline_name)) echo _("Statistics for ").$airline_name; else echo _("Statistics"); ?></h1>
<?php 
if ($type == 'aircraft') {
	$last_update = $Stats->getLastStatsUpdate();
} elseif ($type == 'marine') {
	$last_update = $Stats->getLastStatsUpdate('last_update_stats_marine');
} elseif ($type == 'tracker') {
	$last_update = $Stats->getLastStatsUpdate('last_update_stats_tracker');
}
if (isset($last_update[0]['value'])) {
	date_default_timezone_set('UTC');
	$lastupdate = strtotime($last_update[0]['value']);
	if (isset($globalTimezone) && $globalTimezone != '') date_default_timezone_set($globalTimezone);
	print '<i>Last update: '.date('Y-m-d G:i:s',$lastupdate).'</i>';
}

?>
    </div>
<?php 
include('statistics-sub-menu.php'); 
?>
    <p class="global-stats">
<?php
if ($type == 'aircraft') {
?>
        <span><span class="badge"><?php print number_format($Stats->countOverallFlights($airline_icao,$filter_name,$year,$month)); ?></span> <?php echo _("Flights"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <span><span class="badge"><?php print number_format($Stats->countOverallArrival($airline_icao,$filter_name,$year,$month)); ?></span> <?php echo _("Arrivals seen"); ?></span>
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {
?>
    	    <span><span class="badge"><?php print number_format($Stats->countOverallPilots($airline_icao,$filter_name,$year,$month)); ?></span> <?php echo _("Pilots"); ?></span>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
	if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
?>
    	    <span><span class="badge"><?php print number_format($Stats->countOverallOwners($airline_icao,$filter_name,$year,$month)); ?></span> <?php echo _("Owners"); ?></span>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
?>
        <span><span class="badge"><?php print number_format($Stats->countOverallAircrafts($airline_icao,$filter_name,$year,$month)); ?></span> <?php echo _("Aircraft types"); ?></span>
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	if ($airline_icao == '') {
?>
        <span><span class="badge"><?php print number_format($Stats->countOverallAirlines($filter_name,$year,$month)); ?></span> <?php echo _("Airlines"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
?>
<?php
	if (!(isset($globalVA) && $globalVA) && !(isset($globalIVAO) && $globalIVAO) && !(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalphpVMS) && $globalphpVMS) && !(isset($globalVAM) && $globalVAM)) {
		if ($airline_icao == '' || $airline_icao == 'all') {
?>
        <span><span class="badge"><?php print number_format($Stats->countOverallMilitaryFlights($filter_name,$year,$month)); ?></span> <?php echo _("Military"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
		}
	}
	?>
<?php
} elseif ($type == 'marine') {
?>
	<span><span class="badge"><?php print number_format($Stats->countOverallMarine($filter_name,$year,$month)); ?></span> <?php echo _("Vessels"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	if (isset($globalVM) && $globalVM) {
?>
	<span><span class="badge"><?php print number_format($Marine->countOverallMarineTypes($filter_name,$year,$month)); ?></span> <?php echo _("Types"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<span><span class="badge"><?php print number_format($Marine->countOverallMarineRaces($filter_name,$year,$month)); ?></span> <?php echo _("Races"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<span><span class="badge"><?php print number_format($Marine->countOverallMarineCaptains($filter_name,$year,$month)); ?></span> <?php echo _("Captains"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	} else {
?>
	<span><span class="badge"><?php print number_format($Stats->countOverallMarineTypes($filter_name,$year,$month)); ?></span> <?php echo _("Types"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
} elseif ($type == 'tracker') {
?>
	<span><span class="badge"><?php print number_format($Stats->countOverallTracker($filter_name,$year,$month)); ?></span> <?php echo _("Trackers"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<span><span class="badge"><?php print number_format($Tracker->countOverallTrackerTypes(array(),$year,$month)); ?></span> <?php echo _("Types"); ?></span>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
}
?>
    </p>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
    <div class="specific-stats">
<?php
if ($type == 'aircraft') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Aircraft Type"); ?></h2>
<?php
	$aircraft_array = $Stats->countAllAircraftTypes(true,$airline_icao,$filter_name,$year,$month);
	if (count($aircraft_array) == 0) {
		print _("No data available");
	} else {
		print '<div id="chart1" class="chart" width="100%"></div><script>';
		$aircraft_data = '';
		foreach($aircraft_array as $aircraft_item) {
			if ($aircraft_item['aircraft_manufacturer'] == 'Not Available') $aircraft_data .= '[" ('.$aircraft_item['aircraft_icao'].')",'.$aircraft_item['aircraft_icao_count'].'],';
			else $aircraft_data .= '["'.$aircraft_item['aircraft_manufacturer'].' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')",'.$aircraft_item['aircraft_icao_count'].'],';
		}
		$aircraft_data = substr($aircraft_data, 0, -1);
		print 'var series = ['.$aircraft_data.'];';
		print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
		print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
		print 'var aircraftype = new d3pie("chart1",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
		print 'dataset';
		print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
		print '</script>';
	}
?>
                <div class="more">
<?php
	if ($year != '' && $month != '') {
?>
            	    <a href="<?php print $globalURL; ?>/statistics/aircraft<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year; ?>/<?php echo $month ?>/" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
<?php
	} else {
?>
            	    <a href="<?php print $globalURL; ?>/statistics/aircraft<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
<?php
	}
?>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	if ($airline_icao == '' || $airline_icao == 'all') {
		$airline_array = $Stats->countAllAirlines(true,$filter_name,$year,$month);
		if (count($airline_array) > 0) {
			print '<div class="col-md-6">';
			print '<h2>'._("Top 10 Most Common Airline").'</h2>';
			print '<div id="chart2" class="chart" width="100%"></div><script>';
			$airline_data = '';
			foreach($airline_array as $airline_item) {
				$airline_data .= '["'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')",'.$airline_item['airline_count'].'],';
			}
			$airline_data = substr($airline_data, 0, -1);
			print 'var series = ['.$airline_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var airlinescnt = new d3pie("chart2",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
			if ($year != '' && $month != '') {
				print '<div class="more"><a href="'.$globalURL.'/statistics/airline';
				if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao;
				print '/'.$year.'/'.$month.'/" class="btn btn-default btn" role="button">'._("See full statistic").'&raquo;</a></div>';
			} else {
				print '<div class="more"><a href="'.$globalURL.'/statistics/airline';
				if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao;
				print '" class="btn btn-default btn" role="button">'._("See full statistic").'&raquo;</a></div>';
			}
			print '</div>';
		}
?>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
}
?>
<?php
if ($type == 'marine') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Vessel Type"); ?></h2>
<?php
	$marine_array = $Stats->countAllMarineTypes(true,$filter_name,$year,$month);
	if (count($marine_array) == 0) print _("No data available");
	else {
		print '<div id="chart1" class="chart" width="100%"></div><script>';
		$marine_data = '';
		foreach($marine_array as $marine_item) {
			$marine_data .= '["'.$marine_item['marine_type'].'",'.$marine_item['marine_type_count'].'],';
		}
		$marine_data = substr($marine_data, 0, -1);
		print 'var series = ['.$marine_data.'];';
		print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
		print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
		print 'var marinetype = new d3pie("chart1",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
		print 'dataset';
		print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
		print '</script>';
	}
?>
                <div class="more">
            	    <?php
            	    
            		if ($year != '' && $month != '') {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/marine/statistics/type/<?php echo $year; ?>/<?php echo $month ?>/" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		} else {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/marine/statistics/type" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		}
            	    ?>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<!--	</div>-->
<?php
	if (isset($globalVM) && $globalVM) {
?>
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Races Number of Participants"); ?></h2>
<?php
	$marine_array = $Marine->countAllCaptainsByRaces(true,$filter_name,$year,$month);
	if (count($marine_array) == 0) print _("No data available");
	else {
		print '<div id="chart991" class="chart" width="100%"></div><script>';
		$marine_data = '';
		foreach($marine_array as $marine_item) {
			$marine_data .= '["'.$marine_item['marine_race_name'].'",'.$marine_item['marine_captain_count'].'],';
		}
		$marine_data = substr($marine_data, 0, -1);
		print 'var series = ['.$marine_data.'];';
		print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
		print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
		print 'var marinetype = new d3pie("chart991",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
		print 'dataset';
		print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3,"format":"value"},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#98e8e3","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
		print '</script>';
	}
?>
                <div class="more">
            	    <?php
            		if ($year != '' && $month != '') {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/marine/statistics/race/<?php echo $year; ?>/<?php echo $month ?>/" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		} else {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/marine/statistics/race" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		}
            	    ?>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	</div>
        <div class="row column">

<?php
	}
}
if ($type == 'tracker') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Tracker Type"); ?></h2>
<?php
	$tracker_array = $Tracker->countAllTrackerTypes(true,0,'',array(),$year,$month);
	if (count($tracker_array) == 0) print _("No data available");
	else {
		print '<div id="chart1" class="chart" width="100%"></div><script>';
		$tracker_data = '';
		foreach($tracker_array as $tracker_item) {
			$tracker_data .= '["'.$tracker_item['tracker_type'].'",'.$tracker_item['tracker_type_count'].'],';
		}
		$tracker_data = substr($tracker_data, 0, -1);
		print 'var series = ['.$tracker_data.'];';
		print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
		print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
		print 'var trackertype = new d3pie("chart1",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
		print 'dataset';
		print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
		print '</script>';
	}
?>
                <div class="more">
            	    <?php
            	    /*
            		if ($year != '' && $month != '') {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/tracker/statistics/type/<?php echo $year; ?>/<?php echo $month ?>/" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		} else {
            	    ?>
            	    <a href="<?php print $globalURL; ?>/tracker/statistics/type" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
            	    <?php
            		}
            		*/
            	    ?>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<!--	</div>-->
<?php
}
if ($type == 'satellite') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Owners"); ?></h2>
<?php
		$owner_array = $Satellite->countAllOwners(true);
		if (count($owner_array) == 0) print _("No data available");
		else {
			print '<div id="chart7" class="chart" width="100%"></div><script>';
			$owner_data = '';
			foreach($owner_array as $owner_item) {
				$owner_data .= '["'.$owner_item['owner_name'].'",'.$owner_item['owner_count'].'],';
			}
			$owner_data = substr($owner_data, 0, -1);
			print 'var series = ['.$owner_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var ownercnt = new d3pie("chart7",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
		}
?>
               <!--
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
                -->
            </div>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Countries Owners"); ?></h2>
<?php
		$countries_array = $Satellite->countAllCountriesOwners(true);
		if (count($countries_array) == 0) print _("No data available");
		else {
			print '<div id="chart8" class="chart" width="100%"></div><script>';
			$owner_data = '';
			foreach($countries_array as $owner_item) {
				$owner_data .= '["'.$owner_item['country_name'].'",'.$owner_item['country_count'].'],';
			}
			$owner_data = substr($owner_data, 0, -1);
			print 'var series = ['.$owner_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var ownercnt = new d3pie("chart8",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
		}
?>
	    <!--
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
                -->
            </div>
            
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
²	</div>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Launch Sites"); ?></h2>
<?php
		$launch_site_array = $Satellite->countAllLaunchSite(true);
		if (count($launch_site_array) == 0) print _("No data available");
		else {
			print '<div id="chart9" class="chart" width="100%"></div><script>';
			$launch_site_data = '';
			foreach($launch_site_array as $launch_site_item) {
				$launch_site_data .= '["'.$launch_site_item['launch_site'].'",'.$launch_site_item['launch_site_count'].'],';
			}
			$launch_site_data = substr($launch_site_data, 0, -1);
			print 'var series = ['.$launch_site_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var ownercnt = new d3pie("chart9",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
		}
?>
               <!--
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
                -->
            </div>
        </div>
<?php
}
if ($type == 'aircraft') {
?>
        <div class="row column">
<?php
	$flightover_array = $Stats->countAllFlightOverCountries(false,$airline_icao,$filter_name,$year,$month);
	//if ((isset($globalVA) && $globalVA) ||(isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
	if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {
		if (empty($flightover_array)) {
			print '<div class="col-md-12">';
		} else {
			print '<div class="col-md-6">';
		}
?>
                <h2><?php echo _("Top 10 Most Common Pilots"); ?></h2>
<?php
		$pilot_array = $Stats->countAllPilots(true,$airline_icao,$filter_name,$year,$month);
		if (count($pilot_array) == 0) print _("No data available");
		else {
			print '<div id="chart7" class="chart" width="100%"></div><script>';
			$pilot_data = '';
			foreach($pilot_array as $pilot_item) {
				$pilot_data .= '["'.$pilot_item['pilot_name'].' ('.$pilot_item['pilot_id'].')",'.$pilot_item['pilot_count'].'],';
			}
			$pilot_data = substr($pilot_data, 0, -1);
			print 'var series = ['.$pilot_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var pilotcnt = new d3pie("chart7",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
		}
		print '<div class="more">';
		print '<a href="'.$globalURL.'/statistics/pilot'; 
		if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao;
		print'" class="btn btn-default btn" role="button">'._("See full statistic").'&raquo;</a>';
		print '</div>';
?>
            </div>

    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
	// else {
	if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
?>
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Owners"); ?></h2>
<?php
		$owner_array = $Stats->countAllOwners(true,$airline_icao,$filter_name,$year,$month);
		if (count($owner_array) == 0) print _("No data available");
		else {
			print '<div id="chart7" class="chart" width="100%"></div><script>';
			$owner_data = '';
			foreach($owner_array as $owner_item) {
				$owner_data .= '["'.$owner_item['owner_name'].'",'.$owner_item['owner_count'].'],';
			}
			$owner_data = substr($owner_data, 0, -1);
			print 'var series = ['.$owner_data.'];';
			print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
			print 'var ownercnt = new d3pie("chart7",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
			print 'dataset';
			print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
	if (!empty($flightover_array)) {
?>
            <div class="col-md-6">
                <h2><?php echo _("Top 20 Most Common Country a Flight was Over"); ?></h2>
<?php
	 //$flightover_array = $Stats->countAllFlightOverCountries();
		if (count($flightover_array) == 0) print _("No data available");
		else {
			print '<div id="chart10" class="chart" width="100%"></div><script>';
			print 'var series = [';
			$flightover_data = '';
			foreach($flightover_array as $flightover_item) {
				$flightover_data .= '[ "'.$flightover_item['flight_country_iso3'].'",'.$flightover_item['flight_count'].'],';
			}
			$flightover_data = substr($flightover_data, 0, -1);
			print $flightover_data;
			print '];';
			print 'var dataset = {};var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
			print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
			print 'series.forEach(function(item){var iso = item[0], value = item[1]; dataset[iso] = { numberOfThings: value, fillColor: paletteScale(value) };});';
			print 'new Datamap({
			    element: document.getElementById("chart10"),
			    projection: "mercator", // big world map
			    fills: { defaultFill: "#F5F5F5" },
			    data: dataset,
			    responsive: true,
			    geographyConfig: {
				borderColor: "#DEDEDE",
				highlightBorderWidth: 2,
				highlightFillColor: function(geo) {
				    return geo["fillColor"] || "#F5F5F5";
				},
				highlightBorderColor: "#B7B7B7",
				done: function(datamap) {
				    datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
				    function redraw() {
					datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
				    }
				},
				popupTemplate: function(geo, data) {
				    if (!data) { return ; }
				    return ['."'".'<div class="hoverinfo">'."','<strong>', geo.properties.name, '</strong>','<br>Count: <strong>', data.numberOfThings, '</strong>','</div>'].join('');
				}
			    }
			});";
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
<?php
	}
?>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
}
if ($type == 'marine') {
	$flightover_array = $Stats->countAllMarineOverCountries(true,$filter_name,$year,$month);
?>
<!--    <div class="row column">-->
	<div class="col-md-6">
            <h2><?php echo _("Top 20 Most Common Country a Vessel was inside"); ?></h2>
<?php
	if (count($flightover_array) == 0) print _("No data available");
	else {
		print '<div id="chart10" class="chart" width="100%"></div><script>';
		print 'var series = [';
		$flightover_data = '';
		foreach($flightover_array as $flightover_item) {
			$flightover_data .= '[ "'.$flightover_item['marine_country_iso3'].'",'.$flightover_item['marine_count'].'],';
		}
		$flightover_data = substr($flightover_data, 0, -1);
		print $flightover_data;
		print '];';
		print 'var dataset = {};var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
		print 'series.forEach(function(item){var iso = item[0], value = item[1]; dataset[iso] = { numberOfThings: value, fillColor: paletteScale(value) };});';
		print 'new Datamap({
		    element: document.getElementById("chart10"),
		    projection: "mercator", // big world map
		    fills: { defaultFill: "#F5F5F5" },
		    data: dataset,
		    responsive: true,
		    geographyConfig: {
			borderColor: "#DEDEDE",
			highlightBorderWidth: 2,
			highlightFillColor: function(geo) {
			    return geo["fillColor"] || "#F5F5F5";
			},
			highlightBorderColor: "#B7B7B7",
			done: function(datamap) {
			    datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
			    function redraw() {
				datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
			    }
			},
			popupTemplate: function(geo, data) {
			    if (!data) { return ; }
			    return ['."'".'<div class="hoverinfo">'."','<strong>', geo.properties.name, '</strong>','<br>Count: <strong>', data.numberOfThings, '</strong>','</div>'].join('');
			}
		    }
		});";
		print '</script>';
	}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/country" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            </div>
        </div>

<?php
}
if ($type == 'tracker') {
	$flightover_array = $Stats->countAllTrackerOverCountries(true);
?>
<!--    <div class="row column">-->
	<div class="col-md-6">
            <h2><?php echo _("Top 20 Most Common Country a Tracker was inside"); ?></h2>
<?php
	if (count($flightover_array) == 0) print _("No data available");
	else {
		print '<div id="chart10" class="chart" width="100%"></div><script>';
		print 'var series = [';
		$flightover_data = '';
		foreach($flightover_array as $flightover_item) {
			$flightover_data .= '[ "'.$flightover_item['tracker_country_iso3'].'",'.$flightover_item['tracker_count'].'],';
		}
		$flightover_data = substr($flightover_data, 0, -1);
		print $flightover_data;
		print '];';
		print 'var dataset = {};var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
		print 'series.forEach(function(item){var iso = item[0], value = item[1]; dataset[iso] = { numberOfThings: value, fillColor: paletteScale(value) };});';
		print 'new Datamap({
		    element: document.getElementById("chart10"),
		    projection: "mercator", // big world map
		    fills: { defaultFill: "#F5F5F5" },
		    data: dataset,
		    responsive: true,
		    geographyConfig: {
			borderColor: "#DEDEDE",
			highlightBorderWidth: 2,
			highlightFillColor: function(geo) {
			    return geo["fillColor"] || "#F5F5F5";
			},
			highlightBorderColor: "#B7B7B7",
			done: function(datamap) {
			    datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
			    function redraw() {
				datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
			    }
			},
			popupTemplate: function(geo, data) {
			    if (!data) { return ; }
			    return ['."'".'<div class="hoverinfo">'."','<strong>', geo.properties.name, '</strong>','<br>Count: <strong>', data.numberOfThings, '</strong>','</div>'].join('');
			}
		    }
		});";
		print '</script>';
	}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/country" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        </div>

<?php
}
if ($type == 'aircraft') {
?>
        <div class="row column">
            <div class="col-md-6">
<?php
	$airport_airport_array = $Stats->countAllDepartureAirports(true,$airline_icao,$filter_name,$year,$month);
	if (count($airport_airport_array) > 0) {
		print '<h2>'._("Top 10 Most Common Departure Airports").'</h2>';
		print '<div id="chart3" class="chart" width="100%"></div><script>';
		print "\n";
		print 'var series = [';
		$airport_data = '';
		foreach($airport_airport_array as $airport_item) {
			$airport_data .= '[ "'.$airport_item['airport_departure_icao_count'].'", "'.$airport_item['airport_departure_icao'].'",'.$airport_item['airport_departure_latitude'].','.$airport_item['airport_departure_longitude'].'],';
		}
		$airport_data = substr($airport_data, 0, -1);
		print $airport_data;
		print '];'."\n";
		print 'var onlyValues = series.map(function(obj){ return obj[0]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);'."\n";
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);'."\n";
		print 'var radiusScale = d3.scale.log().domain([minValue,maxValue]).range([0,10]);'."\n";
		print 'var dataset = [];'."\n";
		print 'var colorset = [];'."\n";
		print 'colorset["defaultFill"] = "#F5F5F5";';
		print 'series.forEach(function(item){'."\n";
		print 'var cnt = item[0], nm = item[1], lat = item[2], long = item[3];'."\n";
		print 'colorset[nm] = paletteScale(cnt);';
		print 'dataset.push({ count: cnt, name: nm, radius: Math.floor(radiusScale(cnt)), latitude: lat, longitude: long, fillKey: nm });'."\n";
		print '});'."\n";
		print 'var bbl = new Datamap({
		    element: document.getElementById("chart3"),
		    projection: "mercator", // big world map
		    fills: colorset,
		    responsive: true,
		    geographyConfig: {
			borderColor: "#DEDEDE",
			highlightBorderWidth: 2,
			highlightFillColor: function(geo) {
			    return geo["fillColor"] || "#F5F5F5";
			},
			highlightBorderColor: "#B7B7B7"
		    },
		    done: function(datamap) {
			datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
			function redraw() {
			    datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
			}
		    }
		});
		bbl.bubbles(dataset,{
		    popupTemplate: function(geo, data) {
			if (!data) { return ; }
			return ['."'".'<div class="hoverinfo">'."','<strong>', data.name, '</strong>','<br>Count: <strong>', data.count, '</strong>','</div>'].join('');
		    }
		});";
		print '</script>';
		print '<div class="more"><a href="'.$globalURL.'/statistics/airport-departure'; 
		if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao;
		print '" class="btn btn-default btn" role="button">'._("See full statistic").'&raquo;</a></div>';
	}
?>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
<?php
	$airport_airport_array2 = $Stats->countAllArrivalAirports(true,$airline_icao,$filter_name,$year,$month);
	if (count($airport_airport_array2) > 0) {
		print '<h2>'._("Top 10 Most Common Arrival Airports").'</h2>';
		print '<div id="chart4" class="chart" width="100%"></div><script>';
		print "\n";
		print 'var series = [';
		$airport_data = '';
		foreach($airport_airport_array2 as $airport_item)
		{
			if (isset($airport_item['airport_arrival_longitude']) && isset($airport_item['airport_arrival_latitude'])) {
				$airport_data .= '[ "'.$airport_item['airport_arrival_icao_count'].'", "'.$airport_item['airport_arrival_icao'].'",'.$airport_item['airport_arrival_latitude'].','.$airport_item['airport_arrival_longitude'].'],';
			}
		}
		$airport_data = substr($airport_data, 0, -1);
		print $airport_data;
		print '];'."\n";
		print 'var onlyValues = series.map(function(obj){ return obj[0]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);'."\n";
		print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);'."\n";
		print 'var radiusScale = d3.scale.log().domain([minValue,maxValue]).range([0,10]);'."\n";
		print 'var dataset = [];'."\n";
		print 'var colorset = [];'."\n";
		print 'colorset["defaultFill"] = "#F5F5F5";';
		print 'series.forEach(function(item){'."\n";
		print 'var cnt = item[0], nm = item[1], lat = item[2], long = item[3];'."\n";
		print 'colorset[nm] = paletteScale(cnt);';
		print 'dataset.push({ count: cnt, name: nm, radius: Math.floor(radiusScale(cnt)), latitude: lat, longitude: long, fillKey: nm });'."\n";
		print '});'."\n";
		print 'var bbl = new Datamap({
		    element: document.getElementById("chart4"),
		    projection: "mercator", // big world map
		    fills: colorset,
		    responsive: true,
		    geographyConfig: {
			borderColor: "#DEDEDE",
			highlightBorderWidth: 2,
			highlightFillColor: function(geo) {
			    return geo["fillColor"] || "#F5F5F5";
			},
			highlightBorderColor: "#B7B7B7"
		    },
		    done: function(datamap) {
			datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
			function redraw() {
			    datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
			}
		    }
		});
		bbl.bubbles(dataset,{
		    popupTemplate: function(geo, data) {
			if (!data) { return ; }
			return ['."'".'<div class="hoverinfo">'."','<strong>', data.name, '</strong>','<br>Count: <strong>', data.count, '</strong>','</div>'].join('');
		    }
		});";
		print '</script>';
		print '<div class="more"><a href="'.$globalURL.'/statistics/airport-arrival';
		if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao;
		print '" class="btn btn-default btn" role="button">'._("See full statistic").'&raquo;</a></div>';
	}
?>
            </div>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php 
}
if ($type == 'aircraft') {
?>
<?php
	if ($year == '' && $month == '') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Busiest Months of the last 12 Months"); ?></h2>
<?php
		$year_array = $Stats->countAllMonthsLastYear(true,$airline_icao,$filter_name);
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart8" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year_name'].'-'.$year_item['month_name'].'-01'.'",';
				$year_cnt .= $year_item['date_count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['flights',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart8",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m"}}, y: { label: "# of Flights"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/year<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last Month"); ?></h2>
<?php
		$month_array = $Stats->countAllDatesLastMonth($airline_icao,$filter_name);
		if (count($month_array) == 0) print _("No data available");
		else {
			print '<div id="chart9" class="chart" width="100%"></div><script>';
			$month_data = '';
			$month_cnt = '';
			foreach($month_array as $month_item) {
				$month_data .= '"'.$month_item['date_name'].'",';
				$month_cnt .= $month_item['date_count'].',';
			}
			$month_data = "['x',".substr($month_data, 0, -1)."]";
			$month_cnt = "['flights',".substr($month_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart9",
			    data: { x: "x",
			     columns: ['.$month_data.','.$month_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m-%d"}}, y: { label: "# of Flights"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/month<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last 7 Days"); ?></h2>
<?php
		$date_array = $Stats->countAllDatesLast7Days($airline_icao,$filter_name);
		if (empty($date_array)) print _("No data available");
		else {
			print '<div id="chart5" class="chart" width="100%"></div><script>';
			$date_data = '';
			$date_cnt = '';
			foreach($date_array as $date_item) {
				$date_data .= '"'.$date_item['date_name'].'",';
				$date_cnt .= $date_item['date_count'].',';
			}
			$date_data = "['x',".substr($date_data, 0, -1)."]";
			$date_cnt = "['flights',".substr($date_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart5",
			    data: { x: "x",
			     columns: ['.$date_data.','.$date_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "timeseries",tick: { format: "%Y-%m-%d"}}, y: { label: "# of Flights"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/date<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Time of the Day"); ?></h2>
<?php
		$hour_array = $Stats->countAllHours('hour',true,$airline_icao,$filter_name);
		if (empty($hour_array)) print _("No data available");
		else {
			print '<div id="chart6" class="chart" width="100%"></div><script>';
			$hour_data = '';
			$hour_cnt = '';
			foreach($hour_array as $hour_item) {
				$hour_data .= '"'.$hour_item['hour_name'].':00",';
				$hour_cnt .= $hour_item['hour_count'].',';
			}
			$hour_data = "[".substr($hour_data, 0, -1)."]";
			$hour_cnt = "['flights',".substr($hour_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart6",
			    data: {
			     columns: ['.$hour_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "category", categories: '.$hour_data.'},y: { label: "# of Flights"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/time<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
	}
}
?>
<?php
if ($type == 'marine') {
?>
<?php
	if ($year == '' && $month == '') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Busiest Months of the last 12 Months"); ?></h2>
<?php
		$year_array = $Stats->countAllMarineMonthsLastYear(true,$filter_name);
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart8" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year_name'].'-'.$year_item['month_name'].'-01'.'",';
				$year_cnt .= $year_item['date_count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['vessels',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart8",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { vessels: "area"}, colors: { vessels: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m"}}, y: { label: "# of Vessels"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
            
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last Month"); ?></h2>
<?php
		$month_array = $Stats->countAllMarineDatesLastMonth($filter_name);
		if (count($month_array) == 0) print _("No data available");
		else {
			print '<div id="chart9" class="chart" width="100%"></div><script>';
			$month_data = '';
			$month_cnt = '';
			foreach($month_array as $month_item) {
				$month_data .= '"'.$month_item['date_name'].'",';
				$month_cnt .= $month_item['date_count'].',';
			}
			$month_data = "['x',".substr($month_data, 0, -1)."]";
			$month_cnt = "['vessels',".substr($month_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart9",
			    data: { x: "x",
			     columns: ['.$month_data.','.$month_cnt.'], types: { vessels: "area"}, colors: { vessels: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m-%d"}}, y: { label: "# of Vessels"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/month" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last 7 Days"); ?></h2>
<?php
		$date_array = $Stats->countAllMarineDatesLast7Days($filter_name);
		if (empty($date_array)) print _("No data available");
		else {
			print '<div id="chart5" class="chart" width="100%"></div><script>';
			$date_data = '';
			$date_cnt = '';
			foreach($date_array as $date_item) {
				$date_data .= '"'.$date_item['date_name'].'",';
				$date_cnt .= $date_item['date_count'].',';
			}
			$date_data = "['x',".substr($date_data, 0, -1)."]";
			$date_cnt = "['vessels',".substr($date_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart5",
			    data: { x: "x",
			     columns: ['.$date_data.','.$date_cnt.'], types: { vessels: "area"}, colors: { vessels: "#1a3151"}},
			     axis: { x: { type: "timeseries",tick: { format: "%Y-%m-%d"}}, y: { label: "# of Vessels"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/date" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Time of the Day"); ?></h2>
<?php
		$hour_array = $Stats->countAllMarineHours('hour',true,$filter_name);
		if (empty($hour_array)) print _("No data available");
		else {
			print '<div id="chart6" class="chart" width="100%"></div><script>';
			$hour_data = '';
			$hour_cnt = '';
			foreach($hour_array as $hour_item) {
				$hour_data .= '"'.$hour_item['hour_name'].':00",';
				$hour_cnt .= $hour_item['hour_count'].',';
			}
			$hour_data = "[".substr($hour_data, 0, -1)."]";
			$hour_cnt = "['vessels',".substr($hour_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart6",
			    data: {
			     columns: ['.$hour_cnt.'], types: { vessels: "area"}, colors: { vessels: "#1a3151"}},
			     axis: { x: { type: "category", categories: '.$hour_data.'},y: { label: "# of Vessels"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/time" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
	}
}
?>
<?php
if ($type == 'tracker') {
?>
<?php
	if ($year == '' && $month == '') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Busiest Months of the last 12 Months"); ?></h2>
<?php
		$year_array = $Stats->countAllTrackerMonthsLastYear(true);
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart8" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year_name'].'-'.$year_item['month_name'].'-01'.'",';
				$year_cnt .= $year_item['date_count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['trackers',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart8",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { trackers: "area"}, colors: { trackers: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m"}}, y: { label: "# of Trackers"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
            
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last Month"); ?></h2>
<?php
		$month_array = $Stats->countAllTrackerDatesLastMonth();
		if (count($month_array) == 0) print _("No data available");
		else {
			print '<div id="chart9" class="chart" width="100%"></div><script>';
			$month_data = '';
			$month_cnt = '';
			foreach($month_array as $month_item) {
				$month_data .= '"'.$month_item['date_name'].'",';
				$month_cnt .= $month_item['date_count'].',';
			}
			$month_data = "['x',".substr($month_data, 0, -1)."]";
			$month_cnt = "['trackers',".substr($month_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart9",
			    data: { x: "x",
			     columns: ['.$month_data.','.$month_cnt.'], types: { trackers: "area"}, colors: { trackers: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m-%d"}}, y: { label: "# of Trackers"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/month" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last 7 Days"); ?></h2>
<?php
		$date_array = $Stats->countAllTrackerDatesLast7Days();
		if (empty($date_array)) print _("No data available");
		else {
			print '<div id="chart5" class="chart" width="100%"></div><script>';
			$date_data = '';
			$date_cnt = '';
			foreach($date_array as $date_item) {
				$date_data .= '"'.$date_item['date_name'].'",';
				$date_cnt .= $date_item['date_count'].',';
			}
			$date_data = "['x',".substr($date_data, 0, -1)."]";
			$date_cnt = "['trackers',".substr($date_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart5",
			    data: { x: "x",
			     columns: ['.$date_data.','.$date_cnt.'], types: { trackers: "area"}, colors: { trackers: "#1a3151"}},
			     axis: { x: { type: "timeseries",tick: { format: "%Y-%m-%d"}}, y: { label: "# of Trackers"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/marine/statistics/date" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Time of the Day"); ?></h2>
<?php
		$hour_array = $Stats->countAllTrackerHours('hour',true);
		if (empty($hour_array)) print _("No data available");
		else {
			print '<div id="chart6" class="chart" width="100%"></div><script>';
			$hour_data = '';
			$hour_cnt = '';
			foreach($hour_array as $hour_item) {
				$hour_data .= '"'.$hour_item['hour_name'].':00",';
				$hour_cnt .= $hour_item['hour_count'].',';
			}
			$hour_data = "[".substr($hour_data, 0, -1)."]";
			$hour_cnt = "['trackers',".substr($hour_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart6",
			    data: {
			     columns: ['.$hour_cnt.'], types: { trackers: "area"}, colors: { trackers: "#1a3151"}},
			     axis: { x: { type: "category", categories: '.$hour_data.'},y: { label: "# of Trackers"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/time" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
	}
}
?>
<?php
if ($type == 'satellite') {
?>
<?php
	if ($year == '' && $month == '') {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Busiest Launch Months of the last 12 Months"); ?></h2>
<?php
		$year_array = $Satellite->countAllMonthsLastYear();
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart21" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year_name'].'-'.$year_item['month_name'].'-01'.'",';
				$year_cnt .= $year_item['date_count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['satellite',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart21",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { trackers: "area"}, colors: { satellite: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m"}}, y: { label: "# of Satellite"}},legend: { show: false }});';
			print '</script>';
		}
?>
	    <!--
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            -->
            </div>
            
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
            <div class="col-md-6">
                <h2><?php echo _("Busiest Launch Years of the last 10 Years"); ?></h2>
<?php
		$year_array = $Satellite->countAllYears();
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart22" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year_name'].'-01-01'.'",';
				$year_cnt .= $year_item['date_count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['satellite',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart22",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { trackers: "area"}, colors: { satellite: "#1a3151"}},
			     axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y"}}, y: { label: "# of Satellite"}},legend: { show: false }});';
			print '</script>';
		}
?>
	    <!--
                <div class="more">
                    <a href="<?php print $globalURL; ?>/tracker/statistics/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            -->
            </div>
            
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
	}
}
?>
<?php
if ($type == 'aircraft') {
?>
<?php
	if (($airline_icao == '' || $airline_icao == 'all') && $year == '' && $month == '' && isset($globalAccidents) && $globalAccidents) {
?>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Fatalities by Years"); ?></h2>
<?php
		$year_array = $Stats->countFatalitiesByYear();
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart32" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year'].'-01-01",';
				$year_cnt .= $year_item['count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['flights',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart32",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "timeseries",tick: { format: "%Y"}}, y: { label: "# of Fatalities"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/fatalities/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Fatalities last 12 Months"); ?></h2>
<?php
		$year_array = $Stats->countFatalitiesLast12Months();
		if (count($year_array) == 0) print _("No data available");
		else {
			print '<div id="chart33" class="chart" width="100%"></div><script>';
			$year_data = '';
			$year_cnt = '';
			foreach($year_array as $year_item) {
				$year_data .= '"'.$year_item['year'].'-'.$year_item['month'].'-01",';
				$year_cnt .= $year_item['count'].',';
			}
			$year_data = "['x',".substr($year_data, 0, -1)."]";
			$year_cnt = "['flights',".substr($year_cnt,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart33",
			    data: { x: "x",
			     columns: ['.$year_data.','.$year_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
			     axis: { x: { type: "timeseries",tick: { format: "%Y-%m"}}, y: { label: "# of Fatalities"}},legend: { show: false }});';
			print '</script>';
		}
?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/fatalities/month" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<br/>
<?php
	}
}
?>
<?php
if (($airline_icao == '' || $airline_icao == 'all') && $filter_name == '' && $year == '' && $month == '' && (!isset($globalSatellite) || $globalSatellite === FALSE)) {
?>
        <div class="row column">
<?php
	//$polar = $Stats->getStatsSource(date('Y-m-d'),'polar');
	if ($year == '' && $month == '') {
		if ($type == 'aircraft') {
			$polar = $Stats->getStatsSource('polar',date('Y'),date('m'),date('d'));
		} elseif ($type == 'marine') {
			$polar = $Stats->getStatsSource('polar_marine',date('Y'),date('m'),date('d'));
		} elseif ($type == 'tracker') {
			$polar = $Stats->getStatsSource('polar_tracker',date('Y'),date('m'),date('d'));
		}
	} else {
		if ($type == 'aircraft') {
			$polar = $Stats->getStatsSource('polar',$year,$month);
		} elseif ($type == 'marine') {
			$polar = $Stats->getStatsSource('polar_marine',$year,$month);
		} elseif ($type == 'tracker') {
			$polar = $Stats->getStatsSource('polar_tracker',$year,$month);
		}
	}
	if (!empty($polar)) {
		print '<h2>'._("Coverage pattern").'</h2>';
		foreach ($polar as $eachpolar) {
			unset($polar_data);
			$Spotter = new Spotter();
			$data = json_decode($eachpolar['source_data']);
			foreach($data as $value => $key) {
				$direction = $Spotter->parseDirection(($value*22.5));
				$distance = $key;
				$unit = 'km';
				if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
					$distance = round($distance*0.539957);
					$unit = 'nm';
				} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
					$distance = round($distance*0.621371);
					$unit = 'mi';
				} elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
					$distance = $distance;
					$unit = 'km';
				}
				if (!isset($polar_data)) $polar_data = '{axis:"'.$direction[0]['direction_shortname'].'",value:'.$key.'}';
				else $polar_data = $polar_data.',{axis:"'.$direction[0]['direction_shortname'].'",value:'.$key.'}';
			}
?>
            <div class="col-md-6">
                <h4><?php print $eachpolar['source_name']; ?></h4>
        	<div id="polar-<?php print str_replace(' ','_',strtolower($eachpolar['source_name'])); ?>" class="chart" width="100%"></div>
        	<script>
        	    (function() {
        	    var margin = {top: 100, right: 100, bottom: 100, left: 100},
			width = Math.min(700, window.innerWidth - 10) - margin.left - margin.right,
			height = Math.min(width, window.innerHeight - margin.top - margin.bottom - 20);
		    var data = [
				    [
				    <?php print $polar_data; ?>
				    ]
				];
		    var color = d3.scale.ordinal().range(["#EDC951","#CC333F","#00A0B0"]);
		    //var color = d3.scaleOrdinal().range(["#EDC951","#CC333F","#00A0B0"]);
		
		    var radarChartOptions = {
		      w: width,
		      h: height,
		      margin: margin,
		      maxValue: 0.5,
		      levels: 5,
		      roundStrokes: true,
		      color: color,
		      unit: '<?php echo $unit; ?>'
		    };
		    RadarChart("#polar-<?php print str_replace(' ','_',strtolower($eachpolar['source_name'])); ?>", data, radarChartOptions);
		    })();
		</script>
            </div>
            <?php
		}
	}
?>
        </div>
        <div class="row column">
            <div class="col-md-6">
<?php
	//$msg = $Stats->getStatsSource(date('Y-m-d'),'msg');
	if ($year == '' && $month == '') {
		if ($type == 'aircraft') {
			$msg = $Stats->getStatsSource('msg',date('Y'),date('m'),date('d'));
		} elseif ($type == 'marine') {
			$msg = $Stats->getStatsSource('msg_marine',date('Y'),date('m'),date('d'));
		} elseif ($type == 'tracker') {
			$msg = $Stats->getStatsSource('msg_tracker',date('Y'),date('m'),date('d'));
		}
	} else {
		if ($type == 'aircraft') {
			$msg = $Stats->getStatsSource('msg',$year,$month);
		} elseif ($type == 'marine') {
			$msg = $Stats->getStatsSource('msg_marine',$year,$month);
		} elseif ($type == 'tracker') {
			$msg = $Stats->getStatsSource('msg_tracker',$year,$month);
		}
	}
	if (!empty($msg)) {
		print '<h2>'._("Messages received").'</h2>';
		foreach ($msg as $eachmsg) {
			//$eachmsg = $msg[0];
			$data = $eachmsg['source_data'];
			if ($data > 500) $max = (round(($data+100)/100))*100;
			else $max = 500;
?>
        	<div id="msg-<?php print str_replace(' ','_',strtolower($eachmsg['source_name'])); ?>" class="col-md-4"></div>
        	<script>
		      var g = new JustGage({
			    id: "msg-<?php print str_replace(' ','_',strtolower($eachmsg['source_name'])); ?>",
			    value: <?php echo $data; ?>,
			    min: 0,
			    max: <?php print $max; ?>,
			    valueMinFontSize: 10,
			    height: 120,
			    width: 220,
			    symbol: ' msg/s',
			    title: "<?php print $eachmsg['source_name']; ?>"
			  });
		</script>
<?php
		}
	}
?>
            </div>
        </div>
        <div class="row column">
<?php
	//$hist = $Stats->getStatsSource(date('Y-m-d'),'hist');
	if ($year == '' && $month == '') {
		if ($type == 'aircraft') {
			$hist = $Stats->getStatsSource('hist',date('Y'),date('m'),date('d'));
		} elseif ($type == 'marine') {
			$hist = $Stats->getStatsSource('hist_marine',date('Y'),date('m'),date('d'));
		} elseif ($type == 'tracker') {
			$hist = $Stats->getStatsSource('hist_tracker',date('Y'),date('m'),date('d'));
		}
	} else {
		if ($type == 'aircraft') {
			$hist = $Stats->getStatsSource('hist',$year,$month);
		} elseif ($type == 'marine') {
			$hist = $Stats->getStatsSource('hist_marine',$year,$month);
		} elseif ($type == 'tracker') {
			$hist = $Stats->getStatsSource('hist_tracker',$year,$month);
		}
	}
	foreach ($hist as $hists) {
		//$hist_data = '';
		$distance_data = '';
		$nb_data = '';
		$source = $hists['source_name'];
		$hist_array = json_decode($hists['source_data']);
		$unit = 'km';
		foreach($hist_array as $distance => $nb) {
			if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
				$distance = round($distance*0.539957);
				$unit = 'nm';
			} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
				$distance = round($distance*0.621371);
				$unit = 'mi';
			} elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
				$distance = $distance;
				$unit = 'km';
			}
			//$hist_data .= '[ "'.$distance.'",'.$nb.'],';
			$distance_data .= '"'.$distance.'",';
			$nb_data .= $nb.',';
		}
		//$hist_data = substr($hist_data, 0, -1);
		$distance_data = "['x',".substr($distance_data, 0, -1)."]";
		$nb_data = "['flights',".substr($nb_data, 0, -1)."]";
?>
            <div class="col-md-6">
                <h2><?php echo sprintf(_("Distance for %s"),$source); ?></h2>
<?php
		print '<div id="charthist-'.str_replace(' ','_',strtolower($source)).'" class="chart" width="100%"></div><script>';
		print 'c3.generate({
		    bindto: "#charthist-'.str_replace(' ','_',strtolower($source)).'",
		    data: { x: "x",
		    columns: ['.$distance_data.','.$nb_data.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
		    axis: { x: {label : { text: "Distance in '.$unit.'", position: "outer-right"}}, y: { label: "# of Flights"}},legend: { show: false }});';
		print '</script>';
?>
    	    </div>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
	}
?>
        </div>
<?php
}
?>
    </div>
</div>  

<?php
require_once('footer.php');
?>
