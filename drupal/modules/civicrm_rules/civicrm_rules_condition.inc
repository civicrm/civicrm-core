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
 * $Id$
 *
 */
function civicrm_rules_get_condition() {
  return array(
    'civicrm_rules_condition_mailing_approved' =>
    array(
      'label' => t('Approval Status: Approved'),
      'arguments' => array(
        'approvalstatus' => array(
          'type' => 'mailing',
          'label' => t('Approved'),
        ),
      ),
      'base' => 'civicrm_rules_condition_mailing_approved',
      'group' => 'CiviCRM Mailing',
    ),
    'civicrm_rules_condition_mailing_rejected' =>
    array(
      'label' => t('Approval Status: Rejected'),
      'arguments' => array(
        'approvalstatus' => array(
          'type' => 'mailing',
          'label' => t('Rejected'),
        ),
      ),
      'base' => 'civicrm_rules_condition_mailing_rejected',
      'group' => 'CiviCRM Mailing',
    ),
    'civicrm_rules_condition_participant_role' =>
    array(
      'label' => t('Participant Role'),
      'base' => 'civicrm_rules_condition_participant_role',
      'parameter' => civicrm_rules_condition_parameter(t('Participant Role'), 'participant'),
      'group' => 'CiviCRM participant',
    ),
    'civicrm_rules_condition_event_type' =>
    array(
      'label' => t('Event Type'),
      'base' => 'civicrm_rules_condition_event_type',
      'parameter' => civicrm_rules_condition_parameter(t('Created Event'), 'event'),
      'group' => 'CiviCRM event',
    ),
  );
}

function civicrm_rules_condition_parameter($label, $type = 'contact') {

  $default = array($type => array('type' => $type, 'label' => $label));

  if ($type == 'participant') {
    return $default + array(
      'civicrm_participant_role' => array(
        'type' => 'list<integer>',
        'label' => t('Participant role'),
        'options list' => 'civicrm_rules_get_participant_role_options',
      ),
    );
  }

  if ($type == 'event') {
    return $default + array(
      'civicrm_event_type' => array(
        'type' => 'list<integer>',
        'label' => t('CiviCRM event type'),
        'options list' => 'civicrm_rules_get_event_type_options',
      ),
    );
  }

  return $default;
}
