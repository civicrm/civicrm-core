<?php

use Civi\Test\EventTestTrait;

class CRM_Event_Page_EventInfoTest extends CiviUnitTestCase {
  use EventTestTrait;


  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function testFullMessage(): void {
    $this->eventCreateUnpaid(['max_participants' => 1]);
    $this->createTestEntity('Participant', [
      'event_id' => $this->getEventID(),
      'contact_id' => $this->createLoggedInUser(),
    ]);
    $page = $this->getTestPage('CRM_Event_Page_EventInfo', [
      'id' => $this->getEventID(),
      'contact_id' => $this->getLoggedInUser(),
      'noFullMsg' => TRUE,
    ]);
    $page->run();
    $this->assertOutputNotContainsString('Sorry! We are already full');
  }

}
