<?php

namespace Civi\Searches;

use Civi\Api4\Order;
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
    $eventID = $this->createTestEntity('Event', ['title' => 'Annual conference', 'event_type_id:name' => 'Conference', 'start_date' => '2020-07-01'])['id'];
    $participantID = $this->createTestEntity('Participant', ['contact_id' => $contactID, 'event_id' => $eventID, 'status_id:name' => 'Registered'])['id'];
    Order::create(FALSE)
      ->setContributionValues([
        'contact_id' => $contactID,
        'financial_type_id:name' => 'Event Fee',
        'receive_date' => '2020-06-01',
      ])
      ->addLineItem([
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participantID,
        'line_total' => 100,
        'unit_price' => 100,
        'qty' => 1,
      ])
      ->execute();

    $formValues = ['start_date' => '', 'end_date' => ''];
    $search = new CRM_Contact_Form_Search_Custom_EventAggregate($formValues);
    $rows = CRM_Core_DAO::executeQuery($search->all())->fetchAll();

    $this->assertSame([[$eventID, 'Conference']], array_map(fn($row) => [(int) $row['event_id'], $row['event_type']], $rows));
  }

}
