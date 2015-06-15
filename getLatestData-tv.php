<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

header('Content-Type: text/javascript');

$spotter_array = Spotter::getRealTimeData($_GET['q']);

$output = '{';

	$output .= '"flights": [';
	
	foreach($spotter_array as $spotter_item)
	  {
	  	
	  	
	  	$output .= '{';
		  	$output .= '"flight_id": "'.$spotter_item['spotter_id'].'",';
		  	$output .= '"html": "';
		  	$output .= '<tr id=\"table-tr-'.$spotter_item['spotter_id'].'\" style=\"display:none;\">';
		    	if ($_GET['image'] == "true")
		    	{
			    	if ($spotter_item['image'] != "")
			    	 {
			    	 	$output .= '<td class=\"aircraft_image\">';
			    	 		$output .= '<img src=\"'.$spotter_item['image'].'\" alt=\"Click to see more information about this flight\" title=\"Click to see more information about this flight\" />';
			    	 	$output .= '</td>';
			    	 } else {
			      	 $output .= '<td class=\"aircraft_image\">';
			      	 	$output .= '<img src=\"'.$globalURL.'/images/placeholder.png\" alt=\"Click to see more information about this flight\" title=\"Click to see more information about this flight\" />';
			      	 $output .= '</td>';
			    	 }
		    	 }
		    	if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif'))
		  		{
		    		$output .= '<td class=\"logo\">';
		    			$output .= '<img src=\"'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif\" />';
		    		$output .= '</td>';
		    	} elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png'))
		  		{
		    		$output .= '<td class=\"logo\">';
		    			$output .= '<img src=\"'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png\" />';
		    		$output .= '</td>';
		  		} else {
		  			$output .= '<td class=\"logo-no-image\">';
		  				if ($spotter_item['airline_name'] != "")
		  				{
		  					$output .= $spotter_item['airline_name'];
		  				}
		  			$output .= '</td>';
		  		}
		  		$output .= '<td class=\"info\">';
		  			$output .= '<div class=\"flight\">';
		  				$output .= $spotter_item['departure_airport_city'].' ('.$spotter_item['departure_airport'].') <i class=\"fa fa-arrow-right\"></i> '.$spotter_item['arrival_airport_city'].' ('.$spotter_item['arrival_airport'].')';
		  			$output .= '</div>';
		  			if ($_GET['other_i'] == "1")
		  			{
			  			$output .= '<div class=\"other1\">';
		  			} else {
			  			$output .= '<div class=\"other1\" style=\"display:none;\">';
		  			}
		  				if ($spotter_item['registration'] != "")
			  			{
			  				$output .= '<span><i class=\"fa fa-align-justify\"></i> '.$spotter_item['registration'].'</span>';
			  			}
			  			if ($spotter_item['aircraft_name'] != "")
			  			{
			    			$output .= '<span><i class=\"fa fa-plane\"></i> '.$spotter_item['aircraft_name'].'</span>';
			  			} else {
			  				if ($spotter_item['aircraft_type'] != "")
			  				{
			    				$output .= '<span><i class=\"fa fa-plane\"></i> '.$spotter_item['aircraft_type'].'</span>';
			  				}
			  			}
			  			$output .= '<span><i class=\"fa fa-calendar\"></i> '.date('M j, Y g:i a', strtotime($spotter_item['date_iso_8601'])).'</span>';
		  			$output .= '</div>';
		  			if ($_GET['other_i'] == "2")
		  			{
			  			$output .= '<div class=\"other2\">';
		  			} else {
			  			$output .= '<div class=\"other2\" style=\"display:none;\">';
		  			}
		  				$output .= '<span><i class=\"fa fa-arrow-up\"></i> '.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].'</span>';
		  				$output .= '<span><i class=\"fa fa-arrow-down\"></i> '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].'</span>';
		  			$output .= '</div>';
		  			if ($_GET['other_i'] == "3")
		  			{
			  			$output .= '<div class=\"other3\">';
		  			} else {
			  			$output .= '<div class=\"other3\" style=\"display:none;\">';
		  			}
		  				if ($spotter_item['ident'] != "")
			  			{
			  				$output .= '<span><i class=\"fa fa-th-list\"></i> '.$spotter_item['ident'].'</span>';
			  			}
			  			if ($spotter_item['airline_name'] != "")
			  			{
			  				$output .= '<span><i class=\"fa fa-align-justify\"></i> '.$spotter_item['airline_name'].'</span>';
			  			}
			  			if ($spotter_item['airline_country'] != "")
			  			{
			  				$output .= '<span><i class=\"fa fa-globe\"></i> '.$spotter_item['airline_country'].'</span>';
			  			}
		  			$output .= '</div>';
		  		$output .= '</td>';
		  	$output .= '</tr>';
		  	
		  	$output .= '"';
		  	
	  	$output .= '},';
							
	  }
	  
	  $output = substr($output, 0, -1);
	  
	$output .= ']';
	  
$output .= '}';

print $output;

?>