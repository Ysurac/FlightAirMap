<?php
/**
 * This class is part of FlightAirmap. It's used to get weather data and create clouds
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Common.php');
class Weather {
	public function buildcloudlayer($metar) {
		//print_r($metar);
		$result = array();
		foreach($metar['cloud'] as $key => $data) {
			$alt_m = $metar['cloud'][$key]['level'];
			$alt_ft = $alt_m*3.28084;
			$pressure = $metar['QNH'];
			$cumulus_base = 122.0 * ($metar['temperature'] - $metar['dew']);
			$stratus_base = 100.0 * (100.0 * $metar['rh'])*0.3048;
			$coverage_norm = 0.0;
			if ($metar['cloud'][$key]['type'] == 'Few') {
				$coverage_norm = 2.0/8.0;
			} elseif ($metar['cloud'][$key]['type'] == 'Scattered') {
				$coverage_norm = 4.0/8.0;
			} elseif ($metar['cloud'][$key]['type'] == 'Broken') {
				$coverage_norm = 6.0/8.0;
			} elseif ($metar['cloud'][$key]['type'] == 'Overcast/Full cloud coverage') {
				$coverage_norm = 8.0/8.0;
			}
			$layer_type = 'nn';
			if ($metar['cloud'][$key]['significant'] == 'cirrus') {
				$layer_type = 'ci';
			} elseif ($alt_ft > 16500) {
				$layer_type = 'ci';
			} elseif ($alt_ft > 6500) {
				$layer_type = 'ac';
				if ($pressure < 1005.0 && $coverage_norm >= 0.5) {
					$layer_type = 'ns';
				}
			} else {
				if ($cumulus_base * 0.80 < $alt_m && $cumulus_base * 1.20 > $alt_m) {
					$layer_type = 'cu';
				} elseif ($stratus_base * 0.80 < $alt_m && $stratus_base * 1.40 > $alt_m) {
					$layer_type = 'st';
				} else {
					if ($alt_ft < 2000) {
						$layer_type = 'st';
					} elseif ($alt_ft < 4500) {
						$layer_type = 'cu';
					} else {
						$layer_type = 'sc';
					}
				}
			}
			//echo 'coverage norm : '.$coverage_norm.' - layer_type: '.$layer_type."\n";
			$result[] = array('cov' => $coverage_norm, 'type' => $layer_type,'alt' => $alt_m,'rh' => $metar['rh']);
		}
		if (count($result) < 2 && $metar['rh'] > 60) {
			$result[] = array('cov' => 0.75, 'type' => 'ci','alt' => 4000,'rh' => $metar['rh']);
		}
		return $result;
	}
	
	public function openweathermap($latitude,$longitude) {
		global $globalOpenWeatherMapKey;
		if ($globalOpenWeatherMapKey == '') return array();
		$Common = new Common();
		$url = 'http://api.openweathermap.org/data/2.5/weather?lat='.$latitude.'&lon='.$longitude.'&units=metric&appid='.$globalOpenWeatherMapKey;
		//echo $url."\n";
		$weatherdata = json_decode($Common->getData($url),true);
		if (!isset($weatherdata['main']['temp']) || !isset($weatherdata['weather'][0]['id'])) return array('clouds' => array(),'rain' => array());
		$dew = $weatherdata['main']['temp'] - ((100-$weatherdata['main']['humidity'])/5);
		$cumulus_base = 122.0 * ($weatherdata['main']['temp'] - $dew);
		$stratus_base = 100.0 * (100.0 * $weatherdata['main']['humidity'])*0.3048;
		$coverage_norm = 0.0;
		if ($weatherdata['weather'][0]['id'] == 801) {
			// few clouds
			$coverage_norm = 2.0/8.0;
		} elseif ($weatherdata['weather'][0]['id'] == 802) {
			//scattered cloud
			$coverage_norm = 4.0/8.0;
		} elseif ($weatherdata['weather'][0]['id'] == 803) {
			// broken clouds
			$coverage_norm = 6.0/8.0;
		} elseif ($weatherdata['weather'][0]['id'] == 804) {
			// overcast clouds
			$coverage_norm = 8.0/8.0;
		}
		$alt_m = 1000;
		if ($cumulus_base * 0.80 < $alt_m && $cumulus_base * 1.20 > $alt_m) {
			$layer_type = 'cu';
		} elseif ($stratus_base * 0.80 < $alt_m && $stratus_base * 1.40 > $alt_m) {
			$layer_type = 'st';
		} else {
			$layer_type = 'st';
		}
		$result = array();
		$result[] = array('cov' => $coverage_norm, 'type' => $layer_type,'alt' => $alt_m,'rh' => $weatherdata['main']['humidity']);
		if ($weatherdata['main']['humidity'] > 60) {
			$result[] = array('cov' => 0.75, 'type' => 'ci','alt' => 4000,'rh' => $weatherdata['main']['humidity']);
		}
		return array('clouds' => $result,'rain' => array('tmp' => $weatherdata['main']['temp'],'rh' => $weatherdata['main']['humidity']));
	}
	
	public function nomad_wind($hour = '') {
		global $globalWindsPath;
		if ($hour == '') $hour = date('G');
		if (isset($globalWindsPath) && $globalWindsPath != '') {
			$grib2json = $globalWindsPath['grib2json'];
			$windpathsrc = $globalWindsPath['source'];
			$windpathdest = $globalWindsPath['destination'];
		} else {
			$grib2json = dirname(__FILE__).'/libs/grib2json/bin/grib2json';
			$windpathsrc = dirname(__FILE__).'/../data/winds.gb2';
			$windpathdest = dirname(__FILE__).'/../data/winds.json';
		}
		
		// http://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_0p50.pl?file=gfs.t05z.pgrb2full.0p50.f000&lev_10_m_above_ground=on&lev_surface=on&var_TMP=on&var_UGRD=on&var_VGRD=on&leftlon=0&rightlon=360&toplat=90&bottomlat=-90&dir=/gfs.2017111717
		$resolution = '0.5';
		$baseurl = 'http://nomads.ncep.noaa.gov/cgi-bin/filter_gfs_'.($resolution === '1' ? '1p00':'0p50').'.pl';
		$get = array('file' => 'gfs.t'.sprintf('%02d',(6*floor($hour/6))).($resolution === '1' ? 'z.pgrb2.1p00.f000' : 'z.pgrb2full.0p50.f000'),
			'lev_10_m_above_ground' => 'on',
			'lev_surface' => 'on',
			'var_TMP' => 'on',
			'var_UGRD' => 'on',
			'var_VGRD' => 'on',
			'leftlon' => 0,
			'rightlon' => 360,
			'toplat' => 90,
			'bottomlat' => -90,
			'dir' => '/gfs.'.date('Ymd').sprintf('%02d',(6*floor($hour/6)))
		);
		$url = $baseurl.'?'.http_build_query($get);
		//echo $url;
		$Common = new Common();
		$Common->download($url,$windpathsrc);
		// Check if the downloaded file is in GRIB format
		$file = fopen($windpathsrc,"r");
		$firc = fgetc($file);
		fclose($file);
		if ($firc == 'G') {
			system($grib2json.' --data --output '.$windpathdest.' --names --compact '.$windpathsrc);
		} else {
			// if not try previous run
			if ($hour == date('G')) $this->nomad_wind(date('G')-6);
		}
	}

	public function oscar_wave() {
		global $globalWavesPath, $globalPODACCuser,$globalPODACCpass;
		if ($globalPODACCuser == '' && $globalPODACCpass == '') return;
		if (isset($globalWavesPath) && $globalWavesPath != '') {
			$grib2json = $globalWavesPath['grib2json'];
			$wavepathsrc = $globalWavesPath['source'];
			$wavepathdest = $globalWavesPath['destination'];
		} else {
			$grib2json = dirname(__FILE__).'/libs/grib2json/bin/grib2json';
			$wavepathsrc = dirname(__FILE__).'/../data/waves.nc';
			$wavepathdest = dirname(__FILE__).'/../data/waves.json';
		}
		
		$url = 'https://podaac.jpl.nasa.gov/ws/search/granule/?datasetId=PODAAC-OSCAR-03D01&itemsPerPage=1&sortBy=timeDesc&format=atom&pretty=false';
		$Common = new Common();
		$auth = base64_encode("$globalPODACCuser:$globalPODACCpass");
		$oscarlst = $Common->getData($url);
		$oscarlst_xml = json_decode(json_encode(simplexml_load_string($oscarlst)),true);
		foreach ($oscarlst_xml['entry']['link'] as $oscarlnk) {
			if ($oscarlnk['@attributes']['type'] == 'application/x-netcdf') {
				$Common->download($oscarlnk['@attributes']['href'],$wavepathsrc.'.gz','',array("Authorization: BASIC $auth"));
				break;
			}
		}
		$Common->gunzip($wavepathsrc.'.gz');
		system($grib2json.' --data --output '.$wavepathdest.' --names --compact '.$wavepathsrc);
	}
}
/*
require_once('class.METAR.php');
$METAR = new METAR();
*/
/*
$themetar = $METAR->getMETAR('LFLL');
print_r($themetar);
$result = $METAR->parse($themetar[0]['metar']);
*/
/*
$result = $METAR->parse('LFLL 081330Z 01006KT 340V050 9999 FEW020 BKN080 07/01 Q1018 NOSIG');
print_r($result);
$Weather = new Weather();
//print_r($Weather->buildcloudlayer($result));
//print_r($Weather->buildcloud('46.3870','5.2941','2000','0.25'));
print_r($Weather->generateRandomPoint('46.3870','5.2941','2000'));
*/
/*
$Weather = new Weather();
$Weather->nomad_wind();
*/
/*
$Weather = new Weather();
$Weather->oscar_wave();
*/
/*
$Weather = new Weather();
print_r($Weather->openweathermap(0.0,-10.0));
*/
?>