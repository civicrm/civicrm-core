<?php

namespace Civi\Searches;

use Civi\Test;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use CRM_Contact_Form_Search_Custom_EventAggregate;
use CRM_Core_DAO;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class EventAggregateTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use EntityTrait;

  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()->install(['legacycustomsearches'])->apply();
  }

  /**
   * A registered participant's paid event is listed with its event type.
   */
  public function testEventTypeLabel(): void {
    $contactID = $this->createTestEntity('Individual', ['first_name' => 'Pat', 'last_name' => 'Participant'])['id'];
    $contributionID = $this->createTestEntity('Contribution', [
      'contact_id' => $contactID,
      'financial_type_id:name' => 'Event Fee',
      'total_amount' => 100,
      'receive_date' => '2020-06-01',
    ])['id'];
    $eventID = $this->createTestEntity('Event', ['title' => 'Annual conference', 'event_type_id:name' => 'Conference', 'start_date' => '2020-07-01'])['id'];
    $participantID = $this->createTestEntity('Participant', ['contact_id' => $contactID, 'event_id' => $eventID, 'status_id:name' => 'Registered'])['id'];
    $payment = ['participant_id' => $participantID, 'contribution_id' => $contributionID];
    \CRM_Event_BAO_ParticipantPayment::create($payment);

    $formValues = ['start_date' => '', 'end_date' => ''];
    $search = new CRM_Contact_Form_Search_Custom_EventAggregate($formValues);
    $rows = CRM_Core_DAO::executeQuery($search->all())->fetchAll();

    $this->assertSame([[$eventID, 'Conference']], array_map(fn($row) => [(int) $row['event_id'], $row['event_type']], $rows));
  }

}
