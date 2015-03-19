<?php
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.Image.php');
require_once('class.Scheduler.php');

class ACARS {
    /**
    * Change IATA to ICAO value for ident
    * 
    * @param String $ident ident
    * @return String the icao
    */
    public static function ident2icao($ident) {
	if (substr($ident,0,2) == 'AF') {
	    if (filter_var(substr($ident,2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $ident;
	    else $icao = 'AFR'.ltrim(substr($ident,2),'0');
	} else {
    	    $identicao = Spotter::getAllAirlineInfo(substr($ident,0,2));
    	    if (isset($identicao[0])) {
        	$icao = $identicao[0]['icao'].ltrim(substr($ident,2),'0');
    	    } else $icao = $ident;
    	}
        return $icao;
    }

    /**
    * Deletes all info in the table
    *
    * @return String success or false
    *
    */
    public static function deleteLiveAcarsData()
    {
        $query  = "DELETE FROM acars_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= acars_live.date";
        try {
            $Connection = new Connection();
            $sth = Connection::$db->prepare($query);
            $sth->execute();
        } catch(PDOException $e) {
            return "error";
        }
        return "success";
    }


    /**
    * Add ACARS data
    *
    * @param String ACARS data in acarsdec data
    *
    */
    static function add($data) {
	global $globalDebug;
//  (null) 4 09/03/2015 08:11:14 0 -41 2         ! SQ   02XA LYSLFL L104544N00505BV136975/ARINC
  //(null) 1 09/03/2015 08:10:08 0 -36 R .F-GPMD T _d 8 S65A AF6202
//    $line = sscapreg_split('/\(null\) \d 
	$n = sscanf($data,'(null) %*d %*02d/%*02d/%*04d %*02d:%*02d:%*02d %*d %*[0-9-] %*[A-Z0-9] .%6s %*c %2[0-9a-zA-Z_] %d %4[0-9A-Z] %6[0-9A-Z] %[^\r\n]',$registration,$label,$block_id,$msg_no,$ident,$message);
/*
    
	$line = explode(' ',$data,13);
	$registration = substr($line[7],1);
	$ident = $line[12];
	$label = $line[9];
	$block_id = $line[10];
	$msg_no = $line[11];
	//$message = implode(' ',array_slice($line,13));
	$message = $line[13];
*/
	$icao = '';
	$airicao = '';
	$decode = '';
	$found = false;
	if ($globalDebug) echo "Reg. : ".$registration." - Ident : ".$ident." - Label : ".$label." - Message : ".$message."\n";
	
	if ($registration != '' && $ident != '') {
	/*
Messages not yet parsed :
=========================

Reg. : G-EUXE - Ident : BA31CE - Label : H1 - Message : #CFBFLR/FR15022612180028424806FUEL QUANTITY           R ULTRACOMP 52QT/IDFUEL
Reg. : OO-DWA - Ident : SN01LY - Label : 13 - Message : EBBR,LFLL,26FEB15,1626,164626
Reg. : OO-SSF - Ident : SN088L - Label : 28 - Message : EBBR,LFSB,LFST,ELLX,
N 46.668,E  5.692,31735

Reg. : D-ABNL - Ident : AB718H - Label : 3V - Message : HB50,,
718H,27FEB15,EDDN,LEPA,.D-ABNL,        LEPA,AB7892,
6600,  3000, 200,,2, 4,






,EOF,

Reg. : F-GHQJ - Ident : AF7889 - Label : H1 - Message : #DFBA02/CCF-GHQJ,FEB27,134654,LFRS,LFMN,0889/C106,15201,4000,42,0010,0,0100,42,X/CEN285,36992,252,778,5619,315,B5B7G8/CNN282,36984,252,777,5618,315/EC731134,42384,01436,41191,12/EE731212,44929,11866,43552,12/N1
Reg. : CN-RNR - Ident : AT816C - Label : 5U - Message :   01 WXRQ   816C/27 GMMN/EDDT .CN-RNR
/TYP 1/STA EDDT/STA LSZH/STA EDDS

--Reg. : D-ABFN - Ident : AB662E - Label : 4A - Message : EDDL,EDDH,EDDV,,-SA,EOF,
Reg. : F-GPMF - Ident : AF7828 - Label : H1 - Message : #DFBA01/A31901,1,1/CCF-GPMF,FEB27,175317,LFMN,LFLL,0828/C106,18139,4000,51,0011,0,1100,51,X/CEN097,15830,220,447,4989,254,C63015/EC731855,00832,01809,00757,C3,--/EE731869,00832,07653,00757,C3/N10333,0333,0656,4

Reg. : F-GHQJ - Ident : AF6241 - Label : 80 - Message : /ORYKOAF.VER/071/A320/M
SCH/AF6241/LFMN/LFPO/27FEB/2010
FTX
TPI

Reg. : YU-API - Ident : EY0314 - Label : 2D - Message : TOD01,ASL314,LYBE,LFPG,,1828,LFPG26L

Reg. : F-GLZH - Ident : AF0669 - Label : H1 - Message : #DFBR01/A34001,1,1
C1,.F-GLZH,15MAR01,02.29.43,HDAM,LFPG,AFR669XXXX,5000,066
C2,002,06.0,000000,SE1N05,VN1005,AF2055,000,052,052
C3,N24.3,37957,0.799,111,1.03,1111,1010,0,0101,1111,1.03,-
C4,N24.3,37956,0.8

Reg. : F-GRHG - Ident : AF6040 - Label : 80 - Message : /RSYGXAF.VER/071/A319/M
SCH/AF6040/LFPO/LFML/09MAR/1715
FTX
DESOLE NOUS NE COMPREN
ONS PAS LE MESSAGE ...


*/
	
	if (!$found) {
	    // example message : "FST01EGLLLIRFN047599E0033586390  55  25- 4C 74254      487  2059194"
	    //FIX : Reg. : G-DOCF - Ident : BA2599 - Label : 15 - Message : FST01LIPXEGKKN478304E006124636000500057M057C060334309304372OA13431246

	    $n = sscanf($message, "FST01%4c%4c%c%06d%c%07d%*11[0-9a-zA-Z ]-%02dC", $dair, $darr, $lac, $la, $lnc, $ln, $temp);
    	    if ($n > 5 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
        	$latitude = $la / 1000.0;
        	$longitude = $ln / 10000.0;
        	// Temp not always available
        	if ($globalDebug) echo 'latitude : '.$latitude.' - longitude : '.$longitude.' - airport depart : '.$dair.' - airport arrival : '.$darr.' - température : '.$temp."°C\n";
        	$decode = array('Latitude' => $latitude, 'Longitude' =>  $longitude, 'Departure airport' => $dair, 'Arrival airport' => $darr, 'Temperature' => $temp);
        	$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}
	if (!$found && ($label == '13' || $label == '12')) {
	    // example message : "Reg. : OO-DWA - Ident : SN01LY - Label : 13 - Message : EBBR,LFLL,26FEB15,1626,164626"
	    /*
	    Reg. : OO-SSP - Ident : SN01LY - Label : 13 - Message : EBBR,LFLL,27FEB15,1624,164400
	    N 46.493,E  3.980,19810
	    */

	    $n = sscanf($message, "%4c,%4c,%*7s,%*d", $dair, $darr);
    	    if ($n == 4) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
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

	    $n = sscanf($message, "%*[0-9A-Z]/%*3d/%4s/%*c\nSCH/%6[0-9A-Z ]/%4c/%4c/%5s/%4d\n%*3c/%4d/%4c/%[0-9A-Z ]/", $airicao,$aident,$dair, $darr, $ddate, $dhour,$ahour, $aair, $apiste);
    	    if ($n > 8) {
    		if ($globalDebug) echo 'airicao : '. $airicao.' - ident : '.$aident.' - departure airport : '.$dair.' - arrival airport : '. $darr.' - date depart : '.$ddate.' - departure hour : '. $dhour.' - arrival hour : '.$ahour.' - arrival airport : '.$aair.' - arrival piste : '.$apiste."\n";
        	if ($dhour != '') $dhour = substr(sprintf('%04d',$dhour),0,2).':'.substr(sprintf('%04d',$dhour),2);
        	if ($ahour != '') $ahour = substr(sprintf('%04d',$ahour),0,2).':'.substr(sprintf('%04d',$ahour),2);
        	$icao = trim($aident);

        	//$decode = 'Departure airport : '.$dair.' ('.$ddate.' at '.$dhour.') - Arrival Airport : '.$aair.' (at '.$ahour.') way '.$apiste;
        	if ($ahour == '') $decode = array('Departure airport' => $dair, 'Departure date' => $ddate, 'Departure hour' => $dhour, 'Arrival airport' => $darr);
        	else $decode = array('Departure airport' => $dair, 'Departure date' => $ddate, 'Departure hour' => $dhour, 'Arrival airport' => $darr, 'Arrival hour' => $ahour, 'Arrival way' => $apiste);
        	Schedule::addSchedule($icao,$dair,$dhour,$darr,$ahour,'ACARS');
        	$found = true;
    	    }
	}
	
	if (!$found) {
    	    // example message : "221111,34985,0817,  65,N 50.056 E 13.850"
    	    //Reg. : CS-TFY - Ident : CR0321 - Label : 16 - Message : 140600,34008,1440,  66,N 46.768 E  4.793

    	    $n = sscanf($message, "%*6c,%*5c,%*4c,%*4c,%c%3d.%3d %c%3d.%3d,", $lac, $las, $lass, $lnc, $lns, $lnss);
    	    if ($n == 10 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
        	$las = $las.'.'.$lass;
        	$lns = $lns.'.'.$lns;
    	        $latitude = $las / 1000.0;
        	$longitude = $lns / 1000.0;
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
    	    $n = sscanf($message, "%*[0-9A-Z ]/%*s %4c/%4c .", $dair, $darr);
    	    if ($n == 4) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
        	$found = true;
    	    }
	}
	if (!$found && $label == '1L') {
	    // example message : "Reg. : TS-ION - Ident : TU0634 - Label : 1L - Message : 000442152001337,DTTJ,LFPO,1609"
    	    $n = sscanf($message, "%*[0-9],%4c,%4c,", $dair, $darr);
    	    if ($n == 4) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
        	$found = true;
    	    }
	}
	if (!$found && $label == '5U') {
	    // example message : "Reg. : OO-TAH - Ident : 3V042J - Label : 5U - Message : 002AF   EBLG EBBR                     N4621.5E  524.2195"
    	    $n = sscanf($message, "002AF %4c %4c ", $dair, $darr);
    	    if ($n == 2) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}

	if (!$found && $label == 'H1') {
	    // example message : 'Reg. : F-GHQJ - Ident : AF6241 - Label : H1 - Message : #DFBA01/CCF-GHQJ,FEB27,205556,LFMN,LFPO,0241/C106,17404,5000,42,0010,0,0100,42,X/CEN270,36012,257,778,6106,299,B5B7G8/EC731134,42387,01439,41194,12/EE731212,44932,11870,43555,12/N10875,0875,0910,6330,1205,-----'
    	    $n = sscanf($message, "#DFBA%*02d/%*[A-Z-],%*[0-9A-Z],%*d,%4c,%4c", $dair, $darr);
    	    if ($n == 6) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
        	$found = true;
    	    }
	}
	if (!$found && $label == 'H1') {
	    // example message : 'Reg. : F-GUGP - Ident : AF1842 - Label : H1 - Message : #DFBA01/A31801,1,1/CCF-GUGP,MAR11,093856,LFPG,LSGG,1842/C106,55832,5000,37,0010,0,0100,37,X/CEN282,31018,277,750,5515,255,C11036/EC577870,02282,07070,01987,73,14/EE577871,02282,06947,01987,73/N10790,0790,0903,5'
    	    $n = sscanf($message, "#DFBA%*02d/%*[0-9A-Z,]/%*[A-Z-],%*[0-9A-Z],%*d,%4c,%4c", $dair, $darr);
    	    if ($n == 7) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $darr);
        	$found = true;
    	    }
	}
	if (!$found) {
	    /* example message :
	     Reg. : PH-BXO - Ident : KL079K - Label : H1 - Message : #DFB(POS-KLM79K  -4319N00252E/143435 F390
RMK/FUEL   2.6 M0.79)
	    */
	    $n = sscanf($message, "#DFB(POS-%s -%4d%c%5d%c/%*d F%d\nRMK/FUEL %f M%f", $aident, $lac, $las, $lnc, $lns, $alt, $fuel, $speed);
    	    if ($n == 9) {
        	//if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
        	$icao = trim($aident);
        	$latitude = $lac / 100.0;
        	$longitude = $lnc / 100.0;

		$decode = array('Latitute' => $latitude,'Longitude' => $longitude,'Altitude' => $alt*100,'Fuel' => $fuel,'speed' => $speed);
        	$found = true;
    	    }
	}

	if (!$found && $label == '80') {
	    /* example message : 
		Reg. : EI-DSB - Ident : AZ0207 - Label : 80 - Message : 3X01 NLINFO 0207/28 EGLL/LIRF .EI-DSB
		/AZ/1783/28/FCO/N
	    */
    	    $n = sscanf($message, "%*[0-9A-Z] NLINFO %*d/%*d %4c/%4c .", $dair, $darr);
    	    if ($n == 5) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
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
    	    $n = sscanf($message, "%*[0-9A-Z],,\n%*[0-9A-Z],%*[0-9A-Z],%4s,%4s,.%*6s,\n%*4[A-Z],\n%[0-9A-Z],", $dair, $darr, $aident);
    	    if ($n == 8) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
        	$icao = trim($aident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $daar);
        	$found = true;
    	    }
	}
	if (!$found) {
	    /* example message : 
		Reg. : N702DN - Ident : DL0008 - Label : 80 - Message : 3401/11 KATL/OMDB .N702DN
		ACK RDA
	    */
	    $n = sscanf($message, "%*d/%*d %4s/%4s .%*6s", $dair, $darr);
    	    if ($n == 5) {
        	if ($globalDebug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$decode = array('Departure airport' => $dair, 'Arrival airport' => $daar);
        	$found = true;
    	    }
	}
	    echo ACARS::addModeSData($ident,$registration,$icao,$airicao);

    	    $image_array = Image::getSpotterImage($registration);
    	    if (!isset($image_array[0]['registration'])) {
    		Image::addSpotterImage($registration);
    	    }
        }
        if ($decode != '') $decode_json = json_encode($decode);
        else $decode_json = '';
	ACARS::addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode_json);
	if ($label == '10' || $label == '80' || $label == '3F') ACARS::addArchiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode_json);
	
	if ($globalDebug && $decode != '') echo "Human readable data : ".implode(' - ',$decode)."\n";
