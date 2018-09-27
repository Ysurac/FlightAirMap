<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="UTF-8">
<title>Spotter TV</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="<?php print $globalURL; ?>/favicon.ico">
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
<!--[if lt IE 9]>
  <script type="text/javascript" src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script type="text/javascript" src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-tv.css?<?php print time(); ?>" />
</head>
<body>


<?php
if (isset($_GET['q']))
{
	$q = filter_input(INPUT_GET,'q',FILTER_SANITIZE_STRING);
	$spotter_array = $Spotter->searchSpotterData($q, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "0,10", "", "");
} else {
	$spotter_array = $Spotter->getLatestSpotterData("0,10", "");
}
 
print '<div class="table-responsive">';
print '<table id="table-tv">';
print '<tbody>';
foreach ($spotter_array as $spotter_item)
{
	if (isset($globalTimezone)) {
		date_default_timezone_set($globalTimezone);
	} else {
		date_default_timezone_set('UTC');
	}
	print '<tr>';
	if (isset($_GET['image']) && $_GET['image'] == "true")
	{
		if ($spotter_item['image'] != "")
		{
			print '<td class="aircraft_image">';
			print '<img src="'.$spotter_item['image'].'" alt="'._("Click to see more information about this flight").'" title="'._("Click to see more information about this flight").'" />';
			print '</td>';
		} else {
			print '<td class="aircraft_image">';
			print '<img src="'.$globalURL.'/images/placeholder.png" alt="'._("Click to see more information about this flight").'" title="'._("Click to see more information about this flight").'" />';
			print '</td>';
		}
	}
	if ($globalIVAO && (@getimagesize('images/airlines/'.$spotter_item['airline_icao'].'.gif') || @getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif')))
	{
		print '<td class="logo">';
		print '<img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.gif" />';
		print '</td>';
	} elseif (@getimagesize('images/airlines/'.$spotter_item['airline_icao'].'.png') || @getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png'))
	{
		print '<td class="logo">';
		print '<img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png" />';
		print '</td>';
	} else {
		print '<td class="logo-no-image">';
		if (isset($spotter_item['airline_name']) && $spotter_item['airline_name'] != "")
		{
			print $spotter_item['airline_name'];
		} else {
			print 'N/A';
		}
		print '</td>';
	}
	print '<td class="info">';
	print '<div class="flight">';
	print $spotter_item['departure_airport_city'].' ('.$spotter_item['departure_airport'].') <i class="fa fa-arrow-right"></i> '.$spotter_item['arrival_airport_city'].' ('.$spotter_item['arrival_airport'].')';
	print '</div>';
	print '<div class="other1">';
	if ($spotter_item['registration'] != "")
	{
		print '<span><i class="fa fa-align-justify"></i> '.$spotter_item['registration'].'</span>';
	}
	if ($spotter_item['aircraft_name'] != "")
	{
		print '<span><i class="fa fa-plane"></i> '.$spotter_item['aircraft_name'].'</span>';
	} else {
		if ($spotter_item['aircraft_type'] != "")
		{
			print '<span><i class="fa fa-plane"></i> '.$spotter_item['aircraft_type'].'</span>';
		}
	}
	print '<span><i class="fa fa-calendar"></i> '.date("r", strtotime($spotter_item['date_iso_8601'])).'</span>';
	print '</div>';
	print '<div class="other2">';
	print '<span><i class="fa fa-arrow-up"></i> '.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'];
	if (isset($spotter_item['departure_airport_time']) && $spotter_item['departure_airport_time'] != '') {
		print ' ('.$spotter_item['departure_airport_time'].')';
	}
	print '</span>';
	print '<span><i class="fa fa-arrow-down"></i> '.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'];
	if (isset($spotter_item['arrival_airport_time']) && $spotter_item['arrival_airport_time'] != '') {
		print ' ('.$spotter_item['arrival_airport_time'].')';
	}
	print '</span>';
	print '</div>';
	print '<div class="other3">';
	if ($spotter_item['ident'] != "")
	{
		print '<span><i class="fa fa-th-list"></i> '.$spotter_item['ident'].'</span>';
	}
	if (isset($spotter_item['airline_name']) && $spotter_item['airline_name'] != "")
	{
		print '<span><i class="fa fa-align-justify"></i> '.$spotter_item['airline_name'].'</span>';
	}
	if ($spotter_item['airline_country'] != "")
	{
		print '<span><i class="fa fa-globe"></i> '.$spotter_item['airline_country'].'</span>';
	}
	print '</div>';
	print '</td>';
	print '</tr>';
}
print '<tbody>';
print '</table>';
print '</div>';
?>

<script>
$( document ).ready(function() {

  //loads the notification system every 16 seconds
  setInterval( getNewDataTV, 16000 );
  
  //changes the information every 10 seconds
  setInterval( changeInformation, 10000 );
  
});
function getNewDataTV()
{
   
<?php
	if (isset($_GET['image']) && isset($_GET['q'])) {
		$image = filter_input(INPUT_GET,'image',FILTER_SANITIZE_STRING);
		$q = filter_input(INPUT_GET,'q',FILTER_SANITIZE_STRING);
?>
   $.getJSON( "<?php print $globalURL; ?>/getLatestData-tv.php?other_i="+other_i+"&image=<?php print $image; ?>&q=<?php print $q; ?>", function( data ) {
<?php
	} elseif (isset($_GET['image'])) {
		$image = filter_input(INPUT_GET,'image',FILTER_SANITIZE_STRING);
?>
   $.getJSON( "<?php print $globalURL; ?>/getLatestData-tv.php?other_i="+other_i+"&image=<?php print $image; ?>", function( data ) {
<?php
	} elseif (isset($_GET['q'])) {
		$q = filter_input(INPUT_GET,'q',FILTER_SANITIZE_STRING);
?>
   $.getJSON( "<?php print $globalURL; ?>/getLatestData-tv.php?other_i="+other_i+"&q=<?php print $q; ?>", function( data ) {
<?php
	} else {
?>
   $.getJSON( "<?php print $globalURL; ?>/getLatestData-tv.php?other_i="+other_i+"", function( data ) {
<?php
	}
?>
   	$.each(data.flights, function(i, item) {
	   	if (item.html != "")
	   	{
	   		$('#table-tv').prepend(item.html);
	   		$('#table-tr-'+item.flight_id).fadeIn();
	   		$('#table-tv tr:last').remove();
	   	}
   	});
	});
}
var other_i = 1;
$('.other2').hide();
$('.other3').hide();
function changeInformation()
{
	if (other_i == 1)
	{
		$('.other3').hide();
		$('.other1').fadeOut( "slow", function() {
    	$('.other2').fadeIn();
		});
		other_i = 2;
	} else if (other_i == 2)
	{
		$('.other1').hide();
		$('.other2').fadeOut( "slow", function() {
    	$('.other3').fadeIn();
		});
		other_i = 3;
	} else if (other_i == 3)
	{
		$('.other2').hide();
		$('.other3').fadeOut( "slow", function() {
    	$('.other1').fadeIn();
		});
		other_i = 1;
	}
}
</script>

</body>
</html>