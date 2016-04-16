<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

/*
 * Heavily based on anonymous code contribution. No claim.
 *
 * civicrm_handler_field_event_type.inc
 * Display's Event Type after grabbing the label from the database
 *
 */
class civicrm_handler_field_encounter_medium extends views_handler_field {
  static $_encounter_medium;
  function construct() {
    parent::construct();
    if (!self::$_encounter_medium) {
      if (!civicrm_initialize()) {
        return;
      }
      require_once 'CRM/Core/OptionGroup.php';
      self::$_encounter_medium = CRM_Core_OptionGroup::values('encounter_medium', FALSE);
    }
  }

  function render($values) {
    $sid = $values->{$this->field_alias};
    if (empty($sid) ||
      (int ) $sid <= 0
    ) {
      return NULL;
    }
    return self::$_encounter_medium[$values->{$this->field_alias}];
  }
}

