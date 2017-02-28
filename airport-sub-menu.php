<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/airport/<?php print $airport; ?>" <?php if (strtolower($current_page) == "airport-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "airport-statistics-aircraft" || strtolower($current_page) == "airport-statistics-registration" || strtolower($current_page) == "airport-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/airport/statistics/aircraft/<?php print $airport; ?>"><?php echo _("Aircraft Type"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/airport/statistics/registration/<?php print $airport; ?>"><?php echo _("Registration"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/airport/statistics/manufacturer/<?php print $airport; ?>"><?php echo _("Manufacturer"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "airport-statistics-airline" || strtolower($current_page) == "airport-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/airport/statistics/airline/<?php print $airport; ?>"><?php echo _("Airline"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/airport/statistics/airline-country/<?php print $airport; ?>"><?php echo _("Airline by Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "airport-statistics-departure-airport" || strtolower($current_page) == "airport-statistics-departure-airport-country" || strtolower($current_page) == "airport-statistics-arrival-airport" || strtolower($current_page) == "airport-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/airport/statistics/departure-airport/<?php print $airport; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/airport/statistics/departure-airport-country/<?php print $airport; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/airport/statistics/arrival-airport/<?php print $airport; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/airport/statistics/arrival-airport-country/<?php print $airport; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/airport/statistics/route/<?php print $airport; ?>" <?php if (strtolower($current_page) == "airport-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/airport/statistics/time/<?php print $airport; ?>" <?php if (strtolower($current_page) == "airport-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>