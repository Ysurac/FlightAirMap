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
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
<!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script src="<?php print $globalURL; ?>/js/bootstrap-select.min.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/jquery.slides.min.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/jquery-ui-timepicker-addon.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/script.js?<?php print time(); ?>"></script>
<link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-select.min.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/print.css?<?php print time(); ?>" />
<?php
if (strtolower($current_page) == "about")
{
?>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
<?php
}
?>
<?php
if (strtolower($current_page) == "index")
{
?>
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style-map.css?<?php print time(); ?>" />
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
<script src="<?php print $globalURL; ?>/js/leaflet.ajax.min.js"></script>
<script src="<?php print $globalURL; ?>/js/Marker.Rotate.js?<?php print time(); ?>"></script>
<script src="<?php print $globalURL; ?>/js/map.js.php?<?php print time(); ?>"></script>
<?php
}
?>
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
if ($globalURL == "http://www.barriespotter.com"){ ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-51151807-1', 'barriespotter.com');
  ga('send', 'pageview');

</script>
<?php } ?>
</head>

<body class="page-<?php print strtolower($current_page); ?>">
<div class="navbar navbar-fixed-top" role="navigation">
  <div class="container">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="<?php print $globalURL; ?>/search" class="navbar-toggle navbar-toggle-search"><i class="fa fa-search"></i></a>
      <a class="navbar-brand" href="<?php print $globalURL; ?>"><img src="<?php print $globalURL.$logoURL; ?>" height="30px" /></a>
    </div>
    <div class="collapse navbar-collapse">
      <ul class="nav navbar-nav">
      	<li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Explore <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/aircraft">Aircraft Types</a></li>
			<li><a href="<?php print $globalURL; ?>/airline">Airlines</a></li>
			<li><a href="<?php print $globalURL; ?>/airport">Airports</a></li>
			<li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/latest">Latest Activity</a></li>
            <li><a href="<?php print $globalURL; ?>/date/<?php print date("Y-m-d"); ?>">Today's Activity</a></li>
            <li><a href="<?php print $globalURL; ?>/newest">Newest by Category</a></li>
            <li><hr /></li>
            <li><a href="<?php print $globalURL; ?>/highlights">Special Highlights</a></li>
          </ul>
        </li>
      	<li><a href="<?php print $globalURL; ?>/search">Search</a></li>
      	<li><a href="<?php print $globalURL; ?>/statistics">Statistics</a></li>
        <li class="dropdown">
          <a href="<?php print $globalURL; ?>/about" class="dropdown-toggle" data-toggle="dropdown">About <b class="caret"></b></a>
          <ul class="dropdown-menu">
          	<li><a href="<?php print $globalURL; ?>/about">The Project</a></li>
          	<li><a href="<?php print $globalURL; ?>/about/export">Exporting Data</a></li>
            <li><hr /></li>
			<li><a href="<?php print $globalURL; ?>/about/tv">Spotter TV</a></li>
            <li><hr /></li>
            
            <?php if ($globalURL == "http://barriespotter.com") { ?>
          	<li><a href="https://github.com/barriespotter/Web_App/issues" target="_blank">Report any Issues</a></li>
          	<li><a href="https://www.facebook.com/barriespotter" target="_blank">Contact</a></li>
            <?php } else { ?>
        	<li><a href="https://github.com/Ysurac/Web_App/issues" target="_blank">Report any Issues</a></li>
            <?php } ?>
          </ul>
        </li>
      </ul>
      <form action="<?php print $globalURL; ?>/search" method="get">
  			<input type="text" name="q" value="<?php if (isset($GET['q'])) { if ($_GET['q'] != ""){ print $_GET['q']; } else { print 'search'; } } else { print 'search'; } ?>" onfocus="if (this.value=='search'){this.value='';}" /><button type="submit"><i class="fa fa-search"></i></button>
  		</form>
  		<div class="social">
            <?php if ($globalURL == "http://barriespotter.com") { ?>
  			<a href="http://www.facebook.com/barriespotter" target="_blank" title="Like us on Facebook"><i class="fa fa-facebook"></i></a>
  			<a href="http://www.twitter.com/barriespotter" target="_blank" title="Follow us on Twitter"><i class="fa fa-twitter"></i></a>
  			<a href="http://barriespotter.github.io" target="_blank" title="Fork us on Github"><i class="fa fa-github"></i></a>
  		<?php } ?>
  		</div>
    </div><!--/.nav-collapse -->
  </div>
</div>

<?php
if (isset($top_header)) {
    if ($top_header != "")
    {
	print '<div class="top-header container clear" role="main">';
		print '<img src="'.$globalURL.'/images/'.$top_header.'" alt="'.$title.'" title="'.$title.'" />';
	print '</div>';
    }
}
?>

<section class="container main-content clear">
