<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if ($_POST['aircraft_type'] != "")
{
	header('Location: '.$globalURL.'/aircraft/'.$_POST['aircraft_type']);
} else {
	$title = "Aircraft Types";
	require('header.php');
	
	print '<div class="column">';
		
		print '<h1>Aircraft Types</h1>';
		
		$aircraft_types = Spotter::getAllAircraftTypes();
		$previous = null;
		print '<div class="alphabet-legend">';
			foreach($aircraft_types as $value) {
			    $firstLetter = substr($value['aircraft_name'], 0, 1);
			    if($previous !== $firstLetter)
			    {
			    	if ($previous != null){
				    	print ' | ';
			    	}
			    	print '<a href="#'.$firstLetter.'">'.$firstLetter.'</a>';
			    }
			    $previous = $firstLetter;
			}
		print '</div>';
		$previous = null;
		foreach($aircraft_types as $value) {
		    $firstLetter = substr($value['aircraft_name'], 0, 1);
		    if ($firstLetter != "")
		    {
			    if($previous !== $firstLetter)
			    {
			    	if ($previous != null){
				    	print '</div>';
			    	}
			    	print '<a name="'.$firstLetter.'"></a><h4 class="alphabet-header">'.$firstLetter.'</h4><div class="alphabet">';
			    }
			    $previous = $firstLetter;
			
			    print '<div class="alphabet-item">';
			    	print '<a href="'.$globalURL.'/aircraft/'.$value['aircraft_icao'].'">';
								print $value['aircraft_name'];
						print '</a>';
					print '</div>';
				}
		}
		
	
	print '</div>';
	
	require('footer.php');
}
?>