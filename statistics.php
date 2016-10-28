<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$beginpage = microtime(true);
$Stats = new Stats();
$title = _("Statistics");

$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == 'all') {
	unset($_COOKIE['stats_airline_icao']);
	setcookie('stats_airline_icao', '', time()-3600);
	$airline_icao = '';
} elseif ($airline_icao == '' && isset($_COOKIE['stats_airline_icao'])) {
	$airline_icao = $_COOKIE['stats_airline_icao'];
} elseif ($airline_icao == '' && isset($globalFilter)) {
	if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
setcookie('stats_airline_icao',$airline_icao);
require_once('header.php');

?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<!--<script type="text/javascript" src="https://d3js.org/d3.v4.min.js"></script>-->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>
<script type="text/javascript" src="js/radarChart.js"></script>
<script type="text/javascript" src="js/raphael-2.1.4.min.js"></script>
<script type="text/javascript" src="js/justgage.js"></script>
<div class="column">
    <div class="info">
            <h1><?php echo _("Statistics"); ?></h1>
    <?php 
	$last_update = $Stats->getLastStatsUpdate();
	//if (isset($last_update[0]['value'])) print '<!-- Last update : '.$last_update[0]['value'].' -->';
	if (isset($last_update[0]['value'])) {
		date_default_timezone_set('UTC');
		$lastupdate = strtotime($last_update[0]['value']);
		if (isset($globalTimezone) && $globalTimezone != '') date_default_timezone_set($globalTimezone);
		print '<i>Last update: '.date('Y-m-d G:i:s',$lastupdate).'</i>';
	}
    ?>
    </div>
    <?php    
	// print_r($Stats->getAllAirlineNames()); 
    ?>
    <?php include('statistics-sub-menu.php'); ?>
    <div class="row global-stats">
        <div class="col-md-2"><span class="type"><?php echo _("Flights"); ?></span><span><?php print number_format($Stats->countOverallFlights($airline_icao)); ?></span></div> 
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <div class="col-md-2"><span class="type"><?php echo _("Arrivals seen"); ?></span><span><?php print number_format($Stats->countOverallArrival($airline_icao)); ?></span></div> 
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<?php
	    if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
	?>
    	    <div class="col-md-2"><span class="type"><?php echo _("Pilots"); ?></span><span><?php print number_format($Stats->countOverallPilots($airline_icao)); ?></span></div> 
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <?php
    	    } else {
    	?>
    	    <div class="col-md-2"><span class="type"><?php echo _("Owners"); ?></span><span><?php print number_format($Stats->countOverallOwners($airline_icao)); ?></span></div> 
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
    	<?php
    	    }
    	?>
        <div class="col-md-2"><span class="type"><?php echo _("Aircrafts"); ?></span><span><?php print number_format($Stats->countOverallAircrafts($airline_icao)); ?></span></div> 
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <?php
    		if ($airline_icao == '') {
    	?>
        <div class="col-md-2"><span class="type"><?php echo _("Airlines"); ?></span><span><?php print number_format($Stats->countOverallAirlines()); ?></span></div>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<?php
		}
	?>
	<?php
		if (!(isset($globalIVAO) && $globalIVAO) && !(isset($globalVATSIM) && $globalVATSIM) && !(isset($globalphpVMS) && $globalphpVMS)) {
	?>
        <div class="col-md-2"><span class="type"><?php echo _("Military"); ?></span><span><?php print number_format($Stats->countOverallMilitaryFlights()); ?></span></div> 
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<?php
		}
	?>
    </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
    <div class="specific-stats">
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Aircraft Type"); ?></h2>
                 <?php
                  $aircraft_array = $Stats->countAllAircraftTypes(true,$airline_icao);
		    if (count($aircraft_array) == 0) print _("No data available");
		    else {

                    print '<div id="chart1" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart1);
                      function drawChart1() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Aircraft").'", "'._("# of times").'"], ';
                            $aircraft_data = '';
                          foreach($aircraft_array as $aircraft_item)
                                    {
                                            $aircraft_data .= '[ "'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')",'.$aircraft_item['aircraft_icao_count'].'],';
                                    }
                                    $aircraft_data = substr($aircraft_data, 0, -1);
                                    print $aircraft_data;
                        print ']);

                        var options = {
                            chartArea: {"width": "80%", "height": "60%"},
                            height:300,
                             is3D: true
                        };

                        var chart = new google.visualization.PieChart(document.getElementById("chart1"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart1();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/aircraft" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
//    echo $airline_icao;
    if ($airline_icao == '' || $airline_icao == 'all') {
?>
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Airline"); ?></h2>
                 <?php
                  $airline_array = $Stats->countAllAirlines();
		    if (count($airline_array) == 0) print _("No data available");
		    else {

                  print '<div id="chart2" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart2);
                      function drawChart2() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Airline").'", "'._("# of times").'"], ';
                            $airline_data = '';
                          foreach($airline_array as $airline_item)
                                    {
                                            $airline_data .= '[ "'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')",'.$airline_item['airline_count'].'],';
                                    }
                                    $airline_data = substr($airline_data, 0, -1);
                                    print $airline_data;
                        print ']);

                        var options = {
                            chartArea: {"width": "80%", "height": "60%"},
                            height:300,
                             is3D: true
                        };

                        var chart = new google.visualization.PieChart(document.getElementById("chart2"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart2();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/airline" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
<?php
    }
?>
        <div class="row column">

	    <?php
                 $flightover_array = $Stats->countAllFlightOverCountries($airline_icao);
		if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
		    if (empty($flightover_array)) {
	    ?>
            <div class="col-md-12">
            <?php
        	    } else {
            ?>
            <div class="col-md-6">
            <?php
            	    }
            ?>
                <h2><?php echo _("Top 10 Most Common Pilots"); ?></h2>
                 <?php
                  $pilot_array = $Stats->countAllPilots(true,$airline_icao);
		    if (count($pilot_array) == 0) print _("No data available");
		    else {

                  print '<div id="chart7" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart7);
                      function drawChart7() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Pilots").'", "'._("# of times").'"], ';
                            $pilot_data = '';
                          foreach($pilot_array as $pilot_item)
                                    {
                                            $pilot_data .= '[ "'.$pilot_item['pilot_name'].' ('.$pilot_item['pilot_id'].')",'.$pilot_item['pilot_count'].'],';
                                    }
                                    $pilot_data = substr($pilot_data, 0, -1);
                                    print $pilot_data;
                        print ']);

                        var options = {
                            chartArea: {"width": "80%", "height": "60%"},
                            height:300,
                             is3D: true
                        };

                        var chart = new google.visualization.PieChart(document.getElementById("chart7"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart7();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/pilot" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <?php
    	    } else {
    	?>
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Owners"); ?></h2>
                 <?php
                  $owner_array = $Stats->countAllOwners(true,$airline_icao);
		    if (count($owner_array) == 0) print _("No data available");
		    else {

                  print '<div id="chart7" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart7);
                      function drawChart7() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Owner").'", "'._("# of times").'"], ';
                            $owner_data = '';
                          foreach($owner_array as $owner_item)
                                    {
                                            $owner_data .= '[ "'.$owner_item['owner_name'].'",'.$owner_item['owner_count'].'],';
                                    }
                                    $owner_data = substr($owner_data, 0, -1);
                                    print $owner_data;
                        print ']);

                        var options = {
                            chartArea: {"width": "80%", "height": "60%"},
                            height:300,
                             is3D: true
                        };

                        var chart = new google.visualization.PieChart(document.getElementById("chart7"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart7();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <?php
    	    }
    	    if (!empty($flightover_array)) {
    	?>
    	
            <div class="col-md-6">
                <h2><?php echo _("Top 20 Most Common Country a Flight was Over"); ?></h2>
                 <?php
                  //$flightover_array = $Stats->countAllFlightOverCountries();
		    if (count($flightover_array) == 0) print _("No data available");
		    else {

                  print '<div id="chart10" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart10);
                      function drawChart10() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Country").'", "'._("# of times").'"], ';
                            $flightover_data = '';
                          foreach($flightover_array as $flightover_item)
                                    {
                                            $flightover_data .= '[ "'.$flightover_item['flight_country'].' ('.$flightover_item['flight_country_iso2'].')",'.$flightover_item['flight_count'].'],';
                                    }
                                    $flightover_data = substr($flightover_data, 0, -1);
                                    print $flightover_data;
                        print ']);

                        var options = {
                            chartArea: {"width": "80%", "height": "60%"},
                            height:300,
                             is3D: true,
	                    colors: ["#8BA9D0","#1a3151"]
                        };

                        //var chart = new google.visualization.PieChart(document.getElementById("chart10"));
            		var chart = new google.visualization.GeoChart(document.getElementById("chart10"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart10();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/country" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
        <?php
            }
        ?>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

    	
        </div>
        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Departure Airports"); ?></h2>
                <?php
                $airport_airport_array = $Stats->countAllDepartureAirports($airline_icao);
		    if (count($airport_airport_array) == 0) print _("No data available");
		    else {

                 print '<div id="chart3" class="chart" width="100%"></div>
                <script>
                google.load("visualization", "1", {packages:["geochart"]});
                google.setOnLoadCallback(drawCharts3);
                $(window).resize(function(){
                    drawCharts3();
                });
                function drawCharts3() {

                var data = google.visualization.arrayToDataTable([ 
                    ["'._("Airport").'", "'._("# of times").'"],';
                    $airport_data = '';
                  foreach($airport_airport_array as $airport_item)
                        {
                            $name = $airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')';
                            $name = str_replace("'", "", $name);
                            $name = str_replace('"', "", $name);
                            $airport_data .= '[ "'.$name.'",'.$airport_item['airport_departure_icao_count'].'],';
                        }
                        $airport_data = substr($airport_data, 0, -1);
                        print $airport_data;
                print ']);

                var options = {
                    legend: {position: "none"},
                    chartArea: {"width": "80%", "height": "60%"},
                    height:300,
                    displayMode: "markers",
                    colors: ["#8BA9D0","#1a3151"]
                };

                var chart = new google.visualization.GeoChart(document.getElementById("chart3"));
                chart.draw(data, options);
              }
                </script>';
                }
              ?>
              <div class="more">
                <a href="<?php print $globalURL; ?>/statistics/airport-departure" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
              </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Top 10 Most Common Arrival Airports"); ?></h2>
                <?php
                $airport_airport_array2 = $Stats->countAllArrivalAirports($airline_icao);
		    if (count($airport_airport_array2) == 0) print _("No data available");
		    else {

                print '<div id="chart4" class="chart" width="100%"></div>
                <script>
                google.load("visualization", "1", {packages:["geochart"]});
                google.setOnLoadCallback(drawCharts4);
                $(window).resize(function(){
                    drawCharts4();
                });
                function drawCharts4() {

                var data = google.visualization.arrayToDataTable([ 
                    ["'._("Airport").'", "'._("# of times").'"],';
                    $airport_data2 = '';
                  foreach($airport_airport_array2 as $airport_item2)
                        {
                            $name2 = $airport_item2['airport_arrival_city'].', '.$airport_item2['airport_arrival_country'].' ('.$airport_item2['airport_arrival_icao'].')';
                            $name2 = str_replace("'", "", $name2);
                            $name2 = str_replace('"', "", $name2);
                            $airport_data2 .= '[ "'.$name2.'",'.$airport_item2['airport_arrival_icao_count'].'],';
                        }
                        $airport_data2 = substr($airport_data2, 0, -1);
                        print $airport_data2;
                print ']);

                var options = {
                    legend: {position: "none"},
                    chartArea: {"width": "80%", "height": "60%"},
                    height:300,
                    displayMode: "markers",
                    colors: ["#8BA9D0","#1a3151"]
                };

                var chart = new google.visualization.GeoChart(document.getElementById("chart4"));
                chart.draw(data, options);
              }
                </script>';
                }
              ?>
              <div class="more">
                <a href="<?php print $globalURL; ?>/statistics/airport-arrival" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
              </div>
            </div>
        </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

        <div class="row column">
            <div class="col-md-6">
                <h2><?php echo _("Busiest Months of the last 12 Months"); ?></h2>
                <?php
                  $year_array = $Stats->countAllMonthsLastYear($airline_icao);
		    if (count($year_array) == 0) print _("No data available");
		    else {
                  print '<div id="chart8" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart8);
                      function drawChart8() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Month").'", "'._("# of Flights").'"], ';
                            $year_data = '';
                          foreach($year_array as $year_item)
                                    {
                                        $year_data .= '[ "'.date('F, Y',strtotime($year_item['year_name'].'-'.$year_item['month_name'].'-01')).'",'.$year_item['date_count'].'],';
                                    }
                                    $year_data = substr($year_data, 0, -1);
                                    print $year_data;
                        print ']);

                        var options = {
                            legend: {position: "none"},
                            chartArea: {"width": "80%", "height": "60%"},
                            vAxis: {title: "'._("# of Flights").'"},
                            hAxis: {showTextEvery: 2},
                            height:300,
                            colors: ["#1a3151"]
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById("chart8"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart8();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/year" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last Month"); ?></h2>
                <?php
                  $month_array = $Stats->countAllDatesLastMonth($airline_icao);
		    if (count($month_array) == 0) print _("No data available");
		    else {
                  print '<div id="chart9" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart9);
                      function drawChart9() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Day").'", "'._("# of Flights").'"], ';
                            $month_data = '';
                          foreach($month_array as $month_item)
                                    {
                                        $month_data .= '[ "'.date('F j, Y',strtotime($month_item['date_name'])).'",'.$month_item['date_count'].'],';
                                    }
                                    $month_data = substr($month_data, 0, -1);
                                    print $month_data;
                        print ']);

                        var options = {
                            legend: {position: "none"},
                            chartArea: {"width": "80%", "height": "60%"},
                            vAxis: {title: "'._("# of Flights").'"},
                            hAxis: {showTextEvery: 2},
                            height:300,
                            colors: ["#1a3151"]
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById("chart9"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart9();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/month" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Day in the last 7 Days"); ?></h2>
                <?php
                    $date_array = $Stats->countAllDatesLast7Days($airline_icao);
		    if (empty($date_array)) print _("No data available");
		    else {
                  print '<div id="chart5" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart5);
                      function drawChart5() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Date").'", "'._("# of Flights").'"], ';
                            $date_data = '';
                        
                          foreach($date_array as $date_item)
                                    {
                                        $date_data .= '[ "'.date("F j, Y", strtotime($date_item['date_name'])).'",'.$date_item['date_count'].'],';
                                    }
                                    $date_data = substr($date_data, 0, -1);
                                    print $date_data;
                        print ']);

                        var options = {
                            legend: {position: "none"},
                            chartArea: {"width": "80%", "height": "60%"},
                            vAxis: {title: "'._("# of Flights").'"},
                            hAxis: {showTextEvery: 2},
                            height:300,
                            colors: ["#1a3151"]
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById("chart5"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart5();
                            });
                  </script>';
                  }
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/date" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->

            <div class="col-md-6">
                <h2><?php echo _("Busiest Time of the Day"); ?></h2>
                <?php
                  $hour_array = $Stats->countAllHours('hour',$airline_icao);
		    if (empty($hour_array)) print _("No data available");
		    else {

                  print '<div id="chart6" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart6);
                      function drawChart6() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Hour").'", "'._("# of Flights").'"], ';
                            $hour_data = '';
                          foreach($hour_array as $hour_item)
                                    {
                                        $hour_data .= '[ "'.$hour_item['hour_name'].':00",'.$hour_item['hour_count'].'],';
                                    }
                                    $hour_data = substr($hour_data, 0, -1);
                                    print $hour_data;
                        print ']);

                        var options = {
                            legend: {position: "none"},
                            chartArea: {"width": "80%", "height": "60%"},
                            vAxis: {title: "'._("# of Flights").'"},
                            hAxis: {showTextEvery: 2},
                            height:300,
                            colors: ["#1a3151"]
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById("chart6"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart6();
                            });
                  </script>';
                  }
                ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/time" class="btn btn-default btn" role="button"><?php echo _("See full statistic"); ?>&raquo;</a>
                </div>
            </div>
    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        </div>
<?php
    if ($airline_icao == '') {
?>
        <div class="row column">
        	<?php
        	    $polar = $Stats->getStatsSource(date('Y-m-d'),'polar');
        	    if (!empty($polar)) {
            		print '<h2>'._("Coverage pattern").'</h2>';
        		foreach ($polar as $eachpolar) {
        		    unset($polar_data);
	        	    $Spotter = new Spotter();
        		    $data = json_decode($eachpolar['source_data']);
        		    foreach($data as $value => $key) {
        			$direction = $Spotter->parseDirection(($value*22.5));
        			$distance = $key;
        			$unit = 'km';
				if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
					$distance = round($distance*0.539957);
					$unit = 'nm';
				} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
					$distance = round($distance*0.621371);
					$unit = 'mi';
				} elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
					$distance = $distance;
					$unit = 'km';
				}
        			if (!isset($polar_data)) $polar_data = '{axis:"'.$direction[0]['direction_shortname'].'",value:'.$key.'}';
        	    		else $polar_data = $polar_data.',{axis:"'.$direction[0]['direction_shortname'].'",value:'.$key.'}';
        		    }
        	?>
            <div class="col-md-6">
                <h4><?php print $eachpolar['source_name']; ?></h4>
        	<div id="polar-<?php print str_replace(' ','_',strtolower($eachpolar['source_name'])); ?>" class="chart" width="100%"></div>
        	<script>
        	    (function() {
        	    var margin = {top: 100, right: 100, bottom: 100, left: 100},
			width = Math.min(700, window.innerWidth - 10) - margin.left - margin.right,
			height = Math.min(width, window.innerHeight - margin.top - margin.bottom - 20);
		    var data = [
				    [
				    <?php print $polar_data; ?>
				    ]
				];
		    var color = d3.scale.ordinal().range(["#EDC951","#CC333F","#00A0B0"]);
		    //var color = d3.scaleOrdinal().range(["#EDC951","#CC333F","#00A0B0"]);
		
		    var radarChartOptions = {
		      w: width,
		      h: height,
		      margin: margin,
		      maxValue: 0.5,
		      levels: 5,
		      roundStrokes: true,
		      color: color,
		      unit: '<?php echo $unit; ?>'
		    };
		    RadarChart("#polar-<?php print str_replace(' ','_',strtolower($eachpolar['source_name'])); ?>", data, radarChartOptions);
		    })();
		</script>
            </div>
            <?php
        	    }
        	}
            ?>
        </div>
        <div class="row column">
            <div class="col-md-6">
        	<?php
        	    $msg = $Stats->getStatsSource(date('Y-m-d'),'msg');
        	    if (!empty($msg)) {
            		print '<h2>'._("Messages received").'</h2>';
        		foreach ($msg as $eachmsg) {
        		    //$eachmsg = $msg[0];
        		    $data = $eachmsg['source_data'];
        		    if ($data > 500) $max = (round(($data+100)/100))*100;
        		    else $max = 500;
        	?>
        	<div id="msg-<?php print str_replace(' ','_',strtolower($eachmsg['source_name'])); ?>" class="col-md-4"></div>
        	<script>
		      var g = new JustGage({
			    id: "msg-<?php print str_replace(' ','_',strtolower($eachmsg['source_name'])); ?>",
			    value: <?php echo $data; ?>,
			    min: 0,
			    max: <?php print $max; ?>,
			    valueMinFontSize: 10,
			    height: 120,
			    width: 220,
			    symbol: ' msg/s',
			    title: "<?php print $eachmsg['source_name']; ?>"
			  });
		</script>
            <?php
        	   }
        	}
            ?>
            </div>
        </div>
        <div class="row column">

            <?php
		$hist = $Stats->getStatsSource(date('Y-m-d'),'hist');
		foreach ($hist as $hists) {
			$hist_data = '';
			$source = $hists['source_name'];
			$hist_array = json_decode($hists['source_data']);
			foreach($hist_array as $distance => $nb)
			{
				$unit = 'km';
				if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
					$distance = round($distance*0.539957);
					$unit = 'nm';
				} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
					$distance = round($distance*0.621371);
					$unit = 'mi';
				} elseif ((!isset($_COOKIE['unitdistance']) && ((isset($globalUnitDistance) && $globalUnitDistance == 'km') || !isset($globalUnitDistance))) || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'km')) {
					$distance = $distance;
					$unit = 'km';
				}
				$hist_data .= '[ "'.$distance.'",'.$nb.'],';
			}
			$hist_data = substr($hist_data, 0, -1);
            ?>
            <div class="col-md-6">
                <h2><?php echo sprintf(_("Flights Distance for %s"),$source); ?></h2>
                <?php
                  print '<div id="charthist-'.str_replace(' ','_',strtolower($source)).'" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawCharthist_'.str_replace(' ','_',strtolower($source)).');
                      function drawCharthist_'.str_replace(' ','_',strtolower($source)).'() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Distance").'", "'._("# of Flights").'"], ';
                            print $hist_data;
                        print ']);

                        var options = {
                            legend: {position: "none"},
                            chartArea: {"width": "80%", "height": "60%"},
                            vAxis: {title: "'._("# of Flights").'"},
                            hAxis: {showTextEvery: 2,title: "'._("Distance").' ('.$unit.')"},
                            height:300,
                            colors: ["#1a3151"]
                        };

                        var chart = new google.visualization.AreaChart(document.getElementById("charthist-'.str_replace(' ','_',strtolower($source)).'"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawCharthist_'.str_replace(' ','_',strtolower($source)).'();
                            });
                  </script>';
        	?>
    	    </div>
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        	<?php
                  }
                ?>
        </div>
<?php
    }
?>
    </div>
</div>  

<?php
require_once('footer.php');
?>