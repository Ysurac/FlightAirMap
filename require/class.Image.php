<?php
require_once('class.Spotter.php');
require_once('settings.php');

class Image {

    /**
    * Gets the images based on the aircraft registration
    *
    * @return Array the images list
    *
    */
    public static function getSpotterImage($registration)
    {
	    $registration = filter_var($registration,FILTER_SANITIZE_STRING);
	$registration = trim($registration);

	$query  = "SELECT spotter_image.*
				FROM spotter_image 
				WHERE spotter_image.registration = :registration";

	$Connection = new Connection();
	$sth = Connection::$db->prepare($query);
	$sth->execute(array(':registration' => $registration));
        
        $images_array = array();
	$temp_array = array();

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
	{
	    $temp_array['spotter_image_id'] = $row['spotter_image_id'];
            $temp_array['registration'] = $row['registration'];
            $temp_array['image'] = $row['image'];
            $temp_array['image_thumbnail'] = $row['image_thumbnail'];
            $temp_array['image_source'] = $row['image_source'];
            $temp_array['image_source_website'] = $row['image_source_website'];
            $temp_array['image_copyright'] = $row['image_copyright'];
          
            $images_array[] = $temp_array;
	}
        
        return $images_array;
    }

    
    /**
    * Adds the images based on the aircraft registration
    *
    * @return String either success or error
    *
    */
    public static function addSpotterImage($registration)
    {
	$registration = filter_var($registration,FILTER_SANITIZE_STRING);
	$registration = trim($registration);
	//getting the aircraft image
	$image_url = Image::findAircraftImage($registration);
	if ($image_url['original'] != '') {
	    $query  = "INSERT INTO spotter_image (registration, image, image_thumbnail, image_copyright, image_source,image_source_website) VALUES (:registration,:image,:image_thumbnail,:copyright,:source,:source_website)";
	    try {
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration,':image' => $image_url['original'],':image_thumbnail' => $image_url['thumbnail'], ':copyright' => $image_url['copyright'],':source' => $image_url['source'],':source_website' => $image_url['source_website']));
	    } catch(PDOException $e) {
		echo $e->message;
		return "error";
	    }
	}
	return "success";
    }
    
    
    /**
    * Gets the aircraft image
    *
    * @param String $aircraft_registration the registration of the aircraft
    * @return Array the aircraft thumbnail, orignal url and copyright
    *
    */
    public static function findAircraftImage($aircraft_registration)
    {
	global $globalAircraftImageSources;
	$aircraft_registration = filter_var($aircraft_registration,FILTER_SANITIZE_STRING);
	if (strpos($aircraft_registration,'/') !== false) return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '','source_website' => '');

	$aircraft_registration = urlencode(trim($aircraft_registration));
	$aircraft_info = Spotter::getAircraftInfoByRegistration($aircraft_registration);
        if (isset($aircraft_info[0]['aircraft_name'])) $aircraft_name = $aircraft_info[0]['aircraft_name'];
        else $aircraft_name = '';
	if ($aircraft_registration == '') return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '','source_website' => '');

	if (!isset($globalAircraftImageSources)) $globalAircraftImageSources = array('wikimedia','deviantart','flickr','bing','planespotters');
	
	foreach ($globalAircraftImageSources as $source) {
		$source = strtolower($source);
		if ($source == 'planespotters') $images_array = Image::fromPlanespotters($aircraft_registration,$aircraft_name);
		if ($source == 'flickr') $images_array = Image::fromFlickr($aircraft_registration,$aircraft_name);
		if ($source == 'bing') $images_array = Image::fromBing($aircraft_registration,$aircraft_name);
		if ($source == 'deviantart') $images_array = Image::fromDeviantart($aircraft_registration,$aircraft_name);
		if ($source == 'wikimedia') $images_array = Image::fromWikimedia($aircraft_registration,$aircraft_name);
		if (is_array($images_array) && $images_array['original'] != '') return $images_array;
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
    public static function fromPlanespotters($aircraft_registration, $aircraft_name='') {
	// If aircraft registration is only number, also check with aircraft model
	if (preg_match('/^[[:digit]]+$/',$aircraft_registration) && $aircraft_name != '') {
	    $url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$aircraft_registration.'&actype=s_'.urlencode($aircraft_name).'&output=rss';
	} else {
	    //$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$airline_aircraft_type.'&output=rss';
	    $url= 'http://www.planespotters.net/Aviation_Photos/search.php?reg='.$aircraft_registration.'&output=rss';
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);
	if ($xml = simplexml_load_string($data)) {
	    if (isset($xml->channel->item)) {
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
    public static function fromDeviantart($aircraft_registration, $aircraft_name='') {
	// If aircraft registration is only number, also check with aircraft model
	if (preg_match('/^[[:digit]]+$/',$aircraft_registration) && $aircraft_name != '') {
	    $url= 'http://backend.deviantart.com/rss.xml?type=deviation&q='.$aircraft_registration.'%20'.urlencode($aircraft_name);
	} else {
	    $url= 'http://backend.deviantart.com/rss.xml?type=deviation&q=aircraft%20'.$aircraft_registration;
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);
	if ($xml = simplexml_load_string($data)) {
	    if (isset($xml->channel->item->link)) {
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
    * Gets the aircraft image from Flickr
    *
    * @param String $aircraft_registration the registration of the aircraft
    * @param String $aircraft_name type of the aircraft
    * @return Array the aircraft thumbnail, orignal url and copyright
    *
    */
    public static function fromFlickr($aircraft_registration,$aircraft_name='') {

	    if ($aircraft_name != '') $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$aircraft_registration.','.urlencode($aircraft_name);
	    else $url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&license=1,2,3,4,5,6,7&per_page=1&tags='.$aircraft_registration.',aircraft';
	    
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);
	
	if ($xml = simplexml_load_string($data)) {
	    if (isset($xml->channel->item)) {
		$original_url = trim((string)$xml->channel->item->enclosure->attributes()->url);
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

    /**
    * Gets the aircraft image from Bing
    *
    * @param String $aircraft_registration the registration of the aircraft
    * @param String $aircraft_name type of the aircraft
    * @return Array the aircraft thumbnail, orignal url and copyright
    *
    */
    public static function fromBing($aircraft_registration,$aircraft_name='') {
	global $globalImageBingKey;
	if (!isset($globalImageBingKey) || $globalImageBingKey == '') return false;
	if ($aircraft_name != '') $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27'.$aircraft_registration.'%20'.urlencode($aircraft_name).'%20-site:planespotters.com%20-site:flickr.com%27';
	else $url = 'https://api.datamarket.azure.com/Bing/Search/v1/Image?$format=json&$top=1&Query=%27%2B'.$aircraft_registration.'%20%2Baircraft%20-site:planespotters.com%20-site:flickr.com%27';

	$headers = array("Authorization: Basic " . base64_encode("ignored:".$globalImageBingKey));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);

	$result = json_decode($data);
	if (isset($result->d->results[0]->MediaUrl)) {
	    $image_url['original'] = $result->d->results[0]->MediaUrl;
	    $image_url['source_website'] = $result->d->results[0]->SourceUrl;
	    $image_url['thumbnail'] = $result->d->results[0]->Thumbnail->MediaUrl;
	    $url = parse_url($image_url['source_website']);
	    $image_url['copyright'] = $url['host'];
	    $image_url['source'] = 'bing';
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
    public static function fromWikimedia($aircraft_registration,$aircraft_name='') {
	if ($aircraft_name != '') $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$aircraft_registration.'"%20'.urlencode($aircraft_name);
	else $url = 'https://commons.wikimedia.org/w/api.php?action=query&list=search&format=json&srlimit=1&srnamespace=6&continue&srsearch="'.$aircraft_registration.'"%20aircraft';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	curl_setopt($ch, CURLOPT_URL, $url);
	$data = curl_exec($ch);
	curl_close($ch);
	$result = json_decode($data);
	if (isset($result->query->search[0]->title)) {
	    $fileo = $result->query->search[0]->title;
	    if (substr($fileo,-3) == 'pdf') return false;
	    $file = urlencode($fileo);
	    $url2 = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&continue&iilimit=500&prop=imageinfo&iiprop=user|url|size|mime|sha1|timestamp&iiurlwidth=200%27&titles='.$file;

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
	    curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
	    curl_setopt($ch, CURLOPT_URL, $url2);
	    $data2 = curl_exec($ch);
	    curl_close($ch);

	    $result2 = json_decode($data2);
	    if (isset($result2->query->pages)) {
		foreach ($result2->query->pages as $page) {
		    if (isset($page->imageinfo[0]->user)) {
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

	        $ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
	        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
		curl_setopt($ch, CURLOPT_URL, $url2);
		$data2 = curl_exec($ch);
		curl_close($ch);

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


}

//print_r(Image::findAircraftImage('472/CC'));
//print_r(Image::findAircraftImage('F-GRHG'));
//print_r(Image::fromBing('CN-RGF'));
//print_r(Image::fromBing('472/CC'));

?>