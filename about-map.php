<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Barrie Spotter Live Map";
require('header.php');
?>

<div class="info column">
    <h1>Barrie Spotter Live Map</h1> 
    
    <div class="image-right-desktop">
    	<img src="/images/about-live-map.png" alt="Barrie Spotter Live Map" title="Barrie Spotter Live Map" />
    </div>
    
    <p>The <a href="<?php print $globalURL; ?>">Barrie Spotter Live Map</a> is a full screen page showing the latest positions of the aircrafts in near real-time within the Barrie Spotter coverage area. The map itself updates automatically every minute and you can even see the brighter area in the map which is the Barrie Spotter coverage area.</p>
    
    <p>Clicking on the aircraft icon allows you to see information about the flight, including details such as aircraft type, registration, current altitude (in both feet and Flight level), speed (in knots), heading (in degrees) and the coordinates. Additionally, the aircraft image also shows up, which is based on the registration of the aircraft, just like on the rest of the site.</p>
    
    <p>You can also plot your own location on the map, by clicking the location icon on the left side. This way you can see the aircraft relative to your current location.</p>
    
    <p>The information presented on the map ties into the existing Barrie Spotter database. For example, you can easily click on the airport on the map popup to go to the airport profile to see all the other flights that have been flown to/from that airport.</p>
    
    <p>&nbsp</p><p>&nbsp</p>
    
    <div class="image-right-mobile">
    	<img src="/images/about-mobile-live-map.png" alt="Barrie Spotter Live Map" title="Barrie Spotter Live Map" />
    </div>
    
    <h3>Mobile - Geolocation &amp; Compass Mode</h3>
    
    <p>The map on mobile devices has the same features as the desktop version, with one additional mode. Besides plotting your current location (using your smartphone's GPS functionailty), there is also a compass mode.</p>
    
    <p>With the compass mode you can point with your phone into the direction of the airplane you want to see more information about, and the map will also change and point into the direction. This can be very useful when your out and about and there is a airliner flying above and you just want see more information about it.</p>
    
    <p>Keep in mind that this isn't an app, but a mobile website. Compass mode uses the <a href="http://www.w3.org/TR/orientation-event/" target="_blank">W3C Device Orientation Specification</a> and is not supported on all mobile browsers just yet. <a href="http://caniuse.com/#feat=deviceorientation" target="_blank">Check out this link</a> to see if your mobile browser is supported.</p>
    
    <p>&nbsp</p><p>&nbsp</p>
    
    <h3>Frequently Asked Questions (FAQ)</h3>
    
    <strong>Why is the map blank? There are no aircrafts visible.</strong>
    <p>A: If the map is blank that means that there are no aircrafts currently flying within the Barrie Spotter coverage area.</p>
    
    <strong>When I see an aircraft in the sky and I check the website I don't see anything on the map. Why is that?</strong>
    <p>A: Unfortunately, some of the data sources (such as the FAA) are delayed by 2-5 minutes.</p>
    
    <strong>Why are there no images available in the aircraft popup?</strong>
    <p>A: We get the images based on the aircraft registration. Some of our data sources don't include the aircraft registrations, so we can't get an image for that particular flight.</p>
    
    <strong>What is the update frequency?</strong>
    <p>A: The map updates automatically every minute.</p>
		
</div>

<?php
require('footer.php');
?>