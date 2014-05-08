# Website

This repository is only about the website itself and will public document the to do lists and a few other things.

## Todo

* Add a `Group By` option in the `advanced search` page. Style the output accordingly.
* Add `Print styles` for the `advanced search` page for a better printing experience.
* Add a `export option` for the `advanced search` page. Perhaps integrate that with the `API`?
* `Include images` for each `aircraft type page`.
* `Improve the front page`. Perhaps add line graph or even a map of todays entries.
* Change the API parameters for all outputs to allow the same parameters as the search page.
* ~~Look into `SQL query slowness`.~~
* ~~Create a `individual page for a spotter ID` (include a map to plot the departure airport, arrival airport, route planned and the geo coordinates of the aircraft as it was recorded).~~
* ~~Fix styling on mobile devices, particular in portrait view mode~~

## Changelog

* May 7, 2014
	* Revamped the statistic section to make it easier to read as well as added a new statistic ‘Route (Waypoint)’. [See Statistic](http://www.barriespotter.com/statistic)
	* Added a new real-time notification system throughout the site, which lets users know of new database entries as they browse the site.
	* Added aircraft pictures back again. The picture that shows up only takes into account the Airline and Aircraft type, but its close enough.
* May 6, 2014
	* Significantly improved the speed of certain pages on the site.
	* The front page also has a map of todays flights.
* May 3, 2014
	* Added a new `individual page for a spotter ID` with a Google map of the departure & arrival airport, route of what its supposed fly and the position of the aircraft of when it was captured into the database. [See Example](http://www.barriespotter.com/flightid/4655)
	* Fixed the ‘styling for the mobile portrait’ view mode.
	* Changed the general styles of the website (colours, fonts etc.)
* May 2, 2014
	* Launched an `experimental simple API`. I decided to host the documentation of the API on [GitHub](https://github.com/barriespotter/API).
* May 1, 2014
	* Re-launched the site with new data feed by [FlightAware](http://flightaware.com).