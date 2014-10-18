<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Upgrade_TwoTwo_Form_Step1 extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    // check if log file is writable
    $config = CRM_Core_Config::singleton();

    if (!is_writable($config->uploadDir . 'CiviCRM.log') &&
      !is_writable($config->uploadDir . 'CiviCRM.log.' .
        md5($config->dsn . $config->userFrameworkResourceURL)
      )
    ) {
      $errorMessage = ts('Log file CiviCRM.log is not writable. Make sure files directory is writable.',
        array(1 => $config->uploadDir)
      );
      return FALSE;
    }

    $errorMessage = ts('Database check failed - the current database is not v2.1.');
    $is21db = TRUE;

    // abort if partial upgraded db found.
    if ($this->checkVersion('2.1.101') ||
      $this->checkVersion('2.1.102') ||
      $this->checkVersion('2.1.103')
    ) {
      $errorMessage = ts('Corrupt / Partial Upgraded database found. Looks like upgrade wizard failed to complete all the required steps to convert your database to v2.2. Please fix any errors and start the upgrade process again with a clean v2.1 database.');
      return FALSE;
    }

    // abort if already 2.2
    if ($this->checkVersion('2.2')) {
      $errorMessage = ts('Database check failed - looks like you have already upgraded to the latest version (v2.2) of the database.');
      return FALSE;
    }

    // check if 2.1 version
    if (!$this->checkVersion('2.1.2') ||
      !$this->checkVersion('2.1.3') ||
      !$this->checkVersion('2.1.4')
    ) {
      $is21db = FALSE;
    }

    // check if 2.1 tables exists
    if (!CRM_Core_DAO::checkTableExists('civicrm_pledge') ||
      !CRM_Core_DAO::checkTableExists('civicrm_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_group_contact_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_discount') ||
      !CRM_Core_DAO::checkTableExists('civicrm_menu') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge_block') ||
      !CRM_Core_DAO::checkTableExists('civicrm_case_contact')
    ) {
      // db is not 2.1
      $errorMessage .= ' Few 2.1 tables were found missing.';
      $is21db = FALSE;
    }

    // check fields which MUST be present if a proper 2.1 db
    if ($is21db) {
      if (!CRM_Core_DAO::checkFieldExists('civicrm_contribution_page', 'is_recur_interval') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_contribution_page', 'recur_frequency_unit') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_contribution_page', 'for_organization') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_contribution_page', 'is_for_organization') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_contribution', 'is_pay_later') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_membership', 'is_pay_later') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_membership_status', 'is_reserved') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_participant', 'is_pay_later') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_participant', 'fee_amount') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_participant', 'registered_by_id') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_participant', 'discount_id') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_contact', 'employer_id') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_domain', 'locales') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_mapping', 'mapping_type_id') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_custom_field', 'is_view') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_group', 'cache_date') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_group', 'parents') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_group', 'children') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_preferences', 'editor_id') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_uf_group', 'group_type') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_address', 'name') ||
        !CRM_Core_DAO::checkFieldExists('civicrm_uf_match', 'language')
      ) {
        // db looks to have stuck somewhere between 2.1 & 2.2
        $errorMessage .= ' Few important fields were found missing in some of the tables.';
        $is21db = FALSE;
      }
    }

    // check if the db is 2.2
    if (!CRM_Core_DAO::checkTableExists('civicrm_event_page') &&
      CRM_Core_DAO::checkFieldExists('civicrm_participant', 'registered_by_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'intro_text') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_multiple_registrations') &&
      CRM_Core_DAO::checkTableExists('civicrm_pcp_block') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'tellfriend_limit') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'supporter_profile_id') &&
      CRM_Core_DAO::checkTableExists('civicrm_pcp') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp', 'status_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp', 'goal_amount') &&
      CRM_Core_DAO::checkTableExists('civicrm_contribution_soft') &&
      CRM_Core_DAO::checkFieldExists('civicrm_contribution_soft', 'pcp_display_in_roll') &&
      CRM_Core_DAO::checkFieldExists('civicrm_contribution_soft', 'amount')
    ) {

      $errorMessage = ts('Database check failed - it looks like you have already upgraded to the latest version (v2.2) of the database.');
      return FALSE;
    }


    // check tables which should not exist for v2.x
    if (CRM_Core_DAO::checkTableExists('civicrm_custom_option') ||
      CRM_Core_DAO::checkTableExists('civicrm_custom_value') ||
      CRM_Core_DAO::checkTableExists('civicrm_email_history') ||
      CRM_Core_DAO::checkTableExists('civicrm_geo_coord') ||
      CRM_Core_DAO::checkTableExists('civicrm_individual') ||
      CRM_Core_DAO::checkTableExists('civicrm_location') ||
      CRM_Core_DAO::checkTableExists('civicrm_meeting') ||
      CRM_Core_DAO::checkTableExists('civicrm_organization') ||
      CRM_Core_DAO::checkTableExists('civicrm_phonecall') ||
      CRM_Core_DAO::checkTableExists('civicrm_sms_history') ||
      CRM_Core_DAO::checkTableExists('civicrm_validation')
    ) {
      // table(s) found in the db which are no longer required
      // for v2.x, though would not do any harm it's recommended
      // to remove them.
      CRM_Core_Session::setStatus(ts("Table(s) found in your db which are no longer required for v2.x, though would not do any harm it's recommended to remove them"), ts('Redundant Tables'), 'info');
    }

    // show error if any of the tables, use 'MyISAM' storage engine.
    // just check the first 10 civicrm tables, rather than checking all 106!
    if (CRM_Core_DAO::isDBMyISAM(10)) {
      $errorMessage = ts('Your database is configured to use the MyISAM database engine. CiviCRM  requires InnoDB. You will need to convert any MyISAM tables in your database to InnoDB before proceeding.');
      return FALSE;
    }

    return TRUE;
  }

  function upgrade() {
    $this->setVersion('2.1.101');
  }

  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPostDBState(&$errorMessage) {
    $errorMessage = ts('Post-condition failed for upgrade step %1.', array(1 => '1'));
    return $this->checkVersion('2.1.101');
  }

  /**
   * @return string
   */
  function getTitle() {
    return ts('CiviCRM 2.2 Upgrade: Step One (Check Version)');
  }

  /**
   * @return string
   */
  function getTemplateMessage() {
    $msg = '<p><strong>' . ts('This process will upgrade your v2.1 CiviCRM database to the v2.2 database format.') . '</strong></p><div class="messsages status"><ul><li><strong>' . ts('Make sure you have a current and complete backup of your CiviCRM database and codebase files before starting the upgrade process.') . '</strong></li><li>' . '</li></ul></div><p>' . ts('Click <strong>Begin Upgrade</strong> to begin the process.') . '</p>';

    return $msg;
  }

  /**
   * @return string
   */
  function getButtonTitle() {
    return ts('Begin Upgrade');
  }
}

