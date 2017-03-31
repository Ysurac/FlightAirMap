<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Arrival Airport by Country");

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
	  	<h1>'._("Most common Arrival Airport by Country").'</h1>
	  </div>
    	 <p>'._("Below are the <strong>Top 10</strong> most common countries of all the arrival airports.").'</p>';
print '<div id="chartCountry" class="chart" width="100%"></div>';

$airport_country_array = $Stats->countAllArrivalCountries(true,$airline_icao,$filter_name,$year,$month);
print '<script>';
print 'var series = [';
$country_data = '';
foreach($airport_country_array as $airport_item)
{
	$country_data .= '[ "'.$airport_item['airport_arrival_country_iso3'].'",'.$airport_item['airport_arrival_country_count'].'],';
}
$country_data = substr($country_data, 0, -1);
print $country_data;
print '];';
print 'var dataset = {};var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
print 'series.forEach(function(item){var iso = item[0], value = item[1]; dataset[iso] = { numberOfThings: value, fillColor: paletteScale(value) };});';
print 'new Datamap({
	    element: document.getElementById("chartCountry"),
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

print '<div class="table-responsive">';
print '<table class="common-country table-striped">';
print '<thead>';
print '<th></th>';
print '<th>'._("Country").'</th>';
print '<th>'._("# of times").'</th>';
print '</thead>';
print '<tbody>';
$i = 1;
foreach($airport_country_array as $airport_item)
{
	print '<tr>';
	print '<td><strong>'.$i.'</strong></td>';
	print '<td>';
	print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
	print '</td>';
	print '<td>';
	print $airport_item['airport_arrival_country_count'];
	print '</td>';
	print '</tr>';
	$i++;
}
print '<tbody>';
print '</table>';
print '</div>';

require_once('footer.php');
?>