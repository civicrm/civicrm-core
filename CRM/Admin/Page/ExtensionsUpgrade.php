<?php

require_once 'CRM/Core/Page.php';

/**
 * Display a page which displays a progress bar while executing
 * upgrade tasks.
 */
class CRM_Admin_Page_ExtensionsUpgrade extends CRM_Core_Page {
  const END_URL = 'civicrm/admin/extensions';
  const END_PARAMS = 'reset=1';

  /**
   * Run Page.
   */
  public function run() {
    $queue = CRM_Extension_Upgrades::createQueue();
    $runner = new CRM_Queue_Runner(array(
      'title' => ts('Database Upgrades'),
      'queue' => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      'onEnd' => array('CRM_Admin_Page_ExtensionsUpgrade', 'onEnd'),
      'onEndUrl' => !empty($_GET['destination']) ? $_GET['destination'] : CRM_Utils_System::url(self::END_URL, self::END_PARAMS),
    ));

    CRM_Core_Error::debug_log_message('CRM_Admin_Page_ExtensionsUpgrade: Start upgrades');
    $runner->runAllViaWeb(); // does not return
  }

  /**
   * Handle the final step of the queue.
   *
   * @param \CRM_Queue_TaskContext $ctx
   */
  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    CRM_Core_Error::debug_log_message('CRM_Admin_Page_ExtensionsUpgrade: Finish upgrades');
  }

}
