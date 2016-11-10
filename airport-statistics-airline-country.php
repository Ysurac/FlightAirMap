<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
if (!isset($_GET['airport'])) {
        header('Location: '.$globalURL.'/airport');
        die();
}
$airport = filter_input(INPUT_GET,'airport',FILTER_SANITIZE_STRING);
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAirport($airport,"0,1","");
$airport_array = $Spotter->getAllAirportInfo($airport);

if (!empty($airport_array))
{
	$title = sprintf(_("Most Common Airlines by Country to/from %s, %s (%s)"),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']);
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airport" method="post">';
	print '<select name="airport" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$Stats = new Stats();
	$airport_names = $Stats->getAllAirportNames();
	if (empty($airport_names)) $airport_names = $Spotter->getAllAirportNames();
	ksort($airport_names);
	foreach($airport_names as $airport_name)
	{
		if($airport == $airport_name['airport_icao'])
		{
			print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		} else {
			print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';
	
	if ($airport != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
		print '<div><span class="label">'._("Name").'</span>'.$airport_array[0]['name'].'</div>';
		print '<div><span class="label">'._("City").'</span>'.$airport_array[0]['city'].'</div>';
		print '<div><span class="label">'._("Country").'</span>'.$airport_array[0]['country'].'</div>';
		print '<div><span class="label">'._("ICAO").'</span>'.$airport_array[0]['icao'].'</div>';
		print '<div><span class="label">'._("IATA").'</span>'.$airport_array[0]['iata'].'</div>';
		print '<div><span class="label">'._("Altitude").'</span>'.$airport_array[0]['altitude'].'</div>';
		print '<div><span class="label">'._("Coordinates").'</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">'._("This special airport profile shows all flights that do NOT have a departure and/or arrival airport associated with them.").'</div>';
	}

	include('airport-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Airlines by Country").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common airlines by Country of origin of flights to/from <strong>%s, %s (%s)</strong>."),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']).'</p>';

	$airline_array = $Spotter->countAllAirlineCountriesByAirport($airport);
	print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
	print '<div id="chartCountry" class="chart" width="100%"></div>
	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Country").'", "'._("# of times").'"], ';
            	$country_data = '';
	foreach($airline_array as $airline_item)
	{
		$country_data .= '[ "'.$airline_item['airline_country'].'",'.$airline_item['airline_country_count'].'],';
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
			print '<td>';
			print $airline_item['airline_country_count'];
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
	$title = _("Airport");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the airport does not exist in this database. :(").'</p>';  
}

require_once('footer.php');
?>