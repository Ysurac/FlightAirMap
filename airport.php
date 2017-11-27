<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Common.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');

if (isset($_POST['airport']))
{
	header('Location: '.$globalURL.'/airport/'.filter_input(INPUT_POST,'airport',FILTER_SANITIZE_STRING));
} else if (isset($_GET['airport'])){
	$Spotter = new Spotter();
	//calculuation for the pagination
	if($_GET['limit'] == "")
	{
	  $limit_start = 0;
	  $limit_end = 25;
	  $absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$airport = filter_input(INPUT_GET,'airport',FILTER_SANITIZE_STRING);
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/airport/'.$airport;
	$airport_array = $Spotter->getAllAirportInfo($airport);
	
	if (!empty($airport_array))
	{
		$spotter_array = $Spotter->getSpotterDataByAirport($airport,$limit_start.",".$absolute_difference, $sort);
		$title = $airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';

		require_once('header.php');
		
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/airport" method="post">';
		print '<select name="airport" class="selectpicker" data-live-search="true">';
		print '<option></option>';
		$Stats = new Stats();
		$airport_names = $Stats->getAllAirportNames();
		if (empty($airport_names)) {
			$airport_names = $Spotter->getAllAirportNames();
		}
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
			print '<div class="alert alert-warning">'._("This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.").'</div>';
		}

		include('airport-sub-menu.php');
		print '<div class="table column">';
		if ($airport_array[0]['iata'] != "NA")
		{
			print '<p>'.sprintf(_("The table below shows the route(s) aircraft have used to/from <strong>%s</strong>, sorted by the most recent one."),$airport_array[0]['name']).'</p>';
		}

		include('table-output.php');  
		print '<div class="pagination">';
		if ($limit_previous_1 >= 0)
		{
			print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$sort.'">&laquo;'._("Previous Page").'</a>';
		}
		if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
		{
			print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$sort.'">'._("Next Page").'&raquo;</a>';
		}
		print '</div>';
		print '</div>';
	} else {
		$title = _("Airport");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, the airport does not exist in this database. :(").'</p>'; 
	}
} else {
	$Spotter= new Spotter();
	$Stats = new Stats();
	$Common = new Common();
	$title = _("Airports");
	require_once('header.php');
	print '<div class="column">';
	print '<h1>'._("Airports").'</h1>';
	$airport_names = $Stats->getAllAirportNames();
	if (empty($airport_names)) {
		$airport_names = $Spotter->getAllAirportNames();
	}
	ksort($airport_names);
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($airport_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['airport_city'], 0, 1));
		if($previous !== $firstLetter)
		{
			if ($previous !== null){
				print ' | ';
			}
			print '<a href="#'.$firstLetter.'">'.$firstLetter.'</a>';
		}
		$previous = $firstLetter;
	}
	print '</div>';
	$previous = null;
	foreach($airport_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['airport_city'], 0, 1));
		if ($firstLetter != "")
		{
			if($previous !== $firstLetter)
			{
				if ($previous !== null){
					print '</div>';
				}
				print '<a name="'.$firstLetter.'"></a><h4 class="alphabet-header">'.$firstLetter.'</h4><div class="alphabet">';
			}
			$previous = $firstLetter;
			print '<div class="alphabet-item">';
			print '<a href="'.$globalURL.'/airport/'.$value['airport_icao'].'">';
			print $value['airport_city'].', '.$value['airport_name'].', '.$value['airport_country'].' ('.$value['airport_icao'].')';
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
}

require_once('footer.php');
?>