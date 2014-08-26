<?php
//GLOBAL SITE NAME
$globalName = "";

// GLOBAL URL
$globalURL = "";

// Logo URL
$logoURL = "/images/logo.png";

//COVERAGE AREA (its based on a box model. i.e. top-left | top-right | bottom-right | bottom-left)
$globalLatitudeMax = ''; //the maximum latitude (north)
$globalLatitudeMin = ''; //the minimum latitude (south)
$globalLongitudeMax = ''; //the maximum longitude (west)
$globalLongitudeMin = ''; //the minimum longitude (east)

$globalCenterLatitude = ''; //the latitude center of your coverage area
$globalCenterLongitude = '';//the longitude center of your coverage area

// DATABASE CONNECTION LOGIN
$globalDBdriver = 'mysql'; // PDO driver used. Tested with mysql, maybe pgsql or others work...
$globalDBhost = 'localhost'; //database connection url
$globalDBuser = ''; //database username
$globalDBpass = ''; //database password
$globalDBname = ''; //database name

//FLIGHTAWARE API INFO
$globalFlightAware = FALSE; //set to TRUE to use FlightAware as data import
$globalFlightAwareUsername = ''; //FlightAware Username
$globalFlightAwarePassword = ''; //FlightAware Password/API key

//BIT.LY API INFO (used in the search page for a shorter URL)
$globalBitlyAccessToken = ''; //the access token from the bit.ly API
?>
