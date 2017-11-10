<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="airports.geojson"');
}
header('Content-Type: text/javascript');

if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
	$spotter_array = $Spotter->getAllAirportInfobyCoord($coords);
} else {
	$spotter_array = $Spotter->getAllAirportInfo();
	//$spotter_array = $Spotter->getAllAirportInfobyCountry(array('France','Switzerland'));
}
      
$output = '{"type": "FeatureCollection","features": [';
            
if (!empty($spotter_array))
{	  
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"name": '.json_encode(str_replace('"',"'",$spotter_item['name'])).',';
			$output .= '"city": '.json_encode(str_replace('"',"'",$spotter_item['city'])).',';
			$output .= '"country": "'.$spotter_item['country'].'",';
			$output .= '"altitude": "'.$spotter_item['altitude'].'",';
			$output .= '"popupContent": '.json_encode(str_replace('"',"'",$spotter_item['name']).' : '.str_replace('"',"'",$spotter_item['city']).', '.$spotter_item['country']).',';
			if ($spotter_item['type'] == 'large_airport') {
				$output .= '"icon": "'.$globalURL.'/images/airport.png",';
			} elseif ($spotter_item['type'] == 'heliport') {
				$output .= '"icon": "'.$globalURL.'/images/heliport.png",';
			} elseif ($spotter_item['type'] == 'military') {
				$output .= '"icon": "'.$globalURL.'/images/military.png",';
			} elseif ($spotter_item['type'] == 'medium_airport') {
				$output .= '"icon": "'.$globalURL.'/images/medium_airport.png",';
			} else {
				$output .= '"icon": "'.$globalURL.'/images/small_airport.png",';
			}
			$output .= '"type": "'.$spotter_item['type'].'",';
			$output .= '"icao": "'.$spotter_item['icao'].'",';
			$output .= '"iata": "'.$spotter_item['iata'].'",';
			$output .= '"homepage": "'.$spotter_item['home_link'].'",';
			$output .= '"image_thumb": "'.$spotter_item['image_thumb'].'"';
//			$output .= '"photo": "'.$spotter_item['image_thumbnail'].'",';
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude'].', '.$spotter_item['latitude'];
			$output .= ']';
		    $output .= '}';
		$output .= '},';
	}
	$output  = substr($output, 0, -1);
}
$output .= ']}';
print $output;
?>