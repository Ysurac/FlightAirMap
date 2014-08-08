<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Statistics <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/country/<?php print $country; ?>" <?php if (strtolower($current_page) == "country-detailed"){ print 'class="active"'; } ?>>Detailed</a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "country-statistics-aircraft" || strtolower($current_page) == "country-statistics-registration" || strtolower($current_page) == "country-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Aircraft <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/country/statistics/aircraft/<?php print $country; ?>">Aircraft Type</a></li>
					<li><a href="<?php print $globalURL; ?>/country/statistics/registration/<?php print $country; ?>">Registration</a></li>
					<li><a href="<?php print $globalURL; ?>/country/statistics/manufacturer/<?php print $country; ?>">Manufacturer</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "country-statistics-airline" || strtolower($current_page) == "country-statistics-airline-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airline <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/country/statistics/airline/<?php print $country; ?>">Airline</a></li>
			  <li><a href="<?php print $globalURL; ?>/country/statistics/airline-country/<?php print $country; ?>">Airline by Country</a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "country-statistics-departure-airport" || strtolower($current_page) == "country-statistics-departure-airport-country" || strtolower($current_page) == "country-statistics-arrival-airport" || strtolower($current_page) == "country-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      Airport <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/country/statistics/departure-airport/<?php print $country; ?>">Departure Airport</a></li>
		      <li><a href="<?php print $globalURL; ?>/country/statistics/departure-airport-country/<?php print $country; ?>">Departure Airport by Country</a></li>
			  <li><a href="<?php print $globalURL; ?>/country/statistics/arrival-airport/<?php print $country; ?>">Arrival Airport</a></li>
			  <li><a href="<?php print $globalURL; ?>/country/statistics/arrival-airport-country/<?php print $country; ?>">Arrival Airport by Country</a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/country/statistics/route/<?php print $country; ?>" <?php if (strtolower($current_page) == "country-statistics-route"){ print 'class="active"'; } ?>>Route</a></li>
		<li><a href="<?php print $globalURL; ?>/country/statistics/time/<?php print $country; ?>" <?php if (strtolower($current_page) == "country-statistics-time"){ print 'class="active"'; } ?>>Time</a></li>
	</ul>
</div>