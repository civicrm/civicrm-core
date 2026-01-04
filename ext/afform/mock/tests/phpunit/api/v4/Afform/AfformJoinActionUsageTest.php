<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\CustomValue;
use Civi\Api4\Email;

/**
 * Test case for Afform.submit.
 *
 * @group headless
 */
class AfformJoinActionUsageTest extends AfformUsageTestCase {

  public function setUp(): void {
    $this->createTestRecord('CustomGroup', [
      'name' => 'MyThings',
      'title' => 'My Things',
      'style' => 'Tab with table',
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);
    $this->saveTestRecords('CustomField', [
      'defaults' => ['custom_group_id.name' => 'MyThings'],
      'records' => [
        ['name' => 'my_text', 'label' => 'My Text', 'data_type' => 'String', 'html_type' => 'Text'],
      ],
    ]);
    parent::setUp();
  }

  /**
   * Generate layout for testing permutations of the 'actions' parameter
   */
  private function getLayout(array $actions = []): string {
    $emailActions = empty($actions['Email']) ? '' : 'actions="' . \CRM_Utils_JS::encode($actions['Email']) . '"';
    $myThingsActions = empty($actions['Custom_MyThings']) ? '' : 'actions="' . \CRM_Utils_JS::encode($actions['Custom_MyThings']) . '"';
    return <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC"/>
        <fieldset af-fieldset="Individual1">
          <af-field name="id"></af-field>
          <afblock-name-individual></afblock-name-individual>
          <div af-join="Email" min="1" af-repeat="Add" $emailActions >
            <afblock-contact-email></afblock-contact-email>
          </div>
          <div af-join="Custom_MyThings" af-repeat="Add" $myThingsActions>
            <afblock-custom-my-things></afblock-custom-my-things>
          </div>
        </fieldset>
        <button class="af-button btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;
  }

  /**
   * Checks that unchecked actions allows creation without deleting previous data
   */
  public function testJoinEntityActionsUnchecked(): void {
    $this->useValues([
      'layout' => $this->getLayout([
        'Custom_MyThings' => ['update' => FALSE, 'delete' => FALSE],
        'Email' => ['update' => TRUE, 'delete' => FALSE],
      ]),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $newLocationId = $this->createTestRecord('LocationType')['id'];

    // Create contact with email and custom fields
    $contact = $this->createTestRecord('Individual', [
      'first_name' => 'Bob',
      'last_name' => $lastName,
      'email_primary.email' => '123@example.com',
    ]);
    CustomValue::create('MyThings', FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('my_text', 'One')
      ->execute();

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'first_name')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();
    $this->assertCount(1, $result);
    $this->assertEquals('Bob', $result[0]['first_name']);
    $this->assertEquals('One', $result[0]['Custom_MyThings.my_text']);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'id' => $contact['id'],
            'first_name' => 'Bobby',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Email' => [
              ['email' => '1234@example.com', 'location_type_id' => $newLocationId, 'is_primary' => TRUE],
            ],
            'Custom_MyThings' => [
              ['my_text' => 'Two'],
              ['my_text' => 'Three'],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'first_name')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();

    $this->assertCount(3, $result);
    $this->assertEquals('Bobby', $result[0]['first_name']);
    $this->assertEquals('One', $result[0]['Custom_MyThings.my_text']);
    $this->assertEquals('Two', $result[1]['Custom_MyThings.my_text']);
    $this->assertEquals('Three', $result[2]['Custom_MyThings.my_text']);

    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $emails);
    $this->assertEquals('1234@example.com', $emails[0]['email']);
  }

  /**
   * Checks that checked actions will behave as is
   */
  public function testJoinEntityActionsChecked(): void {
    $this->useValues([
      'layout' => $this->getLayout([
        'Custom_MyThings' => ['update' => TRUE, 'delete' => TRUE],
        'Email' => ['update' => FALSE, 'delete' => FALSE],
      ]),
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $newLocationId = $this->createTestRecord('LocationType')['id'];

    // Create contact with email and custom fields
    $contact = $this->createTestRecord('Individual', [
      'first_name' => 'Bob',
      'last_name' => $lastName,
      'email_primary.email' => '123@example.com',
    ]);
    CustomValue::create('MyThings', FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('my_text', 'One')
      ->execute();
    CustomValue::create('MyThings', FALSE)
      ->addValue('entity_id', $contact['id'])
      ->addValue('my_text', 'Two')
      ->execute();

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'first_name')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();
    $this->assertCount(2, $result);
    $this->assertEquals('Bob', $result[0]['first_name']);
    $this->assertEquals('One', $result[0]['Custom_MyThings.my_text']);
    $this->assertEquals('Two', $result[1]['Custom_MyThings.my_text']);

    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $emails);
    $this->assertEquals('123@example.com', $emails[0]['email']);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'id' => $contact['id'],
            'first_name' => 'Bobby',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Email' => [
              ['email' => '1234@example.com', 'location_type_id' => $newLocationId, 'is_primary' => TRUE],
            ],
            'Custom_MyThings' => [
              ['my_text' => 'Three'],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'first_name')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('Bobby', $result[0]['first_name']);
    $this->assertEquals('Three', $result[0]['Custom_MyThings.my_text']);

    $emails = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(2, $emails);
    $this->assertEquals('123@example.com', $emails[0]['email']);
    $this->assertEquals('1234@example.com', $emails[1]['email']);
  }

}
