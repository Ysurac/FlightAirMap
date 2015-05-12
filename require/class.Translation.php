<?php
require_once('settings.php');
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.Common.php');
require_once('libs/uagent/uagent.php');


class Translation {

    /**
    * Change IATA to ICAO value for ident
    * 
    * @param String $ident ident
    * @return String the icao
    */
    public static function ident2icao($ident) {
	if (!is_numeric(substr($ident, 0, 3)))
        {
	    if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
        	$airline_icao = substr($ident, 0, 2);
            } elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
        	//$airline_icao = substr($ident, 0, 3);
        	return $ident;
            } else return $ident;
        } else return $ident;
        if ($airline_icao == 'AF') {
            if (filter_var(substr($ident,2),FILTER_VALIDATE_INT,array("flags"=>FILTER_FLAG_ALLOW_OCTAL))) $icao = $ident;
            else $icao = 'AFR'.ltrim(substr($ident,2),'0');
        } else {
            $identicao = Spotter::getAllAirlineInfo($airline_icao);
            if (isset($identicao[0])) {
                $icao = $identicao[0]['icao'].ltrim(substr($ident,2),'0');
            } else $icao = $ident;
        }
        return $icao;
    }


       public static function getOperator($ident) {
                $query = "SELECT * FROM translation WHERE Operator = :ident LIMIT 1";
                $query_values = array(':ident' => $ident);
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if (count($row) > 0) {
                        return $row['Operator_correct'];
                } else return $ident;
        }

       public static function addOperator($ident,$correct_ident,$source) {
                $query = "INSERT INTO translation (Operator,Operator_correct,Source) VALUES (:ident,:correct_ident,:source)";
                $query_values = array(':ident' => $ident,':correct_ident' => $correct_ident, ':source' => $source);
                 try {
                        $Connection = new Connection();
                        $sth = Connection::$db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
        
        public static function checkTranslation($ident,$web = true) {
    	    //echo "Check Translation for ".$ident."...";
    	    $correct = Translation::getOperator($ident);
    	    if ($correct != '' && $correct != $ident) {
    		//echo "Found in DB !\n";
    		 return $correct;
    	    } elseif ($web) {
    		if (! is_numeric(substr($ident,-4))) {
    		    $correct = Translation::fromPlanefinder($ident);
    		    if ($correct != '') {
    			$correct = Translation::ident2icao($correct);
    			if ($correct != $ident) {
    				Translation::addOperator($ident,$correct,'planefinder');
    				//echo "Add to DB ! (".$correct.") \n";
    				return $correct;
    			}
    		    }
    		}
    	    }
    	    return Translation::ident2icao($ident);
        }

    
    static function fromPlanefinder($icao) {
	$url = 'http://planefinder.net/data/endpoints/search_ajax.php?searchText='.$icao;
	$json = Common::getData($url);
	$parsed_json = json_decode($json);
	if (isset($parsed_json->flights[0]->title) && isset($parsed_json->flights[0]->subtitle) && $parsed_json->flights[0]->subtitle == $icao) return $parsed_json->flights[0]->title;
	else return '';
    }
}
//echo Translation::checkTranslation('EZY268X');
//Translation::fromPlanefinder('EZY268X');
?>