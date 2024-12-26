<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Individual;
use Civi\Api4\Relationship;
use Civi\Api4\SearchSegment;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class SearchSegmentTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    foreach (['Activity', 'SearchSegment', 'CustomGroup'] as $entity) {
      civicrm_api4($entity, 'delete', [
        'checkPermissions' => FALSE,
        'where' => [['id', '>', '0']],
      ]);
    }
    // Delete all contacts without a UFMatch.
    $contacts = Contact::get(FALSE)
      ->addSelect('id')
      ->addJoin('UFMatch AS uf_match', 'EXCLUDE')
      ->execute()->column('id');
    Contact::delete(FALSE)
      ->addWhere('id', 'IN', $contacts)
      ->execute();
    parent::tearDown();
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
      ->addValue('description', 'Tiers by donation amount')
      ->addValue('items', [
        [
          'label' => 'Low ball',
          'when' => [['total_amount', '<', 10]],
        ],
        [
          'label' => 'Minor league',
          'when' => [['total_amount', '>=', 10], ['total_amount', '<', 25]],
        ],
        [
          'label' => 'Major league',
          'when' => [['total_amount', '>=', 25], ['total_amount', '<', 40]],
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
    $this->assertEquals('Extra', $getField['type']);
    $this->assertEquals('Select', $getField['input_type']);
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
      'sort' => [['segment_Giving_Tier:label', 'ASC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    // Results should be in alphabetical order by giving tier
    $this->assertEquals('Heavy hitter', $result[0]['columns'][0]['val']);
    $this->assertEquals(56.0, $result[0]['data']['AVG_total_amount']);
    $this->assertEquals(1, $result[0]['data']['COUNT_total_amount']);

    $this->assertEquals('Low ball', $result[1]['columns'][0]['val']);
    $this->assertEquals(1.5, $result[1]['data']['AVG_total_amount']);
    $this->assertEquals(1, $result[1]['data']['COUNT_total_amount']);

    $this->assertEquals('Major league', $result[2]['columns'][0]['val']);
    $this->assertEquals(30.0, $result[2]['data']['AVG_total_amount']);
    $this->assertEquals(3, $result[2]['data']['COUNT_total_amount']);

    $this->assertEquals('Minor league', $result[3]['columns'][0]['val']);
    $this->assertEquals(15.0, $result[3]['data']['AVG_total_amount']);
    $this->assertEquals(2, $result[3]['data']['COUNT_total_amount']);
  }

  /**
   * Tests a segment based on custom data using a bridge join
   */
  public function testSegmentCustomField() {
    CustomGroup::create(FALSE)
      ->addValue('title', 'TestActivitySegment')
      ->addValue('extends', 'Activity')
      ->execute();
    CustomField::create(FALSE)
      ->addValue('label', 'ActColor')
      ->addValue('custom_group_id.name', 'TestActivitySegment')
      ->addValue('html_type', 'Select')
      ->addValue('option_values', ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue', 'k' => 'Black'])
      ->execute();
    $cid = Contact::create(FALSE)->execute()->single()['id'];

    $sampleData = [
      ['TestActivitySegment.ActColor' => 'r'],
      ['TestActivitySegment.ActColor' => 'g'],
      ['TestActivitySegment.ActColor' => 'b'],
      ['TestActivitySegment.ActColor' => 'k'],
      ['TestActivitySegment.ActColor' => 'k'],
      [],
    ];
    Activity::save(FALSE)
      ->addDefault('source_contact_id', $cid)
      ->addDefault('activity_type_id:name', 'Meeting')
      ->addDefault('activity_date_time', 'now')
      ->setRecords($sampleData)->execute();

    SearchSegment::create(FALSE)
      ->addValue('label', 'Activity Cluster')
      ->addValue('entity_name', 'Activity')
      ->addValue('description', 'Clusters based on activity custom field')
      ->addValue('items', [
        [
          'label' => 'Primary Color',
          'when' => [['TestActivitySegment.ActColor:label', 'IN', ['Red', 'Blue']]],
        ],
        [
          'label' => 'Secondary Color',
          'when' => [['TestActivitySegment.ActColor:label', 'NOT IN', ['Red', 'Blue', 'Black']]],
        ],
        [
          'label' => 'Not a Color!',
        ],
      ])
      ->execute();

    $getField = Activity::getFields(FALSE)
      ->addWhere('name', '=', 'segment_Activity_Cluster')
      ->setLoadOptions(TRUE)
      ->execute()->single();
    $this->assertEquals('Activity Cluster', $getField['label']);
    $this->assertEquals(['Primary Color', 'Secondary Color', 'Not a Color!'], $getField['options']);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'Activity_01.segment_Activity_Cluster:label',
            'COUNT(Activity_01.id) AS COUNT_id',
          ],
          'where' => [['id', '=', $cid]],
          'groupBy' => [
            'Activity_01.segment_Activity_Cluster',
          ],
          'join' => [
            [
              'Activity AS Activity_01',
              'LEFT',
              'ActivityContact',
              ['id', '=', 'Activity_01.contact_id'],
              ['Activity_01.record_type_id:name', '=', '"Activity Source"'],
            ],
          ],
          'having' => [],
        ],
      ],
      'sort' => [['Activity_01.segment_Activity_Cluster:label', 'ASC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(3, $result);

    $this->assertEquals('Not a Color!', $result[0]['columns'][0]['val']);
    $this->assertEquals(3, $result[0]['data']['COUNT_id']);

    $this->assertEquals('Primary Color', $result[1]['columns'][0]['val']);
    $this->assertEquals(2, $result[1]['data']['COUNT_id']);

    $this->assertEquals('Secondary Color', $result[2]['columns'][0]['val']);
    $this->assertEquals(1, $result[2]['data']['COUNT_id']);
  }

  /**
   * Uses a calc field as the basis for a segment,
   * and fetches it via related contact join, just to test that extra bit of complexity
   */
  public function testSegmentCalcField() {
    $cid = Contact::create(FALSE)->execute()->single()['id'];
    $sampleData = [
      ['birth_date' => 'now - 1 year - 1 month'],
      ['birth_date' => 'now - 12 year - 1 month'],
      ['birth_date' => 'now - 13 year - 1 month'],
      ['birth_date' => 'now - 30 year - 1 month'],
      ['birth_date' => 'now - 33 year - 1 month'],
      [],
    ];
    Individual::save(FALSE)
      ->setRecords($sampleData)
      ->addChain('rel', Relationship::create()
        ->addValue('relationship_type_id', 1)
        ->addValue('contact_id_b', $cid)
        ->addValue('contact_id_a', '$id')
      )
      ->execute();

    SearchSegment::create(FALSE)
      ->addValue('label', 'Age Range')
      ->addValue('entity_name', 'Contact')
      ->addValue('description', 'Babies, Children, Adults')
      ->addValue('items', [
        [
          'label' => 'Baby',
          'when' => [['age_years', '<', 2]],
        ],
        [
          'label' => 'Child',
          'when' => [['age_years', '>=', 2], ['age_years', '<', 18]],
        ],
        [
          'label' => 'Adult',
          'when' => [['age_years', '>=', 18]],
        ],
      ])
      ->execute();

    $field = Individual::getFields(FALSE)
      ->addWhere('name', '=', 'segment_Age_Range')
      ->execute()->single();
    $this->assertEquals('Age Range', $field['label']);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'page:1',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => [
            'Related_Contact.segment_Age_Range:label',
            'COUNT(Related_Contact.id) AS COUNT_id',
          ],
          'where' => [['id', '=', $cid]],
          'groupBy' => [
            'Related_Contact.segment_Age_Range',
          ],
          'join' => [
            [
              'Contact AS Related_Contact',
              'INNER',
              'RelationshipCache',
              ['id', '=', 'Related_Contact.far_contact_id'],
              ['Related_Contact.near_relation:name', '=', '"Child of"'],
            ],
          ],
          'having' => [],
        ],
      ],
      'sort' => [['Related_Contact.segment_Age_Range:label', 'DESC']],
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(4, $result);

    $this->assertEquals('Child', $result[0]['columns'][0]['val']);
    $this->assertEquals(2, $result[0]['data']['COUNT_id']);

    $this->assertEquals('Baby', $result[1]['columns'][0]['val']);
    $this->assertEquals(1, $result[1]['data']['COUNT_id']);

    $this->assertEquals('Adult', $result[2]['columns'][0]['val']);
    $this->assertEquals(2, $result[2]['data']['COUNT_id']);

    $this->assertNull($result[3]['columns'][0]['val']);
    $this->assertEquals(1, $result[3]['data']['COUNT_id']);
  }

}
