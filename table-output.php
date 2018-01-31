<?php
print '<div class="table-responsive">';
print '<table class="table-striped">';

require_once(dirname(__FILE__).'/require/class.Common.php');
$Common = new Common();
$showRouteStop = $Common->multiKeyExists($spotter_array,'route_stop');
if (isset($globalVM) && $globalVM && isset($type) && $type == 'marine') {
	$showDuration = $Common->multiKeyExists($spotter_array,'race_time');
	if ($showDuration === false) $showDuration = $Common->multiKeyExists($spotter_array,'duration');
} else {
	$showDuration = $Common->multiKeyExists($spotter_array,'duration');
}
if (isset($globalVM) && $globalVM && isset($type) && $type == 'marine') {
	$showDistance = $Common->multiKeyExists($spotter_array,'distance');
}


if (!isset($type)) $type = 'aircraft';

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
	if ($type == 'marine' && isset($globalVM) && $globalVM) {
		print '<th class="rank">'._("Rank").'</th>';
	}
	print '<th class="aircraft_thumbnail"></th>';
	if ($type == 'aircraft') {
		if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
			if ($_GET['sort'] == "airline_name_asc")
			{
				print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "airline_name_desc")
			{
				 print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
			}
		}
	}
	if (!isset($globalNoIdents) || $globalNoIdents === FALSE) {
		if ($_GET['sort'] == "ident_asc")
		{
			print '<th class="ident"><a href="'.$page_url.'&sort=ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "ident_desc")
		{
			print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
		}
	}
	if ($type == 'aircraft') {
		if ($_GET['sort'] == "aircraft_asc")
		{
			print '<th class="type"><a href="'.$page_url.'&sort=aircraft_desc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "aircraft_desc")
		{
			print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc">'._("Aircraft").'</a> <i class="fa fa-sort small"></i></th>';
		}
	} elseif ($type == 'marine') {
		if ($_GET['sort'] == "type_asc")
		{
			print '<th class="type">'._("Type").'</th>';
		} else if ($_GET['sort'] == "type_desc")
		{
			print '<th class="type">'._("Type").'</th>';
		} else {
			print '<th class="type">'._("Type").'</th>';
		}
	} elseif ($type == 'tracker') {
		if ($_GET['sort'] == "type_asc")
		{
			print '<th class="type">'._("Type").'</th>';
		} else if ($_GET['sort'] == "type_desc")
		{
			print '<th class="type">'._("Type").'</th>';
		} else {
			print '<th class="type">'._("Type").'</th>';
		}
	}
	if ($type == 'aircraft') {
		if ($_GET['sort'] == "airport_departure_asc")
		{
			print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_desc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "airport_departure_desc")
		{
			print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-sort small"></i></th>';
		}
	}
	if ($type == 'aircraft' || $type == 'marine') {
		if (isset($globalVM) && $globalVM && $type == 'marine') {
			if ($_GET['sort'] == "race_asc")
			{
				print '<th class="arrival"><a href="'.$page_url.'&sort=race_desc" class="active">'._("Races").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "race_desc")
			{
				print '<th class="arrival"><a href="'.$page_url.'&sort=race_asc" class="active">'._("Races").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="arrival"><a href="'.$page_url.'&sort=race_asc">'._("Races").'</a> <i class="fa fa-sort small"></i></th>';
			}
			print '<th class="status">'._("Status").'</th>';
		} else {
			if ($_GET['sort'] == "airport_arrival_asc")
			{
				print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_desc" class="active"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "airport_arrival_desc")
			{
				print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc" class="active"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-sort small"></i></th>';
			}
		}
	}
	if ($type == 'aircraft') {
		if ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalVAM) && $globalVAM) || (isset($globalphpVMS) && $globalphpVMS)) {
			print '<th class="routestop"><span class="nomobile">'._("Route stop").'</span><span class="mobile">'._("Stop").'</span></a></th>';
		}
		if (isset($_GET['dist']) && $_GET['dist'] != '') {
			if ($_GET['sort'] == "distance_asc")
			{
				print '<th class="distance"><a href="'.$page_url.'&sort=distance_desc" class="active"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-caret-up"></i></th>';
			} elseif ($_GET['sort'] == "distance_desc")
			{
				print '<th class="distance"><a href="'.$page_url.'&sort=distance_asc" class="active"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="distance"><a href="'.$page_url.'&sort=distance_desc" class="active"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-sort small"></i></th>';
			}
		}
		if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {
			print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
		}
		if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
			print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
		}
	}
	if ($type == 'marine' && isset($globalVM) && $globalVM) {
		if ($_GET['sort'] == "distance_asc")
		{
			print '<th class="distance"><a href="'.$page_url.'&sort=distance_desc" class="active"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-caret-up"></i></th>';
		} elseif ($_GET['sort'] == "distance_desc")
		{
			print '<th class="distance"><a href="'.$page_url.'&sort=distance_asc" class="active"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="distance"><a href="'.$page_url.'&sort=distance_desc"><span class="nomobile">'._("Distance").'</span><span class="mobile">'._("Distance").'</span></a> <i class="fa fa-sort small"></i></th>';
		}
		print '<th class="captain"><span class="nomobile">'._("Captain name").'</span><span class="mobile">'._("Captain").'</span></a></th>';
		print '<th class="duration"><span class="nomobile">'._("Race duration").'</span><span class="mobile">'._("Race duration").'</span></th>';
	}

	if ($type == 'tracker') {
		print '<th class="comment"><span class="nomobile">'._("Comment").'</span><span class="mobile">'._("Comment").'</span></th>';
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
	if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
		if ($_GET['sort'] == "airline_name_asc")
		{
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "airline_name_desc")
		{
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
		}
	}
	if (!isset($globalNoIdents) || $globalNoIdents === FALSE) {
		if ($_GET['sort'] == "ident_asc")
		{
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
		} else if ($_GET['sort'] == "ident_desc")
		{
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
		} else {
			print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
		}
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
	/*
	if ($_GET['sort'] == "date_asc")
	{
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Expected Time").'</a> <i class="fa fa-caret-up"></i></th>';
	} else if ($_GET['sort'] == "date_desc")
	{
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Expected Time").'</a> <i class="fa fa-caret-down"></i></th>';
	} else {
		print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Expected Time").'</a> <i class="fa fa-sort small"></i></th>';
	}
	*/
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
} else if (strtolower($current_page) == "accident-latest" || strtolower($current_page) == "accident-detailed") {
	print '<thead>';
	print '<th class="aircraft_thumbnail"></th>';
	print '<th class="logo">'._("Airline").'</th>';
	print '<th class="ident">'._("Ident").'</th>';
	print '<th class="type">'._("Aircraft").'</th>';
	print '<th class="owner">'._("Owner").'</th>';
//	print '<th class="acctype">'._("Type").'</th>';
	print '<th class="fatalities">'._("Fatalities").'</th>';
	print '<th class="message">'._("Message").'</th>';
	print '<th class="time">'._("Date").'</th>';
	print '<th class="more"></th>';
	print '</thead>';
} else if (strtolower($current_page) == "incident-latest" || strtolower($current_page) == "incident-detailed") {
	print '<thead>';
	print '<th class="aircraft_thumbnail"></th>';
	print '<th class="logo">'._("Airline").'</th>';
	print '<th class="ident">'._("Ident").'</th>';
	print '<th class="type">'._("Aircraft").'</th>';
	print '<th class="owner">'._("Owner").'</th>';
//	print '<th class="acctype">'._("Type").'</th>';
//	print '<th class="fatalities">'._("Fatalities").'</th>';
	print '<th class="message">'._("Message").'</th>';
	print '<th class="time">'._("Date").'</th>';
	print '<th class="more"></th>';
	print '</thead>';
} else {

	if ($hide_th_links === true){
		print '<thead>';
		if ($type == 'marine' && isset($globalVM) && $globalVM) {
			print '<th class="rank">'._("Rank").'</th>';
		}
		print '<th class="aircraft_thumbnail"></th>';
		if ($type == 'aircraft') {
			if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
				if ($_GET['sort'] == "airline_name_asc")
				{
					print '<th class="logo">'._("Airline").'</th>';
				} else if ($_GET['sort'] == "airline_name_desc")
				{
					print '<th class="logo">'._("Airline").'</th>';
				} else {
					print '<th class="logo">'._("Airline").'</th>';
				}
			}
		}
		if (!isset($globalNoIdents) || $globalNoIdents === FALSE) {
			if ($_GET['sort'] == "ident_asc")
			{
				print '<th class="ident">'._("Ident").'</th>';
			} else if ($_GET['sort'] == "ident_desc")
			{
				print '<th class="ident">'._("Ident").'</th>';
			} else {
				print '<th class="ident">'._("Ident").'</th>';
			}
		}
		if ($type == 'aircraft') {
			if ($_GET['sort'] == "aircraft_asc")
			{
				print '<th class="type">'._("Aircraft").'</th>';
			} else if ($_GET['sort'] == "aircraft_desc")
			{
				print '<th class="type">'._("Aircraft").'</th>';
			} else {
				print '<th class="type">'._("Aircraft").'</th>';
			}
		} elseif ($type == 'marine') {
			if ($_GET['sort'] == "type_asc")
			{
				print '<th class="type">'._("Type").'</th>';
			} else if ($_GET['sort'] == "type_desc")
			{
				print '<th class="type">'._("Type").'</th>';
			} else {
				print '<th class="type">'._("Type").'</th>';
			}
		} elseif ($type == 'tracker') {
			if ($_GET['sort'] == "type_asc")
			{
				print '<th class="type">'._("Type").'</th>';
			} else if ($_GET['sort'] == "type_desc")
			{
				print '<th class="type">'._("Type").'</th>';
			} else {
				print '<th class="type">'._("Type").'</th>';
			}
		}
		if ($type == 'aircraft') {
			if ($_GET['sort'] == "airport_departure_asc")
			{
				print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
			} else if ($_GET['sort'] == "airport_departure_desc")
			{
				print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
			} else {
				print '<th class="departure"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></th>';
			}
		}
		if ($type == 'aircraft' || $type == 'marine') {
			if (isset($globalVM) && $globalVM && $type == 'marine') {
				if ($_GET['sort'] == "race_asc")
				{
					print '<th class="arrival">'._("Race").''._("To").'</th>';
				} else if ($_GET['sort'] == "race_desc")
				{
					print '<th class="arrival"><'._("Race").'</th>';
				} else {
					print '<th class="arrival">'._("Race").'</th>';
				}
				print '<th class="status">'._("Status").'</th>';
			} else {
				if ($_GET['sort'] == "airport_arrival_asc")
				{
					print '<th class="arrival"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></th>';
				} else if ($_GET['sort'] == "airport_arrival_desc")
				{
					print '<th class="arrival"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></th>';
				} else {
					print '<th class="arrival"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></th>';
				}
			}
		}
    		if ($type == 'aircraft') {
			if ((isset($globalUsePilot) && $globalUsePilot) || (!isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalVAM) && $globalVAM) || (isset($globalphpVMS) && $globalphpVMS)))) {
				print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
			}
			if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
				print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
			}
			if ($showRouteStop) {
				print '<th class="route"><span class="nomobile">'._("Route").'</span><span class="mobile">'._("Route").'</span></th>';
			}
		}
		if ($type == 'marine' && isset($globalVM) && $globalVM && $showDistance) {
			if ($_GET['sort'] == "distance_asc")
			{
				print '<th class="distance">'._("Distance").'</th>';
			} elseif ($_GET['sort'] == "distance_desc")
			{
				print '<th class="distance">'._("Distance").'</th>';
			} else {
				print '<th class="distance">'._("Distance").'</th>';
			}
			print '<th class="captain"><span class="nomobile">'._("Captain name").'</span><span class="mobile">'._("Captain").'</span></a></th>';
		}
		if ($type == 'tracker') {
			print '<th class="comment"><span class="nomobile">'._("Comment").'</span><span class="mobile">'._("Comment").'</span></th>';
		}
		if ($showDuration && $type == 'marine' && isset($globalVM) && $globalVM === TRUE) {
			print '<th class="duration"><span class="nomobile">'._("Race duration").'</span><span class="mobile">'._("Race duration").'</span></th>';
		} elseif ($showDuration && strtolower($current_page) != "currently") {
			print '<th class="duration"><span class="nomobile">'._("Spotted duration").'</span><span class="mobile">'._("Duration").'</span></th>';
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
				print '<th class="time">'._("Date last seen").'</th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time">'._("Date last seen").'</th>';
			} else {
				print '<th class="time">'._("Date last seen").'</th>';
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
		if ($type == 'marine' && isset($globalVM) && $globalVM) {
			print '<th class="rank">'._("Rank").'</th>';
		}
		print '<th class="aircraft_thumbnail"></th>';
		if ($type == 'aircraft') {
			if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
				if ($_GET['sort'] == "airline_name_asc")
				{
					print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_desc" class="active">'._("Airline").'</a> <i class="fa fa-caret-up"></i></th>';
				} else if ($_GET['sort'] == "airline_name_desc")
				{
					print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc" class="active">'._("Airline").'</a> <i class="fa fa-caret-down"></i></th>';
				} else {
					print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc">'._("Airline").'</a> <i class="fa fa-sort small"></i></th>';
				}
			}
		}
		if (!isset($globalNoIdents) || $globalNoIdents === FALSE) {
			if ($_GET['sort'] == "ident_asc")
			{
				print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_desc" class="active">'._("Ident").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "ident_desc")
			{
				print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc" class="active">'._("Ident").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc">'._("Ident").'</a> <i class="fa fa-sort small"></i></th>';
			}
		}
		if ($type == 'aircraft') {
			if ($_GET['sort'] == "aircraft_asc")
			{
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_desc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "aircraft_desc")
			{
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc" class="active">'._("Aircraft").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc">'._("Aircraft").'</a> <i class="fa fa-sort small"></i></th>';
			}
		} elseif ($type == 'marine' || $type == 'tracker') {
			if ($_GET['sort'] == "type_asc")
			{
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/type_desc" class="active">'._("Type").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "type_desc")
			{
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/type_asc" class="active">'._("Type").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/type_asc">'._("Type").'</a> <i class="fa fa-sort small"></i></th>';
			}
		}
		if ($type == 'aircraft') {
			if ($_GET['sort'] == "airport_departure_asc")
			{
				print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_desc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "airport_departure_desc")
			{
				print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc" class="active"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc"><span class="nomobile">'._("Coming from").'</span><span class="mobile">'._("From").'</span></a> <i class="fa fa-sort small"></i></th>';
			}
		}
		if ($type == 'aircraft' || $type == 'marine') {
			if (isset($globalVM) && $globalVM && $type == 'marine') {
				if ($_GET['sort'] == "race_asc")
				{
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/race_desc" class="active">'._("Race").'</a> <i class="fa fa-caret-up"></i></th>';
				} else if ($_GET['sort'] == "race_desc")
				{
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/race_asc" class="active">'._("Race").'</a> <i class="fa fa-caret-down"></i></th>';
				} else {
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/race_asc">'._("Race").'</a> <i class="fa fa-sort small"></i></th>';
				}
				print '<th class="status">'._("Status").'</th>';
			} else {
				if ($_GET['sort'] == "airport_arrival_asc")
				{
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_desc" class="active"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-up"></i></th>';
				} else if ($_GET['sort'] == "airport_arrival_desc")
				{
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc" class="active"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-caret-down"></i></th>';
				} else {
					print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc"><span class="nomobile">'._("Going to").'</span><span class="mobile">'._("To").'</span></a> <i class="fa fa-sort small"></i></th>';
				}
			}
		}
		if ($type == 'aircraft') {
			if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {
				print '<th class="pilot"><span class="nomobile">'._("Pilot name").'</span><span class="mobile">'._("Pilot").'</span></a></th>';
			}
			if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
				print '<th class="owner"><span class="nomobile">'._("Owner name").'</span><span class="mobile">'._("Owner").'</span></a></th>';
			}
			if ($showRouteStop) {
				print '<th class="route"><span class="nomobile">'._("Route").'</span><span class="mobile">'._("Route").'</span></th>';
			}
		}
		if ($type == 'marine' && isset($globalVM) && $globalVM) {
			if ($showDistance) {
				if ($_GET['sort'] == "distance_asc")
				{
					print '<th class="distance"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/distance_desc" class="active">'._("Distance").'</a> <i class="fa fa-caret-up"></i></th>';
				} elseif ($_GET['sort'] == "distance_desc")
				{
					print '<th class="distance"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/distance_asc" class="active">'._("Distance").'</a> <i class="fa fa-caret-down"></i></th>';
				} else {
					print '<th class="distance"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/distance_desc">'._("Distance").'</a> <i class="fa fa-sort small"></i></th>';
				}
			}
			print '<th class="captain"><span class="nomobile">'._("Captain name").'</span><span class="mobile">'._("Captain").'</span></a></th>';
		}
		if ($type == 'tracker') {
			print '<th class="comment"><span class="nomobile">'._("Comment").'</span><span class="mobile">'._("Comment").'</span></th>';
		}
		if ($showDuration && $type == 'marine' && isset($globalVM) && $globalVM === TRUE) {
			print '<th class="duration"><span class="nomobile">'._("Race duration").'</span><span class="mobile">'._("Race duration").'</span></th>';
		} elseif ($showDuration && strtolower($current_page) != "currently") {
			print '<th class="duration"><span class="nomobile">'._("Spotted duration").'</span><span class="mobile">'._("Duration").'</span></th>';
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
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">'._("Date last seen").'</a> <i class="fa fa-caret-up"></i></th>';
			} else if ($_GET['sort'] == "date_desc")
			{
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">'._("Date last seen").'</a> <i class="fa fa-caret-down"></i></th>';
			} else {
				print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">'._("Date last seen").'</a> <i class="fa fa-sort small"></i></th>';
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
	if ($showSpecial === true)
	{
		print '<tr class="special">'."\n";
		print '<td colspan="10"><h4>'.$spotter_item['registration'].' - '.$spotter_item['highlight'].'</h4></td>'."\n";
		print '</tr>'."\n";
	}
	if (strtolower($current_page) == "upcoming" && isset($spotter_item['date_iso_8601']) && date("ga") == date("ga", strtotime($spotter_item['date_iso_8601'])))
	{
		print '<tr class="currentHour">';
	} else {
		if (isset($spotter_item['spotted'])) {
			print '<tr class="active">';
		} elseif (isset($spotter_item['spotted_registration'])) {
			print '<tr class="info">';
		} else print '<tr>';
	}
	if (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive" || strtolower($current_page) == "currently" || strtolower($current_page) == "accident-latest" || strtolower($current_page) == "incident-latest" || strtolower($current_page) == "accident-detailed" || strtolower($current_page) == "incident-detailed") {
		if ($type == 'aircraft') {
			if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
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
		} elseif ($type == 'marine') {
			if (isset($globalVM) && $globalVM) {
				if (!isset($spotter_item['race_rank'])) {
					print '<td class="rank"></td>'."\n";
				} else {
					print '<td class="rank">'.$spotter_item['race_rank'].'</td>'."\n";
				}
			}
			if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
					$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
				} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				if (isset($spotter_item['mmsi']) && $spotter_item['mmsi'] != '') {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['mmsi'].'" alt="'.$spotter_item['mmsi'].'" data-content="'._("MMSI:").' '.$spotter_item['mmsi'].'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['ident'].'" data-content="'._("Ident:").' '.$spotter_item['ident'].'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
				print '</td>'."\n";
			} else {
				print '<td class="aircraft_thumbnail">'."\n";
				if (isset($spotter_item['mmsi']) && $spotter_item['mmsi'] != '') {
					print '<img src="'.$globalURL.'/images/placeholder_marine_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['mmsi'].'" alt="'.$spotter_item['mmsi'].'" data-content="'._("MMSI:").' '.$spotter_item['mmsi'].'" data-html="true" width="100px" />'."\n";
				} else {
					print '<img src="'.$globalURL.'/images/placeholder_marine_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['ident'].'" data-content="'._("Ident:").' '.$spotter_item['ident'].'" data-html="true" width="100px" />'."\n";
				}
				print '</td>'."\n";
			}
		} elseif ($type == 'tracker') {
			if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
					$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
				} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				print '</td>'."\n";
			} else {
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['type'] == 'Truck' || $spotter_item['type'] == 'Truck (18 Wheeler)') {
					print '<img src="'.$globalURL.'/images/placeholder_truck_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Car') {
					print '<img src="'.$globalURL.'/images/placeholder_car_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Jogger') {
					print '<img src="'.$globalURL.'/images/placeholder_pedestrian_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Phone') {
					print '<img src="'.$globalURL.'/images/placeholder_phone_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Jeep') {
					print '<img src="'.$globalURL.'/images/placeholder_jeep_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Laptop') {
					print '<img src="'.$globalURL.'/images/placeholder_laptop_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Snowmobile') {
					print '<img src="'.$globalURL.'/images/placeholder_snowmobile_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Helicopter') {
					print '<img src="'.$globalURL.'/images/placeholder_helicopter_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Bike') {
					print '<img src="'.$globalURL.'/images/placeholder_bike_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Motorcycle') {
					print '<img src="'.$globalURL.'/images/placeholder_motorbike_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Balloon') {
					print '<img src="'.$globalURL.'/images/placeholder_balloon_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Bus') {
					print '<img src="'.$globalURL.'/images/placeholder_bus_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Ambulance') {
					print '<img src="'.$globalURL.'/images/placeholder_ambulance_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Police') {
					print '<img src="'.$globalURL.'/images/placeholder_police_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Van') {
					print '<img src="'.$globalURL.'/images/placeholder_van_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Railroad Engine') {
					print '<img src="'.$globalURL.'/images/placeholder_rail_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} else {
					print '<img src="'.$globalURL.'/images/placeholder_antenna_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				}
				print '</td>'."\n";
			}
		}
	} elseif(strtolower($current_page) != "currently" && strtolower($current_page) != "upcoming" && strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive" && strtolower($current_page) != "accident-latest" && strtolower($current_page) != "incident-latest" && strtolower($current_page) != "accident-detailed" && strtolower($current_page) != "incident-detailed"){
		if ($type == 'aircraft') {
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
		} elseif ($type == 'marine') {
			if (isset($globalVM) && $globalVM) {
				if (!isset($spotter_item['race_rank'])) {
					print '<td class="rank"></td>'."\n";
				} else {
					print '<td class="rank">'.$spotter_item['race_rank'].'</td>'."\n";
				}
			}
			if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
					$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
				} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				if (isset($spotter_item['mmsi']) && $spotter_item['mmsi'] != '') {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['mmsi'].'" alt="'.$spotter_item['mmsi'].'" data-content="'._("MMSI:").' '.$spotter_item['mmsi'].'" data-html="true" width="100px" />'."\n";
					if ($spotter_item['image_copyright'] != '') print '<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				} else {
					print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['ident'].'" data-content="'._("Ident:").' '.$spotter_item['ident'].'" data-html="true" width="100px" />'."\n";
					if ($spotter_item['image_copyright'] != '') print '<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				}
				print '</td>'."\n";
			} else {
				print '<td class="aircraft_thumbnail">'."\n";
				if (isset($spotter_item['mmsi']) && $spotter_item['mmsi'] != '') {
					print '<img src="'.$globalURL.'/images/placeholder_marine_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['mmsi'].'" alt="'.$spotter_item['mmsi'].'" data-content="'._("MMSI:").' '.$spotter_item['mmsi'].'" data-html="true" width="100px" />'."\n";
				} else {
					print '<img src="'.$globalURL.'/images/placeholder_marine_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['ident'].'" data-content="'._("Ident:").' '.$spotter_item['ident'].'" data-html="true" width="100px" />'."\n";
				}
				print '</td>'."\n";
			}
		} elseif ($type == 'tracker') {
			if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart' || $spotter_item['image_source'] == 'flickr') {
					$image_thumbnail = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
				} else 	$image_thumbnail = $spotter_item['image_thumbnail'];
				print '<img src="'.$image_thumbnail.'" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n".'<div class="thumbnail-copyright">&copy; '.$spotter_item['image_copyright'].'</div>';
				print '</td>'."\n";
			} else {
				print '<td class="aircraft_thumbnail">'."\n";
				if ($spotter_item['type'] == 'Truck' || $spotter_item['type'] == 'Truck (18 Wheeler)') {
					print '<img src="'.$globalURL.'/images/placeholder_truck_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Car') {
					print '<img src="'.$globalURL.'/images/placeholder_car_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Jogger') {
					print '<img src="'.$globalURL.'/images/placeholder_pedestrian_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Phone') {
					print '<img src="'.$globalURL.'/images/placeholder_phone_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Jeep') {
					print '<img src="'.$globalURL.'/images/placeholder_jeep_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Laptop') {
					print '<img src="'.$globalURL.'/images/placeholder_laptop_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Snowmobile') {
					print '<img src="'.$globalURL.'/images/placeholder_snowmobile_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Helicopter') {
					print '<img src="'.$globalURL.'/images/placeholder_helicopter_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Bike') {
					print '<img src="'.$globalURL.'/images/placeholder_bike_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Motorcycle') {
					print '<img src="'.$globalURL.'/images/placeholder_motorbike_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Balloon') {
					print '<img src="'.$globalURL.'/images/placeholder_balloon_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Bus') {
					print '<img src="'.$globalURL.'/images/placeholder_bus_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Ambulance') {
					print '<img src="'.$globalURL.'/images/placeholder_ambulance_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Police') {
					print '<img src="'.$globalURL.'/images/placeholder_police_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Van') {
					print '<img src="'.$globalURL.'/images/placeholder_van_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} elseif ($spotter_item['type'] == 'Railroad Engine') {
					print '<img src="'.$globalURL.'/images/placeholder_rail_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				} else {
					print '<img src="'.$globalURL.'/images/placeholder_antenna_thumb.png" class="img-rounded" data-toggle="popover" title="'.$spotter_item['ident'].'" alt="'.$spotter_item['type'].'" data-content="'._("Type:").' '.$spotter_item['type'].'" data-html="true" width="100px" />'."\n";
				}
				print '</td>'."\n";
			}
		}
	}
	if ($type == 'aircraft') {
		if (!isset($globalNoAirlines) || $globalNoAirlines === FALSE) {
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
		}
	}
	if (!isset($globalNoIdents) || $globalNoIdents === FALSE) {
		// Aircraft ident
		print '<td class="ident">'."\n";
		if ($type == 'aircraft') {
			if (isset($spotter_item['ident']) && $spotter_item['ident'] != "")
			{
				if ($spotter_item['ident'] == "NA") {
					print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'._("Not available").'</a>'."\n";
				} else {
					print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>'."\n";
				}
			} else {
				print '<a href="'.$globalURL.'/ident/NA">'._("Not available").'</a>'."\n";
			}
		} elseif ($type == 'marine') {
			if (isset($spotter_item['ident']) && $spotter_item['ident'] != "")
			{
				if ($spotter_item['ident'] == "NA") {
					print '<a href="'.$globalURL.'/marine/ident/'.$spotter_item['ident'].'">'._("Not available").'</a>'."\n";
				} else {
					print '<a href="'.$globalURL.'/marine/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>'."\n";
				}
			} else {
				print '<a href="'.$globalURL.'/marine/ident/NA">'._("Not available").'</a>'."\n";
			}
		} elseif ($type == 'tracker') {
			if (isset($spotter_item['ident']) && $spotter_item['ident'] != "")
			{
				if ($spotter_item['ident'] == "NA") {
					print '<a href="'.$globalURL.'/tracker/ident/'.$spotter_item['ident'].'">'._("Not available").'</a>'."\n";
				} else {
					print '<a href="'.$globalURL.'/tracker/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>'."\n";
				}
			} else {
				print '<a href="'.$globalURL.'/tracker/ident/NA">'._("Not available").'</a>'."\n";
			}
		}
		print '</td>'."\n";
	}
	// Aircraft type
	if(strtolower($current_page) != "upcoming" && strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive"){
		print '<td class="type">'."\n";
		if ($type == 'aircraft') {
			if (!isset($spotter_item['aircraft_type']) && isset($spotter_item['aircraft_name'])) {
				print '<span class="nomobile">'.$spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'].'</span>'."\n";
			} elseif (!isset($spotter_item['aircraft_name']) || ($spotter_item['aircraft_manufacturer'] == 'N/A' && $spotter_item['aircraft_name'] == 'N/A')) {
				//print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'._("Not available").'</a></span>'."\n";
				print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a></span>'."\n";
			} else {
				$aircraft_names = explode('/',$spotter_item['aircraft_name']);
				if (count($aircraft_names) == 1) print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'].'</a></span>'."\n";
				else print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'" title="'.$spotter_item['aircraft_name'].'">'.$spotter_item['aircraft_manufacturer'].' '.$aircraft_names[0].'</a></span>'."\n";
			}
			print '<span class="mobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a></span>'."\n";
		} elseif ($type == 'marine') {
			if (isset($spotter_item['type_id'])) {
				if ($spotter_item['type'] == '') {
					print '<span class="nomobile"><a href="'.$globalURL.'/marine/type/'.$spotter_item['type_id'].'">'._("Not available").'</a></span>'."\n";
				} else {
					print '<span class="nomobile"><a href="'.$globalURL.'/marine/type/'.$spotter_item['type_id'].'">'.$spotter_item['type'].'</a></span>'."\n";
				}
				print '<span class="mobile"><a href="'.$globalURL.'/marine/type/'.$spotter_item['type_id'].'">'.$spotter_item['type'].'</a></span>'."\n";
			} else {
				if ($spotter_item['type'] == '') {
					print '<span class="nomobile">'._("Not available").'</span>'."\n";
				} else {
					print '<span class="nomobile">'.$spotter_item['type'].'</span>'."\n";
				}
				print '<span class="mobile">'.$spotter_item['type'].'</span>'."\n";
			}
		} elseif ($type == 'tracker') {
			if ($spotter_item['type'] == '') {
				print '<span class="nomobile">'._("Not available").'</span>'."\n";
			} else {
				print '<span class="nomobile">'.$spotter_item['type'].'</span>'."\n";
			}
			print '<span class="mobile">'.$spotter_item['type'].'</span>'."\n";
		}
		print '</td>'."\n";
	}
	if (strtolower($current_page) != "acars-latest" && strtolower($current_page) != "acars-archive" && strtolower($current_page) != "accident-latest" && strtolower($current_page) != "incident-latest" && strtolower($current_page) != "accident-detailed" && strtolower($current_page) != "incident-detailed") {
		if ($type == 'aircraft') {
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
			} elseif (isset($spotter_item['real_departure_airport_time']) && $spotter_item['real_departure_airport_time'] != 'NULL') {
				if ($spotter_item['real_departure_airport_time'] > 2460) {
					$real_departure_airport_time = date('H:m',$spotter_item['real_departure_airport_time']);
				} else $real_departure_airport_time = $spotter_item['real_departure_airport_time'];
				print '<br /><span class="airport_time">'.$real_departure_airport_time.'</span>'."\n";
			} elseif (isset($spotter_item['departure_airport_time']) && $spotter_item['departure_airport_time'] != 'NULL') {
				if ($spotter_item['departure_airport_time'] > 2460) {
					$departure_airport_time = date('H:m',$spotter_item['departure_airport_time']);
				} else {
					$departure_airport_time = substr($spotter_item['departure_airport_time'],0,-2).':'.substr($spotter_item['departure_airport_time'],-2);
				}
				print '<br /><span class="airport_time">'.$departure_airport_time.'</span>'."\n";
			}
			if ($spotter_item['departure_airport'] != 'NA') {
				if (isset($spotter_item['latitude']) && $spotter_item['latitude'] != 0 && isset($spotter_item['longitude']) && $spotter_item['longitude'] != 0) {
					require_once(dirname(__FILE__).'/require/class.Spotter.php');
					$Spotter = new Spotter();
					if (isset($spotter_item['last_latitude']) && $spotter_item['last_latitude'] != '' && isset($spotter_item['last_longitude']) && $spotter_item['last_longitude'] != '') {
						$latitude = $spotter_item['last_latitude'];
						$longitude = $spotter_item['last_longitude'];
					} else {
						$latitude = $spotter_item['latitude'];
						$longitude = $spotter_item['longitude'];
					}
					$distance = $Spotter->getAirportDistance($spotter_item['departure_airport'],$latitude,$longitude);
				} else $distance = '';
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
		}
		if ($type == 'aircraft') {
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
					$arrival_airport_info = $Spotter->getAllAirportInfo($spotter_item['real_arrival_airport']);
					if (isset($arrival_airport_info[0])) {
                        print '<br /><span class="nomobile">' . _("Real:") . ' <a href="' . $globalURL . '/airport/' . $spotter_item['real_arrival_airport'] . '">' . $arrival_airport_info[0]['city'] . ',' . $arrival_airport_info[0]['country'] . ' (' . $spotter_item['real_arrival_airport'] . ')</a></span>' . "\n";
                    }
                    print '<span class="mobile">'._("Scheduled:").' <a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$spotter_item['real_arrival_airport'].'</a></span>'."\n";
                    if (isset($arrival_airport_info[0])) {
                        print '<span class="mobile">'._("Real:").' <a href="'.$globalURL.'/airport/'.$spotter_item['real_arrival_airport'].'">'.$arrival_airport_info[0]['city'].','.$arrival_airport_info[0]['country'].' ('.$spotter_item['real_arrival_airport'].')</a></span>'."\n";
					}
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
			} elseif (isset($spotter_item['arrival_airport_time']) && $spotter_item['arrival_airport_time'] != 'NULL') {
				if ($spotter_item['arrival_airport_time'] > 2460) {
					$arrival_airport_time = date('H:m',$spotter_item['arrival_airport_time']);
				} else $arrival_airport_time = $spotter_item['arrival_airport_time'];
				print '<br /><span class="airport_time">'.$arrival_airport_time.'</span>'."\n";
			}
			if (!isset($spotter_item['real_arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
				if (isset($spotter_item['latitude']) && $spotter_item['latitude'] != 0 && isset($spotter_item['longitude']) && $spotter_item['longitude'] != 0) {
					if (isset($spotter_item['last_latitude']) && $spotter_item['last_latitude'] != '' && isset($spotter_item['last_longitude']) && $spotter_item['last_longitude'] != '') {
						$latitude = $spotter_item['last_latitude'];
						$longitude = $spotter_item['last_longitude'];
					} else {
						$latitude = $spotter_item['latitude'];
						$longitude = $spotter_item['longitude'];
					}
					$distance = $Spotter->getAirportDistance($spotter_item['arrival_airport'],$latitude,$longitude);
				} else $distance = '';
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
		} elseif ($type == 'marine') {
			if (isset($globalVM) && $globalVM) {
				print '<td class="arrival_airport">'."\n";
				if (!isset($spotter_item['race_name']) || $spotter_item['race_name'] == '') {
					print '<span class="nomobile">'._("Not available").'</span>'."\n";
					print '<span class="mobile">'._("Not available").'</span>'."\n";
				} else {
					print '<span class="nomobile"><a href="'.$globalURL.'/marine/race/'.$spotter_item['race_id'].'">'.$spotter_item['race_name'].'</a></span>'."\n";
					print '<span class="mobile"><a href="'.$globalURL.'/marine/race/'.$spotter_item['race_id'].'">'.$spotter_item['race_name'].'</a></span>'."\n";
				}
				print '</td>'."\n";
				print '<td class="status">';
				if (!isset($spotter_item['status']) || $spotter_item['status'] == '') {
					print _("Not available")."\n";
				} else {
					print $spotter_item['status']."\n";
				}
				print '</td>';
			} else {
				print '<td class="arrival_airport">'."\n";
				if (!isset($spotter_item['arrival_port_name'])) {
					//print '<span class="nomobile"><a href="'.$globalURL.'/marine/port/NA">'._("Not available").'</a></span>'."\n";
					//print '<span class="mobile"><a href="'.$globalURL.'/marine/port/NA">'._("Not available").'</a></span>'."\n";
					print '<span class="nomobile">'._("Not available").'</span>'."\n";
					print '<span class="mobile">'._("Not available").'</span>'."\n";
				} else {
					//print '<span class="nomobile"><a href="'.$globalURL.'/marine/port/'.urlencode($spotter_item['arrival_port_name']).'">'.$spotter_item['arrival_port_name'].'</a></span>'."\n";
					//print '<span class="mobile"><a href="'.$globalURL.'/marine/port/'.urlencode($spotter_item['arrival_port_name']).'">'.$spotter_item['arrival_port_name'].'</a></span>'."\n";
					print '<span class="nomobile">'.$spotter_item['arrival_port_name'].'</span>'."\n";
					print '<span class="mobile">'.$spotter_item['arrival_port_name'].'</span>'."\n";
				}
				print '</td>'."\n";
			}
		}
		
		if ($type == 'tracker') {
			print '<td class="comment">'."\n";
			print $spotter_item['comment'];
			print '</td>'."\n";
		}


		if (isset($_GET['dist']) && $_GET['dist'] != '') {
			print '<td class="distance">'."\n";
			if (!isset($spotter_item['distance']) || $spotter_item['distance'] == '') {
				print '<span class="nomobile">-</span>'."\n";
				print '<span class="mobile">-</span>'."\n";
			} else {
				if ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
					print '<span class="nomobile">'.round($spotter_item['distance'],2).' km</span>'."\n";
					print '<span class="mobile">'.round($spotter_item['distance'],2).' km</span><br />'."\n";
				} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
					print '<span class="nomobile">'.round($spotter_item['distance']*0.621371,2).' mi</span>'."\n";
					print '<span class="mobile">'.round($spotter_item['distance']*0.621371,2).' mi</span><br />'."\n";
				} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
					print '<span class="nomobile">'.round($spotter_item['distance']*0.539957,2).' nm</span>'."\n";
					print '<span class="mobile">'.round($spotter_item['distance']*0.539957,2).' nm</span><br />'."\n";
				}
			}
			print '</td>'."\n";
		}
		if(strtolower($current_page) != "upcoming"){
			if ($type == 'aircraft') {
				//if ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
				if ((isset($globalUsePilot) && $globalUsePilot) || !isset($globalUsePilot) && ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS) || (isset($globalVAM) && $globalVAM))) {
					print '<td class="pilot">'."\n";
					if ((!isset($spotter_item['pilot_id']) || $spotter_item['pilot_id'] == '') && (!isset($spotter_item['pilot_name']) || $spotter_item['pilot_name'] == '')) {
						print '<span class="nomobile">-</span>'."\n";
						print '<span class="mobile">-</span>'."\n";
					} elseif ((!isset($spotter_item['pilot_id']) || $spotter_item['pilot_id'] == '') && (isset($spotter_item['pilot_name']) && $spotter_item['pilot_name'] != '')) {
						print '<span class="nomobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_name'].'">'.$spotter_item['pilot_name'].'</a></span>'."\n";
						print '<span class="mobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_name'].'">'.$spotter_item['pilot_name'].'</a></span>'."\n";
					} else {
						if (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'whazzup') {
							print '<span class="nomobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].'</a> (<a href="https://www.ivao.aero/Member.aspx?ID='.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_id'].'</a>)</span>'."\n";
							print '<span class="mobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].'</a> (<a href="https://www.ivao.aero/Member.aspx?ID='.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_id'].'</a>)</span>'."\n";
						} else {
							if (!isset($spotter_item['pilot_name'])) {
								print '<span class="nomobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">('.$spotter_item['pilot_id'].')</a></span>'."\n";
								print '<span class="mobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">('.$spotter_item['pilot_id'].')</a></span>'."\n";
							} else {
								print '<span class="nomobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a></span>'."\n";
								print '<span class="mobile"><a href="'.$globalURL.'/pilot/'.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a></span>'."\n";
							}
						}
					}
					print '</td>'."\n";
				}
				if ((isset($globalUseOwner) && $globalUseOwner) || (!isset($globalUseOwner) && (!isset($globalVA) || !$globalVA) && (!isset($globalIVAO) || !$globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM))) {
					print '<td class="owner">'."\n";
					if (!isset($spotter_item['aircraft_owner']) || $spotter_item['aircraft_owner'] == '') {
						print '<span class="nomobile">-</span>'."\n";
						print '<span class="mobile">-</span>'."\n";
					} else {
						print '<span class="nomobile"><a href="'.$globalURL.'/owner/'.$spotter_item['aircraft_owner'].'">'.$spotter_item['aircraft_owner'].'</a></span>'."\n";
						print '<span class="mobile"><a href="'.$globalURL.'/owner/'.$spotter_item['aircraft_owner'].'">'.$spotter_item['aircraft_owner'].'</a></span>'."\n";
					}
					print '</td>'."\n";
				}
			}
			if ($type == 'marine') {
				if (isset($globalVM) && $globalVM) {
					if ($showDistance) {
						if (isset($spotter_item['distance'])) {
							print '<td class="distance">';
							if ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
								print '<span class="nomobile">'.round($spotter_item['distance'],2).' km</span>'."\n";
								print '<span class="mobile">'.round($spotter_item['distance'],2).' km</span><br />'."\n";
							} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
								print '<span class="nomobile">'.round($spotter_item['distance']*0.621371,2).' mi</span>'."\n";
								print '<span class="mobile">'.round($spotter_item['distance']*0.621371,2).' mi</span><br />'."\n";
							} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
								print '<span class="nomobile">'.round($spotter_item['distance']*0.539957,2).' nm</span>'."\n";
								print '<span class="mobile">'.round($spotter_item['distance']*0.539957,2).' nm</span><br />'."\n";
							}
							print '</td>'."\n";
						} else {
							print '<td class="distance"></td>'."\n";
						}
					}
					print '<td class="captain">'."\n";
					if ((!isset($spotter_item['captain_id']) || $spotter_item['captain_id'] == '') && (!isset($spotter_item['captain_name']) || $spotter_item['captain_name'] == '')) {
						print '<span class="nomobile">-</span>'."\n";
						print '<span class="mobile">-</span>'."\n";
					} elseif ((!isset($spotter_item['captain_id']) || $spotter_item['captain_id'] == '') && (isset($spotter_item['captain_name']) && $spotter_item['captain_name'] != '')) {
						print '<span class="nomobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_name'].'">'.$spotter_item['captain_name'].'</a></span>'."\n";
						print '<span class="mobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_name'].'">'.$spotter_item['captain_name'].'</a></span>'."\n";
					} else {
						if (!isset($spotter_item['captain_name'])) {
							print '<span class="nomobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_id'].'">('.$spotter_item['captain_id'].')</a></span>'."\n";
							print '<span class="mobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_id'].'">('.$spotter_item['captain_id'].')</a></span>'."\n";
						} else {
							print '<span class="nomobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_id'].'">'.$spotter_item['captain_name'].' ('.$spotter_item['captain_id'].')</a></span>'."\n";
							print '<span class="mobile"><a href="'.$globalURL.'/marine/captain/'.$spotter_item['captain_id'].'">'.$spotter_item['captain_name'].' ('.$spotter_item['captain_id'].')</a></span>'."\n";
						}
					}
					print '</td>'."\n";
				}
			
			}
		}
		
		if ($showRouteStop) {
		// Route stop
			if(strtolower($current_page) != "upcoming"){
				print '<td class="route_stop">'."\n";
				if (!isset($spotter_item['route_stop']) || $spotter_item['route_stop'] == '' || $spotter_item['route_stop'] == 'NULL') {
					print '<span class="nomobile">-</span>'."\n";
					print '<span class="mobile">-</span>'."\n";
				} elseif (!isset($spotter_item['route_stop_details'])) {
					print '<span class="nomobile">'.$spotter_item['route_stop'].'</span>'."\n";
					print '<span class="mobile">'.$spotter_item['route_stop'].'</span>'."\n";
				} else {
					foreach ($spotter_item['route_stop_details'] as $rst) {
						print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$rst['airport_icao'].'">'.$rst['airport_city'].', '.$rst['airport_country'].' ('.$rst['airport_icao'].')</a></span>'."\n";
						print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$rst['airport_icao'].'">'.$rst['airport_icao'].'</a></span><br />'."\n";
					}
				}
				print '</td>'."\n";
			}
		}
		if ($showDuration) {
			// Duration
			if (isset($globalVM) && $globalVM && $type == 'marine') {
				print '<td class="duration">'."\n";
				if (isset($spotter_item['race_time'])) {
					if ($spotter_item['race_time'] > 86400) {
						print '<span class="nomobile">'.gmdate("z\d. H\h. i\m. s\s.",$spotter_item['race_time']).'</span>'."\n";
						print '<span class="mobile">'.gmdate("z\d. H\h. i\m. s\s.",$spotter_item['race_time']).'</span>'."\n";
					} else {
						print '<span class="nomobile">'.gmdate("H\h. i\m. s\s.",$spotter_item['race_time']).'</span>'."\n";
						print '<span class="mobile">'.gmdate("H\h. i\m. s\s.",$spotter_item['race_time']).'</span>'."\n";
					}
				} elseif (isset($spotter_item['duration'])) {
					if ($spotter_item['duration'] > 86400) {
						print '<span class="nomobile">'.gmdate('z\d. H\h. i\m. s\s.',$spotter_item['duration']).'</span>'."\n";
						print '<span class="mobile">'.gmdate('z\d. H\h. i\m. s\s.',$spotter_item['duration']).'</span>'."\n";
					} else {
						print '<span class="nomobile">'.gmdate('H\h. i\m. s\s.',$spotter_item['duration']).'</span>'."\n";
						print '<span class="mobile">'.gmdate('H\h. i\m. s\s.',$spotter_item['duration']).'</span>'."\n";
					}
				} else {
					print '<span class="nomobile">-</span>'."\n";
					print '<span class="mobile">-</span>'."\n";
				}
				print '</td>'."\n";
			} else {
				if(strtolower($current_page) != "upcoming"){
					print '<td class="duration">'."\n";
					if (isset($spotter_item['duration'])) {
						print '<span class="nomobile">'.gmdate('H:i:s',$spotter_item['duration']).'</span>'."\n";
						print '<span class="mobile">'.gmdate('H:i:s',$spotter_item['duration']).'</span>'."\n";
					} else {
						print '<span class="nomobile">-</span>'."\n";
						print '<span class="mobile">-</span>'."\n";
					}
					print '</td>'."\n";
				}
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
	if (strtolower($current_page) == "accident-latest" || strtolower($current_page) == "accident-detailed") {
		print '<td class="owner">'."\n";
		if (isset($spotter_item['aircraft_owner'])) {
			print $spotter_item['aircraft_owner'];
		} else {
			echo _('Not Available');
		}
		print '</td>'."\n";
		/*
		print '<td class="acctype">'."\n";
		print $spotter_item['type'];
		print '</td>'."\n";
		*/
		print '<td class="fatalities">'."\n";
		if ($spotter_item['fatalities'] == '') {
			print _("Not available");
		} else {
			print $spotter_item['fatalities'];
		}
		print '</td>'."\n";
		print '<td class="message">'."\n";
		print str_replace(array("\r\n", "\n", "\r"),'<br />',$spotter_item['message']);
		print '</td>'."\n";
	}
	if (strtolower($current_page) == "incident-latest" || strtolower($current_page) == "incident-detailed") {
		print '<td class="owner">'."\n";
		if (isset($spotter_item['aircraft_owner'])) {
			print $spotter_item['aircraft_owner'];
		} else {
			echo _('Not Available');
		}
		print '</td>'."\n";
		/*
		print '<td class="acctype">'."\n";
		print $spotter_item['type'];
		print '</td>'."\n";
		*/
		/*
		print '<td class="fatalities">'."\n";
		if ($spotter_item['fatalities'] == '') {
			print _("Not available");
		} else {
			print $spotter_item['fatalities'];
		}
		print '</td>'."\n";
		*/
		print '<td class="message">'."\n";
		print str_replace(array("\r\n", "\n", "\r"),'<br />',$spotter_item['message']);
		print '</td>'."\n";
	}

	// Date
	if (strtolower($current_page) == "date")
	{
		print '<td class="time">'."\n";
		print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
		if (isset($spotter_item['last_seen_date_iso_8601'])) {
			print '<hr />';
			print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['last_seen_date_iso_8601'])).'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['last_seen_date_iso_8601'])).'</a></span>'."\n";
		}
		print '</td>'."\n";
	} else if (strtolower($current_page) == "index")
	{
		print '<td class="time">'."\n";
		print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>'."\n";
		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>'."\n";
		print '</td>'."\n";
	} else if (strtolower($current_page) == "upcoming")
	{
		if (isset($spotter_item['date_iso_8601'])) {
			print '<td class="time">';
			print '<span>'.date("g:i a", strtotime($spotter_item['date_iso_8601'])).'</span>';
			print '</td>';
		}
	} elseif (strtolower($current_page) == "acars-latest" || strtolower($current_page) == "acars-archive")
	{
		print '<td class="date">'."\n";
		print '<span class="nomobile">'.date("r", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '<span class="mobile">'.date("j/n/Y g:i a", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '</td>'."\n";
	} elseif (strtolower($current_page) == "accident-latest" || strtolower($current_page) == "accident-detailed")
	{
		print '<td class="date">'."\n";
		print '<span class="nomobile">'.date("d/m/Y", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '<span class="mobile">'.date("d/m/Y", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '</td>'."\n";
	} elseif (strtolower($current_page) == "incident-latest" || strtolower($current_page) == "incident-detailed")
	{
		print '<td class="date">'."\n";
		print '<span class="nomobile">'.date("d/m/Y", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '<span class="mobile">'.date("d/m/Y", strtotime($spotter_item['date'].' UTC')).'</span>'."\n";
		print '</td>'."\n";
	} else {
		if ($type == 'aircraft') {
			print '<td class="date">'."\n";
			print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("r", $spotter_item['date_unix']).'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
			if (isset($spotter_item['last_seen_date_iso_8601'])) {
				print '<hr />';
				print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("r", $spotter_item['last_seen_date_unix']).'</a></span>'."\n";
				print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['last_seen_date_iso_8601'])).'</a></span>'."\n";
			}
			print '</td>'."\n";
		} elseif ($type == 'marine' || $type == 'tracker') {
			/*
			print '<td class="date">'."\n";
			print '<span class="nomobile"><a href="'.$globalURL.'/marineid/'.$spotter_item['marine_id'].'">'.date("r", $spotter_item['date_unix']).'</a></span>'."\n";
			print '<span class="mobile"><a href="'.$globalURL.'/marineid/'.$spotter_item['marine_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</a></span>'."\n";
			if (isset($spotter_item['last_seen_date_iso_8601'])) {
				print '<hr />';
				print '<span class="nomobile"><a href="'.$globalURL.'/marineid/'.$spotter_item['marine_id'].'">'.date("r", $spotter_item['last_seen_date_unix']).'</a></span>'."\n";
				print '<span class="mobile"><a href="'.$globalURL.'/marineid/'.$spotter_item['marine_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['last_seen_date_iso_8601'])).'</a></span>'."\n";
			}
			print '</td>'."\n";
			*/
			print '<td class="date">'."\n";
			print '<span class="nomobile">'.date("r", $spotter_item['date_unix']).'</span>'."\n";
			print '<span class="mobile">'.date("j/n/Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</span>'."\n";
			if (isset($spotter_item['last_seen_date_iso_8601'])) {
				print '<hr />';
				print '<span class="nomobile">'.date("r", $spotter_item['last_seen_date_unix']).'</span>'."\n";
				print '<span class="mobile">'.date("j/n/Y g:i a", strtotime($spotter_item['last_seen_date_iso_8601'])).'</span>'."\n";
			}
			print '</td>'."\n";
		}
	}
	if ($type == 'marine' || $type == 'tracker') {
		print '<td class="more"></td>';
	} elseif (strtolower($current_page) != "upcoming")
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
		if (strtolower($current_page) == "accident-latest" || strtolower($current_page) == "accident-detailed" || strtolower($current_page) == "incident-latest" || strtolower($current_page) == "incident-detailed") {
			if (isset($spotter_item['flightaware_id'])) {
				print '<li><a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'">'._("Aircraft History").' ('.$spotter_item['registration'].')</a></li>';
			}
		} elseif (isset($spotter_item['registration']) && $spotter_item['registration'] != "")
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
		if (isset($spotter_item['airline_country']) && $spotter_item['airline_country'] != "" && $spotter_item['airline_country'] != "NA")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['airline_country'])).'">'._("Airline Country Profile").'</a></li>';
			print '<li><hr /></li>';
		}
		if (isset($spotter_item['departure_airport_country']) && $spotter_item['departure_airport_country'] != "" && $spotter_item['departure_airport_country'] != "N/A" && $spotter_item['departure_airport_country'] != "NA")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['departure_airport_country'])).'">'._("Departure Airport Country Profile").'</a></li>';
		}
		if (isset($spotter_item['arrival_airport_country']) && $spotter_item['arrival_airport_country'] != "" && $spotter_item['arrival_airport_country'] != "N/A" && $spotter_item['arrival_airport_country'] != "NA")
		{
			print '<li><a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $spotter_item['arrival_airport_country'])).'">'._("Arrival Airport Country Profile").'</a></li>';
		}
		if (strtolower($current_page) == "accident-latest" || strtolower($current_page) == "incident-latest" || strtolower($current_page) == "accident-detailed" || strtolower($current_page) == "incident-detailed") {
			if (isset($spotter_item['url']) && $spotter_item['url'] != "")
			{
				print '<li><a href="'.$spotter_item['url'].'">'._("Detailed information").'</a></li>';
			}
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