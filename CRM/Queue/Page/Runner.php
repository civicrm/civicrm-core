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


require_once 'CRM/Core/Page.php';

/**
 * The queue-runner page provides an interactive, web-based system
 * running the tasks in a queue and monitoring its progression.
 *
 * Do not link or redirect to this page directly -- go through
 * CRM_Queue_Runner::runAllViaWeb().
 *
 * Note: The queue runner only requires 'access CiviCRM' permission.
 * To ensure that malicious parties don't use this feature to
 * run queues on the wrong schedule, the queue-runner has an
 * extra authorization step: it checks for a session variable named
 * $_SESSION['queueRunners][$qrid]. This variable is properly setup
 * if you use the CRM_Queue_Runner::runAllViaWeb() interface.
 */
class CRM_Queue_Page_Runner extends CRM_Core_Page {

  /**
   *
   * POST Param 'qrid': string, usually the name of the queue
   */
  public function run() {
    $qrid = CRM_Utils_Request::retrieve('qrid', 'String', $this, TRUE);
    $runner = CRM_Queue_Runner::instance($qrid);
    if (!is_object($runner)) {
      CRM_Core_Error::statusBounce(ts('Queue runner must be configured before execution.'));
    }

    CRM_Utils_System::setTitle($runner->title);
    $this->assign('queueRunnerData', [
      'qrid' => $runner->qrid,
      'runNextAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/runNext', NULL, FALSE, NULL, FALSE),
      'skipNextAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/skipNext', NULL, FALSE, NULL, FALSE),
      'onEndAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/onEnd', NULL, FALSE, NULL, FALSE),
      'completed' => 0,
      'numberOfItems' => $runner->queue->numberOfItems(),
      'buttons' => $runner->buttons,
    ]);

    if ($runner->isMinimal) {
      $smarty = CRM_Core_Smarty::singleton();
      $content = $smarty->fetch('CRM/Queue/Page/Runner.tpl');
      if ($this->_print) {
        // unexpected - trying to print the output of the upgrader?
        // @todo remove this case and just ignore $this->_print entirely
        // for now we use the original call, to maintain preexisting behaviour (however strange that is)
        \CRM_Core_Error::deprecatedWarning('Calling CRM_Utils_System::theme with $print and $maintenance is unexpected and may behave strangely. This codepath will be removed in a future release. If you need it, please comment on https://lab.civicrm.org/dev/core/-/issues/5803');
        echo CRM_Utils_System::theme($content, $this->_print, TRUE);
      }
      else {
        echo CRM_Utils_System::renderMaintenanceMessage($content);
      }
    }
    else {
      parent::run();
    }
  }

}
