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

use api\v4\UnitTestCase;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;

/**
 * @group headless
 */
class SqlFunctionTest extends UnitTestCase {

  public function testGetFunctions() {
    $functions = array_column(\CRM_Api4_Page_Api4Explorer::getSqlFunctions(), NULL, 'name');
    $this->assertArrayHasKey('SUM', $functions);
    $this->assertArrayNotHasKey('', $functions);
    $this->assertArrayNotHasKey('SqlFunction', $functions);
    $this->assertEquals(1, $functions['MAX']['params'][0]['expr']);
  }

  public function testGroupAggregates() {
    $cid = Contact::create(FALSE)->addValue('first_name', 'bill')->execute()->first()['id'];
    Contribution::save(FALSE)
      ->setDefaults(['contact_id' => $cid, 'financial_type_id:name' => 'Donation'])
      ->setRecords([
        ['total_amount' => 100, 'receive_date' => '2020-01-01'],
        ['total_amount' => 200, 'receive_date' => '2020-01-01'],
        ['total_amount' => 300, 'receive_date' => '2020-01-01', 'financial_type_id:name' => 'Member Dues'],
        ['total_amount' => 400, 'receive_date' => '2020-01-01', 'financial_type_id:name' => 'Event Fee'],
      ])
      ->execute();

    // Test AVG, SUM, MAX, MIN, COUNT
    $agg = Contribution::get(FALSE)
      ->addGroupBy('contact_id')
      ->addWhere('contact_id', '=', $cid)
      ->addSelect('AVG(total_amount) AS average')
      ->addSelect('SUM(total_amount)')
      ->addSelect('MAX(total_amount)')
      ->addSelect('MIN(total_amount)')
      ->addSelect('COUNT(*) AS count')
      ->execute()
      ->first();
    $this->assertTrue(250.0 === $agg['average']);
    $this->assertTrue(1000.0 === $agg['SUM:total_amount']);
    $this->assertTrue(400.0 === $agg['MAX:total_amount']);
    $this->assertTrue(100.0 === $agg['MIN:total_amount']);
    $this->assertTrue(4 === $agg['count']);

    // Test GROUP_CONCAT
    $agg = Contribution::get(FALSE)
      ->addGroupBy('contact_id')
      ->addWhere('contact_id', '=', $cid)
      ->addSelect('GROUP_CONCAT(financial_type_id:name)')
      ->addSelect('COUNT(*) AS count')
      ->execute()
      ->first();

    $this->assertTrue(4 === $agg['count']);
    $this->assertContains('Donation', $agg['GROUP_CONCAT:financial_type_id:name']);

    // Test GROUP_CONCAT with a CONCAT as well
    $agg = Contribution::get(FALSE)
      ->addGroupBy('contact_id')
      ->addWhere('contact_id', '=', $cid)
      ->addSelect("GROUP_CONCAT(CONCAT(financial_type_id, ', ', contact_id, ', ', total_amount))")
      ->addSelect('COUNT(*) AS count')
      ->execute()
      ->first();

    $this->assertTrue(4 === $agg['count']);
    $this->assertContains('1, ' . $cid . ', 100.00', $agg['GROUP_CONCAT:financial_type_id_contact_id_total_amount']);
  }

  public function testGroupHaving() {
    $cid = Contact::create(FALSE)->addValue('first_name', 'donor')->execute()->first()['id'];
    Contribution::save(FALSE)
      ->setDefaults(['contact_id' => $cid, 'financial_type_id' => 1])
      ->setRecords([
        ['total_amount' => 100, 'receive_date' => '2020-02-02'],
        ['total_amount' => 200, 'receive_date' => '2020-02-02'],
        ['total_amount' => 300, 'receive_date' => '2020-03-03'],
        ['total_amount' => 400, 'receive_date' => '2020-04-04'],
      ])
      ->execute();
    $result = Contribution::get(FALSE)
      ->addGroupBy('contact_id')
      ->addGroupBy('receive_date')
      ->addSelect('contact_id')
      ->addSelect('receive_date')
      ->addSelect('AVG(total_amount) AS average')
      ->addSelect('SUM(total_amount)')
      ->addSelect('MAX(total_amount)')
      ->addSelect('MIN(total_amount)')
      ->addSelect('COUNT(*) AS count')
      ->addOrderBy('receive_date')
      ->addHaving('contact_id', '=', $cid)
      ->addHaving('receive_date', '<', '2020-04-01')
      ->execute();
    $this->assertCount(2, $result);
    $this->assertEquals(150, $result[0]['average']);
    $this->assertEquals(300, $result[1]['average']);
    $this->assertEquals(300, $result[0]['SUM:total_amount']);
    $this->assertEquals(300, $result[1]['SUM:total_amount']);
    $this->assertEquals(200, $result[0]['MAX:total_amount']);
    $this->assertEquals(100, $result[0]['MIN:total_amount']);
    $this->assertEquals(2, $result[0]['count']);
    $this->assertEquals(1, $result[1]['count']);
  }

}
