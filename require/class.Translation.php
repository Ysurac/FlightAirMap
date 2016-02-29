<?php
require_once('settings.php');
require_once('class.Connection.php');
require_once('class.Spotter.php');
require_once('class.Common.php');
require_once('libs/uagent/uagent.php');


class Translation {
    public $db;
    function __construct($dbc = null) {
	    $Connection = new Connection($dbc);
	    $this->db = $Connection->db;
    }

    /**
    * Change IATA to ICAO value for ident
    * 
    * @param String $ident ident
    * @return String the icao
    */
    public function ident2icao($ident) {
	$Spotter = new Spotter();
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
            $identicao = $Spotter->getAllAirlineInfo($airline_icao);
            if (isset($identicao[0])) {
                $icao = $identicao[0]['icao'].ltrim(substr($ident,2),'0');
            } else $icao = $ident;
        }
        return $icao;
    }


       public function getOperator($ident) {
                $query = "SELECT * FROM translation WHERE Operator = :ident LIMIT 1";
                $query_values = array(':ident' => $ident);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                if (count($row) > 0) {
                        return $row['operator_correct'];
                } else return $ident;
        }

       public function addOperator($ident,$correct_ident,$source) {
                $query = "INSERT INTO translation (Operator,Operator_correct,Source) VALUES (:ident,:correct_ident,:source)";
                $query_values = array(':ident' => $ident,':correct_ident' => $correct_ident, ':source' => $source);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

       public function updateOperator($ident,$correct_ident,$source) {
                $query = "UPDATE translation SET Operator_correct = :correct_ident,Source = :source WHERE Operator = :ident";
                $query_values = array(':ident' => $ident,':correct_ident' => $correct_ident, ':source' => $source);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
        
        public function checkTranslation($ident,$web = false) {
    	    global $globalTranslationSources, $globalTranslationFetch;
    	    //if (!isset($globalTranslationSources)) $globalTranslationSources = array('planefinder');
    	    $globalTranslationSources = array();
    	    if (!isset($globalTranslationFetch)) $globalTranslationFetch = TRUE;
    	    //echo "Check Translation for ".$ident."...";
    	    $correct = $this->getOperator($ident);
    	    if ($correct != '' && $correct != $ident) {
    		//echo "Found in DB !\n";
    		 return $correct;
    	    } elseif ($web && $globalTranslationFetch) {
    		if (! is_numeric(substr($ident,-4))) {
    		    if (count($globalTranslationSources) > 0) {
    			$correct = $this->fromPlanefinder($ident);
    			if ($correct != '') {
    			    $correct = $this->ident2icao($correct);
    			    if ($correct != $ident) {
    				$this->addOperator($ident,$correct,'planefinder');
    				//echo "Add to DB ! (".$correct.") \n";
    				return $correct;
    			    }
    			}
    		    }
    		}
    	    }
    	    return $this->ident2icao($ident);
        }

    
    function fromPlanefinder($icao) {
	$url = 'http://planefinder.net/data/endpoints/search_ajax.php?searchText='.$icao;
	$Common = new Common();
	$json = $Common->getData($url);
	$parsed_json = json_decode($json);
	if (isset($parsed_json->flights[0]->title) && isset($parsed_json->flights[0]->subtitle) && $parsed_json->flights[0]->subtitle == $icao) return $parsed_json->flights[0]->title;
	else return '';
    }
}
//echo Translation->checkTranslation('EZY268X');
//Translation->fromPlanefinder('EZY268X');
?>