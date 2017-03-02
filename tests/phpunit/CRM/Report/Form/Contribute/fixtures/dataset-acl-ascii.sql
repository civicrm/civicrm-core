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
-- Dumping data for table `civicrm_contact`
--

INSERT INTO `civicrm_contact` (`id`, `contact_type`, `contact_sub_type`, `do_not_email`, `do_not_phone`, `do_not_mail`, `do_not_sms`, `do_not_trade`, `is_opt_out`, `legal_identifier`, `external_identifier`, `sort_name`, `display_name`, `nick_name`, `legal_name`, `image_URL`, `preferred_communication_method`, `preferred_language`, `preferred_mail_format`, `hash`, `api_key`, `source`, `first_name`, `middle_name`, `last_name`, `prefix_id`, `suffix_id`, `email_greeting_id`, `email_greeting_custom`, `email_greeting_display`, `postal_greeting_id`, `postal_greeting_custom`, `postal_greeting_display`, `addressee_id`, `addressee_custom`, `addressee_display`, `job_title`, `gender_id`, `birth_date`, `is_deceased`, `deceased_date`, `household_name`, `primary_contact_id`, `organization_name`, `sic_code`, `user_unique_id`, `employer_id`, `is_deleted`, `created_date`, `modified_date`) VALUES
(1, 'Organization', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Default Organization', 'Default Organization', NULL, 'Default Organization', NULL, NULL, NULL, 'Both', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Default Organization', NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:22'),
(2, 'Individual', NULL, 0, 0, 0, 0, 0, 0, NULL, NULL, 'Doe, Jane', 'Jane Doe', NULL, NULL, NULL, '3', NULL, 'Both', '748043021', NULL, NULL, 'Jane', 'Doe', NULL, 4, NULL, 1, NULL, 'Dear Jane', 1, NULL, 'Dear Jane', 1, NULL, 'Jane Doe', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2013-05-29 17:58:51'),
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
(3, 6, 2, NULL, 4, '2010-04-29 00:00:00', 0.00, 25.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '2095', NULL),
(4, 8, 2, NULL, 4, '2010-04-11 00:00:00', 0.00, 50.00, NULL, NULL, NULL, NULL, 'USD', NULL, NULL, NULL, NULL, 'Apr 2007 Mailer 1', NULL, NULL, 0, 0, 1, NULL, '10552', NULL);

--
-- Dumping data for table `civicrm_line_item`
--
INSERT INTO `civicrm_line_item` (`id`, `entity_table`, `entity_id`, `contribution_id`, `price_field_id`, `label`, `qty`, `unit_price`, `line_total`, `participant_count`, `price_field_value_id`, `financial_type_id`, `non_deductible_amount`, `tax_amount`) VALUES
(1, 'civicrm_contribution', 1, 1, 1, 'Contribution Amount', 1.00, 125.00, 125.00, 0, 1, 1, 0.00, NULL),
(2, 'civicrm_contribution', 2, 2, 1, 'Contribution Amount', 1.00, 50.00, 50.00, 0, 1, 1, 0.00, NULL),
(3, 'civicrm_contribution', 3, 3, 1, 'Contribution Amount', 1.00, 25.00, 25.00, 0, 1, 2, 0.00, NULL),
(4, 'civicrm_contribution', 4, 4, 1, 'Contribution Amount', 1.00, 50.00, 50.00, 0, 1, 2, 0.00, NULL);