//	ACARS::addModeSData($ident,$registration,$icao,$airicao);
	//TODO: Update registration in live and in output with a script
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
    */
    public static function addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode = '') {
	global $globalDebug;
	date_default_timezone_set('UTC');
	if ($label != 'SQ' && $label != 'Q0' && $label != '_d' && $message != '') {
	    if ($globalDebug) echo "Add Live ACARS data...";
    	    $query = "INSERT INTO acars_live (`ident`,`registration`,`label`,`block_id`,`msg_no`,`message`,`decode`) VALUES (:ident,:registration,:label,:block_id,:msg_no,:message,:decode)";
    	    $query_values = array(':ident' => $ident,':registration' => $registration, ':label' => $label,':block_id' => $block_id, ':msg_no' => $msg_no, ':message' => $message, ':decode' => $decode);
    	    try {
        	$Connection = new Connection();
        	$sth = Connection::$db->prepare($query);
            	$sth->execute($query_values);
    	    } catch(PDOException $e) {
                return "error : ".$e->getMessage();
    	    }
	    if ($globalDebug) echo "Done\n";
	}
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
    */
    public static function addArchiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message,$decode = '') {
	global $globalDebug;
	date_default_timezone_set('UTC');
	if ($label != 'SQ' && $label != 'Q0' && $label != '_d' && $message != '') {
	    if ($globalDebug) echo "Add Live ACARS data...";
    	    $query = "INSERT INTO acars_archive (`ident`,`registration`,`label`,`block_id`,`msg_no`,`message`,`decode`) VALUES (:ident,:registration,:label,:block_id,:msg_no,:message,:decode)";
    	    $query_values = array(':ident' => $ident,':registration' => $registration, ':label' => $label,':block_id' => $block_id, ':msg_no' => $msg_no, ':message' => $message, ':decode' => $decode);
    	    try {
        	$Connection = new Connection();
        	$sth = Connection::$db->prepare($query);
            	$sth->execute($query_values);
    	    } catch(PDOException $e) {
                return "error : ".$e->getMessage();
    	    }
	    if ($globalDebug) echo "Done\n";
	}
    }

    /**
    * Get Live ACARS data from DB
    *
    * @param String $ident
    * @return Array Return ACARS data in array
    */
    public static function getLiveAcarsData($ident) {
	date_default_timezone_set('UTC');
    	$query = "SELECT * FROM acars_live WHERE `ident` = :ident ORDER BY acars_live_id DESC";
    	$query_values = array(':ident' => $ident);
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
            return "error : ".$e->getMessage();
    	}
    	$row = $sth->fetchAll(PDO::FETCH_ASSOC);
    	if (count($row) > 0) return $row[0];
    	else return array();
    }

    /**
    * Get Latest ACARS data from DB
    *
    * @return Array Return ACARS data in array
    */
    public static function getLatestAcarsData($limit = '') {
	date_default_timezone_set('UTC');
	
	$limit_query = '';
	if ($limit != "")
	{
	    $limit_array = explode(",", $limit);
	    
	    $limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
	    $limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
	    
	    if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
	    {
		$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
	    }
	}
	
    	//$query = "SELECT *, name as airline_name FROM acars_live a, spotter_image i, airlines l WHERE i.registration = a.registration AND l.icao = a.airline_icao AND l.icao != '' ORDER BY acars_live_id DESC LIMIT 25";
    	
    	$query = "SELECT * FROM acars_live ORDER BY acars_live_id DESC".$limit_query;
    	$query_values = array();
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
            return "error : ".$e->getMessage();
    	}
    	$i = 0;
    	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    	    $data = array();
    	    if ($row['registration'] != '') {
    	        $image_array = Image::getSpotterImage($row['registration']);
    	        if (count($image_array) > 0) $data = array_merge($data,array('image' => $image_array[0]['image'],'image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
    	        else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
    	    } else $data = array_merge($data,array('image' => '','image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
    	    $icao = '';
    	    if ($row['registration'] == '') $row['registration'] = 'NA';
    	    if ($row['ident'] == '') $row['ident'] = 'NA';
    	    $identicao = Spotter::getAllAirlineInfo(substr($row['ident'],0,2));
    	    if (isset($identicao[0])) {
        	if (substr($row['ident'],0,2) == 'AF') {
		    if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
		    else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
		} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
        	
        	$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
    	    } else $icao = $row['ident'];
    	    
    	    $decode = json_decode($row['decode'],true);
    	    $found = false;
    	    if ($decode != '' && array_key_exists('Departure airport',$decode)) {
		$airport_info = Spotter::getAllAirportInfo($decode['Departure airport']);
		$decode['Departure airport'] = $airport_info[0]['name'].' at '.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')';
		$found = true;
    	    }
    	    if ($decode != '' && array_key_exists('Arrival airport',$decode)) {
		$airport_info = Spotter::getAllAirportInfo($decode['Arrival airport']);
		$decode['Arrival airport'] = $airport_info[0]['name'].' at '.$airport_info[0]['city'].','.$airport_info[0]['country'].' ('.$airport_info[0]['icao'].')';
		$found = true;
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
    * @return Array Return ACARS data in array
    */
    public static function getArchiveAcarsData($limit = '') {
	date_default_timezone_set('UTC');

	$limit_query = '';
	if ($limit != "")
	{
	    $limit_array = explode(",", $limit);
	    
	    $limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
	    $limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
	    
	    if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
	    {
		$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
	    }
	}


    	//$query = "SELECT *, name as airline_name FROM acars_live a, spotter_image i, airlines l WHERE i.registration = a.registration AND l.icao = a.airline_icao AND l.icao != '' ORDER BY acars_live_id DESC LIMIT 25";
    	$query = "SELECT * FROM acars_archive ORDER BY acars_archive_id DESC".$limit_query;
    	$query_values = array();
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
            return "error : ".$e->getMessage();
    	}
    	$i=0;
    	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    	    $data = array();
    	    if ($row['registration'] != '') {
    	        $image_array = Image::getSpotterImage($row['registration']);
    	        if (count($image_array) > 0) $data = array_merge($data,array('image_thumbnail' => $image_array[0]['image_thumbnail'],'image_copyright' => $image_array[0]['image_copyright'],'image_source' => $image_array[0]['image_source'],'image_source_website' => $image_array[0]['image_source_website']));
    	        else $data = array_merge($data,array('image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
    	    } else $data = array_merge($data,array('image_thumbnail' => '','image_copyright' => '','image_source' => '','image_source_website' => ''));
    	    $icao = '';
    	    if ($row['registration'] == '') $row['registration'] = 'NA';
    	    if ($row['ident'] == '') $row['ident'] = 'NA';
    	    $identicao = Spotter::getAllAirlineInfo(substr($row['ident'],0,2));
    	    if (isset($identicao[0])) {
        	if (substr($row['ident'],0,2) == 'AF') {
		    if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
		    else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
		} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
        	
        	$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
    	    } else $icao = $row['ident'];
    	    
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
    * Add ModeS data to DB
    *
    * @param String $ident ident
    * @param String $registration Registration of the aircraft
    * @param String $icao
    * @param String $ICAOTypeCode
    */
    public static function addModeSData($ident,$registration,$icao = '',$ICAOTypeCode = '') {
	global $globalDebug;
	if ($globalDebug) echo "Test if we add ModeS data...";
	if ($icao == '') $icao = ACARS::ident2icao($ident);
	if ($globalDebug) echo '- Ident : '.$icao.' - ';
	if ($ident == '' || $registration == '') {
	    if ($globalDebug) echo "Ident or registration null, exit\n";
	    return '';
	}
    	$query = "SELECT flightaware_id, ModeS FROM spotter_output WHERE `ident` =  :ident ORDER BY spotter_id DESC LIMIT 1";
    	$query_values = array(':ident' => $icao);
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
    	    if ($globalDebug) echo $e->getMessage();
            return "error : ".$e->getMessage();
    	}
    	$result = $sth->fetch(PDO::FETCH_ASSOC);
    	//print_r($result);
    	if (isset($result['flightaware_id'])) {
    	    $ModeS = $result['ModeS'];
    	    if ($ModeS == '') {
    		$id = explode('-',$result['flightaware_id']);
    		$ModeS = $id[0];
    	    }
    	    if ($ModeS != '') {
    		$country = Spotter::countryFromAircraftRegistration($registration);
		$queryc = "SELECT * FROM aircraft_modes WHERE `ModeS` = :modes LIMIT 1";
    		$queryc_values = array(':modes' => $ModeS);
    		try {
    		    $Connection = new Connection();
    		    $sthc = Connection::$db->prepare($queryc);
        	    $sthc->execute($queryc_values);
    		} catch(PDOException $e) {
    		    if ($globalDebug) echo $e->getMessage();
        	    return "error : ".$e->getMessage();
    		}
    		$row = $sthc->fetch(PDO::FETCH_ASSOC);
    		
    		if (count($row) ==  0) {
    		    if ($globalDebug) echo " Add to ModeS table - ";
    		    $queryi = "INSERT INTO aircraft_modes (`ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`Source`) VALUES (:ModeS,:ModeSCountry,:Registration, :ICAOTypeCode,'ACARS')";
    		    $queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode);
    		    try {
        		$Connection = new Connection();
        		$sthi = Connection::$db->prepare($queryi);
            		$sthi->execute($queryi_values);
    		    } catch(PDOException $e) {
    			if ($globalDebug) echo $e->getMessage();
            		return "error : ".$e->getMessage();
    		    }
    		} else {
    		    if ($globalDebug) echo " Update ModeS table - ";
    		    if ($ICAOTypeCode != '') {
    			$queryi = "UPDATE aircraft_modes SET `ModeSCountry` = :ModeSCountry,`Registration` = :Registration,`ICAOTypeCode` = :ICAOTypeCode,`Source` = 'ACARS' WHERE `ModeS` = :ModeS";
    			$queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode);
    		    } else {
    			$queryi = "UPDATE aircraft_modes SET `ModeSCountry` = :ModeSCountry,`Registration` = :Registration,`Source` = 'ACARS' WHERE `ModeS` = :ModeS";
    			$queryi_values = array(':ModeS' => $ModeS,':ModeSCountry' => $country,':Registration' => $registration);
    		    }
    		    try {
        		$Connection = new Connection();
        		$sthi = Connection::$db->prepare($queryi);
            		$sthi->execute($queryi_values);
    		    } catch(PDOException $e) {
    			if ($globalDebug) echo $e->getMessage();
            		return "error : ".$e->getMessage();
    		    }
    		    
    		    // FIXME : Update Registration in Live Data & image?
    		    
    		}
    	    }
    	} else {
    		if ($globalDebug) echo " Can't find ModeS in spotter_output - ";
    	}
    	if ($globalDebug) echo "Done\n";
    }
}
?>
