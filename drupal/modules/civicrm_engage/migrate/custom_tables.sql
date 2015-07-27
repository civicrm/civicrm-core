-- phpMyAdmin SQL Dump
-- version 3.2.2.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 03, 2010 at 08:02 PM
-- Server version: 5.1.37
-- PHP Version: 5.2.10-2ubuntu6.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `civicrm_31`
--

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_communication_details`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_communication_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `best_time_to_contact` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `communication_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reason_for_do_not_mail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reason_for_do_not_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reason_for_do_not_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_best_time_to_contact` (`best_time_to_contact`),
  KEY `INDEX_communication_status` (`communication_status`),
  KEY `INDEX_reason_for_do_not_mail` (`reason_for_do_not_mail`),
  KEY `INDEX_reason_for_do_not_phone` (`reason_for_do_not_phone`),
  KEY `INDEX_reason_for_do_not_email` (`reason_for_do_not_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_contact_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_contact_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `constituent_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_constituent_type` (`constituent_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_contribution_source`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_contribution_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `campaign_source_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'General Donations',
  `campaign_method` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_campaign_source_code` (`campaign_source_code`),
  KEY `INDEX_campaign_method` (`campaign_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_core_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_core_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `constituent_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `staff_responsible` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_started` datetime DEFAULT NULL,
  `other_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `how_started` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_constituent_type` (`constituent_type`),
  KEY `INDEX_staff_responsible` (`staff_responsible`),
  KEY `INDEX_date_started` (`date_started`),
  KEY `INDEX_other_name` (`other_name`),
  KEY `INDEX_how_started` (`how_started`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_demographics`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_demographics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `ethnicity` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `secondary_language` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `kids` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_ethnicity` (`ethnicity`),
  KEY `INDEX_primary_language` (`primary_language`),
  KEY `INDEX_secondary_language` (`secondary_language`),
  KEY `INDEX_kids` (`kids`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_door_knock_responses`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_door_knock_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `is_this_a_field_engage_campaign` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q3` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q4` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `walk_list_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_is_this_a_field_engage_campaign` (`is_this_a_field_engage_campaign`),
  KEY `INDEX_q1` (`q1`),
  KEY `INDEX_q2` (`q2`),
  KEY `INDEX_q3` (`q3`),
  KEY `INDEX_q4` (`q4`),
  KEY `INDEX_walk_list_status` (`walk_list_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_event_campaign_details`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_event_campaign_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `q1_text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q2_text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q3_text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q4_text` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_q1_text` (`q1_text`),
  KEY `INDEX_q2_text` (`q2_text`),
  KEY `INDEX_q3_text` (`q3_text`),
  KEY `INDEX_q4_text` (`q4_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_event_details`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_event_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `event_contact_person` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_source_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'General Donations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_event_contact_person` (`event_contact_person`),
  KEY `INDEX_event_source_code` (`event_source_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_grant_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_grant_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `average_amount` decimal(20,2) DEFAULT NULL,
  `funding_areas` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `requirements_notes` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_average_amount` (`average_amount`),
  KEY `INDEX_funding_areas` (`funding_areas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_grassroots_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_grassroots_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `leadership_level` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issues_interest` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `volunteer_interests` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `member_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_leadership_level` (`leadership_level`),
  KEY `INDEX_issues_interest` (`issues_interest`),
  KEY `INDEX_volunteer_interests` (`volunteer_interests`),
  KEY `INDEX_member_status` (`member_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_leadership_level`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_leadership_level` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `leadership_level` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_leadership_level` (`leadership_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_organizational_details`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_organizational_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `rating` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_participant_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_participant_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `childcare_needed` int(11) DEFAULT NULL,
  `ride_to` int(11) DEFAULT NULL,
  `ride_back` int(11) DEFAULT NULL,
  `invitation_date` datetime DEFAULT NULL,
  `invitation_response` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `second_call_date` datetime DEFAULT NULL,
  `second_call_response` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reminder_date` datetime DEFAULT NULL,
  `reminder_response` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contact_attended` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `participant_campaign_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'General Donations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_childcare_needed` (`childcare_needed`),
  KEY `INDEX_ride_to` (`ride_to`),
  KEY `INDEX_ride_back` (`ride_back`),
  KEY `INDEX_invitation_date` (`invitation_date`),
  KEY `INDEX_invitation_response` (`invitation_response`),
  KEY `INDEX_second_call_response` (`second_call_response`),
  KEY `INDEX_reminder_date` (`reminder_date`),
  KEY `INDEX_reminder_response` (`reminder_response`),
  KEY `INDEX_contact_attended` (`contact_attended`),
  KEY `INDEX_participant_campaign_code` (`participant_campaign_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_phone_bank_responses`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_phone_bank_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `is_this_a_phone_bank` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `call_list_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q3` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `q4` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_is_this_a_phone_bank` (`is_this_a_phone_bank`),
  KEY `INDEX_call_list_status` (`call_list_status`),
  KEY `INDEX_q1` (`q1`),
  KEY `INDEX_q2` (`q2`),
  KEY `INDEX_q3` (`q3`),
  KEY `INDEX_q4` (`q4`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_primary_contact`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_primary_contact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_proposal_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_proposal_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `ask_amount` decimal(20,2) DEFAULT NULL,
  `amount_to_be_received` decimal(20,2) DEFAULT NULL,
  `date_to_be_received` datetime DEFAULT NULL,
  `multiyear_grant` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `years` double DEFAULT NULL,
  `proposal_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_ask_amount` (`ask_amount`),
  KEY `INDEX_amount_to_be_received` (`amount_to_be_received`),
  KEY `INDEX_date_to_be_received` (`date_to_be_received`),
  KEY `INDEX_multiyear_grant` (`multiyear_grant`),
  KEY `INDEX_years` (`years`),
  KEY `INDEX_proposal_status` (`proposal_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_source_details`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_source_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `activity_source_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'General Donations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_activity_source_code` (`activity_source_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_value_voter_info`
--

CREATE TABLE IF NOT EXISTS `civicrm_value_voter_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `precinct` double DEFAULT NULL,
  `state_district` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city_district` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `federal_district` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `party_registration` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `if_other_party` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state_voter_id` double DEFAULT NULL,
  `voted_in_2008_general_election` tinyint(4) DEFAULT NULL,
  `voted_in_2008_primary_election` tinyint(4) DEFAULT NULL,
  `county_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `voter_history` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `INDEX_precinct` (`precinct`),
  KEY `INDEX_state_district` (`state_district`),
  KEY `INDEX_city_district` (`city_district`),
  KEY `INDEX_federal_district` (`federal_district`),
  KEY `INDEX_party_registration` (`party_registration`),
  KEY `INDEX_if_other_party` (`if_other_party`),
  KEY `INDEX_state_voter_id` (`state_voter_id`),
  KEY `INDEX_voted_in_2008_general_election` (`voted_in_2008_general_election`),
  KEY `INDEX_voted_in_2008_primary_election` (`voted_in_2008_primary_election`),
  KEY `INDEX_county_name` (`county_name`),
  KEY `INDEX_voter_history` (`voter_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_value_communication_details`
--
ALTER TABLE `civicrm_value_communication_details`
  ADD CONSTRAINT `FK_civicrm_value_communication_details_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_contact_info`
--
ALTER TABLE `civicrm_value_contact_info`
  ADD CONSTRAINT `FK_civicrm_value_contact_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_contribution_source`
--
ALTER TABLE `civicrm_value_contribution_source`
  ADD CONSTRAINT `FK_civicrm_value_contribution_source_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contribution` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_core_info`
--
ALTER TABLE `civicrm_value_core_info`
  ADD CONSTRAINT `FK_civicrm_value_core_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_demographics`
--
ALTER TABLE `civicrm_value_demographics`
  ADD CONSTRAINT `FK_civicrm_value_demographics_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_door_knock_responses`
--
ALTER TABLE `civicrm_value_door_knock_responses`
  ADD CONSTRAINT `FK_civicrm_value_door_knock_responses_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_event_campaign_details`
--
ALTER TABLE `civicrm_value_event_campaign_details`
  ADD CONSTRAINT `FK_civicrm_value_event_campaign_details_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_event` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_event_details`
--
ALTER TABLE `civicrm_value_event_details`
  ADD CONSTRAINT `FK_civicrm_value_event_details_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_event` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_grant_info`
--
ALTER TABLE `civicrm_value_grant_info`
  ADD CONSTRAINT `FK_civicrm_value_grant_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_grassroots_info`
--
ALTER TABLE `civicrm_value_grassroots_info`
  ADD CONSTRAINT `FK_civicrm_value_grassroots_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_leadership_level`
--
ALTER TABLE `civicrm_value_leadership_level`
  ADD CONSTRAINT `FK_civicrm_value_leadership_level_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_organizational_details`
--
ALTER TABLE `civicrm_value_organizational_details`
  ADD CONSTRAINT `FK_civicrm_value_organizational_details_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_participant_info`
--
ALTER TABLE `civicrm_value_participant_info`
  ADD CONSTRAINT `FK_civicrm_value_participant_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_participant` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_phone_bank_responses`
--
ALTER TABLE `civicrm_value_phone_bank_responses`
  ADD CONSTRAINT `FK_civicrm_value_phone_bank_responses_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_primary_contact`
--
ALTER TABLE `civicrm_value_primary_contact`
  ADD CONSTRAINT `FK_civicrm_value_primary_contact_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_proposal_info`
--
ALTER TABLE `civicrm_value_proposal_info`
  ADD CONSTRAINT `FK_civicrm_value_proposal_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_source_details`
--
ALTER TABLE `civicrm_value_source_details`
  ADD CONSTRAINT `FK_civicrm_value_source_details_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_value_voter_info`
--
ALTER TABLE `civicrm_value_voter_info`
  ADD CONSTRAINT `FK_civicrm_value_voter_info_entity_id` FOREIGN KEY (`entity_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
