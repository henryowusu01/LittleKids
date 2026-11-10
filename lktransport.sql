-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 11:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lktransport`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `adminID` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `roleName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbladminactivity`
--

CREATE TABLE `tbladminactivity` (
  `activityID` int(11) NOT NULL,
  `adminID` int(11) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblchildren`
--

CREATE TABLE `tblchildren` (
  `ChildID` int(11) NOT NULL,
  `guardianID` int(11) DEFAULT NULL,
  `fullName` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `specialNeeds` varchar(255) NOT NULL,
  `EmergName` varchar(100) NOT NULL,
  `EmergNum` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbldriver`
--

CREATE TABLE `tbldriver` (
  `driverID` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `phoneNum` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(100) NOT NULL,
  `LicenseNum` varchar(50) NOT NULL,
  `VehicleType` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblguardians`
--

CREATE TABLE `tblguardians` (
  `guardianID` int(11) NOT NULL,
  `phoneNum` varchar(20) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblincidents`
--

CREATE TABLE `tblincidents` (
  `incidentID` int(11) NOT NULL,
  `inDesc` text NOT NULL,
  `inDate` datetime NOT NULL,
  `emergAlert` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblroutes`
--

CREATE TABLE `tblroutes` (
  `routeID` int(11) NOT NULL,
  `routeName` varchar(100) NOT NULL,
  `pickup` varchar(225) NOT NULL,
  `dropoff` varchar(255) NOT NULL,
  `routeStatus` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblschedule`
--

CREATE TABLE `tblschedule` (
  `scheduleID` int(11) NOT NULL,
  `transpDate` date DEFAULT NULL,
  `routeID` int(11) DEFAULT NULL,
  `serviceDays` varchar(50) DEFAULT NULL,
  `departureTime` time DEFAULT NULL,
  `arrivalTime` time DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `userID` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `roleID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`adminID`),
  ADD UNIQUE KEY `uq_tbladmin_email` (`email`);

--
-- Indexes for table `tblchildren`
--
ALTER TABLE `tblchildren`
  ADD PRIMARY KEY (`ChildID`),
  ADD KEY `guardianID` (`guardianID`);

--
-- Indexes for table `tbldriver`
--
ALTER TABLE `tbldriver`
  ADD PRIMARY KEY (`driverID`);

--
-- Indexes for table `tblguardians`
--
ALTER TABLE `tblguardians`
  ADD PRIMARY KEY (`guardianID`);

--
-- Indexes for table `tblincidents`
--
ALTER TABLE `tblincidents`
  ADD PRIMARY KEY (`incidentID`);

--
-- Indexes for table `tblroutes`
--
ALTER TABLE `tblroutes`
  ADD PRIMARY KEY (`routeID`);

--
-- Indexes for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD PRIMARY KEY (`scheduleID`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbladmin`
--
ALTER TABLE `tbladmin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbladminactivity`
--
ALTER TABLE `tbladminactivity`
  ADD PRIMARY KEY (`activityID`),
  MODIFY `activityID` int(11) NOT NULL AUTO_INCREMENT,
  ADD KEY `idx_tbladminactivity_recent` (`createdAt`, `activityID`);

--
-- AUTO_INCREMENT for table `tblchildren`
--
ALTER TABLE `tblchildren`
  MODIFY `ChildID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbldriver`
--
ALTER TABLE `tbldriver`
  MODIFY `driverID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblguardians`
--
ALTER TABLE `tblguardians`
  MODIFY `guardianID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblincidents`
--
ALTER TABLE `tblincidents`
  MODIFY `incidentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblroutes`
--
ALTER TABLE `tblroutes`
  MODIFY `routeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `scheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Sample Gauteng routes and weekday schedules
--
INSERT INTO `tblroutes` (`routeName`, `pickup`, `dropoff`, `routeStatus`) VALUES
  ('Soweto to Johannesburg CBD', 'Orlando East, Soweto', 'Johannesburg CBD', 'Active'),
  ('Roodepoort to Randburg', 'Constantia Kloof, Roodepoort', 'Randburg', 'Active'),
  ('Midrand to Sandton', 'Carlswald, Midrand', 'Sandton', 'Active'),
  ('Centurion to Pretoria CBD', 'Lyttelton, Centurion', 'Pretoria CBD', 'Active'),
  ('Kempton Park to Edenvale', 'Festival Mall, Kempton Park', 'Edenvale', 'Active');

INSERT INTO `tblschedule` (`routeID`, `serviceDays`, `departureTime`, `arrivalTime`, `status`)
SELECT `routeID`, 'Monday-Friday', '06:30:00', '07:30:00', 'Active'
FROM `tblroutes` WHERE `routeName` = 'Soweto to Johannesburg CBD';

INSERT INTO `tblschedule` (`routeID`, `serviceDays`, `departureTime`, `arrivalTime`, `status`)
SELECT `routeID`, 'Monday-Friday', '06:30:00', '07:15:00', 'Active'
FROM `tblroutes` WHERE `routeName` = 'Roodepoort to Randburg';

INSERT INTO `tblschedule` (`routeID`, `serviceDays`, `departureTime`, `arrivalTime`, `status`)
SELECT `routeID`, 'Monday-Friday', '06:25:00', '07:15:00', 'Active'
FROM `tblroutes` WHERE `routeName` = 'Midrand to Sandton';

INSERT INTO `tblschedule` (`routeID`, `serviceDays`, `departureTime`, `arrivalTime`, `status`)
SELECT `routeID`, 'Monday-Friday', '06:30:00', '07:15:00', 'Active'
FROM `tblroutes` WHERE `routeName` = 'Centurion to Pretoria CBD';

INSERT INTO `tblschedule` (`routeID`, `serviceDays`, `departureTime`, `arrivalTime`, `status`)
SELECT `routeID`, 'Monday-Friday', '06:30:00', '07:10:00', 'Active'
FROM `tblroutes` WHERE `routeName` = 'Kempton Park to Edenvale';

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD CONSTRAINT `fk_tblschedule_route` FOREIGN KEY (`routeID`) REFERENCES `tblroutes` (`routeID`) ON DELETE CASCADE;

--
-- Constraints for table `tblchildren`
--
ALTER TABLE `tblchildren`
  ADD CONSTRAINT `fk_tblchildren_guardian` FOREIGN KEY (`guardianID`) REFERENCES `tblguardians` (`guardianID`) ON DELETE CASCADE;

--
-- Sample incidents for the guardian incident page
--
INSERT INTO `tblincidents` (`inDesc`, `inDate`, `emergAlert`)
SELECT 'Sample incident: Morning traffic caused a delay on the Soweto to Johannesburg CBD route.', CURRENT_TIMESTAMP, 'Route delay'
WHERE NOT EXISTS (
  SELECT 1 FROM `tblincidents`
  WHERE `inDesc` = 'Sample incident: Morning traffic caused a delay on the Soweto to Johannesburg CBD route.'
);

INSERT INTO `tblincidents` (`inDesc`, `inDate`, `emergAlert`)
SELECT 'Sample incident: A vehicle safety inspection delayed the Midrand to Sandton service.', CURRENT_TIMESTAMP, 'Service update'
WHERE NOT EXISTS (
  SELECT 1 FROM `tblincidents`
  WHERE `inDesc` = 'Sample incident: A vehicle safety inspection delayed the Midrand to Sandton service.'
);

INSERT INTO `tblincidents` (`inDesc`, `inDate`, `emergAlert`)
SELECT 'Sample incident: Heavy rain required slower travel on the Centurion to Pretoria CBD route.', CURRENT_TIMESTAMP, 'Weather advisory'
WHERE NOT EXISTS (
  SELECT 1 FROM `tblincidents`
  WHERE `inDesc` = 'Sample incident: Heavy rain required slower travel on the Centurion to Pretoria CBD route.'
);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
