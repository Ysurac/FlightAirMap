<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$title = _("Statistics").' - '._("Most common Route by Waypoint");
require_once('header.php');
if (!isset($filter_name)) $filter_name = '';
include('statistics-sub-menu.php'); 

print '<div class="info">
	  	<h1>'._("Most common Route by Waypoint").'</h1>
	  </div>
      <p>'._("Below are the <strong>Top 10</strong> most common routes, based on the waypoint data. Theoretically, since the waypoint data is the full 'planned flight route' this statistic would show the actual most common route.").'</p>';
      
$route_array = $Spotter->countAllRoutesWithWaypoints();
if (!empty($route_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-routes-waypoints table-striped">';
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
		print '<a href="'.$globalURL.'/flightid/'.$route_item['spotter_id'].'">'._("Recent Flight on this route").'</a>';
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