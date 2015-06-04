<?php
require_once('settings.php');
require_once('class.Connection.php');

class Source {

       public static function getAllLocationInfo() {
                $query = "SELECT * FROM source_location";
                $query_values = array();
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }

       public static function addLocation($name,$latitude,$longitude,$altitude,$city,$country,$logo = 'antenna.png') {
                $query = "INSERT INTO source_location (name,latitude,longitude,altitude,country,city,logo) VALUES (:name,:latitude,:longitude,:altitude,:country,:city,:logo)";
                $query_values = array(':name' => $name,':latitude' => $latitude, ':longitude' => $longitude,':altitude' => $altitude,':city' => $city,':country' => $country,':logo' => $logo);
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

       public static function deleteLocation($id) {
                $query = "DELETE FROM source_location WHERE id = :id";
                $query_values = array(':id' => $id);
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
       public static function deleteAllLocation() {
                $query = "DELETE FROM source_location";
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
}
?>