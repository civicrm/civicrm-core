<?php
namespace api\v4\Afform;

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */

use Civi\Api4\Afform;
use Civi\Api4\Contact;

class AfformFileUploadTest extends AfformUsageTestCase {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    self::$layouts['customFiles'] = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
  <fieldset af-fieldset="Individual1" af-repeat="Add" max="2">
    <legend class="af-text">Individual 1</legend>
    <afblock-name-individual></afblock-name-individual>
    <af-field name="MyInfo.private_file"></af-field>
    <div af-join="Custom_MyFiles" af-repeat="Add" max="3">
      <afblock-custom-my-files></afblock-custom-my-files>
    </div>
  </fieldset>
  <button class="af-button btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;
  }

  public function tearDown(): void {
    parent::tearDown();
    $_FILES = [];
  }

  /**
   * Test the submitFile api action
   */
  public function testSubmitFile(): void {
    // Single-value set
    $this->createTestRecord('CustomGroup', [
      'name' => 'MyInfo',
      'title' => 'My Info',
      'extends' => 'Contact',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'MyInfo',
      'name' => 'private_file',
      'label' => 'A File',
      'data_type' => 'File',
      'html_type' => 'File',
    ]);

    // Multi-record set
    $this->createTestRecord('CustomGroup', [
      'name' => 'MyFiles',
      'title' => 'My Files',
      'style' => 'Tab with table',
      'extends' => 'Contact',
      'is_multiple' => TRUE,
      'max_multiple' => 3,
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'MyFiles',
      'name' => 'public_files',
      'label' => 'My File',
      'data_type' => 'File',
      'html_type' => 'File',
      'file_is_public' => TRUE,
    ]);

    $this->useValues([
      'layout' => self::$layouts['customFiles'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'First',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Custom_MyFiles' => [
              [],
              [],
            ],
          ],
        ],
        [
          'fields' => [
            'first_name' => 'Second',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Custom_MyFiles' => [
              [],
              [],
            ],
          ],
        ],
      ],
    ];
    $submission = Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute()->first();

    foreach ([0, 1] as $entityIndex) {
      $this->mockUploadFile();
      Afform::submitFile()
        ->setName($this->formName)
        ->setToken($submission['token'])
        ->setModelName('Individual1')
        ->setFieldName('MyInfo.private_file')
        ->setEntityIndex($entityIndex)
        ->execute();

      foreach ([0, 1] as $joinIndex) {
        $this->mockUploadFile();
        Afform::submitFile()
          ->setName($this->formName)
          ->setToken($submission['token'])
          ->setModelName('Individual1')
          ->setFieldName('public_files')
          ->setEntityIndex($entityIndex)
          ->setJoinEntity('Custom_MyFiles')
          ->setJoinIndex($joinIndex)
          ->execute();
      }
    }

    $contacts = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addJoin('Custom_MyFiles AS MyFiles', 'LEFT', ['id', '=', 'MyFiles.entity_id'])
      ->addSelect('first_name', 'MyInfo.private_file', 'MyFiles.public_files')
      ->addOrderBy('id')
      ->addOrderBy('MyFiles.public_files')
      ->execute();
    $fileId = $contacts[0]['MyInfo.private_file'];
    $this->assertEquals(++$fileId, $contacts[0]['MyFiles.public_files']);
    $this->assertEquals(++$fileId, $contacts[1]['MyFiles.public_files']);
    $this->assertEquals(++$fileId, $contacts[2]['MyInfo.private_file']);
    $this->assertEquals(++$fileId, $contacts[2]['MyFiles.public_files']);
    $this->assertEquals(++$fileId, $contacts[3]['MyFiles.public_files']);

    // Check that files are properly public or private
    foreach ([0, 1] as $contactIndex) {
      $privateFile = $this->getTestRecord('File', $contacts[$contactIndex]['MyInfo.private_file'], ['*', 'url']);
      $this->assertEquals(FALSE, $privateFile['is_public']);
      $this->assertStringNotContainsString($privateFile['uri'], $privateFile['url']);

      $publicFile = $this->getTestRecord('File', $contacts[$contactIndex]['MyFiles.public_files'], ['*', 'url']);
      $this->assertEquals(TRUE, $publicFile['is_public']);
      $this->assertStringContainsString($publicFile['uri'], $publicFile['url']);
    }
  }

  /**
   * Mock a file being uploaded
   */
  protected function mockUploadFile() {
    $tmpDir = sys_get_temp_dir();
    $this->assertTrue($tmpDir && is_dir($tmpDir), 'Tmp dir must exist: ' . $tmpDir);
    $fileName = uniqid() . '.txt';
    $filePath = $tmpDir . '/' . $fileName;
    \Civi::fs()->dumpFile($filePath, 'Hello');
    $_FILES['file'] = [
      'name' => $fileName,
      'tmp_name' => $filePath,
      'type' => 'text/plain',
    ];
  }

}
