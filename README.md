# FlightAirMap

FlightAirMap is a fork of Barrie Spotter [https://github.com/barriespotter/Web_App](https://github.com/barriespotter/Web_App) with map, airspaces, PDO and ADS-B support.
Browse through the data based on a particular aircraft, airline or airport to search through the database. See extensive statistics such as most common aircraft type, airline, departure & arrival airport and busiest time of the day, or just explore flights.

## System Requirements

To install the web app which Barrie Spotter runs, you need to have meet the following requirements:

* PHP version 5.2.3 or greater (5.3 or greater is recommended)
* MySQL version 5.0 or greater (5.6 if you want to see airspace)
* SQLite 3 (if you use ADS-B as datasource)
* An HTTP Server such as:
	* Apache 1.3+
	* Apache 2.0+

(Nginx not supported for now)

## Required Extensions

The follwing is a list of PHP extensions that must be installed on your server in order for this web app to run properly:

* cURL [http://php.net/curl](http://php.net/curl) which is used to access remote sites.
* PDO [http://php.net/pdo](http://php.net/pdo) with MySQL driver is required for database access (Maybe other databases work too, not tested). SQLite driver needed for SBS.
* JSON [http://php.net/json](http://php.net/json)
* ZIP [http://php.net/zip](http://php.net/zip) needed for SBS.
* DOM [http://php.net/dom](http://php.net/dom)
* SimpleXML [http://php.net/simplexml](http://php.net/simplexml)

## Install ##
### Web install ###
(This is the recommanded way to install)

Use install/index.php

### Console install ###
* Create a mysql database.
* Populate the database with db/*.sql
* supply require/settings.php with your database credentials.
* If you use ADS-B as datasource, supply install/update_db.sh with your database credentials and run it. (You should go to http://www.virtualradarserver.co.uk/ and http://pp-sqb.mantma.co.uk/ to read the licences. If you find databases with better licences contact me)

## Data Sources
You can choose [FlightAware](http://www.flightaware.com) *OR* ADS-B in SBS1 (BaseStation) format AND/OR ACARS from acarsdec.
(FlightAware is no more tested, I don't have a paid API account)

### FlightAware Api Key 
* get yourself a flightAware api key http://flightaware.com/commercial/flightxml/
* supply require/settings.php with this key
* run cron.php

### ADS-B
* You can use dump1090 [https://github.com/mutability/dump1090](https://github.com/mutability/dump1090) with a RTL dongle
* run cron-sbs.php (The name is not really good, this should be run one time like a daemon, use a init script or screen)

### ACARS
* You have to use acarsdec [http://sourceforge.net/projects/acarsdec/](http://sourceforge.net/projects/acarsdec/) : acarsdec -N 127.0.0.1:9999 -r 0 131.525 131.550 131.725
* run cron-acars.php (also a daemon)