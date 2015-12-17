DROP TABLE IF EXISTS metar;
CREATE TABLE metar (
  metar_id serial,
  metar_location varchar(10) NOT NULL,
  metar_date timestamp,
  metar varchar(999) NOT NULL
);


ALTER TABLE metar
  ADD PRIMARY KEY (metar_id), ADD UNIQUE KEY location (metar_location);

