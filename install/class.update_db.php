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
	
	public static function check() {
		global $globalDebug;
		$Common = new Common();
		$writable = $Common->is__writable(dirname(__FILE__).'/tmp/');
		if ($writable === false && $globalDebug) {
			echo dirname(__FILE__).'/tmp/'.' is not writable, fix permissions and try again.';
		}
		return $writable;
	}

	public static function download($url, $file, $referer = '') {
		global $globalProxy, $globalForceIPv4;
		$fp = fopen($file, 'w');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (isset($globalForceIPv4) && $globalForceIPv4) {
			if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
				curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			}
		}
		if (isset($globalProxy) && $globalProxy != '') {
			curl_setopt($ch, CURLOPT_PROXY, $globalProxy);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 200);
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
						$datebe = explode('  -  ',$line[2]);
						if (strtotime($datebe[0]) > time() && strtotime($datebe[1]) < time()) {
							$query_dest_values = array(':CallSign' => str_replace('*','',$line[6]),':Operator_ICAO' => '',':FromAirport_ICAO' => $Spotter->getAirportICAO($line[0]),':FromAirport_Time' => $line[4],':ToAirport_ICAO' => $Spotter->getAirportICAO($line[1]),':ToAirport_Time' => $line[5],':routestop' => '',':source' => 'skyteam');
							$sth_dest->execute($query_dest_values);
						}
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

		// Remove data already in DB from ACARS
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
			$query_dest = 'INSERT INTO aircraft_modes (ModeS,Registration,ICAOTypeCode,Source,source_type) VALUES (:ModeS,:Registration,:ICAOTypeCode,:source,:source_type)';
		
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
					if ($values['Registration'] != '' && $values['Registration'] != '0000') {
						//$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
						$query_dest_values = array(':ModeS' => $values['ModeS'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':source' => $database_file,':source_type' => 'flarm');
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
			$query_dest = 'INSERT INTO aircraft_modes (LastModified,ModeS,Registration,ICAOTypeCode,Source,source_type) VALUES (:lastmodified,:ModeS,:Registration,:ICAOTypeCode,:source,:source_type)';
		
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
            				$values['ICAOTypeCode'] = '';
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
					if ($values['Registration'] != '' && $values['Registration'] != '0000' && $values['ICAOTypeCode'] != '') {
						//$query_dest_values = array(':AircraftID' => $values['AircraftID'],':FirstCreated' => $values['FirstCreated'],':LastModified' => $values['LastModified'],':ModeS' => $values['ModeS'],':ModeSCountry' => $values['ModeSCountry'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':SerialNo' => $values['SerialNo'], ':OperatorFlagCode' => $values['OperatorFlagCode'], ':Manufacturer' => $values['Manufacturer'], ':Type' => $values['Type'], ':FirstRegDate' => $values['FirstRegDate'], ':CurrentRegDate' => $values['CurrentRegDate'], ':Country' => $values['Country'], ':PreviousID' => $values['PreviousID'], ':DeRegDate' => $values['DeRegDate'], ':Status' => $values['Status'], ':PopularName' => $values['PopularName'],':GenericName' => $values['GenericName'],':AircraftClass' => $values['AircraftClass'], ':Engines' => $values['Engines'], ':OwnershipStatus' => $values['OwnershipStatus'],':RegisteredOwners' => $values['RegisteredOwners'],':MTOW' => $values['MTOW'], ':TotalHours' => $values['TotalHours'],':YearBuilt' => $values['YearBuilt'], ':CofACategory' => $values['CofACategory'], ':CofAExpiry' => $values['CofAExpiry'], ':UserNotes' => $values['UserNotes'], ':Interested' => $values['Interested'], ':UserTag' => $values['UserTag'], ':InfoUrl' => $values['InfoURL'], ':PictureUrl1' => $values['PictureURL1'], ':PictureUrl2' => $values['PictureURL2'], ':PictureUrl3' => $values['PictureURL3'], ':UserBool1' => $values['UserBool1'], ':UserBool2' => $values['UserBool2'], ':UserBool3' => $values['UserBool3'], ':UserBool4' => $values['UserBool4'], ':UserBool5' => $values['UserBool5'], ':UserString1' => $values['UserString1'], ':UserString2' => $values['UserString2'], ':UserString3' => $values['UserString3'], ':UserString4' => $values['UserString4'], ':UserString5' => $values['UserString5'], ':UserInt1' => $values['UserInt1'], ':UserInt2' => $values['UserInt2'], ':UserInt3' => $values['UserInt3'], ':UserInt4' => $values['UserInt4'], ':UserInt5' => $values['UserInt5']);
						$query_dest_values = array(':lastmodified' => date('Y-m-d H:m:s'),':ModeS' => $values['ModeS'],':Registration' => $values['Registration'],':ICAOTypeCode' => $values['ICAOTypeCode'],':source' => $database_file,':source_type' => 'flarm');
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
		global $globalTransaction, $globalMasterSource;
		//$query = 'TRUNCATE TABLE aircraft_modes';
		$query = "DELETE FROM aircraft_owner WHERE Source = '' OR Source IS NULL OR Source = :source; DELETE FROM aircraft_modes WHERE Source = :source;";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => $database_file));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		$Spotter = new Spotter();
		if ($fh = fopen($database_file,"r")) {
			//$query_dest = 'INSERT INTO aircraft_modes (`AircraftID`,`FirstCreated`,`LastModified`, `ModeS`,`ModeSCountry`,`Registration`,`ICAOTypeCode`,`SerialNo`, `OperatorFlagCode`, `Manufacturer`, `Type`, `FirstRegDate`, `CurrentRegDate`, `Country`, `PreviousID`, `DeRegDate`, `Status`, `PopularName`,`GenericName`,`AircraftClass`, `Engines`, `OwnershipStatus`,`RegisteredOwners`,`MTOW`, `TotalHours`, `YearBuilt`, `CofACategory`, `CofAExpiry`, `UserNotes`, `Interested`, `UserTag`, `InfoUrl`, `PictureUrl1`, `PictureUrl2`, `PictureUrl3`, `UserBool1`, `UserBool2`, `UserBool3`, `UserBool4`, `UserBool5`, `UserString1`, `UserString2`, `UserString3`, `UserString4`, `UserString5`, `UserInt1`, `UserInt2`, `UserInt3`, `UserInt4`, `UserInt5`) VALUES (:AircraftID,:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:SerialNo, :OperatorFlagCode, :Manufacturer, :Type, :FirstRegDate, :CurrentRegDate, :Country, :PreviousID, :DeRegDate, :Status, :PopularName,:GenericName,:AircraftClass, :Engines, :OwnershipStatus,:RegisteredOwners,:MTOW, :TotalHours,:YearBuilt, :CofACategory, :CofAExpiry, :UserNotes, :Interested, :UserTag, :InfoUrl, :PictureUrl1, :PictureUrl2, :PictureUrl3, :UserBool1, :UserBool2, :UserBool3, :UserBool4, :UserBool5, :UserString1, :UserString2, :UserString3, :UserString4, :UserString5, :UserInt1, :UserInt2, :UserInt3, :UserInt4, :UserInt5)';
			$query_dest = 'INSERT INTO aircraft_owner (registration,base,owner,date_first_reg,Source) VALUES (:registration,:base,:owner,:date_first_reg,:source)';
		        $query_modes = 'INSERT INTO aircraft_modes (ModeS,ModeSCountry,Registration,ICAOTypeCode,Source) VALUES (:modes,:modescountry,:registration,:icaotypecode,:source)';
		        
			$Connection = new Connection();
			$sth_dest = $Connection->db->prepare($query_dest);
			$sth_modes = $Connection->db->prepare($query_modes);
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
					    $values['modes'] = $line[7];
					    $values['icao'] = $line[8];
					    
					} elseif ($country == 'HB') {
					    // TODO : add modeS & reg to aircraft_modes
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[5];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					    $values['modes'] = $line[4];
					    $values['icao'] = $line[7];
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
					} elseif ($country == 'ZK') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[3];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					    $values['modes'] = $line[5];
					    $values['icao'] = $line[9];
					} elseif ($country == 'M') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[6];
            				    $values['date_first_reg'] = date("Y-m-d",strtotime($line[5]));
					    $values['cancel'] = date("Y-m-d",strtotime($line[8]));
					    $values['modes'] = $line[4];
					    $values['icao'] = $line[10];
					} elseif ($country == 'OY') {
            				    $values['registration'] = $line[0];
            				    $values['date_first_reg'] = date("Y-m-d",strtotime($line[4]));
					    $values['modes'] = $line[5];
					    $values['icao'] = $line[6];
					} elseif ($country == 'PH') {
            				    $values['registration'] = $line[0];
            				    $values['date_first_reg'] = date("Y-m-d",strtotime($line[3]));
					    $values['modes'] = $line[4];
					    $values['icao'] = $line[5];
					} elseif ($country == 'OM' || $country == 'TF') {
            				    $values['registration'] = $line[0];
            				    $values['base'] = null;
            				    $values['owner'] = $line[3];
            				    $values['date_first_reg'] = null;
					    $values['cancel'] = '';
					}
					if (isset($values['cancel']) && $values['cancel'] == '' && $values['registration'] != null && isset($values['owner'])) {
						$query_dest_values = array(':registration' => $values['registration'],':base' => $values['base'],':date_first_reg' => $values['date_first_reg'],':owner' => $values['owner'],':source' => $database_file);
						$sth_dest->execute($query_dest_values);
					}
					if ($globalMasterSource && $values['registration'] != null && isset($values['modes']) && $values['modes'] != '') {
						$modescountry = $Spotter->countryFromAircraftRegistration($values['registration']);
						$query_modes_values = array(':registration' => $values['registration'],':modes' => $values['modes'],':modescountry' => $modescountry,':icaotypecode' => $values['icao'],':source' => $database_file);
						$sth_modes->execute($query_modes_values);
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
  
		/*
		$query = 'TRUNCATE TABLE airport';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                */
                /*
		$query = 'ALTER TABLE airport DROP INDEX icaoidx';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                */

		$query_dest = "INSERT INTO airport (name,city,country,iata,icao,latitude,longitude,altitude,type,home_link,wikipedia_link,image_thumb,image)
		    VALUES (:name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image_thumb, :image)";
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
				$row['type'] = 'military';
			} elseif ($row['type'] == 'http://dbpedia.org/resource/Airport' || $row['type'] == 'Civil' || $row['type'] == 'Public use' || $row['type'] == 'Public' || $row['type'] == 'http://dbpedia.org/resource/Civilian' || $row['type'] == 'Public, Civilian' || $row['type'] == 'Public / Military' || $row['type'] == 'Private & Civilian' || $row['type'] == 'Civilian and Military' || $row['type'] == 'Public/military' || $row['type'] == 'Active With Few Facilities' || $row['type'] == '?ivilian' || $row['type'] == 'Civil/Military' || $row['type'] == 'NA' || $row['type'] == 'Public/Military') {
				$row['type'] = 'small_airport';
			}
			
			$row['city'] = urldecode(str_replace('_',' ',str_replace('http://dbpedia.org/resource/','',$row['city'])));
			$query_dest_values = array(':name' => $row['name'],':iata' => $row['iata'],':icao' => $row['icao'],':latitude' => $row['latitude'],':longitude' => $row['longitude'],':altitude' => round($row['altitude']),':type' => $row['type'],':city' => $row['city'],':country' => $row['country'],':home_link' => $row['homepage'],':wikipedia_link' => $row['wikipedia_page'],':image' => $row['image'],':image_thumb' => $row['image_thumb']);
			//print_r($query_dest_values);
			
			if ($row['icao'] != '') {
				try {
					$sth = $Connection->db->prepare('SELECT COUNT(*) FROM airport WHERE icao = :icao');
					$sth->execute(array(':icao' => $row['icao']));
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
					if ($sth->fetchColumn() > 0) {
						// Update ?
						$query = 'UPDATE airport SET type = :type WHERE icao = :icao';
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':icao' => $row['icao'],':type' => $row['type']));
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
						echo $row['icao'].' : '.$row['type']."\n";
					} else {
						try {
							$sth_dest->execute($query_dest_values);
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
					}
				}
			}

			$i++;
		}
		if ($globalTransaction) $Connection->db->commit();
		/*
		echo "Delete duplicate rows...\n";
		$query = 'ALTER IGNORE TABLE airport ADD UNIQUE INDEX icaoidx (icao)';
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                */


		/*
		if ($globalDebug) echo "Insert Not available Airport...\n";
		$query = "INSERT INTO airport (airport_id,name,city,country,iata,icao,latitude,longitude,altitude,type,home_link,wikipedia_link,image,image_thumb)
		    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image, :image_thumb)";
		$query_values = array(':airport_id' => $i, ':name' => 'Not available',':iata' => 'NA',':icao' => 'NA',':latitude' => '0',':longitude' => '0',':altitude' => '0',':type' => 'NA',':city' => 'N/A',':country' => 'N/A',':home_link' => '',':wikipedia_link' => '',':image' => '',':image_thumb' => '');
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                */
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
						$sth = $Connection->db->prepare('SELECT COUNT(*) FROM airport WHERE icao = :icao');
						$sth->execute(array(':icao' => $data['ident']));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					if ($sth->fetchColumn() > 0) {
						$query = 'UPDATE airport SET type = :type WHERE icao = :icao';
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':icao' => $data['ident'],':type' => $data['type']));
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
					} else {
						if ($data['gps_code'] == $data['ident']) {
						$query = "INSERT INTO airport (name,city,country,iata,icao,latitude,longitude,altitude,type,home_link,wikipedia_link)
						    VALUES (:name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link)";
						$query_values = array(':name' => $data['name'],':iata' => $data['iata_code'],':icao' => $data['gps_code'],':latitude' => $data['latitude_deg'],':longitude' => $data['longitude_deg'],':altitude' => round($data['elevation_ft']),':type' => $data['type'],':city' => $data['municipality'],':country' => $data['iso_country'],':home_link' => $data['home_link'],':wikipedia_link' => $data['wikipedia_link']);
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

					$query = 'UPDATE airport SET city = :city, country = :country WHERE icao = :icao';
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
			$sth = $Connection->db->prepare("SELECT icao FROM airport WHERE name LIKE '%Air Base%'");
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$query2 = 'UPDATE airport SET type = :type WHERE icao = :icao';
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
		global $tmp_dir, $globalTransaction;
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

	public static function diagrams_fam() {
		global $tmp_dir, $globalTransaction;
		$delimiter = " ";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'diagramspdf', 'r')) !== FALSE)
		{
			$Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				$query = 'UPDATE airport SET diagram_pdf = :diagrampdf, diagram_png = :diagrampng WHERE icao = :icao';
				$icao = str_replace('.pdf','',$data[2]);
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute(array(':icao' => $icao,':diagrampdf' => 'https://data.flightairmap.com/data/diagrams/'.$icao.'.pdf',':diagrampng' => 'https://data.flightairmap.com/data/diagrams/'.$icao.'.png'));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
					return "error : ".$e->getMessage();
				}
			}
			fclose($handle);
			$Connection->db->commit();
		}
		return '';
        }

	/*
	* This function use FAA public data.
	* With the help of data from other source, Mfr id is added to manufacturer table. Then ModeS with ICAO are added based on that.
	*/
	public static function modes_faa() {
		global $tmp_dir, $globalTransaction, $globalDebug;
		$query = "DELETE FROM aircraft_modes WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'website_faa'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$query = "DELETE FROM aircraft_owner WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'website_faa'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$delimiter = ",";
		$mfr = array();
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'MASTER.txt', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$query_search = 'SELECT icaotypecode FROM aircraft_modes WHERE registration = :registration AND Source <> :source LIMIT 1';
					try {
						$sths = $Connection->db->prepare($query_search);
						$sths->execute(array(':registration' => 'N'.$data[0],':source' => 'website_faa'));
					} catch(PDOException $e) {
						return "error s : ".$e->getMessage();
					}
					$result_search = $sths->fetchAll(PDO::FETCH_ASSOC);
					if (!empty($result_search)) {
						if ($globalDebug) echo '.';
							//if ($globalDBdriver == 'mysql') {
							//	$queryi = 'INSERT INTO faamfr (mfr,icao) VALUES (:mfr,:icao) ON DUPLICATE KEY UPDATE icao = :icao';
							//} else {
								$queryi = "INSERT INTO faamfr (mfr,icao) SELECT :mfr,:icao WHERE NOT EXISTS (SELECT 1 FROM faamfr WHERE mfr = :mfr);"; 
							//}
						try {
							$sthi = $Connection->db->prepare($queryi);
							$sthi->execute(array(':mfr' => $data[2],':icao' => $result_search[0]['icaotypecode']));
						} catch(PDOException $e) {
							return "error u : ".$e->getMessage();
						}
					} else {
						$query_search_mfr = 'SELECT icao FROM faamfr WHERE mfr = :mfr';
						try {
							$sthsm = $Connection->db->prepare($query_search_mfr);
							$sthsm->execute(array(':mfr' => $data[2]));
						} catch(PDOException $e) {
							return "error mfr : ".$e->getMessage();
						}
						$result_search_mfr = $sthsm->fetchAll(PDO::FETCH_ASSOC);
						if (!empty($result_search_mfr)) {
							if (trim($data[16]) == '' && trim($data[23]) != '') $data[16] = $data[23];
							if (trim($data[16]) == '' && trim($data[15]) != '') $data[16] = $data[15];
							$queryf = 'INSERT INTO aircraft_modes (FirstCreated,LastModified,ModeS,ModeSCountry,Registration,ICAOTypeCode,Source) VALUES (:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:source)';
							try {
								$sthf = $Connection->db->prepare($queryf);
								$sthf->execute(array(':FirstCreated' => $data[16],':LastModified' => $data[15],':ModeS' => $data[33],':ModeSCountry' => $data[14], ':Registration' => 'N'.$data[0],':ICAOTypeCode' => $result_search_mfr[0]['icao'],':source' => 'website_faa'));
							} catch(PDOException $e) {
								return "error f : ".$e->getMessage();
							}
						}
					}
					if (strtotime($data[29]) > time()) {
						if ($globalDebug) echo 'i';
						$query = 'INSERT INTO aircraft_owner (registration,base,owner,date_first_reg,Source) VALUES (:registration,:base,:owner,:date_first_reg,:source)';
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':registration' => 'N'.$data[0],':base' => $data[9],':owner' => ucwords(strtolower($data[6])),':date_first_reg' => date('Y-m-d',strtotime($data[23])), ':source' => 'website_faa'));
						} catch(PDOException $e) {
							return "error i : ".$e->getMessage();
						}
					}
				}
				if ($i % 90 == 0) {
					if ($globalTransaction) $Connection->db->commit();
					if ($globalTransaction) $Connection->db->beginTransaction();
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
	}

	public static function modes_fam() {
		global $tmp_dir, $globalTransaction;
		$query = "DELETE FROM aircraft_modes WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':source' => 'website_fam'));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'modes.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					if ($data[1] == 'NULL') $data[1] = $data[0];
					$query = 'INSERT INTO aircraft_modes (FirstCreated,LastModified,ModeS,ModeSCountry,Registration,ICAOTypeCode,type_flight,Source) VALUES (:FirstCreated,:LastModified,:ModeS,:ModeSCountry,:Registration,:ICAOTypeCode,:type_flight,:source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':FirstCreated' => $data[0],':LastModified' => $data[1],':ModeS' => $data[2],':ModeSCountry' => $data[3], ':Registration' => $data[4],':ICAOTypeCode' => $data[5],':type_flight' => $data[6],':source' => 'website_fam'));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
	}

	public static function airlines_fam() {
		global $tmp_dir, $globalTransaction, $globalDBdriver;
		$Connection = new Connection();
		/*
		if ($globalDBdriver == 'mysql') {
			$query = "LOCK TABLE airlines WRITE";
		} else {
			$query = "LOCK TABLE airlines IN ACCESS EXCLUSIVE WORK";
		}
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		*/
		$query = "DELETE FROM airlines WHERE forsource IS NULL";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$delimiter = "\t";
		if (($handle = fopen($tmp_dir.'airlines.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					if ($data[1] == 'NULL') $data[1] = $data[0];
					$query = 'INSERT INTO airlines (airline_id,name,alias,iata,icao,callsign,country,active,type,home_link,wikipedia_link,alliance,ban_eu) VALUES (0,:name,:alias,:iata,:icao,:callsign,:country,:active,:type,:home,:wikipedia_link,:alliance,:ban_eu)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':name' => $data[0],':alias' => $data[1],':iata' => $data[2],':icao' => $data[3], ':callsign' => $data[4],':country' => $data[5],':active' => $data[6],':type' => $data[7],':home' => $data[8],':wikipedia_link' => $data[9],':alliance' => $data[10],':ban_eu' => $data[11]));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		/*
		$query = "UNLOCK TABLES";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		*/
		return '';
        }
        
	public static function owner_fam() {
		global $tmp_dir, $globalTransaction;
		$query = "DELETE FROM aircraft_owner WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':source' => 'website_fam'));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'owners.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$query = 'INSERT INTO aircraft_owner (registration,base,owner,date_first_reg,Source) VALUES (:registration,:base,:owner,NULL,:source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':registration' => $data[0],':base' => $data[1],':owner' => $data[2], ':source' => 'website_fam'));
					} catch(PDOException $e) {
						//print_r($data);
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
        }

	public static function routes_fam() {
		global $tmp_dir, $globalTransaction, $globalDebug;
		$query = "DELETE FROM routes WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':source' => 'website_fam'));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'routes.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$data = array_map(function($v) { return $v === 'NULL' ? NULL : $v; },$data);
					$query = 'INSERT INTO routes (CallSign,Operator_ICAO,FromAirport_ICAO,FromAirport_Time,ToAirport_ICAO,ToAirport_Time,RouteStop,Source) VALUES (:CallSign,:Operator_ICAO,:FromAirport_ICAO,:FromAirport_Time,:ToAirport_ICAO,:ToAirport_Time,:RouteStop,:source)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':CallSign' => $data[0],':Operator_ICAO' => $data[1],':FromAirport_ICAO' => $data[2],':FromAirport_Time' => $data[3], ':ToAirport_ICAO' => $data[4],':ToAirport_Time' => $data[5],':RouteStop' => $data[6],':source' => 'website_fam'));
					} catch(PDOException $e) {
						if ($globalDebug) echo "error: ".$e->getMessage()." - data: ".implode(',',$data);
						die();
					}
				}
				if ($globalTransaction && $i % 2000 == 0) {
					$Connection->db->commit();
					if ($globalDebug) echo '.';
					$Connection->db->beginTransaction();
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
	}

	public static function block_fam() {
		global $tmp_dir, $globalTransaction, $globalDebug;
		$query = "DELETE FROM aircraft_block WHERE Source = '' OR Source = :source";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':source' => 'website_fam'));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'block.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgets($handle, 1000)) !== FALSE)
			{
				$query = 'INSERT INTO aircraft_block (callSign,Source) VALUES (:callSign,:source)';
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute(array(':callSign' => trim($data),':source' => 'website_fam'));
				} catch(PDOException $e) {
					if ($globalDebug) echo "error: ".$e->getMessage()." - data: ".$data;
					die();
				}
				if ($globalTransaction && $i % 2000 == 0) {
					$Connection->db->commit();
					if ($globalDebug) echo '.';
					$Connection->db->beginTransaction();
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
        }

	public static function marine_identity_fam() {
		global $tmp_dir, $globalTransaction;
		$query = "TRUNCATE TABLE marine_identity";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		
		//update_db::unzip($out_file);
		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'marine_identity.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			//$Connection->db->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
			//$Connection->db->beginTransaction();
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$data = array_map(function($v) { return $v === 'NULL' ? NULL : $v; },$data);
					$query = 'INSERT INTO marine_identity (mmsi,imo,call_sign,ship_name,length,gross_tonnage,dead_weight,width,country,engine_power,type) VALUES (:mmsi,:imo,:call_sign,:ship_name,:length,:gross_tonnage,:dead_weight,:width,:country,:engine_power,:type)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':mmsi' => $data[0],':imo' => $data[1],':call_sign' => $data[2],':ship_name' => $data[3], ':length' => $data[4],':gross_tonnage' => $data[5],':dead_weight' => $data[6],':width' => $data[7],':country' => $data[8],':engine_power' => $data[9],':type' => $data[10]));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
        }

	public static function satellite_fam() {
		global $tmp_dir, $globalTransaction;
		$query = "TRUNCATE TABLE satellite";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$delimiter = "\t";
		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'satellite.tsv', 'r')) !== FALSE)
		{
			$i = 0;
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if ($i > 0) {
					$data = array_map(function($v) { return $v === 'NULL' ? NULL : $v; },$data);
					$query = 'INSERT INTO satellite (name, name_alternate, country_un, country_owner, owner, users, purpose, purpose_detailed, orbit, type, longitude_geo, perigee, apogee, eccentricity, inclination, period, launch_mass, dry_mass, power, launch_date, lifetime, contractor, country_contractor, launch_site, launch_vehicule, cospar, norad, comments, source_orbital, sources) 
					    VALUES (:name, :name_alternate, :country_un, :country_owner, :owner, :users, :purpose, :purpose_detailed, :orbit, :type, :longitude_geo, :perigee, :apogee, :eccentricity, :inclination, :period, :launch_mass, :dry_mass, :power, :launch_date, :lifetime, :contractor, :country_contractor, :launch_site, :launch_vehicule, :cospar, :norad, :comments, :source_orbital, :sources)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':name' => $data[0], ':name_alternate' => $data[1], ':country_un' => $data[2], ':country_owner' => $data[3], ':owner' => $data[4], ':users' => $data[5], ':purpose' => $data[6], ':purpose_detailed' => $data[7], ':orbit' => $data[8], ':type' => $data[9], ':longitude_geo' => $data[10], ':perigee' => !empty($data[11]) ? $data[11] : NULL, ':apogee' => !empty($data[12]) ? $data[12] : NULL, ':eccentricity' => $data[13], ':inclination' => $data[14], ':period' => !empty($data[15]) ? $data[15] : NULL, ':launch_mass' => !empty($data[16]) ? $data[16] : NULL, ':dry_mass' => !empty($data[17]) ? $data[17] : NULL, ':power' => !empty($data[18]) ? $data[18] : NULL, ':launch_date' => $data[19], ':lifetime' => $data[20], ':contractor' => $data[21],':country_contractor' => $data[22], ':launch_site' => $data[23], ':launch_vehicule' => $data[24], ':cospar' => $data[25], ':norad' => $data[26], ':comments' => $data[27], ':source_orbital' => $data[28], ':sources' => $data[29]));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			fclose($handle);
			if ($globalTransaction) $Connection->db->commit();
		}
		return '';
	}

	public static function banned_fam() {
		global $tmp_dir, $globalTransaction;
		$query = "UPDATE airlines SET ban_eu = 0";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}

		$Connection = new Connection();
		if (($handle = fopen($tmp_dir.'ban_eu.csv', 'r')) !== FALSE)
		{
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($data = fgetcsv($handle, 1000)) !== FALSE)
			{
				$query = 'UPDATE airlines SET ban_eu = 1 WHERE icao = :icao AND forsource IS NULL';
				if ($data[0] != '') {
					$icao = $data[0];
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':icao' => $icao));
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

	public static function satellite_ucsdb($filename) {
		global $tmp_dir, $globalTransaction;
		
		$query = "DELETE FROM satellite";
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
			while (($data = fgetcsv($handle, 1000,"\t")) !== FALSE)
			{
				if ($i > 0 && $data[0] != '') {
					$sources = trim($data[28].' '.$data[29].' '.$data[30].' '.$data[31].' '.$data[32].' '.$data[33]);
					$period = str_replace(',','',$data[14]);
					if (!empty($period) && strpos($period,'days')) $period = str_replace(' days','',$period)*24*60;
					if ($data[18] != '') $launch_date = date('Y-m-d',strtotime($data[18]));
					else $launch_date = NULL;
					$data = array_map(function($value) {
						return trim($value) === '' ? null : $value;
					}, $data);
					//print_r($data);
					$query = 'INSERT INTO satellite (name, name_alternate, country_un, country_owner, owner, users, purpose, purpose_detailed, orbit, type, longitude_geo, perigee, apogee, eccentricity, inclination, period, launch_mass, dry_mass, power, launch_date, lifetime, contractor, country_contractor, launch_site, launch_vehicule, cospar, norad, comments, source_orbital, sources) 
					    VALUES (:name, :name_alternate, :country_un, :country_owner, :owner, :users, :purpose, :purpose_detailed, :orbit, :type, :longitude_geo, :perigee, :apogee, :eccentricity, :inclination, :period, :launch_mass, :dry_mass, :power, :launch_date, :lifetime, :contractor, :country_contractor, :launch_site, :launch_vehicule, :cospar, :norad, :comments, :source_orbital, :sources)';
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute(array(':name' => $data[0], ':name_alternate' => '', ':country_un' => $data[1], ':country_owner' => $data[2], ':owner' => $data[3], ':users' => $data[4], ':purpose' => $data[5], ':purpose_detailed' => $data[6], ':orbit' => $data[7], ':type' => $data[8], ':longitude_geo' => $data[9], ':perigee' => !empty($data[10]) ? str_replace(',','',$data[10]) : NULL, ':apogee' => !empty($data[11]) ? str_replace(',','',$data[11]) : NULL, ':eccentricity' => $data[12], ':inclination' => $data[13], ':period' => !empty($period) ? $period : NULL, ':launch_mass' => !empty($data[15]) ? str_replace(array('+',','),'',$data[15]) : NULL, ':dry_mass' => !empty($data[16]) ? str_replace(array(',','-1900',' (BOL)',' (EOL)'),'',$data[16]) : NULL, ':power' => !empty($data[17]) ? str_replace(array(',',' (BOL)',' (EOL)'),'',$data[17]) : NULL, ':launch_date' => $launch_date, ':lifetime' => $data[19], ':contractor' => $data[20],':country_contractor' => $data[21], ':launch_site' => $data[22], ':launch_vehicule' => $data[23], ':cospar' => $data[24], ':norad' => $data[25], ':comments' => $data[26], ':source_orbital' => $data[27], ':sources' => $sources));
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

	public static function satellite_celestrak($filename) {
		global $tmp_dir, $globalTransaction, $globalDebug;
		$satcat_sources = array(
			'AB' => array('country' => 'Multinational', 'owner' => 'Arab Satellite Communications Org. (ASCO)'),
			'ABS' => array('country' => 'Multinational', 'owner' => 'Asia Broadcast Satellite Ltd.'),
			'AC' => array('country' => 'China', 'owner' => 'Asia Satellite Telecommunications Co. Ltd.'),
			'ALG' => array('country' => 'Algeria', 'owner' => ''),
			'ARGN' => array('country' => 'Argentina', 'owner' => ''),
			'ASRA' => array('country' => 'Austria', 'owner' => ''),
			'AUS' => array('country' => 'Australia', 'owner' => ''),
			'AZER' => array('country' => 'Azerbaijan', 'owner' => ''),
			'BEL' => array('country' => 'Belgium', 'owner' => ''),
			'BELA' => array('country' => 'Belarus', 'owner' => ''),
			'BERM' => array('country' => 'Bermuda', 'owner' => ''),
			'BOL' => array('country' => 'Bolivia', 'owner' => ''),
			'BUL' => array('country' => 'Bulgaria', 'owner' => ''),
			'BRAZ' => array('country' => 'Brazil', 'owner' => ''),
			'CA' => array('country' => 'Canada', 'owner' => ''),
			'CHBZ' => array('country' => 'China/Brazil', 'owner' => ''),
			'CHLE' => array('country' => 'Chile', 'owner' => ''),
			'CIS' => array('country' => 'Russia', 'owner' => ''),
			'COL' => array('country' => 'Colombia', 'owner' => ''),
			'CZCH' => array('country' => 'Czech Republic (former Czechoslovakia)', 'owner' => ''),
			'DEN' => array('country' => 'Denmark', 'owner' => ''),
			'ECU' => array('country' => 'Ecuador', 'owner' => ''),
			'EGYP' => array('country' => 'Egypt', 'owner' => ''),
			'ESA' => array('country' => 'Multinational', 'owner' => 'European Space Agency'),
			'ESRO' => array('country' => 'Multinational', 'owner' => 'European Space Research Organization'),
			'EST' => array('country' => 'Estonia','owner' => ''),
			'EUME' => array('country' => 'Multinational', 'owner' => 'EUMETSAT (European Organization for the Exploitation of Meteorological Satellites)'),
			'EUTE' => array('country' => 'Multinational', 'owner' => 'European Telecommunications Satellite Consortium (EUTELSAT)'),
			'FGER' => array('country' => 'France/Germany', 'owner' => ''),
			'FIN' => array('country' => 'Finland', 'owner' => ''),
			'FR' => array('country' => 'France', 'owner' => ''),
			'FRIT' => array('country' => 'France/Italy', 'owner' => ''),
			'GER' => array('country' => 'Germany', 'owner' => ''),
			'GLOB' => array('country' => 'USA', 'owner' => 'Globalstar'),
			'GREC' => array('country' => 'Greece', 'owner' => ''),
			'HUN' => array('country' => 'Hungary', 'owner' => ''),
			'IM' => array('country' => 'United Kingdom', 'owner' => 'INMARSAT, Ltd.'),
			'IND' => array('country' => 'India', 'owner' => ''),
			'INDO' => array('country' => 'Indonesia', 'owner' => ''),
			'IRAN' => array('country' => 'Iran', 'owner' => ''),
			'IRAQ' => array('country' => 'Iraq', 'owner' => ''),
			'IRID' => array('country' => 'USA', 'owner' => 'Iridium Satellite LLC'),
			'ISRA' => array('country' => 'Israel', 'owner' => ''),
			'ISRO' => array('country' => 'India', 'owner' => 'Indian Space Research Organisation (ISRO)'),
			'ISS' => array('country' => 'Multinational', 'owner' => 'NASA/Multinational'),
			'IT' => array('country' => 'Italy', 'owner' => ''),
			'ITSO' => array('country' => 'USA', 'owner' => 'Intelsat, S.A.'),
			'JPN' => array('country' => 'Japan', 'owner' => ''),
			'KAZ' => array('country' => 'Kazakhstan', 'owner' => ''),
			'LAOS' => array('country' => 'Laos', 'owner' => ''),
			'LTU' => array('country' => 'Lithuania', 'owner' => ''),
			'LUXE' => array('country' => 'Luxembourg', 'owner' => ''),
			'MALA' => array('country' => 'Malaysia', 'owner' => ''),
			'MEX' => array('country' => 'Mexico', 'owner' => ''),
			'NATO' => array('country' => 'Multinational', 'owner' => 'North Atlantic Treaty Organization'),
			'NETH' => array('country' => 'Netherlands', 'owner' => ''),
			'NICO' => array('country' => 'USA', 'owner' => 'New ICO'),
			'NIG' => array('country' => 'Nigeria', 'owner' => ''),
			'NKOR' => array('country' => 'North Korea', 'owner' => ''),
			'NOR' => array('country' => 'Norway', 'owner' => ''),
			'O3B' => array('country' => 'United Kingdom', 'owner' => 'O3b Networks Ltd.'),
			'ORB' => array('country' => 'USA', 'owner' => 'ORBCOMM Inc.'),
			'PAKI' => array('country' => 'Pakistan', 'owner' => ''),
			'PERU' => array('country' => 'Peru', 'owner' => ''),
			'POL' => array('country' => 'Poland', 'owner' => ''),
			'POR' => array('country' => 'Portugal', 'owner' => ''),
			'PRC' => array('country' => 'China', 'owner' => ''),
			'PRES' => array('country' => 'Multinational', 'owner' => 'China/ESA'),
			'RASC' => array('country' => 'Multinational', 'owner' => 'Regional African Satellite Communications Organisation (RASCOM)'),
			'ROC' => array('country' => 'Taiwan', 'owner' => ''),
			'ROM' => array('country' => 'Romania', 'owner' => ''),
			'RP' => array('country' => 'Philippines', 'owner' => ''),
			'SAFR' => array('country' => 'South Africa', 'owner' => ''),
			'SAUD' => array('country' => 'Saudi Arabia', 'owner' => ''),
			'SEAL' => array('country' => 'USA', 'owner' => ''),
			'SES' => array('country' => 'Multinational', 'owner' => 'SES World Skies'),
			'SING' => array('country' => 'Singapore', 'owner' => ''),
			'SKOR' => array('country' => 'South Korea', 'owner' => ''),
			'SPN' => array('country' => 'Spain', 'owner' => ''),
			'STCT' => array('country' => 'Singapore/Taiwan', 'owner' => ''),
			'SWED' => array('country' => 'Sweden', 'owner' => ''),
			'SWTZ' => array('country' => 'Switzerland', 'owner' => ''),
			'THAI' => array('country' => 'Thailand', 'owner' => ''),
			'TMMC' => array('country' => 'Turkmenistan/Monaco', 'owner' => ''),
			'TURK' => array('country' => 'Turkey', 'owner' => ''),
			'UAE' => array('country' => 'United Arab Emirates', 'owner' => ''),
			'UK' => array('country' => 'United Kingdom', 'owner' => ''),
			'UKR' => array('country' => 'Ukraine', 'owner' => ''),
			'URY' => array('country' => 'Uruguay', 'owner' => ''),
			'US' => array('country' => 'USA', 'owner' => ''),
			'USBZ' => array('country' => 'USA/Brazil', 'owner' => ''),
			'VENZ' => array('country' => 'Venezuela', 'owner' => ''),
			'VTNM' => array('country' => 'Vietnam', 'owner' => '')
		);
		$satcat_launch_site = array(
			'AFETR' => 'Cape Canaveral',
			'AFWTR' => 'Vandenberg AFB',
			'CAS' => 'Canaries Airspace',
			'DLS' => 'Dombarovsky Air Base',
			'ERAS' => 'Eastern Range Airspace',
			'FRGUI' => 'Guiana Space Center',
			'HGSTR' => 'Hammaguira Space Track Range, Algeria',
			'JSC' => 'Jiuquan Satellite Launch Center',
			'KODAK' => 'Kodiak Launch Complex',
			'KSCUT' => 'Uchinoura Space Center',
			'KWAJ' => 'Kwajalein Island',
			'KYMSC' => 'Kapustin Yar Missile and Space Complex, Russia',
			'NSC' => 'Naro Space Center',
			'PLMSC' => 'Plesetsk Cosmodrome',
			'SEAL' => 'Sea Launch',
			'SEMLS' => 'Semnan Satellite Launch Site, Iran',
			'SNMLP' => 'San Marco Launch Platform, Indian Ocean (Kenya)',
			'SRILR' => 'Satish Dhawan Space Center',
			'SUBL' => 'Submarine Launch Platform (mobile), Russia',
			'SVOBO' => 'Svobodny Cosmodrome',
			'TAISC' => 'Taiyuan Launch Center',
			'TANSC' => 'Tanegashima Space Center',
			'TYMSC' => 'Baikonur Cosmodrome',
			'VOSTO' => 'Vostochny Cosmodrome',
			'WLPIS' => 'Wallops Island Flight Facility',
			'WOMRA' => 'Woomera, Australia',
			'WRAS' => 'Western Range Airspace',
			'WSC' => 'Wenchang Satellite Launch Center',
			'XICLF' => 'Xichang Satellite Launch Center',
			'YAVNE' => 'Palmachim Launch Complex',
			'YUN' => 'Yunsong Launch Site'
		);

		/*
		$query = "DELETE FROM satellite";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':source' => $filename));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		*/
		
		$Connection = new Connection();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			$i = 0;
			//$Connection->db->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
			//$Connection->db->beginTransaction();
			while (($data = fgets($handle, 1000)) !== FALSE)
			{
				if ($data != '') {
				$result = array();
				$result['cospar'] = trim(substr($data,0,11));
				$result['norad'] = trim(substr($data,13,6));
				$result['operational'] = trim(substr($data,21,1));
				$result['name'] = trim(substr($data,23,24));
				/*
				    * R/B(1) = Rocket body, first stage
				    * R/B(2) = Rocket body, second stage
				    * DEB = Debris
				    * PLAT = Platform
				    * Items in parentheses are alternate names
				    * Items in brackets indicate type of object
				    (e.g., BREEZE-M DEB [TANK] = tank)
				    * An ampersand (&) indicates two or more objects are attached
				*/
				
				$owner_code = trim(substr($data,49,5));
				
				if (!isset($satcat_sources[$owner_code]) && $owner_code != 'TBD') {
					if ($globalDebug) echo $data.'owner_code: '.$owner_code."\n";
				}
				if (!isset($satcat_launch_site[trim(substr($data,68,5))])) {
					if ($globalDebug) echo 'launch_site_code: '.trim(substr($data,68,5))."\n";
				}
				
				if ($owner_code != 'TBD' && isset($satcat_sources[$owner_code]) && isset($satcat_launch_site[trim(substr($data,68,5))])) {
					$result['country_owner'] = $satcat_sources[$owner_code]['country'];
					$result['owner'] = $satcat_sources[$owner_code]['owner'];
					$result['launch_date'] = trim(substr($data,56,10));
					$launch_site_code = trim(substr($data,68,5));
					$result['launch_site'] = $satcat_launch_site[$launch_site_code];
					$result['lifetime'] = trim(substr($data,75,10));
					$result['period'] = trim(substr($data,87,7));
					$result['inclination'] = trim(substr($data,96,5));
					$result['apogee'] = trim(substr($data,103,6));
					$result['perigee'] = trim(substr($data,111,6));
					//$result['radarcross'] = trim(substr($data,119,8));
					$result['status'] = trim(substr($data,129,3));
					//print_r($result);
					$result = array_map(function($value) {
						return trim($value) === '' ? null : $value;
					}, $result);
					//print_r($data);
					if ($result['operational'] != 'D') {
						$query = "SELECT * FROM satellite WHERE cospar = :cospar LIMIT 1";
						try {
							$Connection = new Connection();
							$sth = $Connection->db->prepare($query);
							$sth->execute(array(':cospar' => $result['cospar']));
							$exist = $sth->fetchAll(PDO::FETCH_ASSOC);
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
						if (empty($exist)) {
							$query = 'INSERT INTO satellite (name, name_alternate, country_un, country_owner, owner, users, purpose, purpose_detailed, orbit, type, longitude_geo, perigee, apogee, eccentricity, inclination, period, launch_mass, dry_mass, power, launch_date, lifetime, contractor, country_contractor, launch_site, launch_vehicule, cospar, norad, comments, source_orbital, sources) 
							    VALUES (:name, :name_alternate, :country_un, :country_owner, :owner, :users, :purpose, :purpose_detailed, :orbit, :type, :longitude_geo, :perigee, :apogee, :eccentricity, :inclination, :period, :launch_mass, :dry_mass, :power, :launch_date, :lifetime, :contractor, :country_contractor, :launch_site, :launch_vehicule, :cospar, :norad, :comments, :source_orbital, :sources)';
							try {
								$sth = $Connection->db->prepare($query);
								$sth->execute(array(
								    ':name' => $result['name'], ':name_alternate' => '', ':country_un' => '', ':country_owner' => $result['country_owner'], ':owner' => $result['owner'], ':users' => '', ':purpose' => '', ':purpose_detailed' => '', ':orbit' => $result['status'],
								    ':type' => '', ':longitude_geo' => NULL, ':perigee' => !empty($result['perigee']) ? $result['perigee'] : NULL, ':apogee' => !empty($result['apogee']) ? $result['apogee'] : NULL, ':eccentricity' => NULL, ':inclination' => $result['inclination'],
								    ':period' => !empty($result['period']) ? $result['period'] : NULL, ':launch_mass' => NULL, ':dry_mass' => NULL, ':power' => NULL, ':launch_date' => $result['launch_date'], ':lifetime' => $result['lifetime'], 
								    ':contractor' => '',':country_contractor' => '', ':launch_site' => $result['launch_site'], ':launch_vehicule' => '', ':cospar' => $result['cospar'], ':norad' => $result['norad'], ':comments' => '', ':source_orbital' => '', ':sources' => ''
								    )
								);
							} catch(PDOException $e) {
								return "error : ".$e->getMessage();
							}
						} elseif ($exist[0]['name'] != $result['name'] && $exist[0]['name_alternate'] != $result['name']) {
							$query = "UPDATE satellite SET name_alternate = :name_alternate WHERE cospar = :cospar";
							try {
								$Connection = new Connection();
								$sth = $Connection->db->prepare($query);
								$sth->execute(array(':name_alternate' => $result['name'],':cospar' => $result['cospar']));
							} catch(PDOException $e) {
								return "error : ".$e->getMessage();
							}
						}
					}
				}
				}
				$i++;
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
/*
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
*/
       /**
        * Get data from form result
        * @param String $url form URL
        * @return String the result
        */
/*
        private static function getData($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
                return curl_exec($ch);
        }
*/
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

		$query_dest = 'INSERT INTO waypoints (ident,latitude,longitude,control,usage) VALUES (:ident, :latitude, :longitude, :control, :usage)';
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

	public static function update_fires() {
		global $tmp_dir, $globalTransaction, $globalDebug;
		require_once(dirname(__FILE__).'/../require/class.Source.php');
		$delimiter = ',';
		$Common = new Common();
		$Common->download('http://firms.modaps.eosdis.nasa.gov/active_fire/viirs/text/VNP14IMGTDL_NRT_Global_24h.csv',$tmp_dir.'fires.csv');
		$Connection = new Connection();
		$Source = new Source();
		$Source->deleteLocationByType('fires');
		$i = 0;
		if (($handle = fopen($tmp_dir.'fires.csv','r')) !== false) {
			if ($globalTransaction) $Connection->db->beginTransaction();
			while (($row = fgetcsv($handle,1000)) !== false) {
				if ($i > 0 && $row[0] != '' && $row[8] != 'low') {
					$description = array('bright_t14' => $row[2],'scan' => $row[3],'track' => $row[4],'sat' => $row[7],'confidence' => $row[8],'version' => $row[9],'bright_t15' => $row[10],'frp' => $row[11],'daynight' => $row[12]);
					$query = "INSERT INTO source_location (name,latitude,longitude,altitude,country,city,logo,source,type,source_id,last_seen,location_id,description) VALUES (:name,:latitude,:longitude,:altitude,:country,:city,:logo,:source,:type,:source_id,:last_seen,:location_id,:description)";
					$query_values = array(':name' => '',':latitude' => $row[0], ':longitude' => $row[1],':altitude' => null,':city' => '',':country' => '',':logo' => 'fire.png',':source' => 'NASA',':type' => 'fires',':source_id' => 0,':last_seen' => $row[5].' '.substr($row[6],0,2).':'.substr($row[6],2,2),':location_id' => 0,':description' => json_encode($description));
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute($query_values);
					} catch(PDOException $e) {
						echo "error : ".$e->getMessage();
					}
				}
				$i++;
			}
			if ($globalTransaction) $Connection->db->commit();
		}
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
		update_db::download('http://data.flightairmap.com/data/notam.txt.gz.md5',$tmp_dir.'notam.txt.gz.md5');
		$error = '';
		if (file_exists($tmp_dir.'notam.txt.gz.md5')) {
			$notam_md5_file = explode(' ',file_get_contents($tmp_dir.'notam.txt.gz.md5'));
			$notam_md5 = $notam_md5_file[0];
			if (!update_db::check_notam_version($notam_md5)) {
				update_db::download('http://data.flightairmap.com/data/notam.txt.gz',$tmp_dir.'notam.txt.gz');
				if (file_exists($tmp_dir.'notam.txt.gz')) {
					if (md5_file($tmp_dir.'notam.txt.gz') == $notam_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.'notam.txt.gz');
						if ($globalDebug) echo "Add to DB...";
						//$error = create_db::import_file($tmp_dir.'notam.sql');
						$NOTAM = new NOTAM();
						$NOTAM->updateNOTAMfromTextFile($tmp_dir.'notam.txt');
						update_db::insert_notam_version($notam_md5);
					} else $error = "File ".$tmp_dir.'notam.txt.gz'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.'notam.txt.gz'." doesn't exist. Download failed.";
			} elseif ($globalDebug) echo "No new version.";
		} else $error = "File ".$tmp_dir.'notam.txt.gz.md5'." doesn't exist. Download failed.";
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
		if (extension_loaded('zip')) {
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
		} else $error = "ZIP module not loaded but required for IVAO.";
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
		update_db::download('http://data.flightairmap.com/data/schedules/oneworld.csv.gz',$tmp_dir.'oneworld.csv.gz');
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
		update_db::download('http://data.flightairmap.com/data/schedules/skyteam.csv.gz',$tmp_dir.'skyteam.csv.gz');
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
//		update_db::download('http://planebase.biz/sqb.php?f=basestationall.zip',$tmp_dir.'basestation_latest.zip','http://planebase.biz/bstnsqb');
		update_db::download('http://data.flightairmap.com/data/BaseStation.sqb.gz',$tmp_dir.'BaseStation.sqb.gz');

//		if (file_exists($tmp_dir.'basestation_latest.zip')) {
		if (file_exists($tmp_dir.'BaseStation.sqb.gz')) {
			if ($globalDebug) echo "Unzip...";
//			update_db::unzip($tmp_dir.'basestation_latest.zip');
			update_db::gunzip($tmp_dir.'BaseStation.sqb.gz');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_modes_sqlite_to_dest($tmp_dir.'BaseStation.sqb');
//			$error = update_db::retrieve_modes_sqlite_to_dest($tmp_dir.'basestation.sqb');
		} else $error = "File ".$tmp_dir.'basestation_latest.zip'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_ModeS_faa() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Modes FAA: Download...";
		update_db::download('http://registry.faa.gov/database/ReleasableAircraft.zip',$tmp_dir.'ReleasableAircraft.zip');
		if (file_exists($tmp_dir.'ReleasableAircraft.zip')) {
			if ($globalDebug) echo "Unzip...";
			update_db::unzip($tmp_dir.'ReleasableAircraft.zip');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::modes_faa();
		} else $error = "File ".$tmp_dir.'ReleasableAircraft.zip'." doesn't exist. Download failed.";
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
		global $tmp_dir, $globalDebug, $globalMasterSource;
		
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
		if ($globalDebug) echo "Owner Isle of Man: Download...";
		update_db::download('http://antonakis.co.uk/registers/IsleOfMan.txt',$tmp_dir.'owner_m.csv');
		if (file_exists($tmp_dir.'owner_m.csv')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::retrieve_owner($tmp_dir.'owner_m.csv','M');
		} else $error = "File ".$tmp_dir.'owner_m.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalMasterSource) {
			if ($globalDebug) echo "ModeS Netherlands: Download...";
			update_db::download('http://antonakis.co.uk/registers/Netherlands.txt',$tmp_dir.'owner_ph.csv');
			if (file_exists($tmp_dir.'owner_ph.csv')) {
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::retrieve_owner($tmp_dir.'owner_ph.csv','PH');
			} else $error = "File ".$tmp_dir.'owner_ph.csv'." doesn't exist. Download failed.";
			if ($error != '') {
				return $error;
			} elseif ($globalDebug) echo "Done\n";
			if ($globalDebug) echo "ModeS Denmark: Download...";
			update_db::download('http://antonakis.co.uk/registers/Denmark.txt',$tmp_dir.'owner_oy.csv');
			if (file_exists($tmp_dir.'owner_oy.csv')) {
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::retrieve_owner($tmp_dir.'owner_oy.csv','OY');
			} else $error = "File ".$tmp_dir.'owner_oy.csv'." doesn't exist. Download failed.";
			if ($error != '') {
				return $error;
			} elseif ($globalDebug) echo "Done\n";
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
		$error = '';
		if ($globalDebug) echo "Translation from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/translation.tsv.gz',$tmp_dir.'translation.tsv.gz');
		update_db::download('http://data.flightairmap.com/data/translation.tsv.gz.md5',$tmp_dir.'translation.tsv.gz.md5');
		if (file_exists($tmp_dir.'translation.tsv.gz') && file_exists($tmp_dir.'translation.tsv.gz')) {
			$translation_md5_file = explode(' ',file_get_contents($tmp_dir.'translation.tsv.gz.md5'));
			$translation_md5 = $translation_md5_file[0];
			if (md5_file($tmp_dir.'translation.tsv.gz') == $translation_md5) {
				if ($globalDebug) echo "Gunzip...";
				update_db::gunzip($tmp_dir.'translation.tsv.gz');
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::translation_fam();
			} else $error = "File ".$tmp_dir.'translation.tsv.gz'." md5 failed. Download failed.";
		} else $error = "File ".$tmp_dir.'translation.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_ModeS_fam() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "ModeS from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/modes.tsv.gz',$tmp_dir.'modes.tsv.gz');
		update_db::download('http://data.flightairmap.com/data/modes.tsv.gz.md5',$tmp_dir.'modes.tsv.gz.md5');
		if (file_exists($tmp_dir.'modes.tsv.gz') && file_exists($tmp_dir.'modes.tsv.gz.md5')) {
			$modes_md5_file = explode(' ',file_get_contents($tmp_dir.'modes.tsv.gz.md5'));
			$modes_md5 = $modes_md5_file[0];
			if (md5_file($tmp_dir.'modes.tsv.gz') == $modes_md5) {
				if ($globalDebug) echo "Gunzip...";
				update_db::gunzip($tmp_dir.'modes.tsv.gz');
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::modes_fam();
			} else $error = "File ".$tmp_dir.'modes.tsv.gz'." md5 failed. Download failed.";
		} else $error = "File ".$tmp_dir.'modes.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_airlines_fam() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Airlines from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/airlines.tsv.gz.md5',$tmp_dir.'airlines.tsv.gz.md5');
		if (file_exists($tmp_dir.'airlines.tsv.gz.md5')) {
			$airlines_md5_file = explode(' ',file_get_contents($tmp_dir.'airlines.tsv.gz.md5'));
			$airlines_md5 = $airlines_md5_file[0];
			if (!update_db::check_airlines_version($airlines_md5)) {
				update_db::download('http://data.flightairmap.com/data/airlines.tsv.gz',$tmp_dir.'airlines.tsv.gz');
				if (file_exists($tmp_dir.'airlines.tsv.gz')) {
					if (md5_file($tmp_dir.'airlines.tsv.gz') == $airlines_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.'airlines.tsv.gz');
						if ($globalDebug) echo "Add to DB...";
						$error = update_db::airlines_fam();
						update_db::insert_airlines_version($airlines_md5);
					} else $error = "File ".$tmp_dir.'airlines.tsv.gz'." md5 failed. Download failed.";
			    } else $error = "File ".$tmp_dir.'airlines.tsv.gz'." doesn't exist. Download failed.";
			} elseif ($globalDebug) echo "No update.";
		} else $error = "File ".$tmp_dir.'airlines.tsv.gz.md5'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} else {
			if ($globalDebug) echo "Done\n";
		}
		return '';
	}

	public static function update_owner_fam() {
		global $tmp_dir, $globalDebug, $globalOwner;
		if ($globalDebug) echo "owner from FlightAirMap website : Download...";
		$error = '';
		if ($globalOwner === TRUE) {
			update_db::download('http://data.flightairmap.com/data/owners_all.tsv.gz',$tmp_dir.'owners.tsv.gz');
			update_db::download('http://data.flightairmap.com/data/owners_all.tsv.gz.md5',$tmp_dir.'owners.tsv.gz.md5');
		} else {
			update_db::download('http://data.flightairmap.com/data/owners.tsv.gz',$tmp_dir.'owners.tsv.gz');
			update_db::download('http://data.flightairmap.com/data/owners.tsv.gz.md5',$tmp_dir.'owners.tsv.gz.md5');
		}
		if (file_exists($tmp_dir.'owners.tsv.gz') && file_exists($tmp_dir.'owners.tsv.gz.md5')) {
			$owners_md5_file = explode(' ',file_get_contents($tmp_dir.'owners.tsv.gz.md5'));
			$owners_md5 = $owners_md5_file[0];
			if (md5_file($tmp_dir.'owners.tsv.gz') == $owners_md5) {
				if ($globalDebug) echo "Gunzip...";
				update_db::gunzip($tmp_dir.'owners.tsv.gz');
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::owner_fam();
			} else $error = "File ".$tmp_dir.'owners.tsv.gz'." md5 failed. Download failed.";
		} else $error = "File ".$tmp_dir.'owners.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_routes_fam() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Routes from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/routes.tsv.gz',$tmp_dir.'routes.tsv.gz');
		update_db::download('http://data.flightairmap.com/data/routes.tsv.gz.md5',$tmp_dir.'routes.tsv.gz.md5');
		if (file_exists($tmp_dir.'routes.tsv.gz') && file_exists($tmp_dir.'routes.tsv.gz.md5')) {
			$routes_md5_file = explode(' ',file_get_contents($tmp_dir.'routes.tsv.gz.md5'));
			$routes_md5 = $routes_md5_file[0];
			if (md5_file($tmp_dir.'routes.tsv.gz') == $routes_md5) {
				if ($globalDebug) echo "Gunzip...";
				update_db::gunzip($tmp_dir.'routes.tsv.gz');
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::routes_fam();
			} else $error = "File ".$tmp_dir.'routes.tsv.gz'." md5 failed. Download failed.";
		} else $error = "File ".$tmp_dir.'routes.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_block_fam() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Blocked aircraft from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/block.tsv.gz',$tmp_dir.'block.tsv.gz');
		update_db::download('http://data.flightairmap.com/data/block.tsv.gz.md5',$tmp_dir.'block.tsv.gz.md5');
		if (file_exists($tmp_dir.'block.tsv.gz') && file_exists($tmp_dir.'block.tsv.gz.md5')) {
			$block_md5_file = explode(' ',file_get_contents($tmp_dir.'block.tsv.gz.md5'));
			$block_md5 = $block_md5_file[0];
			if (md5_file($tmp_dir.'block.tsv.gz') == $block_md5) {
				if ($globalDebug) echo "Gunzip...";
				update_db::gunzip($tmp_dir.'block.tsv.gz');
				if ($globalDebug) echo "Add to DB...";
				$error = update_db::block_fam();
			} else $error = "File ".$tmp_dir.'block.tsv.gz'." md5 failed. Download failed.";
		} else $error = "File ".$tmp_dir.'block.tsv.gz'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_marine_identity_fam() {
		global $tmp_dir, $globalDebug;
		update_db::download('http://data.flightairmap.com/data/marine_identity.tsv.gz.md5',$tmp_dir.'marine_identity.tsv.gz.md5');
		if (file_exists($tmp_dir.'marine_identity.tsv.gz.md5')) {
			$marine_identity_md5_file = explode(' ',file_get_contents($tmp_dir.'marine_identity.tsv.gz.md5'));
			$marine_identity_md5 = $marine_identity_md5_file[0];
			if (!update_db::check_marine_identity_version($marine_identity_md5)) {
				if ($globalDebug) echo "Marine identity from FlightAirMap website : Download...";
				update_db::download('http://data.flightairmap.com/data/marine_identity.tsv.gz',$tmp_dir.'marine_identity.tsv.gz');
				if (file_exists($tmp_dir.'marine_identity.tsv.gz')) {
					if (md5_file($tmp_dir.'marine_identity.tsv.gz') == $marine_identity_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.'marine_identity.tsv.gz');
						if ($globalDebug) echo "Add to DB...";
						$error = update_db::marine_identity_fam();
					} else $error = "File ".$tmp_dir.'marine_identity.tsv.gz'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.'marine_identity.tsv.gz'." doesn't exist. Download failed.";
				if ($error != '') {
					return $error;
				} else {
					update_db::insert_marine_identity_version($marine_identity_md5);
					if ($globalDebug) echo "Done\n";
				}
			}
		}
		return '';
	}

	public static function update_satellite_fam() {
		global $tmp_dir, $globalDebug;
		update_db::download('http://data.flightairmap.com/data/satellite.tsv.gz.md5',$tmp_dir.'satellite.tsv.gz.md5');
		if (file_exists($tmp_dir.'satellite.tsv.gz.md5')) {
			$satellite_md5_file = explode(' ',file_get_contents($tmp_dir.'satellite.tsv.gz.md5'));
			$satellite_md5 = $satellite_md5_file[0];
			if (!update_db::check_satellite_version($satellite_md5)) {
				if ($globalDebug) echo "Satellite from FlightAirMap website : Download...";
				update_db::download('http://data.flightairmap.com/data/satellite.tsv.gz',$tmp_dir.'satellite.tsv.gz');
				if (file_exists($tmp_dir.'satellite.tsv.gz')) {
					if (md5_file($tmp_dir.'satellite.tsv.gz') == $satellite_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.'satellite.tsv.gz');
						if ($globalDebug) echo "Add to DB...";
						$error = update_db::satellite_fam();
					} else $error = "File ".$tmp_dir.'satellite.tsv.gz'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.'satellite.tsv.gz'." doesn't exist. Download failed.";
				if ($error != '') {
					return $error;
				} else {
					update_db::insert_satellite_version($satellite_md5);
					if ($globalDebug) echo "Done\n";
				}
			}
		}
		return '';
	}
	public static function update_diagrams_fam() {
		global $tmp_dir, $globalDebug;
		update_db::download('http://data.flightairmap.com/data/diagrams/diagramspdf.md5',$tmp_dir.'diagramspdf.md5');
		if (file_exists($tmp_dir.'diagramspdf.md5')) {
			$diagrams_md5_file = explode(' ',file_get_contents($tmp_dir.'diagramspdf.md5'));
			$diagrams_md5 = $diagrams_md5_file[0];
			if (!update_db::check_diagrams_version($diagrams_md5)) {
				if ($globalDebug) echo "Airports diagrams from FlightAirMap website : Download...";
				update_db::download('http://data.flightairmap.com/data/diagrams/diagramspdf',$tmp_dir.'diagramspdf');
				if (file_exists($tmp_dir.'diagramspdf')) {
					if (md5_file($tmp_dir.'diagramspdf') == $diagrams_md5) {
						if ($globalDebug) echo "Add to DB...";
						$error = update_db::diagrams_fam();
					} else $error = "File ".$tmp_dir.'diagramspdf'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.'diagramspdf'." doesn't exist. Download failed.";
				if ($error != '') {
					return $error;
				} else {
					update_db::insert_diagrams_version($diagrams_md5);
					if ($globalDebug) echo "Done\n";
				}
			}
		}
		return '';
	}
	public static function update_banned_fam() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Banned airlines in Europe from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/ban-eu.csv',$tmp_dir.'ban_eu.csv');
		if (file_exists($tmp_dir.'ban_eu.csv')) {
			//if ($globalDebug) echo "Gunzip...";
			//update_db::gunzip($tmp_dir.'ban_ue.csv');
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::banned_fam();
		} else $error = "File ".$tmp_dir.'ban_eu.csv'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_airspace_fam() {
		global $tmp_dir, $globalDebug, $globalDBdriver;
		include_once('class.create_db.php');
		$error = '';
		if ($globalDebug) echo "Airspace from FlightAirMap website : Download...";
		if ($globalDBdriver == 'mysql') {
			update_db::download('http://data.flightairmap.com/data/airspace_mysql.sql.gz.md5',$tmp_dir.'airspace.sql.gz.md5');
		} else {
			update_db::download('http://data.flightairmap.com/data/airspace_pgsql.sql.gz.md5',$tmp_dir.'airspace.sql.gz.md5');
		}
		if (file_exists($tmp_dir.'airspace.sql.gz.md5')) {
			$airspace_md5_file = explode(' ',file_get_contents($tmp_dir.'airspace.sql.gz.md5'));
			$airspace_md5 = $airspace_md5_file[0];
			if (!update_db::check_airspace_version($airspace_md5)) {
				if ($globalDBdriver == 'mysql') {
					update_db::download('http://data.flightairmap.com/data/airspace_mysql.sql.gz',$tmp_dir.'airspace.sql.gz');
				} else {
					update_db::download('http://data.flightairmap.com/data/airspace_pgsql.sql.gz',$tmp_dir.'airspace.sql.gz');
				}
				if (file_exists($tmp_dir.'airspace.sql.gz')) {
					if (md5_file($tmp_dir.'airspace.sql.gz') == $airspace_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.'airspace.sql.gz');
						if ($globalDebug) echo "Add to DB...";
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
						$error = create_db::import_file($tmp_dir.'airspace.sql');
						update_db::insert_airspace_version($airspace_md5);
					} else $error = "File ".$tmp_dir.'airspace.sql.gz'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.'airspace.sql.gz'." doesn't exist. Download failed.";
			}
		} else $error = "File ".$tmp_dir.'airspace.sql.gz.md5'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_geoid_fam() {
		global $tmp_dir, $globalDebug, $globalGeoidSource;
		$error = '';
		if ($globalDebug) echo "Geoid from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/geoid/'.$globalGeoidSource.'.pgm.gz.md5',$tmp_dir.$globalGeoidSource.'.pgm.gz.md5');
		if (file_exists($tmp_dir.$globalGeoidSource.'.pgm.gz.md5')) {
			$geoid_md5_file = explode(' ',file_get_contents($tmp_dir.$globalGeoidSource.'.pgm.gz.md5'));
			$geoid_md5 = $geoid_md5_file[0];
			if (!update_db::check_geoid_version($geoid_md5)) {
				update_db::download('http://data.flightairmap.com/data/geoid/'.$globalGeoidSource.'.pgm.gz',$tmp_dir.$globalGeoidSource.'.pgm.gz');
				if (file_exists($tmp_dir.$globalGeoidSource.'.pgm.gz')) {
					if (md5_file($tmp_dir.$globalGeoidSource.'.pgm.gz') == $geoid_md5) {
						if ($globalDebug) echo "Gunzip...";
						update_db::gunzip($tmp_dir.$globalGeoidSource.'.pgm.gz',dirname(__FILE__).'/../data/'.$globalGeoidSource.'.pgm');
						if (file_exists(dirname(__FILE__).'/../data/'.$globalGeoidSource.'.pgm')) {
							update_db::insert_geoid_version($geoid_md5);
						} else $error = "File data/".$globalGeoidSource.'.pgm'." doesn't exist. Gunzip failed.";
					} else $error = "File ".$tmp_dir.$globalGeoidSource.'.pgm.gz'." md5 failed. Download failed.";
				} else $error = "File ".$tmp_dir.$globalGeoidSource.'.pgm.gz'." doesn't exist. Download failed.";
			} elseif ($globalDebug) echo 'No new version'."\n";
		} else $error = "File ".$tmp_dir.$globalGeoidSource.'.pgm.gz.md5'." doesn't exist. Download failed.";
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
		'engineering.txt','education.txt','military.txt','radar.txt','cubesat.txt','other.txt','tle-new.txt','visual.txt','sarsat.txt','argos.txt','ses.txt','iridium-NEXT.txt','beidou.txt');
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

	public static function update_ucsdb() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Download UCS DB : Download...";
		update_db::download('https://s3.amazonaws.com/ucs-documents/nuclear-weapons/sat-database/5-9-19-update/UCS_Satellite_Database_officialname_4-1-2019.txt',$tmp_dir.'UCS_Satellite_Database_officialname_4-1-2019.txt');
		if (file_exists($tmp_dir.'UCS_Satellite_Database_officialname_4-1-2019.txt')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::satellite_ucsdb($tmp_dir.'UCS_Satellite_Database_officialname_4-1-2019.txt');
		} else $error = "File ".$tmp_dir.'UCS_Satellite_Database_officialname_4-1-2019.txt'." doesn't exist. Download failed.";
		if ($error != '') {
			echo $error."\n";
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_celestrak() {
		global $tmp_dir, $globalDebug;
		if ($globalDebug) echo "Download Celestrak DB : Download...";
		update_db::download('http://celestrak.com/pub/satcat.txt',$tmp_dir.'satcat.txt');
		if (file_exists($tmp_dir.'satcat.txt')) {
			if ($globalDebug) echo "Add to DB...";
			$error = update_db::satellite_celestrak($tmp_dir.'satcat.txt');
		} else $error = "File ".$tmp_dir.'satcat.txt'." doesn't exist. Download failed.";
		if ($error != '') {
			echo $error."\n";
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Models from FlightAirMap website : Download...";
		if (!is_writable(dirname(__FILE__).'/../models')) {
			if ($globalDebug) echo dirname(__FILE__).'/../models'.' is not writable !';
			return '';
		}
		update_db::download('http://data.flightairmap.com/data/models/models.md5sum',$tmp_dir.'models.md5sum');
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
				update_db::download('http://data.flightairmap.com/data/models/'.$key,dirname(__FILE__).'/../models/'.$key);
			}
			update_db::download('http://data.flightairmap.com/data/models/models.md5sum',dirname(__FILE__).'/../models/models.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		if ($globalDebug) echo "glTF 2.0 Models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/models/gltf2/models.md5sum',$tmp_dir.'modelsgltf2.md5sum');
		if (file_exists($tmp_dir.'modelsgltf2.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'modelsgltf2.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/gltf2/models.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/gltf2/models.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading model '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.com/data/models/gltf2/'.$key,dirname(__FILE__).'/../models/gltf2/'.$key);
				
			}
			update_db::download('http://data.flightairmap.com/data/models/gltf2/models.md5sum',dirname(__FILE__).'/../models/gltf2/models.md5sum');
		} else $error = "File ".$tmp_dir.'modelsgltf2.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}
	public static function update_weather_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Models from FlightAirMap website : Download...";
		if (!is_writable(dirname(__FILE__).'/../models/gltf2/weather')) {
			if ($globalDebug) echo dirname(__FILE__).'/../models/gltf2/weather'.' is not writable !';
			return '';
		}
		if ($globalDebug) echo "Weather Models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/models/gltf2/weather/models.md5sum',$tmp_dir.'modelsweather.md5sum');
		if (file_exists($tmp_dir.'modelsweather.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'modelsweather.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/gltf2/weather/models.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/gltf2/weather/models.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading model '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.com/data/models/gltf2/weather/'.$key,dirname(__FILE__).'/../models/gltf2/weather/'.$key);
				
			}
			update_db::download('http://data.flightairmap.com/data/models/gltf2/weather/models.md5sum',dirname(__FILE__).'/../models/gltf2/weather/models.md5sum');
		} else $error = "File ".$tmp_dir.'modelsweather.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_liveries() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if ($globalDebug) echo "Liveries from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/models/gltf2/liveries/liveries.md5sum',$tmp_dir.'liveries.md5sum');
		if (file_exists($tmp_dir.'liveries.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'liveries.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/gltf2/liveries/liveries.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/gltf2/liveries/liveries.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading liveries '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.com/data/models/gltf2/liveries/'.$key,dirname(__FILE__).'/../models/gltf2/liveries/'.$key);
				
			}
			update_db::download('http://data.flightairmap.com/data/models/gltf2/liveries/liveries.md5sum',dirname(__FILE__).'/../models/gltf2/liveries/liveries.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_space_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if (!is_writable(dirname(__FILE__).'/../models')) {
			if ($globalDebug) echo dirname(__FILE__).'/../models'.' is not writable !';
			return '';
		}
		if ($globalDebug) echo "Space models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/models/space/space_models.md5sum',$tmp_dir.'space_models.md5sum');
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
				update_db::download('http://data.flightairmap.com/data/models/space/'.$key,dirname(__FILE__).'/../models/space/'.$key);
				
			}
			update_db::download('http://data.flightairmap.com/data/models/space/space_models.md5sum',dirname(__FILE__).'/../models/space/space_models.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_vehicules_models() {
		global $tmp_dir, $globalDebug;
		$error = '';
		if (!is_writable(dirname(__FILE__).'/../models/vehicules')) {
			if ($globalDebug) echo dirname(__FILE__).'/../models/vehicules'.' is not writable !';
			return '';
		}
		if ($globalDebug) echo "Vehicules models from FlightAirMap website : Download...";
		update_db::download('http://data.flightairmap.com/data/models/vehicules/vehicules_models.md5sum',$tmp_dir.'vehicules_models.md5sum');
		if (file_exists($tmp_dir.'vehicules_models.md5sum')) {
			if ($globalDebug) echo "Check files...\n";
			$newmodelsdb = array();
			if (($handle = fopen($tmp_dir.'vehicules_models.md5sum','r')) !== FALSE) {
				while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
					$model = trim($row[2]);
					$newmodelsdb[$model] = trim($row[0]);
				}
			}
			$modelsdb = array();
			if (file_exists(dirname(__FILE__).'/../models/vehicules/vehicules_models.md5sum')) {
				if (($handle = fopen(dirname(__FILE__).'/../models/vehicules/vehicules_models.md5sum','r')) !== FALSE) {
					while (($row = fgetcsv($handle,1000," ")) !== FALSE) {
						$model = trim($row[2]);
						$modelsdb[$model] = trim($row[0]);
					}
				}
			}
			$diff = array_diff($newmodelsdb,$modelsdb);
			foreach ($diff as $key => $value) {
				if ($globalDebug) echo 'Downloading vehicules model '.$key.' ...'."\n";
				update_db::download('http://data.flightairmap.com/data/models/vehicules/'.$key,dirname(__FILE__).'/../models/vehicules/'.$key);
				
			}
			update_db::download('http://data.flightairmap.com/data/models/vehicules/vehicules_models.md5sum',dirname(__FILE__).'/../models/vehicules/vehicules_models.md5sum');
		} else $error = "File ".$tmp_dir.'models.md5sum'." doesn't exist. Download failed.";
		if ($error != '') {
			return $error;
		} elseif ($globalDebug) echo "Done\n";
		return '';
	}

	public static function update_aircraft() {
		global $tmp_dir, $globalDebug;
		date_default_timezone_set('UTC');
		$Common = new Common();
		$data = $Common->getData('https://www4.icao.int/doc8643/External/AircraftTypes','post',array('X-Requested-With: XMLHttpRequest','Accept: application/json, text/javascript, */*; q=0.01','Host: www4.icao.int','Origin: https://www.icao.int','Content-Length: 0'),'','','https://www.icao.int/publications/DOC8643/Pages/Search.aspx',60);
		$all = json_decode($data,true);
		$Connection = new Connection();
		$querychk = "SELECT COUNT(1) as nb FROM aircraft WHERE icao = :icao";
		$sth = $Connection->db->prepare($querychk);
		$queryins = "INSERT INTO aircraft (icao,type,manufacturer,aircraft_shadow,aircraft_description,engine_type,engine_count,wake_category,official_page) VALUES (:icao,:type,:manufacturer,:aircraft_shadow,:aircraft_description,:engine_type,:engine_count,:wake_category,'')";
		$queryup = "UPDATE aircraft SET type = :type WHERE icao = :icao";
		$sthins = $Connection->db->prepare($queryins);
		$sthup = $Connection->db->prepare($queryup);
		$allicao = array();
		foreach ($all as $model) {
			$icao = $model['Designator'];
			if (!isset($allicao[$icao])) {
				$aircraft_shadow = 'generic_'.substr($model['EngineType'],0,1).$model['EngineCount'].$model['WTC'].'.png';
				$allicao[$icao] = array(':icao' => $icao,':type' => $model['ModelFullName'],':manufacturer' => $model['ManufacturerCode'],':aircraft_shadow' => $aircraft_shadow,':aircraft_description' => $model['AircraftDescription'],':engine_type' => $model['EngineType'],':engine_count' => $model['EngineCount'],':wake_category' => $model['WTC']);
			} else {
				$allicao[$icao][':type'] = $allicao[$icao][':type'].'/'.$model['ModelFullName'];
			}
		}
		foreach ($allicao as $icao => $airdata) {
			try {
				$sth->execute(array(':icao' => $icao));
				$exist = $sth->fetchAll(PDO::FETCH_ASSOC);
				if ($exist[0]['nb'] == 0) {
					$sthins->execute($airdata);
				} else {
					$sthup->execute(array(':type' => $airdata[':type'],':icao' => $icao));
				}
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		
		/*
		foreach ($all as $model) {
			try {
				$sth->execute(array(':icao' => $model['Designator']));
				$exist = $sth->fetchAll(PDO::FETCH_ASSOC);
				if ($exist[0]['nb'] == 0) {
					echo 'ICAO: '.$model['Designator'].' is not available'."\n";
					$aircraft_shadow = 'generic_'.substr($model['EngineType'],0,1).$model['EngineCount'].$model['WTC'].'.png';
					$sthins->execute(array(':icao' => $model['Designator'],':type' => $model['ModelFullName'],':manufacturer' => $model['ManufacturerCode'],':aircraft_shadow' => $aircraft_shadow,':aircraft_description' => $model['AircraftDescription'],':engine_type' => $model['EngineType'],':engine_count' => $model['EngineCount'],':wake_category' => $model['WTC']));
				}
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		*/
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
	
	public static function create_airspace() {
		global $globalDBdriver, $globalDebug, $tmp_dir, $globalDBhost, $globalDBuser, $globalDBname, $globalDBpass, $globalDBport;
		$Connection = new Connection();
		if ($Connection->tableExists('airspace')) {
			if ($globalDBdriver == 'mysql') {
				$query = 'DROP TABLE geometry_columns; DROP TABLE spatial_ref_sys;DROP TABLE airspace;';
			} else {
				$query = 'DROP TABLE airspace';
			}
			try {
				$Connection = new Connection();
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		$Common = new Common();
		$airspace_lst = $Common->getData('https://raw.githubusercontent.com/XCSoar/xcsoar-data-repository/master/data/airspace.json');
		$airspace_json = json_decode($airspace_lst,true);
		foreach ($airspace_json['records'] as $airspace) {
			if ($globalDebug) echo $airspace['name']."...\n";
			update_db::download($airspace['uri'],$tmp_dir.$airspace['name']);
			if (file_exists($tmp_dir.$airspace['name'])) {
				file_put_contents($tmp_dir.$airspace['name'], utf8_encode(file_get_contents($tmp_dir.$airspace['name'])));
				//system('recode l9..utf8 '.$tmp_dir.$airspace['name']);
				if ($globalDBdriver == 'mysql') {
					system('ogr2ogr -update -append -f "MySQL" MySQL:"'.$globalDBname.',host='.$globalDBhost.',user='.$globalDBuser.',password='.$globalDBpass.',port='.$globalDBport.'" -nln airspace -nlt POLYGON -skipfailures -lco ENGINE=MyISAM "'.$tmp_dir.$airspace['name'].'"');
				} else {
					system('ogr2ogr -append -f "PostgreSQL" PG:"host='.$globalDBhost.' user='.$globalDBuser.' dbname='.$globalDBname.' password='.$globalDBpass.' port='.$globalDBport.'" -nln airspace -nlt POLYGON -skipfailures "'.$tmp_dir.$airspace['name'].'"');
				}
			}
		}
	}
	
	public static function fix_icaotype() {
		require_once(dirname(__FILE__).'/../require/class.Spotter.php');
		$Spotter = new Spotter();
		foreach ($Spotter->aircraft_correct_icaotype as $old => $new) {
			$query = 'UPDATE aircraft_modes SET ICAOTypeCode = :new WHERE ICAOTypeCode = :old';
			try {
				$Connection = new Connection();
				$sth = $Connection->db->prepare($query);
				$sth->execute(array(':new' => $new, ':old' => $old));
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}

	public static function check_last_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_db' AND value > DATE_SUB(NOW(), INTERVAL 15 DAY)";
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

	public static function check_airspace_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'airspace_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':version' => $version));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return true;
                else return false;
	}

	public static function check_geoid_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'geoid_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':version' => $version));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if ($row['nb'] > 0) return true;
                else return false;
	}

	public static function check_marine_identity_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'marine_identity_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return true;
		else return false;
	}

	public static function check_satellite_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'satellite_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return true;
		else return false;
	}
	public static function check_diagrams_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'diagrams_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return true;
		else return false;
	}

	public static function check_airlines_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'airlines_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return true;
		else return false;
	}

	public static function check_notam_version($version) {
		$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'notam_version' AND value = :version";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if ($row['nb'] > 0) return true;
		else return false;
	}

	public static function insert_airlines_version($version) {
		$query = "DELETE FROM config WHERE name = 'airlines_version';
			INSERT INTO config (name,value) VALUES ('airlines_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function insert_notam_version($version) {
		$query = "DELETE FROM config WHERE name = 'notam_version';
			INSERT INTO config (name,value) VALUES ('notam_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function insert_airspace_version($version) {
		$query = "DELETE FROM config WHERE name = 'airspace_version';
			INSERT INTO config (name,value) VALUES ('airspace_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	
	public static function insert_geoid_version($version) {
		$query = "DELETE FROM config WHERE name = 'geoid_version';
			INSERT INTO config (name,value) VALUES ('geoid_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute(array(':version' => $version));
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}

	public static function insert_marine_identity_version($version) {
		$query = "DELETE FROM config WHERE name = 'marine_identity_version';
			INSERT INTO config (name,value) VALUES ('marine_identity_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function insert_satellite_version($version) {
		$query = "DELETE FROM config WHERE name = 'satellite_version';
			INSERT INTO config (name,value) VALUES ('satellite_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public static function insert_diagrams_version($version) {
		$query = "DELETE FROM config WHERE name = 'diagrams_version';
			INSERT INTO config (name,value) VALUES ('diagrams_version',:version);";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute(array(':version' => $version));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function check_last_notam_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_notam_db' AND value > DATE_SUB(NOW(), INTERVAL 1 DAY)";
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

	public static function check_last_airspace_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_airspace_db' AND value > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_airspace_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '7 DAYS'";
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

	public static function insert_last_airspace_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_airspace_db';
			INSERT INTO config (name,value) VALUES ('last_update_airspace_db',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}

	public static function check_last_geoid_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_geoid' AND value > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_geoid' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '7 DAYS'";
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

	public static function insert_last_geoid_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_geoid';
			INSERT INTO config (name,value) VALUES ('last_update_geoid',NOW());";
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
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_owner_db' AND value > DATE_SUB(NOW(), INTERVAL 15 DAY)";
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

	public static function check_last_fires_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_fires' AND value > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_fires' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '1 HOUR'";
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

	public static function insert_last_fires_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_fires';
			INSERT INTO config (name,value) VALUES ('last_update_fires',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function check_last_airlines_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_airlines_db' AND value > DATE_SUB(NOW(), INTERVAL 15 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_airlines_db' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '15 DAYS'";
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

	public static function insert_last_airlines_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_airlines_db';
			INSERT INTO config (name,value) VALUES ('last_update_airlines_db',NOW());";
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
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_schedules' AND value > DATE_SUB(NOW(), INTERVAL 15 DAY)";
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
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_tle' AND value > DATE_SUB(NOW(), INTERVAL 7 DAY)";
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

	public static function check_last_ucsdb_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_ucsdb' AND value > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_ucsdb' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '1 MONTH'";
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

	public static function insert_last_ucsdb_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_ucsdb';
			INSERT INTO config (name,value) VALUES ('last_update_ucsdb',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function check_last_celestrak_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_celestrak' AND value > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_celestrak' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '7 DAYS'";
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

	public static function insert_last_celestrak_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_celestrak';
			INSERT INTO config (name,value) VALUES ('last_update_celestrak',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function check_last_marine_identity_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_marine_identity' AND value > DATE_SUB(NOW(), INTERVAL 7 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_marine_identity' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '7 DAYS'";
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

	public static function check_last_satellite_update() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_satellite' AND value > DATE_SUB(NOW(), INTERVAL 1 DAY)";
		} else {
			$query = "SELECT COUNT(*) as nb FROM config WHERE name = 'last_update_satellite' AND value::timestamp > CURRENT_TIMESTAMP - INTERVAL '1 DAYS'";
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

	public static function insert_last_marine_identity_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_marine_identity';
			INSERT INTO config (name,value) VALUES ('last_update_marine_identity',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function insert_last_satellite_update() {
		$query = "DELETE FROM config WHERE name = 'last_update_satellite';
			INSERT INTO config (name,value) VALUES ('last_update_satellite',NOW());";
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public static function delete_duplicatemodes() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "DELETE a FROM aircraft_modes a, aircraft_modes b WHERE a.ModeS = b.ModeS AND a.FirstCreated < b.FirstCreated AND a.Source != 'ACARS'";
		} else {
			$query = "DELETE FROM aircraft_modes WHERE AircraftID IN (SELECT AircraftID FROM (SELECT AircraftID, ROW_NUMBER() OVER (partition BY ModeS ORDER BY FirstCreated) AS rnum FROM aircraft_modes) t WHERE t.rnum > 1) AND Source != 'ACARS'";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}
	public static function delete_duplicateowner() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "DELETE a FROM aircraft_owner a, aircraft_owner b WHERE a.registration = b.registration AND a.owner_id < b.owner_id";
		} else {
			$query = "DELETE FROM aircraft_owner WHERE owner_id IN (SELECT owner_id FROM (SELECT owner_id, ROW_NUMBER() OVER (partition BY registration ORDER BY owner_id) AS rnum FROM aircraft_owner) t WHERE t.rnum > 1)";
		}
		try {
			$Connection = new Connection();
			$sth = $Connection->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
	}
	
	public static function update_all() {
		global $globalMasterServer, $globalMasterSource;
		if (!isset($globalMasterServer) || !$globalMasterServer) {
			if (isset($globalMasterSource) && $globalMasterSource) {
				echo update_db::update_routes();
				echo update_db::update_translation();
				//echo update_db::update_notam_fam();
				echo update_db::update_ModeS();
				//echo update_db::update_ModeS_flarm();
				echo update_db::update_ModeS_ogn();
				echo update_db::update_ModeS_faa();
				echo update_db::fix_icaotype();
				echo update_db::update_banned_fam();
				echo update_db::update_block_fam();
				echo update_db::update_diagrams_fam();
				//echo update_db::update_celestrak();
				//echo update_db::delete_duplicatemodes();
			} else {
				//echo update_db::update_routes();
				echo update_db::update_routes_fam();
				//echo update_db::update_translation();
				echo update_db::update_translation_fam();
				//echo update_db::update_notam_fam();
				//echo update_db::update_ModeS();
				echo update_db::update_ModeS_fam();
				//echo update_db::update_ModeS_flarm();
				echo update_db::update_ModeS_ogn();
				//echo update_db::delete_duplicatemodes();
				echo update_db::update_banned_fam();
				echo update_db::update_block_fam();
				echo update_db::update_diagrams_fam();
			}
		}
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
//echo update_db::update_owner();
//update_db::update_translation_fam();
//echo update_db::update_routes();
//update_db::update_models();
//echo $update_db::update_skyteam();
//echo $update_db::update_tle();
//echo update_db::update_notam_fam();
//echo update_db::create_airspace();
//echo update_db::update_ModeS();
//echo update_db::update_ModeS_fam();
//echo update_db::update_routes_fam();
//echo update_db::update_ModeS_faa();
//echo update_db::update_banned_fam();
//echo update_db::modes_faa();
//echo update_db::update_owner_fam();
//echo update_db::delete_duplicateowner();
//echo update_db::fix_icaotype();
//echo update_db::satellite_ucsdb('tmp/UCS_Satellite_Database_officialname_1-1-17.txt');
//echo update_db::update_celestrak();
//echo update_db::update_aircraft();
//echo update_db::update_block_fam();
//echo update_db::update_diagrams_fam();

?>
