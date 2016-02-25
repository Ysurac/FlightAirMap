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
	public function getData($url, $type = 'get', $data = '', $headers = '',$cookie = '',$referer = '',$timeout = '',$useragent = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true); 
		curl_setopt($ch,CURLOPT_ENCODING , "gzip");
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
//		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:42.0) Gecko/20100101 Firefox/42.0');
		if ($useragent == '') {
			curl_setopt($ch, CURLOPT_USERAGENT, UAgent::random());
		} else {
			curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		}
		if ($timeout == '') curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
		else curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); 
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
		}
		if ($headers != '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if ($cookie != '') {
			if (is_array($cookie)) {
				curl_setopt($ch, CURLOPT_COOKIE, implode($cookie,';'));
			} else {
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
			}
		}
		if ($referer != '') {
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info['http_code'] == '503' && strstr($result,'DDoS protection by CloudFlare')) {
			echo "Cloudflare Detected\n";
			require_once 'libs/cloudflare-bypass/libraries/cloudflareClass.php';
			$useragent = UAgent::random();
			cloudflare::useUserAgent($useragent);
			if ($clearanceCookie = cloudflare::bypass($url)) {
				return $this->getData($url,'get',$data,$headers,$clearanceCookie,$referer,$timeout,$useragent);
			}
		} else {
		    return $result;
		}
	}
	
	private function curlResponseHeaderCallback($ch, $headerLine) {
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
	public function table2array($data) {
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
	public function text2array($data) {
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
	public function distance($lat, $lon, $latc, $lonc, $unit = 'km') {
		$dist = rad2deg(acos(sin(deg2rad(floatval($lat)))*sin(deg2rad(floatval($latc)))+ cos(deg2rad(floatval($lat)))*cos(deg2rad(floatval($latc)))*cos(deg2rad(floatval($lon)-floatval($lonc)))))*60*1.1515;
		if ($unit == "km") {
			return round($dist * 1.609344);
		} elseif ($unit == "m") {
			return round($dist * 1.609344 * 1000);
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
	public function withinThreshold ($timeDifference, $distance) {
		$x = abs($timeDifference);
		$d = abs($distance);
		if ($x == 0 || $d == 0) return true;
		// may be due to Internet jitter; distance is realistic
		if ($x < 0.7 && $d < 2000) return true;
		else return $d/$x < 1200*0.27778; // 1200 km/h max
	}


	// Check if an array is assoc
	public function isAssoc($array)
	{
		return ($array !== array_values($array));
	}

	public function isInteger($input){
	    return(ctype_digit(strval($input)));
	}


	public function convertDec($dms,$latlong) {
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
	public function xcopy($source, $dest, $permissions = 0755)
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
	public function urlexist($url){
		$headers=get_headers($url);
		return stripos($headers[0],"200 OK")?true:false;
	}
	
	/**
	* Convert hexa to string
	* @param	String $hex data in hexa
	* @return	String Return result
	*/
	public function hex2str($hex) {
		$str = '';
		for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
		return $str;
	}
	
	
	public function getHeading($lat1, $lon1, $lat2, $lon2) {
		//difference in longitudinal coordinates
		$dLon = deg2rad($lon2) - deg2rad($lon1);
		//difference in the phi of latitudinal coordinates
		$dPhi = log(tan(deg2rad($lat2) / 2 + pi() / 4) / tan(deg2rad($lat1) / 2 + pi() / 4));
		//we need to recalculate $dLon if it is greater than pi
		if(abs($dLon) > pi()) {
			if($dLon > 0) {
				$dLon = (2 * pi() - $dLon) * -1;
			} else {
				$dLon = 2 * pi() + $dLon;
			}
		}
		//return the angle, normalized
		return (rad2deg(atan2($dLon, $dPhi)) + 360) % 360;
	}
	
	public function checkLine($lat1,$lon1,$lat2,$lon2,$lat3,$lon3,$approx = 0.1) {
		$a = ($lon2-$lon1)*$lat3+($lat2-$lat1)*$lon3+($lat1*$lon2+$lat2*$lon1);
		$a = -($lon2-$lon1);
		$b = $lat2 - $lat1;
		$c = -($a*$lat1+$b*$lon1);
		$d = $a*$lat3+$b*$lon3+$c;
		if ($d > -$approx && $d < $approx) return true;
		else return false;
	}
}
?>