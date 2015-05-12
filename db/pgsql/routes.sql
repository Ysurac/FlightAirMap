CREATE TABLE routes (
  RouteID serial,
  CallSign varchar(8),
  Operator_ICAO varchar(4),
  FromAirport_ICAO varchar(4),
  FromAirport_Time varchar(10) NULL,
  ToAirport_ICAO varchar(4),
  ToAirport_Time varchar(10) NULL,
  RouteStop varchar(255),
  Source varchar(255) NULL,
  date_added timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  date_modified timestamp NULL,
  date_lastseen timestamp NULL,
  PRIMARY KEY (RouteID)
);
