<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

//for the date manipulation into the query
if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
	$start_date = $_GET['start_date'].":00";
	$end_date = $_GET['end_date'].":00";
  $sql_date = $start_date.",".$end_date;
} else if($_GET['start_date'] != ""){
	$start_date = $_GET['start_date'].":00";
  $sql_date = $start_date;
} else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
	$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date'].":00";
  $sql_date = $end_date;
}

//for altitude manipulation
if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
	$end_altitude = $_GET['highest_altitude'];
	$start_altitude = $_GET['lowest_altitude'];
  $sql_altitude = $start_altitude.",".$end_altitude;
} else if($_GET['highest_altitude'] != ""){
	$end_altitude = $_GET['highest_altitude'];
	$sql_altitude = $end_altitude;
} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
	$start_altitude = $_GET['lowest_altitude'].",60000";
	$sql_altitude = $start_altitude;
}

//calculuation for the pagination
if($_GET['limit'] == "")
{
  if ($_GET['number_results'] == "")
  {
  $limit_start = 0;
  $limit_end = 25;
  $absolute_difference = 25;
  } else {
	if ($_GET['number_results'] > 1000){
		$_GET['number_results'] = 1000;
	}
	$limit_start = 0;
	$limit_end = $_GET['number_results'];
	$absolute_difference = $_GET['number_results'];
  }
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = $limit_explode[0];
	$limit_end = $limit_explode[1];
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if (!isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="barriespotter.kml"');
}

header('Content-Type: text/xml');

$spotter_array = Spotter::searchSpotterData($_GET['q'],$_GET['registration'],$_GET['aircraft'],strtolower(str_replace("-", " ", $_GET['manufacturer'])),$_GET['highlights'],$_GET['airline'],$_GET['airline_country'],$_GET['airline_type'],$_GET['airport'],$_GET['airport_country'],$_GET['callsign'],$_GET['departure_airport_route'],$_GET['arrival_airport_route'],$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$_GET['sort'],'');
      
      
$output .= '<?xml version="1.0" encoding="UTF-8"?>';
	$output .= '<kml xmlns="http://www.opengis.net/kml/2.2">';
    $output .= '<Document>';
	    $output .= '<Style id="departureAirport">';
	    	$output .= '<IconStyle>';
	      	$output .= '<Icon>';
	        	$output .= '<href>http://barriespotter.com/images/kml_departure_airport.png</href>';
	         $output .= '</Icon>';
	        $output .= '</IconStyle>';
	    $output .= '</Style>';
	    $output .= '<Style id="arrivalAirport">';
	    	$output .= '<IconStyle>';
	      	$output .= '<Icon>';
	        	$output .= '<href>http://barriespotter.com/images/kml_arrival_airport.png</href>';
	         $output .= '</Icon>';
	        $output .= '</IconStyle>';
	    $output .= '</Style>';
	    $output .= '<Style id="route">';
				$output .= '<LineStyle>';  
					$output .= '<color>7f0000ff</color>';
					$output .= '<width>2</width>';
					$output .= '<outline>0</outline>';
				$output .= '</LineStyle>';
			$output .= '</Style>';
            
    if (!empty($spotter_array))
	  {	  
	    foreach($spotter_array as $spotter_item)
	    {
				
				
				$altitude = $spotter_item['altitude'].'00';
				
				//waypoint plotting
				$output .= '<Placemark>'; 
					$output .= '<styleUrl>#route</styleUrl>';
					$output .= '<LineString>';
						$output .= '<coordinates>';
            	$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
							$waypoint_pieces = array_chunk($waypoint_pieces, 2);
							    
							foreach ($waypoint_pieces as $waypoint_coordinate)
							{
							        $output .=  $waypoint_coordinate[1].','.$waypoint_coordinate[0].','.$altitude.' ';
							
							}
						$output .= '</coordinates>';
						$output .= '<altitudeMode>absolute</altitudeMode>';
					$output .= '</LineString>';
				$output .= '</Placemark>';

				//departure airport 
				$output .= '<Placemark>';  
					$output .= '<name>'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</name>';
                    $output .= '<description><![CDATA[ ';
                        $output .= '<div class="ge-balloon">';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Name</span>';
                                $output .= $spotter_item['departure_airport_name'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>City</span>';
                                $output .= $spotter_item['departure_airport_city'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Country</span>';
                                $output .= $spotter_item['departure_airport_country'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>ICAO</span>';
                                $output .= $spotter_item['departure_airport_icao'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>IATA</span>';
                                $output .= $spotter_item['departure_airport_iata'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Coordinates</span>';
                                $output .= $spotter_item['departure_airport_latitude'].', '.$spotter_item['departure_airport_longitude'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Altitude</span>';
                                $output .= $spotter_item['departure_airport_altitude'];
                            $output .= '</div>';
                         $output .= '</div>';
                    $output .= ' ]]></description>';
					$output .= '<styleUrl>#departureAirport</styleUrl>';
					$output .= '<Point>';
						$output .=  '<coordinates>'.$spotter_item['departure_airport_longitude'].', '.$spotter_item['departure_airport_latitude'].', '.$spotter_item['departure_airport_altitude'].'</coordinates>';
						$output .= '<altitudeMode>absolute</altitudeMode>';
					$output .= '</Point>';
				$output .= '</Placemark>'; 
				
				//arrival airport 
				$output .= '<Placemark>';  
					$output .= '<name>'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</name>';
                    $output .= '<description><![CDATA[ ';
                        $output .= '<div class="ge-balloon">';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Name</span>';
                                $output .= $spotter_item['arrival_airport_name'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>City</span>';
                                $output .= $spotter_item['arrival_airport_city'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Country</span>';
                                $output .= $spotter_item['arrival_airport_country'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>ICAO</span>';
                                $output .= $spotter_item['arrival_airport_icao'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>IATA</span>';
                                $output .= $spotter_item['arrival_airport_iata'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Coordinates</span>';
                                $output .= $spotter_item['arrival_airport_latitude'].', '.$spotter_item['arrival_airport_longitude'];
                            $output .= '</div>';
                            $output .= '<div class="ge-row">';
                                $output .= '<span>Altitude</span>';
                                $output .= $spotter_item['arrival_airport_altitude'];
                            $output .= '</div>';
                         $output .= '</div>';
                    $output .= ' ]]></description>';
					$output .= '<styleUrl>#arrivalAirport</styleUrl>';
					$output .= '<Point>';
						$output .=  '<coordinates>'.$spotter_item['arrival_airport_longitude'].', '.$spotter_item['arrival_airport_latitude'].', '.$spotter_item['arrival_airport_altitude'].'</coordinates>';
						$output .= '<altitudeMode>absolute</altitudeMode>';
					$output .= '</Point>';
				$output .= '</Placemark>'; 

				
				//location of aircraft
				$output .= '<Placemark>';  
					$output .= '<name>'.$spotter_item['ident'].' - '.$spotter_item['registration'].' - '.$spotter_item['airline_name'].'</name>';
					$output .= '<description><![CDATA[ ';
                            $output .= '<div class="ge-balloon">';
                                if ($spotter_item['image_thumbnail'] != "")
                                {
                                    $output .= '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'" target="_blank"><img src="'.$spotter_item['image_thumbnail'].'" alt="Click on image to see Flight profile" title="Click on image to see Flight profile" class="ge-image" /></a>';
                                }
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Ident</span>';
                                    $output .= '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'" target="_blank">'.$spotter_item['ident'].'</a>';
                                $output .= '</div>';
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Registration</span>';
                                    $output .= '<a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'" target="_blank">'.$spotter_item['registration'].'</a>';
                                $output .= '</div>';
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Aircraft</span>';
                                    $output .= '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'" target="_blank">'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')</a>';
                                $output .= '</div>';
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Airline</span>';
                                    $output .= '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'" target="_blank">'.$spotter_item['airline_name'].'</a>';
                                $output .= '</div>';
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Departure Airport</span>';
                                    $output .= '<a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a>';
                                $output .= '</div>';
                                $output .= '<div class="ge-row">';
                                    $output .= '<span>Arrival Airport</span>';
                                    $output .= '<a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a>';
                                $output .= '</div>';
$output .= '<div class="ge-row">';
                                    $output .= '<span>Date</span>';
                                    $output .= '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'" target="_blank">'.date("D M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'</a>';
                                $output .= '</div>';
                        $output .= '</div>';
                    $output .= ' ]]></description>';
					$output .= '<styleUrl>#aircraft_'.$spotter_item['spotter_id'].'</styleUrl>';
					$output .= '<Point>';
						$output .=  '<coordinates>'.$spotter_item['longitude'].', '.$spotter_item['latitude'].', '.$altitude.'</coordinates>';
						$output .= '<altitudeMode>absolute</altitudeMode>';
					$output .= '</Point>';
				$output .= '</Placemark>'; 
				
				$output .= '<Style id="aircraft_'.$spotter_item['spotter_id'].'">';
		    	$output .= '<IconStyle>';
		      	$output .= '<Icon>';
		        	$output .= '<href>http://barriespotter.com/images/kml_aircraft.png</href>';
		         $output .= '</Icon>';
		         $output .= '<heading>'.$spotter_item['heading'].'</heading>';
		        $output .= '</IconStyle>';
		    $output .= '</Style>';
	    }
	   }
	   
		 $output .= '</Document>';
$output .= '</kml>';

print $output;

?>