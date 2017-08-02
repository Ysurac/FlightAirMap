CREATE TABLE IF NOT EXISTS stats_marine (
  stats_marine_id serial,
  stats_type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  marine_date varchar(255) NOT NULL,
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_marine ADD PRIMARY KEY (stats_marine_id);
