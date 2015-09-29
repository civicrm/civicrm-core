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
 * $Id$
 *
 */
/*
 * Settings metadata file
 */

return array(
  'profile_double_optin' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'profile_double_optin',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => '1',
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => NULL,
  ),
  'track_civimail_replies' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'track_civimail_replies',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '4.1',
    'title' => 'Track replies using VERP in Reply-To header',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If checked, mailings will default to tracking replies using VERP-ed Reply-To. ',
    'help_text' => NULL,
    'validate_callback' => 'CRM_Core_BAO_Setting::validateBoolSetting',
  ),
  'civimail_workflow' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'civimail_workflow',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '4.1',
    'title' => 'Use CiviMail Workflow',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => NULL,
  ),
  'civimail_server_wide_lock' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'civimail_server_wide_lock',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '4.1',
    'title' => 'Lock Mails Server-Wide for Mail Sending',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'replyTo' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'replyTo',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 0,
    'add' => '4.6',
    'title' => 'Enable Custom Reply-To',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Allow CiviMail users to send mailings with a custom Reply-To header',
    'help_text' => NULL,
  ),
  'mailing_backend' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailing_backend',
    'type' => 'Array',
    'html_type' => 'checkbox',
    'default' => array('outBound_option' => '3'),
    'add' => '4.1',
    'title' => 'Mailing Backend',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'profile_add_to_group_double_optin' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'profile_add_to_group_double_optin',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => '0',
    'add' => '4.1',
    'title' => 'Enable Double Opt-in for Profile Group(s) field',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'When CiviMail is enabled, users who "subscribe" to a group from a profile Group(s) checkbox will receive a confirmation email. They must respond (opt-in) before they are added to the group.',
    'help_text' => NULL,
  ),
  'disable_mandatory_tokens_check' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'disable_mandatory_tokens_check',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.4',
    'title' => 'Disable check for mandatory tokens',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Don\'t check for presence of mandatory tokens (domain address; unsubscribe/opt-out) before sending mailings. WARNING: Mandatory tokens are a safe-guard which facilitate compliance with the US CAN-SPAM Act. They should only be disabled if your organization adopts other mechanisms for compliance or if your organization is not subject to CAN-SPAM.',
    'help_text' => NULL,
  ),
  'dedupe_email_default' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'dedupe_email_default',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 1,
    'add' => '4.5',
    'title' => 'CiviMail dedupes e-mail addresses by default',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Set the "dedupe e-mail" option when sending a new mailing to "true" by default.',
    'help_text' => NULL,
  ),
  'hash_mailing_url' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'hash_mailing_url',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.5',
    'title' => 'Hashed Mailing URL\'s',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If enabled, a randomized hash key will be used to reference the mailing URL in the mailing.viewUrl token, instead of the mailing ID',
    'help_text' => NULL,
  ),
  'civimail_multiple_bulk_emails' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'civimail_multiple_bulk_emails',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => 0,
    'add' => '4.5',
    'title' => ' Multiple Bulk Emails',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If enabled, CiviMail will deliver a copy of the email to each bulk email listed for the contact.',
    'help_text' => NULL,
  ),
  'include_message_id' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'include_message_id',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'default' => FALSE,
    'add' => '4.5',
    'title' => 'Enable CiviMail to generate Message-ID header',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'help_text' => NULL,
  ),
  'mailerBatchLimit' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailerBatchLimit',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
      'maxlength' => 8,
    ),
    'default' => 0,
    'add' => '4.7',
    'title' => 'Mailer Batch Limit',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Throttle email delivery by setting the maximum number of emails sent during each CiviMail run (0 = unlimited).',
    'help_text' => NULL,
  ),
  'mailerJobSize' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailerJobSize',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
      'maxlength' => 8,
    ),
    'default' => 0,
    'add' => '4.7',
    'title' => 'Mailer Job Size',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If you want to utilize multi-threading enter the size you want your sub jobs to be split into. Recommended values are between 1,000 and 10,000. Use a lower value if your server has multiple cron jobs running simultaneously, but do not use values smaller than 1,000. Enter "0" to disable multi-threading and process mail as one single job - batch limits still apply.',
    'help_text' => NULL,
  ),
  'mailerJobsMax' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailerJobsMax',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
      'maxlength' => 8,
    ),
    'default' => 0,
    'add' => '4.7',
    'title' => 'Mailer Cron Job Limit',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'The maximum number of mailer delivery jobs executing simultaneously (0 = allow as many processes to execute as started by cron)',
    'help_text' => NULL,
  ),
  'mailThrottleTime' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailThrottleTime',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
      'maxlength' => 8,
    ),
    'default' => 0,
    'add' => '4.7',
    'title' => 'Mailer Throttle Time',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'The time to sleep in between each e-mail in micro seconds. Setting this above 0 allows you to control the rate at which e-mail messages are sent to the mail server, avoiding filling up the mail queue very quickly. Set to 0 to disable.',
    'help_text' => NULL,
  ),
  'verpSeparator' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'verpSeparator',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => array(
      'size' => 4,
      'maxlength' => 32,
    ),
    'default' => '.',
    'add' => '4.7',
    'title' => 'VERP Separator',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Separator character used when CiviMail generates VERP (variable envelope return path) Mail-From addresses.',
    'help_text' => NULL,
  ),
  'write_activity_record' => array(
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'write_activity_record',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '1',
    'add' => '4.7',
    'title' => 'Enable CiviMail to create activities on delivery',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
);
