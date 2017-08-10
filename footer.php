</section>
<div class="pub onmap">
<?php
	if (isset($globalPub)) print $globalPub;
?>
</div>
<footer class="container">
	<?php
	    if (isset($sql_time)) {
	?>
	<i><?php echo _("Page generated in").' '.round($sql_time+$page_time,2); ?>s (<?php print round($page_time,2); ?>ms PHP - <?php print round($sql_time,2); ?>ms SQL)</i>
	<br />
	<?php
	    }
	?>
	<span>Developed in Barrie by <a href="http://mtru.nz/" target="_blank">Mario Trunz</a> & at <a href="http://www.zugaina.com" target="_blank">Zugaina</a> by Ycarus</span> - <span><a href="<?php if (isset($globalURL)) print $globalURL; ?>/about#source">Source &amp; Credits</a></span> - <span><a href="https://www.flightairmap.com/" target="_blank">Get source code</a></span>
</footer>

<div class="notifications bottom-left"></div>
<table id="header-fixed">
</table>

</body>
</html>