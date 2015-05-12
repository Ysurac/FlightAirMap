CREATE TABLE spotter_image (
  spotter_image_id serial,
  registration varchar(999) NOT NULL,
  image_thumbnail varchar(999) NOT NULL,
  image varchar(999) NOT NULL,
  image_copyright varchar(255),
  image_source varchar(255),
  image_source_website varchar(999),
  PRIMARY KEY (spotter_image_id)
);

