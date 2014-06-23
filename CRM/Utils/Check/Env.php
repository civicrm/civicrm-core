<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * $Id: $
 *
 */
class CRM_Utils_Check_Env {

  /**
   * Run some sanity checks.
   *
   * @return array<CRM_Utils_Check_Message>
   */
  public function checkAll() {
    $messages = array_merge(
      $this->checkMysqlTime(),
      $this->checkDebug(),
      $this->checkOutboundMail()
    );
    return $messages;
  }

  /**
   * Check that the MySQL time settings match the PHP time settings.
   *
   * @return array<CRM_Utils_Check_Message> an empty array, or a list of warnings
   */
  public function checkMysqlTime() {
    $messages = array();

    $phpNow = date('Y-m-d H:i');
    $sqlNow = CRM_Core_DAO::singleValueQuery("SELECT date_format(now(), '%Y-%m-%d %H:%i')");
    if (!CRM_Utils_Time::isEqual($phpNow, $sqlNow, 2.5 * 60)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkMysqlTime',
        ts('Timestamps reported by MySQL (eg "%2") and PHP (eg "%3" ) are mismatched.<br /><a href="%1">Read more about this warning</a>', array(
          1 => CRM_Utils_System::getWikiBaseURL() . 'checkMysqlTime',
          2 => $sqlNow,
          3 => $phpNow,
        )),
        ts('Environment Settings')
      );
    }

    return $messages;
  }

  /**
   * @return array
   */
  public function checkDebug() {
    $messages = array();

    $config = CRM_Core_Config::singleton();
    if ($config->debug) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkDebug',
        ts('Warning: Debug is enabled in <a href="%1">system settings</a>. This should not be enabled on production servers.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/debug', 'reset=1'))),
        ts('Debug Mode')
      );
    }

    return $messages;
  }

  /**
   * @return array
   */
  public function checkOutboundMail() {
    $messages = array();

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'mailing_backend');
    if (($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB
      || (defined('CIVICRM_MAIL_LOG') && CIVICRM_MAIL_LOG)
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MOCK)
    ) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkOutboundMail',
        ts('Warning: Outbound email is disabled in <a href="%1">system settings</a>. Proper settings should be enabled on production servers.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))),

        ts('Outbound Email Settings')
      );
    }

    return $messages;
  }
}
