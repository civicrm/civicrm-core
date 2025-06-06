<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\SavedSearch;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SearchSaveFileTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Test saving an array formatted SavedSearch.
   */
  public function testSaveArrayFile() {
    $cid = Contact::create(FALSE)->execute()->single()['id'];
    $subject = uniqid(__FUNCTION__);
    $sampleData = [
      ['duration' => 1, 'subject' => $subject, 'details' => '<p>Markup</p>'],
      ['duration' => 3, 'subject' => $subject, 'details' => 'Plain &amp; simple'],
      ['duration' => 3, 'subject' => $subject],
      ['duration' => 4, 'subject' => $subject],
    ];
    Activity::save(FALSE)
      ->setRecords($sampleData)
      ->setDefaults(['activity_type_id:name' => 'Meeting', 'source_contact_id' => $cid])
      ->execute();

    SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'TestContactActivity',
        'label' => 'TestContactActivity',
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['subject', 'details'],
          'where' => [],
        ],
      ])
      ->execute();

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'array',
      'savedSearch' => 'TestContactActivity',
      'filters' => ['subject' => $subject],
      'afform' => NULL,
      'appendDate' => TRUE,
      'reportName' => 'Save File Test',
      'fileName' => 'Test_File',
      'folderName' => 'TestDirectory',
    ];

    $data = civicrm_api4('SearchDisplay', 'saveFile', $params);
    $file = $data['file'];
    $config = \CRM_Core_Config::singleton();
    $directoryName = $config->customFileUploadDir;

    // Assert file creation.
    // ---------------------------------
    // Make sure report name is correct.
    $this->assertEquals('Save File Test', $file->description);
    $this->assertEquals('application/json', $file->mime_type);

    // The following directory and name checks may not be needed if the
    // overall file exists check passes, it would mean these aren't needed.
    // --------------------
    // Make sure we have base CiviCRM Upload Directory.
    $this->assertDirectoryExists($directoryName);
    // Make sure our custom directory from the API call is created.
    $this->assertDirectoryExists($directoryName . 'TestDirectory/');
    // Check to see if our test file name is found in the returned URL.
    $this->assertStringStartsWith($directoryName . 'TestDirectory/Test_File', $file->uri);
    // Check to see if our test file name, with current date,
    // is found in the returned URL.
    $this->assertStringStartsWith($directoryName . 'TestDirectory/Test_File' . date("_Ymd", time()), $file->uri);

    // --------------------
    // Make sure our saved file exists in the file system.
    $this->assertFileExists($file->uri);

    // Assert entity file creation.
    $entityFiles = civicrm_api4('EntityFile', 'get', [
      'where' => [
        ['entity_table', '=', 'civicrm_saved_search'],
        ['file_id', '=', $file->id],
      ],
    ]);

    // Make sure we have a result record.
    $this->assertCount(1, $entityFiles);

    // Make sure we are getting back the correct entity_table.
    $this->assertEquals('civicrm_saved_search', $entityFiles->first()['entity_table']);

    // Make sure the file_id matches.
    $this->assertEquals($file->id, $entityFiles->first()['file_id']);
  }

}
