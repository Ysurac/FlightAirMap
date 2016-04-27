<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;"><?php echo _("Additional Data"); ?> <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/flightid/<?php print $_GET['id']; ?>" <?php if (strtolower($current_page) == "flightid-overview"){ print 'class="active"'; } ?>><?php echo _("Detailed"); ?></a></li>
		<?php if ($globalFlightAware) { ?>
		<li><a href="http://flightaware.com/live/flight/id/<?php print $spotter_array[0]['flightaware_id']; ?>" target="_blank"><?php echo _("Flight Status"); ?>&raquo;</a></li>
		<li><a href="http://flightaware.com/live/flight/id/<?php print $spotter_array[0]['flightaware_id']; ?>/tracklog" target="_blank"><?php echo _("Flight Log"); ?>&raquo;</a></li>
		<?php } ?>
		 <li class="dropdown">
		    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
			<i class="fa fa-download"></i> <?php echo _("Download Flight Data"); ?> <span class="caret"></span>
		    </a>
		    <ul class="dropdown-menu" role="menu">
			<li><a href="<?php print $globalURL; ?>/search/csv?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">CSV</a></li>
			<li><a href="<?php print $globalURL; ?>/search/rss?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">RSS</a></li>
			<li><hr /></li>
			<li><span>For Advanced Users</strong></li>
			<li><a href="<?php print $globalURL; ?>/search/json?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">JSON</a></li>
			<li><a href="<?php print $globalURL; ?>/search/xml?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">XML</a></li>
			<li><a href="<?php print $globalURL; ?>/search/yaml?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">YAML</a></li>
			<li><a href="<?php print $globalURL; ?>/search/php?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">PHP (serialized array)</a></li>
			<li><hr /></li>
			<li><span>For Geo/Map Users</span></li>
			<li><a href="<?php print $globalURL; ?>/search/kml?q=<?php print $spotter_array[0]['spotter_id']; ?>">KML</a></li>
			<li><a href="<?php print $globalURL; ?>/search/geojson?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">GeoJSON</a></li>
			<li><a href="<?php print $globalURL; ?>/search/georss?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">GeoRSS</a></li>
			<li><a href="<?php print $globalURL; ?>/search/gpx?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">GPX</a></li>
			<li><a href="<?php print $globalURL; ?>/search/wkt?q=<?php print $spotter_array[0]['spotter_id']; ?>&download=true">WKT</a></li>
		     </ul>
		</li>
	</ul>
</div>