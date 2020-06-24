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
 * A module is any software package that participates in the hook
 * system, such as CiviCRM Module-Extension, a Drupal Module, or
 * a Joomla Plugin.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Module {

  /**
   * @var string
   */
  public $name;

  /**
   * Is the module enabled.
   *
   * @var bool
   */
  public $is_active;

  /**
   * Class constructor.
   *
   * @param string $name
   * @param bool $is_active
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
      // pseudo-module for core
      $result[] = new CRM_Core_Module('civicrm', TRUE);

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
    $statuses = [];
    foreach ($modules as $module) {
      $statuses[$module->name] = $module->is_active ? CRM_Extension_Manager::STATUS_INSTALLED : CRM_Extension_Manager::STATUS_DISABLED;

    }
    return $statuses;
  }

}
