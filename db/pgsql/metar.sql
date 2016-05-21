DROP TABLE IF EXISTS metar;
CREATE TABLE metar (
  metar_id serial,
  metar_location varchar(10) NOT NULL,
  metar_date timestamp,
  metar text NOT NULL
);


ALTER TABLE metar ADD PRIMARY KEY (metar_id), ADD UNIQUE (metar_location);

