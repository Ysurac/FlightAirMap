<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

$title = "Spotter TV";
require_once('header.php');
?>
<div class="info column">
    <h1>Spotter TV</h1>  
    <p><?php echo _("Spotter TV is a interface that shows the latest flights for displays with a resolution of 1920x1080 (1080p). Its <strong>FREE</strong> and no custom hardware is required. Simply plug a modern laptop or computer into any 1080p HDTV and access the link below. Data gets updated in near real-time."); ?></p>
    <div class="tv-image">
    	<img src="<?php print $globalURL; ?>/images/about-tv.png" alt="Spotter TV" title="Spotter TV" />
    </div>
     <div class="tv-launch">
    	<a href="<?php print $globalURL; ?>/tv" target="_blank"><?php echo _("Launch Spotter TV"); ?></a>
    </div>
</div>
<?php
require_once('footer.php');
?>