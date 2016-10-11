CREATE TABLE IF NOT EXISTS stats_country (
  stats_country_id serial,
  iso2 varchar(5) NOT NULL,
  iso3 varchar(5) NOT NULL,
  cnt integer NOT NULL,
  name varchar(255) DEFAULT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_country ADD PRIMARY KEY (stats_country_id), ADD UNIQUE (iso2,stats_airline,filter_name);
