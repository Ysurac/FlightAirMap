<?php
require_once("settings.php");

class Connection{
	public $db;
	public $latest_schema = 10;
	
	public function __construct() {
	    if ($this->db === null) {
		$this->createDBConnection();
	    }
	}


	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/

	public function createDBConnection()
	{
		global $globalDBdriver, $globalDBhost, $globalDBuser, $globalDBpass, $globalDBname, $globalDebug;
		try {
			$this->db = new PDO("$globalDBdriver:host=$globalDBhost;dbname=$globalDBname;charset=utf8", $globalDBuser,  $globalDBpass);
			$this->db->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
			if (!isset($globalDBTimeOut)) $this->db->setAttribute(PDO::ATTR_TIMEOUT,20);
			else $this->db->setAttribute(PDO::ATTR_TIMEOUT,$globalDBTimeOut);
			$this->db->setAttribute(PDO::ATTR_PERSISTENT,true);
		} catch(PDOException $e) {
			if (isset($globalDebug) && $globalDebug) echo $e->getMessage();
			//exit;
			return false;
		}
		return true;
	}

	public function tableExists($table)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SHOW TABLES LIKE '".$table."'";
		} elseif ($globalDBdriver == 'pgsql') {
			$query = "SELECT * FROM pg_catalog.pg_tables WHERE tablename = '".$table."'";
		}
		try {
			$Connection = new Connection();
			$results = $this->db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	public function indexExists($table,$index)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema=DATABASE() AND table_name='".$table."' AND index_name='".$index."'";
		} elseif ($globalDBdriver == 'pgsql') {
			$query = "SELECT 1 FROM   pg_class c JOIN   pg_namespace n ON n.oid = c.relnamespace WHERE c.relname = '".$index."' AND n.nspname = '".$table."'";
		}
		try {
			$Connection = new Connection();
			$results = $Connection->$db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	public function check_schema_version() {
		$version = 0;
		if ($this->tableExists('aircraft')) {
			if (!$this->tableExists('config')) {
	    			$version = '1';
	    			return $version;
			} else {
				$Connection = new Connection();
				$query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage()."\n";
				}
				$result = $sth->fetch(PDO::FETCH_ASSOC);
				return $result['value'];
			}
		} else return $version;
	}
	
	public function latest() {
	    if ($this->check_schema_version() == $this->latest_schema) return true;
	    else return false;
	}

}
?>
