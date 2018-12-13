<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for the maling component preferences.
 */
class CRM_Admin_Form_Preferences_Mailing extends CRM_Admin_Form_Preferences {

  protected $_settings = [
    'profile_double_optin' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'profile_add_to_group_double_optin' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'track_civimail_replies' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'civimail_workflow' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'civimail_multiple_bulk_emails' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'civimail_server_wide_lock' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'include_message_id' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'write_activity_record' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'disable_mandatory_tokens_check' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'dedupe_email_default' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'hash_mailing_url' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'auto_recipient_rebuild' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
  ];

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (empty($params['write_activity_record'])) {
      // @todo use the setting onToggle & add an action rather than have specific form handling.
      // see logging setting for eg.
      $existingViewOptions = Civi::settings()->get('contact_view_options');

      $displayValue = CRM_Core_OptionGroup::getValue('contact_view_options', 'CiviMail', 'name');
      $viewOptions = explode(CRM_Core_DAO::VALUE_SEPARATOR, $existingViewOptions);

      if (!in_array($displayValue, $viewOptions)) {
        $existingViewOptions .= $displayValue . CRM_Core_DAO::VALUE_SEPARATOR;

        Civi::settings()->set('contact_view_options', $existingViewOptions);
        CRM_Core_Session::setStatus(ts('We have automatically enabled the Mailings tab for the Contact Summary screens
        so that you can view mailings sent to each contact.'), ts('Saved'), 'success');
      }
    }

    parent::postProcess();
  }

}
