<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic";
require('header.php');
?>

<div class="column">
    <div class="info">
            <h1>Barrie Spotter Statistics</h1>
    </div>

    <p>There are an average of 380 flights every day recorded on Barrie Spotter. Below are some of the other key site statistics or select a particular statistic category from the menu below.</p>

    <?php include('statistics-sub-menu.php'); ?>

    <div class="global-stats">
        <div><span class="type">Flights</span><span><?php print number_format(Spotter::countOverallFlights()); ?></span></div> 
        <div><span class="type">Aircrafts</span><span><?php print number_format(Spotter::countOverallAircrafts()); ?></span></div> 
        <div><span class="type">Airlines</span><span><?php print number_format(Spotter::countOverallAirlines()); ?></span></div>
    </div>

    <div class="specific-stats">
        <div class="col-lg-6">
            <h2>Top 10 Most Common Aircraft Type</h2>
             <?php
              $aircraft_array = Spotter::countAllAircraftTypes();

                print '<div id="chart1" class="chart" width="100%"></div>
                <script> 
                    google.load("visualization", "1", {packages:["corechart"]});
                  google.setOnLoadCallback(drawChart1);
                  function drawChart1() {
                    var data = google.visualization.arrayToDataTable([
                        ["Aircraft", "# of Times"], ';
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

        <div class="col-lg-6">
            <h2>Top 10 Most Common Airline</h2>
             <?php
              $airline_array = Spotter::countAllAirlines();

              print '<div id="chart2" class="chart" width="100%"></div>
                <script> 
                    google.load("visualization", "1", {packages:["corechart"]});
                  google.setOnLoadCallback(drawChart2);
                  function drawChart2() {
                    var data = google.visualization.arrayToDataTable([
                        ["Airline", "# of Times"], ';
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

        <div class="col-lg-6">
            <h2>Top 10 Most Common Departure Airports</h2>
            <?php
            $airport_airport_array = Spotter::countAllDepartureAirports();

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

        <div class="col-lg-6">
            <h2>Top 10 Most Common Arrival Airports</h2>
            <?php
            $airport_airport_array2 = Spotter::countAllArrivalAirports();

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

        <div class="col-lg-6">
            <h2>Busiest Day in the last 7 Days</h2>
            <?php
              $date_array = Spotter::countAllDatesLast7Days();

              print '<div id="chart5" class="chart" width="100%"></div>
                <script> 
                    google.load("visualization", "1", {packages:["corechart"]});
                  google.setOnLoadCallback(drawChart5);
                  function drawChart5() {
                    var data = google.visualization.arrayToDataTable([
                        ["Date", "# of Flights"], ';
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

        <div class="col-lg-6">
            <h2>Busiest Time of the Day</h2>
            <?php
              $hour_array = Spotter::countAllHours('hour');

              print '<div id="chart6" class="chart" width="100%"></div>
                <script> 
                    google.load("visualization", "1", {packages:["corechart"]});
                  google.setOnLoadCallback(drawChart6);
                  function drawChart6() {
                    var data = google.visualization.arrayToDataTable([
                        ["Hour", "# of Flights"], ';
                      foreach($hour_array as $hour_item)
                                {
                                    $hour_data .= '[ "'.date("ga", strtotime($hour_item['hour_name'].":00")).'",'.$hour_item['hour_count'].'],';
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

<?php
require('footer.php');
?>