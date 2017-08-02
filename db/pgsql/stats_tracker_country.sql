CREATE TABLE IF NOT EXISTS stats_tracker_country (
  stats_tracker_country_id serial,
  iso2 varchar(5) NOT NULL,
  iso3 varchar(5) NOT NULL,
  cnt integer NOT NULL,
  name varchar(255) DEFAULT NULL,
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_tracker_country ADD PRIMARY KEY (stats_tracker_country_id), ADD UNIQUE (iso2,filter_name);
