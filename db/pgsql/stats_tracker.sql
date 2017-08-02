CREATE TABLE IF NOT EXISTS stats_tracker (
  stats_tracker_id serial,
  stats_type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  tracker_date varchar(255) NOT NULL,
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_tracker ADD PRIMARY KEY (stats_tracker_id);
