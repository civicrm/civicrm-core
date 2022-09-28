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

use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;

/**
 * This class stores logic for managing schema upgrades in CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Upgrades {

  const QUEUE_NAME = 'ext-upgrade';

  /**
   * Determine whether any extensions have pending upgrades.
   *
   * @return bool
   */
  public static function hasPending() {
    $hasTrue = function($checks) {
      if (is_array($checks)) {
        foreach ($checks as $check) {
          if ($check) {
            return TRUE;
          }
        }
      }
      return FALSE;
    };

    foreach (self::getActiveUpgraders() as $upgrader) {
      /** @var \CRM_Extension_Upgrader_Interface $upgrader */
      if ($hasTrue($upgrader->notify('upgrade', ['check']))) {
        return TRUE;
      }
    }

    $checks = CRM_Utils_Hook::upgrade('check');
    return $hasTrue($checks);
  }

  /**
   * Fill a queue with upgrade tasks.
   *
   * @return CRM_Queue_Queue
   */
  public static function createQueue() {
    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => self::QUEUE_NAME,
      'reset' => TRUE,
    ]);
    return static::fillQueue($queue);
  }

  /**
   * @param \CRM_Queue_Queue $queue
   *
   * @return \CRM_Queue_Queue
   */
  public static function fillQueue(CRM_Queue_Queue $queue): CRM_Queue_Queue {
    foreach (self::getActiveUpgraders() as $upgrader) {
      /** @var \CRM_Extension_Upgrader_Interface $upgrader */
      $upgrader->notify('upgrade', ['enqueue', $queue]);
    }

    CRM_Utils_Hook::upgrade('enqueue', $queue);

    // dev/core#1618 When Extension Upgrades are run reconcile log tables
    $task = new CRM_Queue_Task(
      [__CLASS__, 'upgradeLogTables'],
      [],
      ts('Update log tables')
    );
    // Set weight low so that it will be run last.
    $queue->createItem($task, -2);

    return $queue;
  }

  /**
   * Update log tables following execution of extension upgrades
   */
  public static function upgradeLogTables() {
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  /**
   * @return array
   *   Array(string $extKey => CRM_Extension_Upgrader_Interface $upgrader)
   */
  protected static function getActiveUpgraders() {
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    $keys = self::getActiveKeys();

    $upgraders = [];
    foreach ($keys as $key) {
      $upgrader = $mapper->getUpgrader($key);
      if ($upgrader !== NULL) {
        $upgraders[$key] = $upgrader;
      }
    }
    return $upgraders;
  }

  /**
   * @return string[]
   */
  protected static function getActiveKeys() {
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    try {
      return self::sortKeys(array_column($mapper->getActiveModuleFiles(), 'fullName'));
    }
    catch (CircularDependencyException $e) {
      CRM_Core_Error::debug_log_message("Failed to identify extensions. Circular dependency. " . $e->getMessage());
      return [];
    }
    catch (ElementNotFoundException $e) {
      CRM_Core_Error::debug_log_message("Failed to identify extensions. Unrecognized dependency. " . $e->getMessage());
      return [];
    }
  }

  /**
   * Sorts active extensions according to their dependencies
   *
   * @param string[] $keys
   *   Names of all active modules
   *
   * @return string[]
   * @throws \CRM_Extension_Exception
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   */
  protected static function sortKeys($keys) {
    $infos = CRM_Extension_System::singleton()->getMapper()->getAllInfos();

    // Ensure a stable starting order.
    $todoKeys = array_unique($keys);
    sort($todoKeys);

    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    foreach ($todoKeys as $key) {
      /** @var CRM_Extension_Info $info */
      $info = $infos[$key] ?? NULL;

      // Add dependencies
      if ($info) {
        // Filter out missing dependencies; missing modules cannot be upgraded
        $requires = array_intersect($info->requires ?? [], $keys);
        $sorter->add($key, $requires);
      }
      // This shouldn't ever happen if this function is being passed a list of active extensions.
      else {
        throw new CRM_Extension_Exception('Invalid extension key: "' . $key . '"');
      }
    }
    return $sorter->sort();
  }

}
