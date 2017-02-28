<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/pilot/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "pilot-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "pilot-statistics-aircraft" || strtolower($current_page) == "pilot-statistics-registration" || strtolower($current_page) == "pilot-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/pilot/statistics/aircraft/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Aircraft Type"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/pilot/statistics/registration/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Registration"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/pilot/statistics/manufacturer/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Manufacturer"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "pilot-statistics-departure-airport" || strtolower($current_page) == "pilot-statistics-departure-airport-country" || strtolower($current_page) == "pilot-statistics-arrival-airport" || strtolower($current_page) == "pilot-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/pilot/statistics/departure-airport/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/pilot/statistics/departure-airport-country/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/pilot/statistics/arrival-airport/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/pilot/statistics/arrival-airport-country/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/pilot/statistics/route/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "pilot-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/pilot/statistics/time/<?php print $pilot; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "pilot-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>