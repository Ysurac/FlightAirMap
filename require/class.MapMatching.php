<?php
/**
 * This class is part of FlightAirmap. It's used to do Map Matching.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Common.php');

class MapMatching {
	/*
	 * Return results from map matching engine set by $globalMapMatchingSource
	 * @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	 * @return Array Modified data with new map matching coordinates
	*/
	public function match($spotter_history_array) {
		global $globalMapMatchingSource;
		if ($globalMapMatchingSource == 'mapbox') {
			return $this->mapbox($spotter_history_array);
		} elseif ($globalMapMatchingSource == 'graphhopper') {
			return $this->GraphHopper($spotter_history_array);
		} elseif ($globalMapMatchingSource == 'osmr') {
			return $this->osmr($spotter_history_array);
		} elseif ($globalMapMatchingSource == 'fam') {
			return $this->FAMMapMatching($spotter_history_array);
		/*
		} elseif ($globalMapMatchingSource == 'trackmatching') {
			return $this->TrackMatching($spotter_history_array);
		*/
		} else {
			return $spotter_history_array;
		}
	}

	/*
	* Create simple GPX file
	* @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	* @return String The GPX file
	*/
	public function create_gpx($spotter_history_array) {
		date_default_timezone_set('UTC');
		$gpx = '<?xml version="1.0" encoding="UTF-8"?>';
		$gpx .= '<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpsies="http://www.gpsies.com/GPX/1/0" creator="GPSies http://www.gpsies.com - Sendl.-O&amp;apos;sch-heim" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.gpsies.com/GPX/1/0 http://www.gpsies.com/gpsies.xsd">';
		$gpx .= '<trk>';
		$gpx .= '<trkseg>';
		foreach($spotter_history_array as $spotter_data) {
			$gpx .= '<trkpt lat="'.sprintf("%.8f",$spotter_data['latitude']).'" lon="'.sprintf("%.8f",$spotter_data['longitude']).'">';
			if (isset($spotter_data['altitude'])) $gpx .= '<ele>'.sprintf("%.6f",$spotter_data['altitude']).'</ele>';
			$gpx .= '<time>'.date("Y-m-d\TH:i:s\Z",strtotime($spotter_data['date'])).'</time>';
			$gpx .= '</trkpt>';
		}
		$gpx .= '</trkseg>';
		$gpx .= '</trk>';
		$gpx .= '</gpx>';
		return $gpx;
	}
