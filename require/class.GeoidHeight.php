<?php
/*
 * Calculate the height of the geoid above the WGS84
 * ellipsoid at any given latitude and longitude
 *
 * Copyright (c) Charles Karney (2009-2015) <charles@karney.com> and licensed
 * under the MIT/X11 License.  For more information, see
 * https://geographiclib.sourceforge.io/
*/
/*
* Translated to PHP of GeographicLib/src/Geoid.cpp
* by Ycarus <ycarus@zugaina.org> in 2017
*/
class GeoidHeight  {
	private $c0 = 240;
	private $c3 = [[9, -18, -88, 0, 96, 90, 0, 0, -60, -20], [-9, 18, 8, 0, -96, 30, 0, 0, 60, -20], [9, -88, -18, 90, 96, 0, -20, -60, 0, 0], [186, -42, -42, -150, -96, -150, 60, 60, 60, 60], [54, 162, -78, 30, -24, -90, -60, 60, -60, 60], [-9, -32, 18, 30, 24, 0, 20, -60, 0, 0], [-9, 8, 18, 30, -96, 0, -20, 60, 0, 0], [54, -78, 162, -90, -24, 30, 60, -60, 60, -60], [-54, 78, 78, 90, 144, 90, -60, -60, -60, -60], [9, -8, -18, -30, -24, 0, 20, 60, 0, 0], [-9, 18, -32, 0, 24, 30, 0, 0, -60, 20], [9, -18, -8, 0, -24, -30, 0, 0, 60, 20]];
	private $c0n = 372;
	private $c3n = [[0, 0, -131, 0, 138, 144, 0, 0, -102, -31], [0, 0, 7, 0, -138, 42, 0, 0, 102, -31], [62, 0, -31, 0, 0, -62, 0, 0, 0, 31], [124, 0, -62, 0, 0, -124, 0, 0, 0, 62], [124, 0, -62, 0, 0, -124, 0, 0, 0, 62], [62, 0, -31, 0, 0, -62, 0, 0, 0, 31], [0, 0, 45, 0, -183, -9, 0, 93, 18, 0], [0, 0, 216, 0, 33, 87, 0, -93, 12, -93], [0, 0, 156, 0, 153, 99, 0, -93, -12, -93], [0, 0, -45, 0, -3, 9, 0, 93, -18, 0], [0, 0, -55, 0, 48, 42, 0, 0, -84, 31], [0, 0, -7, 0, -48, -42, 0, 0, 84, 31]];
	private $c0s = 372;
	private $c3s = [[18, -36, -122, 0, 120, 135, 0, 0, -84, -31], [-18, 36, -2, 0, -120, 51, 0, 0, 84, -31], [36, -165, -27, 93, 147, -9, 0, -93, 18, 0], [210, 45, -111, -93, -57, -192, 0, 93, 12, 93], [162, 141, -75, -93, -129, -180, 0, 93, -12, 93], [-36, -21, 27, 93, 39, 9, 0, -93, -18, 0], [0, 0, 62, 0, 0, 31, 0, 0, 0, -31], [0, 0, 124, 0, 0, 62, 0, 0, 0, -62], [0, 0, 124, 0, 0, 62, 0, 0, 0, -62], [0, 0, 62, 0, 0, 31, 0, 0, 0, -31], [-18, 36, -64, 0, 66, 51, 0, 0, -102, 31], [18, -36, 2, 0, -66, -51, 0, 0, 102, 31]];
	private $offset = null;
	private $scale = null;
	private $width = null;
	private $height = null;
	private $headerlen = null;
	private $raw = null;
	private $rlonres = null;
	private $rlatres = null;
	private $ix = null;
	private $iy = null;
	private $t = null;
	private $v00 = null;
	private $v01 = null;
	private $v10 = null;
	private $v11 = null;

