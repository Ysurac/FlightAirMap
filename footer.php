</section>

<footer class="container">
	<?php
	    if (isset($sql_time)) {
	?>
	<i>Page generated in <?php print round($sql_time+$page_time,2); ?>s (<?php print round($page_time,2); ?>ms PHP - <?php print round($sql_time,2); ?>ms SQL)</i>
	<br />
	<?php
	    }
	?>
	<span>Developed in Barrie by <a href="http://www.mariotrunz.com" target="_blank">Mario Trunz</a> & at <a href="http://www.zugaina.com" target="_blank">Zugaina</a> by Ycarus</span> - <span><a href="<?php if (isset($globalURL)) print $globalURL; ?>/about#source">Source &amp; Credits</a></span> - <span><a href="https://github.com/Ysurac/FlightAirMap/issues" target="_blank">Report any issues</a></span>
</footer>

<div class="notifications bottom-left"></div>
<table id="header-fixed"></table>

</body>
</html>