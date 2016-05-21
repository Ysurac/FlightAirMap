DROP TABLE IF EXISTS taf;
CREATE TABLE taf (
  taf_id serial,
  taf_location varchar(10) NOT NULL,
  taf_date timestamp,
  taf text NOT NULL
);


ALTER TABLE taf ADD PRIMARY KEY (taf_id), ADD UNIQUE (taf_location);
