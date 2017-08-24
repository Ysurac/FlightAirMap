CREATE TABLE IF NOT EXISTS stats_tracker_type (
  stats_tracker_type_id serial,
  type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_tracker_type ADD PRIMARY KEY (stats_tracker_type_id), ADD UNIQUE (type);
