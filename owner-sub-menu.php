<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="stats_airline">
	<form id="changedate" method="post">
		<input type="month" name="date" onchange="statsdatechange(this);" value="<?php if (isset($year) && $year != '') echo $year.'-'; ?><?php if (isset($month) && $month != '') echo $month; ?>" />
	</form>
</div>
<?php
    if (isset($owner)) {
?>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/owner/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "owner-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "owner-statistics-aircraft" || strtolower($current_page) == "owner-statistics-registration" || strtolower($current_page) == "owner-statistics-manufacturer"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Aircraft"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/owner/statistics/aircraft/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Aircraft Type"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/owner/statistics/registration/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Registration"); ?></a></li>
					<li><a href="<?php print $globalURL; ?>/owner/statistics/manufacturer/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Manufacturer"); ?></a></li>
		    </ul>
		</li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "owner-statistics-departure-airport" || strtolower($current_page) == "owner-statistics-departure-airport-country" || strtolower($current_page) == "owner-statistics-arrival-airport" || strtolower($current_page) == "owner-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php echo _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/owner/statistics/departure-airport/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/owner/statistics/departure-airport-country/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/owner/statistics/arrival-airport/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/owner/statistics/arrival-airport-country/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/owner/statistics/route/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "owner-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/owner/statistics/time/<?php print $owner; ?><?php if (isset($year) && $year != '') echo '/'.$year; ?><?php if (isset($month) && $month != '') echo '/'.$month; ?>" <?php if (strtolower($current_page) == "owner-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>
<?php
    }
?>