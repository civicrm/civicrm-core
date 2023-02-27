<?php

require_once 'CRM/Core/Page.php';

/**
 * An example page which queues several tasks and then executes them
 */
class CRM_Demoqueue_Page_DemoQueue extends CRM_Core_Page {

  function run() {
    $queueName = 'demoqueue_' . time();

    $queue = Civi::queue($queueName, [
      'type' => 'Sql',
      'runner' => 'task',
      'error' => 'abort',
    ]);

    for ($i = 0; $i < 5; $i++) {
      $queue->createItem(new CRM_Queue_Task(
        ['CRM_Demoqueue_Page_DemoQueue', 'doMyWork'], // callback
        [$i, "Task $i takes $i second(s)"], // arguments
        "Task $i" // title
      ));
      if ($i == 2) {
        $queue->createItem(new CRM_Queue_Task(
          ['CRM_Demoqueue_Page_DemoQueue', 'addMoreWork'], // callback
          [], // arguments
          "Add More Work" // title
        ));
      }
    }

    \Civi\Api4\UserJob::create()->setValues([
      'job_type' => 'contact_import',
      'status_id:name' => 'in_progress',
      'queue_id.name' => $queue->getName(),
    ])->execute();

    $runner = new CRM_Queue_Runner([
      'title' => ts('Demo Queue Runner'),
      'queue' => $queue,
      // Deprecated; only works on AJAX runner // 'onEnd' => ['CRM_Demoqueue_Page_DemoQueue', 'onEnd'],
      'onEndUrl' => CRM_Utils_System::url('civicrm/demo-queue/done'),
    ]);
    $runner->runAllInteractive(); // does not return
  }

  /**
   * Perform some business logic
   * @param \CRM_Queue_TaskContext $ctx
   * @param $delay
   * @param $message
   * @return bool
   */
  static function doMyWork(CRM_Queue_TaskContext $ctx, $delay, $message) {
    sleep(1);
    //sleep($delay);
    //$ctx->log->info($message); // PEAR Log interface
    //$ctx->logy->info($message); // PEAR Log interface -- broken, PHP error
    //CRM_Core_DAO::executeQuery('select from alsdkjfasdf'); // broken, PEAR error
    //throw new Exception('whoz'); // broken, exception
    return TRUE; // success
  }

  /**
   * Perform some business logic
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  static function addMoreWork(CRM_Queue_TaskContext $ctx) {
    sleep(1);
    for ($i = 0; $i < 5; $i++) {
      $ctx->queue->createItem(new CRM_Queue_Task(
        ['CRM_Demoqueue_Page_DemoQueue', 'doMyWork'], // callback
        [$i, "Extra task $i takes $i second(s)"], // arguments
        "Extra Task $i" // title
      ), [
        'weight' => -1,
      ]);
    }
    return TRUE; // success
  }

  /**
   * Handle the final step of the queue
   * @param \CRM_Queue_TaskContext $ctx
   */
  static function onEnd(CRM_Queue_TaskContext $ctx) {
    //CRM_Utils_System::redirect('civicrm/demo-queue/done');
    CRM_Core_Error::debug_log_message('finished task');
    //$ctx->logy->info($message); // PEAR Log interface -- broken, PHP error
    //CRM_Core_DAO::executeQuery('select from alsdkjfasdf'); // broken, PEAR error
    //throw new Exception('whoz'); // broken, exception
  }
}
