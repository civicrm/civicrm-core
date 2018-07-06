<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_Check_Component_FinancialTypeAcls extends CRM_Utils_Check_Component {

  public static function checkFinancialAclReport() {
    $messages = array();
    $ftAclSetting = Civi::settings()->get('acl_financial_type');
    $financialAclExtension = civicrm_api3('extension', 'get', array('key' => 'biz.jmaconsulting.financialaclreport'));
    if ($ftAclSetting && (($financialAclExtension['count'] == 1 && $financialAclExtension['status'] != 'Installed') || $financialAclExtension['count'] !== 1)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('CiviCRM will in the future require the extension %1 for CiviCRM Reports to work correctly with the Financial Type ACLs. The extension can be downloaded <a href="%2">here</a>', array(
          1 => 'biz.jmaconsulting.financialaclreport',
          2 => 'https://github.com/JMAConsulting/biz.jmaconsulting.financialaclreport',
        )),
        ts('Extension Missing'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
    }

    return $messages;
  }

}
