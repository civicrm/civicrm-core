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

    foreach (self::getActiveUpgraders() as $upgrader) {
      /** @var \CRM_Extension_Upgrader_Interface $upgrader */
      $upgrader->notify('upgrade', ['enqueue', $queue]);
    }

    CRM_Utils_Hook::upgrade('enqueue', $queue);

    return $queue;
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
   * @param string[] $keys
   *
   * @return string[]
   * @throws \CRM_Extension_Exception
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   */
  protected static function sortKeys($keys) {
    $infos = CRM_Extension_System::singleton()->getMapper()->getAllInfos();

    // Start with our inputs in a normalized form.
    $todoKeys = array_unique($keys);
    sort($todoKeys);

    // Goal: Add all active items to $sorter and flag $doneKeys['org.example.foobar']=1.
    $doneKeys = [];
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    while (!empty($todoKeys)) {
      $key = array_shift($todoKeys);
      if (isset($doneKeys[$key])) {
        continue;
      }
      $doneKeys[$key] = 1;

      /** @var CRM_Extension_Info $info */
      $info = @$infos[$key];

      if ($info && $info->requires) {
        $sorter->add($key, $info->requires);
        $todoKeys = array_merge($todoKeys, $info->requires);
      }
      else {
        $sorter->add($key, []);
      }
    }
    return $sorter->sort();
  }

}
