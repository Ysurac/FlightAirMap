<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if ($_POST['airline'] != "")
{
	header('Location: '.$globalURL.'/airline/'.$_POST['airline']);
} else {

	$title = "Airlines";
	require('header.php');
	
	print '<div class="column">';
		
		print '<h1>Airlines</h1>';
		
		$airline_names = Spotter::getAllAirlineNames();
		$previous = null;
		print '<div class="alphabet-legend">';
			foreach($airline_names as $value) {
			    $firstLetter = substr($value['airline_name'], 0, 1);
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
		foreach($airline_names as $value) {
		    $firstLetter = substr($value['airline_name'], 0, 1);
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
			
			    print '<div class="alphabet-airline alphabet-item">';
			    	print '<a href="'.$globalURL.'/airline/'.$value['airline_icao'].'">';
							if (@getimagesize($globalURL.'/images/airlines/'.$value['airline_icao'].'.png'))
							{
								print '<img src="'.$globalURL.'/images/airlines/'.$value['airline_icao'].'.png" alt="Click to see airline activity" title="Click to see airline activity" /> ';
							} else {
								print $value['airline_name'];
							}
						print '</a>';
					print '</div>';
				}
		}
		
  
  print '</div>';
  
  require('footer.php');
}
?>