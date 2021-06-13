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

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class TranslationTest extends UnitTestCase {

  protected $ids = [];

  public function getCreateOKExamples() {
    $es = [];

    $es['asDraft'] = [
      [
        'status_id:name' => 'draft',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'description',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello world',
      ],
    ];

    $es['defaultStatus'] = [
      [
        'entity_table' => 'civicrm_event',
        'entity_field' => 'title',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello title',
      ],
    ];

    return $es;
  }

  public function getCreateBadExamples() {
    $es = [];

    $es['badStatus'] = [
      [
        'status_id:name' => 'jumping',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'description',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello world',
      ],
      '/Invalid status/',
    ];

    $es['malformedField'] = [
      [
        'entity_table' => 'civicrm_event',
        'entity_field' => 'ti!tle',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello title',
      ],
      '/Entity reference is malformed/',
    ];

    $es['badTable'] = [
      [
        'entity_table' => 'typozcivicrm_event',
        'entity_field' => 'title',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello title',
      ],
      '/(non-existent or non-translatable table|Cannot resolve permissions for dynamic foreign key)/',
    ];

    $es['badFieldName'] = [
      [
        'status_id:name' => 'active',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'zoological_taxonomy',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello world',
      ],
      '/non-existent or non-translatable field/',
    ];

    $es['badFieldType'] = [
      [
        'status_id:name' => 'active',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'event_type_id',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => '9',
      ],
      '/non-existent or non-translatable field/',
    ];

    $es['badEntityId'] = [
      [
        'status_id:name' => 'active',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'description',
        'entity_id' => 9999999,
        'language' => 'fr_CA',
        'string' => 'Hello world',
      ],
      '/Entity does not exist/',
    ];

    return $es;
  }

  public function getUpdateBadExamples() {
    $createOk = $this->getCreateOKExamples()['asDraft'][0];
    $bads = $this->getCreateBadExamples();

    $es = [];
    foreach ($bads as $id => $bad) {
      array_unshift($bad, $createOk);
      $es[$id] = $bad;
    }
    return $es;
  }

  protected function setUp(): void {
    parent::setUp();
    $this->ids = [];
  }

  /**
   * @dataProvider getCreateOKExamples
   * @param array $record
   */
  public function testCreateOK($record) {
    $record = $this->fillRecord($record);
    $createResults = \civicrm_api4('Translation', 'create', [
      'checkPermissions' => FALSE,
      'values' => $record,
    ]);
    $this->assertEquals(1, $createResults->count());
    foreach ($createResults as $createResult) {
      $getResult = \civicrm_api4('Translation', 'get', [
        'where' => [['id', '=', $createResult['id']]],
      ]);
      $this->assertEquals($record['string'], $getResult->single()['string']);
    }
  }

  /**
   * @dataProvider getCreateBadExamples
   * @param array $record
   * @param string $errorRegex
   *   Regular expression to compare against the error message.
   */
  public function testCreateBad($record, $errorRegex) {
    $record = $this->fillRecord($record);
    try {
      \civicrm_api4('Translation', 'create', [
        'checkPermissions' => FALSE,
        'values' => $record,
      ]);
      $this->fail('Create should have failed');
    }
    catch (\API_Exception $e) {
      $this->assertRegExp($errorRegex, $e->getMessage());
    }
  }

  /**
   * @dataProvider getUpdateBadExamples
   * @param $createRecord
   * @param $badUpdate
   * @param $errorRegex
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function testUpdateBad($createRecord, $badUpdate, $errorRegex) {
    $record = $this->fillRecord($createRecord);
    $createResults = \civicrm_api4('Translation', 'create', [
      'checkPermissions' => FALSE,
      'values' => $record,
    ]);
    $this->assertEquals(1, $createResults->count());
    foreach ($createResults as $createResult) {
      $badUpdate = $this->fillRecord($badUpdate);
      try {
        \civicrm_api4('Translation', 'update', [
          'where' => [['id', '=', $createResult['id']]],
          'values' => $badUpdate,
        ]);
        $this->fail('Update should fail');
      }
      catch (\API_Exception $e) {
        $this->assertRegExp($errorRegex, $e->getMessage());
      }
    }
  }

  /**
   * Fill in mocked values for the would-be record..
   *
   * @param array $record
   *
   * @return array
   */
  protected function fillRecord($record) {
    if ($record['entity_id'] === '*EVENT*') {
      $eventId = $this->ids['*EVENT*'] ?? \CRM_Core_DAO::createTestObject('CRM_Event_BAO_Event')->id;
      $record['entity_id'] = $this->ids['*EVENT*'] = $eventId;
    }
    return $record;
  }

}
