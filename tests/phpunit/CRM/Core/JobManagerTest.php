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
 * Class CRM_Core_JobManagerTest
 * @group headless
 */
class CRM_Core_JobManagerTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testHookCron() {
    $mockFunction = $this->mockMethod;
    $hook = $this->getMockBuilder(stdClass::class)
      ->setMethods(['civicrm_cron'])
      ->getMock();
    $hook->expects($this->once())
      ->method('civicrm_cron')
      ->with($this->isInstanceOf('CRM_Core_JobManager'));
    CRM_Utils_Hook::singleton()->setMock($hook);

    $jobManager = new CRM_Core_JobManager();
    $jobManager->execute(FALSE);
  }

}
