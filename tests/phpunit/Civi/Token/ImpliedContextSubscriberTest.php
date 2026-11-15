<?php
namespace Civi\Token;

class ImpliedContextSubscriberTest extends \CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function testParticipantImplicitEvent(): void {
    $participantId = $this->participantCreate();

    $messages = \CRM_Core_TokenSmarty::render(
      ['text' => 'Go to {event.title}!'],
      ['participantId' => $participantId]
    );
    $this->assertSame('Go to Annual CiviCRM meet!', $messages['text']);
  }

  public function testParticipantExplicitEvent(): void {
    $participantId = $this->participantCreate();
    $otherEventId = $this->eventCreateUnpaid(['title' => 'Alternate Event'], 'second')['id'];

    $messages = \CRM_Core_TokenSmarty::render(
      ['text' => 'You may also like {event.title}!'],
      ['participantId' => $participantId, 'eventId' => $otherEventId]
    );
    $this->assertSame('You may also like Alternate Event!', $messages['text']);
  }

}
