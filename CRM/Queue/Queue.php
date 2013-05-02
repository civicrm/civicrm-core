<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * A queue is an object (usually backed by some persistent data store)
 * which stores a list of tasks or messages for use by other processes.
 *
 * This would ideally be an interface, but it's handy to specify the
 * "function __construct()" and the "$name" handling
 *
 * Note: This interface closely parallels the DrupalQueueInterface.
 */
abstract class CRM_Queue_Queue {

  /**
   * @var string
   */
  private $_name;

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
   */ function __construct($queueSpec) {
    $this->_name = $queueSpec['name'];
  }

  /**
   * Determine the string name of this queue
   *
   * @return string
   */
  function getName() {
    return $this->_name;
  }

  /**
   * Perform any registation or resource-allocation for a new queue
   */
  abstract function createQueue();

  /**
   * Perform any loading or pre-fetch for an existing queue.
   */
  abstract function loadQueue();

  /**
   * Release any resources claimed by the queue (memory, DB rows, etc)
   */
  abstract function deleteQueue();

  /**
   * Check if the queue exists
   *
   * @return bool
   */
  abstract function existsQueue();

  /**
   * Add a new item to the queue
   *
   * @param $data serializable PHP object or array
   * @param $options queue-dependent options; for example, if this is a
   *   priority-queue, then $options might specify the item's priority
   *
   * @return bool, TRUE on success
   */
  abstract function createItem($data, $options = array());

  /**
   * Determine number of items remaining in the queue
   *
   * @return int
   */
  abstract function numberOfItems();

  /**
   * Get the next item
   *
   * @param $lease_time seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  abstract function claimItem($lease_time = 3600);

  /**
   * Get the next item, even if there's an active lease
   *
   * @param $lease_time seconds
   *
   * @return object with key 'data' that matches the inputted data
   */
  abstract function stealItem($lease_time = 3600);

  /**
   * Remove an item from the queue
   *
   * @param $item The item returned by claimItem
   */
  abstract function deleteItem($item);

  /**
   * Return an item that could not be processed
   *
   * @param $item The item returned by claimItem
   *
   * @return bool
   */
  abstract function releaseItem($item);
}

