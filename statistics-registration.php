<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Aircraft Registrations");

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == 'all') {
    unset($_COOKIE['stats_airline_icao']);
    setcookie('stats_airline_icao', '', time()-3600);
    $airline_icao = '';
} elseif ($airline_icao == '' && isset($_COOKIE['stats_airline_icao'])) {
    $airline_icao = $_COOKIE['stats_airline_icao'];
} elseif ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
setcookie('stats_airline_icao',$airline_icao);

require_once('header.php');
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
 	<div class="info">
		<h1>'._("Most common Aircraft Registrations").'</h1>
	</div>
    	<p>'._("Below are the <strong>Top 10</strong> most common aircraft registrations.").'</p>';
  
$registration_array = $Stats->countAllAircraftRegistrations(true,$airline_icao,$filter_name);
print '<div id="chart" class="chart" width="100%"></div>
      	    <script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Aircraft Manufacturer").'", "'._("# of times").'"], ';
            	$registration_data = '';
foreach($registration_array as $registration_item)
{
	$registration_data .= '[ "'.$registration_item['registration'].' - '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')",'.$registration_item['aircraft_registration_count'].'],';
}
$registration_data = substr($registration_data, 0, -1);
print $registration_data;
print ']);

            var options = {
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	 is3D: true
            };
    
            var chart = new google.visualization.PieChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

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