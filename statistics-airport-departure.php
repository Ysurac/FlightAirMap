<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Departure Airport");

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
require_once('header.php');

include('statistics-sub-menu.php'); 
print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
print '<div class="info">
	  	<h1>'._("Most common Departure Airport").'</h1>
	  </div>
    	<p>'._("Below are the <strong>Top 10</strong> most common departure airports.").'</p>';

$airport_airport_array = $Stats->countAllDepartureAirports(true,$airline_icao,$filter_name,$year,$month);
print '<div id="chartAirport" class="chart" width="100%"></div>';
print '<script>';
print 'var series = [';
$airport_data = '';
foreach($airport_airport_array as $airport_item)
{
	$airport_data .= '[ "'.$airport_item['airport_departure_icao_count'].'", "'.$airport_item['airport_departure_icao'].'",'.$airport_item['airport_departure_latitude'].','.$airport_item['airport_departure_longitude'].'],';
}
$airport_data = substr($airport_data, 0, -1);
print $airport_data;
print '];'."\n";
print 'var onlyValues = series.map(function(obj){ return obj[0]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);'."\n";
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);'."\n";
print 'var radiusScale = d3.scale.log().domain([minValue,maxValue]).range([2,20]);'."\n";
print 'var dataset = [];'."\n";
print 'var colorset = [];'."\n";
print 'colorset["defaultFill"] = "#F5F5F5";';
print 'series.forEach(function(item){'."\n";
print 'var cnt = item[0], nm = item[1], lat = item[2], long = item[3];'."\n";
print 'colorset[nm] = paletteScale(cnt);';
print 'dataset.push({ count: cnt, name: nm, radius: Math.floor(radiusScale(cnt)), latitude: lat, longitude: long, fillKey: nm });'."\n";
print '});'."\n";
print 'var bbl = new Datamap({
	    element: document.getElementById("chartAirport"),
	    projection: "mercator", // big world map
	    fills: colorset,
	    responsive: true,
	    geographyConfig: {
	    borderColor: "#DEDEDE",
	    highlightBorderWidth: 2,
	    highlightFillColor: function(geo) {
	    return geo["fillColor"] || "#F5F5F5";
	    },
	highlightBorderColor: "#B7B7B7"},
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
print '<div class="table-responsive">';
print '<table class="common-airport table-striped">';
print '<thead>';
print '<th></th>';
print '<th>'._("Airport").'</th>';
print '<th>'._("Country").'</th>';
print '<th>'._("# of times").'</th>';
print '</thead>';
print '<tbody>';
$i = 1;
foreach($airport_airport_array as $airport_item)
{
	print '<tr>';
	print '<td><strong>'.$i.'</strong></td>';
	print '<td>';
	print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
	print '</td>';
	print '<td>';
	print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
	print '</td>';
	print '<td>';
	print $airport_item['airport_departure_icao_count'];
	print '</td>';
	print '</tr>';
	$i++;
}
print '<tbody>';
print '</table>';
print '</div>';

require_once('footer.php');
?>