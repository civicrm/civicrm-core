<?php
/**
 *  api_v3_AttachmentTest
 *
 * @copyright Copyright CiviCRM LLC (C) 2014
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: ContactTest.php 31254 2010-12-15 10:09:29Z eileen $
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Test for the Attachment API
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_AttachmentTest extends CiviUnitTestCase {
  protected static $filePrefix = NULL;

  /**
   * @return string
   */
  public static function getFilePrefix() {
    if (!self::$filePrefix) {
      self::$filePrefix = "test_" . CRM_Utils_String::createRandom(5, CRM_Utils_String::ALPHANUMERIC) . '_';
    }
    return self::$filePrefix;
  }

  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->cleanupFiles();
    file_put_contents($this->tmpFile('mytest.txt'), 'This comes from a file');
  }

  protected function tearDown() {
    parent::tearDown();
    $this->cleanupFiles();
    \Civi::reset();
  }

  /**
   * @return array
   */
  public function okCreateProvider() {
    // array($entityClass, $createParams, $expectedContent)
    $cases = [];

    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => self::getFilePrefix() . 'exampleFromContent.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => 'My test content',
      ],
      'My test content',
    ];

    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => self::getFilePrefix() . 'exampleWithEmptyContent.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => '',
      ],
      '',
    ];

    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => self::getFilePrefix() . 'exampleFromMove.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'options' => [
          'move-file' => $this->tmpFile('mytest.txt'),
        ],
      ],
      'This comes from a file',
    ];
    $cases[] = [
      'CRM_Core_DAO_Domain',
      [
        'name' => self::getFilePrefix() . 'exampleFromContent.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => 'My test content',
      ],
      'My test content',
    ];

    return $cases;
  }

  /**
   * @return array
   */
  public function badCreateProvider() {
    // array($entityClass, $createParams, $expectedError)
    $cases = [];

    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'id' => 12345,
        'name' => self::getFilePrefix() . 'exampleFromContent.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => 'My test content',
      ],
      '/Invalid ID/',
    ];
    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => self::getFilePrefix() . 'failedExample.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
      ],
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'content' or 'options.move-file'/",
    ];
    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => self::getFilePrefix() . 'failedExample.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => 'too much content',
        'options' => [
          'move-file' => $this->tmpFile('too-much.txt'),
        ],
      ],
      "/'content' and 'options.move-file' are mutually exclusive/",
    ];
    $cases[] = [
      'CRM_Activity_DAO_Activity',
      [
        'name' => 'inv/alid.txt',
        'mime_type' => 'text/plain',
        'description' => 'My test description',
        'content' => 'My test content',
      ],
      "/Malformed name/",
    ];

    return $cases;
  }

  /**
   * @return array
   */
  public function badUpdateProvider() {
    // array($entityClass, $createParams, $updateParams, $expectedError)
    $cases = [];

    $readOnlyFields = [
      'name' => 'newname.txt',
      'entity_table' => 'civicrm_domain',
      'entity_id' => 5,
      'upload_date' => '2010-11-12 13:14:15',
    ];
    foreach ($readOnlyFields as $readOnlyField => $newValue) {
      $cases[] = [
        'CRM_Activity_DAO_Activity',
        [
          'name' => self::getFilePrefix() . 'exampleFromContent.txt',
          'mime_type' => 'text/plain',
          'description' => 'My test description',
          'content' => 'My test content',
        ],
        [
          'check_permissions' => 1,
          $readOnlyField => $newValue,
        ],
        "/Cannot modify $readOnlyField/",
      ];
    }

    return $cases;
  }

  /**
   * @return array
   */
  public function okGetProvider() {
    // array($getParams, $expectedNames)
    $cases = [];

    // Each search runs in a DB which contains these attachments:
    // Activity #123: example_123.txt (text/plain) and example_123.csv (text/csv)
    // Activity #456: example_456.txt (text/plain) and example_456.csv (text/csv)

    // NOTE: Searching across multiple records (w/o entity_id) is currently
    // prohibited by DynamicFKAuthorization. The technique used to authorize requests
    // does not adapt well to such searches.

    //$cases[] = array(
    //  array('entity_table' => 'civicrm_activity'),
    //  array(
    //    self::getFilePrefix() . 'example_123.csv',
    //    self::getFilePrefix() . 'example_123.txt',
    //    self::getFilePrefix() . 'example_456.csv',
    //    self::getFilePrefix() . 'example_456.txt',
    //  ),
    //);
    //$cases[] = array(
    //  array('entity_table' => 'civicrm_activity', 'mime_type' => 'text/plain'),
    //  array(self::getFilePrefix() . 'example_123.txt', self::getFilePrefix() . 'example_456.txt'),
    //);

    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '123'],
      [self::getFilePrefix() . 'example_123.txt', self::getFilePrefix() . 'example_123.csv'],
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '456'],
      [self::getFilePrefix() . 'example_456.txt', self::getFilePrefix() . 'example_456.csv'],
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '456', 'mime_type' => 'text/csv'],
      [self::getFilePrefix() . 'example_456.csv'],
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '456', 'mime_type' => 'text/html'],
      [],
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '999'],
      [],
    ];

    return $cases;
  }

  /**
   * @return array
   */
  public function badGetProvider() {
    // array($getParams, $expectedNames)
    $cases = [];

    // Each search runs in a DB which contains these attachments:
    // Activity #123: example_123.txt (text/plain) and example_123.csv (text/csv)
    // Activity #456: example_456.txt (text/plain) and example_456.csv (text/csv)

    $cases[] = [
      ['check_permissions' => 1, 'mime_type' => 'text/plain'],
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'entity_table'/",
    ];
    $cases[] = [
      ['check_permissions' => 1, 'entity_id' => '123'],
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'entity_table'/",
    ];
    $cases[] = [
      ['check_permissions' => 1],
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'entity_table'/",
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '123', 'name' => 'example_456.csv'],
      "/Get by name is not currently supported/",
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '123', 'content' => 'test'],
      "/Get by content is not currently supported/",
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '123', 'path' => '/home/foo'],
      "/Get by path is not currently supported/",
    ];
    $cases[] = [
      ['entity_table' => 'civicrm_activity', 'entity_id' => '123', 'url' => '/index.php'],
      "/Get by url is not currently supported/",
    ];

    return $cases;
  }

  /**
   * Create an attachment using "content" and then "get" the attachment.
   *
   * @param string $testEntityClass
   *   E.g. "CRM_Core_DAO_Activity".
   * @param array $createParams
   * @param string $expectedContent
   * @dataProvider okCreateProvider
   */
  public function testCreate($testEntityClass, $createParams, $expectedContent) {
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', $createParams + [
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertTrue(is_numeric($fileId));
    $this->assertEquals($entity_table, $createResult['values'][$fileId]['entity_table']);
    $this->assertEquals($entity->id, $createResult['values'][$fileId]['entity_id']);
    $this->assertEquals('My test description', $createResult['values'][$fileId]['description']);
    $this->assertRegExp('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $createResult['values'][$fileId]['upload_date']);
    $this->assertTrue(!isset($createResult['values'][$fileId]['content']));
    $this->assertTrue(!empty($createResult['values'][$fileId]['url']));
    $this->assertAttachmentExistence(TRUE, $createResult);

    $getResult = $this->callAPISuccess('Attachment', 'get', [
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);
    $this->assertEquals(1, $getResult['count']);
    foreach (['id', 'entity_table', 'entity_id', 'url'] as $field) {
      if ($field == 'url') {
        $this->assertEquals(substr($createResult['values'][$fileId][$field], 0, -15), substr($getResult['values'][$fileId][$field], 0, -15));
        $this->assertEquals(substr($createResult['values'][$fileId][$field], -3), substr($getResult['values'][$fileId][$field], -3));
        $this->assertApproxEquals(substr($createResult['values'][$fileId][$field], -14, 10), substr($getResult['values'][$fileId][$field], -14, 10), 2);
      }
      else {
        $this->assertEquals($createResult['values'][$fileId][$field], $getResult['values'][$fileId][$field], "Expect field $field to match");
      }
    }
    $this->assertTrue(!isset($getResult['values'][$fileId]['content']));

    $getResult2 = $this->callAPISuccess('Attachment', 'get', [
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
      'return' => ['content'],
    ]);
    $this->assertEquals($expectedContent, $getResult2['values'][$fileId]['content']);
    // Do this again even though we just tested above to demonstrate that these fields should be returned even if you only ask to return 'content'.
    foreach (['id', 'entity_table', 'entity_id', 'url'] as $field) {
      if ($field == 'url') {
        $this->assertEquals(substr($createResult['values'][$fileId][$field], 0, -15), substr($getResult2['values'][$fileId][$field], 0, -15));
        $this->assertEquals(substr($createResult['values'][$fileId][$field], -3), substr($getResult2['values'][$fileId][$field], -3));
        $this->assertApproxEquals(substr($createResult['values'][$fileId][$field], -14, 10), substr($getResult2['values'][$fileId][$field], -14, 10), 2);
      }
      else {
        $this->assertEquals($createResult['values'][$fileId][$field], $getResult2['values'][$fileId][$field], "Expect field $field to match");
      }
    }
  }

  /**
   * @param $testEntityClass
   * @param $createParams
   * @param $expectedError
   * @dataProvider badCreateProvider
   */
  public function testCreateFailure($testEntityClass, $createParams, $expectedError) {
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPIFailure('Attachment', 'create', $createParams + [
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);
    $this->assertRegExp($expectedError, $createResult['error_message']);
  }

  /**
   * @param $testEntityClass
   * @param $createParams
   * @param $updateParams
   * @param $expectedError
   * @dataProvider badUpdateProvider
   */
  public function testCreateWithBadUpdate($testEntityClass, $createParams, $updateParams, $expectedError) {
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', $createParams + [
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertTrue(is_numeric($fileId));

    $updateResult = $this->callAPIFailure('Attachment', 'create', $updateParams + [
      'id' => $fileId,
    ]);
    $this->assertRegExp($expectedError, $updateResult['error_message']);
  }

  /**
   * If one submits a weird file name, it should be automatically converted
   * to something safe.
   */
  public function testCreateWithWeirdName() {
    $entity = CRM_Core_DAO::createTestObject('CRM_Activity_DAO_Activity');
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'weird:na"me.txt',
      'mime_type' => 'text/plain',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => 'civicrm_activity',
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertTrue(is_numeric($fileId));
    $this->assertEquals(self::getFilePrefix() . 'weird_na_me.txt', $createResult['values'][$fileId]['name']);
    // Check for appropriate icon
    $this->assertEquals('fa-file-text-o', $createResult['values'][$fileId]['icon']);
  }

  public function testCreateShouldSetCreatedIdAsTheLoggedInUser() {
    $loggedInUser = $this->createLoggedInUser();

    $testEntityClass = 'CRM_Activity_DAO_Activity';
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'exampleFromContent.txt',
      'mime_type' => 'text/plain',
      'content' => 'My test content',
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);

    $fileId = $createResult['id'];
    $this->assertEquals($loggedInUser, $createResult['values'][$fileId]['created_id']);
  }

  public function testCreateShouldKeepCreatedIdEmptyIfTheresNoLoggedInUser() {
    $testEntityClass = 'CRM_Activity_DAO_Activity';
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'exampleFromContent.txt',
      'mime_type' => 'text/plain',
      'content' => 'My test content',
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ]);

    $fileId = $createResult['id'];
    $this->assertEmpty($createResult['values'][$fileId]['created_id']);
  }

  public function testCreateShouldNotUpdateTheCreatedId() {
    $testEntityClass = 'CRM_Activity_DAO_Activity';
    $entity = CRM_Core_DAO::createTestObject($testEntityClass);
    $entity_table = CRM_Core_DAO_AllCoreTables::getTableForClass($testEntityClass);
    $this->assertTrue(is_numeric($entity->id));

    $attachmentParams = [
      'name' => self::getFilePrefix() . 'exampleFromContent.txt',
      'mime_type' => 'text/plain',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => $entity_table,
      'entity_id' => $entity->id,
    ];

    $createResult = $this->callAPISuccess('Attachment', 'create', $attachmentParams);

    $fileId = $createResult['id'];
    $this->assertEmpty($createResult['values'][$fileId]['created_id']);

    $attachmentParams['id'] = $fileId;
    $attachmentParams['description'] = 'My updated description';

    $loggedInUser = $this->createLoggedInUser();

    $this->callAPISuccess('Attachment', 'create', $attachmentParams);

    $updatedAttachment = $this->callAPISuccess('Attachment', 'get', [
      'id' => $fileId,
      'entity_id' => $attachmentParams['entity_id'],
      'entity_table' => $attachmentParams['entity_table'],
    ]);

    $this->assertNotEmpty($loggedInUser);
    $this->assertEmpty($updatedAttachment['values'][$fileId]['created_id']);
    $this->assertEquals($attachmentParams['description'], $updatedAttachment['values'][$fileId]['description']);
  }

  /**
   * @param $getParams
   * @param $expectedNames
   * @dataProvider okGetProvider
   */
  public function testGet($getParams, $expectedNames) {
    foreach ([123, 456] as $entity_id) {
      foreach (['text/plain' => '.txt', 'text/csv' => '.csv'] as $mime => $ext) {
        $this->callAPISuccess('Attachment', 'create', [
          'name' => self::getFilePrefix() . 'example_' . $entity_id . $ext,
          'mime_type' => $mime,
          'description' => 'My test description',
          'content' => 'My test content',
          'entity_table' => 'civicrm_activity',
          'entity_id' => $entity_id,
        ]);
      }
    }

    $getResult = $this->callAPISuccess('Attachment', 'get', $getParams);
    $actualNames = array_values(CRM_Utils_Array::collect('name', $getResult['values']));
    // Verify the hash generated by the API is valid if we were to try and load the file.
    foreach ($getResult['values'] as $result) {
      $queryResult = [];
      $parsedURl = parse_url($result['url']);
      $parsedQuery = parse_str($parsedURl['query'], $queryResult);
      $this->assertTrue(CRM_Core_BAO_File::validateFileHash($queryResult['fcs'], $queryResult['eid'], $queryResult['id']));
    }

    sort($actualNames);
    sort($expectedNames);
    $this->assertEquals($expectedNames, $actualNames);
  }

  /**
   * @param $getParams
   * @param $expectedError
   * @dataProvider badGetProvider
   */
  public function testGetError($getParams, $expectedError) {
    foreach ([123, 456] as $entity_id) {
      foreach (['text/plain' => '.txt', 'text/csv' => '.csv'] as $mime => $ext) {
        $this->callAPISuccess('Attachment', 'create', [
          'name' => self::getFilePrefix() . 'example_' . $entity_id . $ext,
          'mime_type' => $mime,
          'description' => 'My test description',
          'content' => 'My test content',
          'entity_table' => 'civicrm_activity',
          'entity_id' => $entity_id,
        ]);
      }
    }

    $getResult = $this->callAPIFailure('Attachment', 'get', $getParams);
    $this->assertRegExp($expectedError, $getResult['error_message']);
  }

  /**
   * Take the values from a "get", make a small change, and then send
   * the full thing back in as an update ("create"). This ensures some
   * consistency in the acceptable formats.
   */
  public function testGetThenUpdate() {
    $entity = CRM_Core_DAO::createTestObject('CRM_Activity_DAO_Activity');
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'getThenUpdate.txt',
      'mime_type' => 'text/plain',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => 'civicrm_activity',
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertTrue(is_numeric($fileId));
    $this->assertEquals(self::getFilePrefix() . 'getThenUpdate.txt', $createResult['values'][$fileId]['name']);
    $this->assertAttachmentExistence(TRUE, $createResult);

    $getResult = $this->callAPISuccess('Attachment', 'get', [
      'id' => $fileId,
    ]);
    $this->assertTrue(is_array($getResult['values'][$fileId]));

    $updateParams = $getResult['values'][$fileId];
    $updateParams['description'] = 'new description';
    $this->callAPISuccess('Attachment', 'create', $updateParams);
    $this->assertAttachmentExistence(TRUE, $createResult);
  }

  /**
   * Create an attachment and delete using its ID. Assert that the records are correctly created and destroyed
   * in the DB and the filesystem.
   */
  public function testDeleteByID() {
    $entity = CRM_Core_DAO::createTestObject('CRM_Activity_DAO_Activity');
    $this->assertTrue(is_numeric($entity->id));

    foreach (['first', 'second'] as $n) {
      $createResults[$n] = $this->callAPISuccess('Attachment', 'create', [
        'name' => self::getFilePrefix() . 'testDeleteByID.txt',
        'mime_type' => 'text/plain',
        'content' => 'My test content',
        'entity_table' => 'civicrm_activity',
        'entity_id' => $entity->id,
      ]);
      $this->assertTrue(is_numeric($createResults[$n]['id']));
      $this->assertEquals(self::getFilePrefix() . 'testDeleteByID.txt', $createResults[$n]['values'][$createResults[$n]['id']]['name']);
    }
    $this->assertAttachmentExistence(TRUE, $createResults['first']);
    $this->assertAttachmentExistence(TRUE, $createResults['second']);

    $this->callAPISuccess('Attachment', 'delete', [
      'id' => $createResults['first']['id'],
    ]);
    $this->assertAttachmentExistence(FALSE, $createResults['first']);
    $this->assertAttachmentExistence(TRUE, $createResults['second']);
  }

  /**
   * Create an attachment and delete using its ID. Assert that the records are correctly created and destroyed
   * in the DB and the filesystem.
   */
  public function testDeleteByEntity() {
    // create 2 entities (keepme,delme) -- each with 2 attachments (first,second)
    foreach (['keepme', 'delme'] as $e) {
      $entities[$e] = CRM_Core_DAO::createTestObject('CRM_Activity_DAO_Activity');
      $this->assertTrue(is_numeric($entities[$e]->id));
      foreach (['first', 'second'] as $n) {
        $createResults[$e][$n] = $this->callAPISuccess('Attachment', 'create', [
          'name' => self::getFilePrefix() . 'testDeleteByEntity.txt',
          'mime_type' => 'text/plain',
          'content' => 'My test content',
          'entity_table' => 'civicrm_activity',
          'entity_id' => $entities[$e]->id,
        ]);
        $this->assertTrue(is_numeric($createResults[$e][$n]['id']));
      }
    }
    $this->assertAttachmentExistence(TRUE, $createResults['keepme']['first']);
    $this->assertAttachmentExistence(TRUE, $createResults['keepme']['second']);
    $this->assertAttachmentExistence(TRUE, $createResults['delme']['first']);
    $this->assertAttachmentExistence(TRUE, $createResults['delme']['second']);

    $this->callAPISuccess('Attachment', 'delete', [
      'entity_table' => 'civicrm_activity',
      'entity_id' => $entities[$e]->id,
    ]);
    $this->assertAttachmentExistence(TRUE, $createResults['keepme']['first']);
    $this->assertAttachmentExistence(TRUE, $createResults['keepme']['second']);
    $this->assertAttachmentExistence(FALSE, $createResults['delme']['first']);
    $this->assertAttachmentExistence(FALSE, $createResults['delme']['second']);
  }

  /**
   * Ensure mime type is converted to appropriate icon.
   */
  public function testGetIcon() {
    $entity = CRM_Core_DAO::createTestObject('CRM_Activity_DAO_Activity');
    $this->assertTrue(is_numeric($entity->id));

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'hasIcon.docx',
      'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => 'civicrm_activity',
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertEquals('fa-file-word-o', $createResult['values'][$fileId]['icon']);

    $createResult = $this->callAPISuccess('Attachment', 'create', [
      'name' => self::getFilePrefix() . 'hasIcon.jpg',
      'mime_type' => 'image/jpg',
      'description' => 'My test description',
      'content' => 'My test content',
      'entity_table' => 'civicrm_activity',
      'entity_id' => $entity->id,
    ]);
    $fileId = $createResult['id'];
    $this->assertEquals('fa-file-image-o', $createResult['values'][$fileId]['icon']);
  }

  /**
   * @param $name
   * @return string
   */
  protected function tmpFile($name) {
    $tmpDir = sys_get_temp_dir();
    $this->assertTrue($tmpDir && is_dir($tmpDir), 'Tmp dir must exist: ' . $tmpDir);
    return $tmpDir . '/' . self::getFilePrefix() . $name;
  }

  protected function cleanupFiles() {
    $config = CRM_Core_Config::singleton();
    $dirs = [
      sys_get_temp_dir(),
      $config->customFileUploadDir,
    ];
    foreach ($dirs as $dir) {
      $files = (array) glob($dir . "/" . self::getFilePrefix() . "*");
      foreach ($files as $file) {
        unlink($file);
      }

    }
  }

}
