<?php

use Civi\Test\EventTestTrait;

class CRM_Event_Page_EventInfoTest extends CiviUnitTestCase {
  use EventTestTrait;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    Civi::settings()->set('event_enable_google_calendar_links', TRUE);
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

  public function testGoogleCalendarLinkCanBeDisabled(): void {
    Civi::settings()->set('event_enable_google_calendar_links', FALSE);
    $this->eventCreateUnpaid(['is_show_calendar_links' => TRUE]);
    $page = $this->getTestPage('CRM_Event_Page_EventInfo', [
      'id' => $this->getEventID(),
      'noFullMsg' => TRUE,
    ]);
    $page->run();
    $this->assertStringContainsString('Download iCalendar entry for this event', $page->getOutput());
    $this->assertOutputNotContainsString('Add event to Google Calendar');
  }

}
