<?php
include("settings.php");

class Connection{

	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/

	public static function createDBConnection()
	{
		global $dbhost, $dbuser, $dbpass, $dbname

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
