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
/*
 * Settings metadata file
 */


return [
  'mailing_backend' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'mailing_backend',
    'type' => 'Array',
    'html_type' => 'checkbox',
    'default' => ['outBound_option' => '3'],
    'add' => '4.1',
    'title' => ts('Mailing Backend'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => NULL,
  ],
  'verpSeparator' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'verpSeparator',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => [
      'size' => 4,
      'maxlength' => 32,
    ],
    'default' => '.',
    'add' => '4.7',
    'title' => ts('VERP Separator'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Separator character used when generating VERP (variable envelope return path) Mail-From addresses.'),
    'help_text' => NULL,
    'settings_pages' => ['smtp' => ['weight' => 300]],
  ],
  'simple_mail_limit' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'simple_mail_limit',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => [
      'size' => 4,
      'maxlength' => 8,
    ],
    'default' => 50,
    'title' => ts('Simple mail limit'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('The number of emails sendable via simple mail. Make sure you understand the implications for your spam reputation and legal requirements for bulk emails before editing. As there is some risk both to your spam reputation and the products if this is misused it is a hidden setting.'),
    'help_text' => 'CiviCRM forces users sending more than this number of mails to use CiviMails. CiviMails have additional precautions: not sending to contacts who do not want bulk mail, adding domain name and opt out links. You should familiarise yourself with the law relevant to you on bulk mailings if changing this setting. For the US https://en.wikipedia.org/wiki/CAN-SPAM_Act_of_2003 is a good place to start.',
    'add' => '4.7.25',
    'settings_pages' => ['smtp' => ['weight' => 200]],
  ],
  'allow_mail_from_logged_in_contact' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'allow_mail_from_logged_in_contact',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => 1,
    'title' => ts('Allow mail from logged in contact'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Allow sending email from the logged in contact\'s email address.'),
    'help_text' => ts('CiviCRM allows you to send email from the domain from email addresses and the logged in contact id addresses by default. Disable this if you only want to allow the domain from addresses to be used.'),
    'add' => '4.7.31',
    'settings_pages' => ['smtp' => ['weight' => 10]],
  ],
  'scheduled_reminder_smarty' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'scheduled_reminder_smarty',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'default' => 0,
    'title' => ts('Use Smarty in scheduled reminders'),
    'add' => '5.60',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Controls whether scheduled reminders will attempt to process smarty tokens.'),
    'help_text' => NULL,
    'settings_pages' => ['smtp' => ['weight' => 250]],
  ],
  'smtp_450_is_permanent' => [
    'group_name' => 'Mailing Preferences',
    'group' => 'mailing',
    'name' => 'smtp_450_is_permanent',
    'type' => 'Boolean',
    'html_type' => 'checkbox',
    'quick_form_type' => 'CheckBox',
    'default' => 0,
    'title' => ts('Treat SMTP Error 450 4.1.2 as permanent'),
    'add' => '5.80',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Consider domains that will not resolve (SMTP Error 450 - class 4.1.2 "Domain not found") as permanent failures.'),
    'help_text' => NULL,
    'help' => ['id' => 'smtp_450_is_permanent'],
    'settings_pages' => ['smtp' => ['weight' => 350]],
  ],
];
