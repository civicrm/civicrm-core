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

/**
 * This class generates form components for the mailing component preferences.
 */
class CRM_Admin_Form_Preferences_Mailing extends CRM_Admin_Form_Preferences {

  protected $_settings = [
    'profile_double_optin' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'no_reply_email_address' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
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
    'url_tracking_default' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'open_tracking_default' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'scheduled_reminder_smarty' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'smtp_450_is_permanent' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
  ];

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (empty($params['write_activity_record'])) {
      // @todo use the setting onToggle & add an action rather than have specific form handling.
      // see logging setting for eg.
      $existingViewOptions = Civi::settings()->get('contact_view_options');

      $displayViewOptions = CRM_Core_OptionGroup::values('contact_view_options', TRUE, FALSE, FALSE, NULL, 'name');
      $displayValue = $displayViewOptions['CiviMail'];

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
