<?php
require_once('require/class.Connection.php');
include_once('require/libs/geoPHP/geoPHP.inc');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="airspace.geojson"');
}
header('Content-Type: text/javascript');

$Connection = new Connection();

if (!$Connection->tableExists('airspace')) {
    die;
}

if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
        if ($globalDBdriver == 'mysql') {
		$query = "SELECT *, ST_AsWKB(SHAPE) AS wkb FROM airspace WHERE ST_Intersects(SHAPE, ST_Envelope(linestring(point(:minlon,:minlat), point(:maxlon,:maxlat))))";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':minlon' => $coords[0],':minlat' => $coords[1],':maxlon' => $coords[2],':maxlat' => $coords[3]));
			//$sth->execute();
		} catch(PDOException $e) {
			echo "error";
		}
	} else {
		$query = "SELECT *, ST_AsBinary(wkb_geometry,'NDR') AS wkb FROM airspace WHERE wkb_geometry && ST_MakeEnvelope(".$coords[0].",".$coords[1].",".$coords[2].",".$coords[3].",4326)";
		try {
			$sth = $Connection->db->prepare($query);
			//$sth->execute(array(':minlon' => $coords[0],':minlat' => $coords[1],':maxlon' => $coords[2],':maxlat' => $coords[3]));
			$sth->execute();
		} catch(PDOException $e) {
			echo "error";
		}
	}
} else {
        if ($globalDBdriver == 'mysql') {
		$query = "SELECT *, ST_AsWKB(SHAPE) AS wkb FROM airspace";
	} else {
		$query = "SELECT *, ST_AsBinary(wkb_geometry,'NDR') AS wkb FROM airspace";
	}
	try {
		$sth = $Connection->db->prepare($query);
		$sth->execute();
	} catch(PDOException $e) {
		echo "error";
	}
}

$geojson = array(
    'type' => 'FeatureCollection',
    'features' => array()
);

while ($row = $sth->fetch(PDO::FETCH_ASSOC))
{
		date_default_timezone_set('UTC');
		$properties = $row;
		unset($properties['wkb_geometry']);
		unset($properties['wkb']);
		unset($properties['shape']);
		//print_r($properties);
		if ($globalDBdriver == 'mysql') {
			$geom = geoPHP::load($row['wkb']);
		} else {
			$geom = geoPHP::load(stream_get_contents($row['wkb']));
		}
		if (isset($properties['type'])) $properties['type'] = trim($properties['type']);
		elseif (isset($properties['class'])) $properties['type'] = trim($properties['class']);
		if (isset($properties['ogr_fid'])) $properties['id'] = $properties['ogr_fid'];
		elseif (isset($properties['ogc_fid'])) $properties['id'] = $properties['ogc_fid'];
		if (isset($properties['ceiling'])) $properties['tops'] = $properties['ceiling'];
		if (isset($properties['floor'])) $properties['base'] = $properties['floor'];
		if (isset($properties['tops'])) {
			if (preg_match('/^FL(\s)*(?<alt>\d+)/',strtoupper($properties['tops']),$matches)) {
				$properties['upper_limit'] = round($matches['alt']*100*0.38048);
			} elseif (preg_match('/^(?<alt>\d+)(\s)*(FT|AGL|ALT|MSL)/',strtoupper($properties['tops']),$matches)) {
				$properties['upper_limit'] = round($matches['alt']*0.38048);
			} elseif (preg_match('/^(?<alt>\d+)(\s)*M/',strtoupper($properties['tops']),$matches)) {
				$properties['upper_limit'] = $matches['alt'];
			}
		}
		if (isset($properties['base'])) {
			if ($properties['base'] == 'SFC' || $properties['base'] == 'MSL' || $properties['base'] == 'GROUND' || $properties['base'] == 'GND') {
				$properties['lower_limit'] = 0;
			} elseif (preg_match('/^FL(\s)*(?<alt>\d+)/',strtoupper($properties['base']),$matches)) {
				$properties['lower_limit'] = round($matches['alt']*100*0.38048);
			} elseif (preg_match('/^(?<alt>\d+)(\s)*(FT|AGL|ALT|MSL)/',strtoupper($properties['base']),$matches)) {
				$properties['lower_limit'] = round($matches['alt']*0.38048);
			} elseif (preg_match('/^(?<alt>\d+)(\s)*M/',strtoupper($properties['base']),$matches)) {
				$properties['lower_limit'] = $matches['alt'];
			}
		}
		if ($properties['type'] == 'RESTRICTED' || $properties['type'] == 'R') {
			$properties['color'] = '#cf2626';
		} elseif ($properties['type'] == 'CLASS D' || $properties['type'] == 'D') {
			$properties['color'] = '#1a74b3';
		} elseif ($properties['type'] == 'CLASS B' || $properties['type'] == 'B') {
			$properties['color'] = '#1a74b3';
		} elseif ($properties['type'] == 'CLASS C' || $properties['type'] == 'C') {
			$properties['color'] = '#9b6c9d';
		} elseif ($properties['type'] == 'GSEC' || $properties['type'] == 'G') {
			$properties['color'] = '#1b5acf';
		} elseif ($properties['type'] == 'PROHIBITED' || $properties['type'] == 'P') {
			$properties['color'] = '#1b5acf';
		} elseif ($properties['type'] == 'DANGER' || $properties['type'] == 'W') {
			$properties['color'] = '#781212';
		} elseif ($properties['type'] == 'OTHER' || $properties['type'] == 'O') {
			$properties['color'] = '#ffff7f';
		} else {
			$properties['color'] = '#d9ffcb';
		}
		if (isset($properties['type']) && $properties['type'] != '') {
			$feature = array(
			    'type' => 'Feature',
			    'geometry' => json_decode($geom->out('json')),
			    'properties' => $properties
			);
			array_push($geojson['features'], $feature);
		}
}
print json_encode($geojson, JSON_NUMERIC_CHECK);

?>