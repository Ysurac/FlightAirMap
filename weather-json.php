<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Weather.php');
require_once('require/class.METAR.php');
$Spotter = new Spotter();
if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="weather.json"');
}
header('Content-Type: text/javascript');
$latitude = filter_input(INPUT_GET,'latitude',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_input(INPUT_GET,'longitude',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
if ($latitude == '' || $longitude == '') return '';
//echo 'latitude : '.$latitude.' - longitude : '.$longitude."\n";
$airports = $Spotter->closestAirports($latitude,$longitude,200);
//print_r($airports);
$METAR = new METAR();
$Weather = new Weather();
$i = 0;
$ew = true;
while($ew) {
	$met = $METAR->getMETAR($airports[$i]['icao']);
	//print_r($met);
	if (!empty($met)) {
		$parsed = $METAR->parse($met[0]['metar']);
		//print_r($parsed);
		if (isset($parsed['weather']) && $parsed['weather'] == 'CAVOK') {
			echo json_encode(array());
		} elseif (isset($parsed['cloud'])) {
			$result = $Weather->buildcloudlayer($parsed);
			if (!empty($result)) {
				//print_r($met);
				//print_r($parsed);
				echo json_encode($result);
				$ew = false;
			}
		}
	}
	$i++;
	if ($i >= count($airports)) $ew = false;
}

?>