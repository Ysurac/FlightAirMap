<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store,no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");
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
<meta name="keywords" content="<?php print $title; ?> spotter live flight tracking tracker map aircraft airline airport history database" />
<meta name="description" content="<?php print $title; ?> | <?php print $globalName; ?> is an open source project documenting most of the aircrafts." />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="../images/touch-icon.png">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="../css/bootstrap-select.min.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="../css/style.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="../css/print.css?<?php print time(); ?>" />
<script type="text/javascript" src="../js/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
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
    if (!isset($_SESSION['install']) && !isset($_POST['dbtype'])) {
?>
<script language="JavaScript" type="text/javascript">
    function datasource_js() {
        //document.getElementById("flightaware_data").style.display = document.getElementById("flightaware").checked ? "inline" : "none" ;
        document.getElementById("sailaway_data").style.display = document.getElementById("globalvm").checked ? "inline" : "none" ;
        //document.getElementById("sbs_data").style.display = (document.getElementById("sbs").checked || document.getElementById("aprs").checked) ? "inline" : "none" ;
        document.getElementById("optional_sbs").style.display = (document.getElementById("sbs").checked || document.getElementById("aprs").checked) ? "inline" : "none" ;
        //document.getElementById("sbs_url").style.display = (document.getElementById("ivao").checked || document.getElementById("sbs").checked || document.getElementById("vatsim").checked) ? "inline" : "none" ;
        document.getElementById("acars_data").style.display = document.getElementById("acars").checked ? "inline" : "none" ;
    }
    function schedule_js() {
        document.getElementById("schedules_options").style.display = document.getElementById("schedules").checked ? "inline" : "none" ;
    }
    function mapmatching_js() {
        document.getElementById("mapmatching_options").style.display = document.getElementById("mapmatching").checked ? "inline" : "none" ;
    }
    function daemon_js() {
        document.getElementById("cronends").style.display = document.getElementById("daemon").checked ? "none" : "inline" ;
    }
    function create_database_js() {
        document.getElementById("createdb_data").style.display = document.getElementById("createdb").checked ? "inline" : "none" ;
    }
    function metarcycle_js() {
        document.getElementById("metarsrc").style.display = document.getElementById("metarcycle").checked ? "none" : "inline" ;
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
	    $(".sources").append('<tr><td><input type="text" name="source_name[]" value="" /></td><td><input type="text" name="source_latitude[]" value="" /></td><td><input type="text" name="source_longitude[]" value="" /></td><td><input type="text" name="source_altitude[]" value="" /></td><td><input type="text" name="source_city[]" value="" /></td><td><input type="text" name="source_country[]" value="" /></td><td><input type="text" name="source_name[]" value="" /></td></tr>');
	});
	$('.del-row-source').click(function() {
	    if($(".sources tr").length != 2)
	    {
		$(".sources tr:last-child").remove();
	    }
	});
    });
    
    function deleteRow(el) {
	var table = document.getElementById('SourceTable');
	var i = el.parentNode.parentNode.rowIndex;
	table.deleteRow(i);
    }

    function insRow() {
        var table = document.getElementById('SourceTable'),
	tbody = table.getElementsByTagName('tbody')[0],
        clone = tbody.rows[0].cloneNode(true);
	var new_row = updateRow(clone.cloneNode(true), ++tbody.rows.length, true);
        tbody.appendChild(new_row);
    }
    function updateRow(row, i, reset) {
        var inp1 = row.cells[0].getElementsByTagName('input')[0];
        var inp2 = row.cells[1].getElementsByTagName('input')[0];
        var inp3 = row.cells[2].getElementsByTagName('select')[0];
        var inp4 = row.cells[3].getElementsByTagName('input')[0];
        if (reset) {
            inp1.value = inp2.value = inp4.value = '';
            inp3.value = 'auto';
        }
        return row;
    }
    function deleteRowNews(el) {
	var table = document.getElementById('NewsTable');
	var i = el.parentNode.parentNode.rowIndex;
	table.deleteRow(i);
    }

    function insRowNews() {
        var table = document.getElementById('NewsTable'),
	tbody = table.getElementsByTagName('tbody')[0],
        clone = tbody.rows[0].cloneNode(true);
	var new_row = updateRowNews(clone.cloneNode(true), ++tbody.rows.length, true);
        tbody.appendChild(new_row);
    }

    function updateRowNews(row, i, reset) {
        var inp1 = row.cells[0].getElementsByTagName('input')[0];
        var inp2 = row.cells[1].getElementsByTagName('select')[0];
        var inp3 = row.cells[2].getElementsByTagName('select')[0];
        if (reset) {
            inp1.value = '';
            inp2.value = 'en';
            inp3.value = 'global';
        }
        return row;
    }
</script>
<?php
    }
?>

</head>

<?php
    if (!isset($_SESSION['install']) && !isset($_POST['dbtype']) && isset($_SESSION['identified'])) {
?>

<body class="page-<?php print strtolower($current_page); ?>" onload="datasource_js(); metarcycle_js(); create_database_js(); daemon_js(); schedule_js()">
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
<?php
    if (!isset($_SESSION['install']) && !isset($_POST['dbtype']) && isset($_SESSION['identified'])) {
?>
	<div class="collapse navbar-collapse">
	    <ul class="nav navbar-nav">
		<li><a href="#database">Database</a></li>
		<li><a href="#site">Site</a></li>
		<li><a href="#mapprov">Map</a></li>
		<li><a href="#coverage">Coverage</a></li>
		<li><a href="#zone">Zone of interest</a></li>
		<li class="dropdown">
		    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Sources <b class="caret"></b></a>
		    <ul class="dropdown-menu">
			<li><a href="#sourceloc">Sources location</a></li>
			<li><a href="#datasource">Data source</a></li>
			<li><a href="#sources">Sources</a></li>
			<li><a href="#acars_data">Source ACARS</a></li>
		    </ul>
		</li>
		<li><a href="#optional">Optional</a></li>
		<!--
		<li class="dropdown">
		    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Optional <b class="caret"></b></a>
		    <ul class="dropdown-menu">
		    </ul>
		</li>
		-->
		<li><a href="https://github.com/Ysurac/FlightAirMap/wiki" target="_blank">Documentation</a></li>
	     </ul>
	</div>
<?php
    }
?>
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
