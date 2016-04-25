<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterArchive.php');
$Spotter = new Spotter();
$orderby = $Spotter->getOrderBy();

$title = "Search";

//$page_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$page_url = $_SERVER['REQUEST_URI'];

//$title = "Search";
require_once('header.php');

if (isset($_GET['start_date'])) {
	//for the date manipulation into the query
	if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
		$start_date = $_GET['start_date']." 00:00:00";
		$end_date = $_GET['end_date']." 00:00:00";
		$sql_date = $start_date.",".$end_date;
	} else if($_GET['start_date'] != ""){
		$start_date = $_GET['start_date']." 00:00:00";
		$sql_date = $start_date;
	} else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
		$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date']." 00:00:00";
		$sql_date = $end_date;
	} else $sql_date = '';
} else $sql_date = '';

if (isset($_GET['highest_altitude'])) {
	//for altitude manipulation
	if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
		$end_altitude = $_GET['highest_altitude'];
		$start_altitude = $_GET['lowest_altitude'];
		$sql_altitude = $start_altitude.",".$end_altitude;
	} else if($_GET['highest_altitude'] != ""){
		$end_altitude = $_GET['highest_altitude'];
		$sql_altitude = $end_altitude;
	} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
		$start_altitude = $_GET['lowest_altitude'].",60000";
		$sql_altitude = $start_altitude;
	} else $sql_altitude = '';
} else $sql_altitude = '';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
	if (!isset($_GET['number_results']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		if ($_GET['number_results'] > 1000){
			$_GET['number_results'] = 1000;
		}
		$limit_start = 0;
		$limit_end = $_GET['number_results'];
		$absolute_difference = $_GET['number_results'];
	}
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = $limit_explode[0];
	$limit_end = $limit_explode[1];
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if (!empty($_GET)){  
	$q = filter_input(INPUT_GET, 'q',FILTER_SANITIZE_STRING);
	$registration = filter_input(INPUT_GET, 'registration',FILTER_SANITIZE_STRING);
	$aircraft = filter_input(INPUT_GET, 'aircraft',FILTER_SANITIZE_STRING);
	$manufacturer = filter_input(INPUT_GET, 'manufacturer',FILTER_SANITIZE_STRING);
	$highlights = filter_input(INPUT_GET, 'highlights',FILTER_SANITIZE_STRING);
	$airline = filter_input(INPUT_GET, 'airline',FILTER_SANITIZE_STRING);
	$airline_country = filter_input(INPUT_GET, 'airline_country',FILTER_SANITIZE_STRING);
	$airline_type = filter_input(INPUT_GET, 'airline_type',FILTER_SANITIZE_STRING);
	$airport = filter_input(INPUT_GET, 'airport',FILTER_SANITIZE_STRING);
	$airport_country = filter_input(INPUT_GET, 'airport_country',FILTER_SANITIZE_STRING);
	$callsign = filter_input(INPUT_GET, 'callsign',FILTER_SANITIZE_STRING);
	$owner = filter_input(INPUT_GET, 'owner',FILTER_SANITIZE_STRING);
	$pilot_name = filter_input(INPUT_GET, 'pilot_name',FILTER_SANITIZE_STRING);
	$pilot_id = filter_input(INPUT_GET, 'pilot_id',FILTER_SANITIZE_STRING);
	$departure_airport_route = filter_input(INPUT_GET, 'departure_airport_route',FILTER_SANITIZE_STRING);
	$arrival_airport_route = filter_input(INPUT_GET, 'arrival_airport_route',FILTER_SANITIZE_STRING);
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$archive = filter_input(INPUT_GET,'archive',FILTER_SANITIZE_NUMBER_INT);
	if (!isset($sql_date)) $sql_date = '';
	if ($archive == 1) {
		$SpotterArchive = new SpotterArchive();
		$spotter_array = $SpotterArchive->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
	} else {
		$spotter_array = $Spotter->searchSpotterData($q,$registration,$aircraft,strtolower(str_replace("-", " ", $manufacturer)),$highlights,$airline,$airline_country,$airline_type,$airport,$airport_country,$callsign,$departure_airport_route,$arrival_airport_route,$owner,$pilot_id,$pilot_name,$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
	}
	 
	 print '<span class="sub-menu-statistic column mobile">';
	 	print '<a href="#" onclick="showSubMenu(); return false;">Export <i class="fa fa-plus"></i></a>';
	 	print '</span>';
	 	print '<div class="sub-menu sub-menu-container">';
	 		print '<ul class="nav">';
	 			print '<li class="dropdown">';
		    	print '<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="fa fa-download"></i> Download Search Results <span class="caret"></span></a>';
				    print '<ul class="dropdown-menu">';
				      print '<li><a href="'.$globalURL.'/search/csv?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">CSV</a></li>';
				      print '<li><a href="'.$globalURL.'/search/rss?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">RSS</a></li>';
				      print '<li><hr /></li>';
				      print '<li><span>For Advanced Users</strong></li>';
				      print '<li><a href="'.$globalURL.'/search/json?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">JSON</a></li>';
				      print '<li><a href="'.$globalURL.'/search/xml?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">XML</a></li>';
				      print '<li><a href="'.$globalURL.'/search/yaml?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">YAML</a></li>';
				      print '<li><a href="'.$globalURL.'/search/php?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">PHP (serialized array)</a></li>';
				      print '<li><hr /></li>';
				      print '<li><span>For Geo/Map Users</span></li>';
				      print '<li><a href="'.$globalURL.'/search/kml?'.htmlentities($_SERVER['QUERY_STRING']).'">KML</a></li>';
				      print '<li><a href="'.$globalURL.'/search/geojson?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GeoJSON</a></li>';
				      print '<li><a href="'.$globalURL.'/search/georss?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GeoRSS</a></li>';
				      print '<li><a href="'.$globalURL.'/search/gpx?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">GPX</a></li>';
				      print '<li><a href="'.$globalURL.'/search/wkt?'.htmlentities($_SERVER['QUERY_STRING']).'&download=true" target="_blank">WKT</a></li>';
				      print '<li><hr /></li>';
				      print '<li><a href="'.$globalURL.'/about/export" target="_blank" class="export-info">Download Info/Licence&raquo;</a></li>';
				    print '</ul>';
				print '</li>';
                //remove 3D=true parameter
                $no3D = str_replace("&3D=true", "", $_SERVER['QUERY_STRING']);
                $kmlURL = str_replace("http://", "kml://", $globalURL);
                if (!isset($_GET['3D'])){
                    print '<li><a href="'.$globalURL.'/search?'.$no3D.'" class="active"><i class="fa fa-table"></i> Table</a></li>';
                } else {
                    print '<li><span class="notablet"><a href="'.$globalURL.'/search?'.$no3D.'"><i class="fa fa-table"></i> Table</a></span></li>';
                }
                if (isset($_GET['3D'])){
                    print '<li><a href="'.$globalURL.'/search?'.$no3D.'&3D=true" class="active"><i class="fa fa-globe"></i> 3D Map</a></li>';
                } else {
                    print '<li ><a href="'.$globalURL.'/search?'.$no3D.'&3D=true" class="notablet nomobile"><i class="fa fa-globe"></i> 3D Map</a><a href="'.$kmlURL.'/search/kml?'.htmlentities($_SERVER['QUERY_STRING']).'" class="tablet mobile"><i class="fa fa-globe"></i> 3D Map</a></li>';
                }
                //checks to see if the Bit.ly API settings are set
                if ($globalBitlyAccessToken != "")
                {
                    print '<li class="short-url">';
                        $bitly = $Spotter->getBitlyURL(urlencode('http://'.$_SERVER[HTTP_HOST].''.$_SERVER[REQUEST_URI]));
                        print 'Short URL: <input type="text" name="short_url" value="'.$bitly.'" readonly="readonly" />';
                    print '</li>';
                }
				print '</ul>';
		print '</div>';
	  
	  if (!empty($spotter_array))
	  {	  		
	  	 print '<div class="column">';
    	  	 print '<div class="info">';
    	  	    print '<h1>Search Results for ';
    		  	if (isset($_GET['q']) && $_GET['q'] != ""){ print 'keyword: <span>'.$_GET['q'].'</span> '; }
    		  	if (isset($_GET['aircraft']) && $_GET['aircraft'] != ""){ print 'aircraft: <span>'.$_GET['aircraft'].'</span> '; }
    		  	if (isset($_GET['manufacturer']) && $_GET['manufacturer'] != ""){ print 'manufacturer: <span>'.$_GET['manufacturer'].'</span> '; }
    		  	if (isset($_GET['registration']) && $_GET['registration'] != ""){ print 'registration: <span>'.$_GET['registration'].'</span> '; }
    		  	if (isset($_GET['highlights'])) if ($_GET['highlights'] == "true"){ print 'highlights: <span>'.$_GET['highlights'].'</span> '; }
			if (isset($_GET['airline']) && $_GET['airline'] != ""){ print 'airline: <span>'.$_GET['airline'].'</span> '; }
			if (isset($_GET['airline_country']) && $_GET['airline_country'] != ""){ print 'airline country: <span>'.$_GET['airline_country'].'</span> '; }
			if (isset($_GET['airline_type']) && $_GET['airline_type'] != ""){ print 'airline type: <span>'.$_GET['airline_type'].'</span> '; }
			if (isset($_GET['airport']) && $_GET['airport'] != ""){ print 'airport: <span>'.$_GET['airport'].'</span> '; }
			if (isset($_GET['airport_country']) && $_GET['airport_country'] != ""){ print 'airport country: <span>'.$_GET['airport_country'].'</span> '; }
			if (isset($_GET['callsign']) && $_GET['callsign'] != ""){ print 'callsign: <span>'.$_GET['callsign'].'</span> '; }
			if (isset($_GET['owner']) && $_GET['owner'] != ""){ print 'owner: <span>'.$_GET['owner'].'</span> '; }
			if (isset($_GET['pilot_id']) && $_GET['pilot_id'] != ""){ print 'pilot id: <span>'.$_GET['pilot_id'].'</span> '; }
			if (isset($_GET['pilot_name']) && $_GET['pilot_name'] != ""){ print 'pilot name: <span>'.$_GET['pilot_name'].'</span> '; }
			if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] != "" && (!isset($_GET['arrival_airport_route']) || $_GET['arrival_airport_route'] == "")){ print 'route out of: <span>'.$_GET['departure_airport_route'].'</span> '; }
			if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] == "" && isset($_GET['arrival_airport_route']) && $_GET['arrival_airport_route'] != ""){ print 'route into: <span>'.$_GET['arrival_airport_route'].'</span> '; }
			if (isset($_GET['departure_airport_route']) && $_GET['departure_airport_route'] != "" && isset($_GET['arrival_airport_route']) && $_GET['arrival_airport_route'] != ""){ print 'route between: <span>'.$_GET['departure_airport_route'].'</span> and <span>'.$_GET['arrival_airport_route'].'</span> '; }
			if (isset($_GET['start_date']) && $_GET['start_date'] != "" && isset($_GET['end_date']) && $_GET['end_date'] == ""){ print 'date starting at: <span>'.$_GET['start_date'].'</span> '; }
			if (isset($_GET['start_date']) && $_GET['start_date'] == "" && isset($_GET['end_date']) && $_GET['end_date'] != ""){ print 'date ending at: <span>'.$_GET['end_date'].'</span> '; }
			if (isset($_GET['start_date']) && $_GET['start_date'] != "" && isset($_GET['end_date']) && $_GET['end_date'] != ""){ print 'date between: <span>'.$_GET['start_date'].'</span> and <span>'.$_GET['end_date'].'</span> '; }
			if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] != "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] == ""){ print 'altitude starting at: <span>'.number_format($_GET['lowest_altitude']).' feet</span> '; }
			if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] == "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] != ""){ print 'altitude ending at: <span>'.number_format($_GET['highest_altitude']).' feet</span> '; }
			if (isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] != "" && isset($_GET['highest_altitude']) && $_GET['highest_altitude'] != ""){ print 'altitude between: <span>'.number_format($_GET['lowest_altitude']).' feet</span> and <span>'.number_format($_GET['highest_altitude']).' feet</span> '; }
			if (isset($_GET['number_results']) && $_GET['number_results'] != ""){ print 'limit per page: <span>'.$_GET['number_results'].'</span> '; }
    		    print '</h1>';
    		  print '</div>';
    	  
          
        //  if ($_GET['3D'] == "true")
          if (isset($_GET['3D']))
          {
            ?>  
              <script type="text/javascript" src="https://www.google.com/jsapi"> </script>
              <script type="text/javascript">
                  var ge;
                  google.load("earth", "1", {"other_params":"sensor=false"});

                  function init() {
                     google.earth.createInstance('map3d', initCB, failureCB);
                  }

                  function initCB(instance) {
                     ge = instance;
                     ge.getWindow().setVisibility(true);

                     //set default coordinates
                      var lookAt = ge.createLookAt('');
                      lookAt.setLatitude(44.413333);
                      lookAt.setLongitude(-79.68);
                      lookAt.setRange(400000.0);
                      ge.getView().setAbstractView(lookAt);

                      //show navigation control
                      ge.getNavigationControl().setVisibility(ge.VISIBILITY_SHOW);

                      //show bottom status bar
                      ge.getOptions().setStatusBarVisibility(true);
                      
                      //enable the atmosphere
                      ge.getOptions().setAtmosphereVisibility(true);
                      

                     //load the kml file
                     var href = '<?php print $globalURL; ?>/search/kml?<?php print $_SERVER['QUERY_STRING']; ?>';
                     google.earth.fetchKml(ge, href, function(kmlObject) {
                           if (kmlObject)
                              ge.getFeatures().appendChild(kmlObject);
                         
                           if (kmlObject.getAbstractView() !== null)
                              ge.getView().setAbstractView(kmlObject.getAbstractView());
                     });
                  }

                  function failureCB(errorCode) {
                  }

                  google.setOnLoadCallback(init);
               </script>
              <div id="map3d"></div>
            <?php
          } else {
    	  	include('table-output.php'); 
    			
    			$_SERVER['QUERY_STRING'] = preg_replace('/&?limit=[^&]*/', '', $_SERVER['QUERY_STRING']);
    			print '<div class="pagination">';
        	if ($limit_previous_1 >= 0)
        	{
        	print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_previous_1.','.$limit_previous_2.'">&laquo;Previous Page</a>';
        	}
        	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
        	{
        		print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_end.','.$limit_next.'">Next Page&raquo;</a>';
        	}
            print '</div>';
          }
    
    print '</div>';
			
			
	  } else {
	      
	      print '<div class="column">';
    	  	 print '<div class="info">';
    	  	    print '<h1>Search</h1>';
    	  	print '</div>';
    	  	print '<p>Sorry, your search did not produce any results. :(</p>'; 
    	  print '</div>';
    	  	 
	  }
  } else {
    print '<div class="info column">';
     	print '<h1>Search</h1>';
     print '</div>';
  }
  ?>


