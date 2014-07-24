<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Home";
require('header.php');
?>


<div class="column">
    <?php
    print '<div id="slideshow">';
    $spotter_array = Spotter::getLatestSpotterData("0,10");
    
    foreach($spotter_array as $spotter_item)
     {
    	if ($spotter_item['image'] != "")
        {
        	print '<div class="slide">';
        	    print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'">';
            		print '<img src="'.$spotter_item['image'].'" />';
            		print '<div class="details">';
            			print '<span class="nomobile">'.$spotter_item['ident'].' - '.$spotter_item['airline_name'].' | '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].') | '.$spotter_item['registration'].'<br />'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].') <i class="fa fa-arrow-right"></i> '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</span>';
            			print '<span class="mobile">'.$spotter_item['ident'].' | '.$spotter_item['aircraft_type'].' | '.$spotter_item['departure_airport'].' <i class="fa fa-arrow-right"></i> '.$spotter_item['arrival_airport'].'</span>';
            		print '</div>';
        		print '</a>';
        	print '</div>';
        }
    }
    print '</div>';
    ?>
   
    <div class="stats">
        <h2>A few numbers about Barrie Spotter</h2>
        <div class="flights"><span><?php print number_format(Spotter::countOverallFlights()); ?></span><br />Flights</div> 
        <div><span><?php print number_format(Spotter::countOverallAircrafts()); ?></span><br />Aircrafts</div> 
        <div><span><?php print number_format(Spotter::countOverallAirlines()); ?></span><br />Airlines</div>
    </div>
    
    <div class="hours">
    	<h2>Today's Activity</h2>
		<?php
		$hour_array = Spotter::countAllHoursFromToday();
	      print '<div id="chartHour" class="chart" width="100%"></div>
	      	<script> 
	      		google.load("visualization", "1", {packages:["corechart"]});
	          google.setOnLoadCallback(drawChart);
	          function drawChart() {
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
	    
	            var chart = new google.visualization.AreaChart(document.getElementById("chartHour"));
	            chart.draw(data, options);
	          }
	          $(window).resize(function(){
	    			  drawChart();
	    			});
	      </script>';
		?>
    </div>
    
    <div class="clear latest">
    
        <div class="col-sm-7">
        	<h2><i class="fa fa-plane"></i> About Barrie Spotter</h2>
          <p>Barrie Spotter is an open source project documenting <u>most</u> of the aircrafts that have flown near the Barrie area. Browse through the data based on a particular aircraft, airline or airport to search through the database. See extensive statistics such as most common aircraft type, airline, departure &amp; arrival airport and busiest time of the day, or just explore flights near the Barrie area. <a href="<?php print $globalURL; ?>/about">More info&raquo;</a></p>
          
          <p class="quote"><i class="fa fa-quote-left"></i> I was just having a look at barriespotter.com again, and going through it in detail, and I have to say that it is more awesome than I first realized! I've got Barrie Spotter TV open in a browser window now and can’t take my eyes off it because I’m anticipating updates. Very cool. - Rob Jones, <a href="http://sonicgoose.com" target="_blank">sonicgoose.com</a></p>
        </div>
        <div class="col-sm-5">
            <h2><i class="fa fa-twitter-square"></i> Twitter Feed</h2>
            <a class="twitter-timeline" href="https://twitter.com/barriespotter" data-widget-id="476873136202207233" data-chrome="nofooter noheader transparent noborders" data-tweet-limit="3" width="100%">Tweets by @barriespotter</a>
            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
            <div class="pagination"><a href="https://www.twitter.com/barriespotter" target="_blank">View all Tweets @barriespotter&raquo;</a></div>
        </div>
    
    </div>

</div>

<?php
require('footer.php');
?>