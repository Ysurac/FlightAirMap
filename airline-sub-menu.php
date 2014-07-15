<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Statistics <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/airline/<?php print $_GET['airline']; ?>" <?php if (strtolower($current_page) == "airline-overview"){ print 'class="active"'; } ?>>Overview</a></li>
		<li><a href="<?php print $globalURL; ?>/airline/detailed/<?php print $_GET['airline']; ?>" <?php if (strtolower($current_page) == "airline-detailed"){ print 'class="active"'; } ?>>Detailed</a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "airline-statistics-aircraft" || strtolower($current_page) == "airline-statistics-registration" || strtolower($current_page) == "airline-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Aircraft <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/airline/statistics/aircraft/<?php print $_GET['airline']; ?>">Aircraft Type</a></li>
					<li><a href="<?php print $globalURL; ?>/airline/statistics/registration/<?php print $_GET['airline']; ?>">Registration</a></li>
					<li><a href="<?php print $globalURL; ?>/airline/statistics/manufacturer/<?php print $_GET['airline']; ?>">Manufacturer</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "airline-statistics-departure-airport" || strtolower($current_page) == "airline-statistics-departure-airport-country" || strtolower($current_page) == "airline-statistics-arrival-airport" || strtolower($current_page) == "airline-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airport <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/airline/statistics/departure-airport/<?php print $_GET['airline']; ?>">Departure Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/airline/statistics/departure-airport-country/<?php print $_GET['airline']; ?>">Departure Airport by Country</a></li>
			  <li><a href="<?php print $globalURL; ?>/airline/statistics/arrival-airport/<?php print $_GET['airline']; ?>">Arrival Airport</a></li>
			  <li><a href="<?php print $globalURL; ?>/airline/statistics/arrival-airport-country/<?php print $_GET['airline']; ?>">Arrival Airport by Country</a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/airline/statistics/route/<?php print $_GET['airline']; ?>" <?php if (strtolower($current_page) == "airline-statistics-route"){ print 'class="active"'; } ?>>Route</a></li>
		<li><a href="<?php print $globalURL; ?>/airline/statistics/time/<?php print $_GET['airline']; ?>" <?php if (strtolower($current_page) == "airline-statistics-time"){ print 'class="active"'; } ?>>Time</a></li>
	</ul>
</div>