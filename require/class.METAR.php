<?php
/**
 * This class is part of FlightAirmap. It's used to download and parse METAR
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Common.php');

class METAR {
	public $db;
	
	protected $texts = Array(
	    'MI' => 'Shallow',
	    'PR' => 'Partial',
	    'BC' => 'Low drifting',
	    'BL' => 'Blowing',
	    'SH' => 'Showers',
	    'TS' => 'Thunderstorm',
	    'FZ' => 'Freezing',
	    'DZ' => 'Drizzle',
	    'RA' => 'Rain',
	    'SN' => 'Snow',
	    'SG' => 'Snow Grains',
	    'IC' => 'Ice crystals',
	    'PL' => 'Ice pellets',
	    'GR' => 'Hail',
	    'GS' => 'Small hail',
	    'UP' => 'Unknown',
	    'BR' => 'Mist',
	    'FG' => 'Fog',
	    'FU' => 'Smoke',
	    'VA' => 'Volcanic ash',
	    'DU' => 'Widespread dust',
	    'SA' => 'Sand',
	    'HZ' => 'Haze',
	    'PY' => 'Spray',
	    'PO' => 'Well developed dust / sand whirls',
	    'SQ' => 'Squalls',
	    'FC' => 'Funnel clouds inc tornadoes or waterspouts',
	    'SS' => 'Sandstorm',
	    'DS' => 'Duststorm'
	);

	/*
	 * Initialize db connection
	*/
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
	}

	/*
	 * Check in config table in DB if METAR was updated more than 20 minutes ago
	 * @return Boolean Return true if METAR was updated more than 20 minutes ago
	*/
	public static function check_last_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_metar' AND value > DATE_SUB(NOW(), INTERVAL 20 MINUTE)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_metar' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '20 MINUTES'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if ($row['nb'] > 0) return false;
		else return true;
	}

	/*
	 * Insert METAR update date in config table in DB
	*/
	public static function insert_last_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_metar';
		        INSERT INTO config (name,value) VALUES ('last_update_metar',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Parse METAR
	 * @param String $data METAR text
	 * @return Array All data in METAR available in an array
	*/
	public function parse($data) {
		//$data = str_replace(array('\n','\r','\r','\n'),'',$data);
		$codes = implode('|', array_keys($this->texts));
		$regWeather = '#^(\+|\-|VC)?(' . $codes . ')(' . $codes . ')?$#';
		//$pieces = explode(' ',$data);
		$pieces = preg_split('/\s/',$data);
		$pos = 0;
		if ($pieces[0] == 'METAR') $pos++;
		elseif ($pieces[0] == 'SPECI') $pos++;
		if (strlen($pieces[$pos]) != 4) $pos++;
		$result = array();
		if (!isset($pieces[$pos])) return $result;
		$result['location'] = $pieces[$pos];
		$pos++;
		if (!isset($pieces[$pos])) return $result;
		$result['dayofmonth'] = substr($pieces[$pos],0,2);
		$result['time'] = substr($pieces[$pos],2,4);
		$c = count($pieces);
		for($pos++; $pos < $c; $pos++) {
			$piece = $pieces[$pos];
			if ($piece == 'RMK') break;
			if ($piece == 'AUTO') $result['auto'] = true;
			if ($piece == 'COR') $result['correction'] = true;
			// Wind Speed
			if (preg_match('#(VRB|\d\d\d)(\d\d)(?:G(\d\d))?(KT|MPS|KPH)(?: (\d{1,3})V(\d{1,3}))?$#', $piece, $matches)) {
				$result['wind']['direction'] = (float)$matches[1];
				$result['wind']['unit'] = $matches[4];
				if ($result['wind']['unit'] == 'KT') $result['wind']['speed'] = round(((float)$matches[2])*0.51444444444,2);
				elseif ($result['wind']['unit'] == 'KPH') $result['wind']['speed'] = round(((float)$matches[2])*1000,2);
				elseif ($result['wind']['unit'] == 'MPS') $result['wind']['speed'] = round(((float)$matches[2]),2);
				$result['wind']['gust'] = (float)$matches[3];
				$result['wind']['unit'] = $matches[4];
				$result['wind']['min_variation'] = array_key_exists(5,$matches) ? $matches[5] : 0;
				$result['wind']['max_variation'] = array_key_exists(6,$matches) ? $matches[6] : 0;
			}

/*    			if (preg_match('#^([0-9]{3})([0-9]{2})(G([0-9]{2}))?(KT|MPS)$#', $piece, $matches)) {
    				$result['wind_direction'] = (float)$matches[1];
    				if ($matches[5] == 'KT') {
    					$result['speed'] = round(((float)$matches[2])*0.51444444444,2);
    				} elseif ($matches[5] == 'KPH') {
    					$result['speed'] = round(((float)$matches[2])*1000,2);
    				} elseif ($matches[5] == 'MPS') {
    					$result['speed'] = round(((float)$matches[2]),2);
    				}
    				if ($matches[3]) {
    				    $result['gust'] = $matches[4];
    				    $result['gust_format'] = $matches[5];
    				}
    			}
    			*/

			// Temperature
			if (preg_match('#^(M?[0-9]{2,})/(M?[0-9]{2,})$#', $piece, $matches)) {
				$temp = (float)$matches[1];
				if ($matches[1]{0} == 'M') {
					$temp = ((float)substr($matches[1], 1)) * -1;
				}
				$result['temperature'] = $temp;
				$dew = (float)$matches[2];
				if ($matches[2]{0} == 'M') {
					$dew = ((float)substr($matches[2], 1)) * -1;
				}
				$result['dew'] = $dew;
				$result['rh'] = round(100*(exp((17.625*$dew)/(243.04+$dew))/exp((17.625*$temp)/(243.04+$temp))));
			}
			// QNH
			if (preg_match('#^(A|Q)([0-9]{4})$#', $piece, $matches)) {
			// #^(Q|A)(////|[0-9]{4})( )#
				if ($matches[1] == 'Q') {
					// hPa
					$result['QNH'] = $matches[2];
				} else {
					// inHg
					$result['QNH'] = round(($matches[2] / 100)*33.86389,2);
				}
				/*
    				$result['QNH'] = $matches[1] == 'Q' ? $matches[2] : ($matches[2] / 100);
    				$result['QNH_format'] = $matches[1] == 'Q' ? 'hPa' : 'inHg';
    				*/
			}
                     /*
    			// Wind Direction
    			if (preg_match('#^([0-9]{3})V([0-9]{3})$#', $piece, $matches)) {
    				$result['wind_direction'] = $matches[1];
    				$result['wind_direction_other'] = $matches[2];
    			}
    			// Wind Speed Variable
    			if (preg_match('#^VRB([0-9]{2})KT$#', $piece, $matches)) {
    				$result['speed_variable'] = $matches[1];
    			}
    			*/
    			// Visibility
			if (preg_match('#^([0-9]{4})|(([0-9]{1,4})SM)$#', $piece, $matches)) {
				if (isset($matches[3]) && strlen($matches[3]) > 0) {
					$result['visibility'] = (float)$matches[3] * 1609.34;
				} else {
					if ($matches[1] == '9999') {
						$result['visibility'] = '> 10000';
					} else {
						$result['visibility'] = (float)$matches[1];
					}
				}
			}
			if (preg_match('#^CAVOK$#', $piece, $matches)) {
				$result['visibility'] = '> 10000';
				$result['weather'] = "CAVOK";
			}
			
			// Cloud Coverage
			if (preg_match('#^(SKC|CLR|FEW|SCT|BKN|OVC|VV)([0-9]{3})(AC|CB|CBS|CC|CS|TCU|CU|CI|///)?$#', $piece, $matches)) {
				//$this->addCloudCover($matches[1], ((float)$matches[2]) * 100, isset($matches[3]) ? $matches[3] : '');
				$type = $matches[1];
				$cloud = array();
				if ($type == 'SKC') $cloud['type'] = 'No cloud/Sky clear';
				elseif ($type == 'CLR') $cloud['type'] = 'No cloud below 12,000ft (3700m)';
				elseif ($type == 'NSC') $cloud['type'] = 'No significant cloud';
				elseif ($type == 'FEW') $cloud['type'] = 'Few';
				elseif ($type == 'SCT') $cloud['type'] = 'Scattered';
				elseif ($type == 'BKN') $cloud['type'] = 'Broken';
				elseif ($type == 'OVC') $cloud['type'] = 'Overcast/Full cloud coverage';
				elseif ($type == 'VV') $cloud['type'] = 'Vertical visibility';
				$cloud['type_code'] = $type;
				$cloud['level'] = round(((float)$matches[2]) * 100 * 0.3048);
				if (isset($matches[3])) $significant = $matches[3];
				else $significant = '';
				if ($significant == 'CB') $cloud['significant'] = 'Cumulonimbus';
				elseif ($significant == 'AC') $cloud['significant'] = 'Altocumulus';
				elseif ($significant == 'CBS') $cloud['significant'] = 'Cumulonimbus';
				elseif ($significant == 'CC') $cloud['significant'] = 'Cirrocumulus';
				elseif ($significant == 'CU') $cloud['significant'] = 'Cumulus';
				elseif ($significant == 'CI') $cloud['significant'] = 'Cirrus';
				elseif ($significant == 'CS') $cloud['significant'] = 'Cirrostratus';
				elseif ($significant == 'TCU') $cloud['significant'] = 'Towering Cumulus';
				else $cloud['significant'] = $significant;
				//$cloud['significant'] = isset($matches[3]) ? $matches[3] : '';
				$result['cloud'][] = $cloud;
			}
			// RVR
			if (preg_match('#^(R.+)/([M|P])?(\d{4})(?:V(\d+)|[UDN])?(FT)?$#', $piece, $matches)) {
				$rvr = array();
				$rvr['runway'] = $matches[1];
				$rvr['assessment'] = $matches[2];
				$rvr['rvr'] = $matches[3];
				$rvr['rvr_max'] = array_key_exists(4,$matches) ? $matches[4] : 0;
				$rvr['unit'] = array_key_exists(5,$matches) ? $matches[5] : '';
				$result['RVR'] = $rvr;
			}
			//if (preg_match('#^(R[A-Z0-9]{2,3})/([0-9]{4})(V([0-9]{4}))?(FT)?$#', $piece, $matches)) {
			if (preg_match('#^R(\d{2}[LRC]?)/([\d/])([\d/])([\d/]{2})([\d/]{2})$#', $piece, $matches)) {
				// https://github.com/davidmegginson/metar-taf/blob/master/Metar.php
				$result['RVR']['runway'] = $matches[1];
				$result['RVR']['deposits'] = $matches[2];
				$result['RVR']['extent'] = $matches[3];
				$result['RVR']['depth'] = $matches[4];
				$result['RVR']['friction'] = $matches[5];
			}
			if (preg_match('#^(R[A-Z0-9]{2,3})/([0-9]{4})(V([0-9]{4}))?(FT)?$#', $piece, $matches)) {
				if (isset($matches[5])) $range = array('exact' => (float)$matches[2], 'unit' => $matches[5] ? 'FT' : 'M');
				else $range = array('exact' => (float)$matches[2], 'unit' => 'M');
				if (isset($matches[3])) {
					$range = Array(
					    'from' => (float)$matches[2],
					    'to'   => (float)$matches[4],
					    'unit' => $matches[5] ? 'FT' : 'M'
					);
				}
				$result['RVR'] = $matches[1];
				$result['RVR_range'] = $range;
			}
			// Weather
			if (preg_match($regWeather, $piece, $matches)) {
				$text = Array();
				switch ($matches[1]) {
					case '+':
						$text[] = 'Heavy';
						break;
					case '-':
						$text[] = 'Light';
						break;
					case 'VC':
						$text[] = 'Vicinity';
						break;
					default:
						break;
				}
				if (isset($matches[2])) {
					$text[] = $this->texts[$matches[2]];
				}
				if (isset($matches[3])) {
					$text[] = $this->texts[$matches[3]];
				}
				if (!isset($result['weather'])) $result['weather'] = implode(' ', $text);
				else $result['weather'] = $result['weather'].' / '.implode(' ', $text);
			}
		}
		return $result;
	}

	/*
	 * Get METAR from an airport ICAO
	 * @param String $icao Airport ICAO
	 * @return Array Return array with metar date, location and text
	*/
	public function getMETAR($icao) {
		global $globalMETARcycle, $globalDBdriver;
		if (isset($globalMETARcycle) && $globalMETARcycle) {
			$query = "SELECT * FROM metar WHERE metar_location = :icao";
		} else {
			if ($globalDBdriver == 'mysql') $query = "SELECT * FROM metar WHERE metar_location = :icao AND metar_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 HOUR) LIMIT 1";
			else $query = "SELECT * FROM metar WHERE metar_location = :icao AND metar_date >= now() AT TIMEZONE 'UTC' - '10 HOUR'->INTERVAL LIMIT 0,1";
		}
		$query_values = array(':icao' => $icao);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if ((!isset($globalMETARcycle) || $globalMETARcycle === false) && count($all) == 0) {
			$all = $this->downloadMETAR($icao);
		}
		return $all;
	}

	/*
	 * Add METAR in DB
	 * @param String $location Airport ICAO
	 * @param String $metar METAR text
	 * @param String $date date of the METAR
	*/
	public function addMETAR($location,$metar,$date) {
		global $globalDBdriver;
		$date = date('Y-m-d H:i:s',strtotime($date));
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO metar (metar_location,metar_date,metar) VALUES (:location,:date,:metar) ON DUPLICATE KEY UPDATE metar_date = :date, metar = :metar";
		} else {
			$query = "UPDATE metar SET metar_date = :date, metar = :metar WHERE metar_location = :location;INSERT INTO metar (metar_location,metar_date,metar) SELECT :location,:date,:metar WHERE NOT EXISTS (SELECT 1 FROM metar WHERE metar_location = :location);";
		}
		$query_values = array(':location' => $location,':date' => $date,':metar' => utf8_encode($metar));
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete a METAR based on id
	 * @param Integer $id METAR table id
	*/
	public function deleteMETAR($id) {
		$query = "DELETE FROM metar WHERE id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete all METAR in DB
	*/
	public function deleteAllMETARLocation() {
		$query = "DELETE FROM metar";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Download all METAR from IVAO or NOAA for current hour
	*/
	public function addMETARCycle() {
		global $globalDebug, $globalIVAO, $globalTransaction;
		if (isset($globalDebug) && $globalDebug) echo "Downloading METAR cycle...";
		date_default_timezone_set("UTC");
		$Common = new Common();
		if (isset($globalIVAO) && $globalIVAO) {
			$Common->download('http://wx.ivao.aero/metar.php',dirname(__FILE__).'/../install/tmp/ivaometar.txt');
			$handle = fopen(dirname(__FILE__).'/../install/tmp/ivaometar.txt',"r");
		} else {
			$Common->download('http://tgftp.nws.noaa.gov/data/observations/metar/cycles/'.date('H').'Z.TXT',dirname(__FILE__).'/../install/tmp/'.date('H').'Z.TXT');
			$handle = fopen(dirname(__FILE__).'/../install/tmp/'.date('H').'Z.TXT',"r");
		}
		if ($handle) {
			if (isset($globalDebug) && $globalDebug) echo "Done - Updating DB...";
			$date = '';
			if ($globalTransaction) $this->db->beginTransaction();
			while(($line = fgets($handle,4096)) !== false) {
				$line = str_replace(array("\r\n","\r", "\n"), '', $line);
				if (preg_match('#^([0-9]{4})/([0-9]{2})/([0-9]{2}) ([0-9]{2}):([0-9]{2})$#',$line)) {
					$date = $line;
				} elseif (trim($line) != '') {
					if ($date == '') $date = date('Y/m/d H:m');
					$pos = 0;
					$pieces = preg_split('/\s/',$line);
					if ($pieces[0] == 'METAR') $pos++;
					if (strlen($pieces[$pos]) != 4) $pos++;
					if (isset($pieces[$pos])) {
						$location = $pieces[$pos];
						//if ($location == 'LFLL') echo 'location: '.$location.' - date: '.$date.' - data: '.$line."\n";
						echo $this->addMETAR($location,$line,$date);
					}
				}
			}
			fclose($handle);
			if ($globalTransaction) $this->db->commit();
		}
		if (isset($globalDebug) && $globalDebug) echo "Done\n";
	}

	/*
	 * Download METAR from $globalMETARurl for an airport ICAO
	 * @param String $icao airport ICAO
	 * @return Array Return array with metar date, location and text
	*/
	public function downloadMETAR($icao) {
		global $globalMETARurl;
		if ($globalMETARurl == '') return array();
		date_default_timezone_set("UTC");
		$Common = new Common();
		$url = str_replace('{icao}',$icao,$globalMETARurl);
		$cycle = $Common->getData($url);
		$date = '';
		foreach(explode("\n",$cycle) as $line) {
			$line = str_replace(array("\r\n","\r", "\n"), '', $line);
			if (preg_match('#^([0-9]{4})/([0-9]{2})/([0-9]{2}) ([0-9]{2}):([0-9]{2})$#',$line)) {
				$date = $line;
			} 
			if (trim($line) != '') {
				if ($date == '') $date = date('Y/m/d H:m');
				$pos = 0;
				$pieces = preg_split('/\s/',$line);
				if ($pieces[0] == 'METAR') $pos++;
				if (strlen($pieces[$pos]) != 4) $pos++;
				$location = $pieces[$pos];
				if (strlen($location == 4)) {
					$this->addMETAR($location,$line,$date);
					return array('0' => array('metar_date' => $date, 'metar_location' => $location, 'metar' => $line));
				} else return array();
			}
		}
		return array();
	}
}
/*
$METAR = new METAR();
print_r($METAR->parse('LSGG 151850Z VRB05KT 9999 FEW060 10/01 Q1021 NOSIG'));
*/
/*
echo $METAR->parse('CYZR 070646Z AUTO VRB02KT 1SM R33/3500VP6000FT/ BR OVC001 M03/M03 A3020 RMK ICG PAST HR SLP233');
echo $METAR->parse('CYVQ 070647Z 00000KT 10SM -SN OVC030 M24/M26 A2981 RMK SC8 SLP105');
echo $METAR->parse('CYFC 070645Z AUTO 23003KT 5/8SM R09/3500VP6000FT BR FEW180 M01/M01 A3004 RMK SLP173');
echo $METAR->parse('ZMUB 070700Z VRB01MPS 6000NW SCT100 M11/M15 Q1013 NOSIG RMK QFE652.8 71 NW MO');
echo $METAR->parse('LFLY 070700Z AUTO 00000KT 2800 0500 R34/0600D BCFG NSC 04/03 Q1033');
echo $METAR->parse('LFLY 071100Z 0712/0812 15007KT CAVOK PROB30 0800/0806 4000 BR');
echo $METAR->parse('LFMU 070700Z AUTO 02005KT 5000 BR BKN012/// BKN018/// BKN030/// ///CB 11/11 Q1032');
echo $METAR->parse('METAR LFPO 231027Z AUTO 24004G09MPS 2500 1000NW R32/0400 R08C/0004D +FZRA VCSN //FEW015 17/10 Q1009 REFZRA WS R03');
*/
/*
$METAR = new METAR();
$METAR->addMETARCycle();
*/
/*
2015/12/07 12:21
TAF LFLY 071100Z 0712/0812 15007KT CAVOK PROB30 0800/0806 4000 BR 
      BECMG 0805/0807 0400 FG VV/// 
     FM081002 18010KT CAVOK
     */

?>