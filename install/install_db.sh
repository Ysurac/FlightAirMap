#!/bin/sh
# You should always use PHP scripts.
# This script will certainly not work.
# ! DON'T USE ME PLEASE !
# Config #
DB_USER=""
DB_PASS=""
DB_NAME=""
DB_HOST="127.0.0.1"

CURRENT_PATH=`pwd`

# You should go to http://virtualradarserver.co.uk and http://planebase.biz/ to read the licences

## Create All tables ##
ls -1 ${CURRENT_PATH}/../db/*.sql | awk '{ print "source",$0 }' | mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST} ${DB_NAME}

## Routes ##
cd /tmp
rm -f StandingData.sqb
wget http://www.virtualradarserver.co.uk/Files/StandingData.sqb.gz
gunzip StandingData.sqb.gz
sqlite3 -header -csv StandingData.sqb "select Route.RouteID, Route.callsign, operator.Icao AS operator_icao, FromAir.Icao AS FromAirportIcao, '' as FromAirport_Time, ToAir.Icao AS ToAirportIcao, '' as ToAirport_Time, rstp.allstop AS AllStop, 'tmp/BaseStation.sqb' as Source from Route inner join operator ON Route.operatorId = operator.operatorId LEFT JOIN Airport AS FromAir ON route.FromAirportId = FromAir.AirportId LEFT JOIN Airport AS ToAir ON ToAir.AirportID = route.ToAirportID LEFT JOIN (select RouteId,GROUP_CONCAT(icao,' ') as allstop from routestop left join Airport as air ON routestop.AirportId = air.AirportID group by RouteID) AS rstp ON Route.RouteID = rstp.RouteID" > routes.csv
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST} -e "use ${DB_NAME};" -e "DELETE FROM routes WHERE Source = '' OR Source = 'tmp/StandingData.sqb' OR Source IS NULL;" -e "LOAD DATA INFILE '/tmp/routes.csv' INTO TABLE routes FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"
cd -

## Basestation ## (used for modeS info for now)
cd /tmp
rm -f basestationall.zip
rm -f BaseStation.sqb
wget --referer=http://planebase.biz http://planebase.biz/sqb.php?f=basestationall.zip -O /tmp/basestationall.zip
unzip basestationall.zip
sqlite3 -header -csv BaseStation.sqb "select '' as AircraftID, FirstCreated, LastModified, ModeS, ModeSCountry, Registration, ICAOTypeCode, 'tmp/basestation.sqb' as Source from Aircraft" > basestation.csv
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST}  -e "use ${DB_NAME};" -e "DELETE FROM aircraft_modes WHERE Source = '' OR Source IS NULL OR Source = 'tmp/basestation.sqb';" -e "LOAD DATA INFILE '/tmp/basestation.csv' INTO TABLE aircraft_modes FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES;"
cd -

## Translation ##
cd /tmp
rm -f translation.zip
rm -f translation.csv
wget http://www.acarsd.org/download/translation.php -O /tmp/translation.zip
unzip translation.zip
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST}  -e "use ${DB_NAME};" -e "DELETE FROM translation WHERE Source = '' OR Source IS NULL OR Source = 'tmp/translation.csv';"  -e "LOAD DATA INFILE '/tmp/translation.csv' INTO TABLE translation FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 1 LINES (Reg,Reg_correct,Operator,Operator_correct);"
cd -

## Waypoints ##
cd /tmp
rm -f awy.dat.gz
rm -f awy.dat
#wget https://gitorious.org/fg/fgdata/raw/e81f8a15424a175a7b715f8f7eb8f4147b802a27:Navaids/awy.dat.gz
wget http://pkgs.fedoraproject.org/repo/extras/FlightGear-Atlas/awy.dat.gz/f530c9d1c4b31a288ba88dcc8224268b/awy.dat.gz
#wget http://sourceforge.net/p/flightgear/fgdata/ci/next/tree/Navaids/awy.dat.gz?format=raw -O /tmp/awy.dat.gz
gunzip awy.dat.gz
dos2unix awy.dat
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST}  -e "use ${DB_NAME};" -e "TRUNCATE TABLE waypoints;" -e "LOAD DATA INFILE '/tmp/awy.dat' INTO TABLE waypoints FIELDS TERMINATED BY ' ' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' IGNORE 3 LINES (name_begin,latitude_begin,longitude_begin,name_end,latitude_end,longitude_end,high,base,top,segment_name);"
cd -

## Airspace ##
cd /tmp
rm airspace.sql
cp ${CURRENT_PATH}/../db/airspace.sql.gz /tmp/airspace.sql.gz
gunzip /tmp/airspace.sql.gz
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST} ${DB_NAME} < /tmp/airspace.sql

## Countries ##
cd /tmp
rm countries.sql
cp ${CURRENT_PATH}/../db/countries.sql.gz /tmp/countries.sql.gz
gunzip /tmp/countries.sql.gz
mysql -u ${DB_USER} -p${DB_PASS} -h ${DB_HOST} ${DB_NAME} < /tmp/countries.sql
