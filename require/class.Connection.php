<?php
require_once("settings.php");

class Connection{
	public static $db;
	public static $latest_schema = 6;
	
	public function __construct() {
	    $this->createDBConnection();
	}


	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/

	public static function createDBConnection()
	{
		global $globalDBdriver, $globalDBhost, $globalDBuser, $globalDBpass, $globalDBname;
		try {
			self::$db = new PDO("$globalDBdriver:host=$globalDBhost;dbname=$globalDBname", $globalDBuser,  $globalDBpass);
			self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$db->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
			self::$db->setAttribute(PDO::ATTR_TIMEOUT,10);
		} catch(PDOException $e) {
			echo $e->getMessage();
			exit;
			return false;
		}
		return true;
	}

	public static function tableExists($table)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SHOW TABLES LIKE '".$table."'";
		} elseif ($globalDBdriver == 'pgsql') {
			$query = "SELECT * FROM pg_catalog.pg_tables WHERE tablename = '".$table."'";
		}
		try {
			$Connection = new Connection();
			$results = Connection::$db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	public static function check_schema_version() {
		$version = 0;
		if (self::tableExists('aircraft')) {
			if (!self::tableExists('config')) {
	    			$version = '1';
	    			return $version;
			} else {
				$Connection = new Connection();
				$query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
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
	
	public static function latest() {
	    if (self::check_schema_version() == self::$latest_schema) return true;
	    else return false;
	}

}
?>
