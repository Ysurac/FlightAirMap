<?php
/**
 * This class is part of FlightAirmap. It's used to get Image
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.Common.php');
require_once(dirname(__FILE__).'/settings.php');

class Image {
	public $db;

	/*
	 * Initialize connection to DB
	*/
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Image)');
	}

    /**
     * Gets the images based on the aircraft registration
     *
     * @param $registration
     * @param string $aircraft_icao
     * @param string $airline_icao
     * @return array the images list
     */
	public function getSpotterImage($registration,$aircraft_icao = '', $airline_icao = '')
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$reg = $registration;
		if (($reg == '' || $reg == 'NA') && $aircraft_icao != '') $reg = $aircraft_icao.$airline_icao;
		$reg = trim($reg);
		$query  = "SELECT spotter_image.image, spotter_image.image_thumbnail, spotter_image.image_source, spotter_image.image_source_website,spotter_image.image_copyright, spotter_image.registration 
			FROM spotter_image 
			WHERE spotter_image.registration = :registration LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $reg));
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($result)) return $result;
		elseif ($registration != '' && ($aircraft_icao != '' || $airline_icao != '')) return $this->getSpotterImage('',$aircraft_icao,$airline_icao);
		else return array();
	}

    /**
     * Gets the images based on the ship name
     *
     * @param $mmsi
     * @param string $imo
     * @param string $name
     * @param string $type_name
     * @return array the images list
     */
	public function getMarineImage($mmsi,$imo = '',$name = '',$type_name = '')
	{
		global $globalMarineImagePics;
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_STRING);
		$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$name = filter_var($name,FILTER_SANITIZE_STRING);
		$type_name = str_replace('&#39;',"'",filter_var($type_name,FILTER_SANITIZE_STRING));
		if (isset($globalMarineImagePics) && !empty($globalMarineImagePics)) {
			if ($type_name != '' && isset($globalMarineImagePics['type'][$type_name])) {
				if (!isset($globalMarineImagePics['type'][$type_name]['image_thumbnail'])) {
					$globalMarineImagePics['type'][$type_name]['image_thumbnail'] = $globalMarineImagePics['type'][$type_name]['image'];
				}
				return array($globalMarineImagePics['type'][$type_name]+array('image_thumbnail' => '','image' => '', 'image_copyright' => '','image_source' => '','image_source_website' => ''));
			}
		}
		$name = trim($name);
		if ($mmsi == '' && $imo == '' && $name == '') return array();
		$query  = "SELECT marine_image.image, marine_image.image_thumbnail, marine_image.image_source, marine_image.image_source_website,marine_image.image_copyright, marine_image.mmsi, marine_image.imo, marine_image.name 
			FROM marine_image 
			WHERE marine_image.mmsi = :mmsi";
		$query_data = array(':mmsi' => $mmsi);
		if ($imo != '') {
			$query .= " AND marine_image.imo = :imo";
			$query_data = array_merge($query_data,array(':imo' => $imo));
		}
		if ($name != '') {
			$query .= " AND marine_image.name = :name";
			$query_data = array_merge($query_data,array(':name' => $name));
		}
		$query .= " LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	}

    /**
     * Gets the image copyright based on the Exif data
     *
     * @param $url
     * @return String image copyright
     */
	public function getExifCopyright($url) {
		$exif = exif_read_data($url);
		$copyright = '';
		if (isset($exif['COMPUTED']['copyright'])) $copyright = $exif['COMPUTED']['copyright'];
		elseif (isset($exif['copyright'])) $copyright = $exif['copyright'];
		if ($copyright != '') {
			$copyright = str_replace('Copyright ','',$copyright);
			$copyright = str_replace('© ','',$copyright);
			$copyright = str_replace('(c) ','',$copyright);
		}
		return $copyright;
	}

    /**
     * Adds the images based on the aircraft registration
     *
     * @param $registration
     * @param string $aircraft_icao
     * @param string $airline_icao
     * @return String either success or error
     */
	public function addSpotterImage($registration,$aircraft_icao = '', $airline_icao = '')
	{
		global $globalDebug,$globalAircraftImageFetch, $globalOffline;
		if ((isset($globalAircraftImageFetch) && $globalAircraftImageFetch === FALSE) || (isset($globalOffline) && $globalOffline === TRUE)) return '';
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);
		//getting the aircraft image
		if ($globalDebug && $registration != '') echo 'Try to find an aircraft image for '.$registration.'...';
		elseif ($globalDebug && $aircraft_icao != '') echo 'Try to find an aircraft image for '.$aircraft_icao.'...';
		elseif ($globalDebug && $airline_icao != '') echo 'Try to find an aircraft image for '.$airline_icao.'...';
		$image_url = $this->findAircraftImage($registration,$aircraft_icao,$airline_icao);
		if ($registration == '' && $aircraft_icao != '') $registration = $aircraft_icao.$airline_icao;
		if ($image_url['original'] != '') {
			if ($globalDebug) echo 'Found !'."\n";
			$query  = "INSERT INTO spotter_image (registration, image, image_thumbnail, image_copyright, image_source,image_source_website) VALUES (:registration,:image,:image_thumbnail,:copyright,:source,:source_website)";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':registration' => $registration,':image' => $image_url['original'],':image_thumbnail' => $image_url['thumbnail'], ':copyright' => $image_url['copyright'],':source' => $image_url['source'],':source_website' => $image_url['source_website']));
			} catch(PDOException $e) {
				echo $e->getMessage()."\n";
				return "error";
			}
		} elseif ($globalDebug) echo "Not found :'(\n";
		return "success";
	}

    /**
     * Adds the images based on the marine name
     *
     * @param $mmsi
     * @param string $imo
     * @param string $name
     * @return String either success or error
     */
	public function addMarineImage($mmsi,$imo = '',$name = '')
	{
		global $globalDebug,$globalMarineImageFetch, $globalOffline;
		if ((isset($globalMarineImageFetch) && !$globalMarineImageFetch) || (isset($globalOffline) && $globalOffline === TRUE)) return '';
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_STRING);
		$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$name = filter_var($name,FILTER_SANITIZE_STRING);
		$name = trim($name);
		$Marine = new Marine($this->db);
		if ($imo == '' || $name == '') {
			$identity = $Marine->getIdentity($mmsi);
			if (isset($identity[0]['mmsi'])) {
				$imo = $identity[0]['imo'];
				if ($identity[0]['ship_name'] != '') $name = $identity[0]['ship_name'];
			}
		}
		unset($Marine);

		//getting the aircraft image
		if ($globalDebug && $name != '') echo 'Try to find an vessel image for '.$name.'...';
		$image_url = $this->findMarineImage($mmsi,$imo,$name);
		if ($image_url['original'] != '') {
			if ($globalDebug) echo 'Found !'."\n";
			$query  = "INSERT INTO marine_image (mmsi,imo,name, image, image_thumbnail, image_copyright, image_source,image_source_website) VALUES (:mmsi,:imo,:name,:image,:image_thumbnail,:copyright,:source,:source_website)";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':mmsi' => $mmsi,':imo' => $imo,':name' => $name,':image' => $image_url['original'],':image_thumbnail' => $image_url['thumbnail'], ':copyright' => $image_url['copyright'],':source' => $image_url['source'],':source_website' => $image_url['source_website']));
			} catch(PDOException $e) {
				echo $e->getMessage()."\n";
				return "error";
			}
		} elseif ($globalDebug) echo "Not found :'(\n";
		return "success";
	}

    /**
     * Gets the aircraft image
     *
     * @param String $aircraft_registration the registration of the aircraft
     * @param string $aircraft_icao
     * @param string $airline_icao
     * @return array the aircraft thumbnail, orignal url and copyright
     */
	public function findAircraftImage($aircraft_registration, $aircraft_icao = '', $airline_icao = '')
	{
		global $globalAircraftImageSources, $globalIVAO, $globalAircraftImageCheckICAO, $globalVA;
		$Spotter = new Spotter($this->db);
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
		$aircraft_registration = filter_var($aircraft_registration,FILTER_SANITIZE_STRING);
		if ($aircraft_registration != '' && $aircraft_registration != 'NA' && (!isset($globalVA) || $globalVA !== TRUE)) {
			if (strpos($aircraft_registration,'/') !== false) return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
			$aircraft_registration = urlencode(trim($aircraft_registration));
			$aircraft_info = $Spotter->getAircraftInfoByRegistration($aircraft_registration);
			if (isset($aircraft_info[0]['aircraft_name'])) $aircraft_name = $aircraft_info[0]['aircraft_name'];
			else $aircraft_name = '';
			if (isset($aircraft_info[0]['aircraft_icao'])) $aircraft_name = $aircraft_info[0]['aircraft_icao'];
			else $aircraft_icao = '';
			if (isset($aircraft_info[0]['airline_icao'])) $airline_icao = $aircraft_info[0]['airline_icao'];
			else $airline_icao = '';
		} elseif ($aircraft_icao != '') {
			$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_icao);
			if (isset($aircraft_info[0]['type'])) $aircraft_name = $aircraft_info[0]['type'];
			else $aircraft_name = '';
			$aircraft_registration = $aircraft_icao;
		} else return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '','source_website' => '');
		unset($Spotter);
		if (!isset($globalAircraftImageSources)) $globalAircraftImageSources = array('ivaomtl','wikimedia','airportdata','deviantart','flickr','bing','jetphotos','planepictures','planespotters');
		foreach ($globalAircraftImageSources as $source) {
			$source = strtolower($source);
			if ($source == 'ivaomtl' && $globalIVAO && $aircraft_icao != '' && $airline_icao != '') $images_array = $this->fromIvaoMtl('aircraft',$aircraft_icao,$airline_icao);
			if ($source == 'planespotters' && !$globalIVAO && extension_loaded('simplexml')) $images_array = $this->fromPlanespotters('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'flickr' && extension_loaded('simplexml')) $images_array = $this->fromFlickr('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'bing') $images_array = $this->fromBing('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'deviantart' && extension_loaded('simplexml')) $images_array = $this->fromDeviantart('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'wikimedia') $images_array = $this->fromWikimedia('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'jetphotos' && !$globalIVAO && class_exists("DomDocument")) $images_array = $this->fromJetPhotos('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'planepictures' && !$globalIVAO && class_exists("DomDocument")) $images_array = $this->fromPlanePictures('aircraft',$aircraft_registration,$aircraft_name);
			if ($source == 'airportdata' && !$globalIVAO) $images_array = $this->fromAirportData('aircraft',$aircraft_registration,$aircraft_icao,$aircraft_name);
			if ($source == 'customsources') $images_array = $this->fromCustomSource('aircraft',$aircraft_registration,$aircraft_name);
			if (isset($images_array) && $images_array['original'] != '') return $images_array;
		}
		if ((!isset($globalAircraftImageCheckICAO) || $globalAircraftImageCheckICAO === TRUE) && isset($aircraft_icao)) return $this->findAircraftImage($aircraft_icao);
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the vessel image
	*
	* @param String $mmsi the vessel mmsi
	* @param String $imo the vessel imo
	* @param String $name the vessel name
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function findMarineImage($mmsi,$imo = '',$name = '')
	{
		global $globalMarineImageSources;
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_STRING);
		//$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$name = filter_var($name,FILTER_SANITIZE_STRING);
		$name = trim($name);
		if (strlen($name) < 4) return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '','source_website' => '');
		/*
		$Marine = new Marine($this->db);
		if ($imo == '' || $name == '') {
			$identity = $Marine->getIdentity($mmsi);
			if (isset($identity[0]['mmsi'])) {
				$imo = $identity[0]['imo'];
				$name = $identity[0]['ship_name'];
			}
		}
		unset($Marine);
		*/
		if (!isset($globalMarineImageSources)) $globalMarineImageSources = array('wikimedia','deviantart','flickr','bing');
		foreach ($globalMarineImageSources as $source) {
			$source = strtolower($source);
			if ($source == 'flickr') $images_array = $this->fromFlickr('marine',$mmsi,$name);
			if ($source == 'bing') $images_array = $this->fromBing('marine',$mmsi,$name);
			if ($source == 'deviantart') $images_array = $this->fromDeviantart('marine',$mmsi,$name);
			if ($source == 'wikimedia') $images_array = $this->fromWikimedia('marine',$mmsi,$name);
			if ($source == 'customsources') $images_array = $this->fromCustomSource('marine',$mmsi,$name);
			if (isset($images_array) && $images_array['original'] != '') return $images_array;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from Planespotters
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromPlanespotters($type,$aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		// If aircraft registration is only number, also check with aircraft model
		if (preg_match('/^[[:digit]]+$/',$aircraft_registration) && $aircraft_name != '') {
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$aircraft_registration.'&actype=s_'.urlencode($aircraft_name).'&output=rss';
		} else {
			//$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$airline_aircraft_type.'&output=rss';
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?reg='.$aircraft_registration.'&output=rss';
		}
		$data = $Common->getData($url);
		if (substr($data, 0, 5) != "<?xml") return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		if ($xml = simplexml_load_string($data)) {
			if (isset($xml->channel->item)) {
				$image_url = array();
				$thumbnail_url = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()->url);
				$image_url['thumbnail'] = $thumbnail_url;
				$image_url['original'] = str_replace('thumbnail','original',$thumbnail_url);
				$image_url['copyright'] = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->copyright);
				$image_url['source_website'] = trim((string)$xml->channel->item->link);
				$image_url['source'] = 'planespotters';
				return $image_url;
			}
		} 
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

    /**
     * Gets the aircraft image from Deviantart
     *
     * @param $type
     * @param String $registration the registration of the aircraft
     * @param String $name type of the aircraft
     * @return array the aircraft thumbnail, orignal url and copyright
     */
	public function fromDeviantart($type,$registration, $name='') {
		$Common = new Common();
		if ($type == 'aircraft') {
			// If aircraft registration is only number, also check with aircraft model
			if (preg_match('/^[[:digit]]+$/',$registration) && $name != '') {
				$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q='.$registration.'%20'.urlencode($name);
			} else {
				$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q=aircraft%20'.$registration;
			}
		} elseif ($type == 'marine') {
			$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q=ship%20"'.urlencode($name).'"';
		} else {
			$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q="'.urlencode($name).'"';
		}
		$data = $Common->getData($url);
		if (substr($data, 0, 5) != "<?xml") return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		if ($xml = simplexml_load_string($data)) {
			if (isset($xml->channel->item->link)) {
				$image_url = array();
				$thumbnail_url = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()->url);
				$image_url['thumbnail'] = $thumbnail_url;
				$original_url = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->content->attributes()->url);
				$image_url['original'] = $original_url;
				$image_url['copyright'] = str_replace('Copyright ','',trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->copyright));
				$image_url['source_website'] = trim((string)$xml->channel->item->link);
				$image_url['source'] = 'deviantart';
				return $image_url;
			}
		} 
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from JetPhotos
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromJetPhotos($type,$aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		$url= 'https://www.jetphotos.com/photo/keyword/'.$aircraft_registration;
		$data = $Common->getData($url);
		$dom = new DOMDocument();
		@$dom->loadHTML($data);
		$all_pics = array();
                foreach($dom->getElementsByTagName('img') as $image) {
                 $all_pics[] = $image->getAttribute('src');
                }
		$all_authors = array();
                foreach($dom->getElementsByTagName('span') as $author) {
                 if (strpos($author->nodeValue, "By: ") !== false) {
                  $all_authors[] = $author->nodeValue;
                 }
                }
		$all_ref = array();
                foreach($dom->getElementsByTagName('a') as $link) {
                 if (strpos($link->getAttribute('href'), "/photo/") !== false) {
                  $all_ref[] = $link->getAttribute('href');
                 }
                }
		if (isset($all_pics[8])) {
			$image_url = array();
			$image_url['thumbnail'] = 'http:'.$all_pics[3];
			$image_url['original'] = 'http:'.str_replace('/400/','/full/',$all_pics[3]);
			$image_url['copyright'] = str_replace('By: ','',$all_authors[0]);
			$image_url['source_website'] = 'https://jetphotos.net'.$all_ref[0];
			$image_url['source'] = 'JetPhotos';
			return $image_url;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from PlanePictures
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromPlanePictures($type,$aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		$url= 'https://www.planepictures.net/v3/search_en.php?srch='.$aircraft_registration.'&stype=reg&srng=2';
		$data = $Common->getData($url);
		$dom = new DOMDocument();
		@$dom->loadHTML($data);
		$all_pics = array();
		foreach($dom->getElementsByTagName('img') as $image) {
			$all_pics[] = $image->getAttribute('src');
		}
		$all_links = array();
		foreach($dom->getElementsByTagName('a') as $link) {
			$all_links[] = array('text' => $link->textContent,'href' => $link->getAttribute('href'));
		}
		if (isset($all_pics[4])) {
			$image_url = array();
			$image_url['thumbnail'] = 'http://www.planepictures.net'.$all_pics[4];
			$image_url['original'] = 'http://www.planepictures.net'.str_replace('_TN','',$all_pics[4]);
			$image_url['copyright'] = $all_links[28]['text'];
			$image_url['source_website'] = 'https://www.planepictures.net'.str_replace('./','/',$all_links[23]['href']);
			$image_url['source'] = 'PlanePictures';
			return $image_url;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from Flickr
	*
	* @param String $registration the registration of the aircraft
	* @param String $name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromFlickr($type,$registration,$name='') {
		$Common = new Common();
		if ($type == 'aircraft') {
			if ($name != '') $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$registration.','.urlencode($name);
			else $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$registration.',aircraft';
		} elseif ($type == 'marine') {
			if ($name != '') $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags=ship,'.urlencode($name);
			else $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$registration.',ship';
		}
		$data = $Common->getData($url);
		if (substr($data, 0, 5) != "<?xml") return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		if ($xml = simplexml_load_string($data)) {
			if (isset($xml->channel->item)) {
				$original_url = trim((string)$xml->channel->item->enclosure->attributes()->url);
				$image_url = array();
				$image_url['thumbnail'] = $original_url;
				$image_url['original'] = $original_url;
				$image_url['copyright'] = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->credit);
				$image_url['source_website'] = trim((string)$xml->channel->item->link);
				$image_url['source'] = 'flickr';
				return $image_url;
			}
		} 
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

    /**
     * @param $type
     * @param $aircraft_icao
     * @param $airline_icao
     * @return array
     */
    public function fromIvaoMtl($type, $aircraft_icao, $airline_icao) {
		$Common = new Common();
		//echo "\n".'SEARCH IMAGE : http://mtlcatalog.ivao.aero/images/aircraft/'.$aircraft_icao.$airline_icao.'.jpg';
		if ($Common->urlexist('http://mtlcatalog.ivao.aero/images/aircraft/'.$aircraft_icao.$airline_icao.'.jpg')) {
			$image_url = array();
			$image_url['thumbnail'] = 'http://mtlcatalog.ivao.aero/images/aircraft/'.$aircraft_icao.$airline_icao.'.jpg';
			$image_url['original'] = 'http://mtlcatalog.ivao.aero/images/aircraft/'.$aircraft_icao.$airline_icao.'.jpg';
			$image_url['copyright'] = 'IVAO';
			$image_url['source_website'] = 'http://mtlcatalog.ivao.aero/';
			$image_url['source'] = 'ivao.aero';
			return $image_url;
		} else {
			return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		}
	}

    /**
     * Gets the aircraft image from Bing
     *
     * @param $type
     * @param String $aircraft_registration the registration of the aircraft
     * @param String $aircraft_name type of the aircraft
     * @return array the aircraft thumbnail, orignal url and copyright
     */
	public function fromBing($type,$aircraft_registration,$aircraft_name='') {
		global $globalImageBingKey;
		$Common = new Common();
		if (!isset($globalImageBingKey) || $globalImageBingKey == '') return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		if ($type == 'aircraft') {
			if ($aircraft_name != '') $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27'.$aircraft_registration.'%20'.urlencode($aircraft_name).'%20-site:planespotters.com%20-site:flickr.com%27';
			else $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27%2B'.$aircraft_registration.'%20%2Baircraft%20-site:planespotters.com%20-site:flickr.com%27';
		} elseif ($type == 'marine') {
			if ($aircraft_name != '') $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27'.urlencode($aircraft_name).'%20%2Bship%20-site:flickr.com%27';
			else $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27%2B'.$aircraft_registration.'%20%2Bship%20-site:flickr.com%27';
		}
		$headers = array("Authorization: Basic " . base64_encode("ignored:".$globalImageBingKey));
		$data = $Common->getData($url,'get','',$headers);
		$result = json_decode($data);
		if (isset($result->d->results[0]->MediaUrl)) {
			$image_url = array();
			$image_url['original'] = $result->d->results[0]->MediaUrl;
			$image_url['source_website'] = $result->d->results[0]->SourceUrl;
			// Thumbnail can't be used this way...
			// $image_url['thumbnail'] = $result->d->results[0]->Thumbnail->MediaUrl;
			$image_url['thumbnail'] = $result->d->results[0]->MediaUrl;
			$url = parse_url($image_url['source_website']);
			$image_url['copyright'] = $url['host'];
			$image_url['source'] = 'bing';
			return $image_url;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from airport-data
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_icao the icao code of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromAirportData($type,$aircraft_registration,$aircraft_icao,$aircraft_name='') {
		$Common = new Common();
		$url = 'http://www.airport-data.com/api/ac_thumb.json?&n=1&r='.$aircraft_registration.'&m='.$aircraft_icao;
		$data = $Common->getData($url);
		$result = json_decode($data);
		if (isset($result->count) && $result->count > 0) {
			$image_url = array();
			$image_url['original'] = str_replace('thumbnails','large',$result->data[0]->image);
			$image_url['source_website'] = $result->data[0]->link;
			$image_url['thumbnail'] = $result->data[0]->image;
			$image_url['copyright'] = $result->data[0]->photographer;
			$image_url['source'] = 'AirportData';
			return $image_url;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets image from WikiMedia
	*
	* @param String $registration the registration of the aircraft/mmsi
	* @param String $name name
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromWikimedia($type,$registration,$name='') {
		$Common = new Common();
		if ($type == 'aircraft') {
			if ($name != '') $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$registration.'"%20'.urlencode($name);
			else $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$registration.'"%20aircraft';
		} elseif ($type == 'marine') {
			if ($name != '') $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.urlencode($name).'%20ship"';
			else return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		}
		$data = $Common->getData($url);
		$result = json_decode($data);
		if (isset($result->query->search[0]->title)) {
			$fileo = $result->query->search[0]->title;
			if (substr($fileo,-3) == 'pdf') return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
			$file = urlencode($fileo);
			$url2 = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&continue&iilimit=500&prop=imageinfo&iiprop=user|url|size|mime|sha1|timestamp&iiurlwidth=200%27&titles='.$file;
			$data2 = $Common->getData($url2);
			$result2 = json_decode($data2);
			if (isset($result2->query->pages)) {
				foreach ($result2->query->pages as $page) {
					if (isset($page->imageinfo[0]->user)) {
						$image_url = array();
						$image_url['copyright'] = 'Wikimedia, '.$page->imageinfo[0]->user;
						$image_url['original'] = $page->imageinfo[0]->url;
						$image_url['thumbnail'] = $page->imageinfo[0]->thumburl;
						$image_url['source'] = 'wikimedia';
						$image_url['source_website'] = 'http://commons.wikimedia.org/wiki/'.$fileo;
						//return $image_url;
					}
				}
			}
			if (isset($image_url['original'])) {
				$url2 = 'https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&iiprop=extmetadata&format=json&continue&titles='.$file;
				$data2 = $Common->getData($url2);
				$result2 = json_decode($data2);
				if (isset($result2->query->pages)) {
					foreach ($result2->query->pages as $page) {
						if (isset($page->imageinfo[0]->extmetadata->Artist)) {
							$image_url['copyright'] = preg_replace('/ from(.*)/','',strip_tags($page->imageinfo[0]->extmetadata->Artist->value));
							if (isset($page->imageinfo[0]->extmetadata->License->value)) {
								$image_url['copyright'] = $image_url['copyright'].' (under '.$page->imageinfo[0]->extmetadata->License->value.')';
							}
							$image_url['copyright'] = trim(str_replace('\n','',$image_url['copyright']));
							return $image_url;
						}
					}
				}
				return $image_url;
			}
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from custom url
	*
	* @param String $registration the registration of the aircraft
	* @param String $name type of the aircraft
	* @return array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromCustomSource($type,$registration,$name='') {
		global $globalAircraftImageCustomSources, $globalMarineImageCustomSources, $globalDebug;
		//$globalAircraftImageCustomSource[] = array('thumbnail' => '','original' => '', 'copyright' => '', 'source_website' => '', 'source' => '','exif' => true);
		if (!empty($globalAircraftImageCustomSources) && $type == 'aircraft') {
			$customsources = array();
			if (!isset($globalAircraftImageCustomSources[0])) {
				$customsources[] = $globalAircraftImageCustomSources;
			} else {
				$customsources = $globalAircraftImageCustomSources;
			}
			foreach ($customsources as $source) {
				$Common = new Common();
				if (!isset($source['original']) && $globalDebug) {
					echo 'original entry not found for $globalAircraftImageCustomSources.';
					print_r($source);
					print_r($customsources);
				}
				$url = str_replace('{registration}',$registration,$source['original']);
				$url_thumbnail = str_replace('{registration}',$registration,$source['thumbnail']);
				if ($Common->urlexist($url)) {
					$image_url = array();
					$image_url['thumbnail'] = $url_thumbnail;
					$image_url['original'] = $url;
					if ($source['exif'] && exif_imagetype($url) == IMAGETYPE_JPEG) $exifCopyright = $this->getExifCopyright($url);
					else $exifCopyright = '';
					if ($exifCopyright  != '') $image_url['copyright'] = $exifCopyright;
					elseif (isset($source['copyright'])) $image_url['copyright'] = $source['copyright'];
					else $image_url['copyright'] = $source['source_website'];
					$image_url['source_website'] = $source['source_website'];
					$image_url['source'] = $source['source'];
					return $image_url;
				}
			}
			return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
		} else return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');

		if (!empty($globalMarineImageCustomSources) && $type == 'marine') {
			$customsources = array();
			if (!isset($globalMarineImageCustomSources[0])) {
				$customsources[] = $globalMarineImageCustomSources;
			} else {
				$customsources = $globalMarineImageCustomSources;
			}
			foreach ($customsources as $source) {
				$Common = new Common();
				if (!isset($source['original']) && $globalDebug) {
					echo 'original entry not found for $globalMarineImageCustomSources.';
					print_r($source);
					print_r($customsources);
				}
				$url = str_replace('{registration}',$registration,$source['original']);
				$url = str_replace('{mmsi}',$registration,$url);
				$url = str_replace('{name}',$name,$url);
				$url_thumbnail = str_replace('{registration}',$registration,$source['thumbnail']);
				$url_thumbnail = str_replace('{mmsi}',$registration,$url_thumbnail);
				$url_thumbnail = str_replace('{name}',$name,$url_thumbnail);
				if ($Common->urlexist($url)) {
					$image_url = array();
					$image_url['thumbnail'] = $url_thumbnail;
					$image_url['original'] = $url;
					if ($source['exif'] && exif_imagetype($url) == IMAGETYPE_JPEG) $exifCopyright = $this->getExifCopyright($url);
					else $exifCopyright = '';
					if ($exifCopyright  != '') $image_url['copyright'] = $exifCopyright;
					elseif (isset($source['copyright'])) $image_url['copyright'] = $source['copyright'];
					else $image_url['copyright'] = $source['source_website'];
					$image_url['source_website'] = $source['source_website'];
					$image_url['source'] = $source['source'];
					return $image_url;
				}
			}
			return false;
		} else return false;
	}
}

//$Image = new Image();
//print_r($Image->fromAirportData('F-GZHM'));

?>
