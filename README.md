# FlightAirMap

FlightAirMap is a fork of Barrie Spotter [https://github.com/barriespotter/Web_App](https://github.com/barriespotter/Web_App) with map, airspaces, METAR, PDO and ADS-B support.
Browse through the data based on a particular aircraft, airline or airport to search through the database. See extensive statistics such as most common aircraft type, airline, departure & arrival airport and busiest time of the day, or just explore flights.

Flights are displayed on a 2D map that can be from : OpenStreetMap, Mapbox, MapQuest, Yandex, Bing, Google,... Or a 3D map using OpenStreetMap or Bing.

It can be used with Dump1090 or any SBS source.
Can also be used with virtual airlines sources like FlightGear, whazzup.txt from IVAO, VATSIM, phpvms, Virtual Airlines Manager,...

It also support glidernet APRS source.

Satellites can be displayed on 3D map.
On december, you can track Santa's flight.

## Demo
* From ADS-B : [https://www.flightairmap.fr/](https://www.flightairmap.fr/)
* From IVAO : [https://ivao.flightairmap.fr/](https://ivao.flightairmap.fr/)
* From VATSIM : [https://vatsim.flightairmap.fr/](https://vatsim.flightairmap.fr/)

## Documentation

A wiki is available here: https://github.com/Ysurac/FlightAirMap/wiki

## System Requirements

To install the web app, you need to have meet the following requirements:

* PHP version 5.4 or greater
* MySQL version 5.6.1 or greater, MariaDB or PostgreSQL (with PostGIS if you want waypoints, airspace and countries data)
* An HTTP Server such as:
	* Apache 2.0+
	* Nginx (include install/flightairmap-nginx-conf.include in server part of the config)

## Required Extensions

The follwing is a list of PHP extensions that must be installed on your server in order for this web app to run properly:

* cURL [http://php.net/curl](http://php.net/curl) which is used to access remote sites.
* PDO [http://php.net/pdo](http://php.net/pdo) with MySQL or PostgreSQL driver is required for database access (Maybe other databases work too, not tested).
* JSON [http://php.net/json](http://php.net/json)
* ZIP [http://php.net/zip](http://php.net/zip) needed for SBS.
* DOM [http://php.net/dom](http://php.net/dom)
* SimpleXML [http://php.net/simplexml](http://php.net/simplexml)

## Install ##
Check https://github.com/Ysurac/FlightAirMap/wiki/Installation for detailed installation instruction.

### Web install/Update ###
(This is the recommanded way to install)

Use install/index.php

### Console install ###
* Create a mysql database.
* Populate the database with db/*.sql
* supply require/settings.php with your database credentials.
* run install/populate_all.php or install/populate_ivao.php if you use IVAO as datasource

## Data Sources

### ADS-B in SBS1 (BaseStation) format (real flights)
* You can use dump1090 [https://github.com/mutability/dump1090](https://github.com/mutability/dump1090) with a RTL dongle, Radarcape deltadb.txt or aircraftlist.json, or wazzup file, or /action.php/acars/data of phpvms...
* run scripts/daemon-spotter.php

### ACARS (only messages from real flights)
* You have to use acarsdec [http://sourceforge.net/projects/acarsdec/](http://sourceforge.net/projects/acarsdec/) : acarsdec -N 127.0.0.1:9999 -r 0 131.525 131.550 131.725
* run scripts/daemon-acars.php

### APRS (real flights)
* You can use APRS server from glidernet like aprs.glidernet.org:14580 (or port 10152 without APRS filter)

### IVAO (virtual flights)
* You can use as source a whazzup.txt file like : http://api.ivao.aero/getdata/whazzup/whazzup.txt

### VATSIM (virtual flights)
* You can use as source a vatsim-data.txt file like : http://info.vroute.net/vatsim-data.txt

### Virtual Airlines Manager (virtual flights)
* You need to copy the file **install/VAM/VAM-json.php** in your VAM directory, and use it as source 