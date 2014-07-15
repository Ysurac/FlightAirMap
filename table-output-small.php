<?php
print '<div class="table-responsive">';
print '<table>';
	 print '<thead>';
	 	print '<th class="aircraft_thumbnail"></th>';
	 	print '<th class="logo">Airline</th>';
	 	print '<th class="type">Aircraft</th>';
	 	print '<th class="departure"><span class="nomobile">Coming from</span><span class="mobile">From</span></th>';
	  print '<th class="arrival"><span class="nomobile">Flying to</span><span class="mobile">To</span></th>';
	  print '</thead>';
  print '<tbody>';
  foreach($spotter_array as $spotter_item)
  {
    date_default_timezone_set('America/Toronto');
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
  		print '<td class="type">';
  			print '<span class="nomobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_name'].'</a></span>';
    			print '<span class="mobile"><a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a></span>';
  		print '</td>';
  		print '<td class="departure_airport">';
  			if ($spotter_item['departure_airport_name'] != "")
  			{
    			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a></span>';
    			print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport'].'</a></span>';
  			} else {
  				print $spotter_item['departure_airport'];
  			}
  		print '</td>';
  		print '<td class="arrival_airport">';
  			if ($spotter_item['arrival_airport_name'] != "")
  			{
    			print '<span class="nomobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a></span>';
					print '<span class="mobile"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport'].'</a></span>';
				} else {
					print $spotter_item['arrival_airport'];
				}
			print '</td>';
  	print '</tr>';
  }
	print '<tbody>';
print '</table>';
print '</div>';
?>