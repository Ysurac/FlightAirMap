<?php
require_once("settings.php");

class Connection{
	public static $db;
	
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

		self::$db = new PDO("$globalDBdriver:host=$globalDBhost;dbname=$globalDBname", $globalDBuser,  $globalDBpass);
		self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return true;
	}

}
?>
