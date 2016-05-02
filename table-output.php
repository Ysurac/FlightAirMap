<?php
print '<div class="table-responsive">';
print '<table class="table-striped">';

// FIXME : Dirty Hacks
if (!isset($_GET['sort'])) 
{
	$_GET['sort'] = '';
}
if (!isset($showSpecial)) 
{
	$showSpecial = false;
}
if (!isset($hide_th_links)) 
{
	$hide_th_links = false;
}

if (strtolower($current_page) == "search")
{
	print '<thead>';
	print '<th class="aircraft_thumbnail"></th>';
	if ($_GET['sort'] == "airline_name_asc")
	{
		print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airline_name_desc")
	{
		 print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "ident_asc")
	{
		print '<th class="ident"><a href="'.$page_url.'&sort=ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "ident_desc")
	{
		print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "aircraft_asc")
	{
		print '<th class="type"><a href="'.$page_url.'&sort=aircraft_desc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "aircraft_desc")
	{
		print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc">'._("Aircraft").'</a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "airport_departure_asc")
	{
		print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_desc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airport_departure_desc")
	{
		print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "airport_arrival_asc")
	{
		print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_desc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airport_arrival_desc")
	{
		print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-sort small"></i></th>';
	}
	print '<th class="routestop"><span class="nomobile">'._("Route stop").'</span><span class="mobile">'._("Stop").'</span></a></th>';
	if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
		print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
	} else {
		print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
	}
	if ($_GET['sort'] == "date_asc")
	{
		print '<th class="time"><a href="'.$page_url.'&sort=date_desc" class="active">'._("Date").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "date_desc")
	{
		print '<th class="time"><a href="'.$page_url.'&sort=date_asc" class="active">'._("Date").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="time"><a href="'.$page_url.'&sort=date_asc">'._("Date").'</a> <i class="fa fa-sort small"></i></th>';
	}
	print '<th class="more"></th>';
	print '</thead>';
} else if (strtolower($current_page) == "upcoming"){
	print '<thead>';
	if ($_GET['sort'] == "airline_name_asc")
	{
		print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airline_name_desc")
	{
		print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "ident_asc")
	{
		print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "ident_desc")
	{
		print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "airport_departure_asc")
	{
		print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_desc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airport_departure_desc")
	{
		print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "airport_arrival_asc")
	{
		print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_desc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "airport_arrival_desc")
	{
		print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-sort small"></i></th>';
	}
	if ($_GET['sort'] == "date_asc")
	{
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Expected Time").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "date_desc")
	{
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Expected Time").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Expected Time").'</a> <i class="fa fa-sort small"></i></th>';
	}
	print '</thead>';
} else if (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive"){
	print '<thead>';
	print '<th class="aircraft_thumbnail"></th>';
	print '<th class="logo">'._("Airline").'</th>';
	print '<th class="ident">'._("Ident").'</th>';
	print '<th class="message">'._("Message").'</th>';
	print '<th class="time">'._("Date").'</th>';
	print '<th class="more"></th>';
	print '</thead>';
} else {

	if ($hide_th_links == true){
		print '<thead>';
		print '<th class="aircraft_thumbnail"></th>';
		if ($_GET['sort'] == "airline_name_asc")
		{
			print '<th class="logo">'._("Airline").'</th>';
		} else if ($_GET['sort'] == "airline_name_desc")
		{
			print '<th class="logo">'._("Airline").'</th>';
		} else {
			print '<th class="logo">'._("Airline").'</th>';
		}
		if ($_GET['sort'] == "ident_asc")
		{
			print '<th class="ident">'._("Ident").'</th>';
		} else if ($_GET['sort'] == "ident_desc")
		{
			print '<th class="ident">'._("Ident").'</th>';
		} else {
			print '<th class="ident">'._("Ident").'</th>';
		}
		if ($_GET['sort'] == "aircraft_asc")
		{
			print '<th class="type">'._("Aircraft").'</th>';
		} else if ($_GET['sort'] == "aircraft_desc")
		{
			print '<th class="type">'._("Aircraft").'</th>';
		} else {
			print '<th class="type">'._("Aircraft").'</th>';
		}
		if ($_GET['sort'] == "airport_departure_asc")
		{
			print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
		} else if ($_GET['sort'] == "airport_departure_desc")
		{
			print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
		} else {
			print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
		}
		if ($_GET['sort'] == "airport_arrival_asc")
		{
			print '<th class="arrival"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></th>';
		} else if ($_GET['sort'] == "airport_arrival_desc")
		{
			print '<th class="arrival"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></th>';
		} else {
			print '<th class="arrival"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></th>';
		}                                               
		print '<th class="route"><span class="nomobile">'._("Route").'</span><span class="mobile">'._("Route").'</span></th>';
		if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
			print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
		} else {
			print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
		}
		
		if (strtolower($current_page) == "date")
		{
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time">'._("Time").'</th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time">'._("Time").'</th>';
			} else {
				print '<th class="time">'._("Time").'</th>';
			}
		} elseif (strtolower($current_page) == "currently") {
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time">'._("Date first seen").'</th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time">'._("Date first seen").'</th>';
			} else {
				print '<th class="time">'._("Date first seen").'</th>';
			}
		} else {
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time">'._("Date").'</th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time">'._("Date").'</th>';
			} else {
				print '<th class="time">'._("Date").'</th>';
			}
		}
		print '<th class="more"></th>';
		print '</thead>';
	} else {
		print '<thead>';
		print '<th class="aircraft_thumbnail"></th>';
		if ($_GET['sort'] == "airline_name_asc")
		{
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "airline_name_desc")
		{
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
		}
		if ($_GET['sort'] == "ident_asc")
		{
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "ident_desc")
		{
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
		}
		if ($_GET['sort'] == "aircraft_asc")
		{
			print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_desc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "aircraft_desc")
		{
			print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc">'._("Aircraft").'</a> <i class="fa fa-sort small"></i></th>';
		}
		if ($_GET['sort'] == "airport_departure_asc")
		{
			print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_desc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "airport_departure_desc")
		{
			print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-sort small"></i></th>';
		}
		if ($_GET['sort'] == "airport_arrival_asc")
		{
			print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_desc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "airport_arrival_desc")
		{
			print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc" class="active"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc"><span class="nomobile">'._("Flying to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-sort small"></i></th>';
		}
		print '<th class="routestop"><span class="nomobile">'._("Route stop").'</span><span class="mobile">Stop</span></a></th>';
		if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
			print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
		} else {
			print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
		}
		if (strtolower($current_page) == "date")
		{
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Time").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Time").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Time").'</a> <i class="fa fa-sort small"></i></th>';
			}
		} elseif (strtolower($current_page) == "currently") {
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Date first seen").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Date first seen").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Date first seen").'</a> <i class="fa fa-sort small"></i></th>';
			}
		} else {
			if ($_GET['sort'] == "date_asc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Date").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Date").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Date").'</a> <i class="fa fa-sort small"></i></th>';
			}
		}
		print '<th class="more"></th>';
		print '</thead>';
	}
}

