CREATE TABLE IF NOT EXISTS stats_flight (
  stats_flight_id serial,
  stats_type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  flight_date varchar(255) NOT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_flight ADD PRIMARY KEY (stats_flight_id);
