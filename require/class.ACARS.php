<?php
/**
 * This class is part of FlightAirmap. It's used to parse ACARS messages.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/

require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.SpotterImport.php');
require_once(dirname(__FILE__).'/class.Image.php');
require_once(dirname(__FILE__).'/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.Translation.php');

class ACARS {
	public $db;
	public $SI;
	private $fromACARSscript = false;

	/*
	 * Initialize DB connection
	*/
	public function __construct($dbc = null,$fromACARSscript = false) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (ACARS)');
		if ($fromACARSscript) {
			$this->fromACARSscript = true;
			$this->SI = new SpotterImport($this->db);
		}
	}

	/**
	* Change IATA to ICAO value for ident
	*
	* @param String $ident ident
	* @return String the icao
	*/
	public function ident2icao($ident) {
		if (substr($ident,0,2) == 'AF') {
			if (filter_var(substr($ident,2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $ident;
			else $icao = 'AFR'.ltrim(substr($ident,2),'0');
		} else {
			$Spotter = new Spotter($this->db);
			$identicao = $Spotter->getAllAirlineInfo(substr($ident,0,2));
			if (isset($identicao[0])) {
				$icao = $identicao[0]['icao'].ltrim(substr($ident,2),'0');
			} else $icao = $ident;
		}
		return $icao;
	}

	/**
	* Deletes all info in the live table
	*
	* @return String success or false
	*
	*/
	public function deleteLiveAcarsData()
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = "DELETE FROM acars_live WHERE acars_live.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE)";
		} else {
			$query  = "DELETE FROM acars_live WHERE acars_live.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '30 MINUTES'";
		}
		try {

			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}
		return "success";
	}

	/**
	* Deletes all info in the archive table
	*
	* @return String success or false
	*
	*/
	public function deleteArchiveAcarsData()
	{
		global $globalACARSArchiveKeepMonths, $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = "DELETE FROM acars_archive WHERE acars_archive.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalACARSArchiveKeepMonths." MONTH)";
		} else {
			$query  = "DELETE FROM acars_archive WHERE acars_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalACARSArchiveKeepMonths." MONTHS'";
		}
		try {

			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}
		return "success";
	}


    /**
     * Parse ACARS data
     *
     * @param String ACARS data in acarsdec data
     *
     * @return array
     */
	public function parse($data) {
		global $globalDebug;
		//$Image = new Image($this->db);
		//$Schedule = new Schedule($this->db);
		//$Translation = new Translation($this->db);
		$registration = '';
		$label = '';
		$block_id = '';
		$msg_no = '';
		$ident = '';
		$message = '';
		$result = array();
		$n = sscanf($data,'%*[0-9a-z.] %*d %*02d/%*02d/%*04d %*02d:%*02d:%*02d %*d %*[0-9-] %*[A-Z0-9] %7s %*c %2[0-9a-zA-Z_] %d %4[0-9A-Z] %6[0-9A-Z] %[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n == 0 || $message == '') $n = sscanf($data,'AC%*c %7s %*c %2[0-9a-zA-Z_] %d %4[0-9A-Z] %6[0-9A-Z] %[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n == 0 || $message == '') $n = sscanf($data,'%*04d-%*02d-%*02d,%*02d:%*02d:%*02d,%*7s,%*c,%6[0-9A-Z-],%*c,%2[0-9a-zA-Z_],%d,%4[0-9A-Z],%6[0-9A-Z],%[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n == 0 || $message == '') $n = sscanf($data,'%*04d-%*02d-%*02d,%*02d:%*02d:%*02d,%*7s,%*c,%6[0-9A-Z-],,%2[0-9a-zA-Z_],%d,%4[0-9A-Z],%6[0-9A-Z],%[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n == 0 || $message == '') $n = sscanf($data,'%*04d-%*02d-%*02d,%*02d:%*02d:%*02d,%*7s,%*c,%5[0-9A-Z],%*c,%2[0-9a-zA-Z_],%d,%4[0-9A-Z],%6[0-9A-Z],%[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n == 0 || $message == '') $n = sscanf($data,'%*04d-%*02d-%*02d,%*02d:%*02d:%*02d,%*7s,%*c,%6[0-9A-Z-],%*c,%2[0-9a-zA-Z_],%d,%4[0-9A-Z],%6[0-9A-Z],%[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
		if ($n != 0 && ($registration != '' || $ident != '' || $label != '' || $block_id != '' || $msg_no != '')) {
			$registration = str_replace('.','',$registration);
			$result = array('registration' => $registration, 'ident' => $ident,'label' => $label, 'block_id' => $block_id,'msg_no' => $msg_no,'message' => $message);
			if ($globalDebug) echo "Reg. : ".$registration." - Ident : ".$ident." - Label : ".$label." - Message : ".$message."\n";
		} else $message = $data;
		$decode = array();
		$found = false;
//		if ($registration != '' && $ident != '' && $registration != '!') {
			if (!$found) {
				// example message : "FST01EGLLLIRFN047599E0033586390  55  25- 4C 74254      487  2059194"
				//								    FST01MMMXEGKKN376904W079449733007380380 019061 XA1237 =>  wind direction and velocity (019/061)
				//$n = sscanf($message, "FST01%4c%4c%c%06d%c%07d%*11[0-9a-zA-Z ]-%02dC", $dair, $darr, $lac, $la, $lnc, $ln, $temp);
				$dair = '';
				$darr = '';
				$lac = '';
				$la = '';
				$lnc = '';
				$ln = '';
				$alt = '';
				$temp = '';
				$n = sscanf($message, "FST01%4c%4c%c%06d%c%07d%03d%*8[0-9a-zA-Z ]-%02dC", $dair, $darr, $lac, $la, $lnc, $ln, $alt, $temp);
				if ($n > 5 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
					$latitude = $la / 10000.0;
					$longitude = $ln / 10000.0;
					if ($lac == 'S') $latitude = '-'.$latitude;
					if ($lnc == 'W') $longitude = '-'.$longitude;
					// Temp not always available
					if ($globalDebug) echo 'latitude : '.$latitude.' - longitude : '.$longitude.' - airport depart : '.$dair.' - airport arrival : '.$darr.' - température : '.$temp."°C\n";
					if ($temp == '') $decode = array('Latitude' => $latitude, 'Longitude' =>  $longitude, 'Departure airport' => $dair, 'Arrival airport' => $darr,'Altitude' => $alt);
					else $decode = array('Latitude' => $latitude, 'Longitude' =>  $longitude, 'Departure airport' => $dair, 'Arrival airport' => $darr, 'Altitude' => 'FL'.$alt,'Temperature' => $temp.'°C');

					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$found = true;
				}
			}
			if (!$found && ($label == '10')) {
				$dair = '';
				$dhour = '';
				$darr = '';
				$ahour = '';
				$n = sscanf($message, "ARR01 %4[A-Z]%4d %4[A-Z]%4d", $dair, $dhour, $darr,$ahour);
				if ($n == 4 && strlen($darr) == 4) {
					if ($dhour != '') $dhour = substr(sprintf('%04d',$dhour),0,2).':'.substr(sprintf('%04d',$dhour),2);
					if ($ahour != '') $ahour = substr(sprintf('%04d',$ahour),0,2).':'.substr(sprintf('%04d',$ahour),2);
					if ($globalDebug) echo 'departure airport : '.$dair.' - arrival airport : '. $darr.' - departure hour : '. $dhour.' - arrival hour : '.$ahour."\n";
					//$icao = ACARS->ident2icao($ident);
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,$dhour,$darr,$ahour,'ACARS');
					$decode = array('Departure airport' => $dair, 'Departure hour' => $dhour, 'Arrival airport' => $darr, 'Arrival hour' => $ahour);
					$found = true;
				}
				elseif ($n == 2 || $n  == 4) {
					if ($dhour != '') $dhour = substr(sprintf('%04d',$dhour),0,2).':'.substr(sprintf('%04d',$dhour),2);
					if ($globalDebug) echo 'airport arrival : '.$dair.' - arrival hour : '.$dhour."\n";
					//$icao = ACARS->ident2icao($ident);
					//$icao = $Translation->checkTranslation($ident);
					$decode = array('Arrival airport' => $dair, 'Arrival hour' => $dhour);
					$found = true;
				}
				elseif ($n == 1) {
					if ($globalDebug) echo 'airport arrival : '.$darr."\n";
					//$icao = ACARS->ident2icao($ident);
					//$icao = $Translation->checkTranslation($ident);
					$decode = array('Arrival airport' => $darr);
					$found = true;
				}

			}
			if (!$found && ($label == '13' || $label == '12')) {
				// example message : "Reg. : OO-DWA - Ident : SN01LY - Label : 13 - Message : EBBR,LFLL,26FEB15,1626,164626"
				/*
				Reg. : OO-SSP - Ident : SN01LY - Label : 13 - Message : EBBR,LFLL,27FEB15,1624,164400
				N 46.493,E  3.980,19810
				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message, "%4c,%4c,%*7s,%*d", $dair, $darr);
				if ($n == 4) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = ACARS->ident2icao($ident);
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/*
				example message :
				    VER/071/A320/M
				    SCH/AF6244/LFPO/LFMN/25FEB/0905
				    FTX/ADDRESS/DEST STA/LFMN
				*/
				/*
				example message :
				    VER/070/A319/M
				    SCH/AF7671/LFML/LFPG/23FEB/1820
				    WXR/META/LFLL/LFLC/LFPG
				*/

				//$n = sscanf($message, "%*[0-9A-Z]/%*3d/%4s/%*c\nSCH/%6[0-9A-Z ]/%4c/%4c/%5s/%4d\n%*3c/%4d/%4c/%[0-9A-Z ]/", $airicao,$aident,$dair, $darr, $ddate, $dhour,$ahour, $aair, $apiste);
				$airicao = '';
				$aident = '';
				$dair = '';
				$darr = '';
				$ddate = '';
				$dhour = '';
				$ahour = '';
				$aair = '';
				$apiste = '';
				$n = sscanf(str_replace(array("\r\n", "\n", "\r"),'',$message), "%*[0-9A-Z]/%*3d/%4s/%*cSCH/%6[0-9A-Z ]/%4c/%4c/%5s/%4d%*3c/%4d/%4c/%[0-9A-Z ]/", $airicao,$aident,$dair, $darr, $ddate, $dhour,$ahour, $aair, $apiste);
				if ($n > 8) {
					if ($globalDebug) echo 'airicao : '. $airicao.' - ident : '.$aident.' - departure airport : '.$dair.' - arrival airport : '. $darr.' - date depart : '.$ddate.' - departure hour : '. $dhour.' - arrival hour : '.$ahour.' - arrival airport : '.$aair.' - arrival piste : '.$apiste."\n";
					if ($dhour != '') $dhour = substr(sprintf('%04d',$dhour),0,2).':'.substr(sprintf('%04d',$dhour),2);
					if ($ahour != '') $ahour = substr(sprintf('%04d',$ahour),0,2).':'.substr(sprintf('%04d',$ahour),2);
					$icao = trim($aident);

					//$decode = 'Departure airport : '.$dair.' ('.$ddate.' at '.$dhour.') - Arrival Airport : '.$aair.' (at '.$ahour.') way '.$apiste;
					if ($ahour == '') $decode = array('Departure airport' => $dair, 'Departure date' => $ddate, 'Departure hour' => $dhour, 'Arrival airport' => $darr);
					else $decode = array('Departure airport' => $dair, 'Departure date' => $ddate, 'Departure hour' => $dhour, 'Arrival airport' => $darr, 'Arrival hour' => $ahour, 'Arrival way' => $apiste);
					//$Schedule->addSchedule($icao,$dair,$dhour,$darr,$ahour,'ACARS');
					$decode['icao'] = $icao;
					$found = true;
				}
			}

			if (!$found) {
				// example message : "221111,34985,0817,  65,N 50.056 E 13.850"
				//Reg. : CS-TFY - Ident : CR0321 - Label : 16 - Message : 140600,34008,1440,  66,N 46.768 E  4.793
				$lac = '';
				$las = '';
				$lass = '';
				$lnc = '';
				$lns = '';
				$lnss = '';
				$n = sscanf($message, "%*6c,%*5c,%*4c,%*4c,%c%3d.%3d %c%3d.%3d,", $lac, $las, $lass, $lnc, $lns, $lnss);
				if ($n == 10 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
					$las = $las.'.'.$lass;
					$lns = $lns.'.'.$lns;
					$latitude = $las / 1000.0;
					$longitude = $lns / 1000.0;
					if ($lac == 'S') $latitude = '-'.$latitude;
					if ($lnc == 'W') $longitude = '-'.$longitude;
					if ($globalDebug) echo 'latitude : '.$latitude.' - longitude : '.$longitude."\n";
					$decode = array('Latitude' => $latitude, 'Longitude' => $longitude);
					$found = true;
				}
			}
			if (!$found && $label == '5U') {
				/*
				example message :
				    Reg. : CN-RNR - Ident : AT816C - Label : 5U - Message :   01 WXRQ   816C/27 GMMN/EDDT .CN-RNR
				/TYP 1/STA EDDT/STA LSZH/STA EDDS
				Reg. : G-OOBD - Ident : DP079T - Label : 5U - Message :   01 WXRQ   079T/28 LIPX/EGKK .G-OOBD
				/TYP 1/STA EGKK/STA EGLL/STA EGHH

				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message, "%*[0-9A-Z ]/%*s %4c/%4c .", $dair, $darr);
				if ($n == 4) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found && $label == '1L') {
				// example message : "Reg. : TS-ION - Ident : TU0634 - Label : 1L - Message : 000442152001337,DTTJ,LFPO,1609"
				$dair = '';
				$darr = '';
				$n = sscanf($message, "%*[0-9],%4c,%4c,", $dair, $darr);
				if ($n == 4) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found && $label == '5U') {
				// example message : "Reg. : OO-TAH - Ident : 3V042J - Label : 5U - Message : 002AF   EBLG EBBR                     N4621.5E  524.2195"
				$dair = '';
				$darr = '';
				$n = sscanf($message, "002AF %4c %4c ", $dair, $darr);
				if ($n == 2) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$found = true;
				}
			}

			if (!$found && $label == 'H1') {
				// example message : 'Reg. : F-GHQJ - Ident : AF6241 - Label : H1 - Message : #DFBA01/CCF-GHQJ,FEB27,205556,LFMN,LFPO,0241/C106,17404,5000,42,0010,0,0100,42,X/CEN270,36012,257,778,6106,299,B5B7G8/EC731134,42387,01439,41194,12/EE731212,44932,11870,43555,12/N10875,0875,0910,6330,1205,-----'
				$dair = '';
				$darr = '';
				$n = sscanf($message, "#DFBA%*02d/%*[A-Z-],%*[0-9A-Z],%*d,%4c,%4c", $dair, $darr);
				if ($n == 6) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found && $label == 'H1') {
				// example message : 'Reg. : F-GUGP - Ident : AF1842 - Label : H1 - Message : #DFBA01/A31801,1,1/CCF-GUGP,MAR11,093856,LFPG,LSGG,1842/C106,55832,5000,37,0010,0,0100,37,X/CEN282,31018,277,750,5515,255,C11036/EC577870,02282,07070,01987,73,14/EE577871,02282,06947,01987,73/N10790,0790,0903,5'
				$dair = '';
				$darr = '';
				$n = sscanf($message, "#DFBA%*02d/%*[0-9A-Z,]/%*[A-Z-],%*[0-9A-Z],%*d,%4c,%4c", $dair, $darr);
				if ($n == 7) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				 Reg. : PH-BXO - Ident : KL079K - Label : H1 - Message : #DFB(POS-KLM79K  -4319N00252E/143435 F390
				RMK/FUEL   2.6 M0.79)
				*/
				//$n = sscanf($message, "#DFB(POS-%s -%4d%c%5d%c/%*d F%d\nRMK/FUEL %f M%f", $aident, $las, $lac, $lns, $lnc, $alt, $fuel, $speed);
				$aident = '';
				$las = '';
				$lac = '';
				$lns = '';
				$lnc = '';
				$alt = '';
				$fuel = '';
				$speed = '';
				$n = sscanf(str_replace(array("\r\n", "\n", "\r"),'',$message), "#DFB(POS-%s -%4d%c%5d%c/%*d F%dRMK/FUEL %f M%f", $aident, $las, $lac, $lns, $lnc, $alt, $fuel, $speed);
				if ($n == 9) {
					//if (self->$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					$icao = trim($aident);
					$decode['icao'] = $icao;
					$latitude = $las / 100.0;
					$longitude = $lns / 100.0;
					if ($lac == 'S') $latitude = '-'.$latitude;
					if ($lnc == 'W') $longitude = '-'.$longitude;

					$decode = array('Latitude' => $latitude,'Longitude' => $longitude,'Altitude' => 'FL'.$alt,'Fuel' => $fuel,'speed' => $speed);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				Reg. : F-HBSA - Ident : XK505F - Label : 16 - Message : LAT N 46.184/LON E  5.019
				*/
				$lac = '';
				$las = '';
				$lnc = '';
				$lns = '';
				$n = sscanf($message, "LAT %c %f/LON %c %f", $lac, $las, $lnc, $lns);
				if ($n == 4) {
					$latitude = $las;
					$longitude = $lns;
					if ($lac == 'S') $latitude = '-'.$latitude;
					if ($lnc == 'W') $longitude = '-'.$longitude;

					$decode = array('Latitude' => $latitude,'Longitude' => $longitude);
					$found = true;
				}
			}

			if (!$found && $label == '80') {
				/* example message :
				Reg. : EI-DSB - Ident : AZ0207 - Label : 80 - Message : 3X01 NLINFO 0207/28 EGLL/LIRF .EI-DSB
				/AZ/1783/28/FCO/N
				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message, "%*[0-9A-Z] NLINFO %*d/%*d %4c/%4c .", $dair, $darr);
				if ($n == 5) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				Reg. : D-ABNK - Ident : AB930V - Label : 3O - Message : HB50,,
				930V,12MAR15,EDDH,LEPA,.D-ABNK,
				LEPA,
				AB7757,
				DABNK,10100,  7100,02:46, 200, 44068,52.4, 77000, 62500, 66000,3, 4,
				*/
				// $n = sscanf($message, "%*[0-9A-Z],,\n%*[0-9A-Z],%*[0-9A-Z],%4s,%4s,.%*6s,\n%*4[A-Z],\n%[0-9A-Z],", $dair, $darr, $aident);
				$dair = '';
				$darr = '';
				$aident = '';
				$n = sscanf(str_replace(array("\r\n", "\n", "\r"),'',$message), "%*[0-9A-Z],,%*[0-9A-Z],%*[0-9A-Z],%4s,%4s,.%*6s,%*4[A-Z],%[0-9A-Z],", $dair, $darr, $aident);
				if ($n == 8) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					$icao = trim($aident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode['icao'] = $icao;
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				Reg. : N702DN - Ident : DL0008 - Label : 80 - Message : 3401/11 KATL/OMDB .N702DN
				ACK RDA
				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message, "%*d/%*d %4s/%4s .%*6s", $dair, $darr);
				if ($n == 5) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				LFLLLFRS1315U2687X
				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message,'%4[A-Z]%4[A-Z]%*4d',$dair,$darr);
				if ($n == 3) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				/* example message :
				3J01 DSPTCH 7503/01 LFTH/LFPO .F-HMLF
				*/
				$dair = '';
				$darr = '';
				$n = sscanf($message,'3J01 DSPTCH %*d/%*d %4s/%4s .%*6s',$dair,$darr);
				if ($n == 3) {
					if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
					//$icao = $Translation->checkTranslation($ident);
					//$Schedule->addSchedule($icao,$dair,'',$darr,'','ACARS');
					$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
					$found = true;
				}
			}
			if (!$found) {
				$n = sscanf($message,'MET01%4c',$airport);
				if ($n == 1) {
					if ($globalDebug) echo 'airport name : '.$airport;
					$decode = array('Airport/Waypoint name' => $airport);
					$found = true;
				}
			}
			if ($label == 'H1') {
				if (preg_match('/^#CFBFLR/',$message) || preg_match('/^#CFBWRN/',$message)) {
					$decode = array_merge(array('Message nature' => 'Equipment failure'),$decode);
				}
				elseif (preg_match('/^#DFB\*TKO/',$message) || preg_match('/^#DFBTKO/',$message)) {
					$decode = array_merge(array('Message nature' => 'Take off performance data'),$decode);
				}
				elseif (preg_match('/^#DFB\*CRZ/',$message) || preg_match('/^#DFBCRZ/',$message)) {
					$decode = array_merge(array('Message nature' => 'Cruise performance data'),$decode);
				}
				elseif (preg_match('/^#DFB\*WOB/',$message) || preg_match('/^#DFBWOB/',$message)) {
					$decode = array_merge(array('Message nature' => 'Weather observation'),$decode);
				}
				elseif (preg_match(':^#DFB/PIREP:',$message)) {
					$decode = array_merge(array('Message nature' => 'Pilot Report'),$decode);
				}
				elseif (preg_match('/^#DFBEDA/',$message) || preg_match('/^#DFBENG/',$message)) {
					$decode = array_merge(array('Message nature' => 'Engine Data'),$decode);
				}
				elseif (preg_match(':^#M1AAEP:',$message)) {
					$decode = array_merge(array('Message nature' => 'Position/Weather Report'),$decode);
				}
				elseif (preg_match(':^#M2APWD:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight plan predicted wind data'),$decode);
				}
				elseif (preg_match(':^#M1BREQPWI:',$message)) {
					$decode = array_merge(array('Message nature' => 'Predicted wind info request'),$decode);
				}
				elseif (preg_match(':^#CF:',$message)) {
					$decode = array_merge(array('Message nature' => 'Central Fault Display'),$decode);
				}
				elseif (preg_match(':^#DF:',$message)) {
					$decode = array_merge(array('Message nature' => 'Digital Flight Data Acquisition Unit'),$decode);
				}
				elseif (preg_match(':^#EC:',$message)) {
					$decode = array_merge(array('Message nature' => 'Engine Display System'),$decode);
				}
				elseif (preg_match(':^#EI:',$message)) {
					$decode = array_merge(array('Message nature' => 'Engine Report'),$decode);
				}
				elseif (preg_match(':^#H1:',$message)) {
					$decode = array_merge(array('Message nature' => 'HF Data Radio - Left'),$decode);
				}
				elseif (preg_match(':^#H2:',$message)) {
					$decode = array_merge(array('Message nature' => 'HF Data Radio - Right'),$decode);
				}
				elseif (preg_match(':^#HD:',$message)) {
					$decode = array_merge(array('Message nature' => 'HF Data Radio - Selected'),$decode);
				}
				elseif (preg_match(':^#M1:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Management Computer - Left'),$decode);
				}
				elseif (preg_match(':^#M2:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Management Computer - Right'),$decode);
				}
				elseif (preg_match(':^#M3:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Management Computer - Center'),$decode);
				}
				elseif (preg_match(':^#MD:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Management Computer - Selected'),$decode);
				}
				elseif (preg_match(':^#PS:',$message)) {
					$decode = array_merge(array('Message nature' => 'Keyboard/Display Unit'),$decode);
				}
				elseif (preg_match(':^#S1:',$message)) {
					$decode = array_merge(array('Message nature' => 'SDU - Left'),$decode);
				}
				elseif (preg_match(':^#S2:',$message)) {
					$decode = array_merge(array('Message nature' => 'SDU - Right'),$decode);
				}
				elseif (preg_match(':^#SD:',$message)) {
					$decode = array_merge(array('Message nature' => 'SDU - Selected'),$decode);
				}
				elseif (preg_match(':^#T[0-8]:',$message)) {
					$decode = array_merge(array('Message nature' => 'Cabin Terminal Messages'),$decode);
				}
				elseif (preg_match(':^#WO:',$message)) {
					$decode = array_merge(array('Message nature' => 'Weather Observation Report'),$decode);
				}
				elseif (preg_match(':^#A1:',$message)) {
					$decode = array_merge(array('Message nature' => 'Oceanic Clearance'),$decode);
				}
				elseif (preg_match(':^#A3:',$message)) {
					$decode = array_merge(array('Message nature' => 'Departure Clearance Response'),$decode);
				}
				elseif (preg_match(':^#A4:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Systems Message'),$decode);
				}
				elseif (preg_match(':^#A6:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request ADS Reports'),$decode);
				}
				elseif (preg_match(':^#A8:',$message)) {
					$decode = array_merge(array('Message nature' => 'Deliver Departure Slot'),$decode);
				}
				elseif (preg_match(':^#A9:',$message)) {
					$decode = array_merge(array('Message nature' => 'ATIS report'),$decode);
				}
				elseif (preg_match(':^#A0:',$message)) {
					$decode = array_merge(array('Message nature' => 'ATIS Facility Notification (AFN)'),$decode);
				}
				elseif (preg_match(':^#AA:',$message)) {
					$decode = array_merge(array('Message nature' => 'ATCComm'),$decode);
				}
				elseif (preg_match(':^#AB:',$message)) {
					$decode = array_merge(array('Message nature' => 'TWIP Report'),$decode);
				}
				elseif (preg_match(':^#AC:',$message)) {
					$decode = array_merge(array('Message nature' => 'Pushback Clearance'),$decode);
				}
				elseif (preg_match(':^#AD:',$message)) {
					$decode = array_merge(array('Message nature' => 'Expected Taxi Clearance'),$decode);
				}
				elseif (preg_match(':^#AF:',$message)) {
					$decode = array_merge(array('Message nature' => 'CPC Command/Response'),$decode);
				}
				elseif (preg_match(':^#B1:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request Oceanic Clearance'),$decode);
				}
				elseif (preg_match(':^#B2:',$message)) {
					$decode = array_merge(array('Message nature' => 'Oceanic Clearance Readback'),$decode);
				}
				elseif (preg_match(':^#B3:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request Departure Clearance'),$decode);
				}
				elseif (preg_match(':^#B4:',$message)) {
					$decode = array_merge(array('Message nature' => 'Departure Clearance Readback'),$decode);
				}
				elseif (preg_match(':^#B6:',$message)) {
					$decode = array_merge(array('Message nature' => 'Provide ADS Report'),$decode);
				}
				elseif (preg_match(':^#B8:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request Departure Slot'),$decode);
				}
				elseif (preg_match(':^#B9:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request ATIS Report'),$decode);
				}
				elseif (preg_match(':^#B0:',$message)) {
					$decode = array_merge(array('Message nature' => 'ATS Facility Notification'),$decode);
				}
				elseif (preg_match(':^#BA:',$message)) {
					$decode = array_merge(array('Message nature' => 'ATCComm'),$decode);
				}
				elseif (preg_match(':^#BB:',$message)) {
					$decode = array_merge(array('Message nature' => 'Request TWIP Report'),$decode);
				}
				elseif (preg_match(':^#BC:',$message)) {
					$decode = array_merge(array('Message nature' => 'Pushback Clearance Request'),$decode);
				}
				elseif (preg_match(':^#BD:',$message)) {
					$decode = array_merge(array('Message nature' => 'Expected Taxi Clearance Request'),$decode);
				}
				elseif (preg_match(':^#BE:',$message)) {
					$decode = array_merge(array('Message nature' => 'CPC Aircraft Log-On/Off Request'),$decode);
				}
				elseif (preg_match(':^#BF:',$message)) {
					$decode = array_merge(array('Message nature' => 'CPC WILCO/UNABLE Response'),$decode);
				}
				elseif (preg_match(':^#H3:',$message)) {
					$decode = array_merge(array('Message nature' => 'Icing Report'),$decode);
				}
			}
			if ($label == '10') {
				if (preg_match(':^DTO01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Delayed Takeoff Report'),$decode);
				}
				elseif (preg_match(':^AIS01:',$message)) {
					$decode = array_merge(array('Message nature' => 'AIS Request'),$decode);
				}
				elseif (preg_match(':^FTX01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Free Text Downlink'),$decode);
				}
				elseif (preg_match(':^FPL01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Plan Request'),$decode);
				}
				elseif (preg_match(':^WAB01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Weight & Balance Request'),$decode);
				}
				elseif (preg_match(':^MET01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Weather Data Request'),$decode);
				}
				elseif (preg_match(':^WAB02:',$message)) {
					$decode = array_merge(array('Message nature' => 'Weight and Balance Acknowledgement'),$decode);
				}
			}
			if ($label == '15') {
				if (preg_match(':^FST01:',$message)) {
					$decode = array_merge(array('Message nature' => 'Flight Status Report'),$decode);
				}
			}
			if (!$found && $label == 'SA') {
				$n = sscanf($message, "%d%c%c%6[0-9]", $version,$state,$type,$at);
				if ($n == 4) {
					$vsta = array('Version' => $version);
					if ($state == 'E') {
						$vsta = array_merge($vsta,array('Link state' => 'Established'));
					}
					elseif ($state == 'L') {
						$vsta = array_merge($vsta,array('Link state' => 'Lost'));
					}
					else {
						$vsta = array_merge($vsta,array('Link state' => 'Unknown'));
					}
					if ($type == 'V') {
						$vsta = array_merge($vsta,array('Link type' => 'VHF ACARS'));
					}
					elseif ($type == 'S') {
						$vsta = array_merge($vsta,array('Link type' => 'Generic SATCOM'));
					}
					elseif ($type == 'H') {
						$vsta = array_merge($vsta,array('Link type' => 'HF'));
					}
					elseif ($type == 'G') {
						$vsta = array_merge($vsta,array('Link type' => 'GlobalStar SATCOM'));
					}
					elseif ($type == 'C') {
						$vsta = array_merge($vsta,array('Link type' => 'ICO SATCOM'));
					}
					elseif ($type == '2') {
						$vsta = array_merge($vsta,array('Link type' => 'VDL Mode 2'));
					}
					elseif ($type == 'X') {
						$vsta = array_merge($vsta,array('Link type' => 'Inmarsat Aero'));
					}
					elseif ($type == 'I') {
						$vsta = array_merge($vsta,array('Link type' => 'Irridium SATCOM'));
					}
					else {
						$vsta = array_merge($vsta,array('Link type' => 'Unknown'));
					}
					$vsta = array_merge($vsta,array('Event occured at' => implode(':',str_split($at,2))));
					$decode = array_merge($vsta,$decode);
				}
			}

			$title = $this->getTitlefromLabel($label);
			if ($title != '') $decode = array_merge(array('Message title' => $title),$decode);
			/*
			// Business jets always use GS0001
			if ($ident != 'GS0001') $info = $this->addModeSData($ident,$registration,$icao,$airicao,$latitude,$longitude);
			if ($globalDebug && isset($info) && $info != '') echo $info;
			$image_array = $Image->getSpotterImage($registration);
			if (!isset($image_array[0]['registration'])) {
				$Image->addSpotterImage($registration);
			}
			*/
			$result['decode'] = $decode;
