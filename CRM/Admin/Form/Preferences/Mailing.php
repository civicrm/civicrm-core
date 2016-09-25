<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id: Display.php 36505 2011-10-03 14:19:56Z lobo $
 *
 */

/**
 * This class generates form components for the component preferences
 *
 */
class CRM_Admin_Form_Preferences_Mailing extends CRM_Admin_Form_Preferences {
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviMail Component Settings'));
    $this->_varNames = array(
      CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME => array(
        'profile_double_optin' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable Double Opt-in for Profile Group(s) field'),
          'weight' => 1,
          'description' => ts('When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.'),
        ),
        'profile_add_to_group_double_optin' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable Double Opt-in for Profiles which use the "Add to Group" setting'),
          'weight' => 2,
          'description' => ts('When CiviMail is enabled and a profile uses the "Add to Group" setting, users who complete the profile form will receive a confirmation email. They must respond (opt-in) before they are added to the group.'),
        ),
        'track_civimail_replies' => array(
          'html_type' => 'checkbox',
          'title' => ts('Track replies using VERP in Reply-To header'),
          'weight' => 3,
          'description' => ts('If checked, mailings will default to tracking replies using VERP-ed Reply-To.'),
        ),
        'civimail_workflow' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable workflow support for CiviMail'),
          'weight' => 4,
          'description' => ts('Drupal-only. Rules module must be enabled (beta feature - use with caution).'),
        ),
        'civimail_multiple_bulk_emails' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable multiple bulk email address for a contact.'),
          'weight' => 5,
          'description' => ts('CiviMail will deliver a copy of the email to each bulk email listed for the contact.'),
        ),
        'civimail_server_wide_lock' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable global server wide lock for CiviMail'),
          'weight' => 6,
          'description' => NULL,
        ),
        'include_message_id' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable CiviMail to generate Message-ID header'),
          'weight' => 7,
          'description' => NULL,
        ),
        'write_activity_record' => array(
          'html_type' => 'checkbox',
          'title' => ts('Enable CiviMail to create activities on delivery'),
          'weight' => 8,
          'description' => NULL,
        ),
        'disable_mandatory_tokens_check' => array(
          'html_type' => 'checkbox',
          'title' => ts('Disable check for mandatory tokens'),
          'weight' => 9,
          'description' => ts('Don\'t check for presence of mandatory tokens (domain address; unsubscribe/opt-out) before sending mailings. WARNING: Mandatory tokens are a safe-guard which facilitate compliance with the US CAN-SPAM Act. They should only be disabled if your organization adopts other mechanisms for compliance or if your organization is not subject to CAN-SPAM.'),
        ),
        'dedupe_email_default' => array(
          'html_type' => 'checkbox',
          'title' => ts('CiviMail dedupes e-mail addresses by default'),
          'weight' => 10,
          'description' => NULL,
        ),
        'hash_mailing_url' => array(
          'html_type' => 'checkbox',
          'title' => ts('Hashed Mailing URL\'s'),
          'weight' => 11,
          'description' => 'If enabled, a randomized hash key will be used to reference the mailing URL in the mailing.viewUrl token, instead of the mailing ID',
        ),
      ),
    );

    parent::preProcess();
  }

  public function postProcess() {
    // check if mailing tab is enabled, if not prompt user to enable the tab if "write_activity_record" is disabled
    $params = $this->controller->exportValues($this->_name);

    if (empty($params['write_activity_record'])) {
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
