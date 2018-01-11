<?php
/**
 * This class is part of FlightAirmap. It's used for Accidents/Incidents data.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.Image.php');
require_once(dirname(__FILE__).'/class.Translation.php');

class Accident {
	public $db;

	/*
	 * Initialize DB connection
	 */
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Accident)');
	}

	/*
	 * Get all aircrafts registration by accidents
	 * @return Array Return all registrations in accidents table
	*/
	public function get() {
		$query = 'SELECT DISTINCT registration FROM accidents ORDER BY date DESC';
		$sth = $this->db->prepare($query);
		$sth->execute();
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

	/**
	* Get Accidents data from DB
	* @param String $limit Limit
	* @param String $type Set type accident or incident
	* @param String $date get data for a date
	* @return array Return Accidents data in array
	*/
	public function getAccidentData($limit = '',$type = '',$date = '') {
		global $globalDBdriver;
		$Image = new Image($this->db);
		$Spotter = new Spotter($this->db);
		$Translation = new Translation($this->db);
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		date_default_timezone_set('UTC');
		$result = array();
		$limit_query = '';
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($type != '') {
			if ($date != '') {
				if (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) {
					$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND date = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND date = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					$query_values = array(':type' => $type,':date' => $date);
				} elseif (preg_match("/^[0-9]{4}-[0-9]{2}$/",$date)) {
					$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND date BETWEEN :dated AND :datef GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					$query_values = array(':type' => $type,':dated' => $date.'-01', ':datef' => $date.'-31');
				} elseif (preg_match("/^[0-9]{4}$/",$date)) {
					if ($globalDBdriver == 'mysql') {
						$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND YEAR(date) = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					} else {
						$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND EXTRACT(YEAR FROM date) = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					}
					$query_values = array(':type' => $type,':date' => $date);
				} else {
					$date = $date.'%';
					if ($globalDBdriver == 'mysql') {
						$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND DATE_FORMAT(date,'%Y-%m-%d') LIKE :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
						$query_values = array(':type' => $type,':date' => $date);
					} else {
						$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND to_char(date,'YYYY-MM-DD') LIKE :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
						//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type AND to_char(date,'YYYY-MM-DD') LIKE :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
						$query_values = array(':type' => $type,':date' => $date);
					}
				}
			} else {
				//$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				$query = "SELECT * FROM accidents WHERE type = :type ORDER BY accidents.date DESC".$limit_query;
				//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				$query_values = array(':type' => $type);
			}
		} else {
			if ($date != '') {
				if (preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) {
					$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE date = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE date = :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				} else {
					$date = $date.'%';
					$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE to_char(date,'YYYY-MM-DD') LIKE :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
					//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE to_char(date,'YYYY-MM-DD') LIKE :date GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				}
				$query_values = array(':date' => $date);
			} else {
				//$query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				$query = "SELECT * FROM accidents ORDER BY accidents.date DESC".$limit_query;
				//$query = "SELECT accidents.registration, accidents.ident, accidents.date, accidents.url, accidents.country, accidents.place, accidents.title, accidents.fatalities, accidents.type, accidents.ident, accidents.aircraft_manufacturer, accidents.aircraft_name, accidents.airline_name, accidents.airline_icao, spotter_output.flightaware_id FROM accidents LEFT OUTER JOIN spotter_output ON accidents.registration = spotter_output.registration WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents GROUP BY registration) ORDER BY accidents.date DESC".$limit_query;
				$query_values = array();
			}
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$i = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			if (preg_match('/^[\w\-]+$/',$row['registration'])) {
				$data = array();
				if ($row['registration'] != '') {
					$image_array = $Image->getSpotterImage($row['registration']);
					if (count($image_array) > 0) $data = array_merge($data,array('image' => $image_array[0]['image'],'image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
					else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
					$aircraft_type = $Spotter->getAllAircraftTypeByRegistration($row['registration']);
					$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_type);
					if (!empty($aircraft_info)) {
						$data['aircraft_type'] = $aircraft_info[0]['icao'];
						$data['aircraft_name'] = $aircraft_info[0]['type'];
						$data['aircraft_manufacturer'] = $aircraft_info[0]['manufacturer'];
					} else {
						$data = array_merge($data,array('aircraft_type' => 'NA'));
					}
					$owner_data = $Spotter->getAircraftOwnerByRegistration($row['registration']);
					if (!empty($owner_data)) {
						$data['aircraft_owner'] = $owner_data['owner'];
						$data['aircraft_base'] = $owner_data['base'];
						$data['aircraft_date_first_reg'] = $owner_data['date_first_reg'];
					}
				} else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
				if ($row['registration'] == '') $row['registration'] = 'NA';
				if ($row['ident'] == '') $row['ident'] = 'NA';
				$identicao = $Spotter->getAllAirlineInfo(substr($row['ident'],0,3));
				if (isset($identicao[0])) {
					if (substr($row['ident'],0,2) == 'AF') {
						if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
						else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
					} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
					$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
				} else $icao = $row['ident'];
				$icao = $Translation->checkTranslation($icao,false);
				//$data = array_merge($data,array('registration' => $row['registration'], 'date' => $row['date'], 'ident' => $icao,'url' => $row['url']));
				if ($row['airline_name'] != '' && !isset($data['airline_name'])) {
					//echo 'Check airline info... for '.$row['airline_name'].' ';
					//echo $row['airline_name'];
					$airline_info = $Spotter->getAllAirlineInfoByName($row['airline_name']);
					if (!empty($airline_info)) {
						//echo 'data found !'."\n";
						//print_r($airline_info);
						$data = array_merge($data,$airline_info);
					} 
					//else echo 'No data...'."\n";
				}
				$data = array_merge($row,$data);
				if ($data['ident'] == null) $data['ident'] = $icao;
				if ($data['title'] == null) {
					$data['message'] = $row['type'].' of '.$row['registration'].' at '.$row['place'].','.$row['country'];
				} else $data['message'] = strtolower($data['title']);
				$ids = $Spotter->getAllIDByRegistration($data['registration'],true);
				$date = $data['date'];
				if (isset($ids[$date])) {
					$data['spotted'] = TRUE;
					$data['flightaware_id'] = $ids[$date]['flightaware_id'];
					$data['spotter_id'] = $ids[$date]['spotter_id'];
				} elseif (isset($ids[0])) {
					$data['spotted_registration'] = TRUE;
					$data['flightaware_id'] = $ids[0]['flightaware_id'];
					//$data['spotter_id'] = $ids[0]['spotter_id'];
				}
				$result[] = $data;
			}
			$i++;
		}
		if (isset($result)) {
			$result[0]['query_number_rows'] = $i;
			return $result;
		}
		else return array();
	}

	/*
	* Get fatalities by year
	* @return Array number of fatalities by year
	*/
	public function countFatalitiesByYear() {
		//$query = 'SELECT EXTRACT(year FROM date) AS year, SUM(fatalities) as count FROM accidents WHERE accidents_id IN (SELECT MAX(accidents_id) FROM accidents WHERE fatalities > 0 AND EXTRACT(year FROM date) > 2006 GROUP BY registration) GROUP BY EXTRACT(year FROM date) ORDER BY year';
		$query = "SELECT EXTRACT(year FROM date) AS year, SUM(fatalities) as count FROM accidents WHERE accidents_id IN (SELECT MAX(accidents_id) FROM accidents WHERE fatalities > 0 AND date > '2006-01-01 00:00:00' GROUP BY registration) GROUP BY EXTRACT(year FROM date) ORDER BY year";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo "Error : ".$e->getMessage();
			return array();
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
	* Get fatalities last 12 months
	* @return Array number of fatalities last 12 months
	*/
	public function countFatalitiesLast12Months() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT EXTRACT(month FROM date) AS month, EXTRACT(year FROM date) AS year, SUM(fatalities) as count FROM accidents WHERE accidents_id IN (SELECT MAX(accidents_id) FROM accidents WHERE fatalities > 0 AND date > DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY registration) GROUP BY EXTRACT(month FROM date), EXTRACT(year FROM date) ORDER BY year,month";
		} else {
			$query = "SELECT EXTRACT(month FROM date) AS month, EXTRACT(year FROM date) AS year, SUM(fatalities) as count FROM accidents WHERE accidents_id IN (SELECT MAX(accidents_id) FROM accidents WHERE fatalities > 0 AND date > (current_date - INTERVAL '12 months') GROUP BY registration) GROUP BY EXTRACT(month FROM date), EXTRACT(year FROM date) ORDER BY year,month";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo "Error : ".$e->getMessage();
			return array();
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
	* Import csv accidents file into the DB
	* @param String $file filename of the file to import
	*/
	public function import($file) {
		global $globalDebug;
		if ($globalDebug) echo 'Import '.$file."\n";
		$result = array();
		if (file_exists($file)) {
			if (($handle = fopen($file,'r')) !== FALSE) {
				while (($data = fgetcsv($handle,2000,",")) !== FALSE) {
					if (isset($data[1]) && $data[1] != '0000-00-00 00:00:00') {
						$result[] = array('registration' => $data[0],'date' => strtotime($data[1]),'url' => $data[2],'country' => $data[3],'place' => $data[4],'title' => $data[5],'fatalities' => $data[6],'latitude' => $data[7],'longitude' => $data[8],'type' => $data[9],'ident' => $data[10],'aircraft_manufacturer' => $data[11],'aircraft_name' => $data[12],'operator' => $data[13],'source' => 'website_fam');
					}
				}
				fclose($handle);
			}
			if (!empty($result)) $this->add($result,true);
			elseif ($globalDebug) echo 'Nothing to import';
		}
	}

	/*
	* Check if file changed since last update, if true import modified files
	*/
	public function download_update() {
		global $globalDebug;
		require_once('class.Common.php');
		$Common = new Common();
		$all_md5 = array();
		$all_md5_new = array();
		if (file_exists(dirname(__FILE__).'/../install/tmp/cr-all.md5')) {
			if ($this->check_accidents_nb() > 0) {
				if (($handle = fopen(dirname(__FILE__).'/../install/tmp/cr-all.md5','r')) !== FALSE) {
					while (($data = fgetcsv($handle,2000,"\t")) !== FALSE) {
						if (isset($data[1])) {
							$year = $data[0];
							$all_md5[$year] = $data[1];
						}
					}
					fclose($handle);
				}
			}
		}
		$Common->download('http://data.flightairmap.fr/data/cr/cr-all.md5',dirname(__FILE__).'/../install/tmp/cr-all.md5');
		if (file_exists(dirname(__FILE__).'/../install/tmp/cr-all.md5')) {
			if (($handle = fopen(dirname(__FILE__).'/../install/tmp/cr-all.md5','r')) !== FALSE) {
				while (($data = fgetcsv($handle,2000,"\t")) !== FALSE) {
					if (isset($data[1])) {
						$year = $data[0];
						$all_md5_new[$year] = $data[1];
					}
				}
				fclose($handle);
			} elseif ($globalDebug) echo "Can't open ".dirname(__FILE__).'/../install/tmp/cr-all.md5';
		} elseif ($globalDebug) echo 'Download cr-all.md5 failed. '.dirname(__FILE__).'/../install/tmp/cr-all.md5 not here.';
		$result = $Common->arr_diff($all_md5_new,$all_md5);
		if (empty($result) && $globalDebug) echo 'Nothing to update';
		foreach ($result as $file => $md5) {
			$Common->download('http://data.flightairmap.fr/data/cr/'.$file,dirname(__FILE__).'/../install/tmp/'.$file);
			if (file_exists(dirname(__FILE__).'/../install/tmp/'.$file)) $this->import(dirname(__FILE__).'/../install/tmp/'.$file);
			elseif ($globalDebug) echo 'Download '.$file.' failed';
		}
	}

	/*
	* Add data to DB
	* @param Array $crash An array with accidents/incidents data
	*/
	public function add($crash,$new = false) {
		global $globalTransaction, $globalDebug, $globalAircraftImageFetch;
		require_once('class.Connection.php');
		require_once('class.Image.php');
		require_once('class.Spotter.php');
		$Connection = new Connection($this->db);
		$Image = new Image($this->db);
		$Spotter = new Spotter($this->db);

		if (empty($crash)) return false;
		if ($new === false) {
			$query_delete = 'DELETE FROM accidents WHERE source = :source';
			$sthd = $Connection->db->prepare($query_delete);
			$sthd->execute(array(':source' => $crash[0]['source']));
		}
		if ($globalTransaction) $Connection->db->beginTransaction();
		$initial_array = array('ident' => null,'type' => 'accident','url' => null,'registration' => null, 'date' => null, 'place' => null,'country' => null, 'latitude' => null, 'longitude' => null, 'fatalities' => null, 'title' => '','source' => '','aircraft_manufacturer' => null,'aircraft_name' => null,'operator' => null);
		$query_check = 'SELECT COUNT(*) as nb FROM accidents WHERE registration = :registration AND date = :date AND type = :type AND source = :source';
		$sth_check = $Connection->db->prepare($query_check);
		$query = 'INSERT INTO accidents (aircraft_manufacturer,aircraft_name,ident,registration,date,url,country,place,title,fatalities,latitude,longitude,type,airline_name,source) VALUES (:aircraft_manufacturer,:aircraft_name,:ident,:registration,:date,:url,:country,:place,:title,:fatalities,:latitude,:longitude,:type,:airline_name,:source)';
		$sth = $Connection->db->prepare($query);
		$j = 0;
		try {
			foreach ($crash as $cr) {
				//print_r($cr);
				$cr = $cr + $initial_array;
				$cr = array_map(function($value) {
					return $value === "" ? NULL : $value;
				}, $cr);
				if ($cr['date'] != '' && $cr['registration'] != null && $cr['registration'] != '' && $cr['registration'] != '?' && $cr['registration'] != '-' && strtolower($cr['registration']) != 'unknown' && $cr['date'] < time() && !preg_match('/\s/',$cr['registration'])) {
					if (strpos($cr['registration'],'-') === FALSE) $cr['registration'] = $Spotter->convertAircraftRegistration($cr['registration']);
					$query_check_values = array(':registration' => $cr['registration'],':date' => date('Y-m-d',$cr['date']),':type' => $cr['type'],':source' => $cr['source']);
					$sth_check->execute($query_check_values);
					$result_check = $sth_check->fetchAll(PDO::FETCH_ASSOC);
					if ($result_check[0]['nb'] == 0) {
						$query_values = array(':registration' => trim($cr['registration']),':date' => date('Y-m-d',$cr['date']),':url' => $cr['url'],':country' => $cr['country'],':place' => $cr['place'],':title' => $cr['title'],':fatalities' => $cr['fatalities'],':latitude' => $cr['latitude'],':longitude' => $cr['longitude'],':type' => $cr['type'],':source' => $cr['source'],':ident' => $cr['ident'],':aircraft_manufacturer' => $cr['aircraft_manufacturer'],':aircraft_name' => $cr['aircraft_name'],':airline_name' => $cr['operator']);
						$sth->execute($query_values);
						if ($cr['date'] > time()-(30*86400)) {
							if ($globalAircraftImageFetch) {
								$imgchk = $Image->getSpotterImage($cr['registration']);
								if (empty($imgchk)) {
									if ($globalDebug) echo "\t".'Get image for '.$cr['registration'].'...';
									$Image->addSpotterImage($cr['registration']);
									if ($globalDebug) echo "\t".'Done'."\n";
								}
								// elseif ($globalDebug) echo 'Image already in DB'."\n";
							}
							if ($cr['title'] == '') $cr['title'] = $cr['registration'].' '.$cr['type'];
							$Spotter->setHighlightFlightByRegistration($cr['registration'],$cr['title'],date('Y-m-d',$cr['date']));
						}
					}
				}
				if ($globalTransaction && $j % 1000 == 0) {
					$Connection->db->commit();
					$Connection->db->beginTransaction();
				}
			}
			if ($globalTransaction) $Connection->db->commit();
		} catch(PDOException $e) {
			if ($globalTransaction) $Connection->db->rollBack();
			echo $e->getMessage();
		}
		$sth_check->closeCursor();
		return '';
	}

	/*
	* Get number of accidents
	* @return Integer Number of accidents/incidents in table
	*/
	public static function check_accidents_nb() {
			$query = "SELECT COUNT(*) as nb FROM accidents";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		return $row['nb'];
	}

    /**
     * Check if lastest accident update date is older than 1 day
     * @return bool|string
     */
    public static function check_last_accidents_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_accident_db' AND value > DATE_SUB(NOW(), INTERVAL 1 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_accident_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '1 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return false;
		else return true;
	}

    /**
     * Insert accident update date
     * @return string
     */
    public static function insert_last_accidents_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_accident_db';
		    INSERT INTO config (name,value) VALUES ('last_update_accident_db',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

}
?>