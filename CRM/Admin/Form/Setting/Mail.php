<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components for CiviMail.
 */
class CRM_Admin_Form_Setting_Mail extends CRM_Admin_Form_Setting {

  protected $_settings = array(
    'replyTo' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerBatchLimit' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobSize' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobsMax' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailThrottleTime' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'verpSeparator' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
  );

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - CiviMail'));
    $check = TRUE;

    // redirect to Administer Section After hitting either Save or Cancel button.
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin', 'reset=1'));

    $this->addFormRule(array('CRM_Admin_Form_Setting_Mail', 'formRule'));

    parent::buildQuickForm($check);
  }

  /**
   * @param $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    $errors = array();

    if (CRM_Utils_Array::value('mailerJobSize', $fields) > 0) {
      if (CRM_Utils_Array::value('mailerJobSize', $fields) < 1000) {
        $errors['mailerJobSize'] = ts('The job size must be at least 1000 or set to 0 (unlimited).');
      }
      elseif (CRM_Utils_Array::value('mailerJobSize', $fields) <
        CRM_Utils_Array::value('mailerBatchLimit', $fields)
      ) {
        $errors['mailerJobSize'] = ts('A job size smaller than the batch limit will negate the effect of the batch limit.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

}
