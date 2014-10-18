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
class CRM_Upgrade_ThreeOne_ThreeOne extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $latestVer = CRM_Utils_System::version();

    $errorMessage = ts('Pre-condition failed for upgrade to %1.', array(1 => $latestVer));

    // check tables and table-columns, if the db is already 3.1
    if (CRM_Core_DAO::checkTableExists('civicrm_acl_contact_cache') ||
      CRM_Core_DAO::checkTableExists('civicrm_contact_type') ||
      CRM_Core_DAO::checkTableExists('civicrm_dashboard') ||
      CRM_Core_DAO::checkTableExists('civicrm_dashboard_contact') ||
      CRM_Core_DAO::checkFieldExists('civicrm_country', 'is_province_abbreviated') ||
      CRM_Core_DAO::checkFieldExists('civicrm_custom_field', 'date_format') ||
      CRM_Core_DAO::checkFieldExists('civicrm_custom_field', 'time_format') ||
      CRM_Core_DAO::checkFieldExists('civicrm_mail_settings', 'domain_id') ||
      CRM_Core_DAO::checkFieldExists('civicrm_msg_template', 'workflow_id') ||
      CRM_Core_DAO::checkFieldExists('civicrm_msg_template', 'is_default') ||
      CRM_Core_DAO::checkFieldExists('civicrm_msg_template', 'is_reserved') ||
      CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'domain_id') ||
      CRM_Core_DAO::checkFieldExists('civicrm_preferences', 'contact_autocomplete_options') ||
      CRM_Core_DAO::checkFieldExists('civicrm_preferences_date', 'date_format') ||
      CRM_Core_DAO::checkFieldExists('civicrm_preferences_date', 'time_format') ||
      CRM_Core_DAO::checkFieldExists('civicrm_price_set', 'domain_id') ||
      CRM_Core_DAO::checkFieldExists('civicrm_price_set', 'extends') ||
      CRM_Core_DAO::checkFieldExists('civicrm_relationship_type', 'contact_sub_type_a') ||
      CRM_Core_DAO::checkFieldExists('civicrm_relationship_type', 'contact_sub_type_b') ||
      CRM_Core_DAO::checkFieldExists('civicrm_report_instance', 'domain_id')
    ) {
      $errorMessage = ts("Database check failed - it looks like you have already upgraded to the latest version (v%1) of the database. OR If you think this message is wrong, it is very likely that this a partially upgraded database and you will need to reload the correct database from backup on which upgrade was never tried.", array(1 => $latestVer));
      return FALSE;
    }

    //check previous version tables e.g 3.0.*
    if (!CRM_Core_DAO::checkTableExists('civicrm_participant_status_type') ||
      !CRM_Core_DAO::checkTableExists('civicrm_navigation')
    ) {
      $errorMessage .= ' Few important tables were found missing.';
      return FALSE;
    }

    // check fields which MUST be present if a proper 3.0.* db
    if (!CRM_Core_DAO::checkFieldExists('civicrm_contact', 'email_greeting_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_contribution_page', 'created_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_custom_group', 'created_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_template') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'created_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_mailing', 'created_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_mapping_field', 'im_provider_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_membership_type', 'domain_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'domain_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_participant', 'fee_currency') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_payment_processor', 'domain_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_payment_processor_type', 'payment_type') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_preferences', 'domain_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_preferences', 'navigation') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_relationship_type', 'label_a_b') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_report_instance', 'navigation_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_uf_field', 'is_reserved') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_uf_group', 'created_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_uf_match', 'domain_id')
    ) {
      // db looks to have stuck somewhere between 3.0 & 3.1
      $errorMessage .= ' Few important fields were found missing in some of the tables.';
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $rev
   */
  function upgrade($rev) {

    $upgrade = new CRM_Upgrade_Form();

    //Run the SQL file
    $upgrade->processSQL($rev);

    // fix for CRM-5162
    // we need to encrypt all smtpPasswords if present
    $sql = 'SELECT id, mailing_backend FROM civicrm_preferences';
    $mailingDomain = CRM_Core_DAO::executeQuery($sql);
    while ($mailingDomain->fetch()) {
      if ($mailingDomain->mailing_backend) {
        $values = unserialize($mailingDomain->mailing_backend);

        if (isset($values['smtpPassword'])) {
          $values['smtpPassword'] = CRM_Utils_Crypt::encrypt($values['smtpPassword']);

          $updateSql = 'UPDATE civicrm_preferences SET mailing_backend = %1 WHERE id = %2';
          $updateParams = array(
            1 => array(serialize($values), 'String'),
            2 => array($mailingDomain->id, 'Integer'),
          );
          CRM_Core_DAO::executeQuery($updateSql, $updateParams);
        }
      }
    }

    $domain = new CRM_Core_DAO_Domain();
    $domain->selectAdd();
    $domain->selectAdd('config_backend');
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $defaults = unserialize($domain->config_backend);
      if ($dateFormat = CRM_Utils_Array::value('dateformatQfDate', $defaults)) {
        $dateFormatArray = explode(" ", $dateFormat);

        //replace new date format based on previous month format
        //%b month name [abbreviated]
        //%B full month name ('January'..'December')
        //%m decimal number, 0-padded ('01'..'12')

        if ($dateFormat == '%b %d %Y') {
          $defaults['dateInputFormat'] = 'mm/dd/yy';
        }
        elseif ($dateFormat == '%d-%b-%Y') {
          $defaults['dateInputFormat'] = 'dd-mm-yy';
        }
        elseif (in_array('%b', $dateFormatArray)) {
          $defaults['dateInputFormat'] = 'M d, yy';
        }
        elseif (in_array('%B', $dateFormatArray)) {
          $defaults['dateInputFormat'] = 'MM d, yy';
        }
        else {
          $defaults['dateInputFormat'] = 'mm/dd/yy';
        }
      }
      // %p - lowercase ante/post meridiem ('am', 'pm')
      // %P - uppercase ante/post meridiem ('AM', 'PM')
      if ($dateTimeFormat = CRM_Utils_Array::value('dateformatQfDatetime', $defaults)) {
        $defaults['timeInputFormat'] = 2;
        $dateTimeFormatArray = explode(" ", $dateFormat);
        if (in_array('%P', $dateTimeFormatArray) || in_array('%p', $dateTimeFormatArray)) {
          $defaults['timeInputFormat'] = 1;
        }
        unset($defaults['dateformatQfDatetime']);
      }

      unset($defaults['dateformatQfDate']);
      unset($defaults['dateformatTime']);
      CRM_Core_BAO_ConfigSetting::add($defaults);
    }

    $sql = "SELECT id, form_values FROM civicrm_report_instance";
    $instDAO = CRM_Core_DAO::executeQuery($sql);
    while ($instDAO->fetch()) {
      $fromVal = @unserialize($instDAO->form_values);
      foreach ((array)$fromVal as $key => $value) {
        if (strstr($key, '_relative')) {
          $elementName = substr($key, 0, (strlen($key) - strlen('_relative')));

          $fromNamekey = $elementName . '_from';
          $toNamekey = $elementName . '_to';

          $fromNameVal = $fromVal[$fromNamekey];
          $toNameVal = $fromVal[$toNamekey];
          //check 'choose date range' is set
          if ($value == '0') {
            if (CRM_Utils_Date::isDate($fromNameVal)) {
              $fromDate = CRM_Utils_Date::setDateDefaults(CRM_Utils_Date::format($fromNameVal));
              $fromNameVal = $fromDate[0];
            }
            else {
              $fromNameVal = '';
            }

            if (CRM_Utils_Date::isDate($toNameVal)) {
              $toDate = CRM_Utils_Date::setDateDefaults(CRM_Utils_Date::format($toNameVal));
              $toNameVal = $toDate[0];
            }
            else {
              $toNameVal = '';
            }
          }
          else {
            $fromNameVal = '';
            $toNameVal = '';
          }
          $fromVal[$fromNamekey] = $fromNameVal;
          $fromVal[$toNamekey] = $toNameVal;
          continue;
        }
      }

      $fromVal = serialize($fromVal);
      $updateSQL = "UPDATE civicrm_report_instance SET form_values = '{$fromVal}' WHERE id = {$instDAO->id}";
      CRM_Core_DAO::executeQuery($updateSQL);
    }

    $customFieldSQL = "SELECT id, date_format FROM civicrm_custom_field WHERE data_type = 'Date' ";
    $customDAO = CRM_Core_DAO::executeQuery($customFieldSQL);
    while ($customDAO->fetch()) {
      $datePartKey = $dateParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $customDAO->date_format);
      $dateParts = array_combine($datePartKey, $dateParts);

      $year       = CRM_Utils_Array::value('Y', $dateParts);
      $month      = CRM_Utils_Array::value('M', $dateParts);
      $date       = CRM_Utils_Array::value('d', $dateParts);
      $hour       = CRM_Utils_Array::value('h', $dateParts);
      $minute     = CRM_Utils_Array::value('i', $dateParts);
      $timeFormat = CRM_Utils_Array::value('A', $dateParts);

      $newDateFormat = 'mm/dd/yy';
      if ($year && $month && $date) {
        $newDateFormat = 'mm/dd/yy';
      }
      elseif (!$year && $month && $date) {
        $newDateFormat = 'mm/dd';
      }

      $newTimeFormat = 'NULL';
      if ($timeFormat && $hour == 'h') {
        $newTimeFormat = 1;
      }
      elseif ($hour) {
        $newTimeFormat = 2;
      }
      $updateSQL = "UPDATE civicrm_custom_field SET date_format = '{$newDateFormat}', time_format = {$newTimeFormat} WHERE id = {$customDAO->id}";
      CRM_Core_DAO::executeQuery($updateSQL);
    }

    $template = CRM_Core_Smarty::singleton();
    $afterUpgradeMessage = '';
    if ($afterUpgradeMessage = $template->get_template_vars('afterUpgradeMessage')) {
      $afterUpgradeMessage .= "<br/><br/>";
    }
    $afterUpgradeMessage .= ts("Date Input Format has been set to %1 format. If you want to use a different format please check Administer CiviCRM &raquo; Localization &raquo; Date Formats.", array(1 => $defaults['dateInputFormat']));
    $template->assign('afterUpgradeMessage', $afterUpgradeMessage);
  }

  function upgrade_3_1_3() {
    $count        = 0;
    $totalCount   = 0;
    $addressQuery = "
     UPDATE civicrm_address as address
INNER JOIN ( SELECT id, contact_id FROM civicrm_address WHERE is_primary = 1 GROUP BY contact_id HAVING count( id ) > 1 ) as dup_address
         ON ( address.contact_id = dup_address.contact_id AND address.id != dup_address.id )
        SET address.is_primary = 0";
    CRM_Core_DAO::executeQuery($addressQuery);

    $sql = "SELECT ROW_COUNT();";

    if ($count = CRM_Core_DAO::singleValueQuery($sql)) {
      $totalCount += $count;
    }

    $emailQuery = "
    UPDATE civicrm_email as email
INNER JOIN ( SELECT id, contact_id FROM civicrm_email WHERE is_primary = 1 GROUP BY contact_id HAVING count( id ) > 1 ) as dup_email
        ON ( email.contact_id = dup_email.contact_id AND email.id != dup_email.id )
       SET email.is_primary = 0";
    CRM_Core_DAO::executeQuery($emailQuery);

    if ($count = CRM_Core_DAO::singleValueQuery($sql)) {
      $totalCount += $count;
    }

    $phoneQuery = "
    UPDATE civicrm_phone as phone
INNER JOIN ( SELECT id, contact_id FROM civicrm_phone WHERE is_primary = 1 GROUP BY contact_id HAVING count( id ) > 1 ) as dup_phone
        ON ( phone.contact_id = dup_phone.contact_id AND phone.id != dup_phone.id )
       SET phone.is_primary = 0";
    CRM_Core_DAO::executeQuery($phoneQuery);

    if ($count = CRM_Core_DAO::singleValueQuery($sql)) {
      $totalCount += $count;
    }

    $imQuery = "
    UPDATE civicrm_im as im
INNER JOIN ( SELECT id, contact_id FROM civicrm_im WHERE is_primary = 1 GROUP BY contact_id HAVING count( id ) > 1 ) as dup_im
        ON ( im.contact_id = dup_im.contact_id AND im.id != dup_im.id )
       SET im.is_primary = 0";
    CRM_Core_DAO::executeQuery($imQuery);

    if ($count = CRM_Core_DAO::singleValueQuery($sql)) {
      $totalCount += $count;
    }

    $openidQuery = "
    UPDATE civicrm_openid as openid
INNER JOIN ( SELECT id, contact_id FROM civicrm_openid WHERE is_primary = 1 GROUP BY contact_id HAVING count( id ) > 1 ) as dup_openid
        ON ( openid.contact_id = dup_openid.contact_id AND openid.id != dup_openid.id )
       SET openid.is_primary = 0";
    CRM_Core_DAO::executeQuery($openidQuery);

    if ($count = CRM_Core_DAO::singleValueQuery($sql)) {
      $totalCount += $count;
    }

    $afterUpgradeMessage = '';
    if (!empty($totalCount)) {
      $template = CRM_Core_Smarty::singleton();
      $afterUpgradeMessage = $template->get_template_vars('afterUpgradeMessage');
      $afterUpgradeMessage .= "<br/><br/>";
      $afterUpgradeMessage .= ts("%1 records have been updated so that each contact record should contain only one Address, Email, Phone, Instant Messanger and openID as primary.", array(1 => $totalCount));
      $template->assign('afterUpgradeMessage', $afterUpgradeMessage);
    }
  }

  function upgrade_3_1_4() {
    $query = "SELECT id FROM civicrm_payment_processor WHERE payment_processor_type = 'Moneris' LIMIT 1";
    $isMoneris = CRM_Core_DAO::singleValueQuery($query);

    if ($isMoneris) {
      $template            = CRM_Core_Smarty::singleton();
      $afterUpgradeMessage = $template->get_template_vars('afterUpgradeMessage');
      $docURL              = CRM_Utils_System::docURL2('Moneris Configuration Guide', FALSE, 'download and install',
        NULL, 'color: white; text-decoration: underline;', "wiki"
      );

      $afterUpgradeMessage .= "<br/>" . ts("Please %1 mpgClasses.php in packages/Services in order to continue using Moneris payment processor. That file is no longer included in the CiviCRM distribution.", array(1 => $docURL));
      $template->assign('afterUpgradeMessage', $afterUpgradeMessage);
    }
  }
}

