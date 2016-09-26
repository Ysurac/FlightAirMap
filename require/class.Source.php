<?php
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Connection.php');

class Source {
	public $db;
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
        }
       public function getAllLocationInfo() {
                $query = "SELECT * FROM source_location";
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
       public function getLocationInfobyName($name) {
                $query = "SELECT * FROM source_location WHERE name = :name";
                $query_values = array(':name' => $name);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
       public function getLocationInfobySourceName($name) {
                $query = "SELECT * FROM source_location WHERE source = :name";
                $query_values = array(':name' => $name);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
  
       public function addLocation($name,$latitude,$longitude,$altitude,$city,$country,$source,$logo = 'antenna.png') {
                $query = "INSERT INTO source_location (name,latitude,longitude,altitude,country,city,logo,source) VALUES (:name,:latitude,:longitude,:altitude,:country,:city,:logo,:source)";
                $query_values = array(':name' => $name,':latitude' => $latitude, ':longitude' => $longitude,':altitude' => $altitude,':city' => $city,':country' => $country,':logo' => $logo,':source' => $source);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

       public function deleteLocation($id) {
                $query = "DELETE FROM source_location WHERE id = :id";
                $query_values = array(':id' => $id);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
       public function deleteAllLocation() {
                $query = "DELETE FROM source_location";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
}
?>