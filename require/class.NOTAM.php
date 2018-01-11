<?php
/**
 * This class is part of FlightAirmap. It's used to parse NOTAM
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/class.Common.php');
require_once(dirname(__FILE__).'/class.Spotter.php');

class NOTAM {
	public $db;
	private $abbr = array(
	                    'A/A' => 'Air-to-air',
	                    'A/G' => 'Air-to-ground',
	                    'AAL' => 'Above Aerodrome Level',
	                    'ABM' => 'Abeam',
	                    'ABN' => 'Aerodrome Beacon',
	                    'ABT' => 'About',
	                    'ABV' => 'Above',
	                    'ACC' => 'Area Control',
	                    'ACFT' => 'Aircraft',
	                    'ACK' => 'Acknowledge',
	                    'ACL' => 'Altimeter Check Location',
	                    'ACN' => 'Aircraft Classification Number',
	                    'ACPT' => 'Accepted',
	                    'ACT' => 'Active',
	                    'AD' => 'Aerodrome',
	                    'ADA' => 'Advisory Area',
	                    'ADC' => 'Aerodrome Chart',
	                    'ADDN' => 'Additional',
	                    'ADIZ' => 'Air defense identification zone',
	                    'ADJ' => 'Adjacent',
	                    'ADR' => 'Advisory Route',
	                    'ADS' => 'Automatic Dependent Surveillance',
	                    'ADVS' => 'Advisory Service',
	                    'ADZ' => 'Advised',
	                    'AFIL' => 'Flight Plan Filed In The Air',
	                    'AFIS' => 'Airport flight information service',
	                    'AFM' => 'Affirm',
	                    'AFT' => 'After',
	                    'AGA' => 'Aerodromes, Air Routes and Ground Aids',
	                    'AGN' => 'Again',
	                    'AIS' => 'Aeronautical information service',
	                    'ALERFA' => 'Alert Phase',
	                    'ALRS' => 'Alerting Service',
	                    'ALS' => 'Approach Lighting System',
	                    'ALT' => 'Altitude',
	                    'ALTN' => 'Alternate',
	                    'AMA' => 'Area Minimum Altitude',
	                    'ANC' => 'Aeronautical Chart',
	                    'ANCS' => 'Aeronautical Navigation Chart',
	                    'ANS' => 'Answer',
	                    'AOC' => 'Aerodrome Obstacle Chart',
	                    'AP' => 'Airport',
	                    'APCH' => 'Approach',
	                    'APDC' => 'Aircraft Parking/docking Chart',
	                    'APN' => 'Apron',
	                    'APNS' => 'Aprons',
	                    'APP' => 'Approach Control',
	                    'APR' => 'April',
	                    'APRX' => 'Approximately',
	                    'APSG' => 'After Passing',
	                    'APV' => 'Approved',
	                    'ARC' => 'Area Chart',
	                    'ARNG' => 'Arrange',
	                    'ARO' => 'Air Traffic Services Reporting Office',
	                    'ARP' => 'Aerodrome Reference Point',
	                    'ARR' => 'Arriving',
	                    'ARST' => 'Arresting',
	                    'ASC' => 'Ascend To',
	                    'ASDA' => 'Accelerate-Stop Distance Available',
	                    'ASPEEDG' => 'Airspeed Gain',
	                    'ASPEEDL' => 'Airspeed Loss',
	                    'ASPH' => 'Asphalt',
	                    'ATA' => 'Actual Time of Arrival',
	                    'ATD' => 'Actual Time of Departure',
	                    'ATFM' => 'Air Traffic Flow Management',
	                    'ATIS' => 'Automatic terminal information service',
	                    'ATM' => 'Air Traffic Management',
	                    'ATP' => 'At',
	                    'ATTN' => 'Attention',
	                    'ATZ' => 'Aerodrome Traffic Zone',
	                    'AUG' => 'August',
	                    'AUTH' => 'Authorization',
	                    'AUW' => 'All Up Weight',
	                    'AUX' => 'Auxiliary',
	                    'AVBL' => 'Available',
	                    'AVG' => 'Average',
	                    'AVGAS' => 'Aviation Gasoline',
	                    'AWTA' => 'Advise At What Time Able',
	                    'AWY' => 'Airway',
	                    'AWYS' => 'Airways',
	                    'AZM' => 'Azimuth',
	                    'BA' => 'Braking Action',
	                    'BCN' => 'Beacon',
	                    'BCST' => 'Broadcast',
	                    'BDRY' => 'Boundary',
	                    'BFR' => 'Before',
	                    'BLDG' => 'Building',
	                    'BLO' => 'Below Clouds',
	                    'BLW' => 'Below',
	                    'BRF' => 'Short',
	                    'BRG' => 'Bearing',
	                    'BRKG' => 'Breaking',
	                    'BTL' => 'Between Layers',
	                    'BTN' => 'Between',
	                    'CD' => 'Candela',
	                    'CDN' => 'Coordination',
	                    'CF' => 'Change Frequency To',
	                    'CFM' => 'Confirm',
	                    'CGL' => 'Circling Guidance Light(s)',
	                    'CH' => 'Channel',
	                    'CHG' => 'Changed',
	                    'CIT' => 'Near or Over Large Towns',
	                    'CIV' => 'Civil',
	                    'CK' => 'Check',
	                    'CL' => 'Centre Line',
	                    'CLBR' => 'Calibration',
	                    'CLD' => 'Cloud',
	                    'CLG' => 'Calling',
	                    'CLIMB-OUT' => 'Climb-out Area',
	                    'CLR' => 'Clearance',
	                    'CLRD' => 'Cleared',
	                    'CLSD' => 'Closed',
	                    'CMB' => 'Climb',
	                    'CMPL' => 'Complete',
	                    'CNL' => 'Cancel',
	                    'CNS' => 'Communications, Navigation And Surveillance',
	                    'COM' => 'Communications',
	                    'CONC' => 'Concrete',
	                    'COND' => 'Condition',
	                    'CONS' => 'Continuous',
	                    'CONST' => 'Construction',
	                    'CONT' => 'Continued',
	                    'COOR' => 'Coordination',
	                    'COORD' => 'Coordinates',
	                    'COP' => 'Change-over Point',
	                    'COR' => 'Correction',
	                    'COT' => 'At The Coast',
	                    'COV' => 'Covered',
	                    'CPDLC' => 'Controller-pilot Data Link Communications',
	                    'CPL' => 'Current Flight Plan',
	                    'CRC' => 'Cyclic Redundancy Check',
	                    'CRZ' => 'Cruise',
	                    'CTA' => 'Control area',
	                    'CTAM' => 'Climb To And Maintain',
	                    'CTC' => 'Contact',
	                    'CTL' => 'Control',
	                    'CTN' => 'Caution',
	                    'CTR' => 'Control Zone',
	                    'CVR' => 'Cockpit Voice Recorder',
	                    'CW' => 'Continuous Wave',
	                    'CWY' => 'Clearway',
	                    'DA' => 'Decision Altitude',
	                    'DCKG' => 'Docking',
	                    'DCP' => 'Datum Crossing Point',
	                    'DCPC' => 'Direct Controller-pilot Communications',
	                    'DCT' => 'Direct',
	                    'DEC' => 'December',
	                    'DEG' => 'Degrees',
	                    'DEP' => 'Departing',
	                    'DES' => 'Descend',
	                    'DEST' => 'Destination',
	                    'DETRESFA' => 'Distress Phase',
	                    'DEV' => 'Deviating',
	                    'DFDR' => 'Digital Flight Data Recorder',
	                    'DFTI' => 'Distance From Touchdown Indicator',
	                    'DH' => 'Decision Height',
	                    'DIP' => 'Diffuse',
	                    'DIST' => 'Distance',
	                    'DIV' => 'Divert',
	                    'DLA' => 'Delay',
	                    'DLY' => 'Daily',
	                    'DME' => 'Distance measuring equipment',
	                    'DNG' => 'Dangerous',
	                    'DOM' => 'Domestic',
	                    'DPT' => 'Depth',
	                    'DR' => 'Dead Reckoning',
	                    'DRG' => 'During',
	                    'DTAM' => 'Descend To And Maintain',
	                    'DTG' => 'Date-time Group',
	                    'DTHR' => 'Displaced Runway Threshold',
	                    'DTRT' => 'Deteriorating',
	                    'DTW' => 'Dual Tandem Wheels',
	                    'DUPE' => 'This Is A Duplicate Message',
	                    'DUR' => 'Duration',
	                    'DVOR' => 'Doppler VOR',
	                    'DW' => 'Dual Wheels',
	                    'EAT' => 'Expected Approach Time',
	                    'EB' => 'Eastbound',
	                    'EDA' => 'Elevation Differential Area',
	                    'EET' => 'Estimated Elapsed Time',
	                    'EFC' => 'Expect Further Clearance',
	                    'ELBA' => 'Emergency Location Beacon',
	                    'ELEV' => 'Elevation',
	                    'ELR' => 'Extra Long Range',
	                    'EM' => 'Emission',
	                    'EMERG' => 'Emergency',
	                    'END' => 'Stop-end',
	                    'ENE' => 'East-north-east',
	                    'ENG' => 'Engine',
	                    'ENR' => 'En-route',
	                    'ENRC' => 'En-route Chart',
	                    'EOBT' => 'Estimated Off-block Time',
	                    'EQPT' => 'Equipment',
	                    'ER' => 'Here',
	                    'ESE' => 'East-south-east',
	                    'EST' => 'Estimate',
	                    'ETA' => 'Estimated Time Of Arrival',
	                    'ETD' => 'Estimated Time Of Departure',
	                    'ETO' => 'Estimated Time Over Significant Point',
	                    'EV' => 'Every',
	                    'EXC' => 'Except',
	                    'EXER' => 'Exercise',
	                    'EXP' => 'Expect',
	                    'EXTD' => 'Extend',
	                    'FAC' => 'Facilities',
	                    'FAF' => 'Final Approach Fix',
	                    'FAL' => 'Facilitation of International Airtransport',
	                    'FAP' => 'Final Approach Point',
	                    'FATO' => 'Final Approach And Take-off Area',
	                    'FAX' => 'Fax',
	                    'FBL' => 'Light',
	                    'FCST' => 'Forecast',
	                    'FCT' => 'Friction Coefficient',
	                    'FDPS' => 'Flight Data Processing System',
	                    'FEB' => 'February',
	                    'FIR' => 'Flight information region',
	                    'FIS' => 'Flight information service',
	                    'FLD' => 'Field',
	                    'FLG' => 'Flashing',
	                    'FLR' => 'Flares',
	                    'FLT' => 'Flight',
	                    'FLTS' => 'Flights',
	                    'FLTCK' => 'Flight Check',
	                    'FLUC' => 'Fluctuating',
	                    'FLW' => 'Follow(s)',
	                    'FLY' => 'Fly',
	                    'FM' => 'From',
	                    'FMS' => 'Flight Management System',
	                    'FMU' => 'Flow Management Unit',
	                    'FNA' => 'Final Approach',
	                    'FPAP' => 'Flight Path Alignment Point',
	                    'FPL' => 'Flight Plan',
	                    'FPLS' => 'Flight Plans',
	                    'FPM' => 'Feet Per Minute',
	                    'FPR' => 'Flight Plan Route',
	                    'FR' => 'Fuel Remaining',
	                    'FREQ' => 'Frequency',
	                    'FRI' => 'Friday',
	                    'FRMG' => 'Missile, gun or rocket firing',
	                    'FRNG' => 'Firing',
	                    'FRONT' => 'Front',
	                    'FRQ' => 'Frequent',
	                    'FSL' => 'Full Stop Landing',
	                    'FSS' => 'Flight Service Station',
	                    'FST' => 'First',
	                    'FTP' => 'Fictitious Threshold Point',
	                    'G/A' => 'Ground-to-air',
	                    'G/A/G' => 'Ground-to-air and Air-to-ground',
	                    'GARP' => 'GBAS Azimuth Reference Point',
	                    'GBAS' => 'Ground-based Augmentation System',
	                    'GCAJ' => 'Ground Controlled Approach',
	                    'GCA' => 'Ground Controlled Approach System',
	                    'GEN' => 'General',
	                    'GEO' => 'Geographic or True',
	                    'GES' => 'Ground Earth Station',
	                    'GLD' => 'Glider',
	                    'GMC' => 'Ground Movement Chart',
	                    'GND' => 'Ground',
	                    'GNDCK' => 'Ground Check',
	                    'GP' => 'Glide Path',
	                    'GRASS' => 'Grass landing area',
	                    'GRVL' => 'Gravel',
	                    'GUND' => 'Geoid Undulation',
	                    'H24' => '24 Hours',
	                    'HAPI' => 'Helicopter Approach Path Indicator',
	                    'HBN' => 'Hazard Beacon',
	                    'HDG' => 'Heading',
	                    'HEL' => 'Helicopter',
	                    'HGT' => 'Height',
	                    'HJ' => 'Sunrise to Sunset',
	                    'HLDG' => 'Holding',
	                    'HN' => 'Sunset to Sunrise',
	                    'HO' => 'Service Available To Meet Operational Requirements',
	                    'HOL' => 'Holiday',
	                    'HOSP' => 'Hospital Aircraft',
	                    'HOT' => 'Height',
	                    'HPA' => 'Hectopascal',
	                    'HR' => 'Hours',
	                    'HRS' => 'Hours',
	                    'HS' => 'Service Available During Hours Of Scheduled Operations',
	                    'HURCN' => 'Hurricane',
	                    'HVY' => 'Heavy',
	                    'HX' => 'No Specific Working Hours',
	                    'HYR' => 'Higher',
	                    'IAC' => 'Instrument Approach Chart',
	                    'IAF' => 'Initial Approach Fix',
	                    'IAO' => 'In And Out Of Clouds',
	                    'IAP' => 'Instrument Approach Procedure',
	                    'IAR' => 'Intersection Of Air Routes',
	                    'IBN' => 'Identification Beacon',
	                    'ID' => 'Identifier',
	                    'IDENT' => 'Identification',
	                    'IFF' => 'Identification Friend/Foe',
	                    'IGA' => 'International General Aviation',
	                    'IM' => 'Inner Marker',
	                    'IMPR' => 'Improving',
	                    'IMT' => 'Immediately',
	                    'INA' => 'Initial Approach',
	                    'INBD' => 'Inbound',
	                    'INCERFA' => 'Uncertainty Phase',
	                    'INFO' => 'Information',
	                    'INOP' => 'Inoperative',
	                    'INP' => 'If Not Possible',
	                    'INPR' => 'In Progress',
	                    'INSTL' => 'Installation',
	                    'INSTR' => 'Instrument',
	                    'INT' => 'Intersection',
	                    'INTS' => 'Intersections',
	                    'INTL' => 'International',
	                    'INTRG' => 'Interrogator',
	                    'INTRP' => 'Interruption',
	                    'INTSF' => 'Intensifying',
	                    'INTST' => 'Intensity',
	                    'ISA' => 'International Standard Atmosphere',
	                    'JAN' => 'January',
	                    'JTST' => 'Jet stream',
	                    'JUL' => 'July',
	                    'JUN' => 'June',
	                    'KMH' => 'Kilometres Per Hour',
	                    'KPA' => 'Kilopascal',
	                    'KT' => 'Knots',
	                    'KW' => 'Kilowatts',
	                    'LAN' => 'Inland',
	                    'LAT' => 'Latitude',
	                    'LDA' => 'Landing Distance Available',
	                    'LDAH' => 'Landing Distance Available, Helicopter',
	                    'LDG' => 'Landing',
	                    'LDI' => 'Landing Direction Indicator',
	                    'LEN' => 'Length',
	                    'LGT' => 'Lighting',
	                    'LGTD' => 'Lighted',
	                    'LIH' => 'Light Intensity High',
	                    'LIL' => 'Light Intensity Low',
	                    'LIM' => 'Light Intensity Medium',
	                    'LLZ' => 'Localizer',
	                    'LM' => 'Locator, Middle',
	                    'LMT' => 'Local Mean Time',
	                    'LNG' => 'Long',
	                    'LO' => 'Locator, Outer',
	                    'LOG' => 'Located',
	                    'LONG' => 'Longitude',
	                    'LRG' => 'Long Range',
	                    'LTD' => 'Limited',
	                    'LTP' => 'Landing Threshold Point',
	                    'LVE' => 'Leaving',
	                    'LVL' => 'Level',
	                    'LYR' => 'Layer',
	                    'MAA' => 'Maximum Authorized Altitude',
	                    'MAG' => 'Magnetic',
	                    'MAINT' => 'Maintenance',
	                    'MAP' => 'Aeronautical Maps and Charts',
	                    'MAPT' => 'Missed Approach Point',
	                    'MAR' => 'March',
	                    'MAX' => 'Maximum',
	                    'MAY' => 'May',
	                    'MBST' => 'Microburst',
	                    'MCA' => 'Minimum Crossing Altitude',
	                    'MCW' => 'Modulated Continuous Wave',
	                    'MDA' => 'Minimum Descent Altitude',
	                    'MDH' => 'Minimum Descent Height',
	                    'MEA' => 'Minimum En-route Altitude',
	                    'MEHT' => 'Minimum Eye Height Over Threshold',
	                    'MET' => 'Meteorological',
	                    'MID' => 'Mid-point',
	                    'MIL' => 'Military',
	                    'MIN' => 'Minutes',
	                    'MKR' => 'Marker Radio Beacon',
	                    'MLS' => 'Microwave Landing System',
	                    'MM' => 'Middle Marker',
	                    'MNM' => 'Minimum',
	                    'MNPS' => 'Minimum Navigation Performance Specifications',
	                    'MNT' => 'Monitor',
	                    'MNTN' => 'Maintain',
	                    'MOA' => 'Military Operating Area',
	                    'MOC' => 'Minimum Obstacle Clearance',
	                    'MOD' => 'Moderate',
	                    'MON' => 'Monday',
	                    'MOPS' => 'Minimum Operational Performance Standards',
	                    'MOV' => 'Movement',
	                    'MRA' => 'Minimum Reception Altitude',
	                    'MRG' => 'Medium Range',
	                    'MRP' => 'ATS/MET Reporting Point',
	                    'MS' => 'Minus',
	                    'MSA' => 'Minimum Sector Altitude',
	                    'MSAW' => 'Minimum Safe Altitude Warning',
	                    'MSG' => 'Message',
	                    'MSSR' => 'Monopulse Secondary Surveillance Radar',
	                    'MT' => 'Mountain',
	                    'MTU' => 'Metric Units',
	                    'MTW' => 'Mountain Waves',
	                    'NASC' => 'National AIS System Centre',
	                    'NAT' => 'North Atlantic',
	                    'NAV' => 'Navigation',
	                    'NB' => 'Northbound',
	                    'NBFR' => 'Not Before',
	                    'NE' => 'North-east',
	                    'NEB' => 'North-eastbound',
	                    'NEG' => 'Negative',
	                    'NGT' => 'Night',
	                    'NIL' => 'None',
	                    'NML' => 'Normal',
	                    'NNE' => 'North-north-east',
	                    'NNW' => 'North-north-west',
	                    'NOF' => 'International NOTAM Office',
	                    'NOV' => 'November',
	                    'NOZ' => 'Normal Operating Zone',
	                    'NR' => 'Number',
	                    'NRH' => 'No Reply Heard',
	                    'NTL' => 'National',
	                    'NTZ' => 'No Transgression Zone',
	                    'NW' => 'North-west',
	                    'NWB' => 'North-westbound',
	                    'NXT' => 'Next',
	                    'O/R' => 'On Request',
	                    'OAC' => 'Oceanic Area Control Centre',
	                    'OAS' => 'Obstacle Assessment Surface',
	                    'OBS' => 'Observe',
	                    'OBST' => 'Obstacle',
	                    'OBSTS' => 'Obstacles',
	                    'OCA' => 'Oceanic Control Area',
	                    'OCH' => 'Obstacle Clearance Height',
	                    'OCL' => 'Obstacle Clearance Limit',
	                    'OCS' => 'Obstacle Clearance Surface',
	                    'OCT' => 'October',
	                    'OFZ' => 'Obstacle Free Zone',
	                    'OGN' => 'Originate',
	                    'OHD' => 'Overhead',
	                    'OM' => 'Outer Marker',
	                    'OPC' => 'Control Indicated Is Operational Control',
	                    'OPMET' => 'Operational Meteorological',
	                    'OPN' => 'Open',
	                    'OPR' => 'Operate',
	                    'OPS' => 'Operations',
	                    'ORD' => 'Order',
	                    'OSV' => 'Ocean Station Vessel',
	                    'OTLK' => 'Outlook',
	                    'OTP' => 'On Top',
	                    'OTS' => 'Organized Track System',
	                    'OUBD' => 'Outbound',
	                    'PA' => 'Precision Approach',
	                    'PALS' => 'Precision Approach Lighting System',
	                    'PANS' => 'Procedures for Air Navigation Services',
	                    'PAR' => 'Precision Approach Radar',
	                    'PARL' => 'Parallel',
	                    'PATC' => 'Precision Approach Terrain Chart',
	                    'PAX' => 'Passenger(s)',
	                    'PCD' => 'Proceed',
	                    'PCL' => 'Pilot-controlled Lighting',
	                    'PCN' => 'Pavement Classification Number',
	                    'PDC' => 'Pre-departure Clearance',
	                    'PDG' => 'Procedure Design Gradient',
	                    'PER' => 'Performance',
	                    'PERM' => 'Permanent',
	                    'PIB' => 'Pre-flight Information Bulletin',
	                    'PJE' => 'Parachute Jumping Exercise',
	                    'PLA' => 'Practice Low Approach',
	                    'PLN' => 'Flight Plan',
	                    'PLVL' => 'Present Level',
	                    'PN' => 'Prior Notice Required',
	                    'PNR' => 'Point Of No Return',
	                    'POB' => 'Persons On Board',
	                    'POSS' => 'Possible',
	                    'PPI' => 'Plan Position Indicator',
	                    'PPR' => 'Prior Permission Required',
	                    'PPSN' => 'Present Position',
	                    'PRI' => 'Primary',
	                    'PRKG' => 'Parking',
	                    'PROB' => 'Probability',
	                    'PROC' => 'Procedure',
	                    'PROV' => 'Provisional',
	                    'PS' => 'Plus',
	                    'PSG' => 'Passing',
	                    'PSN' => 'Position',
	                    'PSNS' => 'Positions',
	                    'PSR' => 'Primary Surveillance Radar',
	                    'PSYS' => 'Pressure System(s)',
	                    'PTN' => 'Procedure Turn',
	                    'PTS' => 'Polar Track Structure',
	                    'PWR' => 'Power',
	                    'QUAD' => 'Quadrant',
	                    'RAC' => 'Rules of The Air and Air Traffic Services',
	                    'RAG' => 'Runway Arresting Gear',
	                    'RAI' => 'Runway Alignment Indicator',
	                    'RASC' => 'Regional AIS System Centre',
	                    'RASS' => 'Remote Altimeter Setting Source',
	                    'RB' => 'Rescue Boat',
	                    'RCA' => 'Reach Cruising Altitude',
	                    'RCC' => 'Rescue Coordination Centre',
	                    'RCF' => 'Radiocommunication Failure',
	                    'RCH' => 'Reaching',
	                    'RCL' => 'Runway Centre Line',
	                    'RCLL' => 'Runway Centre Line Light(s)',
	                    'RCLR' => 'Recleared',
	                    'RDH' => 'Reference Datum Height',
	                    'RDL' => 'Radial',
	                    'RDO' => 'Radio',
	                    'RE' => 'Recent',
	                    'REC' => 'Receiver',
	                    'REDL' => 'Runway Edge Light(s)',
	                    'REF' => 'Refer To',
	                    'REG' => 'Registration',
	                    'RENL' => 'Runway End Light(s)',
	                    'REP' => 'Report',
	                    'REQ' => 'Requested',
	                    'RERTE' => 'Re-route',
	                    'RESA' => 'Runway End Safety Area',
	                    'RG' => 'Range (lights)',
	                    'RHC' => 'Right-hand Circuit',
	                    'RIF' => 'Reclearance In Flight',
	                    'RITE' => 'Right',
	                    'RL' => 'Report Leaving',
	                    'RLA' => 'Relay To',
	                    'RLCE' => 'Request Level Change En Route',
	                    'RLLS' => 'Runway Lead-in Lighting System',
	                    'RLNA' => 'Request Level Not Available',
	                    'RMAC' => 'Radar Minimum Altitude Chart',
	                    'RMK' => 'Remark',
	                    'RNG' => 'Radio Range',
	                    'RNP' => 'Required Navigation Performance',
	                    'ROC' => 'Rate Of Climb',
	                    'ROD' => 'Rate Of Descent',
	                    'ROFOR' => 'Route Forecast',
	                    'RON' => 'Receiving Only',
	                    'RPI' => 'Radar Position Indicator',
	                    'RPL' => 'Repetitive Flight Plan',
	                    'RPLC' => 'Replaced',
	                    'RPS' => 'Radar Position Symbol',
	                    'RQMNTS' => 'Requirements',
	                    'RQP' => 'Request Flight Plan',
	                    'RQS' => 'Request Supplementary Flight Plan',
	                    'RR' => 'Report Reaching',
	                    'RSC' => 'Rescue Sub-centre',
	                    'RSCD' => 'Runway Surface Condition',
	                    'RSP' => 'Responder Beacon',
	                    'RSR' => 'En-route Surveillance Radar',
	                    'RTE' => 'Route',
	                    'RTES' => 'Routes',
	                    'RTF' => 'Radiotelephone',
	                    'RTG' => 'Radiotelegraph',
	                    'RTHL' => 'Runway Threshold Light(s)',
	                    'RTN' => 'Return',
	                    'RTODAH' => 'Rejected Take-off Distance Available, Helicopter',
	                    'RTS' => 'Return To Service',
	                    'RTT' => 'Radioteletypewriter',
	                    'RTZL' => 'Runway Touchdown Zone Light(s)',
	                    'RUT' => 'Standard Regional Route Transmitting Frequencies',
	                    'RV' => 'Rescue Vessel',
	                    'RVSM' => 'Reduced Vertical Separation Minimum',
	                    'RWY' => 'Runway',
	                    'RWYS' => 'Runways',
	                    'SALS' => 'Simple Approach Lighting System',
	                    'SAN' => 'Sanitary',
	                    'SAP' => 'As Soon As Possible',
	                    'SAR' => 'Search and Rescue',
	                    'SARPS' => 'Standards and Recommended Practices',
	                    'SAT' => 'Saturday',
	                    'SATCOM' => 'Satellite Communication',
	                    'SB' => 'Southbound',
	                    'SBAS' => 'Satellite-based Augmentation System',
	                    'SDBY' => 'Stand by',
	                    'SE' => 'South-east',
	                    'SEA' => 'Sea',
	                    'SEB' => 'South-eastbound',
	                    'SEC' => 'Seconds',
	                    'SECN' => 'Section',
	                    'SECT' => 'Sector',
	                    'SELCAL' => 'Selective calling system',
	                    'SEP' => 'September',
	                    'SER' => 'Service',
	                    'SEV' => 'Severe',
	                    'SFC' => 'Surface',
	                    'SGL' => 'Signal',
	                    'SID' => 'Standard Instrument Departure',
	                    'SIF' => 'Selective Identification Feature',
	                    'SIG' => 'Significant',
	                    'SIMUL' => 'Simultaneous',
	                    'SKED' => 'Schedule',
	                    'SLP' => 'Speed Limiting Point',
	                    'SLW' => 'Slow',
	                    'SMC' => 'Surface Movement Control',
	                    'SMR' => 'Surface Movement Radar',
	                    'SPL' => 'Supplementary Flight Plan',
	                    'SPOC' => 'SAR Point Of Contact',
	                    'SPOT' => 'Spot Wind',
	                    'SR' => 'Sunrise',
	                    'SRA' => 'Surveillance Radar Approach',
	                    'SRE' => 'Surveillance Radar Element Of Precision Approach Radar System',
	                    'SRG' => 'Short Range',
	                    'SRR' => 'Search and Rescue Region',
	                    'SRY' => 'Secondary',
	                    'SS' => 'Sunset',
	                    'SSE' => 'South-south-east',
	                    'SSR' => 'Secondary Surveillance Radar',
	                    'SST' => 'Supersonic Transport',
	                    'SSW' => 'South-south-west',
	                    'STA' => 'Straight-in Approach',
	                    'STAR' => 'Standard Instrument Arrival',
	                    'STD' => 'Standard',
	                    'STN' => 'Station',
	                    'STNR' => 'Stationary',
	                    'STOL' => 'Short Take-off and Landing',
	                    'STS' => 'Status',
	                    'STWL' => 'Stopway Light(s)',
	                    'SUBJ' => 'Subject To',
	                    'SUN' => 'Sunday',
	                    'SUP' => 'Supplement',
	                    'SUPPS' => 'Regional Supplementary Procedures Service Message',
	                    'SVCBL' => 'Serviceable',
	                    'SW' => 'South-west',
	                    'SWB' => 'South-westbound',
	                    'SWY' => 'Stopway',
	                    'TA' => 'Transition Altitude',
	                    'TAA' => 'Terminal Arrival Altitude',
	                    'TAF' => 'Aerodrome Forecast',
	                    'TAIL' => 'Tail Wind',
	                    'TAR' => 'Terminal Area Surveillance Radar',
	                    'TAX' => 'Taxi',
	                    'TCAC' => 'Tropical Cyclone Advisory Centre',
	                    'TDO' => 'Tornado',
	                    'TDZ' => 'Touchdown Zone',
	                    'TECR' => 'Technical Reason',
	                    'TEMPO' => 'Temporarily',
	                    'TFC' => 'Traffic',
	                    'TGL' => 'Touch-and-go',
	                    'TGS' => 'Taxiing Guidance System',
	                    'THR' => 'Threshold',
	                    'THRU' => 'Through',
	                    'THU' => 'Thursday',
	                    'TIBA' => 'Traffic Information Broadcast By Aircraft',
	                    'TIL' => 'Until',
	                    'TIP' => 'Until Past',
	                    'TKOF' => 'Take-off',
	                    'TL' => 'Till',
	                    'TLOF' => 'Touchdown And Lift-off Area',
	                    'TMA' => 'Terminal Control Area',
	                    'TNA' => 'Turn Altitude',
	                    'TNH' => 'Turn Height',
	                    'TOC' => 'Top of Climb',
	                    'TODA' => 'Take-off Distance Available',
	                    'TODAH' => 'Take-off Distance Available, Helicopter',
	                    'TORA' => 'Take-off Run Available',
	                    'TP' => 'Turning Point',
	                    'TR' => 'Track',
	                    'TRA' => 'Temporary Reserved Airspace',
	                    'TRANS' => 'Transmitter',
	                    'TRL' => 'Transition Level',
	                    'TUE' => 'Tuesday',
	                    'TURB' => 'Turbulence',
	                    'TVOR' => 'Terminal VOR',
	                    'TWR' => 'Tower',
	                    'TWY' => 'Taxiway',
	                    'TWYL' => 'Taxiway-link',
	                    'TXT' => 'Text',
	                    'TYP' => 'Type of Aircraft',
	                    'U/S' => 'Unserviceable',
	                    'UAB' => 'Until Advised By',
	                    'UAC' => 'Upper Area Control Centre',
	                    'UAR' => 'Upper Air Route',
	                    'UDA' => 'Upper advisory area',
	                    'UFN' => 'Until Further Notice',
	                    'UHDT' => 'Unable Higher Due Traffic',
	                    'UIC' => 'Upper Information Centre',
	                    'UIR' => 'Upper Flight Information Region',
	                    'ULR' => 'Ultra Long Range',
	                    'UNA' => 'Unable',
	                    'UNAP' => 'Unable To Approve',
	                    'UNL' => 'Unlimited',
	                    'UNREL' => 'Unreliable',
	                    'UTA' => 'Upper Control Area',
	                    'VAAC' => 'Volcanic Ash Advisory Centre',
	                    'VAC' => 'Visual Approach Chart',
	                    'VAL' => 'In Valleys',
	                    'VAN' => 'Runway Control Van',
	                    'VAR' => 'Visual-aural Radio Range',
	                    'VC' => 'Vicinity',
	                    'VCY' => 'Vicinity',
	                    'VER' => 'Vertical',
	                    'VIS' => 'Visibility',
	                    'VLR' => 'Very Long Range',
	                    'VPA' => 'Vertical Path Angle',
	                    'VRB' => 'Variable',
	                    'VSA' => 'By Visual Reference To The Ground',
	                    'VSP' => 'Vertical Speed',
	                    'VTOL' => 'Vertical Take-off And Landing',
	                    'WAC' => 'World Aeronautical Chart',
	                    'WAFC' => 'World Area Forecast Centre',
	                    'WB' => 'Westbound',
	                    'WBAR' => 'Wing Bar Lights',
	                    'WDI' => 'Wind Direction Indicator',
	                    'WDSPR' => 'Widespread',
	                    'WED' => 'Wednesday',
	                    'WEF' => 'Effective From',
	                    'WI' => 'Within',
	                    'WID' => 'Width',
	                    'WIE' => 'Effective Immediately',
	                    'WILCO' => 'Will Comply',
	                    'WIND' => 'Wind',
	                    'WINTEM' => 'Forecast Upper Wind And Temperature For Aviation',
	                    'WIP' => 'Work In Progress',
	                    'WKN' => 'Weaken',
	                    'WNW' => 'West-north-west',
	                    'WO' => 'Without',
	                    'WPT' => 'Way-point',
	                    'WRNG' => 'Warning',
	                    'WSW' => 'West-south-west',
	                    'WT' => 'Weight',
	                    'WWW' => 'Worldwide Web',
	                    'WX' => 'Weather',
	                    'XBAR' => 'Crossbar',
	                    'XNG' => 'Crossing',
	                    'XS' => 'Atmospherics',
	                    'YCZ' => 'Yellow Caution Zone',
	                    'YR' => 'Your');
	public $code_airspace = array(
			    'AA' => 'Minimum altitude',
			    'AC' => 'Class B, C, D, or E Surface Area',
			    'AD' => 'Air defense identification zone',
			    'AE' => 'Control area',
			    'AF' => 'Flight information region',
			    'AH' => 'Upper control area',
			    'AL' => 'Minimum usable flight level',
			    'AN' => 'Area navigation route',
			    'AO' => 'Oceanic control area',
			    'AP' => 'Reporting point',
			    'AR' => 'ATS route',
			    'AT' => 'Class B Airspace',
			    'AU' => 'Upper flight information region',
			    'AV' => 'Upper advisory area',
			    'AX' => 'Intersection',
			    'AZ' => 'Aerodrome traffic zone');
	public $code_comradar = array(
			    'CA' => 'Air/ground',
			    'CE' => 'En route surveillance radar',
			    'CG' => 'Ground controlled approach system',
			    'CL' => 'Selective calling system',
			    'CM' => 'Surface movement radar',
			    'CP' => 'Precision approach radar',
			    'CR' => 'Surveillance radar element of precision approach radar system',
			    'CS' => 'Secondary surveillance radar',
			    'CT' => 'Terminal area surveillance radar');
	public $code_facilities = array(
			    'FA' => 'airport',
			    'FB' => 'Braking action measurement equipment',
			    'FC' => 'Ceiling measurement equipment',
			    'FD' => 'Docking system',
			    'FF' => 'Fire fighting and rescue',
			    'FG' => 'Ground movement control',
			    'FH' => 'Helicopter alighting area/platform',
			    'FL' => 'Landing direction indicator',
			    'FM' => 'Meteorological service',
			    'FO' => 'Fog dispersal system',
			    'FP' => 'Heliport',
			    'FS' => 'Snow removal equipment',
			    'FT' => 'Transmissometer',
			    'FU' => 'Fuel availability',
			    'FW' => 'Wind direction indicator',
			    'FZ' => 'Customs');
	public $code_instrumentlanding = array(
			    'ID' => 'DME associated with ILS',
			    'IG' => 'Glide path',
			    'II' => 'Inner marker',
			    'IL' => 'Localizer',
			    'IM' => 'Middle marker',
			    'IO' => 'Outer marker',
			    'IS' => 'ILS Category I',
			    'IT' => 'ILS Category II',
			    'IU' => 'ILS Category III',
			    'IW' => 'Microwave landing system',
			    'IX' => 'Locator, outer',
			    'IY' => 'Locator, middle');
	public $code_lightingfacilities = array(
			    'LA' => 'Approach lighting system',
			    'LB' => 'Airport beacon',
			    'LC' => 'Runway center line lights',
			    'LD' => 'Landing direction indicator lights',
			    'LE' => 'Runway edge lights',
			    'LF' => 'Sequenced flashing lights',
			    'LH' => 'High intensity runway lights',
			    'LI' => 'Runway end identifier lights',
			    'LK' => 'Category II components of approach lighting system',
			    'LL' => 'Low intensity runway lights',
			    'LM' => 'Medium intensity runway lights',
			    'LP' => 'Precision approach path indicator',
			    'LR' => 'All landing area lighting facilities',
			    'LS' => 'Stopway lights',
			    'LT' => 'Threshold lights',
			    'LV' => 'Visual approach slope indicator system',
			    'LW' => 'Heliport lighting',
			    'LX' => 'Taxiway center line lights',
			    'LY' => 'Taxiway edge lights',
			    'LZ' => 'Runway touchdown zone lights');
	public $code_movementareas = array(
			    'MA' => 'Movement area',
			    'MB' => 'Bearing strength',
			    'MC' => 'Clearway',
			    'MD' => 'Declared distances',
			    'MG' => 'Taxiing guidance system',
			    'MH' => 'Runway arresting gear',
			    'MK' => 'Parking area',
			    'MM' => 'Daylight markings',
			    'MN' => 'Apron',
			    'MP' => 'Aircraft stands',
			    'MR' => 'Runway',
			    'MS' => 'Stopway',
			    'MT' => 'Threshold',
			    'MU' => 'Runway turning bay',
			    'MW' => 'Strip',
			    'MX' => 'Taxiway');
	public $code_terminalfacilities = array(
			    'NA' => 'All radio navigation facilities',
			    'NB' => 'Non directional beacon',
			    'NC' => 'DECCA',
			    'ND' => 'Distance measuring equipment',
			    'NF' => 'Fan marker',
			    'NL' => 'Locator',
			    'NM' => 'VOR/DME',
			    'NN' => 'TACAN',
			    'NO' => 'OMEGA',
			    'NT' => 'VORTAC',
			    'NV' => 'VOR',
			    'NX' => 'Direction finding station');
	public $code_information = array(
			    'OA' => 'Aeronautical information service',
			    'OB' => 'Obstacle',
			    'OE' => 'Aircraft entry requirements',
			    'OL' => 'Obstacle lights on',
			    'OR' => 'Rescue coordination centre');
	public $code_airtraffic = array(
			    'PA' => 'Standard instrument arrival',
			    'PD' => 'Standard instrument departure',
			    'PF' => 'Flow control procedure',
			    'PH' => 'Holding procedure',
			    'PI' => 'Instrument approach procedure',
			    'PL' => 'Obstacle clearance limit',
			    'PM' => 'Aerodrome operating minima',
			    'PO' => 'Obstacle clearance altitude',
			    'PP' => 'Obstacle clearance height',
			    'PR' => 'Radio failure procedure',
			    'PT' => 'Transition altitude',
			    'PU' => 'Missed approach procedure',
			    'PX' => 'Minimum holding altitude',
			    'PZ' => 'ADIZ procedure');
	public $code_navigationw = array(
			    'RA' => 'Airspace reservation',
			    'RD' => 'Danger area',
			    'RO' => 'Overflying of',
			    'RP' => 'Prohibited area',
			    'RR' => 'Restricted area',
			    'RT' => 'Temporary restricted area');
	public $code_volmet = array(
			    'SA' => 'Automatic terminal information service',
			    'SB' => 'ATS reporting office',
			    'SC' => 'Area control center',
			    'SE' => 'Flight information service',
			    'SF' => 'Airport flight information service',
			    'SL' => 'Flow control centre',
			    'SO' => 'Oceanic area control centre',
			    'SP' => 'Approach control service',
			    'SS' => 'Flight service station',
			    'ST' => 'Airport control tower',
			    'SU' => 'Upper area control centre',
			    'SV' => 'VOLMET broadcast',
			    'SY' => 'Upper advisory service');
	public $code_warnings = array(
			    'WA' => 'Air display',
			    'WB' => 'Aerobatics',
			    'WC' => 'Captive balloon or kite',
			    'WD' => 'Demolition of explosives',
			    'WE' => 'Exercises',
			    'WF' => 'Air refueling',
			    'WG' => 'Glider flying',
			    'WJ' => 'Banner/target towing',
			    'WL' => 'Ascent of free balloon',
			    'WM' => 'Missile, gun or rocket firing',
			    'WP' => 'Parachute jumping exercise',
			    'WS' => 'Burning or blowing gas',
			    'WT' => 'Mass movement of aircraft',
			    'WV' => 'Formation flight',
			    'WZ' => 'model flying');
	public $code_sp_availabity = array(
			    'AC' => 'Withdrawn for maintenance',
			    'AD' => 'Available for daylight operation',
			    'AF' => 'Flight checked and found reliable',
			    'AG' => 'Operating but ground checked only, awaiting flight check',
			    'AH' => 'Hours of service are now',
			    'AK' => 'Resumed normal operations',
			    'AM' => 'Military operations only',
			    'AN' => 'Available for night operation',
			    'AO' => 'Operational',
			    'AP' => 'Available, prior permission required',
			    'AR' => 'Available on request',
			    'AS' => 'Unserviceable',
			    'AU' => 'Not available',
			    'AW' => 'Completely withdrawn',
			    'AX' => 'Previously promulgated shutdown has been cancelled');
	public $code_sp_changes = array(
			    'CA' => 'Activated',
			    'CC' => 'Completed',
			    'CD' => 'Deactivated',
			    'CE' => 'Erected',
			    'CF' => 'Operating frequency(ies) changed to',
			    'CG' => 'Downgraded to',
			    'CH' => 'Changed',
			    'CI' => 'dentification or radio call sign changed to',
			    'CL' => 'Realigned',
			    'CM' => 'Displaced',
			    'CO' => 'Operating',
			    'CP' => 'Operating on reduced power',
			    'CR' => 'Temporarily replaced by',
			    'CS' => 'Installed',
			    'CT' => 'On test, do not use');
	public $code_sp_hazardous = array(
			    'HA' => 'Braking action is',
			    'HB' => 'Braking coefficient is',
			    'HC' => 'Covered by compacted snow to depth of x Ft',
			    'HD' => 'Covered by dry snow to a depth of x Ft',
			    'HE' => 'Covered by water to a depth of x Ft',
			    'HF' => 'Totally free of snow and ice',
			    'HG' => 'Grass cutting in progress',
			    'HH' => 'Hazard due to',
			    'HI' => 'Covered by ice',
			    'HJ' => 'Launch planned',
			    'HK' => 'Migration in progress',
			    'HL' => 'Snow clearance completed',
			    'HM' => 'Marked by',
			    'HN' => 'Covered by wet snow or slush to a depth of x Ft',
			    'HO' => 'Obscured by snow',
			    'HP' => 'Snow clearance in progress',
			    'HQ' => 'Operation cancelled',
			    'HR' => 'Standing water',
			    'HS' => 'Sanding in progress',
			    'HT' => 'Approach according to signal area only',
			    'HU' => 'Launch in progress',
			    'HV' => 'Work completed',
			    'HW' => 'Work in progress',
			    'HX' => 'Concentration of birds',
			    'HY' => 'Snow banks exist',
			    'HZ' => 'Covered by frozen ruts and ridges');
	public $code_sp_limitations = array(
			    'LA' => 'Operating on Auxiliary Power Supply',
			    'LB' => 'Reserved for aircraft based therein',
			    'LC' => 'Closed',
			    'LD' => 'Unsafe',
			    'LE' => 'Operated without auxiliary power supply',
			    'LF' => 'Interference from',
			    'LG' => 'Operating without identification',
			    'LH' => 'Unserviceable for aircraft heavier than',
			    'LI' => 'Close to IFR operations',
			    'LK' => 'Operating as a fixed light',
			    'LL' => 'Usable for lenght of... and width of...',
			    'LN' => 'Close to all night operations',
			    'LP' => 'Prohibited to',
			    'LR' => 'Aircraft restricted to runways and taxiways',
			    'LS' => 'Subject to interruption',
			    'LT' => 'Limited to',
			    'LV' => 'Close to VFR operations',
			    'LW' => 'Will take place',
			    'LX' => 'Operating but caution advised to'); 

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
	}
	public function getAllNOTAM() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT * FROM notam WHERE radius > 0 AND date_end > UTC_TIMESTAMP() AND date_begin < UTC_TIMESTAMP()';
		} else {
			$query  = "SELECT * FROM notam WHERE radius > 0 AND date_end > CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND date_begin < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllNOTAMbyFir($fir) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT * FROM notam WHERE date_end > UTC_TIMESTAMP() AND date_begin < UTC_TIMESTAMP() AND fir = :fir ORDER BY date_begin DESC';
		} else {
			$query  = "SELECT * FROM notam WHERE fir = :fir AND date_end > CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND date_begin < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' ORDER BY date_begin DESC";
		}
		$query_values = array(':fir' => $fir);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllNOTAMtext() {
		$query  = 'SELECT full_notam FROM notam';
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function createNOTAMtextFile($filename) {
		$allnotam_result = $this->getAllNOTAMtext();
		$notamtext = '';
		foreach($allnotam_result as $notam) {
			$notamtext .= '%%'."\n";
			$notamtext .= $notam['full_notam'];
			$notamtext .= "\n".'%%'."\n";
		}
		file_put_contents($filename,$notamtext);
	}
	public function parseNOTAMtextFile($filename) {
		$data = file_get_contents($filename);
		preg_match_all("/%%(.+?)%%/is", $data, $matches);
		if (isset($matches[1])) return $matches[1];
		else return array();
	}
	public function getAllNOTAMbyScope($scope) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT * FROM notam WHERE radius > 0 AND date_end > UTC_TIMESTAMP() AND date_begin < UTC_TIMESTAMP() AND scope = :scope';
		} else {
			$query  = "SELECT * FROM notam WHERE radius > 0 AND date_end > CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND date_begin < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND scope = :scope";
		}
		$query_values = array(':scope' => $scope);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllNOTAMbyCoord($coord) {
		global $globalDBdriver;
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			if ($minlat > $maxlat) {
				$tmplat = $minlat;
				$minlat = $maxlat;
				$maxlat = $tmplat;
			}
			if ($minlong > $maxlong) {
				$tmplong = $minlong;
				$minlong = $maxlong;
				$maxlong = $tmplong;
			}
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT * FROM notam WHERE center_latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND center_longitude BETWEEN '.$minlong.' AND '.$maxlong.' AND radius > 0 AND date_end > UTC_TIMESTAMP() AND date_begin < UTC_TIMESTAMP()';
		} else {
			$query  = 'SELECT * FROM notam WHERE center_latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND center_longitude BETWEEN '.$minlong.' AND '.$maxlong." AND radius > 0 AND date_end > CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND date_begin < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllNOTAMbyCoordScope($coord,$scope) {
		global $globalDBdriver;
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT * FROM notam WHERE center_latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND center_longitude BETWEEN '.$minlong.' AND '.$maxlong.' AND radius > 0 AND date_end > UTC_TIMESTAMP() AND date_begin < UTC_TIMESTAMP() AND scope = :scope';
		} else {
			$query  = 'SELECT * FROM notam WHERE center_latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND center_longitude BETWEEN '.$minlong.' AND '.$maxlong." AND radius > 0 AND date_end > CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND date_begin < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' AND scope = :scope";
		}
		$query_values = array(':scope' => $scope);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getNOTAMbyRef($ref) {
		$query = "SELECT * FROM notam WHERE ref = :ref LIMIT 1";
		$query_values = array('ref' => $ref);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($all[0])) return $all[0];
		else return array();
	}

	public function addNOTAM($ref,$title,$type,$fir,$code,$rules,$scope,$lower_limit,$upper_limit,$center_latitude,$center_longitude,$radius,$date_begin,$date_end,$permanent,$text,$full_notam) {
		$query = "INSERT INTO notam (ref,title,notam_type,fir,code,rules,scope,lower_limit,upper_limit,center_latitude,center_longitude,radius,date_begin,date_end,permanent,notam_text,full_notam) VALUES (:ref,:title,:type,:fir,:code,:rules,:scope,:lower_limit,:upper_limit,:center_latitude,:center_longitude,:radius,:date_begin,:date_end,:permanent,:text,:full_notam)";
		$query_values = array(':ref' => $ref,':title' => $title,':type' => $type,':fir' => $fir,':code' => $code,':rules' => $rules,':scope' => $scope,':lower_limit' => $lower_limit,':upper_limit' => $upper_limit,':center_latitude' => $center_latitude,':center_longitude' => $center_longitude,':radius' => $radius,':date_begin' => $date_begin,':date_end' => $date_end,':permanent' => $permanent,':text' => $text,':full_notam' => $full_notam);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function deleteNOTAM($id) {
		$query = "DELETE FROM notam WHERE id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}
	public function deleteOldNOTAM() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "DELETE FROM notam WHERE date_end < UTC_TIMESTAMP()";
		} else {
			$query = "DELETE FROM notam WHERE date_end < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}
	public function deleteNOTAMbyRef($ref) {
		$query = "DELETE FROM notam WHERE ref = :ref";
		$query_values = array(':ref' => $ref);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}
	public function deleteAllNOTAM() {
		$query = "DELETE FROM notam";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}
	public function deleteAllNOTAMLocation() {
		$query = "DELETE FROM notam";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function updateNOTAM() {
		global $globalNOTAMAirports;
		if (isset($globalNOTAMAirports) && is_array($globalNOTAMAirports) && count($globalNOTAMAirports) > 0) {
			foreach (array_chunk($globalNOTAMAirports,10) as $airport) {
				$airport_icao = implode(',',$airport);
				$alldata = $this->downloadNOTAM($airport_icao);
				if (count($alldata) > 0) {
					foreach ($alldata as $initial_data) {
						$data = $this->parse($initial_data);
						$notamref = $this->getNOTAMbyRef($data['ref']);
						if (count($notamref) == 0) $this->addNOTAM($data['ref'],$data['title'],'',$data['fir'],$data['code'],'',$data['scope'],$data['lower_limit'],$data['upper_limit'],$data['latitude'],$data['longitude'],$data['radius'],$data['date_begin'],$data['date_end'],$data['permanent'],$data['text'],$data['full_notam']);
					}
				}
			}
		}
	}
	public function updateNOTAMfromTextFile($filename) {
		global $globalTransaction, $globalDebug;
		$alldata = $this->parseNOTAMtextFile($filename);
		if (count($alldata) > 0) {
			$this->deleteOldNOTAM();
			if ($globalTransaction) $this->db->beginTransaction();
			$j = 0;
			foreach ($alldata as $initial_data) {
				$j++;
				$data = $this->parse($initial_data);
				$notamref = $this->getNOTAMbyRef($data['ref']);
				if (!isset($notamref['notam_id'])) $this->addNOTAM($data['ref'],$data['title'],'',$data['fir'],$data['code'],'',$data['scope'],$data['lower_limit'],$data['upper_limit'],$data['latitude'],$data['longitude'],$data['radius'],$data['date_begin'],$data['date_end'],$data['permanent'],$data['text'],$data['full_notam']);
				if ($globalTransaction && $j % 1000 == 0) {
					$this->db->commit();
					if ($globalDebug) echo '.';
					$this->db->beginTransaction();
				}
			}
			if ($globalTransaction) $this->db->commit();
		}
	}

	public function updateNOTAMallAirports() {
		global $globalTransaction;
		$Spotter = new Spotter($this->db);
		$allairports = $Spotter->getAllAirportInfo();
		foreach (array_chunk($allairports,20) as $airport) {
			$airports_icao = array();
			foreach($airport as $icao) {
				if (isset($icao['icao'])) $airports_icao[] = $icao['icao'];
			}
			$airport_icao = implode(',',$airports_icao);
			$alldata = $this->downloadNOTAM($airport_icao);
			if ($globalTransaction) $this->db->beginTransaction();
			if (count($alldata) > 0) {
				foreach ($alldata as $initial_data) {
					//print_r($initial_data);
					$data = $this->parse($initial_data);
					//print_r($data);
					if (isset($data['ref'])) {
						$notamref = $this->getNOTAMbyRef($data['ref']);
						if (count($notamref) == 0) {
							if (isset($data['ref_replaced'])) $this->deleteNOTAMbyRef($data['ref_replaced']);
							if (isset($data['ref_cancelled'])) $this->deleteNOTAMbyRef($data['ref_cancelled']);
							elseif (isset($data['latitude']) && isset($data['scope']) && isset($data['text']) && isset($data['permanent'])) echo $this->addNOTAM($data['ref'],'','',$data['fir'],$data['code'],'',$data['scope'],$data['lower_limit'],$data['upper_limit'],$data['latitude'],$data['longitude'],$data['radius'],$data['date_begin'],$data['date_end'],$data['permanent'],$data['text'],$data['full_notam']);
						}
					}
				}
			} else echo 'Error on download. Nothing matches for '.$airport_icao."\n";
			if ($globalTransaction) $this->db->commit();
			sleep(5);
		}
	}

	public function downloadNOTAM($icao) {
		date_default_timezone_set("UTC");
		$Common = new Common();
		//$url = str_replace('{icao}',$icao,'https://pilotweb.nas.faa.gov/PilotWeb/notamRetrievalByICAOAction.do?method=displayByICAOs&reportType=RAW&formatType=DOMESTIC&retrieveLocId={icao}&actionType=notamRetrievalByICAOs');
		$url = str_replace('{icao}',$icao,'https://pilotweb.nas.faa.gov/PilotWeb/notamRetrievalByICAOAction.do?method=displayByICAOs&reportType=RAW&formatType=ICAO&retrieveLocId={icao}&actionType=notamRetrievalByICAOs');
		$data = $Common->getData($url);
		preg_match_all("/<pre>(.+?)<\/pre>/is", $data, $matches);
		//print_r($matches);
		if (isset($matches[1])) return $matches[1];
		else return array();
	}

	public function parse($data) {
		global $globalDebug;
		$Common = new Common();
		$result = array();
		$result['full_notam'] = $data;
		$result['text'] = '';
		$result['permanent'] = '';
		$result['date_begin'] = NULL;
		$result['date_end'] = NULL;
		$data = str_ireplace(array("\r","\n",'\r','\n'),' ',$data);
		$data = preg_split('#\s(?=([A-Z]\)\s))#',$data);
		$q = false;
		$a = false;
		$b = false;
		$c = false;
		$e = false;
		foreach ($data as $line) {
			$line = trim($line);
			if (preg_match('#(^|\s)Q\) (.*)#',$line,$matches) && $q === false) {
				$line = str_replace(' ','',$line);
				if (preg_match('#Q\)([A-Z]{3,4})\/([A-Z]{5})\/(IV|I|V)\/([A-Z]{1,3})\/([A-Z]{1,2})\/([0-9]{3})\/([0-9]{3})\/([0-9]{4})(N|S)([0-9]{5})(E|W)([0-9]{3}|)#',$line,$matches)) {
				//if (preg_match('#Q\)([A-Z]{4})\/([A-Z]{5})\/(IV|I|V)\/([A-Z]{1,3})\/([A-Z]{1,2})\/([0-9]{3})\/([0-9]{3})\/([0-9]{4})(N|S)([0-9]{5})(E|W)([0-9]{3})#',$line,$matches)) {
					$result['fir'] = $matches[1];
					$result['code'] = $matches[2];
					$result['title'] = $this->parse_code($result['code']);
					$rules = str_split($matches[3]);
					foreach ($rules as $rule) {
						if ($rule == 'I') {
							if (isset($result['rules'])) $result['rules'] = $result['rules'].'/IFR';
							else $result['rules'] = 'IFR';
						} elseif ($rule == 'V') {
							if (isset($result['rules'])) $result['rules'] = $result['rules'].'/VFR';
							else $result['rules'] = 'VFR';
						} elseif ($rule == 'K') {
							if (isset($result['rules'])) $result['rules'] = $result['rules'].'/Checklist';
							else $result['rules'] = 'Checklist';
						}
					}
					$attentions = str_split($matches[4]);
					foreach ($attentions as $attention) {
						if ($attention == 'N') {
							if (isset($result['attention'])) $result['attention'] = $result['attention'].' / Immediate attention';
							else $result['rules'] = 'Immediate attention';
						} elseif ($attention == 'B') {
							if (isset($result['attention'])) $result['attention'] = $result['attention'].' / Operational significance';
							else $result['rules'] = 'Operational significance';
						} elseif ($attention == 'O') {
							if (isset($result['attention'])) $result['attention'] = $result['attention'].' / Flight operations';
							else $result['rules'] = 'Flight operations';
						} elseif ($attention == 'M') {
							if (isset($result['attention'])) $result['attention'] = $result['attention'].' / Misc';
							else $result['rules'] = 'Misc';
						} elseif ($attention == 'K') {
							if (isset($result['attention'])) $result['attention'] = $result['attention'].' / Checklist';
							else $result['rules'] = 'Checklist';
						}
					}
					if ($matches[5] == 'A') $result['scope'] = 'Airport warning';
					elseif ($matches[5] == 'E') $result['scope'] = 'Enroute warning';
					elseif ($matches[5] == 'W') $result['scope'] = 'Navigation warning';
					elseif ($matches[5] == 'K') $result['scope'] = 'Checklist';
					elseif ($matches[5] == 'AE') $result['scope'] = 'Airport/Enroute warning';
					elseif ($matches[5] == 'AW') $result['scope'] = 'Airport/Navigation warning';
					$result['lower_limit'] = $matches[6];
					$result['upper_limit'] = $matches[7];
					$latitude = $Common->convertDec($matches[8],'latitude');
					if ($matches[9] == 'S') $latitude = -$latitude;
					$longitude = $Common->convertDec($matches[10],'longitude');
					if ($matches[11] == 'W') $longitude = -$longitude;
					$result['latitude'] = $latitude;
					$result['longitude'] = $longitude;
					if ($matches[12] != '') $result['radius'] = intval($matches[12]);
					else $result['radius'] = 0;
					$q = true;
				} elseif ($globalDebug) {
					echo 'NOTAM error : '.$result['full_notam']."\n";
					echo "Can't parse : ".$line."\n";
				}
			}
			elseif (preg_match('#(^|\s)A\) (.*)#',$line,$matches) && $a === false) {
				$result['icao'] = $matches[2];
				$a = true;
			}
			elseif (preg_match('#(^|\s)B\) ([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})#',$line,$matches) && $b === false) {
				if ($matches[1] > 50) $year = '19'.$matches[2];
				else $year = '20'.$matches[2];
				$result['date_begin'] = $year.'/'.$matches[3].'/'.$matches[4].' '.$matches[5].':'.$matches[6];
				$b = true;
			}
			elseif (preg_match('#(^|\s)C\) ([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#',$line,$matches) && $c === false) {
				if ($matches[2] > 50) $year = '19'.$matches[2];
				else $year = '20'.$matches[2];
				$result['date_end'] = $year.'/'.$matches[3].'/'.$matches[4].' '.$matches[5].':'.$matches[6];
				$result['permanent'] = 0;
				$c = true;
			}
			elseif (preg_match('#(^|\s)C\) ([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2}) (EST|PERM)$#',$line,$matches) && $c === false) {
				if ($matches[2] > 50) $year = '19'.$matches[2];
				else $year = '20'.$matches[2];
				$result['date_end'] = $year.'/'.$matches[3].'/'.$matches[4].' '.$matches[5].':'.$matches[6];
				if ($matches[7] == 'EST') $result['estimated'] = 1;
				else $result['estimated'] = 0;
				if ($matches[7] == 'PERM') $result['permanent'] = 1;
				else $result['permanent'] = 0;
				$c = true;
			}
			elseif (preg_match('#(^|\s)C\) (EST|PERM)$#',$line,$matches) && $c === false) {
				$result['date_end'] = '2030/12/20 12:00';
				if ($matches[2] == 'EST') $result['estimated'] = 1;
				else $result['estimated'] = 0;
				if ($matches[2] == 'PERM') $result['permanent'] = 1;
				else $result['permanent'] = 0;
				$c = true;
			}
			elseif (preg_match('#(^|\s)E\) (.*)#',$line,$matches) && $e === false) {
				$rtext = array();
				$text = explode(' ',$matches[2]);
				foreach ($text as $word) {
					if (isset($this->abbr[$word])) $rtext[] = strtoupper($this->abbr[$word]);
					elseif (ctype_digit(strval(substr($word,3))) && isset($this->abbr[substr($word,0,3)])) $rtext[] = strtoupper($this->abbr[substr($word,0,3)]).' '.substr($word,3);
					else $rtext[] = $word;
				}
				$result['text'] = implode(' ',$rtext);
				$e = true;
			//} elseif (preg_match('#F\) (.*)#',$line,$matches)) {
			//} elseif (preg_match('#G\) (.*)#',$line,$matches)) {
			} elseif (preg_match('#(NOTAMN|NOTAMR|NOTAMC)#',$line,$matches)) {
				$text = explode(' ',$line);
				$result['ref'] = $text[0];
				if ($matches[1] == 'NOTAMN') $result['type'] = 'new';
				if ($matches[1] == 'NOTAMC') {
					$result['type'] = 'cancel';
					$result['ref_cancelled'] = $text[2];
				}
				if ($matches[1] == 'NOTAMR') {
					$result['type'] = 'replace';
					$result['ref_replaced'] = $text[2];
				}
			}
		}
		return $result;
	}
	
	public function parse_code($code) {
		$code = str_split($code);
		$code_fp = $code[1].$code[2];
		$code_sp = $code[3].$code[4];
		$result = '';
		switch ($code[1]) {
			case 'A':
				$result = 'Airspace organization ';
				if (isset($this->code_airspace[$code_fp])) $result .= $this->code_airspace[$code_fp];
				break;
			case 'C':
				$result = 'Communications and radar facilities ';
				if (isset($this->code_comradar[$code_fp])) $result .= $this->code_comradar[$code_fp];
				break;
			case 'F':
				$result = 'Facilities and services ';
				if (isset($this->code_facilities[$code_fp])) $result .= $this->code_facilities[$code_fp];
				break;
			case 'I':
				$result = 'Instrument and Microwave Landing System ';
				if (isset($this->code_instrumentlanding[$code_fp])) $result .= $this->code_instrumentlanding[$code_fp];
				break;
			case 'L':
				$result = 'Lighting facilities ';
				if (isset($this->code_lightingfacilities[$code_fp])) $result .= $this->code_lightingfacilities[$code_fp];
				break;
			case 'M':
				$result = 'Movement and landing areas ';
				if (isset($this->code_movementareas[$code_fp])) $result .= $this->code_movementareas[$code_fp];
				break;
			case 'N':
				$result = 'Terminal and En Route Navigation Facilities ';
				if (isset($this->code_terminalfacilities[$code_fp])) $result .= $this->code_terminalfacilities[$code_fp];
				break;
			case 'O':
				$result = 'Other information ';
				if (isset($this->code_information[$code_fp])) $result .= $this->code_information[$code_fp];
				break;
			case 'P':
				$result = 'Air Traffic procedures ';
				if (isset($this->code_airtraffic[$code_fp])) $result .= $this->code_airtraffic[$code_fp];
				break;
			case 'R':
				$result = 'Navigation Warnings: Airspace Restrictions ';
				if (isset($this->code_navigationw[$code_fp])) $result .= $this->code_navigationw[$code_fp];
				break;
			case 'S':
				$result = 'Air Traffic and VOLMET Services ';
				if (isset($this->code_volmet[$code_fp])) $result .= $this->code_volmet[$code_fp];
				break;
			case 'W':
				$result = 'Navigation Warnings: Warnings ';
				if (isset($this->code_warnings[$code_fp])) $result .= $this->code_warnings[$code_fp];
				break;
		}
		switch ($code[3]) {
			case 'A':
				// Availability
				if (isset($this->code_sp_availabity[$code_sp])) $result .= ' '.$this->code_sp_availabity[$code_sp];
				break;
			case 'C':
				// Changes
				if (isset($this->code_sp_changes[$code_sp])) $result .= ' '.$this->code_sp_changes[$code_sp];
				break;
			case 'H':
				// Hazardous conditions
				if (isset($this->code_sp_hazardous[$code_sp])) $result .= ' '.$this->code_sp_hazardous[$code_sp];
				break;
			case 'L':
				// Limitations
				if (isset($this->code_sp_limitations[$code_sp])) $result .= ' '.$this->code_sp_limitations[$code_sp];
				break;
			case 'X':
				// Other Information
				break;
		}
		return trim($result);
	}
}
/*
$NOTAM = new NOTAM();
//print_r($NOTAM->downloadNOTAM('lfll'));
//print_r($NOTAM->parse(''));
//$NOTAM->deleteAllNOTAM();
//$NOTAM->updateNOTAMallAirports();
//echo $NOTAM->parse_code('QFATT');
//$NOTAM->createNOTAMtextFile('../install/tmp/notam.txt');
$NOTAM->updateNOTAMfromTextFile('../install/tmp/notam.txt');
*/

?>