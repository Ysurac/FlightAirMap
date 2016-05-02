<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
if (!isset($_GET['aircraft_manufacturer'])) {
        header('Location: '.$globalURL.'/manufacturer');
        die();
}
$Spotter = new Spotter();
$manufacturer = ucwords(str_replace("-", " ", filter_input(INPUT_GET,'aircraft_manufacturer',FILTER_SANITIZE_STRING)));
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByManufacturer($manufacturer,"0,1", $sort);

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Airlines by Country from %s"),$manufacturer);

	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/manufacturer" method="post">';
	print '<select name="aircraft_manufacturer" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$all_manufacturers = $Spotter->getAllManufacturers();
	foreach($all_manufacturers as $all_manufacturer)
	{
		if($GET['aircraft_manufacturer'] == strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])))
		{
			print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
		} else {
			print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';

	print '<div class="info column">';
	print '<h1>'.$manufacturer.'</h1>';
	print '</div>';

	include('manufacturer-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Airlines by Country").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common airlines by Country of origin of flights from <strong>%s</strong>."),$manufacturer).'</p>';
	$airline_array = $Spotter->countAllAirlineCountriesByManufacturer($manufacturer);
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
	$title = _("Manufacturer");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the aircraft manufacturer does not exist in this database. :(").'</p>'; 
}

require_once('footer.php');
?>