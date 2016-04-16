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
      CRM_Core_Error::fatal('Queue runner must be configured before execution.');
    }

    CRM_Utils_System::setTitle($runner->title);
    $this->assign('queueRunnerData', array(
      'qrid' => $runner->qrid,
      'runNextAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/runNext', NULL, FALSE, NULL, FALSE),
      'skipNextAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/skipNext', NULL, FALSE, NULL, FALSE),
      'onEndAjax' => CRM_Utils_System::url($runner->pathPrefix . '/ajax/onEnd', NULL, FALSE, NULL, FALSE),
      'completed' => 0,
      'numberOfItems' => $runner->queue->numberOfItems(),
      'buttons' => $runner->buttons,
    ));

    if ($runner->isMinimal) {
      // Render page header
      if (!defined('CIVICRM_UF_HEAD') && $region = CRM_Core_Region::instance('html-header', FALSE)) {
        CRM_Utils_System::addHTMLHead($region->render(''));
      }
      $smarty = CRM_Core_Smarty::singleton();
      $content = $smarty->fetch('CRM/Queue/Page/Runner.tpl');
      echo CRM_Utils_System::theme($content, $this->_print, TRUE);
    }
    else {
      parent::run();
    }
  }

}
