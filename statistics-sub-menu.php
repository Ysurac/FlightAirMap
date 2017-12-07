<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
	<div class="stats_airline">
<?php
	if (!isset($type) || $type != 'satellite') {
?>
		<form id="changedate" method="post">
			<input type="month" name="date" onchange="statsdatechange(this);" value="<?php if (isset($year) && $year != '') echo $year.'-'; ?><?php if (isset($month) && $month != '') echo $month; ?>" />
		</form>
<?php
	}
?>
<?php
	if (!isset($type) || $type == 'aircraft') {
?>
		<form id="changeairline" method="post">
			<!--<select name="airline" class="selectpicker" onchange="this.form.submit()">-->
			<select name="airline" class="selectpicker" onchange="statsairlinechange(this)">
				<?php
					if (isset($airline_icao) && ($airline_icao == '' || $airline_icao == 'all')) {
						print '<option value="all" selected>All</option>';
					} else {
						print '<option value="all">All</option>';
					}
					print '<option disabled>──────────</option>';
					require_once('require/class.Stats.php');
					$Spotter = new Spotter();
					$alliances = $Spotter->getAllAllianceNames();
					if (!empty($alliances)) {
						foreach($alliances as $alliance) {
							if (isset($airline_icao) && str_replace('_',' ',str_replace('alliance_','',$airline_icao)) == $alliance['alliance']) {
								print '<option value="alliance_'.str_replace(' ','_',$alliance['alliance']).'" selected>'.$alliance['alliance'].'</option>';
							} else {
								print '<option value="alliance_'.str_replace(' ','_',$alliance['alliance']).'">'.$alliance['alliance'].'</option>';
							}
						}
						print '<option disabled>──────────</option>';
					}
					$Stats = new Stats();
					if (!isset($filter_name)) $filter_name = '';
					$airlines = $Stats->getAllAirlineNames($filter_name);
					foreach($airlines as $airline) {
						if ($airline['airline_icao'] != '') {
							if (isset($airline_icao) && $airline_icao != 'all' && $airline_icao == $airline['airline_icao']) {
								print '<option value="'.$airline['airline_icao'].'" selected>'.$airline['airline_name'].'</option>';
							} else {
								print '<option value="'.$airline['airline_icao'].'">'.$airline['airline_name'].'</option>';
							}
						}
					}
				?>
			</select>
		</form>
<?php
	}
?>
	</div>
