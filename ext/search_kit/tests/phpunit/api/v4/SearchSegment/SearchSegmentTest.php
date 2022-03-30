<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\SearchSegment;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchSegmentTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test running a searchDisplay with a numeric range segment.
   */
  public function testRangeSearchSegment() {
    $cid = Contact::create(FALSE)->execute()->single()['id'];

    $sampleData = [
      ['total_amount' => 1.5],
      ['total_amount' => 10],
      ['total_amount' => 20],
      ['total_amount' => 25],
      ['total_amount' => 32],
      ['total_amount' => 33],
      ['total_amount' => 56],
    ];
    Contribution::save(FALSE)
      ->addDefault('contact_id', $cid)
      ->addDefault('financial_type_id:name', 'Donation')
      ->addDefault('receive_date', 'now')
      ->setRecords($sampleData)->execute();

    SearchSegment::create(FALSE)
      ->addValue('label', 'Giving Tier')
      ->addValue('entity_name', 'Contribution')
      ->addValue('field_name', 'total_amount')
      ->addValue('description', 'Tiers by donation amount')
      ->addValue('items', [
        // Only a max means no minimum
        [
          'label' => 'Low ball',
          'max' => 10,
        ],
        [
          'label' => 'Minor league',
          'min' => 10,
          'max' => 25,
        ],
        [
          'label' => 'Major league',
          'min' => 25,
          'max' => 40,
        ],
        // No conditions makes this the ELSE clause
        [
          'label' => 'Heavy hitter',
        ],
      ])
      ->execute();

    $getField = Contribution::getFields(FALSE)
      ->addWhere('name', '=', 'segment_Giving_Tier')
      ->setLoadOptions(TRUE)
      ->execute()->single();

    $this->assertEquals('Giving Tier', $getField['label']);
    $this->assertEquals(['Low ball', 'Minor league', 'Major league', 'Heavy hitter'], $getField['options']);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => [
            'segment_Giving_Tier:label',
            'AVG(total_amount) AS AVG_total_amount',
            'COUNT(total_amount) AS COUNT_total_amount',
          ],
          'where' => [['contact_id', '=', $cid]],
          'groupBy' => [
            'segment_Giving_Tier',
          ],
          'join' => [],
          'having' => [],
        ],
      ],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $this->assertEquals('Low ball', $result[0]['columns'][0]['val']);
    $this->assertEquals(1.5, $result[0]['data']['AVG_total_amount']);
    $this->assertEquals(1, $result[0]['data']['COUNT_total_amount']);

    $this->assertEquals('Minor league', $result[1]['columns'][0]['val']);
    $this->assertEquals(15.0, $result[1]['data']['AVG_total_amount']);
    $this->assertEquals(2, $result[1]['data']['COUNT_total_amount']);

    $this->assertEquals('Major league', $result[2]['columns'][0]['val']);
    $this->assertEquals(30.0, $result[2]['data']['AVG_total_amount']);
    $this->assertEquals(3, $result[2]['data']['COUNT_total_amount']);

    $this->assertEquals('Heavy hitter', $result[3]['columns'][0]['val']);
    $this->assertEquals(56.0, $result[3]['data']['AVG_total_amount']);
    $this->assertEquals(1, $result[3]['data']['COUNT_total_amount']);
  }

}
