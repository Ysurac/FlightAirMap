CREATE TABLE stats_source (
  stats_source_id serial,
  source_data text,
  source_name varchar(255),
  stats_type varchar(255),
  stats_date date DEFAULT NULL
);

ALTER TABLE stats_source ADD PRIMARY KEY (stats_source_id), ADD UNIQUE (stats_date,source_name,stats_type);
