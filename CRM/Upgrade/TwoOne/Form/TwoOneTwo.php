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
class CRM_Upgrade_TwoOne_Form_TwoOneTwo extends CRM_Upgrade_Form {
  /**
   * @param null|object $version
   */
  function __construct($version) {
    parent::__construct();
    $this->latestVersion = $version;
  }

  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade to 2.1.2.');
    // check if the db is 2.2
    if (!CRM_Core_DAO::checkTableExists('civicrm_event_page') &&
      CRM_Core_DAO::checkTableExists('civicrm_pcp_block') &&
      CRM_Core_DAO::checkTableExists('civicrm_pcp') &&
      CRM_Core_DAO::checkTableExists('civicrm_contribution_soft')
    ) {
      $errorMessage = ts('Database check failed - it looks like you have already upgraded to the latest version (v2.2) of the database.');
      return FALSE;
    }
    // check if the db is 2.2
    if (CRM_Core_DAO::checkFieldExists('civicrm_participant', 'registered_by_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'intro_text') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_multiple_registrations') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'tellfriend_limit') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp_block', 'supporter_profile_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp', 'status_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_pcp', 'goal_amount') &&
      CRM_Core_DAO::checkFieldExists('civicrm_contribution_soft', 'pcp_display_in_roll') &&
      CRM_Core_DAO::checkFieldExists('civicrm_contribution_soft', 'amount')
    ) {

      $errorMessage = ts('Database check failed - it looks like you have already upgraded to the latest version (v2.2) of the database.');
      return FALSE;
    }

    if (!CRM_Core_DAO::checkTableExists('civicrm_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_group_contact_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_menu') ||
      !CRM_Core_DAO::checkTableExists('civicrm_discount') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge_block') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge_payment')
    ) {
      $errorMessage .= ' Few important tables were found missing.';
      return FALSE;
    }

    // check fields which MUST be present if a proper 2.1 db
    if (!CRM_Core_DAO::checkFieldExists('civicrm_cache', 'group_name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_cache', 'created_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_cache', 'expired_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_discount', 'option_group_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_discount', 'end_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_group_contact_cache', 'contact_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'path_arguments') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'is_exposed') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'page_type') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'component_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'name')
    ) {
      // db looks to have stuck somewhere between 2.0 & 2.1
      $errorMessage .= ' Few important fields were found missing in some of the tables.';
      return FALSE;
    }

    return TRUE;
  }

  function upgrade() {
    $currentDir = dirname(__FILE__);

    $sqlFile = implode(DIRECTORY_SEPARATOR,
      array($currentDir, '../sql', 'two_one_two.mysql')
    );
    $this->source($sqlFile);

    // CRM-3707, Price Set Export has zeros in all columns
    $query = "SELECT distinct(price_field_id) FROM civicrm_line_item";
    $lineItem = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($lineItem->fetch()) {
      $grpName = "civicrm_price_field.amount." . $lineItem->price_field_id;
      $query   = "SELECT id FROM civicrm_option_group WHERE name='$grpName'";
      $optGrp  = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      if ($optGrp->fetch()) {
        // update line_item table
        $query = "UPDATE civicrm_line_item SET option_group_id={$optGrp->id} WHERE price_field_id={$lineItem->price_field_id}";
        CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      }
    }

    // CRM-3796, fix null values of fee amount in participant table
    $query = '
UPDATE civicrm_participant,civicrm_option_group, civicrm_option_value, civicrm_event_page, civicrm_event
SET civicrm_participant.fee_amount = civicrm_option_value.value
WHERE civicrm_option_value.option_group_id = civicrm_option_group.id
AND civicrm_event_page.id = substring( civicrm_option_group.name FROM 27 )
AND civicrm_option_group.name LIKE "civicrm_event_page.amount.%"
AND civicrm_participant.event_id = civicrm_event.id
AND civicrm_event.id = civicrm_event_page.event_id
AND civicrm_option_value.label = civicrm_participant.fee_level
';

    CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    $this->setVersion($this->latestVersion);
  }

  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPostDBState(&$errorMessage) {
    $errorMessage = ts('Post-condition failed for upgrade to 2.1.2.');
    return $this->checkVersion($this->latestVersion);
  }
}

