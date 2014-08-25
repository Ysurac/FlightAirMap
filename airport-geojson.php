<?php
require('../require/class.Connection.php');
require('../require/class.Spotter.php');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="airports.geojson"');
}
header('Content-Type: text/javascript');

if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
	$spotter_array = Spotter::getAllAirportInfobyCoord($coords);
} else {
	$spotter_array = Spotter::getAllAirportInfobyCountry(array('France','Switzerland'));
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
			$output .= '"name": "'.$spotter_item['name'].'",';
			$output .= '"city": "'.$spotter_item['city'].'",';
			$output .= '"country": "'.$spotter_item['country'].'",';
			$output .= '"altitude": "'.$spotter_item['altitude'].'",';
			$output .= '"popupContent": "'.$spotter_item['name'].' : '.$spotter_item['city'].', '.$spotter_item['country'].'"';
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
}
$output  = substr($output, 0, -1);
$output .= ']}';

print $output;

?>