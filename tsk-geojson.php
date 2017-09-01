<?php
require_once('require/class.Connection.php');
require_once('require/class.TSK.php');
$TSK = new TSK();
if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="tsk.geojson"');
}
header('Content-Type: text/javascript');

$tskfile = filter_input(INPUT_GET,'tsk',FILTER_SANITIZE_URL);

$spotter_array = $TSK->parse_xml($tskfile);

$output = '{"type": "FeatureCollection","features": [';
if (!empty($spotter_array))
{	  
	$j = 0;
	foreach($spotter_array['Point'] as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$id = $spotter_item['Waypoint']['@attributes']['id'];
		if ($id == 0 || !is_numeric($id)) $id = $j;
		$output .= '{"type": "Feature",';
		    $output .= '"id": '.$id.',';
		    $output .= '"properties": {';
			$output .= '"type": "'.$spotter_item['@attributes']['type'].'",';
			//$output .= '"id": "'.$spotter_item['Waypoint']['@attributes']['id'].'",';
			$output .= '"name": "'.$spotter_item['Waypoint']['@attributes']['name'].'",';
			$output .= '"altitude": "'.$spotter_item['Waypoint']['@attributes']['altitude'].'",';
			//   $output .= '"color": "#EACC04",';
			if ($spotter_item['@attributes']['type'] == 'Start') {
				$output .= '"icon": "/images/tsk/tsk-start.png"';
			} elseif ($spotter_item['@attributes']['type'] == 'Finish') {
				$output .= '"icon": "/images/tsk/tsk-finish.png"';
			} else $output .= '"icon": "/images/tsk/number_'.$id.'.png"';
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['Waypoint']['Location']['@attributes']['longitude'].', '.$spotter_item['Waypoint']['Location']['@attributes']['latitude'];
			$output .= ']';
		    $output .= '}';
		$output .= '},';
		$j++;
	}
	// Lines
	$output .= '{"type": "Feature",';
	    $output .= '"properties": {';
		$output .= '"type": "'.$spotter_array['@attributes']['type'].'"';
	    $output .= '},';
	    $output .= '"geometry": {';
		$output .= '"type": "LineString",';
		$output .= '"coordinates": [';
	foreach($spotter_array['Point'] as $spotter_item)
	{
			    $output .= '['.$spotter_item['Waypoint']['Location']['@attributes']['longitude'].', '.$spotter_item['Waypoint']['Location']['@attributes']['latitude'].'],';
	}
	$output  = substr($output, 0, -1);
		$output .= ']';
	    $output .= '}';
	$output .= '},';
	$output  = substr($output, 0, -1);
}
$output .= ']}';

print $output;

?>