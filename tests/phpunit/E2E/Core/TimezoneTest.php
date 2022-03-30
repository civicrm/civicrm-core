<?php

namespace E2E\Core;

/**
 * If the UF-integration configures PHP and MySQL with equivalent
 * timezone options, then they will agree on how to convert to/from
 * universal time.
 *
 * @package E2E\Core
 * @group e2e
 */
class TimezoneTest extends \CiviEndToEndTestCase {

  /**
   * @var array
   *   Example contact record.
   */
  protected static $who;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    static::$who = civicrm_api4('Contact', 'create', [
      'values' => [
        'contact_type' => 'Individual',
        'first_name' => 'David',
        'last_name' => 'Whittaker',
      ],
    ])->first();
  }

  public static function tearDownAfterClass(): void {
    civicrm_api4('Contact', 'delete', [
      'where' => [['id', '=', static::$who['id']]],
    ]);
    parent::tearDownAfterClass();
  }

  public function getDates(): array {
    $dates = [];
    $n = 0;
    foreach ([2020, 2022, 2024] as $year) {
      foreach (range(1, 12) as $month) {
        foreach ([1, 16, 28] as $day) {
          $date = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, 13, ($n++) % 60);
          $dates[$date] = [$date];
        }
      }
    }
    return $dates;
  }

  /**
   *
   * @dataProvider getDates
   */
  public function testEpochConversion(string $date): void {
    $phpEpoch = strtotime($date);
    $sqlEpoch = (int) \CRM_Core_DAO::singleValueQuery('SELECT UNIX_TIMESTAMP(%1)', [
      1 => [$date, 'String'],
    ]);
    $this->assertEquals($phpEpoch, $sqlEpoch, "Mismatched epochs for $date: php($phpEpoch) != sql($sqlEpoch)");
  }

  /**
   * @param string $date
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @dataProvider getDates
   */
  public function testWriteRead(string $date): void {
    \CRM_Core_DAO::executeQuery('UPDATE civicrm_contact SET modified_date = FROM_UNIXTIME(%1) WHERE id = %2', [
      1 => [strtotime($date), 'Integer'],
      2 => [static::$who['id'], 'Positive'],
    ]);
    $api3 = civicrm_api3('Contact', 'get', [
      'id' => static::$who['id'],
      'return' => 'modified_date',
    ]);
    $api3 = $api3['values'][static::$who['id']];
    $api4 = civicrm_api4('Contact', 'get', [
      'where' => [['id', '=', static::$who['id']]],
      'select' => ['modified_date'],
    ])->single();

    $this->assertEquals($date, $api4['modified_date'], 'Api3 value should match original');
    $this->assertEquals($date, $api3['modified_date'], 'Api4 value should match original');
  }

}
