CREATE TABLE IF NOT EXISTS stats_marine_type (
  stats_marine_type_id serial,
  type varchar(255) NOT NULL,
  type_id integer NOT NULL,
  cnt integer NOT NULL,
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_marine_type ADD PRIMARY KEY (stats_marine_type_id), ADD UNIQUE (type_id);
