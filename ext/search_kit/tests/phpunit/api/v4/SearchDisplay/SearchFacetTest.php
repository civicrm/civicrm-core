<?php
namespace api\v4\SearchDisplay;

require_once __DIR__ . '/../../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

use api\v4\Api4TestBase;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchFacetTest extends Api4TestBase implements TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test facet stats on a field that has options (e.g. activity_type_id).
   */
  public function testFacetFieldWithOptions(): void {
    $subject = uniqid(__FUNCTION__);
    $records = [
      ['activity_type_id:name' => 'Meeting'],
      ['activity_type_id:name' => 'Meeting'],
      ['activity_type_id:name' => 'Meeting'],
      ['activity_type_id:name' => 'Phone Call'],
      ['activity_type_id:name' => 'Phone Call'],
    ];
    $this->saveTestRecords('Activity', [
      'records' => $records,
      'defaults' => ['subject' => $subject, 'activity_date_time' => 'now'],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'facet:activity_type_id',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'subject', 'activity_type_id:label'],
          'where' => [['subject', '=', $subject]],
        ],
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $resultsByTypeName = $result->column(NULL, 'activity_type_id:name');
    $this->assertArrayHasKey('Meeting', $resultsByTypeName);
    $this->assertArrayHasKey('Phone Call', $resultsByTypeName);

    $meeting = $resultsByTypeName['Meeting'];
    $this->assertNotEmpty($meeting['activity_type_id']);
    $this->assertEquals('Meeting', $meeting['activity_type_id:name']);
    $this->assertEquals('Meeting', $meeting['activity_type_id:label']);
    $this->assertEquals(3, $meeting['count']);

    $phoneCall = $resultsByTypeName['Phone Call'];
    $this->assertNotEmpty($phoneCall['activity_type_id']);
    $this->assertEquals('Phone Call', $phoneCall['activity_type_id:name']);
    $this->assertEquals('Phone Call', $phoneCall['activity_type_id:label']);
    $this->assertEquals(2, $phoneCall['count']);
  }

  /**
   * Test facet stats on a field without options (e.g. first_name).
   */
  public function testFacetFieldWithoutOptions(): void {
    $lastName = uniqid(__FUNCTION__);
    $records = [
      ['first_name' => 'Alice'],
      ['first_name' => 'Alice'],
      ['first_name' => 'Bob'],
    ];
    $this->saveTestRecords('Individual', [
      'records' => $records,
      'defaults' => ['last_name' => $lastName],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'facet:first_name',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name', 'last_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $resultsByName = $result->column(NULL, 'first_name');
    $this->assertArrayHasKey('Alice', $resultsByName);
    $this->assertArrayHasKey('Bob', $resultsByName);

    $this->assertEquals(2, $resultsByName['Alice']['count']);
    $this->assertArrayNotHasKey('first_name:name', $resultsByName['Alice']);
    $this->assertArrayNotHasKey('first_name:label', $resultsByName['Alice']);

    $this->assertEquals(1, $resultsByName['Bob']['count']);
    $this->assertArrayNotHasKey('first_name:name', $resultsByName['Bob']);
    $this->assertArrayNotHasKey('first_name:label', $resultsByName['Bob']);
  }

  /**
   * Test that runtime filters properly constrain facet results.
   */
  public function testFacetWithFilters(): void {
    $lastName = uniqid(__FUNCTION__);
    $records = [
      ['first_name' => 'Alice', 'gender_id:name' => 'Female'],
      ['first_name' => 'Alice', 'gender_id:name' => 'Female'],
      ['first_name' => 'Alice', 'gender_id:name' => 'Male'],
      ['first_name' => 'Bob', 'gender_id:name' => 'Male'],
    ];
    $this->saveTestRecords('Individual', [
      'records' => $records,
      'defaults' => ['last_name' => $lastName],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'facet:gender_id',
      'savedSearch' => [
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'first_name'],
          'where' => [['last_name', '=', $lastName]],
        ],
      ],
      'filters' => [
        'first_name' => 'Alice',
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $resultsByGender = $result->column(NULL, 'gender_id:name');
    $this->assertArrayHasKey('Female', $resultsByGender);
    $this->assertArrayHasKey('Male', $resultsByGender);
    $this->assertEquals(2, $resultsByGender['Female']['count']);
    $this->assertEquals('Female', $resultsByGender['Female']['gender_id:label']);
    $this->assertEquals(1, $resultsByGender['Male']['count']);
    $this->assertEquals('Male', $resultsByGender['Male']['gender_id:label']);
  }

  /**
   * Test facet stats on a joined field that has options.
   */
  public function testFacetWithJoinedField(): void {
    $tag = uniqid(__FUNCTION__);
    $contactFemale = $this->createTestRecord('Individual', ['first_name' => 'Jane', 'last_name' => $tag, 'gender_id:name' => 'Female']);
    $contactMale = $this->createTestRecord('Individual', ['first_name' => 'John', 'last_name' => $tag, 'gender_id:name' => 'Male']);

    $this->saveTestRecords('Activity', [
      'records' => [
        ['source_record_id' => $contactFemale['id']],
        ['source_record_id' => $contactFemale['id']],
        ['source_record_id' => $contactMale['id']],
      ],
      'defaults' => ['activity_type_id:name' => 'Meeting', 'activity_date_time' => 'now'],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'facet:Activity_Contact_01.gender_id',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id'],
          'join' => [
            ['Contact AS Activity_Contact_01', 'INNER', ['source_record_id', '=', 'Activity_Contact_01.id']],
          ],
          'where' => [['Activity_Contact_01.last_name', '=', $tag]],
        ],
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);

    $resultsByGender = $result->column(NULL, 'Activity_Contact_01.gender_id:name');
    $this->assertArrayHasKey('Female', $resultsByGender);
    $this->assertArrayHasKey('Male', $resultsByGender);

    $this->assertEquals('Female', $resultsByGender['Female']['Activity_Contact_01.gender_id:label']);
    $this->assertEquals(2, $resultsByGender['Female']['count']);

    $this->assertEquals('Male', $resultsByGender['Male']['Activity_Contact_01.gender_id:label']);
    $this->assertEquals(1, $resultsByGender['Male']['count']);
  }

  /**
   * Test that facet replaces existing select, groupBy, orderBy and limit from saved search.
   */
  public function testFacetWithExistingGroupByAndComplexSelect(): void {
    $subject = uniqid(__FUNCTION__);
    $records = [
      ['activity_type_id:name' => 'Meeting'],
      ['activity_type_id:name' => 'Meeting'],
      ['activity_type_id:name' => 'Phone Call'],
    ];
    $this->saveTestRecords('Activity', [
      'records' => $records,
      'defaults' => ['subject' => $subject, 'activity_date_time' => 'now'],
    ]);

    $params = [
      'checkPermissions' => FALSE,
      'return' => 'facet:activity_type_id',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'subject', 'status_id:label', 'COUNT(id) AS count_id'],
          'groupBy' => ['status_id'],
          'orderBy' => ['status_id' => 'ASC'],
          'limit' => 1,
          'where' => [['subject', '=', $subject]],
        ],
      ],
      'display' => NULL,
    ];

    $result = civicrm_api4('SearchDisplay', 'run', $params);
    $this->assertCount(2, $result);
    $resultsByTypeName = $result->column(NULL, 'activity_type_id:name');
    $this->assertEquals(2, $resultsByTypeName['Meeting']['count']);
    $this->assertEquals(1, $resultsByTypeName['Phone Call']['count']);
    $this->assertArrayNotHasKey('count_id', $resultsByTypeName['Meeting']);
    $this->assertArrayNotHasKey('status_id:label', $resultsByTypeName['Meeting']);
  }

}
