<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
require_once('require/class.Stats.php');
require_once('require/class.Source.php');
$begintime = microtime(true);
$Stats = new Stats();
$Location = new Source();
$Common = new Common();

if (isset($_GET['download'])) {
	if ($_GET['download'] == "true")
	{
		header('Content-disposition: attachment; filename="flightairmap-polar.json"');
	}
}
header('Content-Type: text/javascript');


$polar = $Stats->getStatsSource('polar',date('Y'),date('m'),date('d'));
$output = '{"type": "FeatureCollection","features": [';
if (!empty($polar)) {
	foreach($polar as $eachpolar) {
		$data = json_decode($eachpolar['source_data']);
		$name = $eachpolar['source_name'];
		$coord = $Location->getLocationInfobySourceName($name);
		$output .= '{"type": "Feature","properties": {"name": "'.$name.'","style": {"color": "#B5DAB1", "opacity": 1.0}},"geometry": {"type": "Polygon","coordinates": [[';
		if (isset($coord[0]['latitude'])) {
			$initial_latitude = $coord[0]['latitude'];
			$initial_longitude = $coord[0]['longitude'];
		} else {
			$initial_latitude = $globalCenterLatitude;
			$initial_longitude = $globalCenterLongitude;
		}
		$first = '';
		foreach($data as $value => $key) {
			$final_coord = $Common->getCoordfromDistanceBearing($initial_latitude,$initial_longitude,$value*22.5,$key);
			if ($first == '') $first = '['.round($final_coord['longitude'],5).','.round($final_coord['latitude'],5).']';
			$output .= '['.$final_coord['longitude'].','.$final_coord['latitude'].'],';
		}
		$output .= $first;
		$output .= ']]}},';
	}
	$output  = substr($output, 0, -1);
}
$output .= ']}';
print $output;

?>
