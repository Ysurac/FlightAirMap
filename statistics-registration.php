<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Aircraft Registrations");

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
setcookie('stats_airline_icao',$airline_icao,time()+60*60*24,'/');
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);

require_once('header.php');
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3pie.min.js"></script>';
print '<div class="info">
		<h1>'._("Most common Aircraft Registrations").'</h1>
	</div>
    	<p>'._("Below are the <strong>Top 10</strong> most common aircraft registrations.").'</p>';
  
$registration_array = $Stats->countAllAircraftRegistrations(true,$airline_icao,$filter_name,$year,$month);
print '<div id="chart" class="chart" width="100%"></div><script>';
$registration_data = '';
foreach($registration_array as $registration_item)
{
	$registration_data .= '[ "'.$registration_item['registration'].' - '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')",'.$registration_item['aircraft_registration_count'].'],';
}
$registration_data = substr($registration_data, 0, -1);
print 'var series = ['.$registration_data.'];';
print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
print 'var aircraftype = new d3pie("chart",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
print 'dataset';
print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#adadad","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
print '</script>';

if (!empty($registration_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-registration table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th></th>';
	print '<th>'._("Registration").'</th>';
	print '<th>'._("Aircraft").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($registration_array as $registration_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		if (isset($registration_item['image_thumbnail']) && $registration_item['image_thumbnail'] != "")
		{
			print '<td class="aircraft_thumbnail">';
			print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'"><img src="'.$registration_item['image_thumbnail'].'" class="img-rounded" data-toggle="popover" title="'.$registration_item['registration'].' - '.$registration_item['aircraft_icao'].' - '.$registration_item['airline_name'].'" alt="'.$registration_item['registration'].' - '.$registration_item['airline_name'].'" data-content="'._("Registration:").' '.$registration_item['registration'].'<br />'._("Aircraft:").' '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')<br />'._("Airline:").' '.$registration_item['airline_name'].'" data-html="true" width="100px" /></a>';
		 	print '</td>';
		} else {
			print '<td class="aircraft_thumbnail">';
			print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$registration_item['registration'].' - '.$registration_item['aircraft_icao'].'" alt="'.$registration_item['registration'].'" data-content="'._("Registration:").' '.$registration_item['registration'].'<br />'._("Aircraft:").' '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')" data-html="true" width="100px" /></a>';
			print '</td>';
		}
		print '<td>';
		print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'">'.$registration_item['registration'].'</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/aircraft/'.$registration_item['aircraft_icao'].'">'.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')</a>';
		print '</td>';
		print '<td>'.$registration_item['aircraft_registration_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}
require_once('footer.php');
?>