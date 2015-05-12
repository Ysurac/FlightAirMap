CREATE TABLE translation (
  TranslationID serial,
  Reg varchar(20),
  Reg_correct varchar(20),
  Operator varchar(20),
  Operator_correct varchar(20),
  Source varchar(255),
  date_added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modified timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (TranslationID)
);
