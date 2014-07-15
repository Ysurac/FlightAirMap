<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Statistics <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/aircraft/<?php print $_GET['aircraft_type']; ?>" <?php if (strtolower($current_page) == "aircraft-overview"){ print 'class="active"'; } ?>>Overview</a></li>
		<li><a href="<?php print $globalURL; ?>/aircraft/detailed/<?php print $_GET['aircraft_type']; ?>" <?php if (strtolower($current_page) == "aircraft-detailed"){ print 'class="active"'; } ?>>Detailed</a></li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/registration/<?php print $_GET['aircraft_type']; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-registration"){ print 'class="active"'; } ?>>Registration</a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "aircraft-statistics-airline" || strtolower($current_page) == "aircraft-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airline <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/airline/<?php print $_GET['aircraft_type']; ?>">Airline</a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/airline-country/<?php print $_GET['aircraft_type']; ?>">Airline by Country</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "aircraft-statistics-departure-airport" || strtolower($current_page) == "aircraft-statistics-departure-airport-country" || strtolower($current_page) == "aircraft-statistics-arrival-airport" || strtolower($current_page) == "aircraft-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airport <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/departure-airport/<?php print $_GET['aircraft_type']; ?>">Departure Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/departure-airport-country/<?php print $_GET['aircraft_type']; ?>">Departure Airport by Country</a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/arrival-airport/<?php print $_GET['aircraft_type']; ?>">Arrival Airport</a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/arrival-airport-country/<?php print $_GET['aircraft_type']; ?>">Arrival Airport by Country</a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/route/<?php print $_GET['aircraft_type']; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-route"){ print 'class="active"'; } ?>>Route</a></li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/time/<?php print $_GET['aircraft_type']; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-time"){ print 'class="active"'; } ?>>Time</a></li>
	</ul>
</div>