<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$title = _("Statistics").' - '._("Most common Route by Airport");
require_once('header.php');
if (!isset($filter_name)) $filter_name = '';
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3pie.min.js"></script>';
print '<div class="info">
	  	<h1>'._("Most common Route by Airport").'</h1>
	  </div>
	<p>'._("Below are the <strong>Top 10</strong> most common Departure &amp; Arrival airport combinations.").'</p>';
     
$route_array = $Spotter->countAllRoutes();
print '<div id="chart" class="chart" width="100%"></div><script>';
$route_data = '';
foreach($route_array as $route_item)
{
	$route_data .= '[ "'.$route_item['airport_departure_icao'].' - '.$route_item['airport_arrival_icao'].'",'.$route_item['route_count'].'],';
}
$route_data = substr($route_data, 0, -1);
print 'var series = ['.$route_data.'];';
print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
print 'var aircraftype = new d3pie("chart",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
print 'dataset';
print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
print '</script>';


if (!empty($route_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-routes table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Departure Airport").'</th>';
	print '<th>'._("Arrival Airport").'</th>';
	print '<th>'._("# of times").'</th>';
	print '<th></th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($route_array as $route_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_departure_icao'].'">'.$route_item['airport_departure_city'].', '.$route_item['airport_departure_country'].' ('.$route_item['airport_departure_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_arrival_icao'].'">'.$route_item['airport_arrival_city'].', '.$route_item['airport_arrival_country'].' ('.$route_item['airport_arrival_icao'].')</a>';
		print '</td>';
		print '<td>'.$route_item['route_count'].'</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">'._("Route Profile").'</a>';
		print '</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>