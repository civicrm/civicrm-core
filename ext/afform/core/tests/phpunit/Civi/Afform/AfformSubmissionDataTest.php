<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Api4\AfformSubmissionData;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class AfformSubmissionDataTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {
  use Api4TestTrait;

  protected $formName;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
    $this->formName = 'mock_data_form_' . rand(0, 100000);
  }

  public function tearDown(): void {
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    parent::tearDown();
  }

  protected function createTestForm() {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{source: 'Hello'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1" af-repeat="Add">
    <af-field name="first_name" />
    <af-field name="last_name" />
    <div af-join="Email" data="{location_type_id: 1}" af-repeat="Add">
      <af-field name="email" />
    </div>
  </fieldset>
  <af-field defn="{name: 'extra_field_1', input_type: 'Text'}" />
  <button class="af-button btn btn-primary" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    Afform::create(FALSE)
      ->setLayoutFormat('html')
      ->setValues([
        'title' => 'Test Data Form',
        'name' => $this->formName,
        'layout' => $layout,
        'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
        'create_submission' => TRUE,
      ])
      ->execute();
  }

  public function testGetFields(): void {
    $this->createTestForm();

    $fields = AfformSubmissionData::getFields(FALSE)
      ->addValue('afform_name', $this->formName)
      ->setLoadOptions(TRUE)
      ->execute()
      ->indexBy('name');

    // Base fields
    $this->assertArrayHasKey('id', $fields);
    $this->assertArrayHasKey('contact_id', $fields);
    $this->assertArrayHasKey('submission_date', $fields);
    $this->assertArrayHasKey('status_id', $fields);

    // Field options
    $this->assertArrayHasKey($this->formName, $fields['afform_name']['options']);

    // Dynamic fields with index 0
    $this->assertArrayHasKey('Individual1.0.first_name', $fields);
    $this->assertArrayHasKey('Individual1.0.last_name', $fields);
    $this->assertArrayHasKey('Individual1.0.id', $fields);
    $this->assertArrayHasKey('Individual1.0.Email.0.email', $fields);
    $this->assertArrayHasKey('Individual1.0.Email.0.id', $fields);

    // Extra fields (unindexed)
    $this->assertArrayHasKey('extra.extra_field_1', $fields);
  }

  public function testGetSubmissionData(): void {
    $this->createLoggedInUser([
      'first_name' => 'Current',
      'last_name' => 'User',
    ]);
    $this->createTestForm();

    // 1. Submit some data
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
          ],
          'joins' => [
            'Email' => [
              [
                'email' => 'john.doe1@example.com',
              ],
              [
                'email' => 'john.doe2@example.com',
              ],
            ],
          ],
        ],
        [
          'fields' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
          ],
          'joins' => [
            'Email' => [
              [
                'email' => 'jane.smith@example.com',
              ],
            ],
          ],
        ],
        [
          'fields' => [
            'first_name' => 'Jack',
            'last_name' => 'Jones',
          ],
          'joins' => [
            'Email' => [
              [
                'email' => 'jack.jones@example.com',
              ],
            ],
          ],
        ],
      ],
      'extra' => [
        'fields' => [
          'extra_field_1' => 'Hello World',
        ],
      ],
    ];

    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $afformModuleName = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addSelect('module_name')
      ->execute()->first()['module_name'];

    // 2. Fetch submission data
    $records = AfformSubmissionData::get(FALSE)
      ->addWhere('afform_name:name', '=', $afformModuleName)
      ->execute();

    $this->assertCount(1, $records);
    $record = $records[0];

    // By default, dynamic fields at index 0 should be populated
    $this->assertEquals('John', $record['Individual1.0.first_name']);
    $this->assertEquals('Doe', $record['Individual1.0.last_name']);
    $this->assertEquals('john.doe1@example.com', $record['Individual1.0.Email.0.email']);
    $this->assertEquals('Hello World', $record['extra.extra_field_1']);
    $this->assertIsNumeric($record['Individual1.0.id']);
    $this->assertIsNumeric($record['Individual1.0.Email.0.id']);

    // Indices other than 0 should not be returned by default
    $this->assertArrayNotHasKey('Individual1.1.first_name', $record);
    $this->assertArrayNotHasKey('Individual1.2.first_name', $record);
    $this->assertArrayNotHasKey('Individual1.0.Email.1.email', $record);

    // 3. Query with explicit SELECT for other indices (0, 1 & 2)
    $recordsWithIndices = AfformSubmissionData::get(FALSE)
      ->addWhere('afform_name', '=', $this->formName)
      ->addSelect(
        'Individual1.0.first_name',
        'Individual1.1.first_name',
        'Individual1.0.Email.1.email',
        'Individual1.1.Email.0.email',
        'Individual1.2.first_name',
        'Individual1.2.Email.0.email',
        'contact_id.sort_name',
        'status_id:label',
      )
      ->execute();

    $this->assertCount(1, $recordsWithIndices);
    $recordWithIndices = $recordsWithIndices[0];

    $this->assertSame('User, Current', $recordWithIndices['contact_id.sort_name']);
    $this->assertSame('john.doe2@example.com', $recordWithIndices['Individual1.0.Email.1.email']);
    $this->assertSame('John', $recordWithIndices['Individual1.0.first_name']);
    $this->assertSame('Jane', $recordWithIndices['Individual1.1.first_name']);
    $this->assertSame('jane.smith@example.com', $recordWithIndices['Individual1.1.Email.0.email']);
    $this->assertSame('Jack', $recordWithIndices['Individual1.2.first_name']);
    $this->assertSame('jack.jones@example.com', $recordWithIndices['Individual1.2.Email.0.email']);
    $this->assertSame('Processed', $recordWithIndices['status_id:label']);
  }

}
