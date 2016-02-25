CREATE TABLE stats_airport (
  stats_airport_id serial,
  airport_icao varchar(10) NOT NULL,
  airport_city varchar(255) DEFAULT NULL,
  airport_country varchar(255) DEFAULT NULL,
  departure integer NOT NULL DEFAULT '0',
  arrival integer NOT NULL DEFAULT '0'
);

ALTER TABLE stats_airport
  ADD PRIMARY KEY (stats_airport_id), ADD UNIQUE KEY airport_icao (airport_icao);

