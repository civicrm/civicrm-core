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
 * @group headless
 */
class CRM_Core_TaskTest extends CiviUnitTestCase {

  public function testTask() {
    CRM_Core_BAO_ConfigSetting::enableAllComponents();

    $taskClasses = [
      'CRM_Activity_Task',
      'CRM_Campaign_Task',
      'CRM_Case_Task',
      'CRM_Contact_Task',
      'CRM_Contribute_Task',
      'CRM_Event_Task',
      'CRM_Mailing_Task',
      'CRM_Member_Task',
      'CRM_Pledge_Task',
    ];

    // Call all of them to ensure they don't interfere with each other.
    foreach ($taskClasses as $className) {
      $tasks = $className::tasks();
      $this->assertIsArray($tasks, "$className::tasks() should return an array");
      $this->assertNotEmpty($tasks, "$className::tasks() should not be empty");
    }

    // Pick one example task from each class and assert something stable about it.
    // (For some classes we assert a specific known entry; for others we assert the "Print" task shape/pattern.)
    $examples = [
      'CRM_Activity_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Activity_Task::TASK_SMS, $tasks);
        $this->assertEquals('CRM_Activity_Form_Task_FileOnCase', $tasks[CRM_Activity_Task::TASK_SMS]['class']);
      },

      'CRM_Contact_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Contact_Task::GROUP_ADD, $tasks);
        $this->assertEquals('CRM_Contact_Form_Task_AddToGroup', $tasks[CRM_Contact_Task::GROUP_ADD]['class']);
      },

      'CRM_Contribute_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Contribute_Task::TASK_EXPORT, $tasks);
        $this->assertIsArray($tasks[CRM_Contribute_Task::TASK_EXPORT]['class']);
        $this->assertEquals('CRM_Contribute_Export_Form_Select', $tasks[CRM_Contribute_Task::TASK_EXPORT]['class'][0]);
      },

      'CRM_Event_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Event_Task::CANCEL_REGISTRATION, $tasks);
        $this->assertEquals('CRM_Event_Form_Task_Cancel', $tasks[CRM_Event_Task::CANCEL_REGISTRATION]['class']);
      },

      // For these, assert the shared "Print selected rows" task exists and looks like the expected Print form.
      'CRM_Campaign_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Campaign_Task::TASK_PRINT, $tasks);
        $this->assertIsString($tasks[CRM_Campaign_Task::TASK_PRINT]['class']);
        $this->assertStringContainsString('_Form_Task_Print', $tasks[CRM_Campaign_Task::TASK_PRINT]['class']);
      },
      'CRM_Case_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Case_Task::TASK_PRINT, $tasks);
        $this->assertIsString($tasks[CRM_Case_Task::TASK_PRINT]['class']);
        $this->assertStringContainsString('_Form_Task_Print', $tasks[CRM_Case_Task::TASK_PRINT]['class']);
      },
      'CRM_Mailing_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Mailing_Task::TASK_PRINT, $tasks);
        $this->assertIsString($tasks[CRM_Mailing_Task::TASK_PRINT]['class']);
        $this->assertStringContainsString('_Form_Task_Print', $tasks[CRM_Mailing_Task::TASK_PRINT]['class']);
      },
      'CRM_Member_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Member_Task::TASK_PRINT, $tasks);
        $this->assertIsString($tasks[CRM_Member_Task::TASK_PRINT]['class']);
        $this->assertStringContainsString('_Form_Task_Print', $tasks[CRM_Member_Task::TASK_PRINT]['class']);
      },
      'CRM_Pledge_Task' => function(array $tasks): void {
        $this->assertArrayHasKey(CRM_Pledge_Task::TASK_PRINT, $tasks);
        $this->assertIsString($tasks[CRM_Pledge_Task::TASK_PRINT]['class']);
        $this->assertStringContainsString('_Form_Task_Print', $tasks[CRM_Pledge_Task::TASK_PRINT]['class']);
      },
    ];

    foreach ($examples as $className => $assertion) {
      $tasks = $className::tasks();
      $assertion($tasks);
    }

  }

}
