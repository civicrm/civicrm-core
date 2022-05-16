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
 */

/**
 * Track a list of known queues.
 */
class CRM_Queue_BAO_Queue extends CRM_Queue_DAO_Queue {

  /**
   * Get a list of valid queue types.
   *
   * @return string[]
   */
  public static function getTypes($context = NULL) {
    return [
      'Memory' => ts('Memory (Linear)'),
      'Sql' => ts('SQL (Linear)'),
      'SqlParallel' => ts('SQL (Parallel)'),
    ];
  }

}
