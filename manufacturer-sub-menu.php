<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/manufacturer/<?php print $manufacturer; ?>" <?php if (strtolower($current_page) == "manufacturer-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "manufacturer-statistics-aircraft" || strtolower($current_page) == "manufacturer-statistics-registration"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/aircraft/<?php print $manufacturer; ?>"><?php echo _("Aircraft Type"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/registration/<?php print $manufacturer; ?>"><?php echo _("Registration"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "manufacturer-statistics-airline" || strtolower($current_page) == "manufacturer-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/airline/<?php print $manufacturer; ?>"><?php echo _("Airline"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/airline-country/<?php print $manufacturer; ?>"><?php echo _("Airline by Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "manufacturer-statistics-departure-airport" || strtolower($current_page) == "manufacturer-statistics-departure-airport-country" || strtolower($current_page) == "manufacturer-statistics-arrival-airport" || strtolower($current_page) == "manufacturer-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/departure-airport/<?php print $manufacturer; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/departure-airport-country/<?php print $manufacturer; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/arrival-airport/<?php print $manufacturer; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/manufacturer/statistics/arrival-airport-country/<?php print $manufacturer; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/manufacturer/statistics/route/<?php print $manufacturer; ?>" <?php if (strtolower($current_page) == "manufacturer-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/manufacturer/statistics/time/<?php print $manufacturer; ?>" <?php if (strtolower($current_page) == "manufacturer-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>