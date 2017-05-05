<?php
require_once('require/class.Connection.php');
require_once('require/class.ATC.php');

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
			$output .= '"ref": "'.$spotter_item['atc_id'].'",';
			$output .= '"ident": "'.$spotter_item['ident'].'",';
			$output .= '"frequency": "'.$spotter_item['frequency'].'",';
			$output .= '"atc_range": "0",';
			$output .= '"ivao_id": "'.$spotter_item['ivao_id'].'",';
			$output .= '"ivao_name": "'.$spotter_item['ivao_name'].'",';
			$output .= '"info": "'.$spotter_item['info'].'",';
			$output .= '"type": "'.$spotter_item['type'].'",';
			if ($spotter_item['type'] == 'Delivery') {
				$output .= '"icon": "images/atc_del.png"';
			} else if ($spotter_item['type'] == 'Ground') {
				$output .= '"icon": "images/atc_gnd.png"';
			} else if ($spotter_item['type'] == 'Tower') {
				$output .= '"icon": "images/atc_twr.png"';
			} else if ($spotter_item['type'] == 'Approach') {
				$output .= '"icon": "images/atc_app.png"';
			} else if ($spotter_item['type'] == 'Departure') {
				$output .= '"icon": "images/atc_dep.png"';
			} else if ($spotter_item['type'] == 'Observer') {
				$output .= '"icon": "images/atc.png"';
			} else if ($spotter_item['type'] == 'Control Radar or Centre') {
				$output .= '"icon": "images/atc_ctr.png"';
			} else {
				$output .= '"icon": "images/atc.png"';
			}
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude'].', '.$spotter_item['latitude'];
			$output .= ']';
		    $output .= '}';
		$output .= '},';
		$radius = $spotter_item['atc_range']*100;
		if ($radius > 0) {
			$output .= '{"type": "Feature",';
			    $output .= '"properties": {';
				$output .= '"ref": "'.$spotter_item['atc_id'].'",';
				$output .= '"ident": "'.$spotter_item['ident'].'",';
				$output .= '"frequency": "'.$spotter_item['frequency'].'",';
				$output .= '"atc_range": "'.$radius.'",';
				$output .= '"ivao_id": "'.$spotter_item['ivao_id'].'",';
				$output .= '"ivao_name": "'.$spotter_item['ivao_name'].'",';
				$output .= '"info": "'.$spotter_item['info'].'",';
				$output .= '"type": "'.$spotter_item['type'].'",';
				if ($spotter_item['type'] == 'Delivery') {
					$output .= '"atccolor": "#781212"';
				} else if ($spotter_item['type'] == 'Ground') {
					$output .= '"atccolor": "#682213"';
				} else if ($spotter_item['type'] == 'Tower') {
					$output .= '"atccolor": "#583214"';
				} else if ($spotter_item['type'] == 'Approach') {
					$output .= '"atccolor": "#484215"';
				} else if ($spotter_item['type'] == 'Departure') {
					$output .= '"atccolor": "#385216"';
				} else if ($spotter_item['type'] == 'Observer') {
					$output .= '"atccolor": "#286217"';
				} else if ($spotter_item['type'] == 'Control Radar or Centre') {
					$output .= '"atccolor": "#187218"';
				} else {
					$output .= '"atccolor": "#888219"';
				}
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
} else {
	$output .= '[';
}
$output .= ']}';

print $output;

?>