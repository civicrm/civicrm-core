<?php
namespace api\v4\SearchSchedule;

use Civi\Api4\SearchSchedule;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchScheduleTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use EntityTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Helper to set up a SavedSearch and SearchDisplay fixture.
   */
  private function createDisplayFixture(string $key = 'test'): array {
    $savedSearch = $this->createTestEntity('SavedSearch', [
      'name' => 'Search_' . $key,
      'label' => 'Search ' . $key,
      'api_entity' => 'Contact',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'display_name'],
      ],
    ], 'savedSearch_' . $key);

    $display = $this->createTestEntity('SearchDisplay', [
      'name' => 'display_' . $key,
      'label' => 'Display ' . $key,
      'saved_search_id' => $savedSearch['id'],
      'type' => 'table',
      'settings' => [
        'actions' => TRUE,
        'columns' => [
          ['key' => 'id', 'label' => 'ID'],
          ['key' => 'display_name', 'label' => 'Name'],
        ],
      ],
    ], 'display_' . $key);

    return [$savedSearch, $display];
  }

  public function testCreateAndAutoCalculateNextRun(): void {
    [, $display] = $this->createDisplayFixture('calc');
    $contact = $this->createTestEntity('Contact', [
      'first_name' => 'Test',
      'last_name' => 'Recipient',
      'email_primary.email' => 'recipient@example.com',
    ], 'contact_calc');

    // 1. Future start_date sets next_run to start_date
    $futureStart = date('Y-m-d H:i:s', strtotime('+5 days'));
    $schedule = SearchSchedule::create(FALSE)
      ->setValues([
        'search_display_id' => $display['id'],
        'label' => 'Future Schedule',
        'subject' => 'Weekly Report',
        'recipients' => [$contact['id']],
        'format' => 'csv',
        'frequency_unit' => 'week',
        'frequency_interval' => 1,
        'start_date' => $futureStart,
        'is_active' => TRUE,
      ])
      ->execute()
      ->single();

    $refreshedCreated = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->execute()
      ->single();

    $this->assertEquals($futureStart, date('Y-m-d H:i:s', strtotime($refreshedCreated['next_run'])));

    // 2. Updating non-timing field (e.g. subject) preserves next_run
    SearchSchedule::update(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->addValue('subject', 'Updated Subject')
      ->execute();

    $refreshed = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->execute()
      ->single();

    $this->assertEquals($futureStart, date('Y-m-d H:i:s', strtotime($refreshed['next_run'])));
    $this->assertEquals('Updated Subject', $refreshed['subject']);

    // 3. Updating timing field (start_date) recalculates next_run
    $newStart = date('Y-m-d H:i:s', strtotime('+10 days'));
    SearchSchedule::update(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->addValue('start_date', $newStart)
      ->execute();

    $refreshedTiming = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->execute()
      ->single();

    $this->assertEquals($newStart, date('Y-m-d H:i:s', strtotime($refreshedTiming['next_run'])));

    // 4. Inactivating sets next_run to NULL
    SearchSchedule::update(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->addValue('is_active', FALSE)
      ->execute();

    $inactivated = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->execute()
      ->single();

    $this->assertNull($inactivated['next_run']);

    // 5. Re-activating calculates next_run
    SearchSchedule::update(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->addValue('is_active', TRUE)
      ->execute();

    $reactivated = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $schedule['id'])
      ->execute()
      ->single();

    $this->assertNotNull($reactivated['next_run']);
  }

  public function testSendDue(): void {
    [, $display] = $this->createDisplayFixture('send');
    $contact = $this->createTestEntity('Contact', [
      'first_name' => 'Report',
      'last_name' => 'Viewer',
      'email_primary.email' => 'viewer@example.com',
    ], 'contact_send');

    // Create schedule due in the past
    $pastDate = date('Y-m-d H:i:s', strtotime('-2 hours'));
    $dueSchedule = SearchSchedule::create(FALSE)
      ->setValues([
        'search_display_id' => $display['id'],
        'label' => 'Due Schedule',
        'subject' => 'Due Report',
        'recipients' => [$contact['id']],
        'format' => 'csv',
        'frequency_unit' => 'day',
        'frequency_interval' => 1,
        'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'next_run' => $pastDate,
        'is_active' => TRUE,
      ])
      ->execute()
      ->single();

    // Create schedule due in the future
    $futureDate = date('Y-m-d H:i:s', strtotime('+2 days'));
    $futureSchedule = SearchSchedule::create(FALSE)
      ->setValues([
        'search_display_id' => $display['id'],
        'label' => 'Future Schedule',
        'subject' => 'Future Report',
        'recipients' => [$contact['id']],
        'format' => 'csv',
        'frequency_unit' => 'day',
        'frequency_interval' => 1,
        'start_date' => $futureDate,
        'next_run' => $futureDate,
        'is_active' => TRUE,
      ])
      ->execute()
      ->single();

    // Run sendDue
    $results = SearchSchedule::sendDue(FALSE)->execute();

    $sentIds = array_column((array) $results, 'id');
    $this->assertContains($dueSchedule['id'], $sentIds);
    $this->assertNotContains($futureSchedule['id'], $sentIds);

    // Verify due schedule updated last_run and advanced next_run
    $refreshedDue = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $dueSchedule['id'])
      ->execute()
      ->single();

    $this->assertNotNull($refreshedDue['last_run']);
    $this->assertGreaterThan($pastDate, $refreshedDue['next_run']);

    // Verify future schedule untouched
    $refreshedFuture = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $futureSchedule['id'])
      ->execute()
      ->single();

    $this->assertNull($refreshedFuture['last_run']);
    $this->assertEquals($futureDate, date('Y-m-d H:i:s', strtotime($refreshedFuture['next_run'])));
  }

  public function testSendDueResilienceOnFailure(): void {
    [, $display] = $this->createDisplayFixture('fail');
    $contact = $this->createTestEntity('Contact', [
      'first_name' => 'Fail',
      'last_name' => 'Test',
      'email_primary.email' => 'fail@example.com',
    ], 'contact_fail');

    // Schedule configured with invalid format that triggers failure during file processing
    $pastDate = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $badSchedule = SearchSchedule::create(FALSE)
      ->setValues([
        'search_display_id' => $display['id'],
        'label' => 'Failing Schedule',
        'subject' => 'Will Fail',
        'recipients' => [$contact['id']],
        'format' => 'unsupported_fmt',
        'frequency_unit' => 'day',
        'frequency_interval' => 1,
        'start_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'next_run' => $pastDate,
        'is_active' => TRUE,
      ])
      ->execute()
      ->single();

    $results = SearchSchedule::sendDue(FALSE)
      ->setScheduleId($badSchedule['id'])
      ->execute();

    $this->assertCount(1, $results);
    $this->assertEquals('error', $results[0]['status']);

    // Schedule next_run must still advance so it does not loop infinitely
    $refreshed = SearchSchedule::get(FALSE)
      ->addWhere('id', '=', $badSchedule['id'])
      ->execute()
      ->single();

    $this->assertGreaterThan($pastDate, $refreshed['next_run']);
    $this->assertNotNull($refreshed['last_run']);
  }

}
