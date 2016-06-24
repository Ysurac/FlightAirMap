<?php
//INSTALLED OR NOT ?
$globalInstalled = FALSE;

//GLOBAL SITE NAME
$globalName = '';

// GLOBAL URL
$globalURL = '';

// Logo URL
$logoURL = '/images/logo2.png';

// Activate debug
$globalDebug = TRUE;

// LANGUAGE
$globalLanguage = 'EN'; // Used only for wikipedia links for now

// MAP PROVIDER
$globalMapProvider = 'MapQuest-OSM'; // Can be Mapbox, OpenStreetMap, MapQuest-OSM or MapQuest-Aerial
$globalMapboxId = 'examples.map-i86nkdio'; // Mapbox id
$globalMapboxToken = ''; // Mapbox token

//COVERAGE AREA (its based on a box model. i.e. top-left | top-right | bottom-right | bottom-left)
$globalLatitudeMax = '46.92'; //the maximum latitude (north)
$globalLatitudeMin = '42.14'; //the minimum latitude (south)
$globalLongitudeMax = '6.2'; //the maximum longitude (west)
$globalLongitudeMin = '1.0'; //the minimum longitude (east)

$globalCenterLatitude = '46.38'; //the latitude center of your coverage area
$globalCenterLongitude = '5.29';//the longitude center of your coverage area

$globalLiveZoom = '9'; //default zoom on Live Map

// FLIGHTS MUST BE INSIDE THIS CIRCLE
$globalDistanceIgnore = array();
// ^^ example for 100km : array('latitude' => '46.38','longitide' => '5.29','distance' => '100');

// DATABASE CONNECTION LOGIN
$globalDBdriver = 'mysql'; // PDO driver used. Tested with mysql, maybe pgsql or others work...
$globalDBhost = 'localhost'; //database connection url
$globalDBuser = ''; //database username
$globalDBpass = ''; //database password
$globalDBname = ''; //database name
$globalTransaction = TRUE; //Activate database transaction support


//FLIGHTAWARE API INFO
$globalFlightAware = FALSE; //set to TRUE to use FlightAware as data import
$globalFlightAwareUsername = ''; //FlightAware Username
$globalFlightAwarePassword = ''; //FlightAware Password/API key

// TIMEZONE
$globalTimezone = 'Europe/Paris';

// DAEMON
$globalDaemon = TRUE; // Run cron-sbs.php as daemon (don't work well if source is a real SBS1 device)
$globalCronEnd = '60'; //the script run for xx seconds if $globalDaemon is disable in SBS mode

// FORK
$globalFork = TRUE; // Allow cron-sbs.php to fork to fetch schedule, no more schedules fetch if set to FALSE

// MINIMUM TIME BETWEEN UPDATES FOR HTTP SOURCES (in seconds)
$globalMinFetch = '50';

// DISPLAY FLIGHT INTERVAL ON MAP (in seconds)
$globalLiveInterval = '200';

// MINIMAL CHANGE TO PUT IN DB
$globalCoordMinChange = '0.02'; // minimal change since last message for latitude/longitude (limit write to DB)

// LIVE MAP REFRESH (in seconds)
$globalMapRefresh = '30';

// IDLE TIMEOUT (in minutes)
$globalMapIdleTimeout = '30';

// DISPLAY INFO OF FLIGHTS IN A POPUP
$globalMapPopup = FALSE;

// DISPLAY ROUTE OF FLIGHT
$globalMapRoute = TRUE;

// DISPLAY FLIGHTS PATH HISTORY
$globalMapHistory = FALSE;

// FLIGHT ESTIMATION BETWEEN UPDATES
$globalMapEstimation = TRUE;

// WRAP MAP OR REPEAT
$globalMapWrap = TRUE;

// ALLOW SITE TRANSLATION
$globalTranslate = TRUE;

// UNITS
$globalUnitDistance = 'km'; // km, nm or mi
$globalUnitAltitude = 'm'; // m or feet
$globalUnitSpeed = 'kmh'; // kmh, knots or mph

// *** Virtual flights ***
//IVAO
$globalIVAO = FALSE;

//VATSIM
$globalVATSIM = FALSE;

//phpVMS
$globalphpVMS = FALSE;

//User can choose between IVAO, VATSIM or phpVMS
$globalMapVAchoose = FALSE;
// ************************

