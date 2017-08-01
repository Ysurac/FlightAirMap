<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();

if (!isset($filter_name)) $filter_name = '';
$type = 'aircraft';
if (isset($_GET['marine'])) {
	$type = 'marine';
	$title = _("Statistics").' - '._("Most common Country a vessel was inside");
	require_once('require/class.Marine.php');
	$Marine = new Marine();
} elseif (isset($_GET['tracker'])) {
	$type = 'tracker';
	$title = _("Statistics").' - '._("Most common Country a tracker was inside");
	require_once('require/class.Tracker.php');
	$Tracker = new Tracker();
} else {
	$title = _("Statistics").' - '._("Most common Country a flight was over");
}
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}

require_once('header.php');
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
print '<div class="info">';
if ($type == 'aircraft') {
	print '<h1>'._("Most common Country a flight was over").'</h1>';
} elseif ($type == 'marine') {
	print '<h1>'._("Most common Country a vessel was inside").'</h1>';
} elseif ($type == 'tracker') {
	print '<h1>'._("Most common Country a tracker was inside").'</h1>';
}
print '</div>';
if ($type == 'aircraft') {
	print '<p>'._("Below are the <strong>Top 10</strong> most common country a flight was over.").'</p>';
} elseif ($type == 'marine') {
	print '<p>'._("Below are the <strong>Top 10</strong> most common country a vessel was inside.").'</p>';
} elseif ($type == 'tracker') {
	print '<p>'._("Below are the <strong>Top 10</strong> most common country a tracker was inside.").'</p>';
}

if ($type == 'aircraft') {
	$flightover_array = $Stats->countAllFlightOverCountries(false,$airline_icao,$filter_name);
} elseif ($type == 'marine') {
	$flightover_array = $Marine->countAllMarineOverCountries();
} elseif ($type == 'tracker') {
	$flightover_array = $Tracker->countAllTrackerOverCountries();
}
/*
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$flightover_array = $Spotter->countAllFlightOverCountries(true,$airline_icao,$filter_name);
*/
print '<div id="chart" class="chart" width="100%"></div><script>';
print 'var series = [';
$flightover_data = '';
foreach($flightover_array as $flightover_item)
{
	if ($type == 'aircraft') $flightover_data .= '[ "'.$flightover_item['flight_country_iso3'].'",'.$flightover_item['flight_count'].'],';
	elseif ($type == 'marine') $flightover_data .= '[ "'.$flightover_item['marine_country_iso3'].'",'.$flightover_item['marine_count'].'],';
	elseif ($type == 'tracker') $flightover_data .= '[ "'.$flightover_item['tracker_country_iso3'].'",'.$flightover_item['tracker_count'].'],';
}
$flightover_data = substr($flightover_data, 0, -1);
print $flightover_data;
print '];';
print 'var dataset = {};var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
print 'series.forEach(function(item){var iso = item[0], value = item[1]; dataset[iso] = { numberOfThings: value, fillColor: paletteScale(value) };});';
print 'new Datamap({
    element: document.getElementById("chart"),
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

if (!empty($flightover_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-countries table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Name").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	array_splice($flightover_array,10);
	foreach($flightover_array as $flightover_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		if ($type == 'aircraft') {
			print '<td>'.$flightover_item['flight_country'].'</td>';
			print '<td>'.$flightover_item['flight_count'].'</td>';
		} elseif ($type == 'marine') {
			print '<td>'.$flightover_item['marine_country'].'</td>';
			print '<td>'.$flightover_item['marine_count'].'</td>';
		} elseif ($type == 'tracker') {
			print '<td>'.$flightover_item['tracker_country'].'</td>';
			print '<td>'.$flightover_item['tracker_count'].'</td>';
		}
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>