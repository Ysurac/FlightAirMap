<?php
require_once('../require/settings.php');
require_once('../require/class.Connection.php');
require_once('../require/class.Scheduler.php');
require_once('class.create_db.php');

class update_schema {

	public static function update_schedule() {
	    $Connection = new Connection();
	    $query = "SELECT * FROM schedule";
            try {
            	$sth = Connection::$db->prepare($query);
		$sth->execute();
    	    } catch(PDOException $e) {
		return "error : ".$e->getMessage()."\n";
    	    }
    	    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    		Schedule::addSchedule($row['ident'],$row['departure_airport_icao'],$row['departure_airport_time'],$row['arrival_airport_icao'],$row['arrival_airport_time']);
    	    }
	
	}

	private static function tableExists($tableName) {
	    $Connection = new Connection();
	    $query = "SHOW TABLES LIKE :tableName";
            try {
            	$sth = Connection::$db->prepare($query);
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
	
	private static function update_from_1() {
    		$Connection = new Connection();
    		// Add new column to routes table
    		$query = "ALTER TABLE `routes` ADD `FromAirport_Time` VARCHAR(10),`ToAirport_Time` VARCHAR(10),`Source` VARCHAR(255),`date_added` DATETIME DEFAULT CURRENT TIMESTAMP,`date_modified` DATETIME,`date_lastseen` DATETIME";
        	try {
            	    $sth = Connection::$db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add new columns to routes table) : ".$e->getMessage()."\n";
    		}
    		// Copy schedules data to routes table
    		self::update_schedule();
    		// Delete schedule table
		$query = "DROP TABLE `schedule`";
        	try {
            	    $sth = Connection::$db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (delete schedule table) : ".$e->getMessage()."\n";
    		}
    		// Add source column
    		$query = "ALTER TABLE `aircraft_modes` ADD `Source` VARCHAR(255)";
    		try {
            	    $sth = Connection::$db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (add source column to aircraft_modes) : ".$e->getMessage()."\n";
    		}
		// Delete unused column
		$query = "ALTER TABLE `aircraft_modes`  DROP `SerialNo`,  DROP `OperatorFlagCode`,  DROP `Manufacturer`,  DROP `Type`,  DROP `FirstRegDate`,  DROP `CurrentRegDate`,  DROP `Country`,  DROP `PreviousID`,  DROP `DeRegDate`,  DROP `Status`,  DROP `PopularName`,  DROP `GenericName`,  DROP `AircraftClass`,  DROP `Engines`,  DROP `OwnershipStatus`,  DROP `RegisteredOwners`,  DROP `MTOW`,  DROP `TotalHours`,  DROP `YearBuilt`,  DROP `CofACategory`,  DROP `CofAExpiry`,  DROP `UserNotes`,  DROP `Interested`,  DROP `UserTag`,  DROP `InfoUrl`,  DROP `PictureUrl1`,  DROP `PictureUrl2`,  DROP `PictureUrl3`,  DROP `UserBool1`,  DROP `UserBool2`,  DROP `UserBool3`,  DROP `UserBool4`,  DROP `UserBool5`,  DROP `UserString1`,  DROP `UserString2`,  DROP `UserString3`,  DROP `UserString4`,  DROP `UserString5`,  DROP `UserInt1`,  DROP `UserInt2`,  DROP `UserInt3`,  DROP `UserInt4`,  DROP `UserInt5`";
    		try {
            	    $sth = Connection::$db->prepare($query);
		    $sth->execute();
    		} catch(PDOException $e) {
		    return "error (Delete unused column of aircraft_modes) : ".$e->getMessage()."\n";
    		}
    		$error = '';
		$error .= create_db::import_file('../db/acars_live.sql');
		$error .= create_db::import_file('../db/config.sql');
		return $error;
        }
    	
    	public static function check_version($update = false) {
    	    $version = 0;
    	    if (self::tableExists('aircraft')) {
    		if (!self::tableExists('config')) {
    		    $version = '1';
    		    if ($update) self::update_from_1();
    		    else return $version;
		} else {
    		    $Connection = new Connection();
		    $query = "SELECT value FROM `config` WHERE `name` = 'schema_version' LIMIT 1";
		    try {
            		$sth = Connection::$db->prepare($query);
		        $sth->execute();
		    } catch(PDOException $e) {
			return "error : ".$e->getMessage()."\n";
    		    }
    		    $result = $sth->fetch(PDO::FETCH_ASSOC);
    		    return $result['value'];
		}
		
	    } else return $version;
    	}
}
//echo update_schema::check_version();
?>