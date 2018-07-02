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
 * $Id$
 *
 */

/**
 * Implements hook_rules_action_info().
 */
function civicrm_rules_get_action() {
  civicrm_initialize();
  //get the default from email address.
  require_once 'CRM/Core/OptionGroup.php';
  $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');

  $settings['message'] = '
A new mailing has been scheduled: {mailing.name}

{mailing.viewUrl}
You can review the mail here: {mailing.editUrl}
You can schedule the mail for here: {mailing.scheduleUrl}

The content of the email is:
<pre>
{mailing.html}
</pre>
';
  $settings['from'] = (isset($defaultAddress[1])) ? $defaultAddress[1] : NULL;

  if (!CRM_Utils_Array::value('subject', $settings)) {
    $settings['subject'] = 'New Email: {mailing.subject}';
  }

  $defaults = array(
    'parameter' => array(
      'contact' => array(
        'type' => 'contact',
        'label' => t('Contact'),
        'save' => TRUE,
      ),
      'civicrm_group' => array(
        'type' => 'list<integer>',
        'label' => t('CiviCRM Groups'),
        'options list' => 'civicrm_rules_options_list',
      ),
    ),
    'group' => t('CiviCRM Contact'),
    'access callback' => 'civicrm_rules_rules_admin_access',
  );

  $items['add_to_group'] = $defaults + array(
    'label' => t('Add Contact to CiviCRM Group'),
    'base' => 'civicrm_rules_rules_action_add_to_group',
  );
  $items['remove_from_group'] = $defaults + array(
    'label' => t('Remove Contact from CiviCRM Group'),
    'base' => 'civicrm_rules_rules_action_remove_from_group',
  );

  $defaults = array(
    'parameter' => array(
      'contact' => array(
        'type' => 'contact',
        'label' => t('Contact'),
        'save' => TRUE,
      ),
      'civicrm_tags' => array(
        'type' => 'list<integer>',
        'label' => t('CiviCRM Tags'),
        'options list' => 'civicrm_rules_options_list',
      ),
    ),
    'group' => t('CiviCRM Contact'),
    'access callback' => 'civicrm_rules_rules_admin_access',
  );

  $items['add_tag'] = $defaults + array(
    'label' => t('Add CiviCRM Tag to Contact'),
    'base' => 'civicrm_rules_rules_action_add_tag',
  );
  $items['remove_tag'] = $defaults + array(
    'label' => t('Remove CiviCRM Tag from Contact'),
    'base' => 'civicrm_rules_rules_action_remove_tag',
  );

  $defaults = array(
    'parameter' => array(
      'to' => array(
        'type' => 'text',
        'label' => t('To'),
        'description' => t('The e-mail address or addresses where the message will be sent to. The formatting of this string must comply with RFC 2822.'),
      ),
      'subject' => array(
        'type' => 'text',
        'label' => t('Subject'),
        'description' => t("The mail's subject."),
      ),
      'message' => array(
        'type' => 'text',
        'label' => t('Message'),
        'description' => t("The mail's message body."),
      ),
      'from' => array(
        'type' => 'text',
        'label' => t('From'),
        'description' => t("The mail's from address. Leave it empty to use the site-wide configured address."),
        'optional' => TRUE,
      ),
    ),
    'group' => t('CiviCRM Contact'),
    'access callback' => 'civicrm_rules_rules_admin_access',
  );

  $items['contact_send_email'] = $defaults + array(
    'label' => t('Send Email'),
    'base' => 'civicrm_rules_rules_action_contact_send_email',
  );

  $defaults = array(
    'parameter' => array(
      'to' => array(
        'type' => 'text',
        'label' => t('To'),
        'description' => t('The e-mail address or addresses where the message will be sent to. The formatting of this string must comply with RFC 2822.'),
      ),
      'subject' => array(
        'type' => 'text',
        'label' => t('Subject'),
        'description' => t("The mail's subject."),
        'default value' => $settings['subject'],
      ),
      'message' => array(
        'type' => 'text',
        'label' => t('Message'),
        'description' => t("The mail's message body."),
        'default value' => $settings['message'],
      ),
      'from' => array(
        'type' => 'text',
        'label' => t('From'),
        'description' => t("The mail's from address. Leave it empty to use the site-wide configured address."),
        'default value' => $settings['from'],
        'optional' => TRUE,
      ),
    ),
    'group' => t('CiviCRM Mailing'),
  );

  $items['mailing_send_email'] = $defaults + array(
    'label' => t('Send Email'),
    'base' => 'civicrm_rules_rules_action_mailing_send_email',
  );
  return $items;
}
