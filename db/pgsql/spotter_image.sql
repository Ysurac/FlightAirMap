CREATE TABLE spotter_image (
  spotter_image_id serial,
  registration varchar(20) NOT NULL,
  image_thumbnail varchar(255) NOT NULL,
  image varchar(255) NOT NULL,
  image_copyright varchar(255),
  image_source varchar(255),
  image_source_website varchar(255),
  PRIMARY KEY (spotter_image_id)
);

