<?php
require_once('../require/settings.php');
require_once('../require/class.Connection.php');
require_once('../require/class.Scheduler.php');
require_once('class.create_db.php');
require_once('class.update_db.php');

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
    		if (!$Connection−>indexExists('spotter_output','flightaware_id')) {
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
    			$query="ALTER TABLE spotter_archive ADD pilot VARCHAR(255) NULL";
            	    }
                } else {
    		    $query="ALTER TABLE spotter_archive ADD pilot VARCHAR(255) NULL";
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

    	public static function check_version($update = false) {
    	    global $globalDBname;
    	    $version = 0;
    	    $Connection = new Connection();
    	    if ($Connection->tableExists('aircraft')) {
    		if (!$Connection->tableExists('config')) {
    		    $version = '1';
    		    if ($update) return self::update_from_1();
    		    else return $version;
		} else {
    		    $Connection = new Connection();
		    $query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
		    try {
            		$sth = $Connection->db->prepare($query);
		        $sth->execute();
		    } catch(PDOException $e) {
			return "error : ".$e->getMessage()."\n";
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
    			} else return '';
    		    }
    		    else return $result['value'];
		}
		
	    } else return $version;
    	}
    	
}
//echo update_schema::check_version();
?>