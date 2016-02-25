<?php
require_once('settings.php');
require_once('class.Connection.php');

class ATC {
	public $db;
        function __construct($dbc = null) {
	    if ($dbc === null) {
    		$Connection = new Connection();
    		$this->db = $Connection->db;
            } else $this->db = $dbc;
	}

       public function getAll() {
                $query = "SELECT * FROM atc GROUP BY ident";
                $query_values = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }

       public function add($ident,$frequency,$latitude,$longitude,$range,$info,$date,$type = '',$ivao_id = '',$ivao_name = '') {
    		$info = preg_replace('/[^(\x20-\x7F)]*/','',$info);
    		$info = str_replace('^','<br />',$info);
    		$info = str_replace('&amp;sect;','',$info);
    		$info = str_replace('"','',$info);
    		if ($type == '') $type = NULL;
                $query = "INSERT INTO atc (ident,frequency,latitude,longitude,atc_range,info,atc_lastseen,type,ivao_id,ivao_name) VALUES (:ident,:frequency,:latitude,:longitude,:range,:info,:date,:type,:ivao_id,:ivao_name)";
                $query_values = array(':ident' => $ident,':frequency' => $frequency,':latitude' => $latitude,':longitude' => $longitude,':range' => $range,':info' => $info,':date' => $date,':ivao_id' => $ivao_id,':ivao_name' => $ivao_name, ':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

       public function deleteById($id) {
                $query = "DELETE FROM atc WHERE atc_id = :id";
                $query_values = array(':id' => $id);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

       public function deleteAll() {
                $query = "DELETE FROM atc";
                $query_values = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

	public function deleteOldATC() {
                global $globalDBdriver;
                if ($globalDBdriver == 'mysql') {
                        $query  = "DELETE FROM atc WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= atc.atc_lastseen";
                } elseif ($globalDBdriver == 'pgsql') {
                        $query  = "DELETE FROM atc WHERE NOW() AT TIME ZONE 'UTC' - '1 HOUR'->INTERVAL >= atc.atc_lastseen";
                }
                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error";
                }
                return "success";
        }
}
?>