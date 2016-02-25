CREATE TABLE stats_aircraft (
  stats_aircraft_id serial,
  aircraft_icao varchar(10) NOT NULL,
  cnt integer NOT NULL,
  aircraft_name varchar(255) DEFAULT NULL
);

ALTER TABLE stats_aircraft
  ADD PRIMARY KEY (stats_aircraft_id), ADD UNIQUE KEY aircraft_icao (aircraft_icao);
