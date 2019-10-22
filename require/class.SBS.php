<?php
/**
 * This class is part of FlightAirmap. It's used to parse SBS binary msg
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/

class SBS {
	static $latlon = array();
	
	/*
	 * Parse binary SBS data
	 * @param String $buffer SBS binary data
	 * @return Array Return parsed result
	*/
	public function parse($buffer) {
		// Not yet finished, no CRC checks
		$data = array();
		$typehex = substr($buffer,0,1);
		if ($typehex == '*' || $typehex == ':') {
			$hex = substr($buffer,1);
			if(substr($hex,-1,1)==";") {
				$hex=substr($hex,0,-1);
			}
		}
		//elseif ($typehex == '@' || $typehex == '%') $hex = substr($buffer,13,-13);
		elseif ($typehex == '@' || $typehex == '%') $hex = substr($buffer,13,-1);
		else $hex = substr($buffer,1,-1);
		$bin = gmp_strval( gmp_init($hex,16), 2);
		//if (strlen($hex) == 28 && $this->parityCheck($hex,$bin)) {
		//if (strlen($hex) == 28) {
		//echo strlen($hex);
		//echo $hex;
		if (strlen($hex) == 28 || strlen($hex) == 16) {
			$df = intval(substr($bin,0,5),2);
			//$ca = intval(substr($bin,5,3),2);
			// Only support DF17 for now
			//if ($df == 17 || ($df == 18 && ($ca == 0 || $ca == 1 || $ca == 6))) {
			if (($df == 17 || $df == 18) && ($this->parityCheck($hex,$bin) || $typehex == '@')) {
				$icao = substr($hex,2,6);
				$data['hex'] = $icao;
				$tc = intval(substr($bin,32,5),2);
				if ($tc >= 1 && $tc <= 4) {
					// Category:
					// 1 = light aircraft <= 7000 kg
					// 2 = reserved
					// 3 = 7000 kg < medium aircraft < 136000 kg
					// 4 = reserved
					// 5 = 136000 kg <= heavy aircraft
					// 6 = highly manoeuvrable (5g acceleration capability) and high speed (>400 knots cruise)
					// 7 to 9 = reserved
					// 10 = rotocraft
					// 11 = glider / sailplane
					// 12 = lighter-than-air
					// 13 = unmanned aerial vehicle
					// 14 = space / transatmospheric vehicle
					// 15 = ultralight / handglider / paraglider
					// 16 = parachutist / skydiver
					// 17 to 19 = reserved
					// 20 = surface emergency vehicle
					// 21 = surface service vehicle
					// 22 = fixed ground or tethered obstruction
					// 23 to 24 = reserved
					//$data['category'] = intval(substr($bin,5,3),2);
					$data['category'] = intval(substr($bin,37,3),2);
					//callsign
					$csbin = substr($bin,40,56);
					$charset = str_split('#ABCDEFGHIJKLMNOPQRSTUVWXYZ#####_###############0123456789######');
					$cs = '';
					$cs .= $charset[intval(substr($csbin,0,6),2)];
					$cs .= $charset[intval(substr($csbin,6,6),2)];
					$cs .= $charset[intval(substr($csbin,12,6),2)];
					$cs .= $charset[intval(substr($csbin,18,6),2)];
					$cs .= $charset[intval(substr($csbin,24,6),2)];
					$cs .= $charset[intval(substr($csbin,30,6),2)];
					$cs .= $charset[intval(substr($csbin,36,6),2)];
					$cs .= $charset[intval(substr($csbin,42,6),2)];
					$cs = str_replace('_','',$cs);
					$cs = str_replace('#','',$cs);
					$callsign = $cs;
					$data['ident'] = $callsign;
				} elseif ($tc >= 9 && $tc <= 18) {
					// Check Q-bit
					$q = substr($bin,47,1);
					if ($q) {
						$n = intval(substr($bin,40,7).substr($bin,48,4),2);
						$alt = $n*25-1000;
						$data['altitude'] = $alt;
					}
					// Check odd/even flag
					$oe = substr($bin,53,1);
					//if ($oe) => odd else even
					//  131072 is 2^17 since CPR latitude and longitude are encoded in 17 bits.
					$cprlat = intval(substr($bin,54,17),2)/131072.0;
					$cprlon = intval(substr($bin,71,17),2)/131072.0;
					if ($oe == 0) $this::$latlon[$icao] = array('latitude' => $cprlat,'longitude' => $cprlon,'created' => time());
					elseif (isset($this::$latlon[$icao]) && (time() - $this::$latlon[$icao]['created']) < 10) {
						$cprlat_odd = $cprlat;
						$cprlon_odd = $cprlon;
						$cprlat_even = $this::$latlon[$icao]['latitude'];
						$cprlon_even = $this::$latlon[$icao]['longitude'];
						$j = 59*$cprlat_even-60*$cprlat_odd+0.5;
						$lat_even = (360.0/60)*($j%60+$cprlat_even);
						$lat_odd = (360.0/59)*($j%59+$cprlat_odd);
						if ($lat_even >= 270) $lat_even = $lat_even - 360;
						if ($lat_odd >= 270) $lat_odd = $lat_odd - 360;
						// check latitude zone
						if ($this->cprNL($lat_even) == $this->cprNL($lat_odd)) {
							if ($this::$latlon[$icao]['created'] > time()) {
								$ni = $this->cprN($lat_even,0);
								$m = floor($cprlon_even*($this->cprNL($lat_even)-1) - $cprlon_odd * $this->cprNL($lat_even)+0.5);
								$lon = (360.0/$ni)*($m%$ni+$cprlon_even);
								$lat = $lat_even;
								if ($lon > 180) $lon = $lon -360;
								if ($lat > -91 && $lat < 91 && $lon > -181 && $lon < 181) {
									//if ($globalDebug) echo 'cs : '.$cs.' - hex : '.$hex.' - lat : '.$lat.' - lon : '.$lon;
									$data['latitude'] = $lat;
									$data['longitude'] = $lon;
								}
							} else {
								$ni = $this->cprN($lat_odd,1);
								$m = floor($cprlon_even*($this->cprNL($lat_odd)-1) - $cprlon_odd * $this->cprNL($lat_odd)+0.5);
								$lon = (360.0/$ni)*($m%$ni+$cprlon_odd);
								$lat = $lat_odd;
								if ($lon > 180) $lon = $lon -360;
								if ($lat > -91 && $lat < 91 && $lon > -181 && $lon < 181) {
									//if ($globalDebug) echo 'icao : '.$icao.' - hex : '.$hex.' - lat : '.$lat.' - lon : '.$lon.' second'."\n";
									$data['latitude'] = $lat;
									$data['longitude'] = $lon;
								}
							}
						} else echo "Not cprNL";
						unset($this::$latlon[$icao]);
					}
				} elseif ($tc == 19) {
					// speed & heading
					$v_ew_dir = intval(substr($bin,45,1));
					$v_ew = intval(substr($bin,46,10),2);
					$v_ns_dir = intval(substr($bin,56,1));
					$v_ns = intval(substr($bin,57,10),2);
					if ($v_ew_dir) $v_ew = -1*$v_ew;
					if ($v_ns_dir) $v_ns = -1*$v_ns;
					$speed = sqrt($v_ns*$v_ns+$v_ew*$v_ew);
					$heading = atan2($v_ew,$v_ns)*360.0/(2*pi());
					if ($heading <0) $heading = $heading+360;
					$data['speed'] = $speed;
					$data['heading'] = $heading;
				}
			}
			if (isset($data)) {
				return $data;
			}
		}
	}

	/*
	 * Lookup table to convert the latitude to index.
	*/
	private function cprNL($lat) {
		if ($lat < 0) $lat = -$lat;             // Table is simmetric about the equator.
		if ($lat < 10.47047130) return 59;
		if ($lat < 14.82817437) return 58;
		if ($lat < 18.18626357) return 57;
		if ($lat < 21.02939493) return 56;
		if ($lat < 23.54504487) return 55;
		if ($lat < 25.82924707) return 54;
		if ($lat < 27.93898710) return 53;
		if ($lat < 29.91135686) return 52;
		if ($lat < 31.77209708) return 51;
		if ($lat < 33.53993436) return 50;
		if ($lat < 35.22899598) return 49;
		if ($lat < 36.85025108) return 48;
		if ($lat < 38.41241892) return 47;
		if ($lat < 39.92256684) return 46;
		if ($lat < 41.38651832) return 45;
		if ($lat < 42.80914012) return 44;
		if ($lat < 44.19454951) return 43;
		if ($lat < 45.54626723) return 42;
		if ($lat < 46.86733252) return 41;
		if ($lat < 48.16039128) return 40;
		if ($lat < 49.42776439) return 39;
		if ($lat < 50.67150166) return 38;
		if ($lat < 51.89342469) return 37;
		if ($lat < 53.09516153) return 36;
		if ($lat < 54.27817472) return 35;
		if ($lat < 55.44378444) return 34;
		if ($lat < 56.59318756) return 33;
		if ($lat < 57.72747354) return 32;
		if ($lat < 58.84763776) return 31;
		if ($lat < 59.95459277) return 30;
		if ($lat < 61.04917774) return 29;
		if ($lat < 62.13216659) return 28;
		if ($lat < 63.20427479) return 27;
		if ($lat < 64.26616523) return 26;
		if ($lat < 65.31845310) return 25;
		if ($lat < 66.36171008) return 24;
		if ($lat < 67.39646774) return 23;
		if ($lat < 68.42322022) return 22;
		if ($lat < 69.44242631) return 21;
		if ($lat < 70.45451075) return 20;
		if ($lat < 71.45986473) return 19;
		if ($lat < 72.45884545) return 18;
		if ($lat < 73.45177442) return 17;
		if ($lat < 74.43893416) return 16;
		if ($lat < 75.42056257) return 15;
		if ($lat < 76.39684391) return 14;
		if ($lat < 77.36789461) return 13;
		if ($lat < 78.33374083) return 12;
		if ($lat < 79.29428225) return 11;
		if ($lat < 80.24923213) return 10;
		if ($lat < 81.19801349) return 9;
		if ($lat < 82.13956981) return 8;
		if ($lat < 83.07199445) return 7;
		if ($lat < 83.99173563) return 6;
		if ($lat < 84.89166191) return 5;
		if ($lat < 85.75541621) return 4;
		if ($lat < 86.53536998) return 3;
		if ($lat < 87.00000000) return 2;
		return 1;
	}

	private function cprN($lat,$isodd) {
		$nl = $this->cprNL($lat) - $isodd;
		if ($nl > 1) return $nl;
		else return 1;
	}

	private function parityCheck($msg, $bin) {
		$modes_checksum_table = array(
		    0x3935ea, 0x1c9af5, 0xf1b77e, 0x78dbbf, 0xc397db, 0x9e31e9, 0xb0e2f0, 0x587178,
		    0x2c38bc, 0x161c5e, 0x0b0e2f, 0xfa7d13, 0x82c48d, 0xbe9842, 0x5f4c21, 0xd05c14,
		    0x682e0a, 0x341705, 0xe5f186, 0x72f8c3, 0xc68665, 0x9cb936, 0x4e5c9b, 0xd8d449,
		    0x939020, 0x49c810, 0x24e408, 0x127204, 0x093902, 0x049c81, 0xfdb444, 0x7eda22,
		    0x3f6d11, 0xe04c8c, 0x702646, 0x381323, 0xe3f395, 0x8e03ce, 0x4701e7, 0xdc7af7,
		    0x91c77f, 0xb719bb, 0xa476d9, 0xadc168, 0x56e0b4, 0x2b705a, 0x15b82d, 0xf52612,
		    0x7a9309, 0xc2b380, 0x6159c0, 0x30ace0, 0x185670, 0x0c2b38, 0x06159c, 0x030ace,
		    0x018567, 0xff38b7, 0x80665f, 0xbfc92b, 0xa01e91, 0xaff54c, 0x57faa6, 0x2bfd53,
		    0xea04ad, 0x8af852, 0x457c29, 0xdd4410, 0x6ea208, 0x375104, 0x1ba882, 0x0dd441,
		    0xf91024, 0x7c8812, 0x3e4409, 0xe0d800, 0x706c00, 0x383600, 0x1c1b00, 0x0e0d80,
		    0x0706c0, 0x038360, 0x01c1b0, 0x00e0d8, 0x00706c, 0x003836, 0x001c1b, 0xfff409,
		    0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
		    0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000,
		    0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000, 0x000000
		);
		$crc = 0;
		$checksum = intval(substr($msg,22,6),16);
		for ($j = 0; $j < strlen($bin); $j++) {
			if ($bin[$j]) $crc = $crc^intval($modes_checksum_table[$j],0);
		}
		if ($crc == $checksum) return true;
		else {
			//echo "**** CRC ERROR ****\n";
			return false;
		}
	}

	/*
	 * Convert data to BaseStation format
	*/
	public function famaprs_to_basestation($data) {
		$result = array();
		if (isset($data['ident']) && $data['ident'] != '') {
			$msg = array();
			$msg['msg_type'] = 'MSG';
			$msg['transmission_type'] = 1;
			$msg['session_id'] = 5;
			$msg['aircraftid'] = hexdec($data['address']);
			$msg['hex'] = $data['address'];
			$msg['flightid'] = hexdec($data['address']);
			$msg['date_gen'] = date('Y/m/d',$data['timestamp']);
			$msg['time_gen'] = date('H:i:s',$data['timestamp']).'.180';
			$msg['date_log'] = date('Y/m/d',$data['timestamp']);
			$msg['time_log'] = date('H:i:s',$data['timestamp']).'.180';
			$msg['callsign'] = $data['ident'];
			$msg['altitude'] = '';
			$msg['speed'] = '';
			$msg['track'] = '';
			$msg['latitude'] = '';
			$msg['longitude'] = '';
			$msg['verticalrate'] = '';
			$msg['squawk'] = '';
			$msg['alert'] = '';
			$msg['emergency'] = '';
			$msg['SPI'] = '';
			$msg['ground'] = '';
			$result[] = implode(',',$msg);
		}
		if (isset($data['latitude']) && $data['latitude'] != 0) {
			$msg = array();
			$msg['msg_type'] = 'MSG';
			$msg['transmission_type'] = 2;
			$msg['session_id'] = 5;
			$msg['aircraftid'] = hexdec($data['address']);
			$msg['hex'] = $data['address'];
			$msg['flightid'] = hexdec($data['address']);
			$msg['date_gen'] = date('Y/m/d',$data['timestamp']);
			$msg['time_gen'] = date('H:i:s',$data['timestamp']).'.180';
			$msg['date_log'] = date('Y/m/d',$data['timestamp']);
			$msg['time_log'] = date('H:i:s',$data['timestamp']).'.180';
			$msg['callsign'] = '';
			if (isset($data['altitude'])) $msg['altitude'] = $data['altitude'];
			else $msg['altitude'] = '';
			$msg['speed'] = $data['speed'];
			if (isset($data['heading'])) $msg['track'] = $data['heading'];
			else $msg['track'] = '';
			$msg['latitude'] = $data['latitude'];
			$msg['longitude'] = $data['longitude'];
			if (isset($data['verticalrate'])) $msg['verticalrate'] = $data['verticalrate'];
			else $msg['verticalrate'] = '';
			if (isset($data['squawk'])) $msg['squawk'] = $data['squawk'];
			else $msg['squawk'] = 0;
			$msg['alert'] = 0;
			$msg['emergency'] = 0;
			$msg['SPI'] = 0;
			if (isset($data['ground'])) $msg['ground'] = 1;
			else $msg['ground'] = 0;
			$result[] = implode(',',$msg);
		}
		return $result;
	}
}
?>
