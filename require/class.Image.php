<?php
require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.Common.php');
require_once(dirname(__FILE__).'/settings.php');

class Image {
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
	}

	/**
	* Gets the images based on the aircraft registration
	*
	* @return Array the images list
	*
	*/
	public function getSpotterImage($registration,$aircraft_icao = '', $airline_icao = '')
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		if ($registration == '' && $aircraft_icao != '') $registration = $aircraft_icao.$airline_icao;
		$registration = trim($registration);
		$query  = "SELECT spotter_image.image, spotter_image.image_thumbnail, spotter_image.image_source, spotter_image.image_source_website,spotter_image.image_copyright, spotter_image.registration 
			FROM spotter_image 
			WHERE spotter_image.registration = :registration";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
          /*
        $images_array = array();
	$temp_array = array();

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
	{
	    //$temp_array['spotter_image_id'] = $row['spotter_image_id'];
            $temp_array['registration'] = $row['registration'];
            $temp_array['image'] = $row['image'];
            $temp_array['image_thumbnail'] = $row['image_thumbnail'];
            $temp_array['image_source'] = $row['image_source'];
            $temp_array['image_source_website'] = $row['image_source_website'];
            $temp_array['image_copyright'] = $row['image_copyright'];
          
            $images_array[] = $temp_array;
	}
        
        return $images_array;
        */
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	* Gets the image copyright based on the Exif data
	*
	* @return String image copyright
	*
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
	* @return String either success or error
	*
	*/
	public function addSpotterImage($registration,$aircraft_icao = '', $airline_icao = '')
	{
		global $globalDebug,$globalAircraftImageFetch;
		if (isset($globalAircraftImageFetch) && !$globalAircraftImageFetch) return '';
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);
		//getting the aircraft image
		if ($globalDebug && $registration != '') echo 'Try to find an aircraft image for '.$registration.'...';
		elseif ($globalDebug && $aircraft_icao != '') echo 'Try to find an aircraft image for '.$aircraft_icao.'...';
		elseif ($globalDebug && $airline_icao != '') echo 'Try to find an aircraft image for '.$airline_icao.'...';
		else return "success";
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
	* Gets the aircraft image
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function findAircraftImage($aircraft_registration, $aircraft_icao = '', $airline_icao = '')
	{
		global $globalAircraftImageSources, $globalIVAO;
		$Spotter = new Spotter($this->db);
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
		$aircraft_registration = filter_var($aircraft_registration,FILTER_SANITIZE_STRING);
		if ($aircraft_registration != '') {
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
			$aircraft_registration = $aircraft_icao;
			$aircraft_name = '';
		} else return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '','source_website' => '');
		if (!isset($globalAircraftImageSources)) $globalAircraftImageSources = array('ivaomtl','wikimedia','airportdata','deviantart','flickr','bing','jetphotos','planepictures','planespotters');
		foreach ($globalAircraftImageSources as $source) {
			$source = strtolower($source);
			if ($source == 'ivaomtl' && $globalIVAO && $aircraft_icao != '' && $airline_icao != '') $images_array = $this->fromIvaoMtl($aircraft_icao,$airline_icao);
			if ($source == 'planespotters' && !$globalIVAO) $images_array = $this->fromPlanespotters($aircraft_registration,$aircraft_name);
			if ($source == 'flickr') $images_array = $this->fromFlickr($aircraft_registration,$aircraft_name);
			if ($source == 'bing') $images_array = $this->fromBing($aircraft_registration,$aircraft_name);
			if ($source == 'deviantart') $images_array = $this->fromDeviantart($aircraft_registration,$aircraft_name);
			if ($source == 'wikimedia') $images_array = $this->fromWikimedia($aircraft_registration,$aircraft_name);
			if ($source == 'jetphotos' && !$globalIVAO) $images_array = $this->fromJetPhotos($aircraft_registration,$aircraft_name);
			if ($source == 'planepictures' && !$globalIVAO) $images_array = $this->fromPlanePictures($aircraft_registration,$aircraft_name);
			if ($source == 'airportdata' && !$globalIVAO) $images_array = $this->fromAirportData($aircraft_registration,$aircraft_name);
			if ($source == 'customsources') $images_array = $this->fromCustomSource($aircraft_registration,$aircraft_name);
			if (isset($images_array) && $images_array['original'] != '') return $images_array;
		}
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');
	}

	/**
	* Gets the aircraft image from Planespotters
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromPlanespotters($aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		// If aircraft registration is only number, also check with aircraft model
		if (preg_match('/^[[:digit]]+$/',$aircraft_registration) && $aircraft_name != '') {
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$aircraft_registration.'&actype=s_'.urlencode($aircraft_name).'&output=rss';
		} else {
			//$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$airline_aircraft_type.'&output=rss';
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?reg='.$aircraft_registration.'&output=rss';
		}
		$data = $Common->getData($url);
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
		return false;
	}

	/**
	* Gets the aircraft image from Deviantart
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromDeviantart($aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		// If aircraft registration is only number, also check with aircraft model
		if (preg_match('/^[[:digit]]+$/',$aircraft_registration) && $aircraft_name != '') {
			$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q='.$aircraft_registration.'%20'.urlencode($aircraft_name);
		} else {
			$url= 'http://backend.deviantart.com/rss.xml?type=deviation&q=aircraft%20'.$aircraft_registration;
		}

		$data = $Common->getData($url);
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
		return false;
	}

	/**
	* Gets the aircraft image from JetPhotos
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromJetPhotos($aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		$url= 'http://jetphotos.net/showphotos.php?displaymode=2&regsearch='.$aircraft_registration;
		$data = $Common->getData($url);
		$dom = new DOMDocument();
		@$dom->loadHTML($data);
		$all_pics = array();
		foreach($dom->getElementsByTagName('img') as $image) {
			if ($image->getAttribute('itemprop') == "http://schema.org/image") {
				$all_pics[] = $image->getAttribute('src');
			}
		}
		$all_authors = array();
		foreach($dom->getElementsByTagName('meta') as $author) {
			if ($author->getAttribute('itemprop') == "http://schema.org/author") {
				$all_authors[] = $author->getAttribute('content');
			}
		}
		$all_ref = array();
		foreach($dom->getElementsByTagName('a') as $link) {
			$all_ref[] = $link->getAttribute('href');
		}
		if (isset($all_pics[0])) {
			$image_url = array();
			$image_url['thumbnail'] = $all_pics[0];
			$image_url['original'] = str_replace('_tb','',$all_pics[0]);
			$image_url['copyright'] = $all_authors[0];
			$image_url['source_website'] = 'http://jetphotos.net'.$all_ref[8];
			$image_url['source'] = 'JetPhotos';
			return $image_url;
		}
		return false;
	}

	/**
	* Gets the aircraft image from PlanePictures
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromPlanePictures($aircraft_registration, $aircraft_name='') {
		$Common = new Common();
		$url= 'http://www.planepictures.net/netsearch4.cgi?srch='.$aircraft_registration.'&stype=reg&srng=2';
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
		if (isset($all_pics[1]) && !preg_match('/bit.ly/',$all_pics[1])) {
			$image_url = array();
			$image_url['thumbnail'] = 'http://www.planepictures.net'.$all_pics[1];
			$image_url['original'] = 'http://www.planepictures.net'.str_replace('_TN','',$all_pics[1]);
			$image_url['copyright'] = $all_links[6]['text'];
			$image_url['source_website'] = 'http://www.planepictures.net/'.$all_links[2]['href'];
			$image_url['source'] = 'PlanePictures';
			return $image_url;
		}
		return false;
	}

	/**
	* Gets the aircraft image from Flickr
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromFlickr($aircraft_registration,$aircraft_name='') {
		$Common = new Common();
		if ($aircraft_name != '') $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$aircraft_registration.','.urlencode($aircraft_name);
		else $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$aircraft_registration.',aircraft';
		$data = $Common->getData($url);
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
		return false;
	}

	public function fromIvaoMtl($aircraft_icao,$airline_icao) {
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
			return false;
		}
	}

	/**
	* Gets the aircraft image from Bing
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromBing($aircraft_registration,$aircraft_name='') {
		global $globalImageBingKey;
		$Common = new Common();
		if (!isset($globalImageBingKey) || $globalImageBingKey == '') return false;
		if ($aircraft_name != '') $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27'.$aircraft_registration.'%20'.urlencode($aircraft_name).'%20-site:planespotters.com%20-site:flickr.com%27';
		else $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27%2B'.$aircraft_registration.'%20%2Baircraft%20-site:planespotters.com%20-site:flickr.com%27';
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
		return false;
	}

	/**
	* Gets the aircraft image from airport-data
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromAirportData($aircraft_registration,$aircraft_name='') {
		$Common = new Common();
		$url = 'http://www.airport-data.com/api/ac_thumb.json?&n=1&r='.$aircraft_registration;
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
		return false;
	}

	/**
	* Gets the aircraft image from WikiMedia
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromWikimedia($aircraft_registration,$aircraft_name='') {
		$Common = new Common();
		if ($aircraft_name != '') $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$aircraft_registration.'"%20'.urlencode($aircraft_name);
		else $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$aircraft_registration.'"%20aircraft';
		$data = $Common->getData($url);
		$result = json_decode($data);
		if (isset($result->query->search[0]->title)) {
			$fileo = $result->query->search[0]->title;
			if (substr($fileo,-3) == 'pdf') return false;
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
		return false;
	}

	/**
	* Gets the aircraft image from custom url
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @param String $aircraft_name type of the aircraft
	* @return Array the aircraft thumbnail, orignal url and copyright
	*
	*/
	public function fromCustomSource($aircraft_registration,$aircraft_name='') {
		global $globalAircraftImageCustomSources, $globalDebug;
		//$globalAircraftImageCustomSource[] = array('thumbnail' => '','original' => '', 'copyright' => '', 'source_website' => '', 'source' => '','exif' => true);
		if (!empty($globalAircraftImageCustomSources)) {
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
				$url = str_replace('{registration}',$aircraft_registration,$source['original']);
				$url_thumbnail = str_replace('{registration}',$aircraft_registration,$source['thumbnail']);
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