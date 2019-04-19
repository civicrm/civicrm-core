<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

namespace Civi\Core\SqlTrigger;

/**
 * Build a set of simple, literal SQL triggers.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class StaticTriggers {

  /**
   * @var array
   *   A list of triggers, in the same format as hook_civicrm_triggerInfo.
   *   Additionally, you may specify `upgrade_check` to ensure that the trigger
   *   is *not* installed during early upgrade steps (before key dependencies are met).
   *
   *   Ex:  $triggers[0]['upgrade_check'] = array('table' => 'civicrm_case', 'column'=> 'modified_date');
   *
   * @see \CRM_Utils_Hook::triggerInfo
   */
  private $triggers;

  /**
   * StaticTriggers constructor.
   * @param $triggers
   */
  public function __construct($triggers) {
    $this->triggers = $triggers;
  }

  /**
   * Add our list of triggers to the global list.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::triggerInfo
   */
  public function onTriggerInfo($e) {
    $this->alterTriggerInfo($e->info, $e->tableName);
  }

  /**
   * Add our list of triggers to the global list.
   *
   * @see \CRM_Utils_Hook::triggerInfo
   * @see \CRM_Core_DAO::triggerRebuild
   *
   * @param array $info
   *   See hook_civicrm_triggerInfo.
   * @param string|NULL $tableFilter
   *   See hook_civicrm_triggerInfo.
   */
  public function alterTriggerInfo(&$info, $tableFilter = NULL) {
    foreach ($this->getTriggers() as $trigger) {
      if ($tableFilter !== NULL) {
        // Because sadism.
        if (in_array($tableFilter, (array) $trigger['table'])) {
          $trigger['table'] = $tableFilter;
        }
      }

      if (\CRM_Core_Config::isUpgradeMode() && isset($trigger['upgrade_check'])) {
        $uc = $trigger['upgrade_check'];
        if (!\CRM_Core_BAO_SchemaHandler::checkIfFieldExists($uc['table'], $uc['column'])
        ) {
          continue;
        }
      }
      unset($trigger['upgrade_check']);
      $info[] = $trigger;
    }
  }

  /**
   * @return mixed
   */
  public function getTriggers() {
    return $this->triggers;
  }

  /**
   * @param mixed $triggers
   * @return StaticTriggers
   */
  public function setTriggers($triggers) {
    $this->triggers = $triggers;
    return $this;
  }

  /**
   * @param $trigger
   * @return StaticTriggers
   */
  public function addTrigger($trigger) {
    $this->triggers[] = $trigger;
    return $this;
  }

}