//		}
		return $result;
	}

	/**
	* Add ACARS data
	*
	* @param String ACARS data in acarsdec data
	*
	*/
	public function add($data,$message = array()) {
		global $globalDebug, $globalACARSArchive;
		$Image = new Image($this->db);
		$Schedule = new Schedule($this->db);
		$Translation = new Translation($this->db);

		$message = array_merge($message,$this->parse($data));
		if (isset($message['registration']) && $message['registration'] != '' && $message['ident'] != '' && $message['registration'] != '!') {
			$ident = (string)$message['ident'];
			$label = $message['label'];
			$block_id = $message['block_id'];
			$msg_no = $message['msg_no'];
			$msg = $message['message'];
			$decode = $message['decode'];
			$registration = (string)$message['registration'];
			if (isset($decode['latitude'])) $latitude = $decode['latitude'];
			else $latitude = '';
			if (isset($decode['longitude'])) $longitude = $decode['longitude'];
			else $longitude = '';
			if (isset($decode['airicao'])) $airicao = $decode['airicao'];
			else $airicao = '';
			if (isset($decode['icao'])) $icao = $decode['icao'];
			else $icao = $Translation->checkTranslation($ident);
			$image_array = $Image->getSpotterImage($registration);
			if (!isset($image_array[0]['registration'])) {
				$Image->addSpotterImage($registration);
			}
			// Business jets always use GS0001
			if ($ident != 'GS0001') $info = $this->addModeSData($ident,$registration,$icao,$airicao,$latitude,$longitude);
			if ($globalDebug && isset($info) && $info != '') echo $info;
			if (count($decode) > 0) $decode_json = json_encode($decode);
			else $decode_json = '';
			if (isset($decode['Departure airport']) && isset($decode['Departure hour']) && isset($decode['Arrival airport']) && isset($decode['Arrival hour'])) {
				$Schedule->addSchedule($icao,$decode['Departure airport'],$decode['Departure hour'],$decode['Arrival airport'],$decode['Arrival hour'],'ACARS');
			} elseif (isset($decode['Departure airport']) && isset($decode['Arrival airport'])) {
				$Schedule->addSchedule($icao,$decode['Departure airport'],'',$decode['Arrival airport'],'','ACARS');
			}
			$result = $this->addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$msg,$decode_json);
			if (!isset($globalACARSArchive)) $globalACARSArchive = array('10','80','81','82','3F');
			if ($result && in_array($label,$globalACARSArchive)) $this->addArchiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$msg,$decode_json);
			if ($globalDebug && count($decode) > 0) {
				echo "Human readable data : ".implode(' - ',$decode)."\n";
			}
		}
	}

    /**
     * Add Live ACARS data in DB
     *
     * @param String $ident ident
     * @param String $registration Registration of the aircraft
     * @param String $label Label of the ACARS message
     * @param String $block_id Block id of the ACARS message
     * @param String $msg_no Number of the ACARS message
     * @param String $message ACARS message
     * @param string $decode
     * @return bool
     */
	public function addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode = '') {
		global $globalDebug;
		date_default_timezone_set('UTC');
		if ($label != 'SQ' && $label != 'Q0' && $label != '_d' && $message != '') {
			$Connection = new Connection($this->db);
			$this->db = $Connection->db;
			if ($globalDebug) echo "Test if not already in Live ACARS table...";
			$query_test = "SELECT COUNT(*) as nb FROM acars_live WHERE ident = :ident AND registration = :registration AND message = :message";
			$query_test_values = array(':ident' => $ident,':registration' => $registration, ':message' => $message);
			try {
				$stht = $this->db->prepare($query_test);
				$stht->execute($query_test_values);
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
				return false;
			}
			if ($stht->fetchColumn() == 0) {
				if ($globalDebug) echo "Add Live ACARS data...";
				$query = "INSERT INTO acars_live (ident,registration,label,block_id,msg_no,message,decode,date) VALUES (:ident,:registration,:label,:block_id,:msg_no,:message,:decode,:date)";
				$query_values = array(':ident' => $ident,':registration' => $registration, ':label' => $label,':block_id' => $block_id, ':msg_no' => $msg_no, ':message' => $message, ':decode' => $decode,':date' => date("Y-m-d H:i:s"));
				try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
					return false;
				}
			} else {
				if ($globalDebug) echo "Data already in DB...\n";
				return false;
			}
			if ($globalDebug) echo "Done\n";
			return true;
		}
		return false;
	}

    /**
     * Add Archive ACARS data in DB
     *
     * @param String $ident ident
     * @param String $registration Registration of the aircraft
     * @param String $label Label of the ACARS message
     * @param String $block_id Block id of the ACARS message
     * @param String $msg_no Number of the ACARS message
     * @param String $message ACARS message
     * @param string $decode
     * @return string
     */
	public function addArchiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode = '') {
		global $globalDebug;
		date_default_timezone_set('UTC');
		if ($label != 'SQ' && $label != 'Q0' && $label != '_d' && $message != '' && preg_match('/^MET0/',$message) === 0 && preg_match('/^ARR0/',$message) === 0 && preg_match('/^ETA/',$message) === 0 && preg_match('/^WXR/',$message) === 0 && preg_match('/^FTX01.FIC/',$message) === 0) {
			/*
				    if ($globalDebug) echo "Test if not already in Archive ACARS table...";
			    	    $query_test = "SELECT COUNT(*) as nb FROM acars_archive WHERE ident = :ident AND registration = :registration AND message = :message";
			    	    $query_test_values = array(':ident' => $ident,':registration' => $registration, ':message' => $message);
			    	    try {
			        	$stht = Connection->$db->prepare($query_test);
			            	$stht->execute($query_test_values);
			    	    } catch(PDOException $e) {
			                return "error : ".$e->getMessage();
			    	    }
				    if ($stht->fetchColumn() == 0) {
			*/
			if ($globalDebug) echo "Add Live ACARS data...";
			$query = "INSERT INTO acars_archive (ident,registration,label,block_id,msg_no,message,decode) VALUES (:ident,:registration,:label,:block_id,:msg_no,:message,:decode)";
			$query_values = array(':ident' => $ident,':registration' => $registration, ':label' => $label,':block_id' => $block_id, ':msg_no' => $msg_no, ':message' => $message, ':decode' => $decode);
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
			if ($globalDebug) echo "Done\n";
		}
		return '';
	}

	/**
	* Get Message title from label from DB
	*
	* @param String $label
	* @return String Return ACARS title
	*/
	public function getTitlefromLabel($label) {
		$Connection = new Connection($this->db);
		$this->db = $Connection->db;
		$query = "SELECT * FROM acars_label WHERE label = :label";
		$query_values = array(':label' => $label);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return '';
		}
		$row = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (count($row) > 0) return $row[0]['title'];
		else return '';
	}

	/**
	* List all Message title & label from DB
	*
	* @return array Return ACARS data in array
	*/
	public function getAllTitleLabel() {
		$query = "SELECT * FROM acars_label ORDER BY title";
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$row = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (count($row) > 0) return $row;
		else return array();
	}

	/**
	* Get Live ACARS data from DB
	*
	* @param String $ident
	* @return array Return ACARS data in array
	*/
	public function getLiveAcarsData($ident) {
		$query = "SELECT * FROM acars_live WHERE ident = :ident ORDER BY acars_live_id DESC";
		$query_values = array(':ident' => $ident);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$row = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (count($row) > 0) return $row[0];
		else return array();
	}

    /**
     * Get Latest ACARS data from DB
     *
     * @param string $limit
     * @param string $label
     * @return array Return ACARS data in array
     */
	public function getLatestAcarsData($limit = '',$label = '') {
		global $globalURL;
		$Image = new Image($this->db);
		$Spotter = new Spotter($this->db);
		$Translation = new Translation($this->db);
		date_default_timezone_set('UTC');
		$result = array();
		$limit_query = '';
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		if ($label != '') {
			$query = "SELECT * FROM acars_live WHERE label = :label ORDER BY acars_live_id DESC".$limit_query;
			$query_values = array(':label' => $label);
		} else {
			$query = "SELECT * FROM acars_live ORDER BY acars_live_id DESC".$limit_query;
			$query_values = array();
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$i = 0;
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$data = array();
			if ($row['registration'] != '') {
				$row['registration'] = str_replace('.','',$row['registration']);
				$image_array = $Image->getSpotterImage($row['registration']);
				if (count($image_array) > 0) $data = array_merge($data,array('image' => $image_array[0]['image'],'image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
				else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
			} else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
			if ($row['registration'] == '') $row['registration'] = 'NA';
			if ($row['ident'] == '') $row['ident'] = 'NA';
			$identicao = $Spotter->getAllAirlineInfo(substr($row['ident'],0,2));
			if (isset($identicao[0])) {
				if (substr($row['ident'],0,2) == 'AF') {
					if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
					else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
				} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
				$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
			} else $icao = $row['ident'];
			$icao = $Translation->checkTranslation($icao,false);
			$decode = json_decode($row['decode'],true);
			$found = false;
			if ($decode != '' && array_key_exists('Departure airport',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Departure airport']);
				if (isset($airport_info[0]['icao'])) {
					$decode['Departure airport'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
					$found = true;
				}
			}
			if ($decode != '' && array_key_exists('Arrival airport',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Arrival airport']);
				if (isset($airport_info[0]['icao'])) {
					$decode['Arrival airport'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
					$found = true;
				}
			}
			if ($decode != '' && array_key_exists('Airport/Waypoint name',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Airport/Waypoint name']);
				if (isset($airport_info[0]['icao'])) {
					$decode['Airport/Waypoint name'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
					$found = true;
				}
			}
			if ($found) $row['decode'] = json_encode($decode);
			$data = array_merge($data,array('registration' => $row['registration'],'message' => $row['message'], 'date' => $row['date'], 'ident' => $icao, 'decode' => $row['decode']));
			$result[] = $data;
			$i++;
		}
		if (isset($result)) {
			$result[0]['query_number_rows'] = $i;
			return $result;
		}
		else return array();
	}

    /**
     * Get Archive ACARS data from DB
     *
     * @param string $limit
     * @param string $label
     * @return array Return ACARS data in array
     */
	public function getArchiveAcarsData($limit = '',$label = '') {
		global $globalURL;
		$Image = new Image($this->db);
		$Spotter = new Spotter($this->db);
		$Translation = new Translation($this->db);
		date_default_timezone_set('UTC');
		$limit_query = '';
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		if ($label != '') {
			if ($label == 'undefined') {
				$query = "SELECT * FROM acars_archive WHERE label NOT IN (SELECT label FROM acars_label) ORDER BY acars_archive_id DESC".$limit_query;
				$query_values = array();
			} else {
				$query = "SELECT * FROM acars_archive WHERE label = :label ORDER BY acars_archive_id DESC".$limit_query;
				$query_values = array(':label' => $label);
			}
		} else {
			$query = "SELECT * FROM acars_archive ORDER BY acars_archive_id DESC".$limit_query;
			$query_values = array();
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$i=0;
		$result = array();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$data = array();
			if ($row['registration'] != '') {
				$row['registration'] = str_replace('.','',$row['registration']);
				$image_array = $Image->getSpotterImage($row['registration']);
				if (count($image_array) > 0) $data = array_merge($data,array('image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
				else $data = array_merge($data,array('image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
			} else $data = array_merge($data,array('image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
			$icao = '';
			if ($row['registration'] == '') $row['registration'] = 'NA';
			if ($row['ident'] == '') $row['ident'] = 'NA';
			$identicao = $Spotter->getAllAirlineInfo(substr($row['ident'],0,2));
			if (isset($identicao[0])) {
				if (substr($row['ident'],0,2) == 'AF') {
					if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
					else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
				} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
				$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
			} else $icao = $row['ident'];
			$icao = $Translation->checkTranslation($icao);
			$decode = json_decode($row['decode'],true);
			$found = false;
			if ($decode != '' && array_key_exists('Departure airport',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Departure airport']);
				if (isset($airport_info[0]['icao'])) $decode['Departure airport'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
				$found = true;
			}
			if ($decode != '' && array_key_exists('Arrival airport',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Arrival airport']);
				if (isset($airport_info[0]['icao'])) $decode['Arrival airport'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
				$found = true;
			}
			if ($decode != '' && array_key_exists('Airport/Waypoint name',$decode)) {
				$airport_info = $Spotter->getAllAirportInfo($decode['Airport/Waypoint name']);
				if (isset($airport_info[0]['icao'])) {
					$decode['Airport/Waypoint name'] = '<a href="'.$globalURL.'/airport/'.$airport_info[0]['icao'].'">'.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')</a>';
					$found = true;
				}
			}
			if ($found) $row['decode'] = json_encode($decode);
			$data = array_merge($data,array('registration' => $row['registration'],'message' => $row['message'], 'date' => $row['date'], 'ident' => $icao, 'decode' => $row['decode']));
			$result[] = $data;
			$i++;
		}
		if (!empty($result)) {
			$result[0]['query_number_rows'] = $i;
			return $result;
		} else return array();
	}

    /**
     * Add ModeS data to DB
     *
     * @param String $ident ident
     * @param String $registration Registration of the aircraft
     * @param String $icao
     * @param String $ICAOTypeCode
     * @param string $latitude
     * @param string $longitude
     * @return string
     */
	public function addModeSData($ident,$registration,$icao = '',$ICAOTypeCode = '',$latitude = '', $longitude = '') {
		global $globalDebug, $globalDBdriver;
		$ident = trim($ident);
		$Translation = new Translation($this->db);
		$Spotter = new Spotter($this->db);
		if ($globalDebug) echo "Test if we add ModeS data...";
		//if ($icao == '') $icao = ACARS->ident2icao($ident);
		if ($icao == '') $icao = $Translation->checkTranslation($ident);
		if ($globalDebug) echo '- Ident : '.$icao.' - ';
		if ($ident == '' || $registration == '') {
			if ($globalDebug) echo "Ident or registration null, exit\n";
			return '';
		}
		$registration = str_replace('.','',$registration);
		$ident = $Translation->ident2icao($ident);
		// Check if a flight with same registration is flying now, if ok check if callsign = name in ACARS, else add it to translation
		if ($globalDebug) echo "Check if needed to add translation ".$ident.'... ';
		$querysi = "SELECT ident FROM spotter_live s,aircraft_modes a WHERE a.ModeS = s.ModeS AND a.Registration = :registration AND s.format_source <> 'ACARS' LIMIT 1";
		$querysi_values = array(':registration' => $registration);
		try {
			$sthsi = $this->db->prepare($querysi);
			$sthsi->execute($querysi_values);
		} catch(PDOException $e) {
			if ($globalDebug) echo $e->getMessage();
			return "error : ".$e->getMessage();
		}
		$resultsi = $sthsi->fetch(PDO::FETCH_ASSOC);
		$sthsi->closeCursor();
		if (count($resultsi) > 0 && $resultsi['ident'] != $ident && $resultsi['ident'] != '') {
			$Translation = new Translation($this->db);
			$trans_ident = $Translation->getOperator($resultsi['ident']);
			if ($globalDebug) echo 'Add translation to table : '.$ident.' -> '.$resultsi['ident'].' ';
			if ($ident != $trans_ident) $Translation->addOperator($resultsi['ident'],$ident,'ACARS');
			elseif ($trans_ident == $ident) $Translation->updateOperator($resultsi['ident'],$ident,'ACARS');
		} else {
			if ($registration != '' && $latitude != '' && $longitude != '') {
				$query = "SELECT ModeS FROM aircraft_modes WHERE Registration = :registration LIMIT 1";
				$query_values = array(':registration' => $registration);
				try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					if ($globalDebug) echo $e->getMessage();
					return "error : ".$e->getMessage();
				}
				$result = $sth->fetch(PDO::FETCH_ASSOC);
				$sth->closeCursor();
				if (isset($result['modes'])) $hex = $result['modes'];
				else $hex = '';
				$SI_data = array('hex' => $hex,'ident' => $ident,'aircraft_icao' => $ICAOTypeCode,'registration' => $registration,'latitude' => $latitude,'$longitude' => $longitude,'format_source' => 'ACARS');
				if ($this->fromACARSscript) $this->SI->add($SI_data);
			}
		}
		if ($globalDebug) echo 'Done'."\n";
		$query = "SELECT flightaware_id, ModeS FROM spotter_output WHERE ident = :ident AND format_source <> 'ACARS' ORDER BY spotter_id DESC LIMIT 1";
		$query_values = array(':ident' => $icao);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			if ($globalDebug) echo $e->getMessage();
			return "error : ".$e->getMessage();
		}
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (isset($result['flightaware_id'])) {
			if (isset($result['ModeS'])) $ModeS = $result['ModeS'];
			else $ModeS = '';
			if ($ModeS == '') {
				$id = explode('-',$result['flightaware_id']);
				$ModeS = $id[0];
			}
			if ($ModeS != '') {
				$country = $Spotter->countryFromAircraftRegistration($registration);
				$queryc = "SELECT * FROM aircraft_modes WHERE ModeS = :modes LIMIT 1";
				$queryc_values = array(':modes' => $ModeS);
				try {
					$sthc = $this->db->prepare($queryc);
					$sthc->execute($queryc_values);
				} catch(PDOException $e) {
					if ($globalDebug) echo $e->getMessage();
					return "error : ".$e->getMessage();
				}
				$row = $sthc->fetch(PDO::FETCH_ASSOC);
				$sthc->closeCursor();
				if (count($row) ==  0) {
					if ($globalDebug) echo " Add to ModeS table - ";
					$queryi = "INSERT INTO aircraft_modes (ModeS,ModeSCountry,Registration,ICAOTypeCode,Source) VALUES (:ModeS,:ModeSCountry,:Registration, :ICAOTypeCode,'ACARS')";
					$queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode);
					try {
						$sthi = $this->db->prepare($queryi);
						$sthi->execute($queryi_values);
					} catch(PDOException $e) {
						if ($globalDebug) echo $e->getMessage();
						return "error : ".$e->getMessage();
					}
				} else {
					if ($globalDebug) echo " Update ModeS table - ";
					if ($ICAOTypeCode != '') {
						$queryi = "UPDATE aircraft_modes SET ModeSCountry = :ModeSCountry,Registration = :Registration,ICAOTypeCode = :ICAOTypeCode,Source = 'ACARS',LastModified = NOW() WHERE ModeS = :ModeS";
						$queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode);
					} else {
						$queryi = "UPDATE aircraft_modes SET ModeSCountry = :ModeSCountry,Registration = :Registration,Source = 'ACARS',LastModified = NOW() WHERE ModeS = :ModeS";
						$queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration);
					}
					try {
						$sthi = $this->db->prepare($queryi);
						$sthi->execute($queryi_values);
					} catch(PDOException $e) {
						if ($globalDebug) echo $e->getMessage();
						return "error : ".$e->getMessage();
					}
				}
				/*
				if ($globalDebug) echo " Update Spotter_live table - ";
				if ($ICAOTypeCode != '') {
				    $queryi = "UPDATE spotter_live SET registration = :Registration,aircraft_icao = :ICAOTypeCode WHERE ident = :ident";
				    $queryi_values = array(':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode, ':ident' => $icao);
				} else {
				    $queryi = "UPDATE spotter_live SET registration = :Registration WHERE ident = :ident";
				    $queryi_values = array(':Registration' => $registration,':ident' => $icao);
				}
				try {
				    $sthi = $this->db->prepare($queryi);
					    $sthi->execute($queryi_values);
				} catch(PDOException $e) {
				    if ($globalDebug) echo $e->getMessage();
					    return "error : ".$e->getMessage();
				}
				*/
				if ($globalDebug) echo " Update Spotter_output table - ";
				if ($ICAOTypeCode != '') {
					if ($globalDBdriver == 'mysql') {
						$queryi = "UPDATE spotter_output SET registration = :Registration,aircraft_icao = :ICAOTypeCode WHERE ident = :ident AND date >= date_sub(UTC_TIMESTAMP(), INTERVAL 1 HOUR)";
					} else if ($globalDBdriver == 'pgsql') {
						$queryi = "UPDATE spotter_output SET registration = :Registration,aircraft_icao = :ICAOTypeCode WHERE ident = :ident AND date >= NOW() AT TIME ZONE 'UTC' - INTERVAL '1 HOUR'";
					}
					$queryi_values = array(':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode, ':ident' => $icao);
				} else {
					if ($globalDBdriver == 'mysql') {
						$queryi = "UPDATE spotter_output SET registration = :Registration WHERE ident = :ident AND date >= date_sub(UTC_TIMESTAMP(), INTERVAL 1 HOUR)";
					}
					elseif ($globalDBdriver == 'pgsql') {
						$queryi = "UPDATE spotter_output SET registration = :Registration WHERE ident = :ident AND date >= NOW() AT TIME ZONE 'UTC' - INTERVAL '1 HOUR'";
					}
					$queryi_values = array(':Registration' => $registration,':ident' => $icao);
				}
				try {
					$sthi = $this->db->prepare($queryi);
					$sthi->execute($queryi_values);
				} catch(PDOException $e) {
					if ($globalDebug) echo $e->getMessage();
					return "error : ".$e->getMessage();
				}
			}
		} else {
			if ($globalDebug) echo " Can't find ModeS in spotter_output - ";
		}
		if ($globalDebug) echo "Done\n";
		return '';
	}
}
?>
