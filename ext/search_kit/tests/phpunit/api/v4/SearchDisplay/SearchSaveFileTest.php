<?php
namespace api\v4\SearchDisplay;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class SearchSaveFileTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
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

    $params = [
      'checkPermissions' => FALSE,
      'format' => 'array',
      'savedSearch' => [
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => ['subject', 'details'],
          'where' => [],
        ],
      ],
      'display' => [
        'type' => 'table',
        'label' => '',
        'settings' => [
          'limit' => 2,
          'actions' => TRUE,
          'pager' => [],
          'columns' => [
            [
              'key' => 'subject',
              'label' => 'Duration Subject',
              'dataType' => 'String',
              'type' => 'field',
              'rewrite' => '[duration] [subject]',
            ],
            // This column ought to be removed by the download action
            [
              'type' => 'links',
              'links' => [],
            ],
            [
              'key' => 'details',
              'label' => 'Details',
              'dataType' => 'String',
              'type' => 'html',
            ],
          ],
          'sort' => [
            ['id', 'ASC'],
          ],
        ],
      ],
      'filters' => ['subject' => $subject],
      'afform' => NULL,
      'appendDate' => TRUE,
      'reportName' => 'Save File Test',
      'fileName' => 'Test_File',
    ];

    $data = (array) civicrm_api4('SearchDisplay', 'saveFile', $params);
    $file = json_decode($data);
    $config = \CRM_Core_Config::singleton();
    $directoryName = $config->customFileUploadDir;

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
    $this->assertStringStartsWith($directoryName . 'TestDirectory/Test_File_' . date("_Ymd", time()), $file->uri);

    // --------------------
    // Make sure our saved file exists in the file system.
    $this->assertFileExists($file->uri);
  }

}