<div class="column">
  <form action="<?php print $globalURL; ?>/search" method="get" role="form" class="form-horizontal">
    <fieldset>
    	<div class="form-group">
	    	<label>Keyword</label> 
		    <input type="text" id="q" name="q" value="<?php if (isset($_GET['q'])) print $_GET['q']; ?>" size="10" placeholder="Keywords" />
		  </div>
    </fieldset>
    <div class="advanced-form">
        <fieldset>
        	<legend>Aircraft</legend>
        	<div class="form-group">
    	    	<label>Manufacturer</label> 
    		    <select name="manufacturer" id="manufacturer" class="selectpicker" data-live-search="true">
    		      <option></option>
    		    </select>
    		  </div>
		  <script type="text/javascript">getSelect('manufacturer','<?php if(isset($_GET['manufacturer'])) print $_GET['manufacturer']; ?>')</script>
		<div class="form-group">
    	    	<label>Type</label> 
    		    <select name="aircraft" id="aircrafttypes" class="selectpicker" data-live-search="true">
    		      <option></option>
    		    </select>
    		  </div>
		  <script type="text/javascript">getSelect('aircrafttypes','<?php if(isset($_GET['aircraft_icao'])) print $_GET['aircraft_icao']; ?>');</script>
    		   <div class="form-group">
    		  	<label>Registration</label> 
    		  	<input type="text" name="registration" value="<?php if (isset($_GET['registration'])) print $_GET['registration']; ?>" size="8" />
    		    </div>
    		<?php
    		    if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
    		?>
    		   <div class="form-group">
    		  	<label>Pilot id</label> 
    		  	<input type="text" name="pilot_id" value="<?php if (isset($_GET['pilot_id'])) print $_GET['pilot_id']; ?>" size="15" />
    		    </div>
    		   <div class="form-group">
    		  	<label>Pilot name</label> 
    		  	<input type="text" name="pilot_name" value="<?php if (isset($_GET['pilot_name'])) print $_GET['pilot_name']; ?>" size="15" />
    		    </div>
		<?php
		    } else {
		?>
    		   <div class="form-group">
    		  	<label>Owner name</label> 
    		  	<input type="text" name="owner" value="<?php if (isset($_GET['owner'])) print $_GET['owner']; ?>" size="15" />
    		    </div>
		<?php
		    }
		?>

    		    <div class="form-group checkbox">
    				<div><input type="checkbox" name="highlights" value="true" id="highlights" <?php if (isset($_GET['highlights'])) if ($_GET['highlights'] == "true"){ print 'checked="checked"'; } ?>> <label for="highlights">Include only aircrafts with special highlights (unique liveries, destinations etc.)</label></div>
    		    </div>
        </fieldset>
        <fieldset>
        	<legend>Airline</legend>
    		  <div class="form-group">
    		  	<label>Name</label> 
    		    <select name="airline" id="airlinenames" class="selectpicker" data-live-search="true">
    		      <option></option>
    		    </select>
    		  </div>
		  <script type="text/javascript">getSelect('airlinenames','<?php if(isset($_GET['airline'])) print $_GET['airline']; ?>');</script>
    		  <div class="form-group">
    		  	<label>Country</label> 
    		    <select name="airline_country" id="airlinecountries" class="selectpicker" data-live-search="true">
    		      <option></option>
    		    </select>
    		  </div>
		  <script type="text/javascript">getSelect('airlinecountries','<?php if(isset($_GET['airline_country'])) print $_GET['airline_country']; ?>');</script>
    		  <div class="form-group">
    		  	<label>Callsign</label> 
    		  	<input type="text" name="callsign" value="<?php if (isset($_GET['callsign'])) print $_GET['callsign']; ?>" size="8" />
    		</div>
    		<div class="form-group radio">
    			<div><input type="radio" name="airline_type" value="all" id="airline_type_all" <?php if (!isset($_GET['airline_type']) || $_GET['airline_type'] == "all"){ print 'checked="checked"'; } ?>> <label for="airline_type_all">All airlines types</label></div>
    			<div><input type="radio" name="airline_type" value="passenger" id="airline_type_passenger" <?php if (isset($_GET['airline_type'])) if ($_GET['airline_type'] == "passenger"){ print 'checked="checked"'; } ?>> <label for="airline_type_passenger">Only Passenger airlines</label></div>
    			<div><input type="radio" name="airline_type" value="cargo" id="airline_type_cargo" <?php if (isset($_GET['airline_type'])) if ( $_GET['airline_type'] == "cargo"){ print 'checked="checked"'; } ?>> <label for="airline_type_cargo">Only Cargo airlines</label></div>
    			<div><input type="radio" name="airline_type" value="military" id="airline_type_military" <?php if (isset($_GET['airline_type'])) if ( $_GET['airline_type'] == "military"){ print 'checked="checked"'; } ?>> <label for="airline_type_military">Only Military airlines</label></div>
    		</div>
        </fieldset>
        <fieldset>
        	<legend>Airport</legend>
    		  <div class="form-group">
    		  	<label>Name</label> 
    		    <select name="airport" id="airportnames" class="selectpicker" data-live-search="true">
    		      <option></option>
    		     </select>
    		  </div>
		  <script type="text/javascript">getSelect('airportnames','<?php if(isset($_GET['airport_icao'])) print $_GET['airport_icao']; ?>');</script>
    		  <div class="form-group">
    		  	<label>Country</label> 
    		    <select name="airport_country" id="airportcountries" class="selectpicker" data-live-search="true">
    		      <option></option>
    		    </select>
    		  </div>
		  <script type="text/javascript">getSelect('airportcountries','<?php if(isset($_GET['airport_country'])) print $_GET['airport_country']; ?>');</script>
        </fieldset>
        
         <fieldset>
        	<legend>Route</legend>
    		  <div class="form-group">
    		  	<label>Departure Airport</label> 
    		    <select name="departure_airport_route" id="departureairportnames" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      </select>
    		  </div>
		  <script type="text/javascript">getSelect('departureairportnames','<?php if(isset($_GET['departure_airport_route'])) print $_GET['departure_airport_route']; ?>');</script>
    		  <div class="form-group">
    		  	<label>Arrival Airport</label> 
    		    <select name="arrival_airport_route" id="arrivalairportnames" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      </select>
    		  </div>
		  <script type="text/javascript">getSelect('arrivalairportnames','<?php if(isset($_GET['arrival_airport_route'])) print $_GET['arrival_airport_route']; ?>');</script>
        </fieldset>
    
    	<fieldset>
        	<legend>Date</legend>
			<div class="form-group">
                            <label>Start Date</label>
                            <div class='input-group date' id='datetimepicker1'>
                                <input type='text' name="start_date" class="form-control" value="<?php if (isset($_GET['start_date'])) print $_GET['start_date']; ?>" placeholder="Start Date/Time" />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <div class='input-group date' id='datetimepicker2'>
                                <input type='text' name="end_date" class="form-control" value="<?php if (isset($_GET['end_date'])) print $_GET['end_date']; ?>" placeholder="End Date/Time" />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                        </div>
                        <script type="text/javascript">
                            $(function () {
                                $('#datetimepicker1').datetimepicker({
                            	    format: 'YYYY-MM-DD'
                                });
                                $('#datetimepicker2').datetimepicker({
                            	    format: 'YYYY-MM-DD',
                                    useCurrent: false //Important! See issue #1075
                                });
                                $("#datetimepicker1").on("dp.change", function (e) {
                                    $('#datetimepicker2').data("DateTimePicker").minDate(e.date);
                                });
                                $("#datetimepicker2").on("dp.change", function (e) {
                                    $('#datetimepicker1').data("DateTimePicker").maxDate(e.date);
                                });
                            });
                        </script>
    		</fieldset>
		</div>
		
		<fieldset>
        	<legend>Altitude</legend>
    			<div class="form-group">
    				<label>Lowest Altitude</label> 
    				<select name="lowest_altitude" class="selectpicker" data-live-search="true">
	    		      <option></option>
	    		      <?php
	    		      $altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
	    		      foreach($altitude_array as $altitude)
	    		      {
	    		        if(isset($_GET['lowest_altitude']) && $_GET['lowest_altitude'] == $altitude)
	    		        {
	    		          print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
	    		        } else {
	    		          print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
	    		        }
	    		      }
	    		      ?></select>
    			</div>
    			<div class="form-group">
    				<label>Highest Altitude</label> 
    				<select name="highest_altitude" class="selectpicker" data-live-search="true">
	    		      <option></option>
	    		      <?php
	    		      $altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
	    		      foreach($altitude_array as $altitude)
	    		      {
	    		        if(isset($_GET['highest_altitude']) && $_GET['highest_altitude'] == $altitude)
	    		        {
	    		          print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
	    		        } else {
	    		          print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
	    		        }
	    		      }
	    		      ?></select>
    			</div>
    		</fieldset>
		
		 <fieldset>
        	<legend>Limit per Page</legend>
    		  <div class="form-group">
    		  	<label>Number of Results</label> 
    		    <select name="number_results">
    		    <?php
    		      $number_results_array = Array(25, 50, 100, 150, 200, 250, 300, 400, 500,  600, 700, 800, 900, 1000);
    		      foreach($number_results_array as $number)
    		      {
    		        if(isset($_GET['number_results']) && $_GET['number_results'] == $number)
    		        {
    		          print '<option value="'.$number.'" selected="selected">'.$number.'</option>';
    		        } else {
    		          print '<option value="'.$number.'">'.$number.'</option>';
    		        }
    		      }
    		      ?>
    		      </select>
    		  </div>
        </fieldset>
    	
        
		<fieldset>
			<div class="form-group">
				<label>Search in archive</label>
				<input type="checkbox" name="archive" value="1" />
			</div>
		</fieldset>
		<fieldset>
			<div>
				<input type="submit" value="Search" />
			</div>
		</fieldset>
	 </form>
</div>

<?php
require_once('footer.php');
?>