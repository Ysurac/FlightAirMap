SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `aircraft_modes`
--

CREATE TABLE IF NOT EXISTS `aircraft_modes` (
  `AircraftID` int(11) NOT NULL,
  `FirstCreated` datetime,
  `LastModified` datetime,
  `ModeS` varchar(6) NOT NULL,
  `ModeSCountry` varchar(24),
  `Registration` varchar(20),
  `ICAOTypeCode` varchar(4),
  `SerialNo` varchar(30),
  `OperatorFlagCode` varchar(20),
  `Manufacturer` varchar(60),
  `Type` varchar(40),
  `FirstRegDate` varchar(10),
  `CurrentRegDate` varchar(10),
  `Country` varchar(24),
  `PreviousID` varchar(10),
  `DeRegDate` varchar(10),
  `Status` varchar(10),
  `PopularName` varchar(20),
  `GenericName` varchar(20),
  `AircraftClass` varchar(20),
  `Engines` varchar(40),
  `OwnershipStatus` varchar(10),
  `RegisteredOwners` varchar(100),
  `MTOW` varchar(10),
  `TotalHours` varchar(10),
  `YearBuilt` varchar(4),
  `CofACategory` varchar(30),
  `CofAExpiry` varchar(10),
  `UserNotes` varchar(300),
  `Interested` int(1) NOT NULL default 0,
  `UserTag` varchar(5),
  `InfoUrl` varchar(150),
  `PictureUrl1` varchar(150),
  `PictureUrl2` varchar(150),
  `PictureUrl3` varchar(150),
  `UserBool1` int(1) NOT NULL default 0,
  `UserBool2` int(1) NOT NULL default 0,
  `UserBool3` int(1) NOT NULL default 0,
  `UserBool4` int(1) NOT NULL default 0,
  `UserBool5` int(1) NOT NULL default 0,
  `UserString1` varchar(20),
  `UserString2` varchar(20),
  `UserString3` varchar(20),
  `UserString4` varchar(20),
  `UserString5` varchar(20),
  `UserInt1` integer default 0,
  `UserInt2` integer default 0,
  `UserInt3` integer default 0,
  `UserInt4` integer default 0,
  `UserInt5` integer default 0,
  PRIMARY KEY (`AircraftID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
