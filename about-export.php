<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

$title = "Exporting Data";
require_once('header.php');
?>

<div class="info column">

    <h1>Exporting <?php print $globalName; ?> Data</h1> 
    
    <p>On the <a href="<?php print $globalURL; ?>/search">search page</a> there is a option to 'export' the search results in over 10 different formats. Most of these formats are industry standards, while a few have lesser adaption but could still have certain use cases.</p>
    <h3>Supported Formats</h3>
    
    <i class="fa fa-cloud-download"></i> 
    
    <ul>
    	<li><strong>CSV (Comma-Separated values):</strong> stores tabular data in plain-text form. Can be opened in most Office programs such as Microsoft Office, OpenOffice etc.</li>
    	<li><strong>RSS (Rich Site Summary):</strong> standard web feed formats to publish frequently updated information. Can be opened in any RSS reader.
	    	<ul>
	    		<li><strong>GeoRSS:</strong> is an emerging standard for encoding location as part of a Web feed.</li>
	    	</ul>
    	</li>
    	<li><strong>XML (Extensible Markup Language):</strong> is a markup language that defines a set of rules for encoding documents in a format that is both human-readable and machine-readable. Almost any programming languages have the ability to parse XML documents.</li>
    	<li><strong>JSON (JavaScript Object Notation):</strong> is an open standard format that uses human-readable text to transmit data objects consisting of attribute–value pairs. It is used primarily to transmit data between a server and web application, as an alternative to XML.
	    	<ul>
	    		<li><strong>GeoJSON:</strong> is an open standard format for encoding collections of simple geographical features along with their non-spatial attributes using JavaScript Object Notation.</li>
	    	</ul>
    	</li>
    	<li><strong>YAML (Yet Another Markup Language):</strong> is a human-readable data serialization format that takes concepts from programming languages such as C, Perl, and Python.</li>
    	<li><strong>PHP (serialized array):</strong> Generates a storable representation of a value, for primary use of the PHP programming language.</li>
    	<li><strong>KML (Keyhole Markup Language):</strong> is an XML notation for expressing geographic annotation and visualization within Internet-based, two-dimensional maps and three-dimensional Earth browsers. Most commonly used for Google Earth but can be imported in most geo visualization software.</li>
    	<li><strong>GPX (GPS Exchange Format):</strong> is an XML schema designed as a common GPS data format for software applications.</li>
    	<li><strong>WKT (Well-known text):</strong> is a text markup language for representing vector geometry objects on a map, spatial reference systems of spatial objects and transformations between spatial reference systems.</li>
    </ul>
    
    <h3>Geospatial Data</h3>
    
    <p>As seen above, some of the export formats can be used for Geospatial analysis. For those formats, they include the location of the aircraft when it first entered the <a href="<?php print $globalURL; ?>/about#coverage">coverage area</a> as well as the entire planned flight route. In addition, some of the geospatial formats also take into consideration the altitude when the aircraft flew over the coverage area, allowing for a three-dimensional geospatial analysis of air traffic.</p>
    
    <div class="export-image">
    	<img src="<?php echo $globalURL; ?>/images/about-export.png" alt="Three-Dimensional Geospatial Analysis" title="Three-Dimensional Geospatial Analysis" />
    </div>
    
    <h3>Data License</h3>
    
    <p>The data published by <?php print $globalName; ?> is made available under the Open Database License: <a href="http://opendatacommons.org/licenses/odbl/1.0/" target="_blank">http://opendatacommons.org/licenses/odbl/1.0/</a>. Any rights in individual contents of the database are licensed under the Database Contents License: <a href="http://opendatacommons.org/licenses/dbcl/1.0/" target="_blank">http://opendatacommons.org/licenses/dbcl/1.0/</a> - See more at: <a href="http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf" target="_blank">http://opendatacommons.org/licenses/odbl/#sthash.3wkOS6zA.dpuf</a></p>
    
		
</div>

<?php
require_once('footer.php');
?>