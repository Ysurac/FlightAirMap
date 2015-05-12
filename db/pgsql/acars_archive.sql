CREATE TABLE acars_archive (
  acars_archive_id serial,
  ident varchar(10) NOT NULL,
  registration varchar(10) NOT NULL,
  label varchar(10) NOT NULL,
  block_id integer NOT NULL,
  msg_no varchar(10) NOT NULL,
  message text NOT NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decode text,
  PRIMARY KEY (acars_archive_id)
);
