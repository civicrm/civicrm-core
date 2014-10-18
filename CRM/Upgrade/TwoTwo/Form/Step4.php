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
class CRM_Upgrade_TwoTwo_Form_Step4 extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade step %1.', array(1 => '4'));

    if (CRM_Core_DAO::checkTableExists('civicrm_event_page')) {
      return FALSE;
    }

    // check fields which MUST be present if a proper 2.2 db
    if (!CRM_Core_DAO::checkFieldExists('civicrm_event', 'intro_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_title') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_email_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_email_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_from_name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'confirm_from_email') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'cc_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'bcc_confirm') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'default_fee_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'default_discount_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_title') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'thankyou_footer_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_pay_later') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'pay_later_text') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'pay_later_receipt') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_event', 'is_multiple_registrations')
    ) {
      // db looks to have stuck somewhere between 2.1 & 2.2
      $errorMessage .= ' Few important fields were found missing in some of the tables.';
      return FALSE;
    }
    if ($this->checkVersion('2.1.103')) {
      $this->setVersion('2.2');
    }
    else {
      return FALSE;
    }

    // update config defaults
    $domain = new CRM_Core_DAO_Domain();
    $domain->selectAdd();
    $domain->selectAdd('config_backend');
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $defaults = unserialize($domain->config_backend);
      // reset components
      $defaults['enableComponents'] = array('CiviContribute', 'CiviPledge', 'CiviMember', 'CiviEvent', 'CiviMail');
      $defaults['enableComponentIDs'] = array(1, 6, 2, 3, 4);
      $defaults['moneyvalueformat'] = '%!i';
      $defaults['fieldSeparator'] = ',';
      $defaults['fatalErrorTemplate'] = 'CRM/common/fatal.tpl';
      // serialise settings
      CRM_Core_BAO_ConfigSetting::add($defaults);
    }

    return $this->checkVersion('2.2');
  }

  function buildQuickForm() {}

  /**
   * @return string
   */
  function getTitle() {
    return ts('Database Upgrade to v2.2 Completed');
  }

  /**
   * @return string
   */
  function getTemplateMessage() {
    $upgradeDoc = CRM_Utils_System::docURL2('Installation and Upgrades', TRUE, NULL, NULL, NULL, "wiki");
    return '<p><strong>' . ts('Your CiviCRM database has been successfully upgraded to v2.2.') . '</strong></p><p>' . ts('Please be sure to follow the remaining steps in the upgrade instructions specific to your version of CiviCRM: %1.', array(
      1 => $upgradeDoc)) . '</p><p>' . ts('Thank you for using CiviCRM.') . '</p>';
  }
}

