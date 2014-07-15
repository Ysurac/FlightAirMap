<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Statistics <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/ident/<?php print $_GET['ident']; ?>" <?php if (strtolower($current_page) == "ident-overview"){ print 'class="active"'; } ?>>Overview</a></li>
		<li><a href="<?php print $globalURL; ?>/ident/detailed/<?php print $_GET['ident']; ?>" <?php if (strtolower($current_page) == "ident-detailed"){ print 'class="active"'; } ?>>Detailed</a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "ident-statistics-aircraft" || strtolower($current_page) == "ident-statistics-registration" || strtolower($current_page) == "ident-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Aircraft <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/aircraft/<?php print $_GET['ident']; ?>">Aircraft Type</a></li>
					<li><a href="<?php print $globalURL; ?>/ident/statistics/registration/<?php print $_GET['ident']; ?>">Registration</a></li>
					<li><a href="<?php print $globalURL; ?>/ident/statistics/manufacturer/<?php print $_GET['ident']; ?>">Manufacturer</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "ident-statistics-departure-airport" || strtolower($current_page) == "ident-statistics-departure-airport-country" || strtolower($current_page) == "ident-statistics-arrival-airport" || strtolower($current_page) == "ident-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airport <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/departure-airport/<?php print $_GET['ident']; ?>">Departure Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/ident/statistics/departure-airport-country/<?php print $_GET['ident']; ?>">Departure Airport by Country</a></li>
			  <li><a href="<?php print $globalURL; ?>/ident/statistics/arrival-airport/<?php print $_GET['ident']; ?>">Arrival Airport</a></li>
			  <li><a href="<?php print $globalURL; ?>/ident/statistics/arrival-airport-country/<?php print $_GET['ident']; ?>">Arrival Airport by Country</a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/ident/statistics/route/<?php print $_GET['ident']; ?>" <?php if (strtolower($current_page) == "ident-statistics-route"){ print 'class="active"'; } ?>>Route</a></li>
		<li><a href="<?php print $globalURL; ?>/ident/statistics/time/<?php print $_GET['ident']; ?>" <?php if (strtolower($current_page) == "ident-statistics-time"){ print 'class="active"'; } ?>>Time</a></li>
	</ul>
</div>