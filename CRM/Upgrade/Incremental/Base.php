<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7.alpha1                                         |
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
 * Base class for incremental upgrades
 */
abstract class CRM_Upgrade_Incremental_Base implements CRM_Upgrade_Incremental_Interface {
  const BATCH_SIZE = 5000;

  /**
   * @param string $startVer
   * @param string $endVer
   * @return string
   */
  public abstract function createPreUpgradeMessage($startVer, $endVer);

  /**
   * @param \CRM_Queue_Queue $queue
   * @param string $postUpgradeMessageFile
   * @param string $startVer
   * @param string $endVer
   */
  public abstract function buildQueue(CRM_Queue_Queue $queue, $postUpgradeMessageFile, $startVer, $endVer);

  /**
   * (Queue Task Callback)
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $rev
   *
   * @return bool
   */
  public static function runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntactic sugar for adding a task.
   *
   * Task is (a) in this class and (b) has a high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   *
   * @param string $title
   * @param string $funcName
   */
  protected function addTask($title, $funcName) {
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ));

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), $funcName),
      $args,
      $title
    );
    $queue->createItem($task, array('weight' => -1));
  }

  /**
   * Remove a payment processor if not in use
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $name
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function removePaymentProcessorType(CRM_Queue_TaskContext $ctx, $name) {
    $processors = civicrm_api3('PaymentProcessor', 'getcount', array('payment_processor_type_id' => $name));
    if (empty($processors['result'])) {
      $result = civicrm_api3('PaymentProcessorType', 'get', array(
        'name' => $name,
        'return' => 'id',
      ));
      if (!empty($result['id'])) {
        civicrm_api3('PaymentProcessorType', 'delete', array('id' => $result['id']));
      }
    }
    return TRUE;
  }

}
