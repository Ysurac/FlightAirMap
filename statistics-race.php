<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
require_once('require/class.Language.php');
$Marine = new Marine();
$title = _("Statistics").' - '._("Top 10 Races Number of Participants");

if (!isset($filter_name)) $filter_name = '';
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
$type = 'marine';
require_once('header.php');
include('statistics-sub-menu.php');
print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3pie.min.js"></script>';
print '<div class="info">
	 	<h1>'._("Top 10 Races Number of Participants").'</h1>
	</div>
	<p>'._("Below are the <strong>Top 10</strong> races with the most number of participants.").'</p>';
	  
$race_array = $Marine->countAllCaptainsByRaces(true,$filter_name,$year,$month);
print '<div id="chart" class="chart" width="100%"></div><script>';
$pilot_data = '';
foreach($race_array as $race_item)
{
	$race_data .= '["'.$race_item['marine_race_name'].' ('.$race_item['marine_race_id'].')",'.$race_item['marine_captain_count'].'],';
}
$race_data = substr($race_data, 0, -1);
print 'var series = ['.$race_data.'];';
print 'var dataset = [];var onlyValues = series.map(function(obj){ return obj[1]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);';
print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#e6e6f6","#1a3151"]);';
print 'series.forEach(function(item){var lab = item[0], value = item[1]; dataset.push({"label":lab,"value":value,"color":paletteScale(value)});});';
print 'var marinetype = new d3pie("chart",{"header":{"title":{"fontSize":24,"font":"open sans"},"subtitle":{"color":"#999999","fontSize":12,"font":"open sans"},"titleSubtitlePadding":9},"footer":{"color":"#999999","fontSize":10,"font":"open sans","location":"bottom-left"},"size":{"canvasWidth":700,"pieOuterRadius":"60%"},"data":{"sortOrder":"value-desc","content":';
print 'dataset';
print '},"labels":{"outer":{"pieDistance":32},"inner":{"hideWhenLessThanPercentage":3,"format":"value"},"mainLabel":{"fontSize":11},"percentage":{"color":"#ffffff","decimalPlaces":0},"value":{"color":"#98e8e3","fontSize":11},"lines":{"enabled":true},"truncation":{"enabled":true}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}},"misc":{"gradient":{"enabled":true,"percentage":100}}});';
print '</script>';

if (!empty($race_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-type table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Race Name").'</th>';
	print '<th>'._("# of captains").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($race_array as $race_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print $race_item['marine_race_name'].' ('.$race_item['marine_race_id'].')</a>';
		print '</td>';
		print '<td>';
		print $race_item['marine_captain_count'];
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