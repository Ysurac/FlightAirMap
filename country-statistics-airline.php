<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
if (!isset($_GET['country'])) {
        header('Location: '.$globalURL.'/country');
        die();
}
$Spotter = new Spotter();
$country = ucwords(str_replace("-", " ", urldecode(filter_input(INPUT_GET,'country',FILTER_SANITIZE_STRING))));
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
if (isset($_GET['sort'])) {
	$spotter_array = $Spotter->getSpotterDataByCountry($country, "0,1", $sort);
} else {
	$spotter_array = $Spotter->getSpotterDataByCountry($country, "0,1", '');
}

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Airlines of %s"),$country);
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/country" method="post">';
	print '<select name="country" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$all_countries = $Spotter->getAllCountries();
	foreach($all_countries as $all_country)
	{
		if($country == $all_country['country'])
		{
			print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'" selected="selected">'.$all_country['country'].'</option>';
		} else {
			print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'">'.$all_country['country'].'</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';

	if ($_GET['country'] != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.sprintf(_("Airports &amp; Airlines from %s"),$country).'</h1>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">'._("This special country profile shows all flights that do <u>not</u> have a country of a airline or departure/arrival airport associated with them.").'</div>';
	}

	include('country-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Airlines").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common airlines of flights from airports &amp; airlines of <strong>%s</strong>."),$country).'</p>';

	$airline_array = $Spotter->countAllAirlinesByCountry($country);
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
			if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif'))
			{
				print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
			} elseif (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
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
			print '<td>';
			print $airline_item['airline_count'];
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
	$title = _("Country");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the country does not exist in this database. :(").'</p>';  
}

require_once('footer.php');
?>