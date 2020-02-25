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
require_once 'civicrm_rules_utils.inc';
require_once 'civicrm_rules.contact-eval.inc';
require_once 'civicrm_rules.mailing-eval.inc';
require_once 'civicrm_rules.event-eval.inc';
require_once 'civicrm_rules.participant-eval.inc';

/**
 * Implements hook_rules_file_info().
 */
function civicrm_rules_rules_file_info() {
  return array(
    'civicrm_rules.contact-eval',
    'civicrm_rules.mailing-eval',
    'civicrm_rules.event-eval',
    'civicrm_rules.participant-eval',
  );
}

/**
 * Implements hook_rules_event_info().
 */
function civicrm_rules_rules_event_info() {
  require_once 'civicrm_rules_event.inc';
  return civicrm_rules_get_event();
}

function civicrm_rules_rules_condition_info() {
  require_once 'civicrm_rules_condition.inc';
  return civicrm_rules_get_condition();
}

/**
 * CiviCRM integration access callback.
 */
function civicrm_rules_rules_integration_access($type, $name) {
  if ($type == 'event' || $type == 'condition') {
    return user_access('access CiviCRM');
  }
}

/**
 * CiviCRM integration admin access callback.
 */
function civicrm_rules_rules_admin_access() {
  return user_access('administer CiviCRM');
}

/**
 * Implements hook_rules_action_info().
 */
function civicrm_rules_rules_action_info() {
  require_once 'civicrm_rules_action.inc';
  return civicrm_rules_get_action();
}

/**
 * Implements hook_rules_data_type_info().
 */
function civicrm_rules_rules_data_info() {
  require_once 'civicrm_rules_entity.inc';
  return civicrm_rules_get_entity();
}
