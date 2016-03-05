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
 * A module is any software package that participates in the hook
 * system, such as CiviCRM Module-Extension, a Drupal Module, or
 * a Joomla Plugin.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Module {

  /**
   * @var string
   */
  public $name;

  /**
   * @var bool, TRUE if fully enabled; FALSE if module exists but is disabled
   */
  public $is_active;

  /**
   * @param string $name
   * @param $is_active
   */
  public function __construct($name, $is_active) {
    $this->name = $name;
    $this->is_active = $is_active;
  }

  /**
   * Get a list of all known modules.
   *
   * @param bool $fresh
   *   Force new results?
   *
   * @return array
   */
  public static function getAll($fresh = FALSE) {
    static $result;
    if ($fresh || !is_array($result)) {
      $result = CRM_Extension_System::singleton()->getMapper()->getModules();
      $result[] = new CRM_Core_Module('civicrm', TRUE); // pseudo-module for core

      $config = CRM_Core_Config::singleton();
      $result = array_merge($result, $config->userSystem->getModules());
    }
    return $result;
  }

  /**
   * Get the status for each module.
   *
   * @param array $modules
   *   Array(CRM_Core_Module).
   * @return array
   *   Array(string $moduleName => string $statusCode).
   * @see CRM_Extension_Manager::STATUS_INSTALLED
   * @see CRM_Extension_Manager::STATUS_DISABLED
   */
  public static function collectStatuses($modules) {
    $statuses = array();
    foreach ($modules as $module) {
      $statuses[$module->name] = $module->is_active ? CRM_Extension_Manager::STATUS_INSTALLED : CRM_Extension_Manager::STATUS_DISABLED;

    }
    return $statuses;
  }

}
