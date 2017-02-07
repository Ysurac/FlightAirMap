<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
if (!isset($_GET['pilot'])) {
        header('Location: '.$globalURL.'/pilot');
        die();
}
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$pilot = filter_input(INPUT_GET,'pilot',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByPilot($pilot,"0,1", $sort);

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Departure Airports by Owner of %s"),$spotter_array[0]['pilot_name']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['pilot_name'].'</h1>';
	//print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
	//print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';
	include('owner-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Departure Airports by Country").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows all departure airports by Country of origin of flights piloted by <strong>%s</strong>."),$spotter_array[0]['pilot_name']).'</p>';

	$airport_country_array = $Spotter->countAllDepartureAirportCountriesByPilot($pilot);
	print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
	print '<div id="chartCountry" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Country").'", "'._("# of times").'"], ';
	$country_data = '';
	foreach($airport_country_array as $airport_item)
	{
		$country_data .= '[ "'.$airport_item['departure_airport_country'].'",'.$airport_item['airport_departure_country_count'].'],';
	}
	$country_data = substr($country_data, 0, -1);
	print $country_data;
	print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	colors: ["#8BA9D0","#1a3151"]
            };
    
            var chart = new google.visualization.GeoChart(document.getElementById("chartCountry"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

	if (!empty($airport_country_array))
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
		foreach($airport_country_array as $airport_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['departure_airport_country'])).'">'.$airport_item['departure_airport_country'].'</a>';
			print '</td>';
			print '<td>';
			print $airport_item['airport_departure_country_count'];
			print '</td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Pilot");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this pilot is not in the database. :(").'</p>';
}

require_once('footer.php');
?>