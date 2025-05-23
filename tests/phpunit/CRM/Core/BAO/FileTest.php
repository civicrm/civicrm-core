<?php

use Civi\Api4\Activity;

/**
 * Class CRM_Core_BAO_FileTest
 *
 * @group headless
 */
class CRM_Core_BAO_FileTest extends CiviUnitTestCase {
  protected $sampleFile = NULL;

  protected function setUp(): void {
    parent::setUp();

    $this->sampleFile = sys_get_temp_dir() . '/file.txt';
    file_put_contents($this->sampleFile, 'This comes from a file');
  }

  /**
   * Clean up after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_activity', 'civicrm_file', 'civicrm_entity_file'], TRUE);
    parent::tearDown();
  }

  /**
   * Test creating a file.
   */
  public function testCreateFile(): void {
    $file = $this->createFile();

    $this->assertDBNotNull('CRM_Core_DAO_File', $file->id, 'created_id', 'id',
      'Database check for created File.'
    );

    $fields = [
      'id' => $file->id,
      'mime_type' => 'image/jpg',
      'uri' => 'fake_file.jpg',
      'description' => 'Edited fake file',
    ];
    CRM_Core_BAO_File::create($fields);

    $this->assertDBNotNull('CRM_Core_DAO_File', $fields['mime_type'], 'id', 'mime_type', 'Database check for edited File.');
    $this->assertDBNotNull('CRM_Core_DAO_File', $fields['uri'], 'id', 'uri', 'Database check for edited File.');
  }

  /**
   * Test creating a file using the filePostProcess method.
   */
  public function testCreateFileUsingPostProcess(): void {
    $activity = $this->createActivity();

    $path = $this->sampleFile;
    $params = [
      'path' => $path,
      'file_type_id' => NULL,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
      'entity_subtype' => NULL,
      'overwrite' => TRUE,
      'file_params' => [
        'uri' => $path,
        'type' => 'text/plain',
        'location' => $path,
        'upload_date' => '20230602075923',
        'description' => '',
        'tag' => [],
        'attachment_taglist' => [],
      ],
      'upload_name' => 'uploadFile',
      'mime_type' => 'text/plain',
    ];
    CRM_Core_BAO_File::filePostProcess(...array_values($params));

    $this->assertDBNotNull('CRM_Core_DAO_File', 'file.txt', 'id', 'uri', 'Database check for created File.');
  }

  /**
   * Test creating a file using the filePostProcess method as the logged in user.
   */
  public function testCreateFileUsingPostProcessAsTheLoggedInUser(): void {
    $loggedInUser = $this->createLoggedInUser();
    $activity = $this->createActivity();

    $path = $this->sampleFile;
    $params = [
      'path' => $path,
      'file_type_id' => NULL,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
      'entity_subtype' => NULL,
      'overwrite' => TRUE,
      'file_params' => [
        'uri' => $path,
        'type' => 'text/plain',
        'location' => $path,
        'upload_date' => '20230602075923',
        'description' => '',
        'tag' => [],
        'attachment_taglist' => [],
      ],
      'upload_name' => 'uploadFile',
      'mime_type' => 'text/plain',
    ];
    CRM_Core_BAO_File::filePostProcess(...array_values($params));

    $this->assertDBNotNull('CRM_Core_DAO_File', $loggedInUser, 'id', 'created_id', 'Database check for created File.');
  }

  /**
   * Create a file
   *
   * @return array
   */
  protected function createFile() {
    $contactId = $this->individualCreate();

    $fields = [
      'file_type_id' => NULL,
      'mime_type' => 'image/png',
      'uri' => 'fake_file.png',
      'description' => 'Fake file',
      'upload_date' => '2023-05-10 15:00:00',
      'created_id' => $contactId,
    ];
    $field = CRM_Core_BAO_File::create($fields);
    return $field;
  }

  /**
   * Create an activity
   *
   * @return array
   */
  protected function createActivity() {
    $contactId = $this->individualCreate();
    $activity = Activity::create()
      ->addValue('source_contact_id', $contactId)
      ->addValue('subject', 'Scheduling Meeting')
      ->addValue('activity_type_id', 1)
      ->execute()
      ->single();

    return $activity;
  }

  /**
   * Data provider for testing `generateFileHash` and `validateFileHash`.
   *
   * @return array
   */
  public function fileHashProvider() {
    return [
      // Test case 1: Valid token with a specific fileId
      'valid_file_hash' => [
        'fileId' => 123,
        'genTs' => 'now',
        'life' => 1,
        'expectedResult' => TRUE,
      ],

      // Test case 2: Expired token
      'expired_file_hash' => [
        'fileId' => 123,
        // Token generated 2 hours ago
        'genTs' => '2 hours ago',
        'life' => 1,
        'expectedResult' => FALSE,
      ],

      // Test case 3: Invalid fileId
      'invalid_file_id' => [
        'fileId' => 123,
        'genTs' => 'now',
        'life' => 1,
        'expectedResult' => FALSE,
        'invalidFileId' => 999,
      ],
    ];
  }

  /**
   * Test `generateFileHash` and `validateFileHash` using a data provider.
   *
   * @dataProvider fileHashProvider
   */
  public function testFileHash($fileId, $genTs, $life, $expectedResult, $invalidFileId = NULL) {
    // We generate it now from its word description because dataproviders run
    // at the start of the suite, so could have expired already in a
    // long-running suite.
    $genTs = strtotime($genTs);

    // Generate token
    $token = CRM_Core_BAO_File::generateFileHash(NULL, $fileId, $genTs, $life);

    // Validate token (valid or invalid fileId depending on the scenario)
    $result = CRM_Core_BAO_File::validateFileHash(
      $token,
      NULL,
      $invalidFileId ?: $fileId
    );

    $this->assertEquals($expectedResult, $result);
  }

}
