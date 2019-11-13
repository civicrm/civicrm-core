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
 * Describe the runtime environment in which a queue task executes
 */
class CRM_Queue_TaskContext {

  /**
   * @var CRM_Queue_Queue
   */
  public $queue;

  /**
   * @var Log
   */
  public $log;

}
