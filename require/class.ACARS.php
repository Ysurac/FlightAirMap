<?php
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.Scheduler.php');

class ACARS {
    static $debug = true;

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
	$line = explode(' ',$data);

	$registration = substr($line[7],1);
	$ident = $line[12];
	$label = $line[9];
	$block_id = $line[10];
	$msg_no = $line[11];
	$message = implode(' ',array_slice($line,13));

	$icao = '';
	$airicao = '';
	if (self::$debug) echo "Reg. : ".$registration." - Ident : ".$ident." - Label : ".$label." - Message : ".$message."\n";
	
	$found = false;
	/*
Messages not yet parsed :
=========================

Reg. : PH-BGT - Ident : KL1413 - Label : H1 - Message : #DFB(POS-KLM1413 -4758N00637E/091901 F350
RMK/FUEL   3.9 M0.78)
Reg. : PH-BXH - Ident : KL051Y - Label : H1 - Message : #DFB(POS-KLM51Y  -4760N00639E/092607 F330
RMK/FUEL   3.8 M0.77)
Reg. : PH-BGG - Ident : KL053M - Label : H1 - Message : #DFB(POS-KLM53M  -4754N00641E/153143 F280
RMK/FUEL   3.9 M0.79)
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


*/
	
	if (!$found) {
	    // example message : "FST01EGLLLIRFN047599E0033586390  55  25- 4C 74254      487  2059194"
	    //FIX : Reg. : G-DOCF - Ident : BA2599 - Label : 15 - Message : FST01LIPXEGKKN478304E006124636000500057M057C060334309304372OA13431246

	    $n = sscanf($message, "FST01%4c%4c%c%06d%c%07d%*11[0-9a-zA-Z ]-%02dC", $dair, $darr, $lac, $la, $lnc, $ln, $temp);
    	    if ($n > 5 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
        	$latitude = $la / 1000.0;
        	$longitude = $ln / 10000.0;
        	// Temp not always available
        	if (self::$debug) echo 'latitude : '.$latitude.' - longitude : '.$longitude.' - airport depart : '.$dair.' - airport arrival : '.$darr.' - température : '.$temp."°C\n";
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
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
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

	    $n = sscanf($message, "VER/%*3d/%4s/%*c\nSCH/%6[0-9A-Z ]/%4c/%4c/%5s/%4d\n%*3c/%4d/%4c/", $icao,$aident,$dair, $darr, $ddate, $dhour,$ahour, $aair);
    	    if ($n > 7) {
    		if (self::$debug) echo 'airicao : '. $airicao.' - ident : '.$icao.' - departure airport : '.$dair.' - arrival airport : '. $darr.' - date depart : '.$ddate.' - departure hour : '. $dhour.' - arrival hour : '.$ahour.' - arrival airport : '.$aair."\n";
        	if ($dhour != '') $dhour = substr(sprintf('%04d',$dhour),0,2).':'.substr(sprintf('%04d',$dhour),2);
        	if ($ahour != '') $ahour = substr(sprintf('%04d',$ahour),0,2).':'.substr(sprintf('%04d',$ahour),2);
        	$icao = trim($icao);
        	Schedule::addSchedule($icao,$dair,$dhour,$darr,$ahour,'ACARS');
        	$found = true;
    	    }
	}
	
	if (!$found) {
    	    // example message : "221111,34985,0817,  65,N 50.056 E 13.850"
    	    $n = sscanf($message, "%*6c,%*5c,%*4c,%*4c,%c%3c.%3c %c%3c.%3c,", $lac, $las, $lass, $lnc, $lns, $lnss);
    	    if ($n == 10 && ($lac == 'N' || $lac == 'S') && ($lnc == 'E' || $lnc == 'W')) {
        	$las = $las.'.'.$lass;
        	$lns = $lns.'.'.$lns;
    	        $latitude = $las / 1000.0;
        	$longitude = $lns / 1000.0;
    	        if (self::$debug) echo 'latitude : '.$latitude.' - longitude : '.$longitude."\n";
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
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}
	if (!$found && $label == '1L') {
	    // example message : "Reg. : TS-ION - Ident : TU0634 - Label : 1L - Message : 000442152001337,DTTJ,LFPO,1609"
    	    $n = sscanf($message, "%*[0-9],%4c,%4c,", $dair, $darr);
    	    if ($n == 4) {
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}
	if (!$found && $label == '5U') {
	    // example message : "Reg. : OO-TAH - Ident : 3V042J - Label : 5U - Message : 002AF   EBLG EBBR                     N4621.5E  524.2195"
    	    $n = sscanf($message, "002AF %4c %4c ", $dair, $darr);
    	    if ($n == 2) {
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}

	if (!$found && $label == 'H1') {
	    // example message : 'Reg. : F-GHQJ - Ident : AF6241 - Label : H1 - Message : #DFBA01/CCF-GHQJ,FEB27,205556,LFMN,LFPO,0241/C106,17404,5000,42,0010,0,0100,42,X/CEN270,36012,257,778,6106,299,B5B7G8/EC731134,42387,01439,41194,12/EE731212,44932,11870,43555,12/N10875,0875,0910,6330,1205,-----'
    	    $n = sscanf($message, "#DFBA%*02d/%*[A-Z-],%*[0-9A-Z],%*d,%4c,%4c", $dair, $darr);
    	    if ($n == 6) {
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
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
        	if (self::$debug) echo 'airport depart : '.$dair.' - airport arrival : '.$darr."\n";
		$icao = ACARS::ident2icao($ident);
        	Schedule::addSchedule($icao,$dair,'',$darr,'','ACARS');
        	$found = true;
    	    }
	}

	ACARS::addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message);
	ACARS::addModeSData($ident,$registration,$icao,$airicao);
	//TODO: Update registration in live and in output with a script
    }
    
    /**
    * Add ACARS data in DB
    *
    * @param String $ident ident
    * @param String $registration Registration of the aircraft
    * @param String $label Label of the ACARS message
    * @param String $block_id Block id of the ACARS message
    * @param String $msg_no Number of the ACARS message
    * @param String $message ACARS message
    */
    public static function addLiveAcarsData($ident,$registration,$label,$block_id,$msg_no,$message) {
	date_default_timezone_set('UTC');
	if ($label != 'Q0' && $label != '_d' && $message != '') {
	    if (self::$debug) echo "Add Live ACARS data...";
    	    $query = "INSERT INTO acars_live (`ident`,`registration`,`label`,`block_id`,`msg_no`,`message`) VALUES (:ident,:registration,:label,:block_id,:msg_no,:message)";
    	    $query_values = array(':ident' => $ident,':registration' => $registration, ':label' => $label,':block_id' => $block_id, ':msg_no' => $msg_no, ':message' => $message);
    	    try {
        	$Connection = new Connection();
        	$sth = Connection::$db->prepare($query);
            	$sth->execute($query_values);
    	    } catch(PDOException $e) {
                return "error : ".$e->getMessage();
    	    }
	    if (self::$debug) echo "Done\n";
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
    public static function getLatestAcarsData() {
	date_default_timezone_set('UTC');
    	//$query = "SELECT *, name as airline_name FROM acars_live a, spotter_image i, airlines l WHERE i.registration = a.registration AND l.icao = a.airline_icao AND l.icao != '' ORDER BY acars_live_id DESC LIMIT 25";
    	$query = "SELECT * FROM acars_live ORDER BY acars_live_id DESC LIMIT 25";
    	$query_values = array();
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
            return "error : ".$e->getMessage();
    	}
    	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    	    $data = array();
    	    if ($row['registration'] != '') {
    	        $image_array = Spotter::getSpotterImage($row['registration']);
    	        if (count($image_array) > 0) $data = array_merge($data,array('image_thumbnail' => $image_array[0]['image_thumbnail']));
    	        else $data = array_merge($data,array('image_thumbnail' => ''));
    	    }
    	    $icao = '';
    	    if ($row['ident'] == '') $row['ident'] = 'N/A';
    	    $identicao = Spotter::getAllAirlineInfo(substr($row['ident'],0,2));
    	    if (isset($identicao[0])) {
        	if (substr($row['ident'],0,2) == 'AF') {
		    if (filter_var(substr($row['ident'],2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $row['ident'];
		    else $icao = 'AFR'.ltrim(substr($row['ident'],2),'0');
		} else $icao = $identicao[0]['icao'].ltrim(substr($row['ident'],2),'0');
        	
        	$data = array_merge($data,array('airline_icao' => $identicao[0]['icao'],'airline_name' => $identicao[0]['name']));
    	    } else $icao = $row['ident'];
    	    
    	    $data = array_merge($data,array('registration' => $row['registration'],'message' => $row['message'], 'date' => $row['date'], 'ident' => $icao));
    	    $result[] = $data;
    	}
    	if (isset($result)) return $result;
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
	if (self::$debug) echo "Test if we add ModeS data...";
	if ($icao == '') $icao = ACARS::ident2icao($ident);
	if (self::$debug) echo '- '.$icao.' - ';
	if ($ident == '') exit;
    	$query = "SELECT flightaware_id, ModeS FROM spotter_output WHERE `ident` =  :ident LIMIT 1";
    	$query_values = array(':ident' => $icao);
    	try {
    	    $Connection = new Connection();
    	    $sth = Connection::$db->prepare($query);
            $sth->execute($query_values);
    	} catch(PDOException $e) {
            return "error : ".$e->getMessage();
    	}
    	$result = $sth->fetch(PDO::FETCH_ASSOC);
    	if (isset($result['ModeS'])) {
    	    $ModeS = $result['ModeS'];
    	    if ($ModeS == '') {
    		$id = explode('-',$result['flightaware_id']);
    		$ModeS = $id[0];
    	    }
    	    if ($ModeS != '') {
    		$country = Spotter::countryFromAircraftRegistration($registration);
    	        if ($ICAOTypeCode != '') {
		    $queryc = "SELECT COUNT(*) FROM aircraft_modes WHERE `ModeS` = :modes AND `Source` = 'ACARS' AND `ICAOTypeCode` = :ICAOTypeCode";
    		    $queryc_values = array(':modes' => $ModeS, ':ICAOTypeCode' => $ICAOTypeCode);
    		} else {
		    $queryc = "SELECT COUNT(*) FROM aircraft_modes WHERE `ModeS` = :modes AND `Source` = 'ACARS'";
    		    $queryc_values = array(':modes' => $ModeS);
    		}
    		try {
    		    $Connection = new Connection();
    		    $sthc = Connection::$db->prepare($queryc);
        	    $sthc->execute($queryc_values);
    		} catch(PDOException $e) {
        	    return "error : ".$e->getMessage();
    		}
    		if ($sthc->fetchColumn() == 0) {
    		    if (self::$debug) echo "\nAdd !!!\n";
    		    $queryi = "INSERT INTO aircraft_modes (`ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`Source`) VALUES (:ModeS,:ModeSCountry,:Registration, :ICAOTypeCode,'ACARS')";
    		    $queryi_values = array(':ModeS' => $ModeS,'ModeSCountry' => $country,':Registration' => $registration, ':ICAOTypeCode' => $ICAOTypeCode);
    		    try {
        		$Connection = new Connection();
        		$sthi = Connection::$db->prepare($queryi);
            		$sthi->execute($queryi_values);
    		    } catch(PDOException $e) {
            		return "error : ".$e->getMessage();
    		    }
    		    if ($ICAOTypeCode != '') {
    			$queryd = "DELETE FROM aircraft_modes WHERE `ModeS` = :ModeS AND (`ICAOTypeCode` != :ICAOTypeCode OR `Source` != 'ACARS')";
    			$queryd_values = array(':ModeS' => $ModeS, ':ICAOTypeCode' => $ICAOTypeCode);
    		    } else {
    			$queryd = "DELETE FROM aircraft_modes WHERE `ModeS` = :ModeS AND `Source` != 'ACARS'";
    			$queryd_values = array(':ModeS' => $ModeS);
    		    }
    		    try {
        		$Connection = new Connection();
        		$sthd = Connection::$db->prepare($queryd);
            		$sthd->execute($queryd_values);
    		    } catch(PDOException $e) {
            		return "error : ".$e->getMessage();
    		    }
    		    
    		    // FIXME : Update Registration in Live Data & image?
    		    
    		}
    	    }
    	}
    	if (self::$debug) echo "Done\n";
    }
}
?>
