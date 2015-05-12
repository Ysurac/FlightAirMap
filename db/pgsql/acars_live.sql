CREATE TABLE acars_live (
  acars_live_id serial,
  ident varchar(10) NULL,
  registration varchar(10) NULL,
  label varchar(10) NULL,
  block_id integer NULL,
  msg_no varchar(10) NULL,
  message text NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decode text null,
  PRIMARY KEY (acars_live_id)
);
