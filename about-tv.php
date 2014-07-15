<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Barrie Spotter TV";
require('header.php');
?>

<div class="info column">
    <h1>Barrie Spotter TV</h1>  
    
    <p>Barrie Spotter TV is a interface that shows the latest flights in the Barrie area for displays with a resolution of 1920x1080 (1080p). Its <strong>FREE</strong> and no custom hardware is required. Simply plug a modern laptop or computer into any 1080p HDTV and access the link below. Data gets updated in near real-time.</p>

    <div class="tv-image">
    	<img src="/images/about-tv.png" alt="Barrie Spotter TV" title="Barrie Spotter TV" />
    </div>
    
     <div class="tv-launch">
    	<a href="<?php print $globalURL; ?>/tv" target="_blank">Launch Barrie Spotter TV</a>
    </div>
		
</div>

<?php
require('footer.php');
?>