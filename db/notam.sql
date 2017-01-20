CREATE TABLE IF NOT EXISTS notam (
  notam_id int(11) NOT NULL AUTO_INCREMENT,
  ref varchar(15) NOT NULL,
  title varchar(255) DEFAULT NULL,
  notam_type varchar(1) NOT NULL,
  fir varchar(4) NOT NULL,
  code varchar(5) NOT NULL,
  rules enum('IFR/VFR','IFR','VFR','') NOT NULL,
  scope enum('Airport warning','Enroute warning','Navigation warning','Airport/Enroute warning','Airport/Navigation warning','Enroute/Navigation warning','Airport/Enroute/Navigation warning') NOT NULL DEFAULT 'Airport warning',
  lower_limit int(11) NOT NULL,
  upper_limit int(11) NOT NULL,
  center_latitude float NOT NULL,
  center_longitude float NOT NULL,
  radius int(11) NOT NULL,
  date_begin datetime NOT NULL,
  date_end datetime DEFAULT NULL,
  permanent tinyint(1) NOT NULL DEFAULT '0',
  notam_text text NOT NULL,
  full_notam text NOT NULL,
  PRIMARY KEY (notam_id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE INDEX ref_idx ON notam (ref);