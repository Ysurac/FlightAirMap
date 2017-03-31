<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Airline by Country");
if (!isset($filter_name)) $filter_name = '';
require_once('header.php');
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
print '<div class="info">
	  	<h1>'._("Most common Airline by Country").'</h1>
	  </div>
      <p>'._("Below are the <strong>Top 10</strong> countries that an airline belongs to.").'</p>';

$airline_array = $Stats->countAllAirlineCountries(true,$filter_name,$year,$month);
if (count($airline_array) > 0) {
print '<div id="chartCountry" class="chart" width="100%"></div><script>';
$country_data = '';
foreach($airline_array as $airline_item)
{
	$country_data .= '[ "'.$airline_item['airline_country_iso3'].'",'.$airline_item['airline_country_count'].'],';
}
$country_data = substr($country_data, 0, -1);
print 'var series = ['.$country_data.'];';
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
}
if (!empty($airline_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-country table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Country").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($airline_array as $airline_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
		print '</td>';
		print '<td>'.$airline_item['airline_country_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>