<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$beginpage = microtime(true);
$Stats = new Stats();
$title = _("Statistics");
require_once('header.php');
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
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

    <?php include('statistics-sub-menu.php'); ?>
    <div class="row global-stats">
        <div class="col-md-2"><span class="type"><?php echo _("Flights"); ?></span><span><?php print number_format($Stats->countOverallFlights()); ?></span></div> 
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <div class="col-md-2"><span class="type"><?php echo _("Arrivals seen"); ?></span><span><?php print number_format($Stats->countOverallArrival()); ?></span></div> 
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
	<?php
	    if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
	?>
    	    <div class="col-md-2"><span class="type"><?php echo _("Pilots"); ?></span><span><?php print number_format($Stats->countOverallPilots()); ?></span></div> 
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <?php
    	    } else {
    	?>
    	    <div class="col-md-2"><span class="type"><?php echo _("Owners"); ?></span><span><?php print number_format($Stats->countOverallOwners()); ?></span></div> 
	    <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
    	<?php
    	    }
    	?>
        <div class="col-md-2"><span class="type"><?php echo _("Aircrafts"); ?></span><span><?php print number_format($Stats->countOverallAircrafts()); ?></span></div> 
        <!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
        <div class="col-md-2"><span class="type"><?php echo _("Airlines"); ?></span><span><?php print number_format($Stats->countOverallAirlines()); ?></span></div>
	<!-- <?php print 'Time elapsed : '.(microtime(true)-$beginpage).'s' ?> -->
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
                  $aircraft_array = $Stats->countAllAircraftTypes();
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
        <div class="row column">

	    <?php
                 $flightover_array = $Stats->countAllFlightOverCountries();

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
                  $pilot_array = $Stats->countAllPilots();
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
                  $owner_array = $Stats->countAllOwners();
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
                $airport_airport_array = $Stats->countAllDepartureAirports();
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
                $airport_airport_array2 = $Stats->countAllArrivalAirports();
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
                  $year_array = $Stats->countAllMonthsLastYear();
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
                  $month_array = $Stats->countAllDatesLastMonth();
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
                    $date_array = $Stats->countAllDatesLast7Days();
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
                  $hour_array = $Stats->countAllHours('hour');
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
    </div>
</div>  

<?php
require_once('footer.php');
?>