//ADS-B, SBS1 FORMAT
$globalSBS1 = TRUE; //set to FALSE to not use SBS1 as data import
$globalSourcesTimeOut = '15';
$globalSourcesupdate = '10'; //Put data in DB after xx seconds/flight

//DATA SOURCES
$globalSources = array(array('host' => '127.0.0.1', 'port' => '30003'));
// ^^ in the form array(array(host => 'host1', 'port' => 'port1','name' => 'first source','format' => 'sbs'),array('host' => 'host2', 'port' => 'port2','name' => 'Other source', 'format' => 'aprs'),array('host' => 'http://xxxxx/whazzup.txt')); Use only sources you have the rights for.

//ACARS Listen in UDP
$globalACARS = FALSE;
$globalACARSHost = '0.0.0.0'; // Local IP to listen
$globalACARSPort = '9999';
$globalACARSArchive = array('10','80','81','82','3F'); // labels of messages to archive
$globalACARSArchiveKeepMonths = '0';

//APRS configuration (for glidernet)
$globalAPRSversion = $globalName.' using FlightAirMap';
$globalAPRSssid = 'FAM';
$globalAPRSfilter = 'r/'.$globalCenterLatitude.'/'.$globalCenterLongitude.'/250.0';

//Minimal distance to tell if a flight is arrived to airport (in km)
$globalClosestMinDist = '50';

// To display Squawk usage we need Squawk country for now
$globalSquawkCountry = 'UK';

//BIT.LY API INFO (used in the search page for a shorter URL)
$globalBitlyAccessToken = ''; //the access token from the bit.ly API

//British Airways API info
$globalBritishAirwaysKey = '';

// Lufhansa API info
$globalLufthansaKey = '';

// Transavia API info
$globalTransaviaKey = '';

//ignore the flights during imports that have the following airports ICAO (departure/arrival) associated with them
$globalAirportIgnore = array();
//accept the flights during imports that have the following airports ICAO (departure/arrival) associated with them
$globalAirportAccept = array();

//ignore the flights that have the following airline ICAO
$globalAirlineIgnore = array();
//accept the flights that have the following airline ICAO
$globalAirlineAccept = array();

//accept the flights that have the following pilot id (only for VA)
$globalPilotIdAccept = array();


// *** Archive ***
//Archive all data
$globalArchive = FALSE;

//Archive data olders than xx months (if globalArchive enabled, else delete) (0 to disable)
$globalArchiveMonths = '0';

//Archive previous year (if globalArchive enabled, else delete)
$globalArchiveYear = FALSE;

//Keep Archive track of flight for xx months (0 to disable)
$globalArchiveKeepTrackMonths = '0';

//Keep Archive of flight for xx months (0 to disable)
$globalArchiveKeepMonths = '0';
// ************************


//NOTAM
$globalNOTAM = TRUE;
$globalNOTAMSource = ''; //URL of your feed from notaminfo.com

//METAR
$globalMETAR = TRUE;
$globalMETARcycle = TRUE; // If update_db.php in cron job, all METAR are downloaded from NOAA
// else put an url as METAR source, can be vatsim.
$globalMETARurl = ''; // Use {icao} to indicate where airport icao must be put in url

//Retrieve private Owner
$globalOwner = FALSE;

// *** Aircraft pics ***
//Retrieve Image from externals sources
$globalAircraftImageFetch = TRUE;
//Sources for Aircraft image
$globalAircraftImageSources = array('ivaomtl','wikimedia','airportdata','deviantart','flickr','bing','jetphotos','planepictures','planespotters','customsources');
// Custom source configuration {registration} will be replaced by aircraft registration (exif get copyright from exif data for each pic)
// example of config : $globalAircraftImageCustomSources = array('thumbnail' => 'http://pics.myurl.com/thumbnail/{registration}.jpg','original' => 'http://myurl/original/{registration}.jpg','source_website' => 'https://www.myurl.com', 'source' => 'customsite', 'exif' => true);
// ************************

//Retrieve schedules from externals sources (set to FALSE for IVAO or if $globalFork = FALSE)
$globalSchedulesFetch = TRUE;
//Sources for airline schedule if not official airline site
$globalSchedulesSources = array('flightmapper','costtotravel','flightradar24','flightaware');

//Retrieve translation from external sources (set to FALSE for IVAO)
$globalTranslationFetch = TRUE;
//Sources for translation, to find name of flight from callsign
$globalTranslationSources = array();
?>
