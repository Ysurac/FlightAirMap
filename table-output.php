<?php
print '<div class="table-responsive">';
print '<table>';
if (strtolower($current_page) == "search")
{
  print '<thead>';
 	print '<th class="aircraft_thumbnail"></th>';
 	if ($_GET['sort'] == "airline_name_asc")
 	{
	 print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_desc" class="active">Airline</a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "airline_name_desc")
 	{
	 print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc" class="active">Airline</a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="logo"><a href="'.$page_url.'&sort=airline_name_asc">Airline</a> <i class="fa fa-sort small"></i></th>';
 	}
 	if ($_GET['sort'] == "ident_asc")
 	{
	 print '<th class="ident"><a href="'.$page_url.'&sort=ident_desc" class="active">Ident</a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "ident_desc")
 	{
	 print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc" class="active">Ident</a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="ident"><a href="'.$page_url.'&sort=ident_asc">Ident</a> <i class="fa fa-sort small"></i></th>';
 	}
 	if ($_GET['sort'] == "aircraft_asc")
 	{
	 print '<th class="type"><a href="'.$page_url.'&sort=aircraft_desc" class="active">Aircraft</a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "aircraft_desc")
 	{
	 print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc" class="active">Aircraft</a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="type"><a href="'.$page_url.'&sort=aircraft_asc">Aircraft</a> <i class="fa fa-sort small"></i></th>';
 	}
 	if ($_GET['sort'] == "airport_departure_asc")
 	{
	 print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_desc" class="active"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "airport_departure_desc")
 	{
	 print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc" class="active"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="departure"><a href="'.$page_url.'&sort=airport_departure_asc"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-sort small"></i></th>';
 	}
    if ($_GET['sort'] == "airport_arrival_asc")
 	{
	 print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_desc" class="active"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "airport_arrival_desc")
 	{
	 print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc" class="active"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="arrival"><a href="'.$page_url.'&sort=airport_arrival_asc"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-sort small"></i></th>';
 	}
    if ($_GET['sort'] == "date_asc")
 	{
	 print '<th class="time"><a href="'.$page_url.'&sort=date_desc" class="active">Date</a> <i class="fa fa-caret-up"></i></th>';
 	} else if ($_GET['sort'] == "date_desc")
 	{
	 print '<th class="time"><a href="'.$page_url.'&sort=date_asc" class="active">Date</a> <i class="fa fa-caret-down"></i></th>';
 	} else {
	 print '<th class="time"><a href="'.$page_url.'&sort=date_asc">Date</a> <i class="fa fa-sort small"></i></th>';
 	}
  print '</thead>';
} else {

 if ($hide_th_links == true){
	 print '<thead>';
	 	print '<th class="aircraft_thumbnail"></th>';
	 	if ($_GET['sort'] == "airline_name_asc")
	 	{
		 print '<th class="logo"Airline</th>';
	 	} else if ($_GET['sort'] == "airline_name_desc")
	 	{
		 print '<th class="logo">Airline</th>';
	 	} else {
		 print '<th class="logo">Airline</th>';
	 	}
	 	if ($_GET['sort'] == "ident_asc")
	 	{
		 print '<th class="ident">Ident</th>';
	 	} else if ($_GET['sort'] == "ident_desc")
	 	{
		 print '<th class="ident">Ident</th>';
	 	} else {
		 print '<th class="ident">Ident</th>';
	 	}
	 	if ($_GET['sort'] == "aircraft_asc")
	 	{
		 print '<th class="type">Aircraft</th>';
	 	} else if ($_GET['sort'] == "aircraft_desc")
	 	{
		 print '<th class="type">Aircraft</th>';
	 	} else {
		 print '<th class="type">Aircraft</th>';
	 	}
	 	if ($_GET['sort'] == "airport_departure_asc")
	 	{
		 print '<th class="departure"><span class="nomobile">Coming from</span><span class="mobile">From</span></th>';
	 	} else if ($_GET['sort'] == "airport_departure_desc")
	 	{
		 print '<th class="departure"><span class="nomobile">Coming from</span><span class="mobile">From</span></th>';
	 	} else {
		 print '<th class="departure"><span class="nomobile">Coming from</span><span class="mobile">From</span></th>';
	 	}
	    if ($_GET['sort'] == "airport_arrival_asc")
	 	{
		 print '<th class="arrival"><span class="nomobile">Flying to</span><span class="mobile">To</span></th>';
	 	} else if ($_GET['sort'] == "airport_arrival_desc")
	 	{
		 print '<th class="arrival"><span class="nomobile">Flying to</span><span class="mobile">To</span></th>';
	 	} else {
		 print '<th class="arrival"><span class="nomobile">Flying to</span><span class="mobile">To</span></th>';
	 	}
	    if (strtolower($current_page) == "date")
		{
	    	if ($_GET['sort'] == "date_asc")
		 	{
			 print '<th class="time">Time</th>';
		 	} else if ($_GET['sort'] == "date_desc")
		 	{
			 print '<th class="time">Time</th>';
		 	} else {
			 print '<th class="time">Time</th>';
		 	}
	    } else {
		    if ($_GET['sort'] == "date_asc")
		 	{
			 print '<th class="time">Date</th>';
		 	} else if ($_GET['sort'] == "date_desc")
		 	{
			 print '<th class="time">Date</th>';
		 	} else {
			 print '<th class="time">Date</th>';
		 	}
	  }
	  print '</thead>';
 } else {
	 print '<thead>';
	 	print '<th class="aircraft_thumbnail"></th>';
	 	if ($_GET['sort'] == "airline_name_asc")
	 	{
		 print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_desc" class="active">Airline</a> <i class="fa fa-caret-up"></i></th>';
	 	} else if ($_GET['sort'] == "airline_name_desc")
	 	{
		 print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc" class="active">Airline</a> <i class="fa fa-caret-down"></i></th>';
	 	} else {
		 print '<th class="logo"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airline_name_asc">Airline</a> <i class="fa fa-sort small"></i></th>';
	 	}
	 	if ($_GET['sort'] == "ident_asc")
	 	{
		 print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_desc" class="active">Ident</a> <i class="fa fa-caret-up"></i></th>';
	 	} else if ($_GET['sort'] == "ident_desc")
	 	{
		 print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc" class="active">Ident</a> <i class="fa fa-caret-down"></i></th>';
	 	} else {
		 print '<th class="ident"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/ident_asc">Ident</a> <i class="fa fa-sort small"></i></th>';
	 	}
	 	if ($_GET['sort'] == "aircraft_asc")
	 	{
		 print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_desc" class="active">Aircraft</a> <i class="fa fa-caret-up"></i></th>';
	 	} else if ($_GET['sort'] == "aircraft_desc")
	 	{
		 print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc" class="active">Aircraft</a> <i class="fa fa-caret-down"></i></th>';
	 	} else {
		 print '<th class="type"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/aircraft_asc">Aircraft</a> <i class="fa fa-sort small"></i></th>';
	 	}
	 	if ($_GET['sort'] == "airport_departure_asc")
	 	{
		 print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_desc" class="active"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-caret-up"></i></th>';
	 	} else if ($_GET['sort'] == "airport_departure_desc")
	 	{
		 print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc" class="active"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-caret-down"></i></th>';
	 	} else {
		 print '<th class="departure"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_departure_asc"><span class="nomobile">Coming from</span><span class="mobile">From</span></a> <i class="fa fa-sort small"></i></th>';
	 	}
	    if ($_GET['sort'] == "airport_arrival_asc")
	 	{
		 print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_desc" class="active"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-caret-up"></i></th>';
	 	} else if ($_GET['sort'] == "airport_arrival_desc")
	 	{
		 print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc" class="active"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-caret-down"></i></th>';
	 	} else {
		 print '<th class="arrival"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/airport_arrival_asc"><span class="nomobile">Flying to</span><span class="mobile">To</span></a> <i class="fa fa-sort small"></i></th>';
	 	}
	    if (strtolower($current_page) == "date")
		{
	    	if ($_GET['sort'] == "date_asc")
		 	{
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">Time</a> <i class="fa fa-caret-up"></i></th>';
		 	} else if ($_GET['sort'] == "date_desc")
		 	{
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">Time</a> <i class="fa fa-caret-down"></i></th>';
		 	} else {
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">Time</a> <i class="fa fa-sort small"></i></th>';
		 	}
	    } else {
		    if ($_GET['sort'] == "date_asc")
		 	{
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_desc" class="active">Date</a> <i class="fa fa-caret-up"></i></th>';
		 	} else if ($_GET['sort'] == "date_desc")
		 	{
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc" class="active">Date</a> <i class="fa fa-caret-down"></i></th>';
		 	} else {
			 print '<th class="time"><a href="'.$page_url.'/'.$limit_start.','.$limit_end.'/date_asc">Date</a> <i class="fa fa-sort small"></i></th>';
		 	}
	    }
	  print '</thead>';
  }
}
  print '<tbody>';
  foreach($spotter_array as $spotter_item)
  {
    date_default_timezone_set('America/Toronto');
    if ($showSpecial == true)
		{
			print '<tr class="special">';
				print '<td colspan="7"><h4>'.$spotter_item['registration'].' - '.$spotter_item['highlight'].'</h4></td>';
			print '</tr>';
		}
    print '<tr>';
    	if ($spotter_item['image_thumbnail'] != "")
    	 {
    	 	print '<td class="aircraft_thumbnail">';
    	 		print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$spotter_item['image_thumbnail'].'" alt="Click to see more information about this flight" title="Click to see more information about this flight" width="100px" /></a>';
    	 	print '</td>';
    	 } else {
      	 print '<td class="aircraft_thumbnail">';
      	 	print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" alt="Click to see more information about this flight" title="Click to see more information about this flight" width="100px" /></a>';
      	 print '</td>';
    	 }
    	if (@getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png'))
  		{
    		print '<td class="logo">';
    			print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png" alt="Click to see airline information" title="Click to see airline information" /></a>';
    		print '</td>';
  		} else {
  			print '<td class="logo-no-image">';
  				if ($spotter_item['airline_icao'] != "")
  				{
  					print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'">'.$spotter_item['airline_name'].'</a>';
  				} else {
	  				print '<a href="'.$globalURL.'/airline/NA">Not Available</a>';
  				}
  			print '</td>';
  		}
  		print '<td class="ident">';
  			if ($spotter_item['ident'] != "")
  			{
  				print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>';
  			} else {
    			print 'N/A';
  			}
  		print '</td>';
  		print '<td class="type">';
  			print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_name'].'</a></span>';
    		print '<span class="mobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a></span>';
  		print '</td>';
  		print '<td class="departure_airport">';
  			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a></span>';
    		print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport'].'</a></span>';
  		print '</td>';
  		print '<td class="arrival_airport">';
  			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a></span>';
				print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport'].'</a></span>';
			print '</td>';
			if (strtolower($current_page) == "date")
			{
  			print '<td class="time">';
  				print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>';
	    		print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a></span>';
	    	print '</td>';
			} else if (strtolower($current_page) == "index")
			{
  			print '<td class="time">';
  				print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>';
		    	print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.$spotter_item['date'].'</a></span>';
	    	print '</td>';
			} else {
			 print '<td class="date">';
	  			print '<span class="nomobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("M j, Y, g:i a", strtotime($spotter_item['date_iso_8601'])).'</a></span>';
	  			print '<span class="mobile"><a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">'.date("j/n/Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</a></span>';
  			print '</td>';
			}
  	print '</tr>';
  }
	print '<tbody>';
print '</table>';
print '</div>';
?>