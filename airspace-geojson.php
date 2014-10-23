<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
include_once('require/libs/geoPHP/geoPHP.inc');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="airspace.geojson"');
}
header('Content-Type: text/javascript');

$Connection = new Connection();

if (!Connection::tableExists('airspace')) {
    die;
}

if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
	$query = "SELECT *, AsWKB(SHAPE) AS wkb FROM airspace WHERE ST_Intersects(SHAPE, envelope(linestring(point(:minlon,:minlat), point(:maxlon,:maxlat))))";
	try {
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':minlon' => $coords[0],':minlat' => $coords[1],':maxlon' => $coords[2],':maxlat' => $coords[3]));
	} catch(PDOException $e) {
		return "error";
	}
} else {

	$query = "SELECT *, AsWKB(SHAPE) AS wkb FROM airspace";
	try {
		$sth = Connection::$db->prepare($query);
		$sth->execute();
	} catch(PDOException $e) {
		return "error";
	}
}

function wkb_to_json($wkb) {
	$geom = geoPHP::load($wkb,'wkb');
	return $geom->out('json');
}
      

$geojson = array(
    'type' => 'FeatureCollection',
    'features' => array()
);

while ($row = $sth->fetch(PDO::FETCH_ASSOC))
{	  
		date_default_timezone_set('UTC');
		$properties = $row;
		unset($properties['wkb']);
		unset($properties['SHAPE']);

		$feature = array(
		    'type' => 'Feature',
		    'geometry' => json_decode(wkb_to_json($row['wkb'])),
		    'properties' => $properties
		);
		
		array_push($geojson['features'], $feature);
}
print json_encode($geojson, JSON_NUMERIC_CHECK);

?>