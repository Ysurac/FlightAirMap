<?php
/**
 * This class is part of FlightAirmap. It's used for DB connection
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/settings.php');

class Connection{
	/** @var $db PDO */
	public $db;
	public $dbs = array();
	public $latest_schema = 55;

	public function __construct($dbc = null,$dbname = null,$user = null,$pass = null) {
		global $globalNoDB;
		if (!isset($globalNoDB) || $globalNoDB === FALSE) {
			if ($dbc === null) {
				if (empty($this->db) && $dbname === null) {
					if ($user === null && $pass === null) {
						$this->createDBConnection();
					} else {
						$this->createDBConnection(null,$user,$pass);
					}
				} else {
					$this->createDBConnection($dbname);
				}
			} elseif ($dbname === null || $dbname === 'default') {
				$this->db = $dbc;
				if ($this->connectionExists() === false) {
					/*
					echo 'Restart Connection !!!'."\n";
					$e = new \Exception;
					var_dump($e->getTraceAsString());
					*/
					$this->createDBConnection();
				}
			} else {
				//$this->connectionExists();
				$this->dbs[$dbname] = $dbc;
			}
		}
	}

	public function db() {
		global $globalNoDB;
		if (!isset($globalNoDB) || $globalNoDB === FALSE) {
			if (empty($this->db)) {
				$this->__construct();
			}
			if (empty($this->db)) {
				echo 'Can\'t connect to database. Check configuration and database status.';
				die;
			} else {
				return $this->db;
			}
		}
	}

	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/
	public function createDBConnection($DBname = null, $user = null, $pass = null)
	{
		global $globalDBdriver, $globalDBhost, $globalDBuser, $globalDBpass, $globalDBname, $globalDebug, $globalDB, $globalDBport, $globalDBTimeOut, $globalDBretry, $globalDBPersistent;
		if ($DBname === null) {
			if ($user === null && $pass === null) {
				$DBname = 'default';
				$globalDBSdriver = $globalDBdriver;
				$globalDBShost = $globalDBhost;
				$globalDBSname = $globalDBname;
				$globalDBSuser = $globalDBuser;
				$globalDBSpass = $globalDBpass;
				if (!isset($globalDBport) || $globalDBport === NULL || $globalDBport == '') $globalDBSport = 3306;
				else $globalDBSport = $globalDBport;
			} else {
				$DBname = 'default';
				$globalDBSdriver = $globalDBdriver;
				$globalDBShost = $globalDBhost;
				$globalDBSname = $globalDBname;
				$globalDBSuser = $user;
				$globalDBSpass = $pass;
				if (!isset($globalDBport) || $globalDBport === NULL || $globalDBport == '') $globalDBSport = 3306;
				else $globalDBSport = $globalDBport;
			}
		} else {
			$globalDBSdriver = $globalDB[$DBname]['driver'];
			$globalDBShost = $globalDB[$DBname]['host'];
			$globalDBSname = $globalDB[$DBname]['name'];
			$globalDBSuser = $globalDB[$DBname]['user'];
			$globalDBSpass = $globalDB[$DBname]['pass'];
			if (isset($globalDB[$DBname]['port'])) $globalDBSport = $globalDB[$DBname]['port'];
			else $globalDBSport = 3306;
		}
		if ($globalDBSname == '' || $globalDBSuser == '') return false;
		// Set number of try to connect to DB
		if (!isset($globalDBretry) || $globalDBretry == '' || $globalDBretry === NULL) $globalDBretry = 5;
		$i = 0;
		while (true) {
			try {
				if ($globalDBSdriver == 'mysql') {
					$this->dbs[$DBname] = new PDO("$globalDBSdriver:host=$globalDBShost;port=$globalDBSport;dbname=$globalDBSname;charset=utf8", $globalDBSuser,  $globalDBSpass);
					$this->dbs[$DBname]->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
					if (!isset($globalDBTimeOut)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,500);
					else $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,$globalDBTimeOut);
					if (!isset($globalDBPersistent)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,true);
					else $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,$globalDBPersistent);
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
					$this->dbs[$DBname]->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
					// Workaround against "ONLY_FULL_GROUP_BY" mode
					// to enable it : $this->dbs[$DBname]->exec('SET sql_mode = "NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY"');
					$this->dbs[$DBname]->exec('SET sql_mode = "NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');
					// Force usage of UTC
					$this->dbs[$DBname]->exec('SET SESSION time_zone = "+00:00"');
					//$this->dbs[$DBname]->exec('SET @@session.time_zone = "+00:00"');
				} else {
					$this->dbs[$DBname] = new PDO("$globalDBSdriver:host=$globalDBShost;port=$globalDBSport;dbname=$globalDBSname;options='--client_encoding=utf8'", $globalDBSuser,  $globalDBSpass);
					//$this->dbs[$DBname]->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_CASE,PDO::CASE_LOWER);
					if (!isset($globalDBTimeOut)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,200);
					else $this->dbs[$DBname]->setAttribute(PDO::ATTR_TIMEOUT,$globalDBTimeOut);
					if (!isset($globalDBPersistent)) $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,true);
					else $this->dbs[$DBname]->setAttribute(PDO::ATTR_PERSISTENT,$globalDBPersistent);
					$this->dbs[$DBname]->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
					$this->dbs[$DBname]->exec('SET timezone="UTC"');
				}
				break;
			} catch(PDOException $e) {
				$i++;
				if (isset($globalDebug) && $globalDebug) echo 'Error connecting to DB: '.$globalDBSname.' - Error: '.$e->getMessage()."\n";
				//exit;
				if ($i > $globalDBretry) return false;
				//return false;
			}
			sleep(2);
		}
		if ($DBname === 'default') $this->db = $this->dbs['default'];
		return true;
	}

	public function tableExists($table)
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SHOW TABLES LIKE '".$table."'";
		} else {
			$query = "SELECT * FROM pg_catalog.pg_tables WHERE tablename = '".$table."'";
		}
		if ($this->db == NULL) return false;
		try {
			//$Connection = new Connection();
			$results = $this->db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		if($results->rowCount()>0) {
		    return true; 
		}
		else return false;
	}

	public function connectionExists()
	{
		global $globalDBCheckConnection, $globalNoDB;
		if (isset($globalDBCheckConnection) && $globalDBCheckConnection === FALSE) return true;
		if (isset($globalNoDB) && $globalNoDB === TRUE) return true;
		$query = "SELECT 1 + 1";
		if ($this->db === null) return false;
		try {
			$sum = @$this->db->query($query);
			if ($sum instanceof \PDOStatement) {
				$sum = $sum->fetchColumn(0);
			} else $sum = 0;
			if (intval($sum) !== 2) {
			     return false;
			}
			
		} catch(PDOException $e) {
			if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
            			throw $e;
	                }
	                //echo 'error ! '.$e->getMessage();
			return false;
		}
		return true; 
	}

	/*
	* Check if index exist
	*/
	public function indexExists($table,$index)
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema=DATABASE() AND table_name='".$table."' AND index_name='".$index."'";
		} else {
			$query = "SELECT count(*) as nb FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE c.relname = '".$index."' AND n.nspname = '".$table."'";
		}
		try {
			//$Connection = new Connection();
			$results = $this->db->query($query);
		} catch(PDOException $e) {
			return false;
		}
		$nb = $results->fetchAll(PDO::FETCH_ASSOC);
		if($nb[0]['nb'] > 0) {
			return true; 
		}
		else return false;
	}

	/*
	* Get columns name of a table
	* @return Array all column name in table
	*/
	public function getColumnName($table)
	{
		$query = "SELECT * FROM ".$table." LIMIT 0";
		try {
			$results = $this->db->query($query);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage()."\n";
		}
		$columns = array();
		$colcnt = $results->columnCount();
		for ($i = 0; $i < $colcnt; $i++) {
			$col = $results->getColumnMeta($i);
			$columns[] = $col['name'];
		}
		return $columns;
	}

	public function getColumnType($table,$column) {
		$select = $this->db->query('SELECT '.$column.' FROM '.$table);
		$tomet = $select->getColumnMeta(0);
		return $tomet['native_type'];
	}

	/*
	* Check if a column name exist in a table
	* @return Boolean column exist or not
	*/
	public function checkColumnName($table,$name)
	{
		global $globalDBdriver, $globalDBname;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT COUNT(*) as nb FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :table AND COLUMN_NAME = :name LIMIT 1";
		} else {
			$query = "SELECT COUNT(*) as nb FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG = :database AND TABLE_NAME = :table AND COLUMN_NAME = :name LIMIT 1";
		}
			try {
				$sth = $this->db()->prepare($query);
				$sth->execute(array(':database' => $globalDBname,':table' => $table,':name' => $name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage()."\n";
				return false;
			}
			$result = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();
			if ($result['nb'] > 0) return true;
			else return false;
/*		} else {
			$query = "SELECT * FROM ".$table." LIMIT 0";
			try {
				$results = $this->db->query($query);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage()."\n";
			}
			$colcnt = $results->columnCount();
			for ($i = 0; $i < $colcnt; $i++) {
				$col = $results->getColumnMeta($i);
				if ($name == $col['name']) return true;
			}
			return false;
		}
*/
	}

	/*
	* Get schema version
	* @return integer schema version
	*/
	public function check_schema_version() {
		$version = 0;
		if ($this->tableExists('aircraft')) {
			if (!$this->tableExists('config')) {
	    			$version = '1';
	    			return $version;
			} else {
				$query = "SELECT value FROM config WHERE name = 'schema_version' LIMIT 1";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage()."\n";
				}
				$result = $sth->fetch(PDO::FETCH_ASSOC);
				$sth->closeCursor();
				return $result['value'];
			}
		} else return $version;
	}
	
	/*
	* Check if schema version is latest_schema
	* @return Boolean if latest version or not
	*/
	public function latest() {
	    global $globalNoDB;
	    if (isset($globalNoDB) && $globalNoDB === TRUE) return true;
	    if ($this->check_schema_version() == $this->latest_schema) return true;
	    else return false;
	}

}
?>
