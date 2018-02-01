<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');

if (!isset($_GET['airline'])){
	header('Location: '.$globalURL.'/airline');
} else{
	$Spotter = new Spotter();
	//calculuation for the pagination
	if(!isset($_GET['limit']) || $_GET['limit'] == "")
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = filter_var($limit_explode[0],FILTER_SANITIZE_NUMBER_INT);
		$limit_end = filter_var($limit_explode[1],FILTER_SANITIZE_NUMBER_INT);
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$airline = urldecode(filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING));
	$page_url = $globalURL.'/airline/'.$airline;
	$alliance = false;
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$airline_info = array();
	if (strpos($airline,'alliance_') !== FALSE) {
		$spotter_array = $Spotter->getSpotterDataByAirline('',$limit_start.",".$absolute_difference, $sort,array('alliance' => str_replace('_',' ',str_replace('alliance_','',$airline))));
		$alliance = true;
	} else {
		$spotter_array = $Spotter->getSpotterDataByAirline($airline,$limit_start.",".$absolute_difference, $sort);
		if (isset($globalIVAO)) {
			$airline_info = $Spotter->getAllAirlineInfo($airline,'ivao');
		} elseif (isset($globalVATSIM)) {
			$airline_info = $Spotter->getAllAirlineInfo($airline,'vatsim');
		} else {
			$airline_info = $Spotter->getAllAirlineInfo($airline);
		}
	}
	if (!empty($spotter_array) || !empty($airline_info))
	{
		if ($alliance) {
			$title = sprintf(_("Detailed View for %s"),str_replace('_',' ',str_replace('alliance_','',$airline)));
		} else {
			if (isset($airline_info[0]['name']) && isset($airline_info[0]['icao'])) {
				$title = sprintf(_("Detailed View for %s (%s)"),$airline_info[0]['name'],$airline_info[0]['icao']);
			} elseif (isset($spotter_array[0]['airline_name']) && isset($spotter_array[0]['airline_icao'])) {
				$title = sprintf(_("Detailed View for %s (%s)"),$spotter_array[0]['airline_name'],$spotter_array[0]['airline_icao']);
			} else $title = '';
		}
		require_once('header.php');
	  
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/airline" method="post">';
		print '<select name="airline" class="selectpicker" data-live-search="true">';
		print '<option></option>';
		$alliances = $Spotter->getAllAllianceNames();
		if (!empty($alliances)) {
			foreach ($alliances as $al) {
				if ($alliance && str_replace('_',' ',str_replace('alliance_','',$airline)) == $al['alliance']) {
					print '<option value="alliance_'.str_replace(' ','_',$al['alliance']).'" selected>'.$al['alliance'].'</option>';
				} else {
					print '<option value="alliance_'.str_replace(' ','_',$al['alliance']).'">'.$al['alliance'].'</option>';
				}
			}
			print '<option disabled>───────────────</option>';
		}
		$Stats = new Stats();
		$airline_names = $Stats->getAllAirlineNames();
		if (empty($airline_names)) $airline_names = $Spotter->getAllAirlineNames();
		foreach($airline_names as $airline_name)
		{
			if($airline == $airline_name['airline_icao'])
			{
				print '<option value="'.$airline_name['airline_icao'].'" selected="selected">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
			} else {
				print '<option value="'.$airline_name['airline_icao'].'">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
			}
		}
		print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
		print '</div>';
		print '<br />';

		if ($airline != "NA")
		{
			if ($alliance === false) {
				if (!empty($airline_info)) {
					print '<div class="info column">';
					print '<h1>'.$airline_info[0]['name'].' ('.$airline_info[0]['icao'].')</h1>';
					if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$airline_info[0]['icao'].'.gif'))
					{
						print '<img src="'.$globalURL.'/images/airlines/'.$airline_info[0]['icao'].'.gif" alt="'.$airline_info[0]['name'].' ('.$airline_info[0]['icao'].')" title="'.$airline_info[0]['name'].' ('.$airline_info[0]['icao'].')" class="logo" />';
					} elseif (@getimagesize($globalURL.'/images/airlines/'.$airline_info[0]['icao'].'.png'))
					{
						print '<img src="'.$globalURL.'/images/airlines/'.$airline_info[0]['icao'].'.png" alt="'.$airline_info[0]['name'].' ('.$airline_info[0]['icao'].')" title="'.$airline_info[0]['name'].' ('.$airline_info[0]['icao'].')" class="logo" />';
					}
					print '<div><span class="label">'._("Name").'</span>'.$airline_info[0]['name'].'</div>';
					print '<div><span class="label">'._("Country").'</span>'.$airline_info[0]['country'].'</div>';
					print '<div><span class="label">'._("ICAO").'</span>'.$airline_info[0]['icao'].'</div>';
					if ($airline_info[0]['iata'] != '') print '<div><span class="label">'._("IATA").'</span>'.$airline_info[0]['iata'].'</div>';
					if ($airline_info[0]['callsign'] != '') print '<div><span class="label">'._("Callsign").'</span>'.$airline_info[0]['callsign'].'</div>'; 
					print '<div><span class="label">'._("Type").'</span>'.ucwords($airline_info[0]['type']).'</div>';
					if (isset($airline_info[0]['home_link']) && $airline_info[0]['home_link'] != '') print '<div><a href="'.$airline_info[0]['home_link'].'"><i class="fa fa-home"></i></a></div>';
					if (isset($airline_info[0]['wikipedia_link']) && $airline_info[0]['wikipedia_link'] != '') print '<div><a href="'.$airline_info[0]['wikipedia_link'].'"><i class="fa fa-wikipedia-w"></i></a></div>';
					if (isset($airline_info[0]['ban_eu']) && $airline_info[0]['ban_eu'] == 1) print '<div><img src="'.$globalURL.'/images/baneu.png" alt="'._("This airline is banned in Europe").'" title="'._("This airline is banned in Europe").'" /></div>';
					print '</div>';
				
				} else {
					print '<div class="info column">';
					print '<h1>'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')</h1>';
					if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif'))
					{
						print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
					} elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
					{
						print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
					}
					print '<div><span class="label">'._("Name").'</span>'.$spotter_array[0]['airline_name'].'</div>';
					print '<div><span class="label">'._("Country").'</span>'.$spotter_array[0]['airline_country'].'</div>';
					print '<div><span class="label">'._("ICAO").'</span>'.$spotter_array[0]['airline_icao'].'</div>';
					if (isset($spotter_array[0]['airline_iata']) && $spotter_array[0]['airline_iata'] != '') print '<div><span class="label">'._("IATA").'</span>'.$spotter_array[0]['airline_iata'].'</div>';
					if (isset($spotter_array[0]['airline_callsign']) && $spotter_array[0]['airline_callsign'] != '') print '<div><span class="label">'._("Callsign").'</span>'.$spotter_array[0]['airline_callsign'].'</div>'; 
					print '<div><span class="label">'._("Type").'</span>'.ucwords($spotter_array[0]['airline_type']).'</div>';
					if (isset($spotter_array[0]['ban_eu']) && $spotter_array[0]['ban_eu'] == 1) print '<div><img src="'.$globalURL.'/images/baneu.png" alt="'._("This airline is banned in Europe").'" title="'._("This airline is banned in Europe").'" /></div>';
					print '</div>';
				}
			} else {
				print '<div class="info column">';
				print '<h1>'.str_replace('_',' ',str_replace('alliance_','',$airline)).'</h1>';
				if (@getimagesize($globalURL.'/images/airlines/'.str_replace('alliance_','',$airline).'.png') || @getimagesize('images/airlines/'.str_replace('alliance_','',$airline).'.png'))
				{
					print '<img src="'.$globalURL.'/images/airlines/'.str_replace('alliance_','',$airline).'.png" alt="'.str_replace('_',' ',str_replace('alliance_','',$airline)).'" title="'.str_replace('_',' ',str_replace('alliance_','',$airline)).'" class="logo" />';
				}
				print '<div><span class="label">'._("Name").'</span>'.str_replace('_',' ',str_replace('alliance_','',$airline)).'</div>';
				print '</div>';
			}
		} else {
			print '<div class="alert alert-warning">'._("This special airline profile shows all flights that do <u>not</u> have a airline associated with them.").'</div>';
		}

		if (!empty($spotter_array)) {
			include('airline-sub-menu.php');
			print '<div class="table column">';
			if (isset($spotter_array[0]['airline_name']) && $alliance === false) {
				print '<p>'.sprintf(_("The table below shows the detailed information of all flights from <strong>%s</strong>."),$spotter_array[0]['airline_name']).'</p>';
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
			print '<p>'._("Sorry, no flights of this airline exist in this database.").'</p>'; 
		}
	} else {
		$title = _("Airline");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, the airline does not exist in this database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>