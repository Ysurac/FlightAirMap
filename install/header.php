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
<link rel="apple-touch-icon" href="<?php print $globalURL; ?>/images/touch-icon.png">
<link href='http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300,300italic,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/bootstrap-select.min.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/style.css?<?php print time(); ?>" />
<link type="text/css" rel="stylesheet" href="<?php print $globalURL; ?>/css/print.css?<?php print time(); ?>" />
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
