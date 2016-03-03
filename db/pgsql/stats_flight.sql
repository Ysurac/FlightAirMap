CREATE TABLE IF NOT EXISTS stats_flight (
  stats_flight_id serial,
  type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  flight_date varchar(255) NOT NULL
);

ALTER TABLE stats_flight ADD PRIMARY KEY (stats_flight_id);