/*
	public function Mapzen($spotter_history_array) {
		global $globalMapMatchingMaxPts, $globalMapzenKey;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 500;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) $spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
		$data = $this->create_gpx($spotter_history_array);
		$url = 'https://valhalla.mapzen.com/trace_route?api_key='.$globalMapzenKey;
		$Common = new Common();
		$matching = $Common->getData($url,'post',$data,array('Content-Type: application/gpx+xml'));
		$matching = json_decode($matching,true);
		//print_r($matching);
		
		if (isset($matching['paths'][0]['points']['coordinates'])) {
			$spotter_history_array = array();
			foreach ($matching['paths'][0]['points']['coordinates'] as $match) {
				$coord = $match;
				$spotter_history_array[] = array('longitude' => $coord[0],'latitude' => $coord[1]);
			}
		}
		return $spotter_history_array;
		
	}
  */

	public function TrackMatching($spotter_history_array) {
		global $globalMapMatchingMaxPts, $globalTrackMatchingAppKey, $globalTrackMatchingAppId;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 100;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) $spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
		$data = $this->create_gpx($spotter_history_array);
		$url = 'https://test.roadmatching.com/rest/mapmatch/?app_id='.$globalTrackMatchingAppId.'&app_key='.$globalTrackMatchingAppKey.'&output.waypoints=true';
		$Common = new Common();
		$matching = $Common->getData($url,'post',$data,array('Content-Type: application/gpx+xml','Accept: application/json'));
		$matching = json_decode($matching,true);
		if (isset($matching['diary']['entries'][0]['route']['links'])) {
			$spotter_history_array = array();
			foreach ($matching['diary']['entries'][0]['route']['links'] as $match) {
				if (isset($match['wpts'])) {
					foreach ($match['wpts'] as $coord) {
						$spotter_history_array[] = array('longitude' => $coord['x'],'latitude' => $coord['y']);
					}
				}
			}
		}
		$spotter_history_array[0]['mapmatching_engine'] = 'trackmatching';
		return $spotter_history_array;
	}

	/*
	* Use https://www.graphhopper.com/ as map matching engine
	* @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	* @return Array Modified data with new map matching coordinates
	*/
	public function GraphHopper($spotter_history_array) {
		global $globalMapMatchingMaxPts, $globalGraphHopperKey;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 100;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		$spotter_history_initial_array = array();
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) {
			$spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
			$spotter_history_initial_array = array_slice($spotter_history_array,0,count($spotter_history_array)-$globalMapMatchingMaxPts);
		}
		$data = $this->create_gpx($spotter_history_array);
		$url = 'https://graphhopper.com/api/1/match?vehicle=car&points_encoded=0&instructions=false&key='.$globalGraphHopperKey;
		$Common = new Common();
		$matching = $Common->getData($url,'post',$data,array('Content-Type: application/gpx+xml'));
		$matching = json_decode($matching,true);
		if (isset($matching['paths'][0]['points']['coordinates'])) {
			$spotter_history_array = array();
			foreach ($matching['paths'][0]['points']['coordinates'] as $match) {
				$coord = $match;
				$spotter_history_array[] = array('longitude' => $coord[0],'latitude' => $coord[1]);
			}
		}
		$spotter_history_array = array_merge($spotter_history_initial_array,$spotter_history_array);
		$spotter_history_array[0]['mapmatching_engine'] = 'graphhopper';
		return $spotter_history_array;
	}

	/*
	* Use https://mapmatching.flightairmap.com/ as map matching engine
	* @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	* @return Array Modified data with new map matching coordinates
	*/
	public function FAMMapMatching($spotter_history_array) {
		global $globalMapMatchingMaxPts;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 100;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		$spotter_history_initial_array = array();
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) {
			$spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
			$spotter_history_initial_array = array_slice($spotter_history_array,0,count($spotter_history_array)-$globalMapMatchingMaxPts);
		}
		$data = $this->create_gpx($spotter_history_array);
		$url = 'https://mapmatching.flightairmap.com/api/1/match?vehicle=car&points_encoded=0&instructions=false';
		//$url = 'https://mapmatching.flightairmap.com/api/1/match?vehicle=car&points_encoded=0';
		$Common = new Common();
		$matching = $Common->getData($url,'post',$data,array('Content-Type: application/gpx+xml'));
		$matching = json_decode($matching,true);
		if (isset($matching['paths'][0]['points']['coordinates'])) {
			$spotter_history_array = array();
			foreach ($matching['paths'][0]['points']['coordinates'] as $match) {
				$coord = $match;
				$spotter_history_array[] = array('longitude' => $coord[0],'latitude' => $coord[1]);
			}
		}
		$spotter_history_array = array_merge($spotter_history_initial_array,$spotter_history_array);
		$spotter_history_array[0]['mapmatching_engine'] = 'fam';
		return $spotter_history_array;
	}

	/*
	* Use https://www.project-osrm.org/ as map matching engine
	* @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	* @return Array Modified data with new map matching coordinates
	*/
	public function osmr($spotter_history_array) {
		global $globalMapMatchingMaxPts;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 50;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		$spotter_history_initial_array = array();
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) {
			$spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
			$spotter_history_initial_array = array_slice($spotter_history_array,0,count($spotter_history_array)-$globalMapMatchingMaxPts);
		}
		$coord = '';
		$ts = '';
		$rd = '';
		foreach ($spotter_history_array as $spotter_data) {
			if ($coord != '') $coord .= ';';
			$coord .= $spotter_data['longitude'].','.$spotter_data['latitude'];
			if ($ts != '') $ts .= ';';
			$ts .= strtotime($spotter_data['date']);
			if ($rd != '') $rd .= ';';
			$rd .= '20';
		}
		$url = 'https://router.project-osrm.org/match/v1/driving/'.$coord.'?timestamps='.$ts.'&overview=full&geometries=geojson&tidy=true&gaps=ignore';
		$Common = new Common();
		$matching = $Common->getData($url);
		$matching  = json_decode($matching,true);
		if (isset($matching['matchings'][0]['geometry']['coordinates'])) {
			$spotter_history_array = array();
			foreach ($matching['matchings'][0]['geometry']['coordinates'] as $match) {
				$coord = $match;
				$spotter_history_array[] = array('longitude' => $coord[0],'latitude' => $coord[1]);
			}
		}
		$spotter_history_array = array_merge($spotter_history_initial_array,$spotter_history_array);
		$spotter_history_array[0]['mapmatching_engine'] = 'osmr';
		return $spotter_history_array;
	}

	/*
	* Use https://www.mapbox.com/ as map matching engine
	* @param Array $spotter_history_array Array with latitude, longitude, altitude and date
	* @return Array Modified data with new map matching coordinates
	*/
	public function mapbox($spotter_history_array) {
		global $globalMapMatchingMaxPts, $globalMapboxToken;
		if (!isset($globalMapMatchingMaxPts)) $globalMapMatchingMaxPts = 60;
		if (count($spotter_history_array) < 2) return $spotter_history_array;
		$spotter_history_initial_array = array();
		if (count($spotter_history_array) > $globalMapMatchingMaxPts) {
			$spotter_history_array = array_slice($spotter_history_array,-$globalMapMatchingMaxPts);
			$spotter_history_initial_array = array_slice($spotter_history_array,0,count($spotter_history_array)-$globalMapMatchingMaxPts);
		}
		$coord = '';
		$ts = '';
		$rd = '';
		foreach ($spotter_history_array as $spotter_data) {
			if ($coord != '') $coord .= ';';
			$coord .= $spotter_data['longitude'].','.$spotter_data['latitude'];
			if ($ts != '') $ts .= ';';
			$ts .= strtotime($spotter_data['date']);
			if ($rd != '') $rd .= ';';
			$rd .= '20';
		}
		//$url = 'https://api.mapbox.com/matching/v5/mapbox/driving/'.$coord.'?access_token='.$globalMapboxToken.'&timestamps='.$ts.'&overview=full&tidy=true&geometries=geojson&radiuses='.$rd;
		$url = 'https://api.mapbox.com/matching/v5/mapbox/driving/'.$coord.'?access_token='.$globalMapboxToken.'&timestamps='.$ts.'&overview=full&tidy=true&geometries=geojson';
		$Common = new Common();
		$matching = $Common->getData($url);
		$matching  = json_decode($matching,true);
		if (isset($matching['matchings'][0]['geometry']['coordinates'])) {
			$spotter_history_array = array();
			foreach ($matching['matchings'][0]['geometry']['coordinates'] as $match) {
				$coord = $match;
				$spotter_history_array[] = array('longitude' => $coord[0],'latitude' => $coord[1]);
			}
		}
		$spotter_history_array = array_merge($spotter_history_initial_array,$spotter_history_array);
		$spotter_history_array[0]['mapmatching_engine'] = 'mapbox';
		return $spotter_history_array;
	}

}
?>