<?php
require_once(dirname(__FILE__).'/class.Common.php');
require_once(dirname(__FILE__).'/class.Connection.php');
/*
Copyright 2017 Jozef Môstka <jozef@mostka.com>

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/
/*
Modified in 2017 by Ycarus <ycarus@zugaina.org>
Original version come from https://github.com/tito10047/hgt-reader
*/

class Elevation {
	private $htgFilesDestination;
	private $resolution  = -1;
	private $measPerDeg;
	private $openedFiles = [];

	public function __construct($htgFilesDestination = '', $resolution = 3) {
		if ($htgFilesDestination == '') $htgFilesDestination = dirname(__FILE__).'/../data/';
		$this->htgFilesDestination = $htgFilesDestination;
		$this->resolution          = $resolution;
		switch ($resolution) {
			case 1:
				$this->measPerDeg = 3601;
				break;
			case 3:
				$this->measPerDeg = 1201;
				break;
			default:
				throw new \Exception("bad resolution can be only one of 1,3");
		}
		register_shutdown_function(function () {
			$this->closeAllFiles();
		});
	}

	public function closeAllFiles() {
		foreach ($this->openedFiles as $file) {
			fclose($file);
		}
		$this->openedFiles = [];
	}

	private function getElevationAtPosition($fileName, $row, $column) {
		if (!array_key_exists($fileName, $this->openedFiles)) {
			if (!file_exists($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName)) {
				throw new \Exception("File '{$fileName}' not exists.");
			}
			$file = fopen($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName, "r");
			if ($file === false) {
				throw new \Exception("Cant open file '{$fileName}' for reading.");
			}
			$this->openedFiles[$fileName] = $file;
		} else {
			$file = $this->openedFiles[$fileName];
		}

		if ($row > $this->measPerDeg || $column > $this->measPerDeg) {
			//TODO:open next file
			throw new \Exception("Not implemented yet");
		}
		$aRow     = $this->measPerDeg - $row;
		$position = ($this->measPerDeg * ($aRow - 1)) + $column;
		$position *= 2;
		fseek($file, $position);
		$short  = fread($file, 2);
		$_      = unpack("n*", $short);
		$shorts = reset($_);
		//echo $shorts."\n";
		return $shorts;
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param null  $fName
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function getElevation($lat, $lon, &$fName = null) {
		if ($this->resolution == -1) {
			throw new \Exception("use HgtReader::init(ASSETS_HGT . DIRECTORY_SEPARATOR, 3);");
		}
		if ($lat < 0) {
			$latd = 'S'.$this->getDeg($lat, 2);
		} else {
			$latd = 'N'.$this->getDeg($lat, 2);
		}
		if ($lon < 0) {
			$lond = 'W'.$this->getDeg($lon, 3);
		} else {
			$lond = 'W'.$this->getDeg($lon, 3);
		}
		$fName  = $latd.$lond.".hgt";

		
		$latSec = $this->getSec($lat);
		$lonSec = $this->getSec($lon);

		$Xn = round($latSec / $this->resolution, 3);
		$Yn = round($lonSec / $this->resolution, 3);

		$a1 = round($Xn);
		$a2 = round($Yn);

		if ($Xn <= $a1 && $Yn <= $a2) {
			$b1 = $a1 - 1;
			$b2 = $a2;
			$c1 = $a1;
			$c2 = $a2 - 1;
		} else if ($Xn >= $a1 && $Yn >= $a2) {
			$b1 = $a1 + 1;
			$b2 = $a2;
			$c1 = $a1;
			$c2 = $a2 + 1;
		} else if ($Xn > $a1 && $Yn < $a2) {
			$b1 = $a1;
			$b2 = $a2 - 1;
			$c1 = $a1 + 1;
			$c2 = $a2;
		} else if ($Xn < $a1 && $Yn > $a2) {
			$b1 = $a1 - 1;
			$b2 = $a2;
			$c1 = $a1;
			$c2 = $a2 + 1;
		} else {
			throw new \Exception("{$Xn}:{$Yn}");
		}
		$a3 = $this->getElevationAtPosition($fName, $a1, $a2);
		$b3 = $this->getElevationAtPosition($fName, $b1, $b2);
		$c3 = $this->getElevationAtPosition($fName, $c1, $c2);

		$n1 = ($c2 - $a2) * ($b3 - $a3) - ($c3 - $a3) * ($b2 - $a2);
		$n2 = ($c3 - $a3) * ($b1 - $a1) - ($c1 - $a1) * ($b3 - $a3);
		$n3 = ($c1 - $a1) * ($b2 - $a2) - ($c2 - $a2) * ($b1 - $a1);

		$d  = -$n1 * $a1 - $n2 * $a2 - $n3 * $a3;
		$zN = (-$n1 * $Xn - $n2 * $Yn - $d) / $n3;
		if ($zN > 10000) return 0;
		else return $zN;
	}

	private function getDeg($deg, $numPrefix) {
		$deg = abs($deg);
		$d   = floor($deg);     // round degrees
		if ($numPrefix >= 3) {
			if ($d < 100) {
				$d = '0' . $d;
			}
		} // pad with leading zeros
		if ($d < 10) {
			$d = '0' . $d;
		}
		return $d;
	}

	private function getSec($deg) {
		$deg = abs($deg);
		$sec = round($deg * 3600, 4);
		$m   = fmod(floor($sec / 60), 60);
		$s   = round(fmod($sec, 60), 4);
		return ($m * 60) + $s;
	}

	public function download($lat,$lon, $debug = false) {
		if ($lat < 0) {
			$latd = 'S'.$this->getDeg($lat, 2);
		} else {
			$latd = 'N'.$this->getDeg($lat, 2);
		}
		if ($lon < 0) {
			$lond = 'W'.$this->getDeg($lon, 3);
		} else {
			$lond = 'W'.$this->getDeg($lon, 3);
		}
		$fileName  = $latd.$lond.".hgt";
		if (!file_exists($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName)) {
			$Common = new Common();
			if ($debug) echo 'Downloading '.$fileName.'.gz ...';
			$Common->download('https://s3.amazonaws.com/elevation-tiles-prod/skadi/'.$latd.'/'.$fileName.'.gz',$this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName . '.gz');
			if (!file_exists($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName . '.gz')) {
				if ($debug) echo "File '{$fileName}.gz' not exists.";
				return false;
			}
			if ($debug) echo 'Done'."\n";
			if ($debug) echo 'Decompress '.$fileName.' ....';
			$Common->gunzip($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName . '.gz',$this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName);
			if ($debug) echo 'Done'."\n";
			unlink($this->htgFilesDestination . DIRECTORY_SEPARATOR . $fileName . '.gz');
		}
		return true;
	}
	
	public function downloadNeeded() {
		$Connection = new Connection();
		$db = $Connection->db;
		$query = 'SELECT latitude, longitude FROM spotter_output WHERE latitude <> 0 AND longitude <> 0 ORDER BY date DESC LIMIT 10';
		$query_values = array();
		try {
			$sth = $db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
			$this->download($data['latitude'],$data['longitude'],true);
		}
		$query = 'SELECT latitude, longitude FROM tracker_output WHERE latitude <> 0 AND longitude <> 0 ORDER BY date DESC LIMIT 10';
		$query_values = array();
		try {
			$sth = $db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
			$this->download($data['latitude'],$data['longitude'],true);
		}
	}
}
/*
$lat = 38.40207;
$lon = -11.273;
$Elevation = new Elevation();
$Elevation->download($lat,$lon);
echo($Elevation->getElevation($lat,$lon));
*/
/*
$Elevation = new Elevation();
echo $Elevation->downloadNeeded();
*/