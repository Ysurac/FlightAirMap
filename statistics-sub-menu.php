<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
	<div class="stats_airline">
		<form>
			<select name="airline" class="selectpicker" onchange="this.form.submit()">
				<?php
					require_once('require/class.Stats.php');
					$Stats = new Stats();
					if (!isset($filter_name)) $filter_name = '';
					$airlines = $Stats->getAllAirlineNames($filter_name);
					if (isset($airline_icao) && ($airline_icao == '' || $airline_icao == 'all')) {
						print '<option value="all" selected>All</option>';
					} else {
						print '<option value="all">All</option>';
					}
					foreach($airlines as $airline) {
						if (isset($airline_icao) && $airline_icao == $airline['airline_icao']) {
							print '<option value="'.$airline['airline_icao'].'" selected>'.$airline['airline_name'].'</option>';
						} else {
							print '<option value="'.$airline['airline_icao'].'">'.$airline['airline_name'].'</option>';
						}
					}
				?>
			</select>
		</form>
	</div>

<div class="sub-menu sub-menu-container">
	<ul class="nav">
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-aircraft" || strtolower($current_page) == "statistics-registration" || strtolower($current_page) == "statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#" >
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu">
			<li><a href="<?php print $globalURL; ?>/statistics/aircraft"><?php echo _("Aircraft"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/registration"><?php echo _("Registration"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/manufacturer"><?php echo _("Manufacturer"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/country"><?php echo _("Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airline" || strtolower($current_page) == "statistics-airline-country" || strtolower($current_page) == "statistics-callsign"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airline"><?php echo _("Airline"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline-country"><?php echo _("Airline by Country"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/callsign"><?php echo _("Callsign"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airport-departure" || strtolower($current_page) == "statistics-airport-departure-country" || strtolower($current_page) == "statistics-airport-arrival" || strtolower($current_page) == "statistics-airport-arrival-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure-country"><?php echo _("Departure Airport by Country"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival"><?php echo _("Arrival Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival-country"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-route-airport" || strtolower($current_page) == "statistics-route-waypoint"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Route"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/route-airport"><?php echo _("Route by Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/route-waypoint"><?php echo _("Route by Waypoint"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Date &amp; Time"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/date"><?php echo _("Date"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/time"><?php echo _("Time"); ?></a></li>
		    </ul>
		</li>
	</ul>
</div>