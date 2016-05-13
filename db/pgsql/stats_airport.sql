CREATE TABLE stats_airport (
  stats_airport_id serial,
  airport_icao varchar(10) NOT NULL,
  airport_name varchar(255) NOT NULL,
  airport_city varchar(255) DEFAULT NULL,
  airport_country varchar(255) DEFAULT NULL,
  departure integer NOT NULL DEFAULT '0',
  arrival integer NOT NULL DEFAULT '0',
  stats_type varchar(50) NOT NULL DEFAULT 'yearly',
  date date DEFAULT NULL
);

ALTER TABLE stats_airport ADD PRIMARY KEY (stats_airport_id), ADD UNIQUE (airport_icao,stats_type,date);
