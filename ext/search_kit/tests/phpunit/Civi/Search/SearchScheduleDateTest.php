<?php
namespace Civi\Search;

use CRM_Search_BAO_SearchSchedule as BAO;

/**
 * @group headless
 */
class SearchScheduleDateTest extends \PHPUnit\Framework\TestCase {

  public function testFutureStartDate() {
    $schedule = [
      'start_date' => '2030-05-01 10:00:00',
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'is_active' => TRUE,
    ];
    $after = new \DateTime('2026-10-16 10:00:00');
    $this->assertEquals('2030-05-01 10:00:00', BAO::calculateNextRun($schedule, $after));
  }

  public function testInactiveScheduleReturnsNull() {
    $schedule = [
      'start_date' => '2026-10-01 10:00:00',
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'is_active' => FALSE,
    ];
    $this->assertNull(BAO::calculateNextRun($schedule));
  }

  public function testMissingRequiredFields() {
    $this->assertNull(BAO::calculateNextRun([]));
    $this->assertNull(BAO::calculateNextRun(['start_date' => '2026-10-01 10:00:00']));
    $this->assertNull(BAO::calculateNextRun(['frequency_unit' => 'day']));
  }

  public function testDailyFrequency() {
    $schedule = [
      'start_date' => '2026-10-01 09:30:00',
      'frequency_unit' => 'day',
      'frequency_interval' => 1,
      'is_active' => TRUE,
    ];

    // Same day before scheduled time -> runs today
    $after = new \DateTime('2026-10-01 08:00:00');
    $this->assertEquals('2026-10-01 09:30:00', BAO::calculateNextRun($schedule, $after));

    // Later in the day -> runs tomorrow
    $after = new \DateTime('2026-10-01 10:00:00');
    $this->assertEquals('2026-10-02 09:30:00', BAO::calculateNextRun($schedule, $after));

    // Multiple days later
    $after = new \DateTime('2026-10-16 10:00:00');
    $this->assertEquals('2026-10-17 09:30:00', BAO::calculateNextRun($schedule, $after));

    // Daily interval 3
    $schedule['frequency_interval'] = 3;
    // Oct 1 + 15 days = Oct 16 09:30:00. If after is 08:00:00, next run is today at 09:30:00
    $after = new \DateTime('2026-10-16 08:00:00');
    $this->assertEquals('2026-10-16 09:30:00', BAO::calculateNextRun($schedule, $after));

    // If after is 10:00:00 on Oct 16, next run is Oct 19 09:30:00
    $after = new \DateTime('2026-10-16 10:00:00');
    $this->assertEquals('2026-10-19 09:30:00', BAO::calculateNextRun($schedule, $after));
  }

  public function testWeeklyFrequency() {
    // Thursday start date
    $schedule = [
      'start_date' => '2026-10-01 14:00:00',
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'is_active' => TRUE,
    ];

    // Friday Oct 16 -> next Thursday is Oct 22
    $after = new \DateTime('2026-10-16 10:00:00');
    $this->assertEquals('2026-10-22 14:00:00', BAO::calculateNextRun($schedule, $after));

    // Thursday Oct 22 morning -> runs today
    $after = new \DateTime('2026-10-22 10:00:00');
    $this->assertEquals('2026-10-22 14:00:00', BAO::calculateNextRun($schedule, $after));

    // Bi-weekly (every 2 weeks): Oct 1 -> Oct 15 -> Oct 29
    $schedule['frequency_interval'] = 2;
    $after = new \DateTime('2026-10-16 10:00:00');
    $this->assertEquals('2026-10-29 14:00:00', BAO::calculateNextRun($schedule, $after));
  }

  public function testMonthlyFrequencyAndDayClamping() {
    // Normal mid-month schedule
    $schedule = [
      'start_date' => '2026-01-15 08:00:00',
      'frequency_unit' => 'month',
      'frequency_interval' => 1,
      'is_active' => TRUE,
    ];
    $after = new \DateTime('2026-03-20 00:00:00');
    $this->assertEquals('2026-04-15 08:00:00', BAO::calculateNextRun($schedule, $after));

    // Schedule on 31st of the month: must clamp in February without losing 31st in March
    $schedule['start_date'] = '2026-01-31 08:00:00';
    $after = new \DateTime('2026-01-31 09:00:00');
    // In Feb 2026 (28 days):
    $this->assertEquals('2026-02-28 08:00:00', BAO::calculateNextRun($schedule, $after));

    // After Feb 28: March should run on 31st (not 28th!)
    $after = new \DateTime('2026-02-28 09:00:00');
    $this->assertEquals('2026-03-31 08:00:00', BAO::calculateNextRun($schedule, $after));

    // Leap year Feb 2024 has 29 days
    $schedule['start_date'] = '2024-01-31 08:00:00';
    $after = new \DateTime('2024-01-31 09:00:00');
    $this->assertEquals('2024-02-29 08:00:00', BAO::calculateNextRun($schedule, $after));

    // Quarterly (every 3 months): Jan 31 -> Apr 30 -> Jul 31
    $schedule = [
      'start_date' => '2026-01-31 08:00:00',
      'frequency_unit' => 'month',
      'frequency_interval' => 3,
      'is_active' => TRUE,
    ];
    $after = new \DateTime('2026-01-31 09:00:00');
    $this->assertEquals('2026-04-30 08:00:00', BAO::calculateNextRun($schedule, $after));

    $after = new \DateTime('2026-04-30 09:00:00');
    $this->assertEquals('2026-07-31 08:00:00', BAO::calculateNextRun($schedule, $after));
  }

  public function testYearlyFrequency() {
    $schedule = [
      'start_date' => '2026-06-15 11:00:00',
      'frequency_unit' => 'year',
      'frequency_interval' => 1,
      'is_active' => TRUE,
    ];
    $after = new \DateTime('2026-06-15 12:00:00');
    $this->assertEquals('2027-06-15 11:00:00', BAO::calculateNextRun($schedule, $after));

    // Leap year leap day: 2024-02-29 clamped to Feb 28 on non-leap years
    $schedule['start_date'] = '2024-02-29 12:00:00';
    $after = new \DateTime('2024-03-01 00:00:00');
    $this->assertEquals('2025-02-28 12:00:00', BAO::calculateNextRun($schedule, $after));

    // Every 4 years: 2024-02-29 -> 2028-02-29
    $schedule['frequency_interval'] = 4;
    $this->assertEquals('2028-02-29 12:00:00', BAO::calculateNextRun($schedule, $after));
  }

}
