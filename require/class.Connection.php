<?php
require_once("settings.php");

class Connection{

	/**
	* Creates the database connection
	*
	* @return Boolean of the database connection
	*
	*/

	public static function createDBConnection()
	{
		global $globalDBhost, $globalDBuser, $globalDBpass, $globalDBname;

		if($conn = ($GLOBALS["___mysqli_ston"] = mysqli_connect($globalDBhost,  $globalDBuser,  $globalDBpass)))
		{
			((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE $globalDBname"));

			return true;
		} else {
			return false;
		}
	}

}
?>
