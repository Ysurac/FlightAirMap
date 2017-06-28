<?php
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.SpotterLive.php');
require_once(dirname(__FILE__).'/class.SpotterArchive.php');
require_once(dirname(__FILE__).'/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.Translation.php');

class SpotterServer {
	public $dbs = null;

	function __construct($dbs = null) {
		if ($dbs === null) {
			$Connection = new Connection(null,'server');
			$this->dbs = $Connection->dbs;
			$query = "CREATE TABLE IF NOT EXISTS `spotter_temp` ( `id_data` INT NOT NULL AUTO_INCREMENT , `id_user` INT NOT NULL , `datetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `hex` VARCHAR(20) NOT NULL , `ident` VARCHAR(20) NULL , `latitude` FLOAT NULL , `longitude` FLOAT NULL , `verticalrate` INT NULL , `speed` INT NULL , `squawk` INT NULL , `altitude` INT NULL , `heading` INT NULL , `registration` VARCHAR(10) NULL , `aircraft_icao` VARCHAR(10) NULL , `waypoints` VARCHAR(255) NULL , `noarchive` BOOLEAN NOT NULL DEFAULT FALSE, `id_source` INT NOT NULL DEFAULT '1', `format_source` VARCHAR(25) NULL, `source_name` VARCHAR(25) NULL, `over_country` VARCHAR(255) NULL, PRIMARY KEY (`id_data`) ) ENGINE = MEMORY;";
			try {
				$sth = $this->dbs['server']->exec($query);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}

	function checkAll() {
		return true;
	}

	function add($line) {
		global $globalDebug, $globalServerUserID;
		date_default_timezone_set('UTC');
		//if (isset($line['format_source']) && ($line['format_source'] === 'sbs' || $line['format_source'] === 'tsv' || $line['format_source'] === 'raw' || $line['format_source'] === 'deltadbtxt' || $line['format_source'] === 'aprs')) {
		if (isset($line['format_source'])) {
			if(is_array($line) && isset($line['hex'])) {
				if ($line['hex'] != '' && $line['hex'] != '00000' && $line['hex'] != '000000' && $line['hex'] != '111111' && ctype_xdigit($line['hex']) && strlen($line['hex']) === 6) {
					$data['hex'] = trim($line['hex']);
					if (preg_match('/^(\d{4}(?:\-\d{2}){2} \d{2}(?:\:\d{2}){2})$/',$line['datetime'])) {
						$data['datetime'] = $line['datetime'];
					} else $data['datetime'] = date('Y-m-d H:i:s');
					if (!isset($line['aircraft_icao'])) {
						$Spotter = new Spotter();
						$aircraft_icao = $Spotter->getAllAircraftType($data['hex']);
						$Spotter->db = null;
						if ($aircraft_icao == '' && isset($line['aircraft_type'])) {
							if ($line['aircraft_type'] == 'PARA_GLIDER') $aircraft_icao = 'GLID';
							elseif ($line['aircraft_type'] == 'HELICOPTER_ROTORCRAFT') $aircraft_icao = 'UHEL';
							elseif ($line['aircraft_type'] == 'TOW_PLANE') $aircraft_icao = 'TOWPLANE';
							elseif ($line['aircraft_type'] == 'POWERED_AIRCRAFT') $aircraft_icao = 'POWAIRC';
						}
						$data['aircraft_icao'] = $aircraft_icao;
					} else $data['aircraft_icao'] = $line['aircraft_icao'];
					//if ($globalDebug) echo "*********** New aircraft hex : ".$data['hex']." ***********\n";
				}
				if (isset($line['registration']) && $line['registration'] != '') {
					$data['registration'] = $line['registration'];
				} else $data['registration'] = null;
				if (isset($line['waypoints']) && $line['waypoints'] != '') {
					$data['waypoints'] = $line['waypoints'];
				} else $data['waypoints'] = null;
				if (isset($line['ident']) && $line['ident'] != '' && $line['ident'] != '????????' && $line['ident'] != '00000000' && preg_match('/^[a-zA-Z0-9]+$/', $line['ident'])) {
					$data['ident'] = trim($line['ident']);
				} else $data['ident'] = null;
				if (isset($line['latitude']) && isset($line['longitude']) && $line['latitude'] != '' && $line['longitude'] != '') {
					if (isset($line['latitude']) && $line['latitude'] != '' && $line['latitude'] != 0 && $line['latitude'] < 91 && $line['latitude'] > -90) {
						$data['latitude'] = $line['latitude'];
					} else $data['latitude'] = null;
					if (isset($line['longitude']) && $line['longitude'] != '' && $line['longitude'] != 0 && $line['longitude'] < 360 && $line['longitude'] > -180) {
						if ($line['longitude'] > 180) $line['longitude'] = $line['longitude'] - 360;
						$data['longitude'] = $line['longitude'];
					} else $data['longitude'] = null;
				} else {
					$data['latitude'] = null;
					$data['longitude'] = null;
				}
				if (isset($line['verticalrate']) && $line['verticalrate'] != '') {
					$data['verticalrate'] = $line['verticalrate'];
				} else $data['verticalrate'] = null;
				if (isset($line['emergency']) && $line['emergency'] != '') {
					$data['emergency'] = $line['emergency'];
				} else $data['emergency'] = null;
				if (isset($line['ground']) && $line['ground'] != '') {
					$data['ground'] = $line['ground'];
				} else $data['ground'] = null;
				if (isset($line['speed']) && $line['speed'] != '') {
					$data['speed'] = round($line['speed']);
				} else $data['speed'] = null;
				if (isset($line['squawk']) && $line['squawk'] != '') {
					$data['squawk'] = $line['squawk'];
				} else $data['squawk'] = null;
				if (isset($line['altitude']) && $line['altitude'] != '') {
					$data['altitude'] = round($line['altitude']);
		  		} else $data['altitude'] = null;
				if (isset($line['heading']) && $line['heading'] != '') {
					$data['heading'] = round($line['heading']);
		 		} else $data['heading'] = null;
				if (isset($line['source_name']) && $line['source_name'] != '') {
					$data['source_name'] = $line['source_name'];
				} else $data['source_name'] = null;
				if (isset($line['over_country']) && $line['over_country'] != '') {
					$data['over_country'] = $line['over_country'];
		 		} else $data['over_country'] = null;
				if (isset($line['noarchive']) && $line['noarchive']) {
					$data['noarchive'] = true;
				} else $data['noarchive'] = false;
				$data['format_source'] = $line['format_source'];
				if (isset($line['id_source'])) $id_source = $line['id_source'];
				if (isset($data['hex'])) {
					echo '.';
					$id_user = $globalServerUserID;
					if ($id_user == NULL) $id_user = 1;
					if (!isset($id_source)) $id_source = 1;
					$query = 'INSERT INTO spotter_temp (id_user,datetime,hex,ident,latitude,longitude,verticalrate,speed,squawk,altitude,heading,registration,aircraft_icao,waypoints,id_source,noarchive,format_source,source_name,over_country) VALUES (:id_user,:datetime,:hex,:ident,:latitude,:longitude,:verticalrate,:speed,:squawk,:altitude,:heading,:registration,:aircraft_icao,:waypoints,:id_source,:noarchive, :format_source, :source_name, :over_country)';
					$query_values = array(':id_user' => $id_user,':datetime' => $data['datetime'],':hex' => $data['hex'],':ident' => $data['ident'],':latitude' => $data['latitude'],':longitude' => $data['longitude'],':verticalrate' => $data['verticalrate'],':speed' => $data['speed'],':squawk' => $data['squawk'],':altitude' => $data['altitude'],':heading' => $data['heading'],':registration' => $data['registration'],':aircraft_icao' => $data['aircraft_icao'],':waypoints' => $data['waypoints'],':id_source' => $id_source,':noarchive' => $data['noarchive'], ':format_source' => $data['format_source'], ':source_name' => $data['source_name'],':over_country' => $data['over_country']);
					try {
						$sth = $this->dbs['server']->prepare($query);
						$sth->execute($query_values);
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
		}
	}
}
?>
