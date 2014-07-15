<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Statistics <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav">
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-aircraft" || strtolower($current_page) == "statistics-registration" || strtolower($current_page) == "statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#" >
		      Aircraft <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/aircraft">Aircraft</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/registration">Registration</a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/manufacturer">Manufacturer</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airline" || strtolower($current_page) == "statistics-airline-country" || strtolower($current_page) == "statistics-callsign"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airline <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airline">Airline</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline-country">Airline by Country</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/callsign">Callsign</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airport-departure" || strtolower($current_page) == "statistics-airport-departure-country" || strtolower($current_page) == "statistics-airport-arrival" || strtolower($current_page) == "statistics-airport-arrival-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airport <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure">Departure Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure-country">Departure Airport by Country</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival">Arrival Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival-country">Arrival Airport by Country</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-route-airport" || strtolower($current_page) == "statistics-route-waypoint"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Route <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/route-airport">Route by Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/route-waypoint">Route by Waypoint</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Date &amp; Time <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/date">Date</a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/time">Time</a></li>
		    </ul>
		</li>
	</ul>
</div>