<?php
//gets the page file and stores it in a variable
$file_path = pathinfo($_SERVER['SCRIPT_NAME']);
$current_page = $file_path['filename'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=10" />
<title><?php print $title; ?> | <?php print $globalName; ?></title>
<meta name="keywords" content="<?php print $title; ?> barrie ontario canada spotter live flight tracking tracker map aircraft airline airport history database" />
<meta name="description" content="<?php print $title; ?> | <?php print $globalName; ?> is an open source project documenting most of the aircrafts that have flown near the Barrie area." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="../images/touch-icon.png">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="../css/bootstrap-select.min.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="../css/style.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="../css/print.css?<?php print time(); ?>" />
<script type="text/javascript" src="../js/jquery-2.1.3.min.js"></script>
<?php
/*
if ($facebook_meta_image != "")
{
?>
<meta property="og:image" content="<?php print $facebook_meta_image; ?>"/>
<?php
} else {
?>
<meta property="og:image" content="<?php print $globalURL; ?>/images/touch-icon.png"/>
<?php
}
*/
?>
<meta property="og:title" content="<?php print $title; ?> | <?php print $globalName; ?>"/>
<meta property="og:url" content="<?php print $globalURL.$_SERVER['REQUEST_URI']; ?>"/>
<meta property="og:site_name" content="<?php print $globalName; ?>"/>

<?php
    if (!isset($_SESSION['install']) && !$globalInstalled) {
?>
<script language="JavaScript" type="text/javascript">
    function datasource_js() {
        //document.getElementById("flightaware_data").style.display = document.getElementById("flightaware").checked ? "inline" : "none" ;
        document.getElementById("sbs_data").style.display = (document.getElementById("sbs").checked || document.getElementById("aprs").checked) ? "inline" : "none" ;
        document.getElementById("optional_sbs").style.display = (document.getElementById("sbs").checked || document.getElementById("aprs").checked) ? "inline" : "none" ;
        document.getElementById("sbs_url").style.display = (document.getElementById("ivao").checked || document.getElementById("sbs").checked || document.getElementById("vatsim").checked) ? "inline" : "none" ;
        document.getElementById("acars_data").style.display = document.getElementById("acars").checked ? "inline" : "none" ;
    }
    function schedule_js() {
        document.getElementById("schedules_options").style.display = document.getElementById("schedules").checked ? "inline" : "none" ;
    }
    function daemon_js() {
        document.getElementById("cronends").style.display = document.getElementById("daemon").checked ? "none" : "inline" ;
    }
    function map_provider_js() {
        if (document.getElementById("mapprovider").value == "Mapbox") {
	    document.getElementById("mapbox_data").style.display = "inline";
	} else {
	    document.getElementById("mapbox_data").style.display = "none";
	}
    }   
    function create_database_js() {
        document.getElementById("createdb_data").style.display = document.getElementById("createdb").checked ? "inline" : "none" ;
    }
</script>
<script type='text/javascript'>
    $(function(){
	$('.add-row-ip').click(function() {
	    $(".sbsip").append('<tr><td><input type="text" name="sbshost[]" value="" /></td><td><input type="number" name="sbsport[]" value="" /></td></tr>');
	});
	$('.del-row-ip').click(function() {
	    if($(".sbsip tr").length != 2)
	    {
		$(".sbsip tr:last-child").remove();
	    }
	});
	$('.add-row-url').click(function() {
	    $(".sbsurl").append('<tr><td><input type="text" name="sbsurl[]" value="" /></td></tr>');
	});
	$('.del-row-url').click(function() {
	    if($(".sbsurl tr").length != 2)
	    {
		$(".sbsurl tr:last-child").remove();
	    }
	});
	$('.add-row-source').click(function() {
	    $(".sources").append('<tr><td><input type="text" name="source_name[]" value="" /></td><td><input type="text" name="source_latitude[]" value="" /></td><td><input type="text" name="source_longitude[]" value="" /></td><td><input type="text" name="source_altitude[]" value="" /></td><td><input type="text" name="source_city[]" value="" /></td><td><input type="text" name="source_country[]" value="" /></td></tr>');
	});
	$('.del-row-source').click(function() {
	    if($(".sources tr").length != 2)
	    {
		$(".sources tr:last-child").remove();
	    }
	});
    });
</script>
<?php
    }
?>

</head>

<?php
    if (!isset($_SESSION['install']) && !$globalInstalled) {
?>

<body class="page-<?php print strtolower($current_page); ?>" onload="datasource_js(); map_provider_js(); create_database_js(); daemon_js(); schedule_js()">
<?php
    } else {
?>
    <body class="page-<?php print strtolower($current_page); ?>">
<?php
    }
?>
<div class="navbar navbar-fixed-top" role="navigation">
  <div class="container">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="../"><img src="<?php print '../'.$logoURL; ?>" height="30px" /></a>
    </div>
  </div>
</div>

<?php
if (isset($top_header)) {
    if ($top_header != "")
    {
	print '<div class="top-header container clear" role="main">';
		print '<img src="../images/'.$top_header.'" alt="'.$title.'" title="'.$title.'" />';
	print '</div>';
    }
}
?>

<section class="container main-content clear">
