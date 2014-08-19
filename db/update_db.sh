#!/bin/sh

# Config #
DB_USER=""
DB_PASS=""
DB_NAME=""

# You should go to http://virtualradarserver.co.uk and http://pp-sqb.mantma.co.uk/ to read the licences

## Routes ##
cd /tmp
rm -f StandingData.sqb
wget http://www.virtualradarserver.co.uk/Files/StandingData.sqb.gz
gunzip StandingData.sqb.gz
sqlite3 -header -csv StandingData.sqb "select Route.RouteID, Route.callsign, operator.Icao AS operator_icao, FromAir.Icao AS FromAirportIcao, ToAir.Icao AS ToAirportIcao from Route inner join operator ON Route.operatorId = operator.operatorId LEFT JOIN Airport AS FromAir ON route.FromAirportId = FromAir.AirportId LEFT JOIN Airport AS ToAir ON ToAir.AirportID = route.ToAirportID" > routes.csv
mysql -u ${DB_USER} -p${DB_PASS} -e "use ${DB_NAME};" -e "TRUNCATE TABLE routes;" -e "LOAD DATA INFILE '/tmp/routes.csv' INTO TABLE routes FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"

## Basestation ## (used for modeS info for now)
cd /tmp
rm -f basestation_latest.zip
rm -rf basestation_latest
wget http://pp-sqb.mantma.co.uk/basestation_latest.zip
unzip basestation_latest.zip
cd basestation_latest
sqlite3 -header -csv basestation.sqb "select * from Aircraft" > basestation.csv
mysql -u ${DB_USER} -p${DB_PASS} -e "use ${DB_NAME};" -e "TRUNCATE TABLE aircraft_modes;" -e "LOAD DATA INFILE '/tmp/basestation_latest/basestation.csv' INTO TABLE aircraft_modes FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"
