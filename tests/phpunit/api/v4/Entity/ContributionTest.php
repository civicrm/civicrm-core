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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\Contribution;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class ContributionTest extends Api4TestBase implements TransactionalInterface {

  public function testGetWithJoinOnFinancialType(): void {
    $cid = $this->createTestRecord('Individual')['id'];
    $fid = $this->createTestRecord('FinancialType')['id'];
    $this->saveTestRecords('Contribution', [
      'records' => [
        ['financial_type_id' => $fid],
        ['financial_type_id' => $fid],
        ['financial_type_id' => $fid],
        ['financial_type_id' => 1],
      ],
      'defaults' => [
        'contact_id' => $cid,
      ],
    ]);

    $apiParams = [
      'select' => [
        'COUNT(id) AS COUNT_id',
        'GROUP_CONCAT(DISTINCT financial.name) AS financial_name',
        'SUM(net_amount) AS SUM_net_amount',
      ],
      'where' => [
        ['contact_id', 'IN', [$cid]],
      ],
      'groupBy' => [
        'financial_type_id',
      ],
      'join' => [
        [
          'FinancialType AS financial',
          'INNER',
          ['financial_type_id', '=', 'financial.id'],
          ['financial.id', 'IN', [$fid]],
        ],
      ],
    ];
    $result = civicrm_api4('Contribution', 'get', $apiParams);
    $this->assertEquals(3, $result[0]['COUNT_id']);
  }

  public function testGetByDate(): void {
    $cid = $this->createTestRecord('Individual')['id'];
    $this->saveTestRecords('Contribution', [
      'records' => [
        ['receive_date' => '2020-02-02 13:00:00'],
        ['receive_date' => '2020-02-02 01:00:00'],
        ['receive_date' => '2020-02-03 00:00:00'],
      ],
      'defaults' => [
        'contact_id' => $cid,
      ],
    ]);

    $result = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addWhere('receive_date', '=', '2020-02-02')
      ->execute();

    $this->assertEquals(2, count($result));
  }

}
