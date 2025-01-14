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
    <af-field name="MyInfo.single_file_field"></af-field>
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
      'name' => 'single_file_field',
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
      'name' => 'my_file',
      'label' => 'My File',
      'data_type' => 'File',
      'html_type' => 'File',
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
        ->setFieldName('MyInfo.single_file_field')
        ->setEntityIndex($entityIndex)
        ->execute();

      foreach ([0, 1] as $joinIndex) {
        $this->mockUploadFile();
        Afform::submitFile()
          ->setName($this->formName)
          ->setToken($submission['token'])
          ->setModelName('Individual1')
          ->setFieldName('my_file')
          ->setEntityIndex($entityIndex)
          ->setJoinEntity('Custom_MyFiles')
          ->setJoinIndex($joinIndex)
          ->execute();
      }
    }

    $contacts = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addJoin('Custom_MyFiles AS MyFiles', 'LEFT', ['id', '=', 'MyFiles.entity_id'])
      ->addSelect('first_name', 'MyInfo.single_file_field', 'MyFiles.my_file')
      ->addOrderBy('id')
      ->addOrderBy('MyFiles.my_file')
      ->execute();
    $fileId = $contacts[0]['MyInfo.single_file_field'];
    $this->assertEquals(++$fileId, $contacts[0]['MyFiles.my_file']);
    $this->assertEquals(++$fileId, $contacts[1]['MyFiles.my_file']);
    $this->assertEquals(++$fileId, $contacts[2]['MyInfo.single_file_field']);
    $this->assertEquals(++$fileId, $contacts[2]['MyFiles.my_file']);
    $this->assertEquals(++$fileId, $contacts[3]['MyFiles.my_file']);
  }

  /**
   * Mock a file being uploaded
   */
  protected function mockUploadFile() {
    $tmpDir = sys_get_temp_dir();
    $this->assertTrue($tmpDir && is_dir($tmpDir), 'Tmp dir must exist: ' . $tmpDir);
    $fileName = uniqid() . '.txt';
    $filePath = $tmpDir . '/' . $fileName;
    file_put_contents($filePath, 'Hello');
    $_FILES['file'] = [
      'name' => $fileName,
      'tmp_name' => $filePath,
      'type' => 'text/plain',
    ];
  }

}
