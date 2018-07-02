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
      ));
  }

  if ($type == 'event') {
    return $default + array(
      'civicrm_event_type' => array(
        'type' => 'list<integer>',
        'label' => t('CiviCRM event type'),
        'options list' => 'civicrm_rules_get_event_type_options',
      ));
  }

  return $default;
}
