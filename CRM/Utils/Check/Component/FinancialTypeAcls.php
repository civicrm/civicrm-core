<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_FinancialTypeAcls extends CRM_Utils_Check_Component {

  /**
   * @return CRM_Utils_Check_Message[]
   * @throws CRM_Core_Exception
   */
  public static function checkFinancialAclReport() {
    $messages = [];
    $ftAclSetting = Civi::settings()->get('acl_financial_type');
    $financialAclExtension = civicrm_api3('extension', 'get', ['key' => 'biz.jmaconsulting.financialaclreport', 'sequential' => 1]);
    if ($ftAclSetting && (($financialAclExtension['count'] == 1 && $financialAclExtension['values'][0]['status'] != 'installed') || $financialAclExtension['count'] !== 1)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('CiviCRM will in the future require the extension %1 for CiviCRM Reports to work correctly with the Financial Type ACLs. The extension can be downloaded <a href="%2">here</a>', [
          1 => 'biz.jmaconsulting.financialaclreport',
          2 => 'https://github.com/JMAConsulting/biz.jmaconsulting.financialaclreport',
        ]),
        ts('Extension Missing'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
    }

    return $messages;
  }

}
