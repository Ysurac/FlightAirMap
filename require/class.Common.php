<?php
/**
 * This class is part of FlightAirmap. It's used for all common functions
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/libs/simple_html_dom.php');
require_once(dirname(__FILE__).'/libs/uagent/uagent.php');
require_once(dirname(__FILE__).'/settings.php');

class Common {
	//protected $cookies = array();
	
	/**
	* Get data from form result
	* @param String $url form URL
	* @param String $type type of submit form method (get or post)
	* @param String|array $data values form post method
	* @param array $headers header to submit with the form
	* @return String the result
	*/
	public function getData($url, $type = 'get', $data = '', $headers = '',$cookie = '',$referer = '',$timeout = '',$useragent = '', $sizelimit = false, $async = false, $getheaders = false) {
		global $globalProxy, $globalForceIPv4;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (isset($globalForceIPv4) && $globalForceIPv4) {
			if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
				curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			}
		}
		if (isset($globalProxy) && $globalProxy != '') {
			curl_setopt($ch, CURLOPT_PROXY, $globalProxy);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true); 
		if ($getheaders) curl_setopt($ch, CURLOPT_HEADER, 1); 
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
		//curl_setopt($ch, CURLOPT_HEADERFUNCTION, array('Common',"curlResponseHeaderCallback"));
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
		} elseif ($type != 'get' && $type != '') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
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
		if ($sizelimit === true) {
			curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
			curl_setopt($ch, CURLOPT_NOPROGRESS, false);
			curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($curlr,$downloadsize, $downloaded, $uploadsize, $uploaded){
				return ($downloaded > (3*1024)) ? 1 : 0;
			});
		}
		if ($async) {
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1); //to timeout immediately if the value is < 1000 ms
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50);
		}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		//var_dump($info);
		curl_close($ch);
		if ($info['http_code'] == '503' && strstr($result,'DDoS protection by CloudFlare')) {
			echo "Cloudflare Detected\n";
			require_once(dirname(__FILE__).'/libs/cloudflare-bypass/libraries/cloudflareClass.php');
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
		global $curl_cookies;
		$curl_cookies = array();
		if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1)
			$curl_cookies[] = $cookie;
		return strlen($headerLine); // Needed by curl
	}


	public static function download($url, $file, $referer = '', $headers = '') {
		global $globalDebug, $globalProxy, $globalForceIPv4;
		$fp = fopen($file, 'w');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		if ($referer != '') curl_setopt($ch, CURLOPT_REFERER, $referer);
		if (isset($globalForceIPv4) && $globalForceIPv4) {
			if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
				curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			}
		}
		if ($headers != '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if (isset($globalProxy) && $globalProxy != '') {
			curl_setopt($ch, CURLOPT_PROXY, $globalProxy);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		if (curl_errno($ch) && $globalDebug) echo 'Download error: '.curl_error($ch);
		curl_close($ch);
		fclose($fp);
	}

	public static function gunzip($in_file,$out_file_name = '') {
		//echo $in_file.' -> '.$out_file_name."\n";
		$buffer_size = 4096; // read 4kb at a time
		if ($out_file_name == '') $out_file_name = str_replace('.gz', '', $in_file); 
		if ($in_file != '' && file_exists($in_file)) {
			// PHP version of Ubuntu use gzopen64 instead of gzopen
			if (function_exists('gzopen')) $file = gzopen($in_file,'rb');
			elseif (function_exists('gzopen64')) $file = gzopen64($in_file,'rb');
			else {
				echo 'gzopen not available';
				die;
			}
			$out_file = fopen($out_file_name, 'wb'); 
			while(!gzeof($file)) {
				fwrite($out_file, gzread($file, $buffer_size));
			}  
			fclose($out_file);
			gzclose($file);
		}
	}

	public static function bunzip2($in_file,$out_file_name = '') {
		//echo $in_file.' -> '.$out_file_name."\n";
		$buffer_size = 4096; // read 4kb at a time
		if ($out_file_name == '') $out_file_name = str_replace('.bz2', '', $in_file); 
		if ($in_file != '' && file_exists($in_file)) {
			// PHP version of Ubuntu use gzopen64 instead of gzopen
			if (function_exists('bzopen')) $file = bzopen($in_file,'rb');
			else {
				echo 'bzopen not available';
				die;
			}
			$out_file = fopen($out_file_name, 'wb'); 
			while(!feof($file)) {
				fwrite($out_file, bzread($file, $buffer_size));
			}  
			fclose($out_file);
			bzclose($file);
		}
	}

	/**
	* Convert a HTML table to an array
	* @param String $data HTML page
	* @return array array of the tables in HTML page
	*/
	public function table2array($data) {
		if (!is_string($data)) return array();
		if ($data == '') return array();
		$html = str_get_html($data);
		if ($html === false) return array();
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
		$html->clear();
		unset($html);
		return(array_filter($tabledata));
	}
	
	/**
	* Convert <p> part of a HTML page to an array
	* @param String $data HTML page
	* @return array array of the <p> in HTML page
	*/
	public function text2array($data) {
		$html = str_get_html($data);
		if ($html === false) return array();
		$tabledata=array();
		foreach($html->find('p') as $element)
		{
			$tabledata [] = trim($element->plaintext);
		}
		$html->clear();
		unset($html);
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
		if ($lat == $latc && $lon == $lonc) return 0;
		$dist = rad2deg(acos(sin(deg2rad(floatval($lat)))*sin(deg2rad(floatval($latc)))+ cos(deg2rad(floatval($lat)))*cos(deg2rad(floatval($latc)))*cos(deg2rad(floatval($lon)-floatval($lonc)))))*60*1.1515;
		if ($unit == "km") {
			return round($dist * 1.609344);
		} elseif ($unit == "m") {
			return round($dist * 1.609344 * 1000);
		} elseif ($unit == "mile" || $unit == "mi") {
			return round($dist);
		} elseif ($unit == "nm") {
			return round($dist*0.868976);
		} else {
			return round($dist);
		}
	}

	/**
	* Give plunge between 2 altitudes and distance
	* @param Float $initial_altitude altitude of first point in m
	* @param Float $final_altitude altitude of second point in m
	* @param String $distance distance between two points in m
	* @return Float plunge
	*/
	public function plunge($initial_altitude,$final_altitude,$distance) {
		$plunge = rad2deg(asin(($final_altitude-$initial_altitude)/$distance));
		/*
		$siter = 6378137.0 + $initial_altitude;
		$planer = 6378137.0 + $final_altitude;
		$airdist = sqrt($siter-$siter + $planer*$planer - 2*$siter*$planer*cos($distance/6378137.0));
		echo 'airdist:'.$airdist;
		$plunge = rad2deg(asin(($planer*$planer - $siter*$siter - $airdist*$airdist)/(2*$siter+$distance)));
		*/
		return $plunge;
	}

	/**
	* Give azimuth between 2 coordonnates
	* @param Float $lat latitude of first point
	* @param Float $lon longitude of first point
	* @param Float $latc latitude of second point
	* @param Float $lonc longitude of second point
	* @return Float Azimuth
	*/
	public function azimuth($lat, $lon, $latc, $lonc) {
		$dX = $latc - $lat;
		$dY = $lonc - $lon;
		$azimuth = rad2deg(atan2($dY,$dX));
		if ($azimuth < 0) return $azimuth+360;
		return $azimuth;
	}
	
	
	/**
	* Check is distance realistic
	* @param int $timeDifference the time between the reception of both messages
	* @param float $distance distance covered
	* @return bool whether distance is realistic
	*/
	public function withinThreshold ($timeDifference, $distance) {
		$x = abs($timeDifference);
		$d = abs($distance);
		if ($x == 0 || $d == 0) return true;
		// may be due to Internet jitter; distance is realistic
		if ($x < 0.7 && $d < 2000) return true;
		else return $d/$x < 1500*0.27778; // 1500 km/h max
	}


	// Check if an array is assoc
	public function isAssoc($array)
	{
		return ($array !== array_values($array));
	}

	public function isInteger($input){
		//return(ctype_digit(strval($input)));
		return preg_match('/^-?[0-9]+$/', (string)$input) ? true : false;
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
	
	public function convertDecLatLong($coord) {
		//N43°36.763' W5°46.845'
		$coords = explode(' ',$coord);
		$latitude = '';
		$longitude = '';
		foreach ($coords as $latlong) {
			$type = substr($latlong,0,1);
			$degmin = explode('°',substr($latlong,1,-1));
			$deg = $degmin[0];
			$min = $degmin[1];
			if ($type == 'N') {
				$latitude = $deg+(($min*60)/3600);
			} elseif ($type == 'S') {
				$latitude = -($deg+(($min*60)/3600));
			} elseif ($type == 'E') {
				$longitude = ($deg+(($min*60)/3600));
			} elseif ($type == 'W') {
				$longitude = -($deg+(($min*60)/3600));
			}
		}
		return array('latitude' => round($latitude,5),'longitude' => round($longitude,5));
	}
	
	public function convertDM($coord,$latlong) {
		if ($latlong == 'latitude') {
			if ($coord < 0) $NSEW = 'S';
			else $NSEW = 'N';
		} else {
			if ($coord < 0) $NSEW = 'W';
			else $NSEW = 'E';
		}
		$coord = abs($coord);
		$deg = floor($coord);
		$coord = ($coord-$deg)*60;
		$min = $coord;
		return array('deg' => $deg,'min' => $min,'NSEW' => $NSEW);
	}
	public function convertDMS($coord,$latlong) {
		if ($latlong == 'latitude') {
			if ($coord < 0) $NSEW = 'S';
			else $NSEW = 'N';
		} else {
			if ($coord < 0) $NSEW = 'W';
			else $NSEW = 'E';
		}
		$coord = abs($coord);
		$deg = floor($coord);
		$coord = ($coord-$deg)*60;
		$min = floor($coord);
		$sec = round(($coord-$min)*60);
		return array('deg' => $deg,'min' => $min,'sec' => $sec,'NSEW' => $NSEW);
	}
	
	/**
	* Copy folder contents
	* @param       string   $source    Source path
	* @param       string   $dest      Destination path
	* @return      bool     Returns true on success, false on failure
	*/
	public function xcopy($source, $dest)
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
		$hexln = strlen($hex);
		for($i=0;$i<$hexln;$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
		return $str;
	}
	
	/**
	* Convert hexa color to rgb
	* @param	String $hex data in hexa
	* @return	String Return result
	*/
	public function hex2rgb($hex) {
		$hex = str_replace('#','',$hex);
		return sscanf($hex, "%02x%02x%02x"); 
	}
	
	public function getHeading($lat1, $lon1, $lat2, $lon2) {
		//difference in longitudinal coordinates
		$dLon = deg2rad($lon2) - deg2rad($lon1);
		//difference in the phi of latitudinal coordinates
		$dPhi = log(tan(deg2rad($lat2) / 2 + M_PI / 4) / tan(deg2rad($lat1) / 2 + M_PI / 4));
		//we need to recalculate $dLon if it is greater than pi
		if(abs($dLon) > M_PI) {
			if($dLon > 0) {
				$dLon = (2 * M_PI - $dLon) * -1;
			} else {
				$dLon = 2 * M_PI + $dLon;
			}
		}
		//return the angle, normalized
		return (rad2deg(atan2($dLon, $dPhi)) + 360) % 360;
	}

	public function checkLine($lat1,$lon1,$lat2,$lon2,$lat3,$lon3,$approx = 0.15) {
		//$a = ($lon2-$lon1)*$lat3+($lat2-$lat1)*$lon3+($lat1*$lon2+$lat2*$lon1);
		$a = -($lon2-$lon1);
		$b = $lat2 - $lat1;
		$c = -($a*$lat1+$b*$lon1);
		$d = $a*$lat3+$b*$lon3+$c;
		if ($d > -$approx && $d < $approx) return true;
		else return false;
	}
	
	public function array_merge_noappend() {
		$output = array();
		foreach(func_get_args() as $array) {
			foreach($array as $key => $value) {
				$output[$key] = isset($output[$key]) ?
				array_merge($output[$key], $value) : $value;
			}
		}
		return $output;
	}
	

	public function arr_diff($arraya, $arrayb) {
		foreach ($arraya as $keya => $valuea) {
			if (in_array($valuea, $arrayb)) {
				unset($arraya[$keya]);
			}
		}
		return $arraya;
	}

	/*
	* Check if a key exist in an array
	* Come from http://stackoverflow.com/a/19420866
	* @param Array array to check
	* @param String key to check
	* @return Bool true if exist, else false
	*/
	public function multiKeyExists(array $arr, $key) {
		// is in base array?
		if (array_key_exists($key, $arr)) {
			return true;
		}
		// check arrays contained in this array
		foreach ($arr as $element) {
			if (is_array($element)) {
				if ($this->multiKeyExists($element, $key)) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	* Returns list of available locales
	*
	* @return array
	 */
	public function listLocaleDir()
	{
		$result = array('en');
		if (!is_dir('./locale')) {
			return $result;
		}
		$handle = @opendir('./locale');
		if ($handle === false) return $result;
		while (false !== ($file = readdir($handle))) {
			$path = './locale'.'/'.$file.'/LC_MESSAGES/fam.mo';
			if ($file != "." && $file != ".." && @file_exists($path)) {
				$result[] = $file;
			}
		}
		closedir($handle);
		return $result;
	}

	public function nextcoord($latitude, $longitude, $speed, $heading, $archivespeed = 1, $seconds = ''){
		global $globalMapRefresh;
		if ($seconds == '') {
			$distance = ($speed*0.514444*$globalMapRefresh*$archivespeed)/1000;
		} else {
			$distance = ($speed*0.514444*$seconds*$archivespeed)/1000;
		}
		$r = 6378;
		$latitude = deg2rad($latitude);
		$longitude = deg2rad($longitude);
		$bearing = deg2rad($heading); 
		$latitude2 =  asin( (sin($latitude) * cos($distance/$r)) + (cos($latitude) * sin($distance/$r) * cos($bearing)) );
		$longitude2 = $longitude + atan2( sin($bearing)*sin($distance/$r)*cos($latitude), cos($distance/$r)-(sin($latitude)*sin($latitude2)) );
		return array('latitude' => number_format(rad2deg($latitude2),5,'.',''),'longitude' => number_format(rad2deg($longitude2),5,'.',''));
	}
	
	public function getCoordfromDistanceBearing($latitude,$longitude,$bearing,$distance) {
		// distance in meter
		$R = 6378.14;
		$latitude1 = $latitude * (M_PI/180);
		$longitude1 = $longitude * (M_PI/180);
		$brng = $bearing * (M_PI/180);
		$d = $distance;

		$latitude2 = asin(sin($latitude1)*cos($d/$R) + cos($latitude1)*sin($d/$R)*cos($brng));
		$longitude2 = $longitude1 + atan2(sin($brng)*sin($d/$R)*cos($latitude1),cos($d/$R)-sin($latitude1)*sin($latitude2));

		$latitude2 = $latitude2 * (180/M_PI);
		$longitude2 = $longitude2 * (180/M_PI);

		$flat = round ($latitude2,6);
		$flong = round ($longitude2,6);
/*
		$dx = $distance*cos($bearing);
		$dy = $distance*sin($bearing);
		$dlong = $dx/(111320*cos($latitude));
		$dlat = $dy/110540;
		$flong = $longitude + $dlong;
		$flat = $latitude + $dlat;
*/
		return array('latitude' => $flat,'longitude' => $flong);
	}

	/**
	 * GZIPs a file on disk (appending .gz to the name)
	 *
	 * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
	 * Based on function by Kioob at:
	 * http://www.php.net/manual/en/function.gzwrite.php#34955
	 * 
	 * @param string $source Path to file that should be compressed
	 * @param integer $level GZIP compression level (default: 9)
	 * @return string New filename (with .gz appended) if success, or false if operation fails
	 */
	public function gzCompressFile($source, $level = 9){ 
		$dest = $source . '.gz'; 
		$mode = 'wb' . $level; 
		$error = false; 
		if ($fp_out = gzopen($dest, $mode)) { 
			if ($fp_in = fopen($source,'rb')) { 
				while (!feof($fp_in)) 
					gzwrite($fp_out, fread($fp_in, 1024 * 512)); 
				fclose($fp_in); 
			} else {
				$error = true; 
			}
			gzclose($fp_out); 
		} else {
			$error = true; 
		}
		if ($error)
			return false; 
		else
			return $dest; 
	} 
	
	public function remove_accents($string) {
		if ( !preg_match('/[\x80-\xff]/', $string) ) return $string;
		$chars = array(
		    // Decompositions for Latin-1 Supplement
		    chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
		    chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
		    chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
		    chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
		    chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
		    chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
		    chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
		    chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
		    chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
		    chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
		    chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
		    chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
		    chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
		    chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
		    chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
		    chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
		    chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
		    chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
		    chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
		    chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
		    chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
		    chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
		    chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
		    chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
		    chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
		    chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
		    chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
		    chr(195).chr(191) => 'y',
		    // Decompositions for Latin Extended-A
		    chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
		    chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
		    chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
		    chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
		    chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
		    chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
		    chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
		    chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
		    chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
		    chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
		    chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
		    chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
		    chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
		    chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
		    chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
		    chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
		    chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
		    chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
		    chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
		    chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
		    chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
		    chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
		    chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
		    chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
		    chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
		    chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
		    chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
		    chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
		    chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
		    chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
		    chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
		    chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
		    chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
		    chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
		    chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
		    chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
		    chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
		    chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
		    chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
		    chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
		    chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
		    chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
		    chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
		    chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
		    chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
		    chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
		    chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
		    chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
		    chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
		    chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
		    chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
		    chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
		    chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
		    chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
		    chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
		    chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
		    chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
		    chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
		    chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
		    chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
		    chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
		    chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
		    chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
		    chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
		);
		$string = strtr($string, $chars);
		return $string;
	}
	
	/*
	* Extract int from a string
	* Come from http://php.net/manual/fr/function.intval.php comment by michiel ed thalent nl
	*
	* @param String Input string
	* @return Integer integer from the string
	*/
	public function str2int($string, $concat = false) {
		$length = strlen($string);    
		for ($i = 0, $int = '', $concat_flag = true; $i < $length; $i++) {
			if (is_numeric($string[$i]) && $concat_flag) {
				$int .= $string[$i];
			} elseif(!$concat && $concat_flag && strlen($int) > 0) {
				$concat_flag = false;
			}
		}
		return (int) $int;
	}
	
	public function create_socket($host, $port, &$errno, &$errstr) {
		$ip = gethostbyname($host);
		$s = socket_create(AF_INET, SOCK_STREAM, 0);
		$r = @socket_connect($s, $ip, $port);
		if (!socket_set_nonblock($s)) echo "Unable to set nonblock on socket\n";
		if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
			return $s;
		}
		$errno = socket_last_error($s);
		$errstr = socket_strerror($errno);
		socket_close($s);
		return false;
	}

	public function create_socket_udp($host, $port, &$errno, &$errstr) {
		$ip = gethostbyname($host);
		$s = socket_create(AF_INET, SOCK_DGRAM, 0);
		$r = @socket_bind($s, $ip, $port);
		if ($r || socket_last_error() == 114 || socket_last_error() == 115) {
			return $s;
		}
		$errno = socket_last_error($s);
		$errstr = socket_strerror($errno);
		socket_close($s);
		return false;
	}

	public function getUserIP() { 
		$client = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		return filter_var($client, FILTER_VALIDATE_IP) ? $client : filter_var($forward, FILTER_VALIDATE_IP) ? $forward : $_SERVER['REMOTE_ADDR']; 
	}
	public function replace_mb_substr($string, $offset, $length)
	{
		if (!function_exists('mb_substr')) {
			$arr = preg_split("//u", $string);
			$slice = array_slice($arr, $offset + 1, $length);
			return implode("", $slice);
		} else {
			return mb_substr($string,$offset,$length,'UTF-8');
		}
	}

	// Come from comment : http://php.net/manual/fr/function.is-writable.php#73596
	public function is__writable($path) {
		//will work in despite of Windows ACLs bug
		//NOTE: use a trailing slash for folders!!!
		//see http://bugs.php.net/bug.php?id=27609
		//see http://bugs.php.net/bug.php?id=30931
		if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
			return $this->is__writable($path.uniqid(mt_rand()).'.tmp');
		else if (is_dir($path))
			return $this->is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f===false)
			return false;
		fclose($f);
		if (!$rm)
			unlink($path);
		return true;
	}
	
	/*
	 * Great circle route
	 * Translated to PHP from javascript version of https://github.com/springmeyer/arc.js
	 * @param Float $begin_lat Latitude of origin point
	 * @param Float $begin_lon Longitude of origin point
	 * @param Float $end_lat Latitude of final point
	 * @param Float $end_lon Longitude of final point
	 * @param Integer $nbpts Number of intermediate vertices desired
	 * @param Integer $offset Controls the likelyhood that lines will be split which cross the dateline
	 * @return Array Coordinate of the route
	*/
	public function greatcircle($begin_lat,$begin_lon,$end_lat,$end_lon,$nbpts = 20, $offset = 10) {
		if ($nbpts <= 2) return array(array($begin_lon,$begin_lat),array($end_lon,$end_lat));
		$sx = deg2rad($begin_lon);
		$sy = deg2rad($begin_lat);
		$ex = deg2rad($end_lon);
		$ey = deg2rad($end_lat);
		$w = $sx - $ex;
		$h = $sy - $ey;
		$z = pow(sin($h/2.0),2) + cos($sy)*cos($ey)*pow(sin($w/2.0),2);
		$g = 2.0*asin(sqrt($z));
		if ($g == M_PI || is_nan($g)) return array(array($begin_lon,$begin_lat),array($end_lon,$end_lat));
		$first_pass = array();
		$delta = 1.0/($nbpts-1);
		for ($i =0; $i < $nbpts; ++$i) {
			$step = $delta*$i;
			$A = sin((1 - $step) * $g) / sin($g);
			$B = sin($step * $g) / sin($g);
			$x = $A * cos($sy) * cos($sx) + $B * cos($ey) * cos($ex);
			$y = $A * cos($sy) * sin($sx) + $B * cos($ey) * sin($ex);
			$z = $A * sin($sy) + $B * sin($ey);
			$lat = rad2deg(atan2($z, sqrt(pow($x, 2) + pow($y, 2))));
			$lon = rad2deg(atan2($y, $x));
			$first_pass[] = array($lon,$lat);
		}
		$bHasBigDiff = false;
		$dfMaxSmallDiffLong = 0;
		// from http://www.gdal.org/ogr2ogr.html
		// -datelineoffset:
		// (starting with GDAL 1.10) offset from dateline in degrees (default long. = +/- 10deg, geometries within 170deg to -170deg will be splited)
		$dfDateLineOffset = $offset;
		$dfLeftBorderX = 180 - $dfDateLineOffset;
		$dfRightBorderX = -180 + $dfDateLineOffset;
		$dfDiffSpace = 360 - $dfDateLineOffset;
		
		// https://github.com/OSGeo/gdal/blob/7bfb9c452a59aac958bff0c8386b891edf8154ca/gdal/ogr/ogrgeometryfactory.cpp#L2342
		$first_pass_ln = count($first_pass);
		for ($j = 1; $j < $first_pass_ln; ++$j) {
			$dfPrevX = $first_pass[$j-1][0];
			$dfX = $first_pass[$j][0];
			$dfDiffLong = abs($dfX - $dfPrevX);
			if ($dfDiffLong > $dfDiffSpace &&
			    (($dfX > $dfLeftBorderX && $dfPrevX < $dfRightBorderX) || ($dfPrevX > $dfLeftBorderX && $dfX < $dfRightBorderX))) 
			{
				$bHasBigDiff = true;
			} else if ($dfDiffLong > $dfMaxSmallDiffLong) {
				$dfMaxSmallDiffLong = $dfDiffLong;
			}
		}
		$poMulti = array();
		$first_pass_ln = count($first_pass);
		if ($bHasBigDiff && $dfMaxSmallDiffLong < $dfDateLineOffset) {
			$poNewLS = array();
			//$poMulti[] = $poNewLS;
			for ($k = 0; $k < $first_pass_ln; ++$k) {
				$dfX0 = floatval($first_pass[$k][0]);
				if ($k > 0 &&  abs($dfX0 - $first_pass[$k-1][0]) > $dfDiffSpace) {
					$dfX1 = floatval($first_pass[$k-1][0]);
					$dfY1 = floatval($first_pass[$k-1][1]);
					$dfX2 = floatval($first_pass[$k][0]);
					$dfY2 = floatval($first_pass[$k][1]);
					if ($dfX1 > -180 && $dfX1 < $dfRightBorderX && $dfX2 == 180 &&
					    $k+1 < count($first_pass) &&
					    $first_pass[$k-1][0] > -180 && $first_pass[$k-1][0] < $dfRightBorderX)
					{
						$poNewLS[] = array(-180, $first_pass[$k][1]);
						$k++;
						//echo 'here';
						$poNewLS[] = array($first_pass[$k][0], $first_pass[$k][1]);
						continue;
					} else if ($dfX1 > $dfLeftBorderX && $dfX1 < 180 && $dfX2 == -180 &&
					    $k+1 < $first_pass_ln &&
					    $first_pass[$k-1][0] > $dfLeftBorderX && $first_pass[$k-1][0] < 180)
					{
						$poNewLS[] = array(180, $first_pass[$k][1]);
						$k++;
						$poNewLS[] = array($first_pass[$k][0], $first_pass[$k][1]);
						continue;
					}
					if ($dfX1 < $dfRightBorderX && $dfX2 > $dfLeftBorderX)
					{
						// swap dfX1, dfX2
						$tmpX = $dfX1;
						$dfX1 = $dfX2;
						$dfX2 = $tmpX;
						// swap dfY1, dfY2
						$tmpY = $dfY1;
						$dfY1 = $dfY2;
						$dfY2 = $tmpY;
					}
					if ($dfX1 > $dfLeftBorderX && $dfX2 < $dfRightBorderX) {
						$dfX2 += 360;
					}
					if ($dfX1 <= 180 && $dfX2 >= 180 && $dfX1 < $dfX2)
					{
						$dfRatio = (180 - $dfX1) / ($dfX2 - $dfX1);
						$dfY = $dfRatio * $dfY2 + (1 - $dfRatio) * $dfY1;
						$poNewLS[] = array($first_pass[$k-1][0] > $dfLeftBorderX ? 180 : -180, $dfY);
						$poMulti[] = $poNewLS;
						$poNewLS = array();
						$poNewLS[] = array($first_pass[$k-1][0] > $dfLeftBorderX ? -180 : 180, $dfY);
						//$poMulti[] = $poNewLS;
					} else {
						//$poNewLS[] = array();
						$poMulti[] = $poNewLS;
						$poNewLS = array();
					}
					$poNewLS[] = array($dfX0, $first_pass[$k][1]);
				} else {
					$poNewLS[] = array($first_pass[$k][0], $first_pass[$k][1]);
				}
			}
			$poMulti[] = $poNewLS;
		} else {
			// add normally
			$poNewLS0 = array();
			//$poMulti[] = $poNewLS0;
			for ($l = 0; $l < $first_pass_ln; ++$l) {
				$poNewLS0[] = array($first_pass[$l][0],$first_pass[$l][1]);
			}
			$poMulti[] = $poNewLS0;
		}
		return $poMulti;
	}
}
?>