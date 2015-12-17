<?php
require('require/class.Connection.php');
require('require/class.ATC.php');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="atc.geojson"');
}
header('Content-Type: text/javascript');
$ATC=new ATC();
if (isset($_GET['coord'])) 
{
	//$coords = explode(',',$_GET['coord']);
	$spotter_array = $ATC->getAll();
} else {
	$spotter_array = $ATC->getAll();
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
			$output .= '"ident": "'.$spotter_item['ident'].'",';
			$output .= '"frequency": "'.$spotter_item['frequency'].'",';
			$radius = $spotter_item['atc_range']*100;
			$output .= '"atc_range": "'.$radius.'",';
			$output .= '"ivao_id": "'.$spotter_item['ivao_id'].'",';
			$output .= '"ivao_name": "'.$spotter_item['ivao_name'].'",';
			$output .= '"info": "'.$spotter_item['info'].'",';
			$output .= '"type": "'.$spotter_item['type'].'"';
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