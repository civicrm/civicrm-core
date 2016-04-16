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
require_once 'civicrm_rules_utils.inc';
require_once 'civicrm_rules.contact-eval.inc';
require_once 'civicrm_rules.mailing-eval.inc';
require_once 'civicrm_rules.event-eval.inc';
require_once 'civicrm_rules.participant-eval.inc';

/**
 * Implements hook_rules_file_info() on behalf of the user module.
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
 * Implementation of hook_rules_event_info().
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
 * Implementation of hook_rules_action_info().
 */
function civicrm_rules_rules_action_info() {
  require_once 'civicrm_rules_action.inc';
  return civicrm_rules_get_action();
}

/**
 * Implementation of hook_rules_data_type_info().
 */
function civicrm_rules_rules_data_info() {
  require_once 'civicrm_rules_entity.inc';
  return civicrm_rules_get_entity();
}

