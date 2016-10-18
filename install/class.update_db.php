<?php
//require_once('libs/simple_html_dom.php');
require(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');

$tmp_dir = dirname(__FILE__).'/tmp/';
//$globalDebug = true;
//$globalTransaction = true;
class update_db {
	public static $db_sqlite;

	public static function download($url, $file, $referer = '') {
		$fp = fopen($file, 'w+');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if ($referer != '') curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}

	public static function gunzip($in_file,$out_file_name = '') {
		//echo $in_file.' -> '.$out_file_name."\n";
		$buffer_size = 4096; // read 4kb at a time
		if ($out_file_name == '') $out_file_name = str_replace('.gz', '', $in_file); 
		if ($in_file != '' && file_exists($in_file)) {
			// PHP version of Ubuntu use gzopen64 instead of gzopen
			if (function_exists('gzopen')) $file = gzopen($in_file,'rb');
			elseif (function_exists('gzopen64')) $file = gzopen64($in_file,'rb');
			else {
				echo 'gzopen not available';
				die;
			}
			$out_file = fopen($out_file_name, 'wb'); 
			while(!gzeof($file)) {
				fwrite($out_file, gzread($file, $buffer_size));
			}  
			fclose($out_file);
			gzclose($file);
		}
	}

	public static function unzip($in_file) {
		if ($in_file != '' && file_exists($in_file)) {
			$path = pathinfo(realpath($in_file), PATHINFO_DIRNAME);
			$zip = new ZipArchive;
			$res = $zip->open($in_file);
			if ($res === TRUE) {
				$zip->extractTo($path);
				$zip->close();
			} else return false;
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
		global $globalDebug, $globalTransaction;
		//$query = 'TRUNCATE TABLE routes';
		if ($globalDebug) echo " - Delete previous routes from DB -";
		$query = "DELETE FROM routes WHERE Source = '' OR Source = :source";
		$Connection = new Connection();
		try {
			//$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

    		if ($globalDebug) echo " - Add routes to DB -";
    		update_db::connect_sqlite($database_file);
		//$query = 'select Route.RouteID, Route.callsign, operator.Icao AS operator_icao, FromAir.Icao AS FromAirportIcao, ToAir.Icao AS ToAirportIcao from Route inner join operator ON Route.operatorId = operator.operatorId LEFT JOIN Airport AS FromAir ON route.FromAirportId = FromAir.AirportId LEFT JOIN Airport AS ToAir ON ToAir.AirportID = route.ToAirportID';
		$query = "select Route.RouteID, Route.callsign, operator.Icao AS operator_icao, FromAir.Icao AS FromAirportIcao, ToAir.Icao AS ToAirportIcao, rstp.allstop AS AllStop from Route inner join operator ON Route.operatorId = operator.operatorId LEFT JOIN Airport AS FromAir ON route.FromAirportId = FromAir.AirportId LEFT JOIN Airport AS ToAir ON ToAir.AirportID = route.ToAirportID LEFT JOIN (select RouteId,GROUP_CONCAT(icao,' ') as allstop from routestop left join Airport as air ON routestop.AirportId = air.AirportID group by RouteID) AS rstp ON Route.RouteID = rstp.RouteID";
		try {
                        $sth = update_db::$db_sqlite->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		//$query_dest = 'INSERT INTO routes (`RouteID`,`CallSign`,`Operator_ICAO`,`FromAirport_ICAO`,`ToAirport_ICAO`,`RouteStop`,`Source`) VALUES (:RouteID, :CallSign, :Operator_ICAO, :FromAirport_ICAO, :ToAirport_ICAO, :routestop, :source)';
		$query_dest = 'INSERT INTO routes (CallSign,Operator_ICAO,FromAirport_ICAO,ToAirport_ICAO,RouteStop,Source) VALUES (:CallSign, :Operator_ICAO, :FromAirport_ICAO, :ToAirport_ICAO, :routestop, :source)';
		$Connection = new Connection();
		$sth_dest = $Connection->db->prepare($query_dest);
		try {
			if ($globalTransaction) $Connection->db->beginTransaction();
            		while ($values = $sth->fetch(PDO::FETCH_ASSOC)) {
				//$query_dest_values = array(':RouteID' => $values['RouteId'],':CallSign' => $values['Callsign'],':Operator_ICAO' => $values['operator_icao'],':FromAirport_ICAO' => $values['FromAirportIcao'],':ToAirport_ICAO' => $values['ToAirportIcao'],':routestop' => $values['AllStop'],':source' => $database_file);
				$query_dest_values = array(':CallSign' => $values['Callsign'],':Operator_ICAO' => $values['operator_icao'],':FromAirport_ICAO' => $values['FromAirportIcao'],':ToAirport_ICAO' => $values['ToAirportIcao'],':routestop' => $values['AllStop'],':source' => $database_file);
				$sth_dest->execute($query_dest_values);
            		}
			if ($globalTransaction) $Connection->db->commit();
		} catch(PDOException $e) {
			if ($globalTransaction) $Connection->db->rollBack(); 
			return "error : ".$e->getMessage();
		}
                return '';
	}
	public static function retrieve_route_oneworld($database_file) {
		global $globalDebug, $globalTransaction;
		//$query = 'TRUNCATE TABLE routes';
		if ($globalDebug) echo " - Delete previous routes from DB -";
		$query = "DELETE FROM routes WHERE Source = '' OR Source = :source";
		$Connection = new Connection();
		try {
			//$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'oneworld'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

    		if ($globalDebug) echo " - Add routes to DB -";
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		$Spotter = new Spotter();
		if ($fh = fopen($database_file,"r")) {
			$query_dest = 'INSERT INTO routes (CallSign,Operator_ICAO,FromAirport_ICAO,FromAirport_Time,ToAirport_ICAO,ToAirport_Time,RouteStop,Source) VALUES (:CallSign, :Operator_ICAO, :FromAirport_ICAO,:FromAirport_Time, :ToAirport_ICAO, :ToAirport_Time,:routestop, :source)';
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (!feof($fh)) {
				$line = fgetcsv($fh,9999,',');
				if ($line[0] != '') {
					if (($line[2] == '-' || ($line[2] != '-' && (strtotime($line[2]) > time()))) && ($line[3] == '-' || ($line[3] != '-' && (strtotime($line[3]) < time())))) {
						try {
							$query_dest_values = array(':CallSign' => str_replace('*','',$line[7]),':Operator_ICAO' => '',':FromAirport_ICAO' => $Spotter->getAirportICAO($line[0]),':FromAirport_Time' => $line[5],':ToAirport_ICAO' => $Spotter->getAirportICAO($line[1]),':ToAirport_Time' => $line[6],':routestop' => '',':source' => 'oneworld');
							$sth_dest->execute($query_dest_values);
						} catch(PDOException $e) {
							if ($globalTransaction) $Connection->db->rollBack(); 
							return "error : ".$e->getMessage();
						}
					}
				}
			}
			if ($globalTransaction) $Connection->db->commit();
		}
                return '';
	}
	
	public static function retrieve_route_skyteam($database_file) {
		global $globalDebug, $globalTransaction;
		//$query = 'TRUNCATE TABLE routes';
		if ($globalDebug) echo " - Delete previous routes from DB -";
		$query = "DELETE FROM routes WHERE Source = '' OR Source = :source";
		$Connection = new Connection();
		try {
			//$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'skyteam'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

    		if ($globalDebug) echo " - Add routes to DB -";

		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		$Spotter = new Spotter();
		if ($fh = fopen($database_file,"r")) {
			$query_dest = 'INSERT INTO routes (CallSign,Operator_ICAO,FromAirport_ICAO,FromAirport_Time,ToAirport_ICAO,ToAirport_Time,RouteStop,Source) VALUES (:CallSign, :Operator_ICAO, :FromAirport_ICAO,:FromAirport_Time, :ToAirport_ICAO, :ToAirport_Time,:routestop, :source)';
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			try {
				if ($globalTransaction) $Connection->db->beginTransaction();
				while (!feof($fh)) {
					$line = fgetcsv($fh,9999,',');
					if ($line[0] != '') {
						//$datebe = explode('  -  ',$line[2]);
						//if (strtotime($datebe[0]) > time() && strtotime($datebe[1]) < time()) {
							$query_dest_values = array(':CallSign' => str_replace('*','',$line[6]),':Operator_ICAO' => '',':FromAirport_ICAO' => $Spotter->getAirportICAO($line[0]),':FromAirport_Time' => $line[4],':ToAirport_ICAO' => $Spotter->getAirportICAO($line[1]),':ToAirport_Time' => $line[5],':routestop' => '',':source' => 'skyteam');
							$sth_dest->execute($query_dest_values);
						//}
					}
				}
				if ($globalTransaction) $Connection->db->commit();
			} catch(PDOException $e) {
				if ($globalTransaction) $Connection->db->rollBack(); 
				return "error : ".$e->getMessage();
			}
		}
                return '';
	}
	public static function retrieve_modes_sqlite_to_dest($database_file) {
		global $globalTransaction;
		//$query = 'TRUNCATE TABLE aircraft_modes';
		$query = "DELETE FROM aircraft_modes WHERE Source = '' OR Source IS NULL OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		$query = "DELETE FROM aircraft_owner WHERE Source = '' OR Source IS NULL OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
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
		//$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
		$query_dest = 'INSERT INTO aircraft_modes (LastModified, ModeS,ModeSCountry,Registration,ICAOTypeCode,type_flight,Source) VALUES (:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:type,:source)';
		
		$query_dest_owner = 'INSERT INTO aircraft_owner (registration,owner,Source) VALUES (:registration,:owner,:source)';
		
		$Connection = new Connection();
		$sth_dest = $Connection->db->prepare($query_dest);
		$sth_dest_owner = $Connection->db->prepare($query_dest_owner);
		try {
			if ($globalTransaction) $Connection->db->beginTransaction();
            		while ($values = $sth->fetch(PDO::FETCH_ASSOC)) {
			//$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
				if ($values['UserString4'] == 'M') $type = 'military';
				else $type = null;
				$query_dest_values = array(':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':source' => $database_file,':type' => $type);
				$sth_dest->execute($query_dest_values);
				if ($values['RegisteredOwners'] != '' && $values['RegisteredOwners'] != NULL && $values['RegisteredOwners'] != 'Private') {
				    $query_dest_owner_values = array(':registration' => $values['Registration'],':source' => $database_file,':owner' => $values['RegisteredOwners']);
				    $sth_dest_owner->execute($query_dest_owner_values);
				}
            		}
			if ($globalTransaction) $Connection->db->commit();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}

		$query = "DELETE FROM aircraft_modes WHERE Source = :source AND ModeS IN (SELECT * FROM (SELECT ModeS FROM aircraft_modes WHERE Source = 'ACARS') _alias)";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		return '';
	}

	public static function retrieve_modes_flarmnet($database_file) {
		global $globalTransaction;
		$Common = new Common();
		//$query = 'TRUNCATE TABLE aircraft_modes';
		$query = "DELETE FROM aircraft_modes WHERE Source = '' OR Source IS NULL OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		
		if ($fh = fopen($database_file,"r")) {
			//$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
			$query_dest = 'INSERT INTO aircraft_modes (ModeS,Registration,ICAOTypeCode,Source) VALUES (:ModeS,:Registration,:ICAOTypeCode,:source)';
		
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			try {
				if ($globalTransaction) $Connection->db->beginTransaction();
            			while (!feof($fh)) {
            				$values = array();
            				$line = $Common->hex2str(fgets($fh,9999));
					//FFFFFF                     RIDEAU VALLEY SOARINGASW-20               C-FBKN MZ 123.400
            				$values['ModeS'] = substr($line,0,6);
            				$values['Registration'] = trim(substr($line,69,6));
            				$aircraft_name = trim(substr($line,48,6));
            				// Check if we can find ICAO, else set it to GLID
            				$aircraft_name_split = explode(' ',$aircraft_name);
            				$search_more = '';
            				if (count($aircraft_name) > 1 && strlen($aircraft_name_split[1]) > 3) $search_more .= " AND LIKE '%".$aircraft_name_split[0]."%'";
            				$query_search = "SELECT * FROM aircraft WHERE type LIKE '%".$aircraft_name."%'".$search_more;
            				$sth_search = $Connection->db->prepare($query_search);
					try {
                                    		$sth_search->execute();
	            				$result = $sth_search->fetch(PDO::FETCH_ASSOC);
	            				//if (count($result) > 0) {
	            				if (isset($result['icao']) && $result['icao'] != '') {
	            				    $values['ICAOTypeCode'] = $result['icao'];
	            				} 
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					if (!isset($values['ICAOTypeCode'])) $values['ICAOTypeCode'] = 'GLID';
					// Add data to db
					if ($values['ModeS'] != '' && $values['Registration'] != '' && $values['Registration'] != '0000') {
						//$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
						$query_dest_values = array(':ModeS' => $values['ModeS'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':source' => $database_file);
						//print_r($query_dest_values);
						$sth_dest->execute($query_dest_values);
					}
				}
				if ($globalTransaction) $Connection->db->commit();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}

		$query = "DELETE FROM aircraft_modes WHERE Source = :source AND ModeS IN (SELECT * FROM (SELECT ModeS FROM aircraft_modes WHERE Source = 'ACARS') _alias)";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		return '';
	}

	public static function retrieve_modes_ogn($database_file) {
		global $globalTransaction;
		//$query = 'TRUNCATE TABLE aircraft_modes';
		$query = "DELETE FROM aircraft_modes WHERE Source = '' OR Source IS NULL OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		
		if ($fh = fopen($database_file,"r")) {
			//$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
			$query_dest = 'INSERT INTO aircraft_modes (ModeS,Registration,ICAOTypeCode,Source) VALUES (:ModeS,:Registration,:ICAOTypeCode,:source)';
		
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			try {
				if ($globalTransaction) $Connection->db->beginTransaction();
				$tmp = fgetcsv($fh,9999,',',"'");
            			while (!feof($fh)) {
            				$line = fgetcsv($fh,9999,',',"'");
            				
					//FFFFFF                     RIDEAU VALLEY SOARINGASW-20               C-FBKN MZ 123.400
					//print_r($line);
            				$values['ModeS'] = $line[1];
            				$values['Registration'] = $line[3];
            				$aircraft_name = $line[2];
            				// Check if we can find ICAO, else set it to GLID
            				$aircraft_name_split = explode(' ',$aircraft_name);
            				$search_more = '';
            				if (count($aircraft_name) > 1 && strlen($aircraft_name_split[1]) > 3) $search_more .= " AND LIKE '%".$aircraft_name_split[0]."%'";
            				$query_search = "SELECT * FROM aircraft WHERE type LIKE '%".$aircraft_name."%'".$search_more;
            				$sth_search = $Connection->db->prepare($query_search);
					try {
                                    		$sth_search->execute();
	            				$result = $sth_search->fetch(PDO::FETCH_ASSOC);
	            				if (isset($result['icao']) && $result['icao'] != '') $values['ICAOTypeCode'] = $result['icao'];
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					//if (!isset($values['ICAOTypeCode'])) $values['ICAOTypeCode'] = 'GLID';
					// Add data to db
					if ($values['ModeS'] != '' && $values['Registration'] != '' && $values['Registration'] != '0000' && $values['ICAOTypeCode'] != '') {
						//$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
						$query_dest_values = array(':ModeS' => $values['ModeS'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':source' => $database_file);
						//print_r($query_dest_values);
						$sth_dest->execute($query_dest_values);
					}
				}
				if ($globalTransaction) $Connection->db->commit();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}

		$query = "DELETE FROM aircraft_modes WHERE Source = :source AND ModeS IN (SELECT * FROM (SELECT ModeS FROM aircraft_modes WHERE Source = 'ACARS') _alias)";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		return '';
	}

	public static function retrieve_owner($database_file,$country = 'F') {
		global $globalTransaction;
		//$query = 'TRUNCATE TABLE aircraft_modes';
		$query = "DELETE FROM aircraft_owner WHERE Source = '' OR Source IS NULL OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		
		if ($fh = fopen($database_file,"r")) {
			//$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
			$query_dest = 'INSERT INTO aircraft_owner (registration,base,owner,date_first_reg,Source) VALUES (:registration,:base,:owner,:date_first_reg,:source)';
		
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			try {
				if ($globalTransaction) $Connection->db->beginTransaction();
				$tmp = fgetcsv($fh,9999,',','"');
            			while (!feof($fh)) {
            				$line = fgetcsv($fh,9999,',','"');
            				$values = array();
            				//print_r($line);
            				if ($country == 'F') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = $line[4];
            				    $values['owner'] = $line[5];
            				    if ($line[6] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[6]));
					    $values['cancel'] = $line[7];
					} elseif ($country == 'EI') {
					    // TODO : add modeS & reg to aircraft_modes
            				    $values['registration'] = $line[0];
            				    $values['base'] = $line[3];
            				    $values['owner'] = $line[2];
            				    if ($line[1] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[1]));
					    $values['cancel'] = '';
					} elseif ($country == 'HB') {
					    // TODO : add modeS & reg to aircraft_modes
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[5];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					} elseif ($country == 'OK') {
					    // TODO : add modeS & reg to aircraft_modes
            				    $values['registration'] = $line[3];
            				    $values['base'] = null;
            				    $values['owner'] = $line[5];
            				    if ($line[18] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[18]));
					    $values['cancel'] = '';
					} elseif ($country == 'VH') {
					    // TODO : add modeS & reg to aircraft_modes
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[12];
            				    if ($line[28] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[28]));

					    $values['cancel'] = $line[39];
					} elseif ($country == 'OE' || $country == '9A' || $country == 'VP' || $country == 'LX' || $country == 'P2' || $country == 'HC') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[4];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					} elseif ($country == 'CC') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[6];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					} elseif ($country == 'HJ') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[8];
            				    if ($line[7] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[7]));
					    $values['cancel'] = '';
					} elseif ($country == 'PP') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[4];
            				    if ($line[6] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[6]));
					    $values['cancel'] = $line[7];
					} elseif ($country == 'E7') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[4];
            				    if ($line[5] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[5]));
					    $values['cancel'] = '';
					} elseif ($country == '8Q') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[3];
            				    if ($line[7] == '') $values['date_first_reg'] = null;
					    else $values['date_first_reg'] = date("Y-m-d",strtotime($line[7]));
					    $values['cancel'] = '';
					} elseif ($country == 'ZK' || $country == 'OM' || $country == 'TF') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[3];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					}
					if ($values['cancel'] == '' && $values['registration'] != null) {
						$query_dest_values = array(':registration' => $values['registration'],':base' => $values['base'],':date_first_reg' => $values['date_first_reg'],':owner' => $values['owner'],':source' => $database_file);
						$sth_dest->execute($query_dest_values);
					}
				}
				if ($globalTransaction) $Connection->db->commit();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		return '';
	}

	/*
	* This function is used to create a list of airports. Sources : Wikipedia, ourairports.com ans partow.net
	*/
	public static function update_airports() {
		global $tmp_dir, $globalTransaction, $globalDebug;

		require_once(dirname(__FILE__).'/libs/sparqllib.php');
		$db = sparql_connect('http://dbpedia.org/sparql');
		$query = '
		    PREFIX dbo: <http://dbpedia.org/ontology/>
		    PREFIX dbp: <http://dbpedia.org/property/>
		    PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		    PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
		    SELECT ?name ?icao ?iata ?faa ?lid ?latitude ?longitude ?airport ?homepage ?type ?country ?country_bis ?altitude ?image
		    FROM <http://dbpedia.org>
		    WHERE {
			?airport rdf:type <http://dbpedia.org/ontology/Airport> .

			OPTIONAL {
			    ?airport dbo:icaoLocationIdentifier ?icao .
			    FILTER regex(?icao, "^[A-Z0-9]{4}$")
			}

			OPTIONAL {
			    ?airport dbo:iataLocationIdentifier ?iata .
			    FILTER regex(?iata, "^[A-Z0-9]{3}$")
			}

			OPTIONAL {
			    ?airport dbo:locationIdentifier ?lid .
			    FILTER regex(?lid, "^[A-Z0-9]{4}$")
			    FILTER (!bound(?icao) || (bound(?icao) && (?icao != ?lid)))
			    OPTIONAL {
				?airport_y rdf:type <http://dbpedia.org/ontology/Airport> .
				?airport_y dbo:icaoLocationIdentifier ?other_icao .
				FILTER (bound(?lid) && (?airport_y != ?airport && ?lid = ?other_icao))
			    }
			    FILTER (!bound(?other_icao))
			}

			OPTIONAL {
			    ?airport dbo:faaLocationIdentifier ?faa .
			    FILTER regex(?faa, "^[A-Z0-9]{3}$")
			    FILTER (!bound(?iata) || (bound(?iata) && (?iata != ?faa)))
			    OPTIONAL {
				?airport_x rdf:type <http://dbpedia.org/ontology/Airport> .
				?airport_x dbo:iataLocationIdentifier ?other_iata .
				FILTER (bound(?faa) && (?airport_x != ?airport && ?faa = ?other_iata))
			    }
			    FILTER (!bound(?other_iata))
			}

			FILTER (bound(?icao) || bound(?iata) || bound(?faa) || bound(?lid))
	
			OPTIONAL {
			    ?airport rdfs:label ?name
			    FILTER (lang(?name) = "en")
			}
    
			OPTIONAL {
			    ?airport foaf:homepage ?homepage
			}
		    
			OPTIONAL {
			    ?airport dbp:coordinatesRegion ?country
			}
    
			OPTIONAL {
			    ?airport dbp:type ?type
			}
			
			OPTIONAL {
			    ?airport dbo:elevation ?altitude
			}
			OPTIONAL {
			    ?airport dbp:image ?image
			}

			{
			    ?airport geo:lat ?latitude .
			    ?airport geo:long ?longitude .
			    FILTER (datatype(?latitude) = xsd:float)
			    FILTER (datatype(?longitude) = xsd:float)
			} UNION {
			    ?airport geo:lat ?latitude .
			    ?airport geo:long ?longitude .
			    FILTER (datatype(?latitude) = xsd:double)
			    FILTER (datatype(?longitude) = xsd:double)
			    OPTIONAL {
				?airport geo:lat ?lat_f .
				?airport geo:long ?long_f .
				FILTER (datatype(?lat_f) = xsd:float)
				FILTER (datatype(?long_f) = xsd:float)
			    }
			    FILTER (!bound(?lat_f) && !bound(?long_f))
			}

		    }
		    ORDER BY ?airport
		';
		$result = sparql_query($query);
  
		$query = 'TRUNCATE TABLE airport';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }


		$query = 'ALTER TABLE airport DROP INDEX icaoidx';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$query_dest = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`,`image_thumb`,`image`)
		    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image_thumb, :image)";
		$Connection = new Connection();
		$sth_dest = $Connection->db->prepare($query_dest);
		if ($globalTransaction) $Connection->db->beginTransaction();
  
		$i = 0;
		while($row = sparql_fetch_array($result))
		{
			if ($i >= 1) {
			//print_r($row);
			if (!isset($row['iata'])) $row['iata'] = '';
			if (!isset($row['icao'])) $row['icao'] = '';
			if (!isset($row['type'])) $row['type'] = '';
			if (!isset($row['altitude'])) $row['altitude'] = '';
			if (isset($row['city_bis'])) {
				$row['city'] = $row['city_bis'];
			}
			if (!isset($row['city'])) $row['city'] = '';
			if (!isset($row['country'])) $row['country'] = '';
			if (!isset($row['homepage'])) $row['homepage'] = '';
			if (!isset($row['wikipedia_page'])) $row['wikipedia_page'] = '';
			if (!isset($row['name'])) continue;
			if (!isset($row['image'])) {
				$row['image'] = '';
				$row['image_thumb'] = '';
			} else {
				$image = str_replace(' ','_',$row['image']);
				$digest = md5($image);
				$folder = $digest[0] . '/' . $digest[0] . $digest[1] . '/' . $image . '/220px-' . $image;
				$row['image_thumb'] = 'http://upload.wikimedia.org/wikipedia/commons/thumb/' . $folder;
				$folder = $digest[0] . '/' . $digest[0] . $digest[1] . '/' . $image;
				$row['image'] = 'http://upload.wikimedia.org/wikipedia/commons/' . $folder;
			}
			
			$country = explode('-',$row['country']);
			$row['country'] = $country[0];
			
			$row['type'] = trim($row['type']);
			if ($row['type'] == 'Military: Naval Auxiliary Air Station' || $row['type'] == 'http://dbpedia.org/resource/Naval_air_station' || $row['type'] == 'Military: Naval Air Station' || $row['type'] == 'Military Northern Fleet' || $row['type'] == 'Military and industrial' || $row['type'] == 'Military: Royal Air Force station' || $row['type'] == 'http://dbpedia.org/resource/Military_airbase' || $row['type'] == 'Military: Naval air station' || preg_match('/air base/i',$row['name'])) {
				$row['type'] = 'Military';
			} elseif ($row['type'] == 'http://dbpedia.org/resource/Airport' || $row['type'] == 'Civil' || $row['type'] == 'Public use' || $row['type'] == 'Public' || $row['type'] == 'http://dbpedia.org/resource/Civilian' || $row['type'] == 'Public, Civilian' || $row['type'] == 'Public / Military' || $row['type'] == 'Private & Civilian' || $row['type'] == 'Civilian and Military' || $row['type'] == 'Public/military' || $row['type'] == 'Active With Few Facilities' || $row['type'] == '?ivilian' || $row['type'] == 'Civil/Military' || $row['type'] == 'NA' || $row['type'] == 'Public/Military') {
				$row['type'] = 'small_airport';
			}
			
			$row['city'] = urldecode(str_replace('_',' ',str_replace('http://dbpedia.org/resource/','',$row['city'])));
			$query_dest_values = array(':airport_id' => $i, ':name' => $row['name'],':iata' => $row['iata'],':icao' => $row['icao'],':latitude' => $row['latitude'],':longitude' => $row['longitude'],':altitude' => $row['altitude'],':type' => $row['type'],':city' => $row['city'],':country' => $row['country'],':home_link' => $row['homepage'],':wikipedia_link' => $row['wikipedia_page'],':image' => $row['image'],':image_thumb' => $row['image_thumb']);
			//print_r($query_dest_values);
			
			try {
				$sth_dest->execute($query_dest_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
			}

			$i++;
		}
		if ($globalTransaction) $Connection->db->commit();
		echo "Delete duplicate rows...\n";
		$query = 'ALTER IGNORE TABLE airport ADD UNIQUE INDEX icaoidx (icao)';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }


		if ($globalDebug) echo "Insert Not available Airport...\n";
		$query = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`,`image`,`image_thumb`)
		    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image, :image_thumb)";
		$query_values = array(':airport_id' => $i, ':name' => 'Not available',':iata' => 'NA',':icao' => 'NA',':latitude' => '0',':longitude' => '0',':altitude' => '0',':type' => 'NA',':city' => 'N/A',':country' => 'N/A',':home_link' => '',':wikipedia_link' => '',':image' => '',':image_thumb' => '');
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		$i++;
/*
		$query = 'DELETE FROM airport WHERE airport_id IN (SELECT * FROM (SELECT min(a.airport_id) FROM airport a GROUP BY a.icao) x)';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
*/

		echo "Download data from ourairports.com...\n";
		$delimiter = ',';
		$out_file = $tmp_dir.'airports.csv';
		update_db::download('http://ourairports.com/data/airports.csv',$out_file);
		if (!file_exists($out_file) || !is_readable($out_file)) return FALSE;
		echo "Add data from ourairports.com...\n";

		$header = NULL;
		if (($handle = fopen($out_file, 'r')) !== FALSE)
		{
			$Connection = new Connection();
			//$Connection->db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header) $header = $row;
				else {
					$data = array();
					$data = array_combine($header, $row);
					try {
						$sth = $Connection->db->prepare('SELECT COUNT(*) FROM airport WHERE `icao` = :icao');
						$sth->execute(array(':icao' => $data['gps_code']));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					if ($sth->fetchColumn() > 0) {
						$query = 'UPDATE airport SET `type` = :type WHERE icao = :icao';
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':icao' => $data['gps_code'],':type' => $data['type']));
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
					} else {
						$query = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`)
						    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link)";
						$query_values = array(':airport_id' => $i, ':name' => $data['name'],':iata' => $data['iata_code'],':icao' => $data['gps_code'],':latitude' => $data['latitude_deg'],':longitude' => $data['longitude_deg'],':altitude' => $data['elevation_ft'],':type' => $data['type'],':city' => $data['municipality'],':country' => $data['iso_country'],':home_link' => $data['home_link'],':wikipedia_link' => $data['wikipedia_link']);
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute($query_values);
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
						$i++;
					}
				}
			}
			fclose($handle);
			//$Connection->db->commit();
		}

		echo "Download data from another free database...\n";
		$out_file = $tmp_dir.'GlobalAirportDatabase.zip';
		update_db::download('http://www.partow.net/downloads/GlobalAirportDatabase.zip',$out_file);
		if (!file_exists($out_file) || !is_readable($out_file)) return FALSE;
		update_db::unzip($out_file);
		$header = NULL;
		echo "Add data from another free database...\n";
		$delimiter = ':';
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'GlobalAirportDatabase.txt', 'r')) !== FALSE)
		{
			//$Connection->db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header) $header = $row;
				else {
					$data = $row;

					$query = 'UPDATE airport SET `city` = :city, `country` = :country WHERE icao = :icao';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':icao' => $data[0],':city' => ucwords(strtolower($data[3])),':country' => ucwords(strtolower($data[4]))));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			fclose($handle);
			//$Connection->db->commit();
		}

		echo "Put type military for all air base";
		$Connection = new Connection();
		try {
			$sth = $Connection->db->prepare("SELECT icao FROM airport WHERE `name` LIKE '%Air Base%'");
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$query2 = 'UPDATE airport SET `type` = :type WHERE icao = :icao';
			try {
				$sth2 = $Connection->db->prepare($query2);
				$sth2->execute(array(':icao' => $row['icao'],':type' => 'military'));
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}



                return "success";
	}
	
	public static function translation() {
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		global $tmp_dir, $globalTransaction;
		$Spotter = new Spotter();
		//$out_file = $tmp_dir.'translation.zip';
		//update_db::download('http://www.acarsd.org/download/translation.php',$out_file);
		//if (!file_exists($out_file) || !is_readable($out_file)) return FALSE;
		
		//$query = 'TRUNCATE TABLE translation';
		$query = "DELETE FROM translation WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'translation.csv'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		
		//update_db::unzip($out_file);
		$header = NULL;
		$delimiter = ';';
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'translation.csv', 'r')) !== FALSE)
		{
			$i = 0;
			//$Connection->db->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
			//$Connection->db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				$i++;
				if($i > 12) {
					$data = $row;
					$operator = $data[2];
					if ($operator != '' && is_numeric(substr(substr($operator, 0, 3), -1, 1))) {
                                                $airline_array = $Spotter->getAllAirlineInfo(substr($operator, 0, 2));
                                                //echo substr($operator, 0, 2)."\n";;
                                                if (count($airline_array) > 0) {
							//print_r($airline_array);
							$operator = $airline_array[0]['icao'].substr($operator,2);
                                                }
                                        }
					
					$operator_correct = $data[3];
					if ($operator_correct != '' && is_numeric(substr(substr($operator_correct, 0, 3), -1, 1))) {
                                                $airline_array = $Spotter->getAllAirlineInfo(substr($operator_correct, 0, 2));
                                                if (count($airline_array) > 0) {
                                            		$operator_correct = $airline_array[0]['icao'].substr($operator_correct,2);
                                            	}
                                        }
					$query = 'INSERT INTO translation (Reg,Reg_correct,Operator,Operator_correct,Source) VALUES (:Reg, :Reg_correct, :Operator, :Operator_correct, :source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':Reg' => $data[0],':Reg_correct' => $data[1],':Operator' => $operator,':Operator_correct' => $operator_correct, ':source' => 'translation.csv'));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			fclose($handle);
			//$Connection->db->commit();
		}
		return '';
        }
	
	public static function translation_fam() {
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		global $tmp_dir, $globalTransaction;
		$Spotter = new Spotter();
		$query = "DELETE FROM translation WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'website_fam'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		
		//update_db::unzip($out_file);
		$header = NULL;
		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'translation.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			//$Connection->db->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
			//$Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$query = 'INSERT INTO translation (Reg,Reg_correct,Operator,Operator_correct,Source) VALUES (:Reg, :Reg_correct, :Operator, :Operator_correct, :source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':Reg' => $data[0],':Reg_correct' => $data[1],':Operator' => $data[2],':Operator_correct' => $data[3], ':source' => 'website_fam'));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			//$Connection->db->commit();
		}
		return '';
        }

	public static function tle($filename,$tletype) {
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		global $tmp_dir, $globalTransaction;
		//$Spotter = new Spotter();
		
		$query = "DELETE FROM tle WHERE tle_source = '' OR tle_source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $filename));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		
		$Connection = new Connection();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			$i = 0;
			//$Connection->db->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
			//$Connection->db->beginTransaction();
			$dbdata = array();
			while (($data = fgets($handle, 1000)) !== FALSE)
			{
				if ($i == 0) {
					$dbdata['name'] = trim($data);
					$i++;
				} elseif ($i == 1) {
					$dbdata['tle1'] = trim($data);
					$i++;
				} elseif ($i == 2) {
					$dbdata['tle2'] = trim($data);
					//print_r($dbdata);
					$query = 'INSERT INTO tle (tle_name,tle_tle1,tle_tle2,tle_type,tle_source) VALUES (:name, :tle1, :tle2, :type, :source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':name' => $dbdata['name'],':tle1' => $dbdata['tle1'],':tle2' => $dbdata['tle2'], ':type' => $tletype,':source' => $filename));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}

					$i = 0;
				}
			}
			fclose($handle);
			//$Connection->db->commit();
		}
		return '';
        }

	/**
        * Convert a HTML table to an array
        * @param String $data HTML page
        * @return Array array of the tables in HTML page
        */
        private static function table2array($data) {
                $html = str_get_html($data);
                $tabledata=array();
                foreach($html->find('tr') as $element)
                {
                        $td = array();
                        foreach( $element->find('th') as $row)
                        {
                                $td [] = trim($row->plaintext);
                        }
                        $td=array_filter($td);
                        $tabledata[] = $td;

                        $td = array();
                        $tdi = array();
                        foreach( $element->find('td') as $row)
                        {
                                $td [] = trim($row->plaintext);
                                $tdi [] = trim($row->innertext);
                        }
                        $td=array_filter($td);
                        $tdi=array_filter($tdi);
                    //    $tabledata[]=array_merge($td,$tdi);
                        $tabledata[]=$td;
                }
                return(array_filter($tabledata));
        }

       /**
        * Get data from form result
        * @param String $url form URL
        * @return String the result
        */
        private static function getData($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
                return curl_exec($ch);
        }
/*
	public static function waypoints() {
		$data = update_db::getData('http://www.fallingrain.com/world/FR/waypoints.html');
		$table = update_db::table2array($data);
//		print_r($table);
		$query = 'TRUNCATE TABLE waypoints';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$query_dest = 'INSERT INTO waypoints (`ident`,`latitude`,`longitude`,`control`,`usage`) VALUES (:ident, :latitude, :longitude, :control, :usage)';
		$Connection = new Connection();
		$sth_dest = $Connection->db->prepare($query_dest);
		$Connection->db->beginTransaction();
                foreach ($table as $row) {
            		if ($row[0] != 'Ident') {
				$ident = $row[0];
				$latitude = $row[2];
				$longitude = $row[3];
				$control = $row[4];
				if (isset($row[5])) $usage = $row[5]; else $usage = '';
				$query_dest_values = array(':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':control' => $control,':usage' => $usage);
				try {
					$sth_dest->execute($query_dest_values);
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			}
                }
		$Connection->db->commit();

	}
*/
	public static function waypoints($filename) {
		//require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		global $tmp_dir, $globalTransaction;
		//$Spotter = new Spotter();
		//$out_file = $tmp_dir.'translation.zip';
		//update_db::download('http://www.acarsd.org/download/translation.php',$out_file);
		//if (!file_exists($out_file) || !is_readable($out_file)) return FALSE;
		$Connection = new Connection();
		//update_db::unzip($out_file);
		$header = NULL;
		$delimiter = ' ';
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				$i++;
				if($i > 3 && count($row) > 2) {
					$data = array_values(array_filter($row));
					$cntdata = count($data);
					if ($cntdata > 10) {
						$value = $data[9];
						
						for ($i =10;$i < $cntdata;$i++) {
							$value .= ' '.$data[$i];
						}
						$data[9] = $value;
					}
					//print_r($data);
					if (count($data) > 9) {
						$query = 'INSERT INTO waypoints (name_begin,latitude_begin,longitude_begin,name_end,latitude_end,longitude_end,high,base,top,segment_name) VALUES (:name_begin, :latitude_begin, :longitude_begin, :name_end, :latitude_end, :longitude_end, :high, :base, :top, :segment_name)';
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':name_begin' => $data[0],':latitude_begin' => $data[1],':longitude_begin' => $data[2],':name_end' => $data[3], ':latitude_end' => $data[4], ':longitude_end' => $data[5], ':high' => $data[6], ':base' => $data[7], ':top' => $data[8], ':segment_name' => $data[9]));
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
					}
				}
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
        }

	public static function ivao_airlines($filename) {
		//require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		global $tmp_dir, $globalTransaction;
		//$query = 'TRUNCATE TABLE airlines';
		$query = "DELETE FROM airlines WHERE forsource = 'ivao'";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$header = NULL;
		$delimiter = ':';
		$Connection = new Connection();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(count($row) > 1) {
					$query = "INSERT INTO airlines (name,icao,active,forsource) VALUES (:name, :icao, 'Y','ivao')";
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':name' => $row[1],':icao' => $row[0]));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
        }
	
	public static function update_airspace() {
		global $tmp_dir, $globalDBdriver;
		include_once('class.create_db.php');
		$Connection = new Connection();
		if ($Connection->tableExists('airspace')) {
			$query = 'DROP TABLE airspace';
			try {
				$sth = $Connection->db->prepare($query);
                    		$sth->execute();
	                } catch(PDOException $e) {
				return "error : ".$e->getMessage();
	                }
	        }


		if ($globalDBdriver == 'mysql') update_db::gunzip('../db/airspace.sql.gz',$tmp_dir.'airspace.sql');
		else {
			update_db::gunzip('../db/pgsql/airspace.sql.gz',$tmp_dir.'airspace.sql');
			$query = "CREATE EXTENSION postgis";
			$Connection = new Connection(null,null,$_SESSION['database_root'],$_SESSION['database_rootpass']);
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		$error = create_db::import_file($tmp_dir.'airspace.sql');
		return $error;
	}

	public static function update_notam_fam() {
		global $tmp_dir, $globalDebug;
		include_once('class.create_db.php');
		require_once(dirname(__FILE__).'/../require/class.NOTAM.php');
		if ($globalDebug) echo "NOTAM from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.fr/data/notam.txt.gz',$tmp_dir.'notam.txt.gz');
		if (file_exists($tmp_dir.'notam.txt.gz')) {
			if ($globalDebug) echo "Gunzip...";
			update_db::gunzip($tmp_dir.'notam.txt.gz');
			if ($globalDebug) echo "Add to DB...";
			//$error = create_db::import_file($tmp_dir.'notam.sql');
			$NOTAM = new NOTAM();
			$NOTAM->updateNOTAMfromTextFile($tmp_dir.'notam.txt');
		} else $error = "File ".$tmp_dir.'notam.txt.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_vatsim() {
		global $tmp_dir;
		include_once('class.create_db.php');
		$error = create_db::import_file('../db/vatsim/airlines.sql');
		return $error;
	}
	
	public static function update_countries() {
		global $tmp_dir, $globalDBdriver;
		include_once('class.create_db.php');
		$Connection = new Connection();
		if ($Connection->tableExists('countries')) {
			$query = 'DROP TABLE countries';
			try {
				$sth = $Connection->db->prepare($query);
            	        	$sth->execute();
	                } catch(PDOException $e) {
    	                	echo "error : ".$e->getMessage();
	                }
		}
		if ($globalDBdriver == 'mysql') {
			update_db::gunzip('../db/countries.sql.gz',$tmp_dir.'countries.sql');
		} else {
			update_db::gunzip('../db/pgsql/countries.sql.gz',$tmp_dir.'countries.sql');
		}
		$error = create_db::import_file($tmp_dir.'countries.sql');
		return $error;
	}

	
	public static function update_waypoints() {
		global $tmp_dir;
//		update_db::download('http://dev.x-plane.com/update/data/AptNav201310XP1000.zip',$tmp_dir.'AptNav.zip');
//		update_db::unzip($tmp_dir.'AptNav.zip');
//		update_db::download('https://gitorious.org/fg/fgdata/raw/e81f8a15424a175a7b715f8f7eb8f4147b802a27:Navaids/awy.dat.gz',$tmp_dir.'awy.dat.gz');
//		update_db::download('http://sourceforge.net/p/flightgear/fgdata/ci/next/tree/Navaids/awy.dat.gz?format=raw',$tmp_dir.'awy.dat.gz','http://sourceforge.net');
		update_db::download('http://pkgs.fedoraproject.org/repo/extras/FlightGear-Atlas/awy.dat.gz/f530c9d1c4b31a288ba88dcc8224268b/awy.dat.gz',$tmp_dir.'awy.dat.gz','http://sourceforge.net');
		update_db::gunzip($tmp_dir.'awy.dat.gz');
		$error = update_db::waypoints($tmp_dir.'awy.dat');
		return $error;
	}

	public static function update_ivao() {
		global $tmp_dir, $globalDebug;
		$Common = new Common();
		$error = '';
		//Direct download forbidden
		//if ($globalDebug) echo "IVAO : Download...";
		//update_db::download('http://fr.mirror.ivao.aero/software/ivae_feb2013.zip',$tmp_dir.'ivae_feb2013.zip');
		if (file_exists($tmp_dir.'ivae_feb2013.zip')) {
			if ($globalDebug) echo "Unzip...";
			update_db::unzip($tmp_dir.'ivae_feb2013.zip');
			if ($globalDebug) echo "Add to DB...";
			update_db::ivao_airlines($tmp_dir.'data/airlines.dat');
			if ($globalDebug) echo "Copy airlines logos to airlines images directory...";
			if (is_writable(dirname(__FILE__).'/../images/airlines')) {
				if (!$Common->xcopy($tmp_dir.'logos/',dirname(__FILE__).'/../images/airlines/')) $error = "Failed to copy airlines logo.";
			} else $error = "The directory ".dirname(__FILE__).'/../images/airlines'." must be writable";
		} else $error = "File ".$tmp_dir.'ivao.zip'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_routes() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Routes : Download...";
		update_db::download('http://www.virtualradarserver.co.uk/Files/StandingData.sqb.gz',$tmp_dir.'StandingData.sqb.gz');
		if (file_exists($tmp_dir.'StandingData.sqb.gz')) {
			if ($globalDebug) echo "Gunzip...";
			update_db::gunzip($tmp_dir.'StandingData.sqb.gz');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_route_sqlite_to_dest($tmp_dir.'StandingData.sqb');
		} else $error = "File ".$tmp_dir.'StandingData.sqb.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_oneworld() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Schedules Oneworld : Download...";
		update_db::download('http://data.flightairmap.fr/data/schedules/oneworld.csv.gz',$tmp_dir.'oneworld.csv.gz');
		if (file_exists($tmp_dir.'oneworld.csv.gz')) {
			if ($globalDebug) echo "Gunzip...";
			update_db::gunzip($tmp_dir.'oneworld.csv.gz');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_route_oneworld($tmp_dir.'oneworld.csv');
		} else $error = "File ".$tmp_dir.'oneworld.csv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_skyteam() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Schedules Skyteam : Download...";
		update_db::download('http://data.flightairmap.fr/data/schedules/skyteam.csv.gz',$tmp_dir.'skyteam.csv.gz');
		if (file_exists($tmp_dir.'skyteam.csv.gz')) {
			if ($globalDebug) echo "Gunzip...";
			update_db::gunzip($tmp_dir.'skyteam.csv.gz');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_route_skyteam($tmp_dir.'skyteam.csv');
		} else $error = "File ".$tmp_dir.'skyteam.csv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_ModeS() {
		global $tmp_dir, $globalDebug;
/*
		if ($globalDebug) echo "Modes : Download...";
		update_db::download('http://pp-sqb.mantma.co.uk/basestation_latest.zip',$tmp_dir.'basestation_latest.zip');
		if ($globalDebug) echo "Unzip...";
		update_db::unzip($tmp_dir.'basestation_latest.zip');
		if ($globalDebug) echo "Add to DB...";
		$error = update_db::retrieve_modes_sqlite_to_dest($tmp_dir.'/basestation_latest/basestation.sqb');
		if ($error != true) {
			echo $error;
			exit;
		} elseif ($globalDebug) echo "Done\n";
*/
		if ($globalDebug) echo "Modes : Download...";
		update_db::download('http://planebase.biz/sqb.php?f=basestationall.zip',$tmp_dir.'basestation_latest.zip','http://planebase.biz/bstnsqb');
		if (file_exists($tmp_dir.'basestation_latest.zip')) {
			if ($globalDebug) echo "Unzip...";
			update_db::unzip($tmp_dir.'basestation_latest.zip');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_modes_sqlite_to_dest($tmp_dir.'BaseStation.sqb');
		} else $error = "File ".$tmp_dir.'basestation_latest.zip'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_ModeS_flarm() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Modes Flarmnet: Download...";
		update_db::download('http://flarmnet.org/files/data.fln',$tmp_dir.'data.fln');
		if (file_exists($tmp_dir.'data.fln')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_modes_flarmnet($tmp_dir.'data.fln');
		} else $error = "File ".$tmp_dir.'data.fln'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_ModeS_ogn() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Modes OGN: Download...";
		update_db::download('http://ddb.glidernet.org/download/',$tmp_dir.'ogn.csv');
		if (file_exists($tmp_dir.'ogn.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_modes_ogn($tmp_dir.'ogn.csv');
		} else $error = "File ".$tmp_dir.'ogn.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_owner() {
		global $tmp_dir, $globalDebug;
		
		if ($globalDebug) echo "Owner France: Download...";
		update_db::download('http://antonakis.co.uk/registers/France.txt',$tmp_dir.'owner_f.csv');
		if (file_exists($tmp_dir.'owner_f.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_f.csv','F');
		} else $error = "File ".$tmp_dir.'owner_f.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		
		if ($globalDebug) echo "Owner Ireland: Download...";
		update_db::download('http://antonakis.co.uk/registers/Ireland.txt',$tmp_dir.'owner_ei.csv');
		if (file_exists($tmp_dir.'owner_ei.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_ei.csv','EI');
		} else $error = "File ".$tmp_dir.'owner_ei.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Switzerland: Download...";
		update_db::download('http://antonakis.co.uk/registers/Switzerland.txt',$tmp_dir.'owner_hb.csv');
		if (file_exists($tmp_dir.'owner_hb.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_hb.csv','HB');
		} else $error = "File ".$tmp_dir.'owner_hb.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Czech Republic: Download...";
		update_db::download('http://antonakis.co.uk/registers/CzechRepublic.txt',$tmp_dir.'owner_ok.csv');
		if (file_exists($tmp_dir.'owner_ok.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_ok.csv','OK');
		} else $error = "File ".$tmp_dir.'owner_ok.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Australia: Download...";
		update_db::download('http://antonakis.co.uk/registers/Australia.txt',$tmp_dir.'owner_vh.csv');
		if (file_exists($tmp_dir.'owner_vh.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_vh.csv','VH');
		} else $error = "File ".$tmp_dir.'owner_vh.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Austria: Download...";
		update_db::download('http://antonakis.co.uk/registers/Austria.txt',$tmp_dir.'owner_oe.csv');
		if (file_exists($tmp_dir.'owner_oe.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_oe.csv','OE');
		} else $error = "File ".$tmp_dir.'owner_oe.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Chile: Download...";
		update_db::download('http://antonakis.co.uk/registers/Chile.txt',$tmp_dir.'owner_cc.csv');
		if (file_exists($tmp_dir.'owner_cc.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_cc.csv','CC');
		} else $error = "File ".$tmp_dir.'owner_cc.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Colombia: Download...";
		update_db::download('http://antonakis.co.uk/registers/Colombia.txt',$tmp_dir.'owner_hj.csv');
		if (file_exists($tmp_dir.'owner_hj.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_hj.csv','HJ');
		} else $error = "File ".$tmp_dir.'owner_hj.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Bosnia Herzegobina: Download...";
		update_db::download('http://antonakis.co.uk/registers/BosniaHerzegovina.txt',$tmp_dir.'owner_e7.csv');
		if (file_exists($tmp_dir.'owner_e7.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_e7.csv','E7');
		} else $error = "File ".$tmp_dir.'owner_e7.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Brazil: Download...";
		update_db::download('http://antonakis.co.uk/registers/Brazil.txt',$tmp_dir.'owner_pp.csv');
		if (file_exists($tmp_dir.'owner_pp.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_pp.csv','PP');
		} else $error = "File ".$tmp_dir.'owner_pp.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Cayman Islands: Download...";
		update_db::download('http://antonakis.co.uk/registers/CaymanIslands.txt',$tmp_dir.'owner_vp.csv');
		if (file_exists($tmp_dir.'owner_vp.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_vp.csv','VP');
		} else $error = "File ".$tmp_dir.'owner_vp.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Croatia: Download...";
		update_db::download('http://antonakis.co.uk/registers/Croatia.txt',$tmp_dir.'owner_9a.csv');
		if (file_exists($tmp_dir.'owner_9a.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_9a.csv','9A');
		} else $error = "File ".$tmp_dir.'owner_9a.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Luxembourg: Download...";
		update_db::download('http://antonakis.co.uk/registers/Luxembourg.txt',$tmp_dir.'owner_lx.csv');
		if (file_exists($tmp_dir.'owner_lx.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_lx.csv','LX');
		} else $error = "File ".$tmp_dir.'owner_lx.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Maldives: Download...";
		update_db::download('http://antonakis.co.uk/registers/Maldives.txt',$tmp_dir.'owner_8q.csv');
		if (file_exists($tmp_dir.'owner_8q.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_8q.csv','8Q');
		} else $error = "File ".$tmp_dir.'owner_8q.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner New Zealand: Download...";
		update_db::download('http://antonakis.co.uk/registers/NewZealand.txt',$tmp_dir.'owner_zk.csv');
		if (file_exists($tmp_dir.'owner_zk.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_zk.csv','ZK');
		} else $error = "File ".$tmp_dir.'owner_zk.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Papua New Guinea: Download...";
		update_db::download('http://antonakis.co.uk/registers/PapuaNewGuinea.txt',$tmp_dir.'owner_p2.csv');
		if (file_exists($tmp_dir.'owner_p2.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_p2.csv','P2');
		} else $error = "File ".$tmp_dir.'owner_p2.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Slovakia: Download...";
		update_db::download('http://antonakis.co.uk/registers/Slovakia.txt',$tmp_dir.'owner_om.csv');
		if (file_exists($tmp_dir.'owner_om.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_om.csv','OM');
		} else $error = "File ".$tmp_dir.'owner_om.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Ecuador: Download...";
		update_db::download('http://antonakis.co.uk/registers/Ecuador.txt',$tmp_dir.'owner_hc.csv');
		if (file_exists($tmp_dir.'owner_hc.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_hc.csv','HC');
		} else $error = "File ".$tmp_dir.'owner_hc.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "Owner Iceland: Download...";
		update_db::download('http://antonakis.co.uk/registers/Iceland.txt',$tmp_dir.'owner_tf.csv');
		if (file_exists($tmp_dir.'owner_tf.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_tf.csv','TF');
		} else $error = "File ".$tmp_dir.'owner_tf.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_translation() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Translation : Download...";
		update_db::download('http://www.acarsd.org/download/translation.php',$tmp_dir.'translation.zip');
		if (file_exists($tmp_dir.'translation.zip')) {
			if ($globalDebug) echo "Unzip...";
			update_db::unzip($tmp_dir.'translation.zip');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::translation();
		} else $error = "File ".$tmp_dir.'translation.zip'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_translation_fam() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Translation from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.fr/data/translation.tsv.gz',$tmp_dir.'translation.tsv.gz');
		if (file_exists($tmp_dir.'translation.tsv.gz')) {
			if ($globalDebug) echo "Gunzip...";
			update_db::gunzip($tmp_dir.'translation.tsv.gz');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::translation_fam();
		} else $error = "File ".$tmp_dir.'translation.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_tle() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Download TLE : Download...";
		$alltle = array('stations.txt','gps-ops.txt','glo-ops.txt','galileo.txt','weather.txt','noaa.txt','goes.txt','resource.txt','dmc.txt','tdrss.txt','geo.txt','intelsat.txt','gorizont.txt',
		'raduga.txt','molniya.txt','iridium.txt','orbcomm.txt','globalstar.txt','amateur.txt','x-comm.txt','other-comm.txt','sbas.txt','nnss.txt','musson.txt','science.txt','geodetic.txt',
		'engineering.txt','education.txt','military.txt','radar.txt','cubesat.txt','other.txt','tle-new.txt');
		foreach ($alltle as $filename) {
			if ($globalDebug) echo "downloading ".$filename.'...';
			update_db::download('http://celestrak.com/NORAD/elements/'.$filename,$tmp_dir.$filename);
			if (file_exists($tmp_dir.$filename)) {
				if ($globalDebug) echo "Add to DB ".$filename."...";
				$error = update_db::tle($tmp_dir.$filename,str_replace('.txt','',$filename));
			} else $error = "File ".$tmp_dir.$filename." doesn't exist. Download failed.";
			if ($error != '') {
				echo $error."\n";
			} elseif ($globalDebug) echo "Done\n";
		}
		return '';
	}

	public static function update_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.fr/data/models/models.md5sum',$tmp_dir.'models.md5sum');
		if (file_exists($tmp_dir.'models.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'models.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/models.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/models.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading model '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.fr/data/models/'.$key,dirname(__FILE__).'/../models/'.$key);
				
			}
			update_db::download('http://data.flightairmap.fr/data/models/models.md5sum',dirname(__FILE__).'/../models/models.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_space_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Space models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.fr/data/models/space/space_models.md5sum',$tmp_dir.'space_models.md5sum');
		if (file_exists($tmp_dir.'space_models.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'space_models.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/space/space_models.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/space/space_models.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading space model '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.fr/data/models/space/'.$key,dirname(__FILE__).'/../models/space/'.$key);
				
			}
			update_db::download('http://data.flightairmap.fr/data/models/space/space_models.md5sum',dirname(__FILE__).'/../models/space/space_models.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_aircraft() {
		global $tmp_dir, $globalDebug;
		date_default_timezone_set('UTC');
		//$error = '';
		/*
		if ($globalDebug) echo "Aircrafts : Download...";
		$data_req_array = array('Mnfctrer' => '','Model' => '','Dscrptn'=> '','EngCount' =>'' ,'EngType'=> '','TDesig' => '*','WTC' => '','Button' => 'Search');
		$data_req = 'Mnfctrer=Airbus&Model=&Dscrptn=&EngCount=&EngType=&TDesig=&WTC=&Button=Search';
		//$data = Common::getData('http://cfapp.icao.int/Doc8643/8643_List1.cfm','post',$data_req_array,array('Content-Type: application/x-www-form-urlencoded','Host: cfapp.icao.int','Origin: http://cfapp.icao.int','Pragma: no-cache','Upgrade-Insecure-Requests: 1','Content-Length: '.strlen($data_req)),'','http://cfapp.icao.int/Doc8643/search.cfm',20);
		$data = Common::getData('http://cfapp.icao.int/Doc8643/8643_List1.cfm','post',$data_req_array,'','','http://cfapp.icao.int/Doc8643/search.cfm',30);
//		echo strlen($data_req);
		echo $data;
		*/
		if (file_exists($tmp_dir.'aircrafts.html')) {
		    //var_dump(file_get_html($tmp_dir.'aircrafts.html'));
		    $fh = fopen($tmp_dir.'aircrafts.html',"r");
		    $result = fread($fh,100000000);
		    //echo $result;
		    //var_dump(str_get_html($result));
		    //print_r(self::table2array($result));
		}

	}
	
	public static function update_notam() {
		global $tmp_dir, $globalDebug, $globalNOTAMSource;
		require(dirname(__FILE__).'/../require/class.NOTAM.php');
		$Common = new Common();
		date_default_timezone_set('UTC');
		$query = 'TRUNCATE TABLE notam';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$error = '';
		if ($globalDebug) echo "Notam : Download...";
		update_db::download($globalNOTAMSource,$tmp_dir.'notam.rss');
		if (file_exists($tmp_dir.'notam.rss')) {
			$notams = json_decode(json_encode(simplexml_load_file($tmp_dir.'notam.rss')),true);
			foreach ($notams['channel']['item'] as $notam) {
				$title = explode(':',$notam['title']);
				$data['ref'] = trim($title[0]);
				unset($title[0]);
				$data['title'] = trim(implode(':',$title));
				$description = strip_tags($notam['description'],'<pre>');
				preg_match(':^(.*?)<pre>:',$description,$match);
				$q = explode('/',$match[1]);
				$data['fir'] = $q[0];
				$data['code'] = $q[1];
				$ifrvfr = $q[2];
				if ($ifrvfr == 'IV') $data['rules'] = 'IFR/VFR';
				if ($ifrvfr == 'I') $data['rules'] = 'IFR';
				if ($ifrvfr == 'V') $data['rules'] = 'VFR';
				if ($q[4] == 'A') $data['scope'] = 'Airport warning';
				if ($q[4] == 'E') $data['scope'] = 'Enroute warning';
				if ($q[4] == 'W') $data['scope'] = 'Navigation warning';
				if ($q[4] == 'AE') $data['scope'] = 'Airport/Enroute warning';
				if ($q[4] == 'AW') $data['scope'] = 'Airport/Navigation warning';
				//$data['scope'] = $q[4];
				$data['lower_limit'] = $q[5];
				$data['upper_limit'] = $q[6];
				$latlonrad = $q[7];
				sscanf($latlonrad,'%4c%c%5c%c%3d',$las,$lac,$lns,$lnc,$radius);
				$latitude = $Common->convertDec($las,'latitude');
				$longitude = $Common->convertDec($lns,'longitude');
				if ($lac == 'S') $latitude = '-'.$latitude;
				if ($lnc == 'W') $longitude = '-'.$longitude;
				$data['center_latitude'] = $latitude;
				$data['center_longitude'] = $longitude;
				$data['radius'] = intval($radius);
				
				preg_match(':<pre>(.*?)</pre>:',$description,$match);
				$data['text'] = $match[1];
				preg_match(':</pre>(.*?)$:',$description,$match);
				$fromto = $match[1];
				preg_match('#FROM:(.*?)TO:#',$fromto,$match);
				$fromall = trim($match[1]);
				preg_match('#^(.*?) \((.*?)\)$#',$fromall,$match);
				$from = trim($match[1]);
				$data['date_begin'] = date("Y-m-d H:i:s",strtotime($from));
				preg_match('#TO:(.*?)$#',$fromto,$match);
				$toall = trim($match[1]);
				if (!preg_match(':Permanent:',$toall)) {
					preg_match('#^(.*?) \((.*?)\)#',$toall,$match);
					$to = trim($match[1]);
					$data['date_end'] = date("Y-m-d H:i:s",strtotime($to));
					$data['permanent'] = 0;
				} else {
				    $data['date_end'] = NULL;
				    $data['permanent'] = 1;
				}
				$data['full_notam'] = $notam['title'].'<br>'.$notam['description'];
				$NOTAM = new NOTAM();
				$NOTAM->addNOTAM($data['ref'],$data['title'],'',$data['fir'],$data['code'],'',$data['scope'],$data['lower_limit'],$data['upper_limit'],$data['center_latitude'],$data['center_longitude'],$data['radius'],$data['date_begin'],$data['date_end'],$data['permanent'],$data['text'],$data['full_notam']);
				unset($data);
			} 
		} else $error = "File ".$tmp_dir.'notam.rss'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	
	public static function check_last_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_db' AND value > DATE_SUB(DATE(NOW()), INTERVAL 15 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '15 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return false;
                else return true;
	}

	public static function insert_last_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_db';
			INSERT INTO config (name,value) VALUES ('last_update_db',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}

	public static function check_last_notam_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_notam_db' AND value > DATE_SUB(DATE(NOW()), INTERVAL 1 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_notam_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '1 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return false;
                else return true;
	}

	public static function insert_last_notam_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_notam_db';
			INSERT INTO config (name,value) VALUES ('last_update_notam_db',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}

	public static function check_last_owner_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_owner_db' AND value > DATE_SUB(DATE(NOW()), INTERVAL 15 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_owner_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '15 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return false;
                else return true;
	}

	public static function insert_last_owner_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_owner_db';
			INSERT INTO config (name,value) VALUES ('last_update_owner_db',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}
	public static function check_last_schedules_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_schedules' AND value > DATE_SUB(DATE(NOW()), INTERVAL 15 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_schedules' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '15 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return false;
                else return true;
	}

	public static function insert_last_schedules_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_schedules';
			INSERT INTO config (name,value) VALUES ('last_update_schedules',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}
	public static function check_last_tle_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_tle' AND value > DATE_SUB(DATE(NOW()), INTERVAL 7 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_tle' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '7 DAYS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return false;
                else return true;
	}

	public static function insert_last_tle_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_tle';
			INSERT INTO config (name,value) VALUES ('last_update_tle',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}
	
	public static function update_all() {
		global $globalMasterServer;
		if (!isset($globalMasterServer) || !$globalMasterServer) {
			echo update_db::update_routes();
			echo update_db::update_translation();
			echo update_db::update_translation_fam();
			echo update_db::update_notam_fam();
		}
		echo update_db::update_ModeS();
		echo update_db::update_ModeS_flarm();
		echo update_db::update_ModeS_ogn();
	}
}

//echo update_db::update_airports();
//echo update_db::translation();
//echo update_db::update_waypoints();
//echo update_db::update_airspace();
//echo update_db::update_notam();
//echo update_db::update_ivao();
//echo update_db::update_ModeS_flarm();
//echo update_db::update_ModeS_ogn();
//echo update_db::update_aircraft();
//$update_db = new update_db();
//echo $update_db->update_owner();
//update_db::update_translation_fam();
//echo update_db::update_routes();
//update_db::update_models();
//echo $update_db::update_skyteam();
//echo $update_db::update_tle();
//echo update_db::update_notam_fam();
?>
