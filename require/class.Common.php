<?php
require_once('libs/simple_html_dom.php');
require_once('libs/uagent/uagent.php');

class Common {
	protected $cookies = array();
	
	/**
	* Get data from form result
	* @param String $url form URL
	* @param String $type type of submit form method (get or post)
	* @param String or Array $data values form post method
	* @param Array $headers header to submit with the form
	* @return String the result
	*/
	public static function getData($url, $type = 'get', $data = '', $headers = '',$cookie = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
		curl_setopt($ch, CURLOPT_USERAGENT, UAgent::random());
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array('Common',"curlResponseHeaderCallback"));
		if ($type == 'post') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			if (is_array($data)) {
				curl_setopt($ch, CURLOPT_POST, count($data));
				$data_string = '';
				foreach($data as $key=>$value) { $data_string .= $key.'='.$value.'&'; }
				rtrim($data_string, '&');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		if ($headers != '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if ($cookie != '') {
			curl_setopt($ch, CURLOPT_COOKIE, implode($cookie,';'));
		}
		return curl_exec($ch);
	}
	
	private static function curlResponseHeaderCallback($ch, $headerLine) {
		global $cookies;
		if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1)
			$cookies[] = $cookie;
		return strlen($headerLine); // Needed by curl
	}
	
	/**
	* Convert a HTML table to an array
	* @param String $data HTML page
	* @return Array array of the tables in HTML page
	*/
	public static function table2array($data) {
		$html = str_get_html($data);
		$tabledata=array();
		foreach($html->find('tr') as $element)
		{
			$td = array();
			foreach( $element->find('th') as $row)
			{
				$td [] = trim($row->plaintext);
			}
			$td=array_filter($td);
			$tabledata[] = $td;

			$td = array();
			$tdi = array();
			foreach( $element->find('td') as $row)
			{
				$td [] = trim($row->plaintext);
				$tdi [] = trim($row->innertext);
			}
			$td=array_filter($td);
			$tdi=array_filter($tdi);
			$tabledata[]=array_merge($td,$tdi);
		}
		return(array_filter($tabledata));
	}
	
	/**
	* Convert <p> part of a HTML page to an array
	* @param String $data HTML page
	* @return Array array of the <p> in HTML page
	*/
	public static function text2array($data) {
		$html = str_get_html($data);
		$tabledata=array();
		foreach($html->find('p') as $element)
		{
			$tabledata [] = trim($element->plaintext);
		}
		return(array_filter($tabledata));
	}

	/**
	* Give distance between 2 coordonnates
	* @param Float $lat latitude of first point
	* @param Float $lon longitude of first point
	* @param Float $latc latitude of second point
	* @param Float $lonc longitude of second point
	* @param String $unit km else no unit used
	* @return Float Distance in $unit
	*/
	public static function distance($lat, $lon, $latc, $lonc, $unit = 'km') {
		$dist = rad2deg(acos(sin(deg2rad(floatval($lat)))*sin(deg2rad(floatval($latc)))+ cos(deg2rad(floatval($lat)))*cos(deg2rad(floatval($latc)))*cos(deg2rad(floatval($lon)-floatval($lonc)))))*60*1.1515;
		if ($unit == "km") {
			return round($dist * 1.609344);
		} else {
			return round($dist);
		}
	}

	/**
	* Check is distance realistic
	* @param int $timeDifference the time between the reception of both messages
	* @param float $distance distance covered
	* @return whether distance is realistic
	*/
	public static function withinThreshold ($timeDifference, $distance) {
		$x = abs($timeDifference);
		$d = abs($distance);
		if ($x == 0 || $d == 0) return true;
		// may be due to Internet jitter; distance is realistic
		if ($x < 0.7 && $d < 2000) return true;
		else return $d/$x < 514.4*2.5; // 1000 knots for airborne, 100 for surface
	}


	// Check if an array is assoc
	public static function isAssoc($array)
	{
		return ($array !== array_values($array));
	}

	public static function convertDec($dms,$latlong) {
		if ($latlong == 'latitude') {
			$deg = substr($dms, 0, 2);
			$min = substr($dms, 2, 4);
		} else {
			$deg = substr($dms, 0, 3);
			$min = substr($dms, 3, 5);
		}
		return $deg+(($min*60)/3600);
	}
	
	/**
	* Copy folder contents
	* @param       string   $source    Source path
	* @param       string   $dest      Destination path
	* @return      bool     Returns true on success, false on failure
	*/
	public static function xcopy($source, $dest, $permissions = 0755)
	{
		$files = glob($source.'*.*');
		foreach($files as $file){
			$file_to_go = str_replace($source,$dest,$file);
			copy($file, $file_to_go);
		}
		return true;
	}
	
	/**
	* Check if an url exist
	* @param	String $url url to check
	* @return	bool Return true on succes false on failure
	*/
	public static function urlexist($url){
		$headers=get_headers($url);
		return stripos($headers[0],"200 OK")?true:false;
	}
	
	/**
	* Convert hexa to string
	* @param	String $hex data in hexa
	* @return	String Return result
	*/
	public static function hex2str($hex) {
		$str = '';
		for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
		return $str;
	}
}
?>