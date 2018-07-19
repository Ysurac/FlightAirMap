<?php
$Spotter = new Spotter();
if ($spotter_array[0]['registration'] != "")
{
	$highlight = $Spotter->getHighlightByRegistration($spotter_array[0]['registration']);
	if ($highlight != "")
	{
		print '<div class="alert alert-warning">'._("This aircraft has a Highlight:").' '.$highlight.'</div>';
	}
}
?>

<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Statistics"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/registration/<?php print $registration; ?>" <?php if (strtolower($current_page) == "registration-detailed"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<li class="dropdown">
		    <a class="dropdown-toggle <?php if(strtolower($current_page) == "registration-statistics-departure-airport" || strtolower($current_page) == "registration-statistics-departure-airport-country" || strtolower($current_page) == "registration-statistics-arrival-airport" || strtolower($current_page) == "registration-statistics-arrival-airport-country"){ print 'active'; } ?>" data-toggle="dropdown" href="#">
		      <?php _("Airport"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
		      <li><a href="<?php print $globalURL; ?>/registration/statistics/departure-airport/<?php print $registration; ?>"><?php echo _("Departure Airport"); ?></a></li>
		      <li><a href="<?php print $globalURL; ?>/registration/statistics/departure-airport-country/<?php print $registration; ?>"><?php echo _("Departure Airport by Country"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/registration/statistics/arrival-airport/<?php print $registration; ?>"><?php echo _("Arrival Airport"); ?></a></li>
			  <li><a href="<?php print $globalURL; ?>/registration/statistics/arrival-airport-country/<?php print $registration; ?>"><?php echo _("Arrival Airport by Country"); ?></a></li>
		    </ul>
		</li>
		<li><a href="<?php print $globalURL; ?>/registration/statistics/route/<?php print $registration; ?>" <?php if (strtolower($current_page) == "registration-statistics-route"){ print 'class="active"'; } ?>><?php echo _("Route"); ?></a></li>
		<li><a href="<?php print $globalURL; ?>/registration/statistics/time/<?php print $registration; ?>" <?php if (strtolower($current_page) == "registration-statistics-time"){ print 'class="active"'; } ?>><?php echo _("Time"); ?></a></li>
	</ul>
</div>