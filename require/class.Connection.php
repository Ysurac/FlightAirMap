<?php
require_once('settings.php');

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
        
        if($conn = mysql_connect($globalDBhost, $globalDBuser, $globalDBpass))
		{
			mysql_select_db($globalDBname);
			
			return true;
		} else {
			return false;
		}
	}

}
?>