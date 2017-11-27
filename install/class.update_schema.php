<?php
require_once(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.create_db.php');
require_once(dirname(__FILE__).'/class.update_db.php');

class update_schema {

	public static function update_schedule() {
	    $Connection = new Connection();
	    $Schedule = new Schedule();
	    $query = "SELECT * FROM schedule";
            try {
            	$sth = $Connection->db->prepare($query);
		$sth->execute();
    	    } catch(PDOException $e) {
		return "error : ".$e->getMessage()."\n";
    	    }
    	    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    		$Schedule->addSchedule($row['ident'],$row['departure_airport_icao'],$row['departure_airport_time'],$row['arrival_airport_icao'],$row['arrival_airport_time']);
    	    }
	
	}
/*
	private static function tableExists($tableName) {
	    $Connection = new Connection();
	    $query = "SHOW TABLES LIKE :tableName";
            try {
            	$sth = $Connection->db->prepare($query);
		$sth->execute(array(':tableName' => $tableName));
    	    } catch(PDOException $e) {
		return "error : ".$e->getMessage()."\n";
    	    }
    	    $row = $sth->fetch(PDO::FETCH_NUM);
    	    if ($row[0]) {
        	//echo 'table was found';
        	return true;
    	    } else {
        	//echo 'table was not found';
        	return false;
    	    }
    	}
*/	
	private static function update_from_1() {
    		$Connection = new Connection();
    		// Add new column to routes table
    		//$query = "ALTER TABLE `routes` ADD `FromAirport_Time` VARCHAR(10),`ToAirport_Time` VARCHAR(10),`Source` VARCHAR(255),`date_added` DATETIME DEFAULT CURRENT TIMESTAMP,`date_modified` DATETIME,`date_lastseen` DATETIME";
		$query = "ALTER TABLE `routes` ADD `FromAirport_Time` VARCHAR(10) NULL , ADD `ToAirport_Time` VARCHAR(10) NULL , ADD `Source` VARCHAR(255) NULL, ADD `date_added` timestamp DEFAULT CURRENT_TIMESTAMP, ADD `date_modified` timestamp NULL, ADD `date_lastseen` timestamp NULL";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to routes table) : ".$e->getMessage()."\n";
    		}
    		// Copy schedules data to routes table
    		self::update_schedule();
    		// Delete schedule table
		$query = "DROP TABLE `schedule`";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (delete schedule table) : ".$e->getMessage()."\n";
    		}
    		// Add source column
    		$query = "ALTER TABLE `aircraft_modes` ADD `Source` VARCHAR(255) NULL";
    		try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add source column to aircraft_modes) : ".$e->getMessage()."\n";
    		}
		// Delete unused column
		$query = "ALTER TABLE `aircraft_modes`  DROP `SerialNo`,  DROP `OperatorFlagCode`,  DROP `Manufacturer`,  DROP `Type`,  DROP `FirstRegDate`,  DROP `CurrentRegDate`,  DROP `Country`,  DROP `PreviousID`,  DROP `DeRegDate`,  DROP `Status`,  DROP `PopularName`,  DROP `GenericName`,  DROP `AircraftClass`,  DROP `Engines`,  DROP `OwnershipStatus`,  DROP `RegisteredOwners`,  DROP `MTOW`,  DROP `TotalHours`,  DROP `YearBuilt`,  DROP `CofACategory`,  DROP `CofAExpiry`,  DROP `UserNotes`,  DROP `Interested`,  DROP `UserTag`,  DROP `InfoUrl`,  DROP `PictureUrl1`,  DROP `PictureUrl2`,  DROP `PictureUrl3`,  DROP `UserBool1`,  DROP `UserBool2`,  DROP `UserBool3`,  DROP `UserBool4`,  DROP `UserBool5`,  DROP `UserString1`,  DROP `UserString2`,  DROP `UserString3`,  DROP `UserString4`,  DROP `UserString5`,  DROP `UserInt1`,  DROP `UserInt2`,  DROP `UserInt3`,  DROP `UserInt4`,  DROP `UserInt5`";
    		try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (Delete unused column of aircraft_modes) : ".$e->getMessage()."\n";
    		}
		// Add ModeS column
		$query = "ALTER TABLE `spotter_output`  ADD `ModeS` VARCHAR(255) NULL";
    		try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (Add ModeS column in spotter_output) : ".$e->getMessage()."\n";
    		}
		$query = "ALTER TABLE `spotter_live`  ADD `ModeS` VARCHAR(255)";
    		try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (Add ModeS column in spotter_live) : ".$e->getMessage()."\n";
    		}
    		// Add auto_increment for aircraft_modes
    		$query = "ALTER TABLE `aircraft_modes` CHANGE `AircraftID` `AircraftID` INT(11) NOT NULL AUTO_INCREMENT";
    		try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (Add Auto increment in aircraft_modes) : ".$e->getMessage()."\n";
    		}
    		$error = '';
		$error .= create_db::import_file('../db/acars_live.sql');
		$error .= create_db::import_file('../db/config.sql');
		// Update schema_version to 2
		$query = "UPDATE `config` SET `value` = '2' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
        }

	private static function update_from_2() {
    		$Connection = new Connection();
    		// Add new column decode to acars_live table
		$query = "ALTER TABLE `acars_live` ADD `decode` TEXT";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to routes table) : ".$e->getMessage()."\n";
    		}
    		$error = '';
    		// Create table acars_archive
		$error .= create_db::import_file('../db/acars_archive.sql');
		// Update schema_version to 3
		$query = "UPDATE `config` SET `value` = '3' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_3() {
    		$Connection = new Connection();
    		// Add default CURRENT_TIMESTAMP to aircraft_modes column FirstCreated
		$query = "ALTER TABLE `aircraft_modes` CHANGE `FirstCreated` `FirstCreated` timestamp DEFAULT CURRENT_TIMESTAMP";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to aircraft_modes) : ".$e->getMessage()."\n";
    		}
    		// Add image_source_website column to spotter_image
		$query = "ALTER TABLE `spotter_image` ADD `image_source_website` VARCHAR(999) NULL";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to spotter_image) : ".$e->getMessage()."\n";
    		}
    		$error = '';
		// Update schema_version to 4
		$query = "UPDATE `config` SET `value` = '4' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}
	
	private static function update_from_4() {
    		$Connection = new Connection();
	
    		$error = '';
    		// Create table acars_label
		$error .= create_db::import_file('../db/acars_label.sql');
		if ($error == '') {
		    // Update schema_version to 5
		    $query = "UPDATE `config` SET `value` = '5' WHERE `name` = 'schema_version'";
        	    try {
            		$sth = $Connection->db->prepare($query);
			$sth->execute();
    		    } catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
    		    }
    		}
		return $error;
	}

	private static function update_from_5() {
    		$Connection = new Connection();
    		// Add columns to translation
		$query = "ALTER TABLE `translation` ADD `Source` VARCHAR(255) NULL, ADD `date_added` timestamp DEFAULT CURRENT_TIMESTAMP , ADD `date_modified` timestamp DEFAULT CURRENT_TIMESTAMP ;";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to translation) : ".$e->getMessage()."\n";
    		}
    		// Add aircraft_shadow column to aircraft
    		$query = "ALTER TABLE `aircraft` ADD `aircraft_shadow` VARCHAR(255) NULL";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new column to aircraft) : ".$e->getMessage()."\n";
    		}
    		// Add aircraft_shadow column to spotter_live
    		$query = "ALTER TABLE `spotter_live` ADD `aircraft_shadow` VARCHAR(255) NULL";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new column to spotter_live) : ".$e->getMessage()."\n";
    		}
    		$error = '';
    		// Update table aircraft
		$error .= create_db::import_file('../db/aircraft.sql');
		$error .= create_db::import_file('../db/spotter_archive.sql');

		// Update schema_version to 6
		$query = "UPDATE `config` SET `value` = '6' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_6() {
    		$Connection = new Connection();
    		if (!$Connection->indexExists('spotter_output','flightaware_id')) {
    		    $query = "ALTER TABLE spotter_output ADD INDEX(flightaware_id);
			ALTER TABLE spotter_output ADD INDEX(date);
			ALTER TABLE spotter_output ADD INDEX(ident);
			ALTER TABLE spotter_live ADD INDEX(flightaware_id);
			ALTER TABLE spotter_live ADD INDEX(ident);
			ALTER TABLE spotter_live ADD INDEX(date);
			ALTER TABLE spotter_live ADD INDEX(longitude);
			ALTER TABLE spotter_live ADD INDEX(latitude);
			ALTER TABLE routes ADD INDEX(CallSign);
			ALTER TABLE aircraft_modes ADD INDEX(ModeS);
			ALTER TABLE aircraft ADD INDEX(icao);
			ALTER TABLE airport ADD INDEX(icao);
			ALTER TABLE translation ADD INDEX(Operator);";
        	    try {
            		$sth = $Connection->db->prepare($query);
			$sth->execute();
    		    } catch(PDOException $e) {
			return "error (add some indexes) : ".$e->getMessage()."\n";
    		    }
    		}
    		$error = '';
    		// Update table countries
    		if ($Connection->tableExists('airspace')) {
    		    $error .= update_db::update_countries();
		    if ($error != '') return $error;
		}
		// Update schema_version to 7
		$query = "UPDATE `config` SET `value` = '7' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
    	}

	private static function update_from_7() {
		global $globalDBname, $globalDBdriver;
    		$Connection = new Connection();
    		$query="ALTER TABLE spotter_live ADD pilot_name VARCHAR(255) NULL, ADD pilot_id VARCHAR(255) NULL;
    			ALTER TABLE spotter_output ADD pilot_name VARCHAR(255) NULL, ADD pilot_id VARCHAR(255) NULL;";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add pilot column to spotter_live and spotter_output) : ".$e->getMessage()."\n";
    		}
    		if ($globalDBdriver == 'mysql') {
    		    $query = "SELECT ENGINE FROM information_schema.TABLES where TABLE_SCHEMA = '".$globalDBname."' AND TABLE_NAME = 'spotter_archive'";
		    try {
            		$sth = $Connection->db->prepare($query);
			$sth->execute();
    		    } catch(PDOException $e) {
			return "error (problem when select engine for spotter_engine) : ".$e->getMessage()."\n";
    		    }
    		    $row = $sth->fetch(PDO::FETCH_ASSOC);
    		    if ($row['engine'] == 'ARCHIVE') {
			$query = "CREATE TABLE copy LIKE spotter_archive; 
				ALTER TABLE copy ENGINE=ARCHIVE;
				ALTER TABLE copy ADD pilot_name VARCHAR(255) NULL, ADD pilot_id VARCHAR(255) NULL;
				INSERT INTO copy SELECT *, '' as pilot_name, '' as pilot_id FROM spotter_archive ORDER BY `spotter_archive_id`;
				DROP TABLE spotter_archive;
				RENAME TABLE copy TO spotter_archive;";
            	    } else {
    			$query="ALTER TABLE spotter_archive ADD pilot_name VARCHAR(255) NULL, ADD pilot_id VARCHAR(255) NULL";
            	    }
                } else {
    		    $query="ALTER TABLE spotter_archive ADD pilot_name VARCHAR(255) NULL, ADD pilot_id VARCHAR(255) NULL";
                }
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add pilot column to spotter_archive) : ".$e->getMessage()."\n";
    		}

    		$error = '';
    		// Update table aircraft
		$error .= create_db::import_file('../db/source_location.sql');
		if ($error != '') return $error;
		// Update schema_version to 6
		$query = "UPDATE `config` SET `value` = '8' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_8() {
    		$Connection = new Connection();
    		$error = '';
    		// Update table aircraft
		$error .= create_db::import_file('../db/notam.sql');
		if ($error != '') return $error;
		$query = "DELETE FROM config WHERE name = 'last_update_db';
                        INSERT INTO config (name,value) VALUES ('last_update_db',NOW());
                        DELETE FROM config WHERE name = 'last_update_notam_db';
                        INSERT INTO config (name,value) VALUES ('last_update_notam_db',NOW());";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (insert last_update values) : ".$e->getMessage()."\n";
    		}
		$query = "UPDATE `config` SET `value` = '9' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_9() {
    		$Connection = new Connection();
    		$query="ALTER TABLE spotter_live ADD verticalrate INT(11) NULL;
    			ALTER TABLE spotter_output ADD verticalrate INT(11) NULL;";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add verticalrate column to spotter_live and spotter_output) : ".$e->getMessage()."\n";
    		}
		$error = '';
    		// Update table atc
		$error .= create_db::import_file('../db/atc.sql');
		if ($error != '') return $error;
		
		$query = "UPDATE `config` SET `value` = '10' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_10() {
    		$Connection = new Connection();
    		$query="ALTER TABLE atc CHANGE `type` `type` ENUM('Observer','Flight Information','Delivery','Tower','Approach','ACC','Departure','Ground','Flight Service Station','Control Radar or Centre') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new enum to ATC table) : ".$e->getMessage()."\n";
    		}
		$error = '';
    		// Add tables
		$error .= create_db::import_file('../db/aircraft_owner.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/metar.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/taf.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/airport.sql');
		if ($error != '') return $error;
		
		$query = "UPDATE `config` SET `value` = '11' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_11() {
		global $globalDBdriver, $globalDBname;
    		$Connection = new Connection();
    		$query="ALTER TABLE spotter_output ADD owner_name VARCHAR(255) NULL DEFAULT NULL, ADD format_source VARCHAR(255) NULL DEFAULT NULL, ADD ground BOOLEAN NOT NULL DEFAULT FALSE, ADD last_ground BOOLEAN NOT NULL DEFAULT FALSE, ADD last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD last_latitude FLOAT NULL, ADD last_longitude FLOAT NULL, ADD last_altitude INT(11) NULL, ADD last_ground_speed INT(11), ADD real_arrival_airport_icao VARCHAR(999), ADD real_arrival_airport_time VARCHAR(20),ADD real_departure_airport_icao VARCHAR(999), ADD real_departure_airport_time VARCHAR(20)";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add owner_name & format_source column to spotter_output) : ".$e->getMessage()."\n";
    		}
    		$query="ALTER TABLE spotter_live ADD format_source VARCHAR(255) NULL DEFAULT NULL, ADD ground BOOLEAN NOT NULL DEFAULT FALSE";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (format_source column to spotter_live) : ".$e->getMessage()."\n";
    		}
    		if ($globalDBdriver == 'mysql') {
    		    $query = "SELECT ENGINE FROM information_schema.TABLES where TABLE_SCHEMA = '".$globalDBname."' AND TABLE_NAME = 'spotter_archive'";
		    try {
            		$sth = $Connection->db->prepare($query);
			$sth->execute();
    		    } catch(PDOException $e) {
			return "error (problem when select engine for spotter_engine) : ".$e->getMessage()."\n";
    		    }
    		    $row = $sth->fetch(PDO::FETCH_ASSOC);
    		    if ($row['engine'] == 'ARCHIVE') {
			$query = "CREATE TABLE copy LIKE spotter_archive; 
				ALTER TABLE copy ENGINE=ARCHIVE;
				ALTER TABLE copy ADD verticalrate INT(11) NULL, ADD format_source VARCHAR(255) NULL DEFAULT NULL, ADD ground BOOLEAN NOT NULL DEFAULT FALSE;
				INSERT INTO copy SELECT *, '' as verticalrate, '' as format_source, '0' as ground FROM spotter_archive ORDER BY `spotter_archive_id`;
				DROP TABLE spotter_archive;
				RENAME TABLE copy TO spotter_archive;";
            	    } else {
    			$query="ALTER TABLE spotter_archive ADD verticalrate INT(11) NULL, ADD format_source VARCHAR(255) NULL DEFAULT NULL, ADD ground BOOLEAN NOT NULL DEFAULT FALSE";
            	    }
                } else {
    		    $query="ALTER TABLE spotter_archive ADD verticalrate INT(11) NULL, ADD format_source VARCHAR(255) NULL DEFAULT NULL, ADD ground BOOLEAN NOT NULL DEFAULT FALSE";
                }
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add columns to spotter_archive) : ".$e->getMessage()."\n";
    		}

		$error = '';
		
		$query = "UPDATE `config` SET `value` = '12' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}
	private static function update_from_12() {
    		$Connection = new Connection();
		$error = '';
    		// Add tables
		$error .= create_db::import_file('../db/stats.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/stats_aircraft.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/stats_airline.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/stats_airport.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/stats_owner.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/stats_pilot.sql');
		if ($error != '') return $error;
		$error .= create_db::import_file('../db/spotter_archive_output.sql');
		if ($error != '') return $error;
		
		$query = "UPDATE `config` SET `value` = '13' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_13() {
    		$Connection = new Connection();
    		if (!$Connection->checkColumnName('spotter_archive_output','real_departure_airport_icao')) {
    			$query="ALTER TABLE spotter_archive_output ADD real_departure_airport_icao VARCHAR(20), ADD real_departure_airport_time VARCHAR(20)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
	    		} catch(PDOException $e) {
				return "error (update spotter_archive_output) : ".$e->getMessage()."\n";
    			}
		}
    		$error = '';
		$query = "UPDATE `config` SET `value` = '14' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_14() {
    		$Connection = new Connection();
		$error = '';
    		// Add tables
    		if (!$Connection->tableExists('stats_flight')) {
			$error .= create_db::import_file('../db/stats_flight.sql');
			if ($error != '') return $error;
		}
		$query = "UPDATE `config` SET `value` = '15' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}


	private static function update_from_15() {
    		$Connection = new Connection();
		$error = '';
    		// Add tables
    		$query="ALTER TABLE `stats` CHANGE `stats_date` `stats_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update stats) : ".$e->getMessage()."\n";
    		}
		if ($error != '') return $error;
		$query = "UPDATE `config` SET `value` = '16' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_16() {
    		$Connection = new Connection();
		$error = '';
    		// Add tables
    		if (!$Connection->tableExists('stats_registration')) {
			$error .= create_db::import_file('../db/stats_registration.sql');
		}
    		if (!$Connection->tableExists('stats_callsign')) {
			$error .= create_db::import_file('../db/stats_callsign.sql');
		}
		if ($error != '') return $error;
		$query = "UPDATE `config` SET `value` = '17' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_17() {
    		$Connection = new Connection();
		$error = '';
    		// Add tables
    		if (!$Connection->tableExists('stats_country')) {
			$error .= create_db::import_file('../db/stats_country.sql');
		}
		if ($error != '') return $error;
		$query = "UPDATE `config` SET `value` = '18' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}
	private static function update_from_18() {
    		$Connection = new Connection();
		$error = '';
    		// Modify stats_airport table
    		if (!$Connection->checkColumnName('stats_airport','airport_name')) {
    			$query = "ALTER TABLE `stats_airport` ADD `stats_type` VARCHAR(50) NOT NULL DEFAULT 'yearly', ADD `airport_name` VARCHAR(255) NOT NULL, ADD `date` DATE NULL DEFAULT NULL, DROP INDEX `airport_icao`, ADD UNIQUE `airport_icao` (`airport_icao`, `type`, `date`)";
    	        	try {
	            	    $sth = $Connection->db->prepare($query);
			    $sth->execute();
    			} catch(PDOException $e) {
			    return "error (update stats) : ".$e->getMessage()."\n";
    			}
    		}
		if ($error != '') return $error;
		$query = "UPDATE `config` SET `value` = '19' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_19() {
    		$Connection = new Connection();
		$error = '';
    		// Update airport table
		$error .= create_db::import_file('../db/airport.sql');
		if ($error != '') return 'Import airport.sql : '.$error;
		// Remove primary key on Spotter_Archive
		$query = "alter table spotter_archive drop spotter_archive_id";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (remove primary key on spotter_archive) : ".$e->getMessage()."\n";
    		}
		$query = "alter table spotter_archive add spotter_archive_id INT(11)";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add id again on spotter_archive) : ".$e->getMessage()."\n";
    		}
		if (!$Connection->checkColumnName('spotter_archive','over_country')) {
			// Add column over_country
    			$query = "ALTER TABLE `spotter_archive` ADD `over_country` VARCHAR(5) NULL DEFAULT NULL";
			try {
            			$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add over_country) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('spotter_live','over_country')) {
			// Add column over_country
    			$query = "ALTER TABLE `spotter_live` ADD `over_country` VARCHAR(5) NULL DEFAULT NULL";
			try {
            			$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add over_country) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('spotter_output','source_name')) {
			// Add source_name to spotter_output, spotter_live, spotter_archive, spotter_archive_output
    			$query = "ALTER TABLE `spotter_output` ADD `source_name` VARCHAR(255) NULL AFTER `format_source`";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add source_name column) : ".$e->getMessage()."\n";
    			}
    		}
		if (!$Connection->checkColumnName('spotter_live','source_name')) {
			// Add source_name to spotter_output, spotter_live, spotter_archive, spotter_archive_output
    			$query = "ALTER TABLE `spotter_live` ADD `source_name` VARCHAR(255) NULL AFTER `format_source`";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add source_name column) : ".$e->getMessage()."\n";
    			}
    		}
		if (!$Connection->checkColumnName('spotter_archive_output','source_name')) {
			// Add source_name to spotter_output, spotter_live, spotter_archive, spotter_archive_output
    			$query = "ALTER TABLE `spotter_archive_output` ADD `source_name` VARCHAR(255) NULL AFTER `format_source`";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add source_name column) : ".$e->getMessage()."\n";
    			}
    		}
		if (!$Connection->checkColumnName('spotter_archive','source_name')) {
			// Add source_name to spotter_output, spotter_live, spotter_archive, spotter_archive_output
    			$query = "ALTER TABLE `spotter_archive` ADD `source_name` VARCHAR(255) NULL AFTER `format_source`;";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add source_name column) : ".$e->getMessage()."\n";
    			}
    		}
		if ($error != '') return $error;
		$query = "UPDATE `config` SET `value` = '20' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_20() {
		global $globalIVAO, $globalVATSIM, $globalphpVMS;
    		$Connection = new Connection();
		$error = '';
    		// Update airline table
    		if (!$globalIVAO && !$globalVATSIM && !$globalphpVMS) {
			$error .= create_db::import_file('../db/airlines.sql');
			if ($error != '') return 'Import airlines.sql : '.$error;
		}
		if (!$Connection->checkColumnName('aircraft_modes','type_flight')) {
			// Add column over_country
    			$query = "ALTER TABLE `aircraft_modes` ADD `type_flight` VARCHAR(50) NULL DEFAULT NULL;";
        		try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add over_country) : ".$e->getMessage()."\n";
    			}
    		}
		if ($error != '') return $error;
		/*
    		if (!$globalIVAO && !$globalVATSIM && !$globalphpVMS) {
			// Force update ModeS (this will put type_flight data
			$error .= update_db::update_ModeS;
			if ($error != '') return "error (update ModeS) : ".$error;
		}
		*/
		$query = "UPDATE `config` SET `value` = '21' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_21() {
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('stats_airport','stats_type')) {
			// Rename type to stats_type
			$query = "ALTER TABLE `stats_airport` CHANGE `type` `stats_type` VARCHAR(50);ALTER TABLE `stats` CHANGE `type` `stats_type` VARCHAR(50);ALTER TABLE `stats_flight` CHANGE `type` `stats_type` VARCHAR(50);";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (rename type to stats_type on stats*) : ".$e->getMessage()."\n";
			}
			if ($error != '') return $error;
		}
		$query = "UPDATE `config` SET `value` = '22' WHERE `name` = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}

	private static function update_from_22() {
		global $globalDBdriver;
    		$Connection = new Connection();
		$error = '';
		// Add table stats polar
    		if (!$Connection->tableExists('stats_source')) {
			if ($globalDBdriver == 'mysql') {
    				$error .= create_db::import_file('../db/stats_source.sql');
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_source.sql');
			}
			if ($error != '') return $error;
		}
		$query = "UPDATE config SET value = '23' WHERE name = 'schema_version'";
        	try {
            	    $sth = $Connection->db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (update schema_version) : ".$e->getMessage()."\n";
    		}
		return $error;
	}


	private static function update_from_23() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		// Add table tle for satellites
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->tableExists('tle')) {
				$error .= create_db::import_file('../db/tle.sql');
				if ($error != '') return $error;
			}
		} else {
			if (!$Connection->tableExists('tle')) {
				$error .= create_db::import_file('../db/pgsql/tle.sql');
				if ($error != '') return $error;
			}
			$query = "create index flightaware_id_idx ON spotter_archive USING btree(flightaware_id)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (create index on spotter_archive) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_aircraft','aircraft_manufacturer')) {
			// Add aircraft_manufacturer to stats_aircraft
    			$query = "ALTER TABLE stats_aircraft ADD aircraft_manufacturer VARCHAR(255) NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add aircraft_manufacturer column) : ".$e->getMessage()."\n";
    			}
    		}
		
		$query = "UPDATE config SET value = '24' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_24() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/airlines.sql');
		} else {
			$error .= create_db::import_file('../db/pgsql/airlines.sql');
		}
		if ($error != '') return 'Import airlines.sql : '.$error;
		if (!$Connection->checkColumnName('airlines','forsource')) {
			// Add forsource to airlines
			$query = "ALTER TABLE airlines ADD forsource VARCHAR(255) NULL DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add forsource column) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_aircraft','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_aircraft ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_aircraft) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index aircraft_icao on stats_aircraft;ALTER TABLE stats_aircraft ADD UNIQUE aircraft_icao (aircraft_icao,stats_airline,filter_name);";
			} else {
				$query = "alter table stats_aircraft drop constraint stats_aircraft_aircraft_icao_key;ALTER TABLE stats_aircraft ADD CONSTRAINT aircraft_icao UNIQUE (aircraft_icao,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_aircraft) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_airport','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_airport ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add filter_name column in stats_airport) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index airport_icao on stats_airport;ALTER TABLE stats_airport ADD UNIQUE airport_icao (airport_icao,stats_type,date,stats_airline,filter_name);";
			} else {
				$query = "alter table stats_airport drop constraint stats_airport_airport_icao_stats_type_date_key;ALTER TABLE stats_airport ADD CONSTRAINT airport_icao UNIQUE (airport_icao,stats_type,date,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_airport) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_country','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_country ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_country) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index iso2 on stats_country;ALTER TABLE stats_country ADD UNIQUE iso2 (iso2,stats_airline,filter_name);";
			} else {
				$query = "alter table stats_country drop constraint stats_country_iso2_key;ALTER TABLE stats_country ADD CONSTRAINT iso2 UNIQUE (iso2,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_airline) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_flight','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_flight ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_flight) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats) : ".$e->getMessage()."\n";
			}
			if ($globalDBdriver == 'mysql' && $Connection->indexExists('stats','type')) {
				// Add unique key
				$query = "drop index type on stats;ALTER TABLE stats ADD UNIQUE stats_type (stats_type,stats_date,stats_airline,filter_name);";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add unique key in stats) : ".$e->getMessage()."\n";
				}
			} else {
				// Add unique key
				if ($globalDBdriver == 'mysql') {
					$query = "drop index stats_type on stats;ALTER TABLE stats ADD UNIQUE stats_type (stats_type,stats_date,stats_airline,filter_name);";
				} else {
					$query = "alter table stats drop constraint stats_stats_type_stats_date_key;ALTER TABLE stats ADD CONSTRAINT stats_type UNIQUE (stats_type,stats_date,stats_airline,filter_name);";
				}
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add unique key in stats) : ".$e->getMessage()."\n";
				}
			}
		}
		if (!$Connection->checkColumnName('stats_registration','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_registration ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_registration) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index registration on stats_registration;ALTER TABLE stats_registration ADD UNIQUE registration (registration,stats_airline,filter_name);";
			} else {
				$query = "alter table stats_registration drop constraint stats_registration_registration_key;ALTER TABLE stats_registration ADD CONSTRAINT registration UNIQUE (registration,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_registration) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_callsign','filter_name')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_callsign ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add filter_name column in stats_callsign) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index callsign_icao on stats_callsign;ALTER TABLE stats_callsign ADD UNIQUE callsign_icao (callsign_icao,filter_name);";
			} else {
				$query = "drop index stats_callsign_callsign_icao_key;ALTER TABLE stats_callsign ADD CONSTRAINT callsign_icao UNIQUE (callsign_icao,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_callsign) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_airline','filter_name')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_airline ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add filter_name column in stats_airline) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index airline_icao on stats_airline;ALTER TABLE stats_airline ADD UNIQUE airline_icao (airline_icao,filter_name);";
			} else {
				$query = "drop index stats_airline_airline_icao_key;ALTER TABLE stats_airline ADD CONSTRAINT airline_icao UNIQUE (airline_icao,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_callsign) : ".$e->getMessage()."\n";
			}
		}
		
		$query = "UPDATE config SET value = '25' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_25() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('stats_owner','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_owner ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_owner) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index owner_name on stats_owner;ALTER TABLE stats_owner ADD UNIQUE owner_name (owner_name,stats_airline,filter_name);";
			} else {
				$query = "drop index stats_owner_owner_name_key;ALTER TABLE stats_owner ADD CONSTRAINT owner_name UNIQUE (owner_name,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_owner) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('stats_pilot','stats_airline')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_pilot ADD stats_airline VARCHAR(255) NULL DEFAULT '', ADD filter_name VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add stats_airline & filter_name column in stats_pilot) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index pilot_id on stats_pilot;ALTER TABLE stats_pilot ADD UNIQUE pilot_id (pilot_id,stats_airline,filter_name);";
			} else {
				$query = "drop index stats_pilot_pilot_id_key;ALTER TABLE stats_pilot ADD CONSTRAINT pilot_id UNIQUE (pilot_id,stats_airline,filter_name);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add unique key in stats_pilot) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '26' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_26() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('atc','format_source')) {
			$query = "ALTER TABLE atc ADD format_source VARCHAR(255) DEFAULT NULL, ADD source_name VARCHAR(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add format_source & source_name column in atc) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '27' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_27() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('stats_pilot','format_source')) {
			// Add forsource to airlines
			$query = "ALTER TABLE stats_pilot ADD format_source VARCHAR(255) NULL DEFAULT ''";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add format_source column in stats_pilot) : ".$e->getMessage()."\n";
			}
			// Add unique key
			if ($globalDBdriver == 'mysql') {
				$query = "drop index pilot_id on stats_pilot;ALTER TABLE stats_pilot ADD UNIQUE pilot_id (pilot_id,stats_airline,filter_name,format_source);";
			} else {
				$query = "drop index pilot_id;ALTER TABLE stats_pilot ADD CONSTRAINT pilot_id UNIQUE (pilot_id,stats_airline,filter_name,format_source);";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (modify unique key in stats_pilot) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '28' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_28() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql' && !$Connection->indexExists('spotter_live','latitude')) {
			// Add unique key
			$query = "alter table spotter_live add index(latitude,longitude)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index latitude,longitude on spotter_live) : ".$e->getMessage()."\n";
			}
                }
		if (!$Connection->checkColumnName('aircraft','mfr')) {
			// Add mfr to aircraft
			$query = "ALTER TABLE aircraft ADD mfr VARCHAR(255) NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add mfr column in aircraft) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->tableExists('accidents')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/accidents.sql');
			} else {
				$error .= create_db::import_file('../db/pgsql/accidents.sql');
			}
		}

		$query = "UPDATE config SET value = '29' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_29() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($Connection->checkColumnName('aircraft','mfr')) {
			// drop mfr to aircraft
			$query = "ALTER TABLE aircraft DROP COLUMN mfr";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (drop mfr column in aircraft) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->tableExists('faamfr')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/faamfr.sql');
			} else {
				$error .= create_db::import_file('../db/pgsql/faamfr.sql');
			}
		}

		$query = "UPDATE config SET value = '30' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_30() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->indexExists('notam','ref_idx')) {
			// Add index key
			$query = "create index ref_idx on notam (ref)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index ref on notam) : ".$e->getMessage()."\n";
			}
                }
		if (!$Connection->indexExists('accidents','registration_idx')) {
			// Add index key
			$query = "create index registration_idx on accidents (registration)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index registration on accidents) : ".$e->getMessage()."\n";
			}
                }
		if (!$Connection->indexExists('accidents','rdts')) {
			// Add index key
			$query = "create index rdts on accidents (registration,date,type,source)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index registration, date, type & source on accidents) : ".$e->getMessage()."\n";
			}
                }

		$query = "UPDATE config SET value = '31' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_31() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('accidents','airline_name')) {
			// Add airline_name to accidents
			$query = "ALTER TABLE accidents ADD airline_name VARCHAR(255) NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add airline_name column in accidents) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('accidents','airline_icao')) {
			// Add airline_icao to accidents
			$query = "ALTER TABLE accidents ADD airline_icao VARCHAR(10) NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add airline_icao column in accidents) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '32' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_32() {
		global $globalDBdriver, $globalVATSIM, $globalIVAO;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('airlines','alliance')) {
			// Add alliance to airlines
			$query = "ALTER TABLE airlines ADD alliance VARCHAR(255) NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add alliance column in airlines) : ".$e->getMessage()."\n";
			}
		}
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/airlines.sql');
			if ($error != '') return $error;
		} else {
			$error .= create_db::import_file('../db/pgsql/airlines.sql');
			if ($error != '') return $error;
		}
		if ((isset($globalVATSIM) && $globalVATSIM) || (isset($globalIVAO) && $globalIVAO)) {
			include_once(dirname(__FILE__).'/class.update_db.php');
			if (isset($globalVATSIM) && $globalVATSIM) {
				$error .= update_db::update_vatsim();
				if ($error != '') return $error;
			}
			if (isset($globalIVAO) && $globalIVAO && file_exists('tmp/ivae_feb2013.zip')) {
				$error .= update_db::update_IVAO();
				if ($error != '') return $error;
			}
		}

		$query = "UPDATE config SET value = '33' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_33() {
		global $globalDBdriver, $globalVATSIM, $globalIVAO;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('airlines','ban_eu')) {
			// Add ban_eu to airlines
			$query = "ALTER TABLE airlines ADD ban_eu INTEGER NOT NULL DEFAULT '0'";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add ban_eu column in airlines) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '34' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_34() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			if ($Connection->getColumnType('spotter_output','date') == 'TIMESTAMP' && $Connection->getColumnType('spotter_output','last_seen') != 'TIMESTAMP') {
				$query = "ALTER TABLE spotter_output CHANGE date date TIMESTAMP NULL DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (delete default timestamp spotter_output) : ".$e->getMessage()."\n";
				}
				$query = "ALTER TABLE spotter_output MODIFY COLUMN last_seen timestamp not null default current_timestamp()";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (convert spotter_output last_seen to timestamp) : ".$e->getMessage()."\n";
				}
				
				$query = "ALTER TABLE spotter_output ALTER COLUMN last_seen DROP DEFAULT";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (delete default timestamp spotter_output) : ".$e->getMessage()."\n";
				}
				/*$query = "SELECT date,last_seen FROM spotter_output WHERE last_seen < date ORDER BY date DESC LIMIT 150";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (get date diff from spotter_output) : ".$e->getMessage()."\n";
				}
				$stats = array();
				while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
					$hours = gmdate('H',strtotime($row['last_seen']) - strtotime($row['date']));
					if ($hours < 12) {
						if (isset($stats[$hours])) $stats[$hours] = $stats[$hours] + 1;
						else $stats[$hours] = 1;
					}
				}
				if (!empty($stats)) {
					asort($stats);
					reset($stats);
					$hour = key($stats);
					$i = 1;
					$j = 0;
					$query_chk = "SELECT count(*) as nb FROM spotter_output WHERE last_seen < date";
					while ($i > 0) {
						$query = "UPDATE spotter_output SET last_seen = DATE_ADD(last_seen, INTERVAL ".$hour." HOUR) WHERE last_seen < date";
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error (fix date) : ".$e->getMessage()."\n";
						}
						try {
							$sth_chk = $Connection->db->prepare($query_chk);
							$sth_chk->execute();
							$result = $sth_chk->fetchAll(PDO::FETCH_ASSOC);
						} catch(PDOException $e) {
							return "error (fix date chk) : ".$e->getMessage()."\n";
						}
						$i = $result[0]['nb'];
						$hour = 1;
						$j++;
						if ($j > 12) $i = 0;
					}
				}
				*/
				$query = "UPDATE spotter_output SET last_seen = date WHERE last_seen < date";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (fix date) : ".$e->getMessage()."\n";
				}
			}
			/*
			if ($Connection->getColumnType('spotter_archive_output','date') == 'TIMESTAMP' && $Connection->getColumnType('spotter_archive_output','last_seen') != 'TIMESTAMP') {
				$query = "ALTER TABLE spotter_archive_output CHANGE date date TIMESTAMP NULL DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (delete default timestamp spotter_output) : ".$e->getMessage()."\n";
				}
				$query = "ALTER TABLE spotter_archive_output MODIFY COLUMN last_seen timestamp not null default current_timestamp()";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (convert spotter_archive_output last_seen to timestamp) : ".$e->getMessage()."\n";
				}
				$query = "ALTER TABLE spotter_archive_output ALTER COLUMN last_seen DROP DEFAULT";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (delete default timestamp spotter_output) : ".$e->getMessage()."\n";
				}
				$query = "SELECT date,last_seen FROM spotter_archive_output WHERE last_seen < date ORDER BY date DESC LIMIT 150";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (get diff from spotter_archive_output) : ".$e->getMessage()."\n";
				}
				$stats = array();
				while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
					$hours = gmdate('H',strtotime($row['last_seen']) - strtotime($row['date']));
					if ($hours < 12) {
						if (isset($stats[$hours])) $stats[$hours] = $stats[$hours] + 1;
						else $stats[$hours] = 1;
					}
				}
				if (!empty($stats)) {
					asort($stats);
					reset($stats);
					$hour = key($stats);
					$i = 1;
					$j = 0;
					$query_chk = "SELECT count(*) as nb FROM spotter_archive_output WHERE last_seen < date";
					while ($i > 0) {
						$query = "UPDATE spotter_archive_output SET last_seen = DATE_ADD(last_seen, INTERVAL ".$hour." HOUR) WHERE last_seen < date";
						try {
							$sth = $Connection->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error (fix date) : ".$e->getMessage()."\n";
						}
						try {
							$sth_chk = $Connection->db->prepare($query_chk);
							$sth_chk->execute();
							$result = $sth_chk->fetchAll(PDO::FETCH_ASSOC);
						} catch(PDOException $e) {
							return "error (fix date chk) : ".$e->getMessage()."\n";
						}
						$i = $result[0]['nb'];
						$hour = 1;
						$j++;
						if ($j > 12) $i = 0;
					}
				}
				$query = "UPDATE spotter_archive_output SET last_seen = date WHERE last_seen < date";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (fix date) : ".$e->getMessage()."\n";
				}
			
			}
			*/
		}
		$query = "UPDATE config SET value = '35' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}
	private static function update_from_35() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->indexExists('accidents','type')) {
			// Add index key
			$query = "create index type on accidents (type,date)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index type on accidents) : ".$e->getMessage()."\n";
			}
                }
		$query = "UPDATE config SET value = '36' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_36() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('aircraft_modes','source_type')) {
			$query = "ALTER TABLE aircraft_modes ADD source_type VARCHAR(255) DEFAULT 'modes'";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add source_type column in aircraft_modes) : ".$e->getMessage()."\n";
			}
		}
		/*
		if ($globalDBdriver == 'mysql') {
			$query = "ALTER TABLE spotter_output MODIFY COLUMN ModeS VARCHAR(20) DEFAULT NULL; ALTER TABLE spotter_archive_output MODIFY COLUMN ModeS VARCHAR(20) DEFAULT NULL; ALTER TABLE spotter_live MODIFY COLUMN ModeS VARCHAR(20) DEFAULT NULL;ALTER TABLE spotter_archive MODIFY COLUMN ModeS VARCHAR(20) DEFAULT NULL;";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change ModeS column in spotter_* to NULL) : ".$e->getMessage()."\n";
			}
		} else {
			$query = "ALTER TABLE spotter_output ALTER COLUMN ModeS DROP NOT NULL;ALTER TABLE spotter_live ALTER COLUMN ModeS DROP NOT NULL;ALTER TABLE spotter_archive_output ALTER COLUMN ModeS DROP NOT NULL;ALTER TABLE spotter_archive ALTER COLUMN ModeS DROP NOT NULL;";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change ModeS column in spotter_* to NULL) : ".$e->getMessage()."\n";
			}
		}
		*/
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->tableExists('tracker_output')) {
				$error .= create_db::import_file('../db/tracker_output.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('tracker_live')) {
				$error .= create_db::import_file('../db/tracker_live.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_output')) {
				$error .= create_db::import_file('../db/marine_output.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_live')) {
				$error .= create_db::import_file('../db/marine_live.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_identity')) {
				$error .= create_db::import_file('../db/marine_identity.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_mid')) {
				$error .= create_db::import_file('../db/marine_mid.sql');
				if ($error != '') return $error;
			}
		} else {
			$error .= create_db::import_file('../db/pgsql/tracker_output.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/tracker_live.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_output.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_live.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_identity.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_mid.sql');
			if ($error != '') return $error;
		}
		$query = "UPDATE config SET value = '37' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_37() {
		global $globalDBdriver, $globalDBname;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->tableExists('marine_image')) {
				$error .= create_db::import_file('../db/marine_image.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_archive')) {
				$error .= create_db::import_file('../db/marine_archive.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_archive_output')) {
				$error .= create_db::import_file('../db/marine_archive_output.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('tracker_archive')) {
				$error .= create_db::import_file('../db/tracker_archive.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('tracker_archive_output')) {
				$error .= create_db::import_file('../db/tracker_archive_output.sql');
				if ($error != '') return $error;
			}
			if (!$Connection->tableExists('marine_archive_output')) {
				$error .= create_db::import_file('../db/tracker_archive_output.sql');
				if ($error != '') return $error;
			}
		} else {
			$error .= create_db::import_file('../db/pgsql/marine_image.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_archive.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/marine_archive_output.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/tracker_archive.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/tracker_archive_output.sql');
			if ($error != '') return $error;
		}
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT ENGINE FROM information_schema.TABLES where TABLE_SCHEMA = '".$globalDBname."' AND TABLE_NAME = 'spotter_archive'";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (problem when select engine for spotter_engine) : ".$e->getMessage()."\n";
			}
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ($row['engine'] == 'ARCHIVE') {
				$query = "ALTER TABLE spotter_archive ENGINE=InnoDB";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (Change table format from archive to InnoDB for spotter_archive) : ".$e->getMessage()."\n";
				}
			}
		}
		if (!$Connection->indexExists('spotter_archive','flightaware_id_date_idx') && !$Connection->indexExists('spotter_archive','flightaware_id')) {
			// Add index key
			$query = "create index flightaware_id_date_idx on spotter_archive (flightaware_id,date)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add index flightaware_id, date on spotter_archive) : ".$e->getMessage()."\n";
			}
                }
		$query = "UPDATE config SET value = '38' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_38() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->checkColumnName('marine_output','type_id')) {
				$query = "ALTER TABLE marine_output ADD COLUMN type_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_live','type_id')) {
				$query = "ALTER TABLE marine_live ADD COLUMN type_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_live) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive','type_id')) {
				$query = "ALTER TABLE marine_archive ADD COLUMN type_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_archive) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive_output','type_id')) {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN type_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_archive_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_output','status_id')) {
				$query = "ALTER TABLE marine_output ADD COLUMN status_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_live','status_id')) {
				$query = "ALTER TABLE marine_live ADD COLUMN status_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_live) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive','status_id')) {
				$query = "ALTER TABLE marine_archive ADD COLUMN status_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_archive) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive_output','status_id')) {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN status_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_archive_output) : ".$e->getMessage()."\n";
				}
			}
		} else {
			if (!$Connection->checkColumnName('marine_output','type_id')) {
				$query = "ALTER TABLE marine_output ADD COLUMN type_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_live','type_id')) {
				$query = "ALTER TABLE marine_live ADD COLUMN type_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_live) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive','type_id')) {
				$query = "ALTER TABLE marine_archive ADD COLUMN type_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_archive) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive_output','type_id')) {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN type_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column type_id in marine_archive_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_output','status_id')) {
				$query = "ALTER TABLE marine_output ADD COLUMN status_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_output) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_live','status_id')) {
				$query = "ALTER TABLE marine_live ADD COLUMN status_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_live) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive','status_id')) {
				$query = "ALTER TABLE marine_archive ADD COLUMN status_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_archive) : ".$e->getMessage()."\n";
				}
			}
			if (!$Connection->checkColumnName('marine_archive_output','status_id')) {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN status_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column status_id in marine_archive_output) : ".$e->getMessage()."\n";
				}
			}
		}
		$query = "UPDATE config SET value = '39' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_39() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$query = "ALTER TABLE stats_pilot MODIFY COLUMN pilot_id varchar(255) NOT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change pilot_id type to varchar in stats_pilot) : ".$e->getMessage()."\n";
			}
			$query = "ALTER TABLE marine_identity MODIFY COLUMN mmsi varchar(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change mmsi type to varchar in marine_identity) : ".$e->getMessage()."\n";
			}
		} else {
			$query = "alter table stats_pilot alter column pilot_id type varchar(255)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change pilot_id type to varchar in stats_pilot) : ".$e->getMessage()."\n";
			}
			$query = "alter table marine_identity alter column mmsi type varchar(255)";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (change mmsi type to varchar in marine_identity) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '40' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_40() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('source_location','last_seen')) {
			$query = "ALTER TABLE source_location ADD COLUMN last_seen timestamp NULL DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column last_seen in source_location) : ".$e->getMessage()."\n";
			}
		}
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->checkColumnName('source_location','location_id')) {
				$query = "ALTER TABLE source_location ADD COLUMN location_id int(11) DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column location_id in source_location) : ".$e->getMessage()."\n";
				}
			}
		} else {
			if (!$Connection->checkColumnName('source_location','location_id')) {
				$query = "ALTER TABLE source_location ADD COLUMN location_id integer DEFAULT NULL";
				try {
					$sth = $Connection->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error (add column location_id in source_location) : ".$e->getMessage()."\n";
				}
			}
		}
		$query = "UPDATE config SET value = '41' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_41() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('source_location','description')) {
			$query = "ALTER TABLE source_location ADD COLUMN description text DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column description in source_location) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '42' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_42() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('spotter_live','real_altitude')) {
			$query = "ALTER TABLE spotter_live ADD COLUMN real_altitude float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column real_altitude in spotter_live) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('spotter_output','real_altitude')) {
			$query = "ALTER TABLE spotter_output ADD COLUMN real_altitude float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column real_altitude in spotter_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('spotter_archive_output','real_altitude')) {
			$query = "ALTER TABLE spotter_archive_output ADD COLUMN real_altitude float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column real_altitude in spotter_archive_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('spotter_archive','real_altitude')) {
			$query = "ALTER TABLE spotter_archive ADD COLUMN real_altitude float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add column real_altitude in spotter_archive) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '43' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_43() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			if (!$Connection->tableExists('tracker_archive_output')) {
				$error .= create_db::import_file('../db/tracker_archive_output.sql');
				if ($error != '') return $error;
			}
			$query = "ALTER TABLE tracker_live MODIFY COLUMN altitude float DEFAULT NULL;ALTER TABLE tracker_output MODIFY COLUMN last_altitude float DEFAULT NULL;ALTER TABLE tracker_output MODIFY COLUMN altitude float DEFAULT NULL;ALTER TABLE tracker_archive MODIFY COLUMN altitude float DEFAULT NULL;ALTER TABLE tracker_archive_output MODIFY COLUMN last_altitude float DEFAULT NULL;ALTER TABLE tracker_output MODIFY COLUMN altitude float DEFAULT NULL;";
		} else {
			$query = "ALTER TABLE tracker_live ALTER COLUMN altitude TYPE float;ALTER TABLE tracker_output ALTER COLUMN last_altitude TYPE float;ALTER TABLE tracker_output ALTER COLUMN altitude TYPE float;ALTER TABLE tracker_archive ALTER COLUMN altitude TYPE float;ALTER TABLE tracker_archive_output ALTER COLUMN last_altitude TYPE float;ALTER TABLE tracker_output ALTER COLUMN altitude TYPE float;";
		}
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (modify column altitude in tracker_*) : ".$e->getMessage()."\n";
		}
		$query = "UPDATE config SET value = '44' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_44() {
		global $globalDBdriver, $globalVATSIM, $globalIVAO;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/airport.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/airlines.sql');
			if ($error != '') return $error;
		} else {
			$error .= create_db::import_file('../db/pgsql/airport.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/airlines.sql');
			if ($error != '') return $error;
		}
		if ((isset($globalVATSIM) && $globalVATSIM) && (isset($globalIVAO) && $globalIVAO)) {
			if (file_exists('tmp/ivae_feb2013.zip')) {
				$error .= update_db::update_IVAO();
			} else {
				$error .= update_db::update_vatsim();
			}
		} elseif (isset($globalVATSIM) && $globalVATSIM) {
			$error .= update_db::update_vatsim();
		} elseif (isset($globalIVAO) && $globalIVAO) {
			if (file_exists('tmp/ivae_feb2013.zip')) {
				$error .= update_db::update_IVAO();
			} else {
				$error .= update_db::update_vatsim();
			}
		}
		if ($error != '') return $error;
		$query = "UPDATE config SET value = '45' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_45() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->tableExists('satellite')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/satellite.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/satellite.sql');
				if ($error != '') return $error;
			}
		}
		$query = "UPDATE config SET value = '46' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_46() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->tableExists('stats_marine')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_marine.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_marine.sql');
				if ($error != '') return $error;
			}
		}
		if (!$Connection->tableExists('stats_marine_country')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_marine_country.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_marine_country.sql');
				if ($error != '') return $error;
			}
		}
		if (!$Connection->tableExists('stats_tracker')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_tracker.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_tracker.sql');
				if ($error != '') return $error;
			}
		}
		if (!$Connection->tableExists('stats_tracker_country')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_tracker_country.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_tracker_country.sql');
				if ($error != '') return $error;
			}
		}
		$query = "UPDATE config SET value = '47' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_47() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->tableExists('stats_marine_type')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_marine_type.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_marine_type.sql');
				if ($error != '') return $error;
			}
		}
		$query = "UPDATE config SET value = '48' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_48() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->tableExists('stats_tracker_type')) {
			if ($globalDBdriver == 'mysql') {
				$error .= create_db::import_file('../db/stats_tracker_type.sql');
				if ($error != '') return $error;
			} else {
				$error .= create_db::import_file('../db/pgsql/stats_tracker_type.sql');
				if ($error != '') return $error;
			}
		}
		$query = "UPDATE config SET value = '49' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_49() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/airport.sql');
			if ($error != '') return $error;
		} else {
			$error .= create_db::import_file('../db/pgsql/airport.sql');
			if ($error != '') return $error;
		}
		$query = "UPDATE config SET value = '50' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_50() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/aircraft.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/aircraft_block.sql');
			if ($error != '') return $error;
		} else {
			$error .= create_db::import_file('../db/pgsql/aircraft.sql');
			if ($error != '') return $error;
			$error .= create_db::import_file('../db/pgsql/aircraft_block.sql');
			if ($error != '') return $error;
		}
		$query = "UPDATE config SET value = '51' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_51() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('marine_live','captain_name')) {
			$query = "ALTER TABLE marine_live ADD COLUMN captain_name varchar(255) DEFAULT NULL,ADD COLUMN captain_id varchar(255) DEFAULT NULL,ADD COLUMN race_name varchar(255) DEFAULT NULL,ADD COLUMN race_id varchar(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns captain and race in marine_live) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_output','captain_name')) {
			$query = "ALTER TABLE marine_output ADD COLUMN captain_name varchar(255) DEFAULT NULL,ADD COLUMN captain_id varchar(255) DEFAULT NULL,ADD COLUMN race_name varchar(255) DEFAULT NULL,ADD COLUMN race_id varchar(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns captain and race in marine_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive','captain_name')) {
			$query = "ALTER TABLE marine_archive ADD COLUMN captain_name varchar(255) DEFAULT NULL,ADD COLUMN captain_id varchar(255) DEFAULT NULL,ADD COLUMN race_name varchar(255) DEFAULT NULL,ADD COLUMN race_id varchar(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns captain and race in marine_archive) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive_output','captain_name')) {
			$query = "ALTER TABLE marine_archive_output ADD COLUMN captain_name varchar(255) DEFAULT NULL,ADD COLUMN captain_id varchar(255) DEFAULT NULL,ADD COLUMN race_name varchar(255) DEFAULT NULL,ADD COLUMN race_id varchar(255) DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns captain and race in marine_archive_output) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '52' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_52() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$query = "ALTER TABLE marine_live MODIFY COLUMN ground_speed float DEFAULT NULL;ALTER TABLE marine_output MODIFY COLUMN ground_speed float DEFAULT NULL;ALTER TABLE marine_archive_output MODIFY COLUMN ground_speed float DEFAULT NULL;ALTER TABLE marine_archive MODIFY COLUMN ground_speed float DEFAULT NULL;";
		} else {
			$query = "ALTER TABLE marine_live ALTER COLUMN ground_speed TYPE float;ALTER TABLE marine_output ALTER COLUMN ground_speed TYPE float;ALTER TABLE marine_archive_output ALTER COLUMN ground_speed TYPE float;ALTER TABLE marine_archive ALTER COLUMN ground_speed TYPE float;";
		}
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (modify column ground_speede in marine_*) : ".$e->getMessage()."\n";
		}
		$query = "UPDATE config SET value = '53' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_53() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if (!$Connection->checkColumnName('marine_live','distance')) {
			$query = "ALTER TABLE marine_live ADD COLUMN distance float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns distance in marine_live) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_live','race_time')) {
			$query = "ALTER TABLE marine_live ADD COLUMN race_time float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_time in marine_live) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_live','race_rank')) {
			if ($globalDBdriver == 'mysql') {
				$query = "ALTER TABLE marine_live ADD COLUMN race_rank int(11) DEFAULT NULL";
			} else {
				$query = "ALTER TABLE marine_live ADD COLUMN race_rank integer DEFAULT NULL";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_rank in marine_live) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_output','distance')) {
			$query = "ALTER TABLE marine_output ADD COLUMN distance float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns distance in marine_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_output','race_time')) {
			$query = "ALTER TABLE marine_output ADD COLUMN race_time float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_time in marine_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_output','race_rank')) {
			if ($globalDBdriver == 'mysql') {
				$query = "ALTER TABLE marine_output ADD COLUMN race_rank int(11) DEFAULT NULL";
			} else {
				$query = "ALTER TABLE marine_output ADD COLUMN race_rank integer DEFAULT NULL";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_rank in marine_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive','distance')) {
			$query = "ALTER TABLE marine_archive ADD COLUMN distance float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns distance in marine_archive) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive','race_time')) {
			$query = "ALTER TABLE marine_archive ADD COLUMN race_time float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_time in marine_archive) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive','race_rank')) {
			if ($globalDBdriver == 'mysql') {
				$query = "ALTER TABLE marine_archive ADD COLUMN race_rank int(11) DEFAULT NULL";
			} else {
				$query = "ALTER TABLE marine_archive ADD COLUMN race_rank integer DEFAULT NULL";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_rank in marine_archive) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive_output','distance')) {
			$query = "ALTER TABLE marine_archive_output ADD COLUMN distance float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns distance in marine_archive_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive_output','race_time')) {
			$query = "ALTER TABLE marine_archive_output ADD COLUMN race_time float DEFAULT NULL";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_time in marine_archive_output) : ".$e->getMessage()."\n";
			}
		}
		if (!$Connection->checkColumnName('marine_archive_output','race_rank')) {
			if ($globalDBdriver == 'mysql') {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN race_rank int(11) DEFAULT NULL";
			} else {
				$query = "ALTER TABLE marine_archive_output ADD COLUMN race_rank integer DEFAULT NULL";
			}
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (add columns race_rank in marine_live) : ".$e->getMessage()."\n";
			}
		}
		if ($Connection->checkColumnName('marine_output','last_altitude')) {
			$query = "ALTER TABLE marine_output DROP COLUMN last_altitude";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (drop columns last_altitude in marine_output) : ".$e->getMessage()."\n";
			}
		}
		if ($Connection->checkColumnName('marine_archive_output','last_altitude')) {
			$query = "ALTER TABLE marine_archive_output DROP COLUMN last_altitude";
			try {
				$sth = $Connection->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error (drop columns last_altitude in marine_archive_output) : ".$e->getMessage()."\n";
			}
		}
		$query = "UPDATE config SET value = '54' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}

	private static function update_from_54() {
		global $globalDBdriver;
		$Connection = new Connection();
		$error = '';
		if ($globalDBdriver == 'mysql') {
			$error .= create_db::import_file('../db/marine_race.sql');
			if ($error != '') return $error;
		} else {
			$error .= create_db::import_file('../db/pgsql/marine_race.sql');
			if ($error != '') return $error;
		}
		$query = "UPDATE config SET value = '55' WHERE name = 'schema_version'";
		try {
			$sth = $Connection->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error (update schema_version) : ".$e->getMessage()."\n";
		}
		return $error;
	}


	public static function check_version($update = false) {
		global $globalDBname;
		$version = 0;
		$Connection = new Connection();
		if (!$Connection->connectionExists()) {
			return "error (check_version): Can't connect to ".$globalDBname."\n";
		} else {
			if ($Connection->tableExists('aircraft')) {
				if (!$Connection->tableExists('config')) {
					$version = '1';
					if ($update) return self::update_from_1();
					else return $version;
				} else {
					$query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
					try {
						$sth = $Connection->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error (check_version): ".$e->getMessage()."\n";
					}
					$result = $sth->fetch(PDO::FETCH_ASSOC);
					if ($update) {
						if ($result['value'] == '2') {
							$error = self::update_from_2();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '3') {
							$error = self::update_from_3();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '4') {
							$error = self::update_from_4();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '5') {
							$error = self::update_from_5();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '6') {
							$error = self::update_from_6();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '7') {
							$error = self::update_from_7();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '8') {
							$error = self::update_from_8();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '9') {
							$error = self::update_from_9();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '10') {
							$error = self::update_from_10();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '11') {
							$error = self::update_from_11();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '12') {
							$error = self::update_from_12();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '13') {
							$error = self::update_from_13();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '14') {
							$error = self::update_from_14();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '15') {
							$error = self::update_from_15();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '16') {
							$error = self::update_from_16();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '17') {
							$error = self::update_from_17();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '18') {
							$error = self::update_from_18();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '19') {
							$error = self::update_from_19();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '20') {
							$error = self::update_from_20();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '21') {
							$error = self::update_from_21();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '22') {
							$error = self::update_from_22();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '23') {
							$error = self::update_from_23();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '24') {
							$error = self::update_from_24();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '25') {
							$error = self::update_from_25();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '26') {
							$error = self::update_from_26();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '27') {
							$error = self::update_from_27();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '28') {
							$error = self::update_from_28();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '29') {
							$error = self::update_from_29();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '30') {
							$error = self::update_from_30();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '31') {
							$error = self::update_from_31();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '32') {
							$error = self::update_from_32();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '33') {
							$error = self::update_from_33();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '34') {
							$error = self::update_from_34();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '35') {
							$error = self::update_from_35();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '36') {
							$error = self::update_from_36();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '37') {
							$error = self::update_from_37();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '38') {
							$error = self::update_from_38();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '39') {
							$error = self::update_from_39();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '40') {
							$error = self::update_from_40();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '41') {
							$error = self::update_from_41();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '42') {
							$error = self::update_from_42();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '43') {
							$error = self::update_from_43();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '44') {
							$error = self::update_from_44();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '45') {
							$error = self::update_from_45();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '46') {
							$error = self::update_from_46();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '47') {
							$error = self::update_from_47();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '48') {
							$error = self::update_from_48();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '49') {
							$error = self::update_from_49();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '50') {
							$error = self::update_from_50();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '51') {
							$error = self::update_from_51();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '52') {
							$error = self::update_from_52();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '53') {
							$error = self::update_from_53();
							if ($error != '') return $error;
							else return self::check_version(true);
						} elseif ($result['value'] == '54') {
							$error = self::update_from_54();
							if ($error != '') return $error;
							else return self::check_version(true);
						} else return '';
					} else {
						if (isset($result['value']) && $result['value'] != '') return $result['value'];
						else return 0;
					}
				}
			} else return $version;
		}
	}
}
//echo update_schema::check_version();
?>