<?php
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Spotter.php');
//require_once(dirname(__FILE__).'/class.SpotterImport.php');
require_once(dirname(__FILE__).'/class.Image.php');
require_once(dirname(__FILE__).'/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.Translation.php');
class Accident {
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
	}


	public function get() {
		$query = 'SELECT DISTINCT registration FROM accidents ORDER BY date DESC';
		$sth = $this->db->prepare($query);
		$sth->execute();
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}
	
	/**
	* Get Latest ACARS data from DB
	*
	* @return Array Return ACARS data in array
	*/
	public function getLatestAccidentData($limit = '',$type = '') {
	global $globalURL, $globalDBdriver;
	$Image = new Image($this->db);
	$Spotter = new Spotter($this->db);
	$Translation = new Translation($this->db);
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
	    $query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents WHERE type = :type GROUP BY registration) ORDER BY date DESC".$limit_query;
	    $query_values = array(':type' => $type);
	} else {
	    //$query = "SELECT * FROM accidents GROUP BY registration ORDER BY date DESC".$limit_query;
	    $query = "SELECT * FROM accidents WHERE accidents_id IN (SELECT max(accidents_id) FROM accidents GROUP BY registration) ORDER BY date DESC".$limit_query;
	    $query_values = array();
	}
	try {

	    $sth = $this->db->prepare($query);
	    $sth->execute($query_values);
	} catch(PDOException $e) {
	    return "error : ".$e->getMessage();
	}
	$i = 0;
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	    $data = array();
	    if ($row['registration'] != '') {
//		$row['registration'] = str_replace('.','',$row['registration']);
		$image_array = $Image->getSpotterImage($row['registration']);
		if (count($image_array) > 0) $data = array_merge($data,array('image' => $image_array[0]['image'],'image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
		else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
		$aircraft_type = $Spotter->getAllAircraftTypeByRegistration($row['registration']);
		$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_type);
		//echo $row['registration'];
		//print_r($aircraft_info);
		if (!empty($aircraft_info)) {
			//echo 'ok!!!';
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
	    $identicao = $Spotter->getAllAirlineInfo(substr($row['ident'],0,2));
	    if (isset($identicao[0])) {
		if (substr($row['ident'],0,2) == 'AF') {
		    if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
		    else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
		} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');

		$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
	    } else $icao = $row['ident'];
	    $icao = $Translation->checkTranslation($icao,false);
	    //$data = array_merge($data,array('registration' => $row['registration'], 'date' => $row['date'], 'ident' => $icao,'url' => $row['url']));
	    $data = array_merge($row,$data);
	    if ($data['ident'] == null) $data['ident'] = $icao;
	    if ($data['title'] == null) {
		$data['message'] = $row['type'].' of '.$row['registration'].' at '.$row['place'].','.$row['country'];
	    } else $data['message'] = strtolower($data['title']);
	    $result[] = $data;
	    $i++;
	}
	if (isset($result)) {
	    $result[0]['query_number_rows'] = $i;
	    return $result;
	}
	else return array();
    }
}
?>