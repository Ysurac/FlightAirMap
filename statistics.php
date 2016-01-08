<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
$Spotter = new Spotter();
$title = "Statistic";
require('header.php');
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<div class="column">
    <div class="info">
            <h1>Statistics</h1>
    </div>

    <?php include('statistics-sub-menu.php'); ?>

    <div class="row global-stats">
        <div class="col-md-2"><span class="type">Flights</span><span><?php print number_format($Spotter->countOverallFlights()); ?></span></div> 
        <div class="col-md-2"><span class="type">Arrivals seen</span><span><?php print number_format($Spotter->countOverallArrival()); ?></span></div> 
	<?php
	    if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
	?>
    	    <div class="col-md-2"><span class="type">Pilots</span><span><?php print number_format($Spotter->countOverallPilots()); ?></span></div> 
        <?php
    	    } else {
    	?>
    	    <div class="col-md-2"><span class="type">Owners</span><span><?php print number_format($Spotter->countOverallOwners()); ?></span></div> 
    	<?php
    	    }
    	?>
        <div class="col-md-2"><span class="type">Aircrafts</span><span><?php print number_format($Spotter->countOverallAircrafts()); ?></span></div> 
        <div class="col-md-2"><span class="type">Airlines</span><span><?php print number_format($Spotter->countOverallAirlines()); ?></span></div>
    </div>

    <div class="specific-stats">
        <div class="row column">
            <div class="col-md-6">
                <h2>Top 10 Most Common Aircraft Type</h2>
                 <?php
                  $aircraft_array = $Spotter->countAllAircraftTypes();

                    print '<div id="chart1" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart1);
                      function drawChart1() {
                        var data = google.visualization.arrayToDataTable([
                            ["Aircraft", "# of Times"], ';
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/aircraft" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Top 10 Most Common Airline</h2>
                 <?php
                  $airline_array = $Spotter->countAllAirlines();

                  print '<div id="chart2" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart2);
                      function drawChart2() {
                        var data = google.visualization.arrayToDataTable([
                            ["Airline", "# of Times"], ';
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/airline" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>
        </div>
        <div class="row column">

	    <?php
		if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
	    ?>
            <div class="col-md-12">
                <h2>Top 10 Most Common Pilots</h2>
                 <?php
                  $pilot_array = $Spotter->countAllPilots();

                  print '<div id="chart7" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart7);
                      function drawChart7() {
                        var data = google.visualization.arrayToDataTable([
                            ["Pilots", "# of Times"], ';
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/pilot" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>
        </div>
        <?php
    	    } else {
    	?>
            <div class="col-md-12">
                <h2>Top 10 Most Common Owners</h2>
                 <?php
                  $owner_array = $Spotter->countAllOwners();

                  print '<div id="chart7" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart7);
                      function drawChart7() {
                        var data = google.visualization.arrayToDataTable([
                            ["Owner", "# of Times"], ';
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/owner" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>
        </div>
        <?php
    	    }
    	?>
        </div>
        <div class="row column">
            <div class="col-md-6">
                <h2>Top 10 Most Common Departure Airports</h2>
                <?php
                $airport_airport_array = $Spotter->countAllDepartureAirports();

                 print '<div id="chart3" class="chart" width="100%"></div>
                <script>
                google.load("visualization", "1", {packages:["geochart"]});
                google.setOnLoadCallback(drawCharts3);
                $(window).resize(function(){
                    drawCharts3();
                });
                function drawCharts3() {

                var data = google.visualization.arrayToDataTable([ 
                    ["Airport", "# of Times"],';
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
              ?>
              <div class="more">
                <a href="<?php print $globalURL; ?>/statistics/airport-departure" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
              </div>
            </div>

            <div class="col-md-6">
                <h2>Top 10 Most Common Arrival Airports</h2>
                <?php
                $airport_airport_array2 = $Spotter->countAllArrivalAirports();

                print '<div id="chart4" class="chart" width="100%"></div>
                <script>
                google.load("visualization", "1", {packages:["geochart"]});
                google.setOnLoadCallback(drawCharts4);
                $(window).resize(function(){
                    drawCharts4();
                });
                function drawCharts4() {

                var data = google.visualization.arrayToDataTable([ 
                    ["Airport", "# of Times"],';
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
              ?>	
              <div class="more">
                <a href="<?php print $globalURL; ?>/statistics/airport-arrival" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
              </div>
            </div>
        </div>

        <div class="row column">
            <div class="col-md-6">
                <h2>Busiest Month in the last Year</h2>
                <?php
                  $year_array = $Spotter->countAllMonthsLastYear();

                  print '<div id="chart8" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart8);
                      function drawChart8() {
                        var data = google.visualization.arrayToDataTable([
                            ["Month", "# of Flights"], ';
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
                            vAxis: {title: "# of Flights"},
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/year" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Busiest Day in the last Month</h2>
                <?php
                  $month_array = $Spotter->countAllDatesLastMonth();
                  print '<div id="chart9" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart9);
                      function drawChart9() {
                        var data = google.visualization.arrayToDataTable([
                            ["Day", "# of Flights"], ';
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
                            vAxis: {title: "# of Flights"},
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/month" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Busiest Day in the last 7 Days</h2>
                <?php
                  $date_array = $Spotter->countAllDatesLast7Days();

                  print '<div id="chart5" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart5);
                      function drawChart5() {
                        var data = google.visualization.arrayToDataTable([
                            ["Date", "# of Flights"], ';
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
                            vAxis: {title: "# of Flights"},
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
                  ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/date" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Busiest Time of the Day</h2>
                <?php
                  $hour_array = $Spotter->countAllHours('hour');

                  print '<div id="chart6" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1", {packages:["corechart"]});
                      google.setOnLoadCallback(drawChart6);
                      function drawChart6() {
                        var data = google.visualization.arrayToDataTable([
                            ["Hour", "# of Flights"], ';
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
                            vAxis: {title: "# of Flights"},
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
                ?>
                <div class="more">
                    <a href="<?php print $globalURL; ?>/statistics/time" class="btn btn-default btn" role="button">See full statistic&raquo;</a>
                </div>
            </div>
        </div>
    </div>
</div>  

<?php
require('footer.php');
?>