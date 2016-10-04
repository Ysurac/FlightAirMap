<?php
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/Sat.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/QTH.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/Time.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/TLE.php');

class Satellite {
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
	}

	public function get_tle($name) {
		$query = 'SELECT tle_name, tle_tle1, tle_tle2, tle_type FROM tle WHERE tle_name = :name LIMIT 1';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':name' => $name));
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}
	public function get_tle_types() {
		$query = 'SELECT DISTINCT tle_type FROM tle ORDER BY tle_type';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	public function get_tle_names() {
		$query = 'SELECT DISTINCT tle_name, tle_type FROM tle';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	public function get_tle_names_type($type) {
		$query = 'SELECT tle_name, tle_type FROM tle WHERE tle_type = :type ORDER BY tle_name';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':type' => $type));
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	
	public function position_all($timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$all_sat = $this->get_tle_names();
		$result = array();
		foreach ($all_sat as $sat) {
			$position = $this->position($sat['tle_name'],$timestamp_begin,$timestamp_end,$second);
			$result = array_merge($position,$result);
		}
		return $result;
	}

	public function position_all_type($type,$timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$all_sat = $this->get_tle_names_type($type);
		$result = array();
		foreach ($all_sat as $sat) {
			$position = $this->position($sat['tle_name'],$timestamp_begin,$timestamp_end,$second);
			$result = array_merge($position,$result);
		}
		return $result;
	}
	
	public function position($name,$timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$qth = new Predict_QTH();
		$qth->lat = floatval(37.790252);
		$qth->lon = floatval(-122.419968);
	
		$tle_file = $this->get_tle($name);
		//print_r($tle_file);
		$type = $tle_file['tle_type'];
		$tle = new Predict_TLE($tle_file['tle_name'],$tle_file['tle_tle1'],$tle_file['tle_tle2']);
		$sat = new Predict_Sat($tle);
		$predict = new Predict();
		//if ($timestamp == '') $now = Predict_Time::get_current_daynum();
		if ($timestamp_begin == '') $timestamp_begin = time();
		if ($timestamp_end == '') {
			$now = Predict_Time::unix2daynum($timestamp_begin);
			//echo $now;
			$predict->predict_calc($sat,$qth,$now);
			return array('name' => $name, 'latitude' => $sat->ssplat,'longitude' => $sat->ssplon, 'altitude' => $sat->alt,'speed' => $sat->velo*60*60,'timestamp' => $timestamp_begin,'type' => $type);
		} else {
			$result = array();
			for ($timestamp = $timestamp_begin; $timestamp <= $timestamp_end; $timestamp=$timestamp+$second) {
				//echo $timestamp."\n";
				$now = Predict_Time::unix2daynum($timestamp);
				//echo $now;
				$predict->predict_calc($sat,$qth,$now);
				$result[] = array('name' => $name,'latitude' => $sat->ssplat,'longitude' => $sat->ssplon, 'altitude' => $sat->alt,'speed' => $sat->velo*60*60,'timestamp' => $timestamp,'type' => $type);
			}
			return $result;
		}
	}
}
/*
$sat = new Satellite();
print_r($sat->position('ISS (ZARYA)',time(),time()+100));
print_r($sat->get_tle_types());
*/
?>