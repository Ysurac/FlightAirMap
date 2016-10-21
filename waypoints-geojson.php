<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="waypoints.geojson"');
}
header('Content-Type: text/javascript');

if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
	$spotter_array = $Spotter->getAllWaypointsInfobyCoord($coords);
} else {
	die;
}
      
$output = '{"type": "FeatureCollection","features": [';
            
if (!empty($spotter_array))
{	  
//	print_r($spotter_array);
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"segment_name": "'.$spotter_item['segment_name'].'",';
			$output .= '"base": "'.$spotter_item['base'].'",';
			$output .= '"top": "'.$spotter_item['top'].'",';
			$output .= '"name_begin": "'.$spotter_item['name_begin'].'",';
			$output .= '"name_end": "'.$spotter_item['name_end'].'",';
//			$output .= '"ident": "'.$spotter_item['name_begin'].'",';
//			$output .= '"popupContent": "'.$spotter_item['name_begin'].'",';
/*			if ($spotter_item['usage'] == 'RNAV') {
				$output .= '"icon": "images/flag_green.png"';
			} elseif ($spotter_item['usage'] == 'High Level') {
				$output .= '"icon": "images/flag_red.png"';
			} elseif ($spotter_item['usage'] == 'Low Level') {
				$output .= '"icon": "images/flag_yellow.png"';
			} elseif ($spotter_item['usage'] == 'High and Low Level') {
				$output .= '"icon": "images/flag_orange.png"';
			} elseif ($spotter_item['usage'] == 'Terminal') {
				$output .= '"icon": "images/flag_finish.png"';
			} else {*/
				$output .= '"icon": "images/flag_blue.png",';
				$output .= '"stroke": "#f0f0f0",';
				$output .= '"stroke-width": 2';
//			}
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "LineString",';
			$output .= '"coordinates": [';
			    //$output .= '['.$spotter_item['longitude_begin'].', '.$spotter_item['latitude_begin'].'], ['.$spotter_item['longitude_end'].', '.$spotter_item['latitude_end'].'], ['.$spotter_item['longitude_end_seg2'].', '.$spotter_item['latitude_end_seg2'].']';
			    $output .= '['.$spotter_item['longitude_begin'].', '.$spotter_item['latitude_begin'].','.round($spotter_item['base']*100*0.3048).'], ['.$spotter_item['longitude_end'].', '.$spotter_item['latitude_end'].','.round($spotter_item['base']*100*0.3048).']';
			//    $output .= '['.$spotter_item['latitude_begin'].', '.$spotter_item['longitude_begin'].'], ['.$spotter_item['latitude_end'].', '.$spotter_item['longitude_end'].']';
			$output .= ']';
		    $output .= '}';
/*		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude_begin'].', '.$spotter_item['latitude_begin'];
			$output .= ']';
		    $output .= '}';
*/
		$output .= '},';
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"ident": "'.$spotter_item['name_begin'].'",';
			$output .= '"high": "'.$spotter_item['high'].'",';
			$output .= '"alt": "'.$spotter_item['base'].'",';
//			$output .= '"popupContent": "'.$spotter_item['name_begin'].'",';
			if ($spotter_item['high'] == '') {
				$output .= '"icon": "images/flag_green.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#00aa00"';
			} elseif ($spotter_item['high'] == '2') {
				$output .= '"icon": "images/flag_red.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#ff0000"';
			} elseif ($spotter_item['high'] == '1') {
				$output .= '"icon": "images/flag_yellow.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#ffff00"';
//			} elseif ($spotter_item['usage'] == 'High and Low Level') {
//				$output .= '"icon": "images/flag_orange.png"';
//			} elseif ($spotter_item['usage'] == 'Terminal') {
//				$output .= '"icon": "images/flag_finish.png"';
			} else {
				$output .= '"icon": "images/flag_blue.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#0000ff"';
			}
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude_begin'].', '.$spotter_item['latitude_begin'].', '.round($spotter_item['base']*100*0.3048);;
			$output .= ']';
		    $output .= '}';

		$output .= '},';
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"ident": "'.$spotter_item['name_end'].'",';
			$output .= '"high": "'.$spotter_item['high'].'",';
			$output .= '"alt": "'.$spotter_item['top'].'",';
//			$output .= '"popupContent": "'.$spotter_item['name_begin'].'",';
			if ($spotter_item['high'] == '') {
				$output .= '"icon": "images/flag_green.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#00aa00"';
			} elseif ($spotter_item['high'] == '2') {
				$output .= '"icon": "images/flag_red.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#ff0000"';
			} elseif ($spotter_item['high'] == '1') {
				$output .= '"icon": "images/flag_yellow.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#ffff00"';
/*			if ($spotter_item['usage'] == 'RNAV') {
				$output .= '"icon": "images/flag_green.png"';
			} elseif ($spotter_item['usage'] == 'High Level') {
				$output .= '"icon": "images/flag_red.png"';
			} elseif ($spotter_item['usage'] == 'Low Level') {
				$output .= '"icon": "images/flag_yellow.png"';
			} elseif ($spotter_item['usage'] == 'High and Low Level') {
				$output .= '"icon": "images/flag_orange.png"';
			} elseif ($spotter_item['usage'] == 'Terminal') {
				$output .= '"icon": "images/flag_finish.png"';
*/
			} else {
				$output .= '"icon": "images/flag_blue.png",';
				$output .= '"marker-symbol": "marker",';
				$output .= '"marker-size": "small",';
				$output .= '"marker-color": "#0000ff"';
			}
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude_end'].', '.$spotter_item['latitude_end'].', '.round($spotter_item['base']*100*0.3048);
			$output .= ']';
		    $output .= '}';

		$output .= '},';
	}
}
$output  = substr($output, 0, -1);
$output .= ']}';

print $output;

?>