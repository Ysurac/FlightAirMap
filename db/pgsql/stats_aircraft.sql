CREATE TABLE stats_aircraft (
  stats_aircraft_id serial,
  aircraft_icao varchar(10) NOT NULL,
  cnt integer NOT NULL,
  aircraft_name varchar(255) DEFAULT NULL,
  aircraft_manufacturer varchar(255) DEFAULT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_aircraft
  ADD PRIMARY KEY (stats_aircraft_id), ADD UNIQUE (aircraft_icao,stats_airline,filter_name);
