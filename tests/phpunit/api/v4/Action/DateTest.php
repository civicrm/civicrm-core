<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Action;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\Relationship;
use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class DateTest extends Api4TestBase implements TransactionalInterface {

  public function testRelationshipDate(): void {
    $c1 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'one')
      ->execute()
      ->first()['id'];
    $c2 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'two')
      ->execute()
      ->first()['id'];
    $r = Relationship::create()
      ->addValue('contact_id_a', $c1)
      ->addValue('contact_id_b', $c2)
      ->addValue('relationship_type_id', 1)
      ->addValue('start_date', 'now')
      ->addValue('end_date', 'now + 1 week')
      ->execute()
      ->first()['id'];
    $result = Relationship::get()
      ->addWhere('start_date', '=', 'now')
      ->addWhere('end_date', '>', 'now + 1 day')
      ->execute()
      ->indexBy('id');
    $this->assertArrayHasKey($r, $result);
    $result = Relationship::get()
      ->addWhere('start_date', '<', 'now')
      ->execute()
      ->indexBy('id');
    $this->assertArrayNotHasKey($r, $result);
  }

  public function testRelativeDateRanges(): void {
    $c1 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'one')
      ->execute()
      ->first()['id'];

    // Avoid problems with `strtotime(<date arithmetic expression>)` giving
    // impossible dates like April 31 which roll over and then don't match.
    $thisMonth = (int) date('m');
    $lastMonth = ($thisMonth === 1 ? 12 : $thisMonth - 1);
    $nextMonth = ($thisMonth === 12 ? 1 : $thisMonth + 1);
    $lastMonthsYear = ($thisMonth === 1 ? date('Y') - 1 : date('Y'));
    $nextMonthsYear = ($thisMonth === 12 ? date('Y') + 1 : date('Y'));

    $act = Activity::save()
      ->setDefaults(['activity_type_id:name' => 'Meeting', 'source_contact_id' => $c1])
      ->addRecord(['activity_date_time' => (date('Y') - 3) . '-' . date('m-01 H:i:s')])
      ->addRecord(['activity_date_time' => (date('Y') - 1) . '-' . date('m-01 H:i:s')])
      ->addRecord(['activity_date_time' => "{$lastMonthsYear}-{$lastMonth}-01 " . date('H:i:s')])
      ->addRecord(['activity_date_time' => 'now'])
      ->addRecord(['activity_date_time' => "{$nextMonthsYear}-{$nextMonth}-01 " . date('H:i:s')])
      ->addRecord(['activity_date_time' => (date('Y') + 1) . '-' . date('m-01 H:i:s')])
      ->addRecord(['activity_date_time' => (date('Y') + 3) . '-' . date('m-01 H:i:s')])
      ->execute()->column('id');

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '>', 'previous.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertContains($act[3], $result);
    $this->assertContains($act[4], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '>', 'this.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertNotContains($act[2], $result);
    $this->assertNotContains($act[3], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    // Ensure it also works if the DATE() function is used
    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('DATE(activity_date_time)', '>', 'this.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertNotContains($act[2], $result);
    $this->assertNotContains($act[3], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '<', 'previous.year')
      ->execute()->column('id');
    $this->assertContains($act[0], $result);
    $this->assertNotContains($act[4], $result);
    $this->assertNotContains($act[5], $result);
    $this->assertNotContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '=', 'next.month')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertNotContains($act[2], $result);
    $this->assertContains($act[4], $result);
    $this->assertNotContains($act[5], $result);
    $this->assertNotContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', 'BETWEEN', ['previous.year', 'this.year'])
      ->execute()->column('id');
    $this->assertContains($act[2], $result);
    $this->assertContains($act[3], $result);
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[6], $result);
  }

  public function testJoinOnRelativeDate(): void {
    $c1 = Contact::create(FALSE)
      ->addValue('first_name', 'Contributor')
      ->addValue('last_name', 'One')
      ->execute()
      ->first()['id'];

    // Contribution from last year
    Contribution::create(FALSE)
      ->addValue('contact_id', $c1)
      ->addValue('receive_date', (date('Y') - 1) . '-06-01')
      ->addValue('financial_type_id', 1)
      ->addValue('total_amount', 12)
      ->execute();

    // Contribution from this year
    Contribution::create(FALSE)
      ->addValue('contact_id', $c1)
      ->addValue('receive_date', date('Y') . '-06-01')
      ->addValue('financial_type_id', 1)
      ->addValue('total_amount', 6)
      ->execute();

    // Contribution from 2 years ago
    Contribution::create(FALSE)
      ->addValue('contact_id', $c1)
      ->addValue('receive_date', (date('Y') - 2) . '-06-01')
      ->addValue('financial_type_id', 1)
      ->addValue('total_amount', 24)
      ->execute();

    // Find contribution from last year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '=', '"previous.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(1, $contact);
    $this->assertEquals(12, $contact[0]['contribution.total_amount']);

    // Find contributions not from last year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '!=', '"previous.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(2, $contact);

    // Find contributions To end of previous calendar year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '=', '"earlier.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(2, $contact);

    // Find contributions not To end of previous calendar year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '!=', '"earlier.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(1, $contact);
    $this->assertEquals(6, $contact[0]['contribution.total_amount']);

    // Find contributions From start of current calendar year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '=', '"greater.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(1, $contact);
    $this->assertEquals(6, $contact[0]['contribution.total_amount']);

    // Find contributions not From start of current calendar year
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('id', 'contribution.total_amount')
      ->setJoin([
        ['Contribution AS contribution', FALSE, NULL, ['contribution.receive_date', '!=', '"greater.year"']],
      ])
      ->addWhere('id', '=', $c1)
      ->execute();
    $this->assertCount(2, $contact);
  }

  public function testHavingDateFunctions(): void {
    $contacts = $this->saveTestRecords('Individual', [
      'records' => 2,
    ]);
    $participants = $this->saveTestRecords('Participant', [
      'records' => [
        ['contact_id' => $contacts[0]['id'], 'register_date' => 'now - 1 year'],
        ['contact_id' => $contacts[1]['id'], 'register_date' => 'now - 3 year'],
        ['contact_id' => $contacts[0]['id'], 'register_date' => 'now - 4 year'],
      ],
    ]);

    $api = Contact::get(FALSE)
      ->addJoin('Participant AS participant', 'LEFT', ['id', '=', 'participant.contact_id'])
      ->addGroupBy('id')
      ->addSelect('id')
      ->addSelect('MAX(participant.register_date) AS max_register_date');

    $result = $api->setHaving([
      ['max_register_date', '<', 'now - 2 year'],
    ])->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($contacts[1]['id'], $result[0]['id']);

    $result = $api->setHaving([
      ['max_register_date', '>', 'now - 2 year'],
    ])->execute();
    $this->assertCount(1, $result);
    $this->assertEquals($contacts[0]['id'], $result[0]['id']);
  }

  public function testDateFunctionsInWhereClause(): void {
    $ids = $this->saveTestRecords('Activity', [
      'records' => [
        ['activity_date_time' => 'now - 3 year'],
        ['activity_date_time' => 'now - 2 year'],
        ['activity_date_time' => 'now - 1 year'],
        ['activity_date_time' => 'now + 2 month'],
      ],
    ])->column('id');

    // Fetch all activities from the last 2 calendar years
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addWhere('YEAR(activity_date_time)', '>=', 'now - 1 year')
      ->execute()->column('id');

    $this->assertCount(2, $result);
    $this->assertNotContains($ids[0], $result);
    $this->assertNotContains($ids[1], $result);

    // Fetch all activities whose month matches the current month
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addWhere('MONTH(activity_date_time)', '=', 'now')
      ->execute()->column('id');

    $this->assertCount(3, $result);
    $this->assertNotContains($ids[3], $result);

    // Fetch all activities from the last 3 calendar years using EXTRACT
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addWhere('EXTRACT(YEAR FROM activity_date_time)', '>=', 'now - 2 year')
      ->execute()->column('id');

    $this->assertCount(3, $result);
    $this->assertNotContains($ids[0], $result);

    // Fetch all activities whose month matches the current month+2
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addWhere('EXTRACT(MONTH FROM activity_date_time)', '=', 'now + 2 month')
      ->execute()->column('id');

    $this->assertCount(1, $result);
    $this->assertContains($ids[3], $result);

    // Ensure SQL functions work in clause 2
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addWhere('EXTRACT(MONTH FROM activity_date_time)', '=', "MONTH(CURDATE())", TRUE)
      ->execute()->column('id');

    $this->assertCount(3, $result);
    $this->assertNotContains($ids[3], $result);
  }

  /**
   * Values that strtotime() cannot parse or would roll over into a different date.
   */
  public static function invalidDateProvider(): array {
    return [
      'text' => ['not a date'],
      'string null' => ['null'],
      'month 13' => ['1980-13-01'],
      'April 31' => ['1980-04-31'],
      'February 29 in a common year' => ['1981-02-29'],
      'impossible day with time' => ['2026-02-30 10:00:00'],
      'zero date' => ['0000-00-00'],
      'negative timestamp' => ['-00011130000000'],
      'whitespace' => [' '],
    ];
  }

  /**
   * @dataProvider invalidDateProvider
   */
  public function testCreateRejectsInvalidDate(string $value): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Invalid date value');
    Contact::create(FALSE)
      ->addValue('first_name', 'c')
      ->addValue('birth_date', $value)
      ->execute();
  }

  /**
   * @dataProvider invalidDateProvider
   */
  public function testWhereRejectsInvalidDate(string $value): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Invalid date value');
    Activity::get(FALSE)
      ->addWhere('activity_date_time', '>', $value)
      ->execute();
  }

  public static function patternOperatorProvider(): array {
    $operators = ['LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP', 'REGEXP BINARY', 'NOT REGEXP BINARY'];
    $cases = [];
    foreach ($operators as $operator) {
      $cases["Date $operator"] = ['birth_date', $operator];
      $cases["Timestamp $operator"] = ['created_date', $operator];
    }
    return $cases;
  }

  /**
   * @dataProvider patternOperatorProvider
   */
  public function testWhereRejectsPatternOnDate(string $field, string $operator): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage("The $operator operator only accepts a relative date");
    Contact::get(FALSE)
      ->addWhere($field, $operator, '2020-%')
      ->execute();
  }

  public function testWhereLikeRelativeDate(): void {
    $thisYear = $this->createTestRecord('Activity', ['activity_date_time' => 'now'])['id'];
    $lastYear = $this->createTestRecord('Activity', ['activity_date_time' => '-1 year'])['id'];
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', [$thisYear, $lastYear])
      ->addWhere('activity_date_time', 'LIKE', 'this.year')
      ->execute()->column('id');
    $this->assertEquals([$thisYear], $result);
    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', [$thisYear, $lastYear])
      ->addWhere('activity_date_time', 'NOT LIKE', 'this.year')
      ->execute()->column('id');
    $this->assertEquals([$lastYear], $result);
  }

  /**
   * A quoted date in an expression condition is passed to permission checks without its quotes.
   */
  public function testWhereExpressionWithQuotedDate(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $id = $this->createTestRecord('Contact', ['birth_date' => '1980-01-01'])['id'];
    $result = Contact::get()
      ->addWhere('id', '=', $id)
      ->addWhere('birth_date', '=', '"1980-01-01"', TRUE)
      ->execute();
    $this->assertCount(1, $result);
  }

  public function testValidDateInput(): void {
    $values = [
      '2024-02-29' => '2024-02-29',
      '20260101000000' => '2026-01-01',
      '2026-01-01T10:00:00' => '2026-01-01',
      'last day of february 2025' => '2025-02-28',
      'first monday of january 2026' => '2026-01-05',
      '' => NULL,
    ];
    foreach ($values as $input => $expected) {
      $id = $this->createTestRecord('Contact', ['birth_date' => $input])['id'];
      $birthDate = Contact::get(FALSE)->addWhere('id', '=', $id)->execute()->single()['birth_date'];
      $this->assertSame($expected, $birthDate, "Input: '$input'");
    }
    $contact = $this->createTestRecord('Contact', ['birth_date' => '2026-01-01']);
    Contact::update(FALSE)->addWhere('id', '=', $contact['id'])->addValue('birth_date', NULL)->execute();
    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addWhere('birth_date', 'IS NULL')
      ->execute();
    $this->assertCount(1, $result);
  }

}
