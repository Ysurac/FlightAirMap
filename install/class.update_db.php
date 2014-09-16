<?php
require('../require/settings.php');
require_once('../require/class.Connection.php');

$tmp_dir = 'tmp/';
class update_db {
	public static $db_sqlite;

	public static function download($url, $file) {
		$fp = fopen($file, 'w+');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$data = curl_exec($ch);
		curl_close($ch);
	}

	public static function gunzip($in_file) {
		$buffer_size = 4096; // read 4kb at a time
		$out_file_name = str_replace('.gz', '', $in_file); 
		$file = gzopen($in_file,'rb');
		$out_file = fopen($out_file_name, 'wb'); 
		while(!gzeof($file)) {
			fwrite($out_file, gzread($file, $buffer_size));
		}  
		fclose($out_file);
		gzclose($file);
	}

	public static function unzip($in_file) {
		$path = pathinfo(realpath($in_file), PATHINFO_DIRNAME);
		$zip = new ZipArchive;
		$res = $zip->open($in_file);
		if ($res === TRUE) {
			$zip->extractTo($path);
			$zip->close();
		} else return false;
	}
	
	public static function connect_sqlite($database) {
		try {
			self::$db_sqlite = new PDO('sqlite:'.$database);
			self::$db_sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	
	public static function retrieve_route_sqlite_to_dest($database_file) {
		$query = 'TRUNCATE TABLE routes';
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

    		update_db::connect_sqlite($database_file);
		$query = 'select Route.RouteID, Route.callsign, operator.Icao AS operator_icao, FromAir.Icao AS FromAirportIcao, ToAir.Icao AS ToAirportIcao from Route inner join operator ON Route.operatorId = operator.operatorId LEFT JOIN Airport AS FromAir ON route.FromAirportId = FromAir.AirportId LEFT JOIN Airport AS ToAir ON ToAir.AirportID = route.ToAirportID';
		try {
                        $sth = update_db::$db_sqlite->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		$query_dest = 'INSERT INTO routes (`RouteID`,`CallSign`,`Operator_ICAO`,`FromAirport_ICAO`,`ToAirport_ICAO`) VALUES (:RouteID, :CallSign, :Operator_ICAO, :FromAirport_ICAO, :ToAirport_ICAO)';
		$Connection = new Connection();
		$sth_dest = Connection::$db->prepare($query_dest);
		Connection::$db->beginTransaction();
                while ($values = $sth->fetch(PDO::FETCH_ASSOC)) {
			$query_dest_values = array(':RouteID' => $values['RouteId'],':CallSign' => $values['Callsign'],':Operator_ICAO' => $values['operator_icao'],':FromAirport_ICAO' => $values['FromAirportIcao'],':ToAirport_ICAO' => $values['ToAirportIcao']);
			try {
				$sth_dest->execute($query_dest_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
                }
		Connection::$db->commit();
                return "success";
	}
	public static function retrieve_modes_sqlite_to_dest($database_file) {
		$query = 'TRUNCATE TABLE aircraft_modes';
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

    		update_db::connect_sqlite($database_file);
		$query = 'select * from Aircraft';
		try {
                        $sth = update_db::$db_sqlite->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
		
		$Connection = new Connection();
		$sth_dest = Connection::$db->prepare($query_dest);
		Connection::$db->beginTransaction();
                while ($values = $sth->fetch(PDO::FETCH_ASSOC)) {
			$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
			try {
				$sth_dest->execute($query_dest_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
                }
		Connection::$db->commit();
                return "success";
	}
	
	public static function update_all() {
		global $tmp_dir;
		update_db::download('http://www.virtualradarserver.co.uk/Files/StandingData.sqb.gz',$tmp_dir.'StandingData.sqb.gz');
		update_db::gunzip($tmp_dir.'StandingData.sqb.gz');
		update_db::retrieve_route_sqlite_to_dest($tmp_dir.'StandingData.sqb');

		update_db::download('http://pp-sqb.mantma.co.uk/basestation_latest.zip',$tmp_dir.'basestation_latest.zip');
		update_db::unzip($tmp_dir.'basestation_latest.zip');
		update_db::retrieve_modes_sqlite_to_dest($tmp_dir.'/basestation_latest/basestation.sqb');
	}
}
?>