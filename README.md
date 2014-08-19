# Barrie Spotter - Web App

Barrie Spotter is an open source project documenting most of the aircrafts that have flown near the Barrie area. Browse through the data based on a particular aircraft, airline or airport to search through the database. See extensive statistics such as most common aircraft type, airline, departure & arrival airport and busiest time of the day, or just explore flights near the Barrie area.

## System Requirements

To install the web app which Barrie Spotter runs, you need to have meet the following requirements:

* PHP version 5.2.3 or greater (5.3 or greater is recommended)
* MySQL version 5.0 or greater
* An HTTP Server such as:
	* Apache 1.3+
	* Apache 2.0+
	* Microsoft Internet Information Server (MS IIS)
	
## Required Extensions

The follwing is a list of PHP extensions that must be installed on your server in order for this web app to run properly:

* cURL [http://php.net/curl](http://php.net/curl) which is used to access remote sites.
* MySQL [http://php.net/mysql](http://php.net/mysql) is required for database access.

Centos instructions:
* yum install php-curl
* yum install php-mysql
* # service httpd restart

## DataBase
* Create a mysql database.
* supply require/settings.php with your database credentials.

## DataBase
* get yourself a flightAware api key http://flightaware.com/commercial/flightxml/
* supply require/settings.php with this key

## Data Sources
At the current moment only [FlightAware](http://www.flightaware.com) is supported as the data source for this web app. More data sources will be avialable soon.
