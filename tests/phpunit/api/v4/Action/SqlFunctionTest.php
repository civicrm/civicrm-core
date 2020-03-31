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
 *
 */


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;

/**
 * @group headless
 */
class SqlFunctionTest extends UnitTestCase {

  public function testGroupAggregates() {
    $cid = Contact::create()->setCheckPermissions(FALSE)->addValue('first_name', 'bill')->execute()->first()['id'];
    Contribution::save()
      ->setCheckPermissions(FALSE)
      ->setDefaults(['contact_id' => $cid, 'financial_type_id' => 1])
      ->setRecords([
        ['total_amount' => 100, 'receive_date' => '2020-01-01'],
        ['total_amount' => 200, 'receive_date' => '2020-01-01'],
        ['total_amount' => 300, 'receive_date' => '2020-01-01'],
        ['total_amount' => 400, 'receive_date' => '2020-01-01'],
      ])
      ->execute();
    $agg = Contribution::get()
      ->setCheckPermissions(FALSE)
      ->addGroupBy('contact_id')
      ->addWhere('contact_id', '=', $cid)
      ->addSelect('AVG(total_amount) AS average')
      ->addSelect('SUM(total_amount)')
      ->addSelect('MAX(total_amount)')
      ->addSelect('MIN(total_amount)')
      ->addSelect('COUNT(*) AS count')
      ->execute()
      ->first();
    $this->assertEquals(250, $agg['average']);
    $this->assertEquals(1000, $agg['SUM:total_amount']);
    $this->assertEquals(400, $agg['MAX:total_amount']);
    $this->assertEquals(100, $agg['MIN:total_amount']);
    $this->assertEquals(4, $agg['count']);
  }

}