	public function __construct($name='') {
		global $globalGeoidSource;
		//if ($name == '') $name = dirname(__FILE__).'/../install/tmp/egm2008-1.pgm';
		if ($name == '') {
			if (isset($globalGeoidSource) && $globalGeoidSource != '') $name = dirname(__FILE__).'/../data/'.$globalGeoidSource.'.pgm';
			else $name = dirname(__FILE__).'/../data/egm96-15.pgm';
		}

		if (file_exists($name) === FALSE) {
			throw new Exception($name." doesn't exist");
		}
		$f = @fopen($name,"r");
		if ($f === FALSE) {
			throw new Exception("Can't open ".$name);
		}
		$line = fgets($f,4096);
		if (trim($line) != 'P5') {
			throw new Exception('No PGM header');
		}
		$headerlen = strlen($line);
		while (true) {
			$line = fgets($f,4096);
			if ((strlen($line) == 0)) {
				throw new Exception('EOF before end of file header');
			}
			$headerlen += strlen($line);
			if (strpos($line,'# Offset ') !== FALSE) {
				$this->offset = substr($line, 9);
			} else if (strpos($line,'# Scale ') !== FALSE) {
				$this->scale = substr($line, 8);
			} else if ((strpos($line,'#') === FALSE)) {
				list($this->width, $this->height) = preg_split('/\s+/',$line);
				break;
			}
		}
		$line = fgets($f,4096);
		$headerlen += strlen($line);
		$levels = (int)$line;
		$this->width = (int)$this->width;
		$this->height = (int)$this->height;
		if (($levels != 65535)) {
			throw new Exception('PGM file must have 65535 gray levels ('.$levels.')');
		}
		if (($this->offset === null)) {
			throw new Exception('PGM file does not contain offset');
		}
		if (($this->scale === null)) {
			throw new Exception('PGM file does not contain scale');
		}
		if (($this->width < 2) || ($this->height < 2)) {
			throw new Exception('Raster size too small');
		}

		$fullsize = filesize($name);
		if ((($fullsize - $headerlen) != (($this->width * $this->height) * 2))) {
			throw new Exception('File has the wrong length');
		}

		$this->headerlen = $headerlen;
		$this->raw= $f;
		$this->rlonres = ($this->width / 360.0);
		$this->rlatres = (($this->height - 1) / 180.0);
	}

	private function _rawval($ix,$iy) {
		if (($iy < 0)) {
			$iy = -$iy;
			$ix += ($this->width / 2);
		} else if (($iy >= $this->height)) {
			$iy = ((2 * ($this->height - 1)) - $iy);
			$ix += ($this->width / 2);
		}
		if (($ix < 0)) {
			$ix += $this->width;
		} else if (($ix >= $this->width)) {
			$ix -= $this->width;
		}
		$k = ((($iy * $this->width) + $ix) * 2) + $this->headerlen;
		fseek($this->raw,$k);
		return unpack('n',fread($this->raw,2))[1];
	}

	public function get($lat,$lon,$cubic=true) {
		if (($lon < 0)) {
			$lon += 360;
		}
		$fy = ((90 - $lat) * $this->rlatres);
		$fx = ($lon * $this->rlonres);
		$iy = (int)$fy;
		$ix = (int)$fx;
		$fx -= $ix;
		$fy -= $iy;
		$t = array();
		if (($iy == ($this->height - 1))) {
			$iy -= 1;
		}
		if (($ix != $this->ix) || ($iy != $this->iy)) {
			$this->ix = $ix;
			$this->iy = $iy;
			if (!($cubic)) {
				$this->v00 = $this->_rawval($ix, $iy);
				$this->v01 = $this->_rawval(($ix + 1), $iy);
				$this->v10 = $this->_rawval($ix, ($iy + 1));
				$this->v11 = $this->_rawval(($ix + 1), ($iy + 1));
			} else {
				$v = [$this->_rawval($ix, ($iy - 1)), $this->_rawval(($ix + 1), ($iy - 1)), $this->_rawval(($ix - 1), $iy), $this->_rawval($ix, $iy), $this->_rawval(($ix + 1), $iy), $this->_rawval(($ix + 2), $iy), $this->_rawval(($ix - 1), ($iy + 1)), $this->_rawval($ix, ($iy + 1)), $this->_rawval(($ix + 1), ($iy + 1)), $this->_rawval(($ix + 2), ($iy + 1)), $this->_rawval($ix, ($iy + 2)), $this->_rawval(($ix + 1), ($iy + 2))];
				if (($iy == 0)) {
					$c3x = $this->c3n;
					$c0x = $this->c0n;
				} else if (($iy == ($this->height - 2))) {
					$c3x = $this->c3s;
					$c0x = $this->c0s;
				} else {
					$c3x = $this->c3;
					$c0x = $this->c0;
				}
				for ($i = 0; $i < 10;++$i) {
					$t[$i] = 0;
					for ($j = 0; $j < 12; ++$j) {
						$t[$i] += $v[$j]*$c3x[$j][$i];
					}
					$t[$i] /= $c0x;
				}
			}
			$this->t = $t;
		} else $t = $this->t;
		if (!($cubic)) {
			$a = (((1 - $fx) * $this->v00) + ($fx * $this->v01));
			$b = (((1 - $fx) * $this->v10) + ($fx * $this->v11));
			$h = (((1 - $fy) * $a) + ($fy * $b));
		} else {
			$h = (($t[0] + ($fx * ($t[1] + ($fx * ($t[3] + ($fx * $t[6])))))) + ($fy * (($t[2] + ($fx * ($t[4] + ($fx * $t[7])))) + ($fy * (($t[5] + ($fx * $t[8])) + ($fy * $t[9]))))));
		}
		return ((float)$this->offset + ((float)$this->scale * (float)$h));
	}
}
/*
$GeoidHeight = new GeoidHeight('../install/tmp/egm96-15.pgm');
$result = $GeoidHeight->get(46.3870,5.2941);
var_dump($result);
*/
?>