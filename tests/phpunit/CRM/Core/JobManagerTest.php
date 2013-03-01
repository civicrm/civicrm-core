<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_JobManagerTest extends CiviUnitTestCase {

  function setUp() {
    parent::setUp();
  }
  
  function testHookCron() {
    $hook = $this->getMock('stdClass', array('civicrm_cron'));
    $hook->expects($this->once())
      ->method('civicrm_cron')
      ->with($this->isInstanceOf('CRM_Core_JobManager'));
    CRM_Utils_Hook::singleton()->setMock($hook);

    $jobManager = new CRM_Core_JobManager();
    $jobManager->execute(FALSE);
  }
}
