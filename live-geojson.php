<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');

if ($_GET['download'] == "true")
{
	header('Content-disposition: attachment; filename="barriespotter.json"');
}

header('Content-Type: text/javascript');

$spotter_array = SpotterLive::getLiveSpotterData();

$output .= '{';
	$output .= '"type": "FeatureCollection",';
		$output .= '"features": [';

		if (!empty($spotter_array))
		{
			foreach($spotter_array as $spotter_item)
			{
				date_default_timezone_set('America/Toronto');

				if ($spotter_item['image_thumbnail'] != "")
				{
					$image = $spotter_item['image_thumbnail'];
				} else {
					$image = "http://www.barriespotter.com/dev/images/placeholder_thumb.png";
				}

				//waypoint plotting
				$output .= '{';
					$output .= '"type": "Feature",';
						$output .= '"properties": {';
                            $output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
							$output .= '"registration": "'.$spotter_item['registration'].'",';
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
							$output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
							$output .= '"departure_airport": "'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].'",';
							$output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
							$output .= '"arrival_airport": "'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].'",';
							$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
							$output .= '"altitude": "'.$spotter_item['altitude'].'",';
							$output .= '"heading": "'.$spotter_item['heading'].'",';
							$output .= '"image": "'.$image.'",';
							$output .= '"type": "route"';
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "LineString",';
								$output .= '"coordinates": [';
									$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
									$waypoint_pieces = array_chunk($waypoint_pieces, 2);

									foreach ($waypoint_pieces as $waypoint_coordinate)
									{
										$output .= '[';
													$output .=  $waypoint_coordinate[1].', ';
													$output .=  $waypoint_coordinate[0];
										$output .= '],';

									}
									$output = substr($output, 0, -1);
								$output .= ']';
							$output .= '}';
				$output .= '},';

				//location of aircraft
				$output .= '{';
					$output .= '"type": "Feature",';
						$output .= '"properties": {';
                            $output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
							$output .= '"registration": "'.$spotter_item['registration'].'",';
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
							$output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
							$output .= '"departure_airport": "'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].'",';
							$output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
							$output .= '"arrival_airport": "'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].'",';
							$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
							$output .= '"altitude": "'.$spotter_item['altitude'].'",';
							$output .= '"heading": "'.$spotter_item['heading'].'",';
							$output .= '"image": "'.$image.'",';
							$output .= '"type": "aircraft"';
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "Point",';
								$output .= '"coordinates": [';
										$output .=  $spotter_item['longitude'].', ';
										$output .=  $spotter_item['latitude'];
								$output .= ']';
							$output .= '}';
				$output .= '},';
			}
		}
		$output  = substr($output, 0, -1);

		$output .= ']';
$output .= '}';

print $output;

?>
