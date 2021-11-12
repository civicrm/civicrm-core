<?php
namespace Civi\Token;

class ImpliedContextSubscriberTest extends \CiviUnitTestCase {

  public function testParticipant_ImplicitEvent() {
    $participantId = $this->participantCreate();

    $messages = \CRM_Core_TokenSmarty::render(
      ['text' => 'Go to {event.title}!'],
      ['participantId' => $participantId]
    );
    $this->assertEquals('Go to Annual CiviCRM meet!', $messages['text']);
  }

  public function testParticipant_ExplicitEvent() {
    $participantId = $this->participantCreate();
    $otherEventId = $this->eventCreate(['title' => 'Alternate Event'])['id'];

    $messages = \CRM_Core_TokenSmarty::render(
      ['text' => 'You may also like {event.title}!'],
      ['participantId' => $participantId, 'eventId' => $otherEventId]
    );
    $this->assertEquals('You may also like Alternate Event!', $messages['text']);
  }

}
