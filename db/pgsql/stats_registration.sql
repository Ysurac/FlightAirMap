CREATE TABLE IF NOT EXISTS stats_registration (
  stats_registration_id serial,
  registration varchar(10) NOT NULL,
  cnt integer NOT NULL,
  aircraft_icao varchar(10) DEFAULT NULL
);

ALTER TABLE stats_registration ADD PRIMARY KEY (stats_registration_id), ADD UNIQUE KEY registration (registration);

