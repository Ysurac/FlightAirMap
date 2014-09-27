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

	public static function update_airports() {
		global $tmp_dir;

		require_once('libs/sparqllib.php');
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
			$sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }


		$query = 'ALTER TABLE airport DROP INDEX icaoidx';
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }

		$query_dest = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`,`image_thumb`,`image`)
		    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image_thumb, :image)";
		$Connection = new Connection();
		$sth_dest = Connection::$db->prepare($query_dest);
		Connection::$db->beginTransaction();
  
		$i = 0;
		while($row = sparql_fetch_array($result))
		{
			if ($i >= 1) {
			print_r($row);
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
			print_r($query_dest_values);
			
			try {
				$sth_dest->execute($query_dest_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
			}

			$i++;
		}
		Connection::$db->commit();
		echo "Delete duplicate rows...\n";
		$query = 'ALTER IGNORE TABLE airport ADD UNIQUE INDEX icaoidx (icao)';
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }


		echo "Insert Not available Airport...\n";
		$query = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`,`image`,`image_thumb`)
		    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link, :image, :image_thumb)";
		$query_values = array(':airport_id' => $i, ':name' => 'Not available',':iata' => 'NA',':icao' => 'NA',':latitude' => '0',':longitude' => '0',':altitude' => '0',':type' => 'NA',':city' => 'N/A',':country' => 'N/A',':home_link' => '',':wikipedia_link' => '',':image' => '',':image_thumb' => '');
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
		$i++;
/*
		$query = 'DELETE FROM airport WHERE airport_id IN (SELECT * FROM (SELECT min(a.airport_id) FROM airport a GROUP BY a.icao) x)';
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
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
			Connection::$db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header) $header = $row;
				else {
					$data = array();
					$data = array_combine($header, $row);
					try {
						$sth = Connection::$db->prepare('SELECT COUNT(*) FROM airport WHERE `icao` = :icao');
						$sth->execute(array(':icao' => $data['gps_code']));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					if ($sth->fetchColumn() > 0) {
						$query = 'UPDATE airport SET `type` = :type WHERE icao = :icao';
						try {
							$sth = Connection::$db->prepare($query);
							$sth->execute(array(':icao' => $data['gps_code'],':type' => $data['type']));
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
					} else {
						$query = "INSERT INTO airport (`airport_id`,`name`,`city`,`country`,`iata`,`icao`,`latitude`,`longitude`,`altitude`,`type`,`home_link`,`wikipedia_link`)
						    VALUES (:airport_id, :name, :city, :country, :iata, :icao, :latitude, :longitude, :altitude, :type, :home_link, :wikipedia_link)";
						$query_values = array(':airport_id' => $i, ':name' => $data['name'],':iata' => $data['iata_code'],':icao' => $data['gps_code'],':latitude' => $data['latitude_deg'],':longitude' => $data['longitude_deg'],':altitude' => $data['elevation_ft'],':type' => $data['type'],':city' => $data['municipality'],':country' => $data['iso_country'],':home_link' => $data['home_link'],':wikipedia_link' => $data['wikipedia_link']);
						try {
							$sth = Connection::$db->prepare($query);
							$sth->execute($query_values);
						} catch(PDOException $e) {
							return "error : ".$e->getMessage();
						}
						$i++;
					}
				}
			}
			fclose($handle);
			Connection::$db->commit();
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
			Connection::$db->beginTransaction();
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header) $header = $row;
				else {
					$data = $row;

					$query = 'UPDATE airport SET `city` = :city, `country` = :country WHERE icao = :icao';
					try {
						$sth = Connection::$db->prepare($query);
						$sth->execute(array(':icao' => $data[0],':city' => ucwords(strtolower($data[3])),':country' => ucwords(strtolower($data[4]))));
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			fclose($handle);
			Connection::$db->commit();
		}

		echo "Put type military for all air base";
		$Connection = new Connection();
		try {
			$sth = Connection::$db->prepare("SELECT icao FROM airport WHERE `name` LIKE '%Air Base%'");
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$query2 = 'UPDATE airport SET `type` = :type WHERE icao = :icao';
			try {
				$sth2 = Connection::$db->prepare($query2);
				$sth2->execute(array(':icao' => $row['icao'],':type' => 'military'));
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}



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
echo update_db::update_airports();
?>
