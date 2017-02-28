<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/route/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>" <?php if (strtolower($current_page) == "route-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "route-statistics-aircraft" || strtolower($current_page) == "route-statistics-registration" || strtolower($current_page) == "route-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/route/statistics/aircraft/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>"><?php echo _("Aircraft Type"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/route/statistics/registration/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>"><?php echo _("Registration"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/route/statistics/manufacturer/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>"><?php echo _("Manufacturer"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "route-statistics-airline" || strtolower($current_page) == "route-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/route/statistics/airline/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>"><?php echo _("Airline"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/route/statistics/airline-country/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>"><?php echo _("Airline by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/route/statistics/time/<?php print $departure_airport; ?>/<?php print $arrival_airport; ?>" <?php if (strtolower($current_page) == "route-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
        <li><a href="http://flightaware.com/live/findflight/<?php print $spotter_array[0]['departure_airport_icao']; ?>/<?php print $spotter_array[0]['arrival_airport_icao']; ?>/" target="_blank"><?php echo _("Upcoming Schedule"); ?>&raquo;</a></li>
	</ul>
</div>