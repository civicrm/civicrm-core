-- phpMyAdmin SQL Dump
-- version 3.3.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 30, 2013 at 01:05 PM
-- Server version: 5.5.31
-- PHP Version: 5.3.10-1ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `civicrm_tests_dev`
--

--
-- Dumping data for table `civicrm_address`
--

INSERT INTO `civicrm_address` (`id`, `contact_id`, `location_type_id`, `is_primary`, `is_billing`, `street_address`, `street_number`, `street_number_suffix`, `street_number_predirectional`, `street_name`, `street_type`, `street_number_postdirectional`, `street_unit`, `supplemental_address_1`, `supplemental_address_2`, `supplemental_address_3`, `city`, `county_id`, `state_province_id`, `postal_code_suffix`, `postal_code`, `usps_adc`, `country_id`, `geo_code_1`, `geo_code_2`, `manual_geo_code`, `timezone`, `name`, `master_id`) VALUES
(5, 71, 1, 1, 0, '877P Caulder Way S', 877, 'P', NULL, 'Caulder', 'Way', 'S', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'B10 G56', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(13, 4, 1, 1, 0, '990G Martin Luther King Dr W', 990, 'G', NULL, 'Martin Luther King', 'Dr', 'W', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(26, 92, 1, 1, 0, '271X Pine Ave N', 271, 'X', NULL, 'Pine', 'Ave', 'N', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'B10 G56', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(42, 34, 1, 1, 0, '691V Main Ln W', 691, 'V', NULL, 'Main', 'Ln', 'W', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(83, 6, 2, 1, 0, '249S Martin Luther King Way N', 249, 'S', NULL, 'Martin Luther King', 'Way', 'N', NULL, 'Subscriptions Dept', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(93, 82, 3, 1, 0, '838D Van Ness Blvd E', 838, 'D', NULL, 'Van Ness', 'Blvd', 'E', NULL, 'Urgent', NULL, NULL, NULL, 1, NULL, NULL, 'B10 G56', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(97, 19, 1, 1, 0, '780L Van Ness St S', 780, 'L', NULL, 'Van Ness', 'St', 'S', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'B10 G56', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(128, 8, 1, 1, 0, '527K Main Pl S', 527, 'K', NULL, 'Main', 'Pl', 'S', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '444567', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(151, 6, 1, 0, 0, '996S States Ln W', 996, 'S', NULL, 'States', 'Ln', 'W', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(171, 16, 1, 1, 0, '409K El Camino Path NE', 409, 'K', NULL, 'El Camino', 'Path', 'NE', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '54286', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(175, NULL, 1, 1, 1, '14S El Camino Way E', 14, 'S', NULL, 'El Camino', 'Way', NULL, NULL, NULL, NULL, NULL, 'Collinsville', NULL, 1006, NULL, '6022', NULL, 1228, 41.8328, -72.9253, 0, NULL, NULL, NULL),
(176, NULL, 1, 1, 1, '11B Woodbridge Path SW', 11, 'B', NULL, 'Woodbridge', 'Path', NULL, NULL, NULL, NULL, NULL, 'Dayton', NULL, 1034, NULL, 'B10 G56', NULL, 1228, 39.7531, -84.2471, 0, NULL, NULL, NULL),
(177, NULL, 1, 1, 1, '581O Lincoln Dr SW', 581, 'O', NULL, 'Lincoln', 'Dr', NULL, NULL, NULL, NULL, NULL, 'Santa Fe', NULL, 1030, NULL, '87594', NULL, 1228, 35.5212, -105.982, 0, NULL, NULL, NULL);

--
-- Dumping data for table `civicrm_contact`
--

INSERT INTO `civicrm_contact` (`id`, `contact_type`, `contact_sub_type`, `do_not_email`, `do_not_phone`, `do_not_mail`, `do_not_sms`, `do_not_trade`, `is_opt_out`, `legal_identifier`, `external_identifier`, `sort_name`, `display_name`, `nick_name`, `legal_name`, `image_URL`, `preferred_communication_method`, `preferred_language`, `preferred_mail_format`, `hash`, `api_key`, `source`, `first_name`, `middle_name`, `last_name`, `prefix_id`, `suffix_id`, `email_greeting_id`, `email_greeting_custom`, `email_greeting_display`, `postal_greeting_id`, `postal_greeting_custom`, `postal_greeting_display`, `addressee_id`, `addressee_custom`, `addressee_display`, `job_title`, `gender_id`, `birth_date`, `is_deceased`, `deceased_date`, `household_name`, `primary_contact_id`, `organization_name`, `sic_code`, `user_unique_id`, `employer_id`, `is_deleted`, `created_date`, `modified_date`) VALUES
(1, 'Organization', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Default Organization', 'Default Organization', NULL, 'Default Organization', NULL, NULL, NULL, 'Both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Default Organization', NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:22'),
(2, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'merriechowski95@notmail.info', 'merriechowski95@notmail.info', NULL, NULL, NULL, '3', NULL, 'Both', '748043021', NULL, NULL, NULL, NULL, NULL, 4, NULL, 1, NULL, 'Dear merriechowski95@notmail.info', 1, NULL, 'Dear merriechowski95@notmail.info', 1, NULL, 'merriechowski95@notmail.info', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:51'),
(4, 'Individual', NULL, 0, 1, 0, 0, 0, 0, NULL, NULL, 'Dimitrov, Kenny', 'Kenny Dimitrov Sr.', NULL, NULL, NULL, NULL, NULL, 'Both', '-1596099917', NULL, NULL, 'Kenny', 'U', 'Dimitrov', NULL, 2, 1, NULL, 'Dear Kenny', 1, NULL, 'Dear Kenny', 1, NULL, 'Kenny Dimitrov Sr.', NULL, NULL, '1957-10-30', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:45'),
(6, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Dimitrov, Iris', 'Ms. Iris Dimitrov', NULL, NULL, NULL, '5', NULL, 'Both', '1205681375', NULL, NULL, 'Iris', 'L', 'Dimitrov', 2, NULL, 1, NULL, 'Dear Iris', 1, NULL, 'Dear Iris', 1, NULL, 'Ms. Iris Dimitrov', NULL, 1, '1965-06-03', 1, '2012-06-27', NULL, NULL, 'United Peace Center', NULL, NULL, NULL, 0, NULL, '2013-05-29 17:59:13'),
(8, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Muller, Billy', 'Mr. Billy Muller Jr.', NULL, NULL, NULL, NULL, NULL, 'Both', '1431681652', NULL, NULL, 'Billy', '', 'Muller', 3, 1, 1, NULL, 'Dear Billy', 1, NULL, 'Dear Billy', 1, NULL, 'Mr. Billy Muller Jr.', NULL, 2, '1975-06-21', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:59:01'),
(16, 'Individual', NULL, 1, 0, 0, 0, 0, 0, NULL, NULL, 'Smith, Errol', 'Dr. Errol Smith II', NULL, NULL, NULL, NULL, NULL, 'Both', '-2025612268', NULL, NULL, 'Errol', 'F', 'Smith', 4, 3, 1, NULL, 'Dear Errol', 1, NULL, 'Dear Errol', 1, NULL, 'Dr. Errol Smith II', NULL, 2, '1958-07-23', 1, '2013-05-19', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:59:06'),
(19, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Bachman, Lincoln', 'Dr. Lincoln Bachman', NULL, NULL, NULL, '4', NULL, 'Both', '-320957811', NULL, NULL, 'Lincoln', 'F', 'Bachman', 4, NULL, 1, NULL, 'Dear Lincoln', 1, NULL, 'Dear Lincoln', 1, NULL, 'Dr. Lincoln Bachman', NULL, 2, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:56'),
(34, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Blackwell, Sanford', 'Mr. Sanford Blackwell III', NULL, NULL, NULL, '3', NULL, 'Both', '-1083735405', NULL, NULL, 'Sanford', 'T', 'Blackwell', 3, 4, 1, NULL, 'Dear Sanford', 1, NULL, 'Dear Sanford', 1, NULL, 'Mr. Sanford Blackwell III', NULL, 2, '1966-06-14', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:54'),
(71, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Grant, Megan', 'Dr. Megan Grant', NULL, NULL, NULL, '1', NULL, 'Both', '597745467', NULL, NULL, 'Megan', '', 'Grant', 4, NULL, 1, NULL, 'Dear Megan', 1, NULL, 'Dear Megan', 1, NULL, 'Dr. Megan Grant', NULL, 1, NULL, 1, '2013-03-17', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:44'),
(82, 'Organization', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, ' Empowerment Association', ' Empowerment Association', NULL, NULL, NULL, '4', NULL, 'Both', '-35644052', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, ' Empowerment Association', NULL, NULL, NULL, 0, NULL, NULL, NULL, ' Empowerment Association', NULL, NULL, NULL, 0, NULL, '2013-05-29 17:59:15'),
(92, 'Individual', NULL, 1, 0, 0, 0, 0, 0, NULL, NULL, 'Reynolds, Brent', 'Mr. Brent Reynolds', NULL, NULL, NULL, '5', NULL, 'Both', '547975558', NULL, NULL, 'Brent', 'E', 'Reynolds', 3, NULL, 1, NULL, 'Dear Brent', 1, NULL, 'Dear Brent', 1, NULL, 'Mr. Brent Reynolds', NULL, 2, '1981-07-27', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:50');

--
-- Dumping data for table `civicrm_contribution`
--

INSERT INTO `civicrm_contribution` (`id`, `contact_id`, `financial_type_id`, `contribution_page_id`, `payment_instrument_id`, `receive_date`, `non_deductible_amount`, `total_amount`, `fee_amount`, `net_amount`, `trxn_id`, `invoice_id`, `currency`, `cancel_date`, `cancel_reason`, `receipt_date`, `thankyou_date`, `source`, `amount_level`, `contribution_recur_id`, `is_test`, `is_pay_later`, `contribution_status_id`, `address_id`, `check_number`, `campaign_id`) VALUES
(1, 2, 1, NULL, 4, '2010-04-11 00:00:00', 0.00, 125.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '1041', NULL),
(2, 4, 1, NULL, 1, '2010-03-21 00:00:00', 0.00, 50.00, NULL, NULL, 'P20901X1', NULL, 'USD', NULL, NULL, NULL, NULL, 'Online: Save the Penguins', NULL, NULL, 0, 0, 1, NULL, NULL, NULL),
(3, 6, 1, NULL, 4, '2010-04-29 00:00:00', 0.00, 25.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '2095', NULL),
(4, 8, 1, NULL, 4, '2010-04-11 00:00:00', 0.00, 50.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '10552', NULL),
(5, 16, 1, NULL, 4, '2010-04-15 00:00:00', 0.00, 500.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '509', NULL),
(6, 19, 1, NULL, 4, '2010-04-11 00:00:00', 0.00, 175.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '102', NULL),
(7, 82, 1, NULL, 1, '2010-03-27 00:00:00', 0.00, 50.00, NULL, NULL, 'P20193L2', NULL, 'USD', NULL, NULL, NULL, NULL, 'Online: Save the Penguins', NULL, NULL, 0, 0, 1, NULL, NULL, NULL),
(8, 92, 1, NULL, 1, '2010-03-08 00:00:00', 0.00, 10.00, NULL, NULL, 'P40232Y3', NULL, 'USD', NULL, NULL, NULL, NULL, 'Online: Help CiviCRM', NULL, NULL, 0, 0, 1, NULL, NULL, NULL),
(9, 34, 1, NULL, 1, '2010-04-22 00:00:00', 0.00, 250.00, NULL, NULL, 'P20193L6', NULL, 'USD', NULL, NULL, NULL, NULL, 'Online: Help CiviCRM', NULL, NULL, 0, 0, 1, NULL, NULL, NULL),
(10, 71, 1, NULL, 1, '2009-07-01 11:53:50', 0.00, 500.00, NULL, NULL, 'PL71', NULL, 'USD', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, NULL);

--
-- Dumping data for table `civicrm_email`
--

INSERT INTO `civicrm_email` (`id`, `contact_id`, `location_type_id`, `email`, `is_primary`, `is_billing`, `on_hold`, `is_bulkmail`, `hold_date`, `reset_date`, `signature_text`, `signature_html`) VALUES
(1, 1, 1, 'fixme.domainemail@example.org', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(7, 71, 1, 'grantm@fishmail.net', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(41, 2, 1, 'merrie@testmail.co.nz', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(42, 2, 1, 'merriechowski95@notmail.info', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(60, 34, 1, 'st.blackwell3@testmail.co.pl', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(113, 8, 1, 'mller.billy30@example.org', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(114, 8, 1, 'mllerb@infomail.co.in', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(137, 6, 1, 'irisdimitrov@infomail.co.pl', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(154, 16, 1, 'errols@sample.com', 1, 0, 0, 0, NULL, NULL, NULL, NULL),
(155, 16, 1, 'smith.errol@mymail.co.pl', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(172, 6, 2, 'dimitrov.l.iris@unitedpeacecenter.org', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(184, NULL, 1, 'development@example.org', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(185, NULL, 1, 'tournaments@example.org', 0, 0, 0, 0, NULL, NULL, NULL, NULL),
(186, NULL, 1, 'celebration@example.org', 0, 0, 0, 0, NULL, NULL, NULL, NULL);

--
-- Dumping data for table `civicrm_phone`
--

INSERT INTO `civicrm_phone` (`id`, `contact_id`, `location_type_id`, `is_primary`, `is_billing`, `mobile_provider_id`, `phone`, `phone_ext`, `phone_numeric`, `phone_type_id`) VALUES
(6, 71, 1, 1, 0, NULL, '573-7649', NULL, '5737649', 2),
(40, 92, 1, 1, 0, NULL, '(713) 802-2262', NULL, '7138022262', 2),
(41, 92, 1, 0, 0, NULL, '469-2012', NULL, '4692012', 1),
(51, 2, 1, 1, 0, NULL, '(330) 886-4629', NULL, '3308864629', 2),
(52, 2, 1, 0, 0, NULL, '(654) 630-9188', NULL, '6546309188', 1),
(67, 34, 1, 1, 0, NULL, '415-8524', NULL, '4158524', 1),
(79, 19, 1, 1, 0, NULL, '(626) 649-9130', NULL, '6266499130', 2),
(157, 16, 1, 1, 0, NULL, '849-6331', NULL, '8496331', 1),
(158, 16, 1, 0, 0, NULL, '880-8760', NULL, '8808760', 2),
(162, NULL, 1, 0, 0, NULL, '204 222-1000', NULL, '2042221000', 1),
(163, NULL, 1, 0, 0, NULL, '204 223-1000', NULL, '2042231000', 1),
(164, NULL, 1, 0, 0, NULL, '303 323-1000', NULL, '3033231000', 1);