<?php 
    if (!isset($year) || (isset($year) && $year == '') && !isset($month) || (isset($month) && $month == '')) {
?>
<div class="sub-menu sub-menu-container">
	<ul class="nav">
<?php
	if (!isset($type) || $type == 'aircraft') {
?>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-aircraft" || strtolower($current_page) == "statistics-registration" || strtolower($current_page) == "statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#" >
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu">
			<li><a href="<?php print $globalURL; ?>/statistics/aircraft<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Aircraft"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/registration<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Registration"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/manufacturer<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Manufacturer"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airline" || strtolower($current_page) == "statistics-airline-country" || strtolower($current_page) == "statistics-callsign"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
<?php
		if (!isset($airline_icao) || $airline_icao == 'all') {
?>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Airline"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Airline by Country"); ?></a></li>
<?php
		}
?>
		      <li><a href="<?php print $globalURL; ?>/statistics/callsign<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Callsign"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airport-departure" || strtolower($current_page) == "statistics-airport-departure-country" || strtolower($current_page) == "statistics-airport-arrival" || strtolower($current_page) == "statistics-airport-arrival-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Arrival Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-route-airport" || strtolower($current_page) == "statistics-route-waypoint"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Route"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/route-airport<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Route by Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/route-waypoint<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Route by Waypoint"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Date &amp; Time"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/date<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Date"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/time<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>"><?php echo _("Time"); ?></a></li>
		    </ul>
		</li>
		<?php
		    if (isset($globalAccidents) && $globalAccidents && (!isset($airline_icao) || $airline_icao == '' || $airline_icao == 'all')) {
		?>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-fatalities-year" || strtolower($current_page) == "statistics-fatalities-month"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Fatalities"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/fatalities/year"><?php echo _("Fatalities by Year"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/fatalities/month"><?php echo _("Fatalities last 12 months"); ?></a></li>
		    </ul>
		</li>
		<?php
		    }
		?>
<?php
	} elseif ($type == 'marine' || $type == 'tracker') {
?>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
			<?php echo _("Date &amp; Time"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
			<li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/date"><?php echo _("Date"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/time"><?php echo _("Time"); ?></a></li>
		    </ul>
		</li>
<?php
		if ($type == 'marine') {
?>
		<li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/type"><?php echo _("Type"); ?></a></li>
<?php
			if (isset($globalVM) && $globalVM) {
?>
		<li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/race"><?php echo _("Race"); ?></a></li>
<?php
			}
		}
?>

<?php
	}
?>
	</ul>
</div>
<?php
    } else {
?>
<div class="sub-menu sub-menu-container">
	<ul class="nav">
<?php
	if (!isset($type) || $type == 'aircraft') {
?>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-aircraft" || strtolower($current_page) == "statistics-registration" || strtolower($current_page) == "statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#" >
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu">
			<li><a href="<?php print $globalURL; ?>/statistics/aircraft<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Aircraft"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/registration<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Registration"); ?></a></li>
			<li><a href="<?php print $globalURL; ?>/statistics/manufacturer<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Manufacturer"); ?></a></li>
			<!-- <li><a href="<?php print $globalURL; ?>/statistics/country"><?php echo _("Country"); ?></a></li> -->
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airline" || strtolower($current_page) == "statistics-airline-country" || strtolower($current_page) == "statistics-callsign"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airline"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
<?php
		if (!isset($airline_icao) || $airline_icao == 'all') {
?>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Airline"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airline-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Airline by Country"); ?></a></li>
<?php
		}
?>
		      <li><a href="<?php print $globalURL; ?>/statistics/callsign<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Callsign"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-airport-departure" || strtolower($current_page) == "statistics-airport-departure-country" || strtolower($current_page) == "statistics-airport-arrival" || strtolower($current_page) == "statistics-airport-arrival-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-departure-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Arrival Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/airport-arrival-country<?php if (isset($airline_icao) && $airline_icao != '' && $airline_icao != 'all') echo '/'.$airline_icao; ?>/<?php echo $year.'/'.$month.'/'; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<!--
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-route-airport" || strtolower($current_page) == "statistics-route-waypoint"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Route"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/route-airport"><?php echo _("Route by Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/statistics/route-waypoint"><?php echo _("Route by Waypoint"); ?></a></li>
		    </ul>
		</li>
		-->
		<!--
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Date &amp; Time"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/date"><?php echo _("Date"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/time"><?php echo _("Time"); ?></a></li>
		    </ul>
		</li>
		-->
		<?php
		    if (isset($globalAccidents) && $globalAccidents && (!isset($airline_icao) || $airline_icao == '' || $airline_icao == 'all')) {
		?>
		<!--
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-fatalities-year" || strtolower($current_page) == "statistics-fatalities-month"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Fatalities"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/statistics/fatalities/year"><?php echo _("Fatalities by Year"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/statistics/fatalities/month"><?php echo _("Fatalities last 12 months"); ?></a></li>
		    </ul>
		</li>
		-->
		<?php
		    }
		?>
<?php
	} elseif ($type == 'marine' || $type == 'tracker') {
?>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "statistics-date" || strtolower($current_page) == "statistics-time"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Date &amp; Time"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/date"><?php echo _("Date"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/<?php print $type; ?>/statistics/time"><?php echo _("Time"); ?></a></li>
		    </ul>
		</li>

<?php
	}
?>
	</ul>
</div>
<?php
    }
?>