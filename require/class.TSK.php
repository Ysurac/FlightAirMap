<?php
/**
 * This class is part of FlightAirmap. It's used to parse TSK XCSoar file.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Common.php');
class TSK {
	/*
	 * Parse .tsk XML file
	 * @param String $url URL of the tsk file
	 * @return Array Parsed tsk
	*/
	public function parse_xml($url) {
		$Common = new Common();
		$filedata = $Common->getData($url,'get','','','','','','',true);
		if ($filedata != '' && $filedata !== false) {
			$xml = simplexml_load_string($filedata);
			if ($xml !== false) {
				return json_decode(json_encode($xml), 1);
			}
		}
		return array();
	}
}
?>