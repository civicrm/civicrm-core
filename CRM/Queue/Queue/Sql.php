<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * A queue implementation which stores items in the CiviCRM SQL database
 */
class CRM_Queue_Queue_Sql extends CRM_Queue_Queue {

  /**
   * Create a reference to queue. After constructing the queue, one should
   * usually call createQueue (if it's a new queue) or loadQueue (if it's
   * known to be an existing queue).
   *
   * @param $queueSpec, array with keys:
   *   - type: string, required, e.g. "interactive", "immediate", "stomp", "beanstalk"
   *   - name: string, required, e.g. "upgrade-tasks"
   *   - reset: bool, optional; if a queue is found, then it should be flushed; default to TRUE
   *   - (additional keys depending on the queue provider)
   */
  function __construct($queueSpec) {
    parent::__construct($queueSpec);
  }

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  function createQueue() {
    // nothing to do -- just start CRUDing items in the appropriate table
  }

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  function loadQueue() {
    // nothing to do -- just start CRUDing items in the appropriate table
  }

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  function deleteQueue() {
    return CRM_Core_DAO::singleValueQuery("
      DELETE FROM civicrm_queue_item
      WHERE queue_name = %1
    ", array(
        1 => array($this->getName(), 'String'),
      ));
  }

  /**
   * Check if the queue exists
   *
   * @return bool
   */
  function existsQueue() {
    return ($this->numberOfItems() > 0);
  }

  /**
   * Add a new item to the queue
   *
   * @param $data serializable PHP object or array
   * @param array|\queue $options queue-dependent options; for example, if this is a
   *   priority-queue, then $options might specify the item's priority
   *
   * @return bool, TRUE on success
   */
  function createItem($data, $options = array()) {
    $dao              = new CRM_Queue_DAO_QueueItem();
    $dao->queue_name  = $this->getName();
    $dao->submit_time = CRM_Utils_Time::getTime('YmdHis');
    $dao->data        = serialize($data);
    $dao->weight      = CRM_Utils_Array::value('weight', $options, 0);
    $dao->save();
  }

  /**
   * Determine number of items remaining in the queue
   *
   * @return int
   */
  function numberOfItems() {
    return CRM_Core_DAO::singleValueQuery("
      SELECT count(*)
      FROM civicrm_queue_item
      WHERE queue_name = %1
    ", array(
        1 => array($this->getName(), 'String'),
      ));
  }

  /**
   * Get the next item
   *
   * @param int|\seconds $lease_time seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  function claimItem($lease_time = 3600) {
    $sql = "
      SELECT id, queue_name, submit_time, release_time, data
      FROM civicrm_queue_item
      WHERE queue_name = %1
      ORDER BY weight ASC, id ASC
      LIMIT 1
    ";
    $params = array(
      1 => array($this->getName(), 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, 'CRM_Queue_DAO_QueueItem');
    if (is_a($dao, 'DB_Error')) {
      // FIXME - Adding code to allow tests to pass
      CRM_Core_Error::fatal();
    }

    if ($dao->fetch()) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      if ($dao->release_time === NULL || strtotime($dao->release_time) < $nowEpoch) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_queue_item SET release_time = %1 WHERE id = %2", array(
            '1' => array(date('YmdHis', $nowEpoch + $lease_time), 'String'),
            '2' => array($dao->id, 'Integer'),
          ));
        // work-around: inconsistent date-formatting causes unintentional breakage
        #        $dao->submit_time = date('YmdHis', strtotime($dao->submit_time));
        #        $dao->release_time = date('YmdHis', $nowEpoch + $lease_time);
        #        $dao->save();
        $dao->data = unserialize($dao->data);
        return $dao;
      }
      else {
        CRM_Core_Error::debug_var('not ready for release', $dao);
        return FALSE;
      }
    }
    else {
      CRM_Core_Error::debug_var('no items found');
      return FALSE;
    }
  }

  /**
   * Get the next item, even if there's an active lease
   *
   * @param int|\seconds $lease_time seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  function stealItem($lease_time = 3600) {
    $sql = "
      SELECT id, queue_name, submit_time, release_time, data
      FROM civicrm_queue_item
      WHERE queue_name = %1
      ORDER BY weight ASC, id ASC
      LIMIT 1
    ";
    $params = array(
      1 => array($this->getName(), 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, 'CRM_Queue_DAO_QueueItem');
    if ($dao->fetch()) {
      $nowEpoch = CRM_Utils_Time::getTimeRaw();
      CRM_Core_DAO::executeQuery("UPDATE civicrm_queue_item SET release_time = %1 WHERE id = %2", array(
          '1' => array(date('YmdHis', $nowEpoch + $lease_time), 'String'),
          '2' => array($dao->id, 'Integer'),
        ));
      $dao->data = unserialize($dao->data);
      return $dao;
    }
    else {
      CRM_Core_Error::debug_var('no items found');
      return FALSE;
    }
  }

  /**
   * Remove an item from the queue
   *
   * @param $dao object The item returned by claimItem
   */
  function deleteItem($dao) {
    $dao->delete();
    $dao->free();
  }

  /**
   * Return an item that could not be processed
   *
   * @param $dao object The item returned by claimItem
   *
   * @return bool
   */
  function releaseItem($dao) {
    $sql = "UPDATE civicrm_queue_item SET release_time = NULL WHERE id = %1";
    $params = array(
      1 => array($dao->id, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);
    $dao->free();
  }
}

