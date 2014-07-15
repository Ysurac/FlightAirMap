<?php
$globalURL = "http://www.barriespotter.com/";

class Connection{

	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/	
		
	public static function createDBConnection()
	{
		// DATABASE CONNECTION
		$dbhost = 'localhost';
		$dbuser = ''; //database username
		$dbpass = ''; //database password
		$dbname = ''; //database name
		
		if($conn = mysql_connect($dbhost, $dbuser, $dbpass))
		{
			mysql_select_db($dbname);
			
			return true;
		} else {
			return false;
		}
	}

}
?>