<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Contact_Page_DedupeMerge extends CRM_Core_Page {

  const BATCHLIMIT = 2;

  /**
   * Browse batch merges.
   */
  public function run() {
    $runner = self::getRunner();
    if ($runner) {
      // Run Everything in the Queue via the Web.
      $runner->runAllViaWeb();
    }
    else {
      CRM_Core_Session::setStatus(ts('Nothing to merge.'));
    }

    // parent run
    return parent::run();
  }

  /**
   * Build a queue of tasks by dividing dupe pairs in batches.
   */
  public static function getRunner() {
    $rgid = CRM_Utils_Request::retrieve('rgid', 'Positive');
    $gid  = CRM_Utils_Request::retrieve('gid', 'Positive');
    $limit  = CRM_Utils_Request::retrieve('limit', 'Positive');
    $action = CRM_Utils_Request::retrieve('action', 'String', CRM_Core_DAO::$_nullObject);
    $mode   = CRM_Utils_Request::retrieve('mode', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'safe');

    $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid);

    $urlQry = "reset=1&action=update&rgid={$rgid}&gid={$gid}&limit={$limit}";

    if ($mode == 'aggressive' && !CRM_Core_Permission::check('force merge duplicate contacts')) {
      CRM_Core_Session::setStatus(ts('You do not have permission to force merge duplicate contact records'), ts('Permission Denied'), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry));
    }
    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name'  => $cacheKeyString,
      'type'  => 'Sql',
      'reset' => TRUE,
    ));

    $where = NULL;
    if ($action == CRM_Core_Action::MAP) {
      $where = "pn.is_selected = 1";
      $isSelected = 1;
    }
    else {
      // else merge all (2)
      $isSelected = 2;
    }

    $total  = CRM_Core_BAO_PrevNextCache::getCount($cacheKeyString, NULL, $where);
    if ($total <= 0) {
      // Nothing to do.
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry));
    }

    // reset merge stats, so we compute new stats
    CRM_Dedupe_Merger::resetMergeStats($cacheKeyString);

    for ($i = 1; $i <= ceil($total / self::BATCHLIMIT); $i++) {
      $task  = new CRM_Queue_Task(
        array('CRM_Contact_Page_DedupeMerge', 'callBatchMerge'),
        array($rgid, $gid, $mode, FALSE, self::BATCHLIMIT, $isSelected),
        "Processed " . $i * self::BATCHLIMIT . " pair of duplicates out of " . $total
      );

      // Add the Task to the Queue
      $queue->createItem($task);
    }

    // Setup the Runner
    $urlQry .= "&context=conflicts";
    $runner = new CRM_Queue_Runner(array(
      'title'     => ts('Merging Duplicates..'),
      'queue'     => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEndUrl'  => CRM_Utils_System::url('civicrm/contact/dedupefind', $urlQry, TRUE, NULL, FALSE),
    ));

    return $runner;
  }

  /**
   * Carry out batch merges.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param int $rgid
   * @param int $gid
   * @param string $mode
   *   'safe' mode or 'force' mode.
   * @param bool $autoFlip
   *   Override the values in the prevnext table & use the lowest value?
   *   As the form offers the user to flip the values themselves this should
   *   only be TRUE if you wish to ignore the user.
   * @param int $batchLimit
   * @param int $isSelected
   *
   * @return int
   */
  public static function callBatchMerge(CRM_Queue_TaskContext $ctx, $rgid, $gid, $mode = 'safe', $autoFlip, $batchLimit, $isSelected) {
    CRM_Dedupe_Merger::batchMerge($rgid, $gid, $mode, $autoFlip, $batchLimit, $isSelected);
    return CRM_Queue_Task::TASK_SUCCESS;
  }

}
