<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/aircraft/<?php print $aircraft_type; ?>" <?php if (strtolower($current_page) == "aircraft-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/registration/<?php print $aircraft_type; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-registration"){ print 'class="active"'; } ?>><?php echo _("Registration"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "aircraft-statistics-airline" || strtolower($current_page) == "aircraft-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/airline/<?php print $aircraft_type; ?>"><?php echo _("Airline"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/airline-country/<?php print $aircraft_type; ?>"><?php echo _("Airline by Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "aircraft-statistics-departure-airport" || strtolower($current_page) == "aircraft-statistics-departure-airport-country" || strtolower($current_page) == "aircraft-statistics-arrival-airport" || strtolower($current_page) == "aircraft-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/departure-airport/<?php print $aircraft_type; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/aircraft/statistics/departure-airport-country/<?php print $aircraft_type; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/arrival-airport/<?php print $aircraft_type; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/aircraft/statistics/arrival-airport-country/<?php print $aircraft_type; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/route/<?php print $aircraft_type; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/aircraft/statistics/time/<?php print $aircraft_type; ?>" <?php if (strtolower($current_page) == "aircraft-statistics-time"){ print 'class="active"'; } ?>><?php echo ("Time"); ?></a></li>
	</ul>
</div>