print '<tbody>'."\n";
foreach($spotter_array as $spotter_item)
{
	if (isset($globalTimezone))
	{
		date_default_timezone_set($globalTimezone);
	} else date_default_timezone_set('UTC');
	if ($showSpecial == true)
	{
		print '<tr class="special">'."\n";
		print '<td colspan="9"><h4>'.$spotter_item['registration'].' - '.$spotter_item['highlight'].'</h4></td>'."\n";
		print '</tr>'."\n";
	}
	if (strtolower($current_page) == "upcoming" && date("ga") == date("ga", strtotime($spotter_item['date_iso_8601'])))
	{
		print '<tr class="currentHour">';
	} else {
		print '<tr>';
	}
	if (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive" || strtolower($current_page) == "currently") {
		if ($spotter_item['image_thumbnail'] != "")
		{
			print '<td class="aircraft_thumbnail">'."\n";
			
			if ($spotter_item['image_source'] == 'planespotters') {
				if ($spotter_item['image_source_website'] != '') $image_src = $spotter_item['image_source_website'];
				else {
					$planespotter_url_array = explode("_", $spotter_item['image']);
					$planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
                                        $image_src = 'https://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id;
				}
				if (isset($spotter_item['airline_name'])) {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Airline:").' '.$spotter_item['airline_name'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Airline:").' '._("Not available").'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
                        } else {
                    		if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
                    		$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
                    		} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				if (isset($spotter_item['airline_name'])) {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Airline:").' '.$spotter_item['airline_name'].'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Airline:").' '._("Not available").'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
			}
			print '</td>'."\n";
		} else {
			print '<td class="aircraft_thumbnail">'."\n";
			print '<img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Airline:").' '._("Not available").'" data-html="true" width="100px" />'."\n";
			print '</td>'."\n";
		}
	}
	if(strtolower($current_page) != "currently" && strtolower($current_page) != "upcoming" && strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive"){
		if (!isset($spotter_item['squawk']) || $spotter_item['squawk'] == 0) {
		    $spotter_item['squawk'] = '-';
		}
		if ($spotter_item['image_thumbnail'] != "")
		{
			print '<td class="aircraft_thumbnail">'."\n";
			//print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$spotter_item['image_thumbnail'].'" alt="Click to see more information about this flight" title="Click to see more information about this flight" width="100px" /></a>';
			if ($spotter_item['image_source'] == 'planespotters') {
				if ($spotter_item['image_source_website'] != '') $image_src = $spotter_item['image_source_website'];
				else {
					$planespotter_url_array = explode("_", $spotter_array[0]['image']);
					$planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
                                        $image_src = 'https://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id;
				}
				if (!isset($spotter_item['airline_name']) && isset($spotter_item['aircraft_name'])) {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '._("Not available").'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} elseif (!isset($spotter_item['aircraft_name']) && isset($spotter_item['airline_name'])) {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} elseif (!isset($spotter_item['aircraft_name']) && !isset($spotter_item['airline_name'])) {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '._("Not available").'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<a href="'.$image_src.'"><img src="'.preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']).'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
			} else {
                    		if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
                    		$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
                    		} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				if (!isset($spotter_item['airline_name']) && isset($spotter_item['aircraft_name'])) {
					print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '._("Not available").'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} elseif (!isset($spotter_item['aircraft_name']) && isset($spotter_item['airline_name'])) {
					print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} elseif (!isset($spotter_item['aircraft_name']) && !isset($spotter_item['airline_name'])) {
					print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - Not available" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - Not available" data-content="Registration: '.$spotter_item['registration'].'<br />Aircraft: ('.$spotter_item['aircraft_type'].')<br />Airline: Not available<br />Squawk: '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
			}
			print '</td>'."\n";
		} else {
			print '<td class="aircraft_thumbnail">'."\n";
       //   	 	print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" alt="Click to see more information about this flight" title="Click to see more information about this flight" width="100px" /></a>';
	//}
			if (!isset($spotter_item['airline_name']) && !isset($spotter_item['aircraft_name'])) {
				print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '._("Not available").' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '._("Not available").'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n";
			} elseif (!isset($spotter_item['aircraft_name'])) {
				print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '._("Not available").' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n";
			} elseif (!isset($spotter_item['airline_name'])) {
				print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '._("Not available").'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '._("Not available").'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n";
			} else {
				print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" alt="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_type'].' - '.$spotter_item['airline_name'].'" data-content="'._("Registration:").' '.$spotter_item['registration'].'<br />'._("Aircraft:").' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')<br />'._("Airline:").' '.$spotter_item['airline_name'].'<br />'._("Squawk:").' '.$spotter_item['squawk'].'" data-html="true" width="100px" /></a>'."\n";
			}
			print '</td>'."\n";
		}
	}
	if (isset($globalIVAO) && $globalIVAO && (@getimagesize('images/airlines/'.$spotter_item['airline_icao'].'.gif') || @getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif')))
	{
		print '<td class="logo">'."\n";
		print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif" alt="'._("Click to see airline information").'" title="'._("Click to see airline information").'" /></a>'."\n";
		print '</td>'."\n";
	} elseif ((!isset($globalIVAO) || !$globalIVAO) && (@getimagesize('images/airlines/'.$spotter_item['airline_icao'].'.png') || @getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png')))
	{
		print '<td class="logo">'."\n";
		print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png" alt="'._("Click to see airline information").'" title="'._("Click to see airline information").'" /></a>'."\n";
		print '</td>'."\n";
	} else {
		print '<td class="logo-no-image">'."\n";
		if (isset($spotter_item['airline_icao']) && $spotter_item['airline_icao'] != 'NA')
		{
			print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'">'.$spotter_item['airline_name'].'</a>'."\n";
		} else {
			print '<a href="'.$globalURL.'/airline/NA">'._("Not Available").'</a>'."\n";
		}
		print '</td>'."\n";
	}
	// Aircraft ident
	print '<td class="ident">'."\n";
	if ($spotter_item['ident'] != "")
	{
		print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>'."\n";
	} else {
		print 'N/A'."\n";
	}
	print '</td>'."\n";
	// Aircraft type
	if(strtolower($current_page) != "upcoming" && strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive"){
		print '<td class="type">'."\n";
		if (!isset($spotter_item['aircraft_name'])) {
			print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'._("Not available").'</a></span>'."\n";
		} else {
			print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'].'</a></span>'."\n";
		}
		print '<span class="mobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a></span>'."\n";
		print '</td>'."\n";
	}
	if (strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive") {
	// Departure Airport
	print '<td class="departure_airport">'."\n";
	if (!isset($spotter_item['departure_airport']) || !isset($spotter_item['departure_airport_city']) || (isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] == 'NA')) {
		print '<span class="nomobile"><a href="'.$globalURL.'/airport/NA">'._("Not available").'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/airport/NA">'._("Not available").'</a></span>'."\n";
	} else {
		print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport'].'</a></span>'."\n";
	}
	if (isset($spotter_item['departure_airport_time']) && isset($spotter_item['real_departure_airport_time'])) {
		if ($spotter_item['departure_airport_time'] > 2460) {
			$departure_airport_time = date('H:m',$spotter_item['departure_airport_time']);
		} else $departure_airport_time = substr($spotter_item['departure_airport_time'],0,-2).':'.substr($spotter_item['departure_airport_time'],-2);
		if ($spotter_item['real_departure_airport_time'] > 2460) {
			$real_departure_airport_time = date('H:m',$spotter_item['real_departure_airport_time']);
		} else $real_departure_airport_time = $spotter_item['real_departure_airport_time'];
		print '<br /><span class="airport_time">'.$departure_airport_time.' ('.$real_departure_airport_time.')</span>'."\n";
	} elseif (isset($spotter_item['real_departure_airport_time'])) {
		if ($spotter_item['real_departure_airport_time'] > 2460) {
			$real_departure_airport_time = date('H:m',$spotter_item['real_departure_airport_time']);
		} else $real_departure_airport_time = $spotter_item['real_departure_airport_time'];
		print '<br /><span class="airport_time">'.$real_departure_airport_time.'</span>'."\n";
	} elseif (isset($spotter_item['departure_airport_time'])) {
		if ($spotter_item['departure_airport_time'] > 2460) {
			$departure_airport_time = date('H:m',$spotter_item['departure_airport_time']);
		} else {
			$departure_airport_time = substr($spotter_item['departure_airport_time'],0,-2).':'.substr($spotter_item['departure_airport_time'],-2);
		}
		print '<br /><span class="airport_time">'.$departure_airport_time.'</span>'."\n";
	}
	if ($spotter_item['departure_airport'] != 'NA') {
		require_once(dirname(__FILE__).'/require/class.Spotter.php');
		$Spotter = new Spotter();
		$distance = $Spotter->getAirportDistance($spotter_item['departure_airport'],$spotter_item['latitude'],$spotter_item['longitude']);
		if ($distance != '') {
		    if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
			    echo '<br/><i>'.round($distance*0.539957).' nm</i>';
		    } elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
			    echo '<br/><i>'.round($distance*0.621371).' mi</i>';
		    } elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
			    echo '<br/><i>'.$distance.' km</i>';
		    }
		}
	}
	print '</td>'."\n";
	// Arrival Airport
	print '<td class="arrival_airport">'."\n";
	if (!isset($spotter_item['arrival_airport']) || !isset($spotter_item['arrival_airport_city'])) {
		if (isset($spotter_item['real_arrival_airport']) && $spotter_item['real_arrival_airport'] != 'NA') {
			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$spotter_item['real_arrival_airport'].'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$spotter_item['real_arrival_airport'].'</a></span>'."\n";
		} else {
			print '<span class="nomobile"><a href="'.$globalURL.'/airport/NA">'._("Not available").'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/airport/NA">'._("Not available").'</a></span>'."\n";
		}
	} else {
		if (isset($spotter_item['real_arrival_airport']) && $spotter_item['real_arrival_airport'] != $spotter_item['arrival_airport']) {
			print '<span class="nomobile">Scheduled : <a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a></span>'."\n";
			if (!isset($Spotter)) $Spotter = new Spotter();
			$arrival_airport_info = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
			print '<br /><span class="nomobile">'._("Real:").' <a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$arrival_airport_info[0]['city'].','.$arrival_airport_info[0]['country'].' ('.$spotter_item['real_arrival_airport'].')</a></span>'."\n";
			print '<span class="mobile">'._("Scheduled:").' <a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$spotter_item['real_arrival_airport'].'</a></span>'."\n";
			print '<span class="mobile">'._("Real:").' <a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$arrival_airport_info[0]['city'].','.$arrival_airport_info[0]['country'].' ('.$spotter_item['real_arrival_airport'].')</a></span>'."\n";
		} elseif ($spotter_item['arrival_airport'] != 'NA') {
			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport'].'</a></span>'."\n";
		} else {
			print '<span class="nomobile"><a href="'.$globalURL.'/airport/NA">'._("Not Available").'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/airport/NA">'._("Not Available").'</a></span>'."\n";
		}
	}
	if (isset($spotter_item['arrival_airport_time']) && isset($spotter_item['real_arrival_airport_time'])) {
		if ($spotter_item['arrival_airport_time'] > 2460) {
			$arrival_airport_time = date('H:m',$spotter_item['arrival_airport_time']);
		} else $arrival_airport_time = $spotter_item['arrival_airport_time'];
		if ($spotter_item['real_arrival_airport_time'] > 2460) {
			$real_arrival_airport_time = date('H:m',$spotter_item['real_arrival_airport_time']);
		} else $real_arrival_airport_time = $spotter_item['real_arrival_airport_time'];
		print '<br /><span class="airport_time">'.$spotter_item['arrival_airport_time'].' ('.$spotter_item['real_arrival_airport_time'].')</span>'."\n";
	} elseif (isset($spotter_item['real_arrival_airport_time'])) {
		if ($spotter_item['real_arrival_airport_time'] > 2460) {
			$real_arrival_airport_time = date('H:m',$spotter_item['real_arrival_airport_time']);
		} else $real_arrival_airport_time = $spotter_item['real_arrival_airport_time'];
		print '<br /><span class="airport_time">'.$real_arrival_airport_time.'</span>'."\n";
	} elseif (isset($spotter_item['arrival_airport_time'])) {
		if ($spotter_item['arrival_airport_time'] > 2460) {
			$arrival_airport_time = date('H:m',$spotter_item['arrival_airport_time']);
		} else $arrival_airport_time = $spotter_item['arrival_airport_time'];
		print '<br /><span class="airport_time">'.$arrival_airport_time.'</span>'."\n";
	}
	if (!isset($spotter_item['real_arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
		$distance = $Spotter->getAirportDistance($spotter_item['arrival_airport'],$spotter_item['latitude'],$spotter_item['longitude']);
		if ($distance != '') {
		    if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
			    echo '<br/><i>'.round($distance*0.539957).' nm</i>';
		    } elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
			    echo '<br/><i>'.round($distance*0.621371).' mi</i>';
		    } elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
			    echo '<br/><i>'.$distance.' km</i>';
		    }
		}
	}
	print '</td>'."\n";
	// Route stop
	if(strtolower($current_page) != "upcoming"){
		print '<td class="route_stop">'."\n";
		if (!isset($spotter_item['route_stop']) || $spotter_item['route_stop'] == '') {
			print '<span class="nomobile">-</span>'."\n";
			print '<span class="mobile">-</span>'."\n";
		} else {
			foreach ($spotter_item['route_stop_details'] as $rst) {
				print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$rst['airport_icao'].'">'.$rst['airport_city'].', '.$rst['airport_country'].' ('.$rst['airport_icao'].')</a></span>'."\n";
				print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$rst['airport_icao'].'">'.$rst['airport_icao'].'</a></span><br />'."\n";
			}
		}
		print '</td>'."\n";
	}
	if(strtolower($current_page) != "upcoming"){
		if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
			print '<td class="pilot">'."\n";
			if ((!isset($spotter_item['pilot_id']) || $spotter_item['pilot_id'] == '') && (!isset($spotter_item['pilot_name']) || $spotter_item['pilot_name'] == '')) {
				print '<span class="nomobile">-</span>'."\n";
				print '<span class="mobile">-</span>'."\n";
			} elseif ((!isset($spotter_item['pilot_id']) || $spotter_item['pilot_id'] == '') && (isset($spotter_item['pilot_name']) && $spotter_item['pilot_name'] != '')) {
				print '<span class="nomobile">'.$spotter_item['pilot_name'].'</span>'."\n";
				print '<span class="mobile">'.$spotter_item['pilot_name'].'-</span>'."\n";
			} else {
				if (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'whazzup') {
					print '<span class="nomobile"><a href="https://www.ivao.aero/Member.aspx?ID='.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a></span>'."\n";
					print '<span class="mobile"><a href="https://www.ivao.aero/Member.aspx?ID='.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a></span>'."\n";
				} else {
					print '<span class="nomobile">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</span>'."\n";
					print '<span class="mobile">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</span>'."\n";
				}
			}
			print '</td>'."\n";
		} else {
			print '<td class="owner">'."\n";
			if (!isset($spotter_item['aircraft_owner']) || $spotter_item['aircraft_owner'] == '') {
				print '<span class="nomobile">-</span>'."\n";
				print '<span class="mobile">-</span>'."\n";
			} else {
				print '<span class="nomobile">'.$spotter_item['aircraft_owner'].'</span>'."\n";
				print '<span class="mobile">'.$spotter_item['aircraft_owner'].'</span>'."\n";
			}
			print '</td>'."\n";
		
		}
	}

	}
	if (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive") {
		if (isset($spotter_item['decode']) && $spotter_item['decode'] != '') {
			print '<td class="message"><p>'."\n";
			print str_replace(array("\r\n", "\n", "\r"),'<br />',$spotter_item['message']);
			print '</p><p class="decode">';
			$decode_array = json_decode($spotter_item['decode']);
			foreach ($decode_array as $key => $value) {
				print '<b>'.$key.'</b> : '.$value.' ';
			}
			print '</p>';
			print '</td>'."\n";
		} else {
			print '<td class="message">'."\n";
			print str_replace(array("\r\n", "\n", "\r"),'<br />',$spotter_item['message']);
			print '</td>'."\n";
		}
	}
	// Date
	if (strtolower($current_page) == "date")
	{
		print '<td class="time">'."\n";
		print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
		print '</td>'."\n";
	} else if (strtolower($current_page) == "index")
	{
		print '<td class="time">'."\n";
		print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>'."\n";
		print '</td>'."\n";
	} else if (strtolower($current_page) == "upcoming")
	{
		print '<td class="time">';
		//print '<span>'.date("ga", strtotime($spotter_item['date_iso_8601'])).'</span>';
		print '<span>'.date("g:i a", strtotime($spotter_item['date_iso_8601'])).'</span>';
		print '</td>';
	} elseif (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive")
	{
		print '<td class="date">'."\n";
		print '<span class="nomobile">'.date("r", strtotime($spotter_item['date'])).'</span>'."\n";
		print '<span class="mobile">'.date("j/n/Y g:i a", strtotime($spotter_item['date'])).'</span>'."\n";
		print '</td>'."\n";
	} else {
		print '<td class="date">'."\n";
		print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("r", $spotter_item['date_unix']).'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
		print '</td>'."\n";
	}
	if (strtolower($current_page) != "upcoming")
	{
		print '<td class="more">';
		print '<ul class="nav nav-pills">';
		print '<li class="dropdown">';
		print '<a class="dropdown-toggle " data-toggle="dropdown" href="#"><span class="caret"></span></a>';
		print '<ul class="dropdown-menu pull-right" role="menu">';
		if (isset($spotter_item['spotter_id'])) {
			print '<li><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'._("Detailed Flight Information").'</a></li>';
			print '<li><a href="'.$globalURL.'/search/xml?q='.$spotter_item['spotter_id'].'&download=true"><i class="fa fa-download"></i>'._("Download Flight Data").' (XML)</a></li>';
			print '<li><hr /></li>';
		}
		if (isset($spotter_item['registration']) && $spotter_item['registration'] != "")
		{
			print '<li><a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'">'._("Aircraft History").' ('.$spotter_item['registration'].')</a></li>';
		}
		if (isset($spotter_item['aircraft_manufacturer']) && $spotter_item['aircraft_manufacturer'] != "")
		{
			print '<li><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_item['aircraft_manufacturer'])).'">'._("Manufacturer Profile").'</a></li>';
		}
		if (isset($spotter_item['aircraft_type']) && $spotter_item['aircraft_type'] != "" && isset($spotter_item['airline_icao']) && $spotter_item['airline_icao'] != "")
		{
			print '<li><a href="'.$globalURL.'/search?aircraft='.$spotter_item['aircraft_type'].'&airline='.$spotter_item['airline_icao'].'">'._("Flights of Aircraft Type &amp; Airline").'</a></li>';
			print '<li><hr /></li>';
		}
		if (isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != "" && $spotter_item['arrival_airport'] != "")
		{
			print '<li><a href="'.$globalURL.'/route/'.$spotter_item['departure_airport'].'/'.$spotter_item['arrival_airport'].'">'._("Route Profile").'</a></li>';
		}
		if (isset($spotter_item['airline_country']) && $spotter_item['airline_country'] != "")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['airline_country'])).'">'._("Airline Country Profile").'</a></li>';
			print '<li><hr /></li>';
		}
		if (isset($spotter_item['departure_airport_country']) && $spotter_item['departure_airport_country'] != "")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['departure_airport_country'])).'">'._("Departure Airport Country Profile").'</a></li>';
		}
		if (isset($spotter_item['arrival_airport_country']) && $spotter_item['arrival_airport_country'] != "")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['arrival_airport_country'])).'">'._("Arrival Airport Country Profile").'</a></li>';
		}
		print '</ul>';
		print '</li>';
		print '</ul>';
		print '</td>';
	}
	print '</tr>'."\n";
}
print '<tbody>'."\n";
print '</table>'."\n";
print '</div>'."\n";
?>