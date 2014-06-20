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
class CRM_Upgrade_ThreeZero_ThreeZero extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $latestVer = CRM_Utils_System::version();

    $errorMessage = ts('Pre-condition failed for upgrade to %1.', array(1 => $latestVer));
    // check table, if the db is 3.0
    if (CRM_Core_DAO::checkTableExists('civicrm_navigation') &&
      CRM_Core_DAO::checkTableExists('civicrm_participant_status_type')
    ) {
      $errorMessage = ts("Database check failed - it looks like you have already upgraded to the latest version (v%1) of the database. OR If you think this message is wrong, it is very likely that this a partially upgraded db and you will need to reload the correct db on which upgrade was never tried.", array(1 => $latestVer));
      return FALSE;
    }
    // check table-column, if the db is 3.0
    if (CRM_Core_DAO::checkFieldExists('civicrm_menu', 'domain_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'created_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_template') &&
      CRM_Core_DAO::checkFieldExists('civicrm_uf_field', 'is_reserved') &&
      CRM_Core_DAO::checkFieldExists('civicrm_contact', 'email_greeting_id') &&
      CRM_Core_DAO::checkFieldExists('civicrm_payment_processor_type', 'payment_type')
    ) {

      $errorMessage = ts("Database check failed - it looks like you have already upgraded to the latest version (v%1) of the database. OR If you think this message is wrong, it is very likely that this a partially upgraded db and you will need to reload the correct db on which upgrade was never tried.", array(1 => $latestVer));
      return FALSE;
    }

    //check previous version table e.g 2.2.*
    if (!CRM_Core_DAO::checkTableExists('civicrm_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pcp_block') ||
      !CRM_Core_DAO::checkTableExists('civicrm_menu') ||
      !CRM_Core_DAO::checkTableExists('civicrm_discount') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pcp') ||
      !CRM_Core_DAO::checkTableExists('civicrm_pledge_block') ||
      !CRM_Core_DAO::checkTableExists('civicrm_contribution_soft')
    ) {

      $errorMessage .= ' Few important tables were found missing.';
      return FALSE;
    }

    // check fields which MUST be present if a proper 2.2.* db
    if (!CRM_Core_DAO::checkFieldExists('civicrm_activity', 'due_date_time') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_contact', 'greeting_type_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_contribution', 'check_number')
    ) {
      // db looks to have stuck somewhere between 2.1 & 2.2
      $errorMessage .= ' Few important fields were found missing in some of the tables.';
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $rev
   */
  function upgrade($rev) {

    // fix CRM-5270: if civicrm_report_instance.description is localised,
    // recreate it based on the first locale’s description_xx_YY contents
    // and drop all the description_xx_YY columns
    if (!CRM_Core_DAO::checkFieldExists('civicrm_report_instance', 'description')) {
      $domain = new CRM_Core_DAO_Domain;
      $domain->find(TRUE);
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_report_instance ADD description VARCHAR(255)");
      CRM_Core_DAO::executeQuery("UPDATE civicrm_report_instance SET description = description_{$locales[0]}");

      CRM_Core_DAO::executeQuery("DROP TRIGGER IF EXISTS civicrm_report_instance_before_insert");
      foreach ($locales as $locale) {
        CRM_Core_DAO::executeQuery("DROP VIEW civicrm_report_instance_$locale");
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_report_instance DROP description_$locale");
      }
    }

    //We execute some part of php after sql and then again sql
    //So using conditions for skipping some part of sql CRM-4575

    $upgrade = new CRM_Upgrade_Form();
    //Run the SQL file (1)
    $upgrade->processSQL($rev);
    //replace  with ; in report instance
    $sql = "UPDATE civicrm_report_instance
                       SET form_values = REPLACE(form_values,'#',';') ";
    CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    //delete unnecessary activities
    $bulkEmailID = CRM_Core_OptionGroup::getValue('activity_type', 'Bulk Email', 'name');

    if ($bulkEmailID) {

      $mailingActivityIds = array();
      $query = "
            SELECT max( ca.id ) as aid,
                   ca.source_record_id sid
            FROM civicrm_activity ca
            WHERE ca.activity_type_id = %1
            GROUP BY ca.source_record_id";

      $params = array(1 => array($bulkEmailID, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);

      while ($dao->fetch()) {
        $updateQuery = "
                UPDATE civicrm_activity_target cat, civicrm_activity ca
                    SET cat.activity_id = {$dao->aid}
                WHERE ca.source_record_id IS NOT NULL   AND
                      ca.activity_type_id = %1          AND
                      ca.id <> {$dao->aid}              AND
                      ca.source_record_id = {$dao->sid} AND
                      ca.id = cat.activity_id";

        $updateParams = array(1 => array($bulkEmailID, 'Integer'));
        CRM_Core_DAO::executeQuery($updateQuery, $updateParams);

        $deleteQuery = "
                DELETE ca.*
                FROM civicrm_activity ca
                WHERE ca.source_record_id IS NOT NULL  AND
                      ca.activity_type_id = %1         AND
                      ca.id <> {$dao->aid}             AND
                      ca.source_record_id = {$dao->sid}";

        $deleteParams = array(1 => array($bulkEmailID, 'Integer'));
        CRM_Core_DAO::executeQuery($deleteQuery, $deleteParams);
      }
    }

    //CRM-4453
    //lets insert column in civicrm_aprticipant table
    $query = "
        ALTER TABLE `civicrm_participant`
            ADD `fee_currency` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '3 character string, value derived from config setting.' AFTER `discount_id`";
    CRM_Core_DAO::executeQuery($query);

    //get currency from contribution table if exists/default
    //insert currency when fee_amount != NULL or event is paid.
    $query = "
        SELECT  civicrm_participant.id
        FROM    civicrm_participant
            LEFT JOIN  civicrm_event
                   ON ( civicrm_participant.event_id = civicrm_event.id )
        WHERE  civicrm_participant.fee_amount IS NOT NULL OR
               civicrm_event.is_monetary = 1";

    $participant = CRM_Core_DAO::executeQuery($query);
    while ($participant->fetch()) {
      $query = "
            SELECT civicrm_contribution.currency
            FROM   civicrm_contribution,
                   civicrm_participant_payment
            WHERE  civicrm_contribution.id = civicrm_participant_payment.contribution_id AND
                   civicrm_participant_payment.participant_id = {$participant->id}";

      $currencyID = CRM_Core_DAO::singleValueQuery($query);
      if (!$currencyID) {
        $config = CRM_Core_Config::singleton();
        $currencyID = $config->defaultCurrency;
      }

      //finally update participant record.
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $participant->id, 'fee_currency', $currencyID);
    }

    //CRM-4575
    //check whether {contact.name} is set in mailing labels
    $mailingFormat = self::getPreference('mailing_format');
    $addNewAddressee = TRUE;

    if (strpos($mailingFormat, '{contact.contact_name}') === FALSE) {
      $addNewAddressee = FALSE;
    }
    else {
      //else compare individual name format with default individual addressee.
      $individualNameFormat = self::getPreference('individual_name_format');

      $defaultAddressee = CRM_Core_OptionGroup::values('addressee', FALSE, FALSE, FALSE,
        " AND v.filter = 1 AND v.is_default =  1", 'label'
      );

      if (array_search($individualNameFormat, $defaultAddressee) !== FALSE) {
        $addNewAddressee = FALSE;
      }
    }

    $docURL = CRM_Utils_System::docURL2('Update Greetings and Address Data for Contacts', FALSE, NULL, NULL, 'color: white; text-decoration: underline;', "wiki");

    if ($addNewAddressee) {
      //otherwise insert new token in addressee and set as a default
      $addresseeGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
        'addressee',
        'id',
        'name'
      );

      $optionValueParams = array(
        'label' => $individualNameFormat,
        'is_active' => 1,
        'contactOptions' => 1,
        'filter' => 1,
        'is_default' => 1,
        'reset_default_for' => array('filter' => "0, 1"),
      );

      $action = CRM_Core_Action::ADD;
      $addresseeGroupParams = array('name' => 'addressee');
      $fieldValues = array('option_group_id' => $addresseeGroupId);
      $weight = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);

      $optionValueParams['weight'] = $weight;
      $addresseeToken = CRM_Core_OptionValue::addOptionValue($optionValueParams, $addresseeGroupParams,
        $action, $optionId = NULL
      );

      $afterUpgradeMessage = ts("During this upgrade, Postal Addressee values have been stored for each contact record using the system default format - %2.You will need to run the included command-line script to update your Individual contact records to use the \"Individual Name Format\" previously specified for your site %1", array(1 => $docURL, 2 => array_pop($defaultAddressee)));
    }
    else {
      $afterUpgradeMessage = ts("Email Greeting, Postal Greeting and Postal Addressee values have been stored for all contact records based on the system default formats. If you want to use a different format for any of these contact fields - you can run the provided command line script to update contacts to a different format %1 ", array(1 => $docURL));
    }

    //replace contact.contact_name with contact.addressee in civicrm_preference.mailing_format
    $updateQuery = "
        UPDATE civicrm_preferences
               SET `mailing_format` =
                    replace(`mailing_format`, '{contact.contact_name}','{contact.addressee}')";

    CRM_Core_DAO::executeQuery($updateQuery);

    //drop column individual_name_format
    $alterQuery = "
        ALTER TABLE `civicrm_preferences`
              DROP `individual_name_format`";

    CRM_Core_DAO::executeQuery($alterQuery);

    //set status message for default greetings
    $template = CRM_Core_Smarty::singleton();
    $template->assign('afterUpgradeMessage', $afterUpgradeMessage);
  }

  /**
   * Load a preference
   *
   * This is replaces the defunct CRM_Core_BAO_Preferences::value()
   */
  static
  function getPreference($name) {
    $sql = "SELECT $name FROM civicrm_preferences WHERE domain_id = %1 AND is_domain = 1 AND contact_id IS NULL";
    $params = array(
      1 => array(CRM_Core_Config::domainID(), 'Integer'),
    );
    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }
}

