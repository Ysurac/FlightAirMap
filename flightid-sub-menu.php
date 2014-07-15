<span class="sub-menu-statistic column mobile">
	<a href="#" onclick="showSubMenu(); return false;">Additional Data <i class="fa fa-plus"></i></a>
</span>
<div class="sub-menu sub-menu-container">
	<ul class="nav nav-pills">
		<li><a href="<?php print $globalURL; ?>/flightid/<?php print $_GET['id']; ?>" <?php if (strtolower($current_page) == "flightid-overview"){ print 'class="active"'; } ?>>Overview</a></li>
		<li><a href="http://flightaware.com/live/flight/id/<?php print $spotter_array[0]['flightaware_id']; ?>" target="_blank">Flight Status&raquo;</a></li>
		<li><a href="http://flightaware.com/live/flight/id/<?php print $spotter_array[0]['flightaware_id']; ?>/tracklog" target="_blank">Flight Log&raquo;</a></li>
	</ul>
</div>