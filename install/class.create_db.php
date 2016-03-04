<?php
require_once(dirname(__FILE__).'/../require/settings.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');

class create_db {
	public static function import_file($filename) {
		$filename = filter_var($filename,FILTER_SANITIZE_STRING);
		$Connection = new Connection();
                //Connection::$db->beginTransaction();
                 $templine = '';
                 $lines = file($filename);
                 foreach ($lines as $line)
                 {
                	if (substr($line,0,2) == '--' || $line == '') continue;
                	$templine .= $line;
                	if (substr(trim($line), -1,1) == ';')
                	{
                		try {
                			$sth = $Connection->db->prepare($templine);
					$sth->execute();
                    		} catch(PDOException $e) {
					return "error (import ".$filename.") : ".$e->getMessage()."\n";
                    		}
                		$templine = '';
                	}
		}
                //Connection::$db->commit();
                $Connection->db = null;
                return '';
	}

	public static function import_all_db($directory) {
		$error = '';
		$dh = opendir($directory);
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $filename)
		while(false !== ($filename = readdir($dh)))
		{
		    if (preg_match('/\.sql$/',$filename)) $error .= create_db::import_file($directory.$filename);
		}
		return $error;
	}

	public static function create_database($root,$root_pass,$user,$pass,$db,$db_type,$host) {
		$root = filter_var($root,FILTER_SANITIZE_STRING);
		$root_pass = filter_var($root_pass,FILTER_SANITIZE_STRING);
		$user = filter_var($user,FILTER_SANITIZE_STRING);
		$password = filter_var($pass,FILTER_SANITIZE_STRING);
		$db = filter_var($db,FILTER_SANITIZE_STRING);
		$db_type = filter_var($db_type,FILTER_SANITIZE_STRING);
		$host = filter_var($host,FILTER_SANITIZE_STRING);
		// Dirty hack
		if ($host != 'localhost' && $host != '127.0.0.1') {
		    $grantright = $_SERVER['SERVER_ADDR'];
		} else $grantright = 'localhost';
		try {
			$dbh = new PDO($db_type.':host='.$host,$root,$root_pass);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			if ($db_type == 'mysql') {
				$dbh->exec('CREATE DATABASE IF NOT EXISTS `'.$db.'`;GRANT ALL ON `'.$db."`.* TO '".$user."'@'".$grantright."' IDENTIFIED BY '".$password."';FLUSH PRIVILEGES;");
				if ($grantright == 'localhost') $dbh->exec('GRANT ALL ON `'.$db."`.* TO '".$user."'@'127.0.0.1' IDENTIFIED BY '".$password."';FLUSH PRIVILEGES;");
			} else if ($db_type == 'pgsql') {
				$dbh->exec("CREATE DATABASE ".$db.";
					CREATE USER ".$user." WITH PASSWORD '".$password."';
					GRANT ALL PRIVILEGES ON DATABASE ".$db." TO ".$user.";");
			}
		} catch(PDOException $e) {
			$dbh = null;
			return "error : ".$e->getMessage();
		}
		$dbh = null;
	}
	
}

?>