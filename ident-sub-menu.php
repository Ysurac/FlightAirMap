<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/ident/<?php print $ident; ?>" <?php if (strtolower($current_page) == "ident-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "ident-statistics-aircraft" || strtolower($current_page) == "ident-statistics-registration" || strtolower($current_page) == "ident-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/aircraft/<?php print $ident; ?>"><?php echo _("Aircraft Type"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/ident/statistics/registration/<?php print $ident; ?>"><?php echo _("Registration"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/ident/statistics/manufacturer/<?php print $ident; ?>"><?php echo _("Manufacturer"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "ident-statistics-departure-airport" || strtolower($current_page) == "ident-statistics-departure-airport-country" || strtolower($current_page) == "ident-statistics-arrival-airport" || strtolower($current_page) == "ident-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/departure-airport/<?php print $ident; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/departure-airport-country/<?php print $ident; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/ident/statistics/arrival-airport/<?php print $ident; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/ident/statistics/arrival-airport-country/<?php print $ident; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/ident/statistics/route/<?php print $ident; ?>" <?php if (strtolower($current_page) == "ident-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/ident/statistics/time/<?php print $ident; ?>" <?php if (strtolower($current_page) == "ident-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>