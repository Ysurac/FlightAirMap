<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Airline");
require_once('header.php');
if (!isset($filter_name)) $filter_name = '';
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3pie.min.js"></script>';
print '<div class="info">
	  	<h1>'._("Most common Airline").'</h1>
	  </div>
    	<p>'._("Below are the <strong>Top 10</strong> most common airlines.").'</p>';

$airline_array = $Stats->countAllAirlines(true,$filter_name,$year,$month);
print '<div id="chart" class="chart" width="100%"></div><script>';
$airline_data = '';
foreach($airline_array as $airline_item)
{
	$airline_data .= '["'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')",'.$airline_item['airline_count'].'],';
}
$airline_data = substr($airline_data, 0, -1);
print 'var series = ['.$airline_data.'];';
print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);';
print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
print 'var airlinescnt = new d3pie("chart",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
print 'dataset';
print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
print '</script>';

if (!empty($airline_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-airline table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th></th>';
	print '<th>'._("Airline").'</th>';
	print '<th>'._("Country").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($airline_array as $airline_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td class="logo">';
		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
		if (isset($globalIVAO) && $globalIVAO && (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif') || @getimagesize('images/airlines/'.$airline_item['airline_icao'].'.gif')))
		{
			print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
		} elseif (@getimagesize('images/airlines/'.$airline_item['airline_icao'].'.png') || @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
		{
			print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
		} else {
			print $globalURL.'/images/airlines/placeholder.png';
		}
		print '" /></a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
		print '</td>';
		print '<td>'.$airline_item['airline_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}
require_once('footer.php');
?>