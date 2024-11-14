<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * Test case for Afform.submit.
 *
 * @group headless
 */
class AfformJoinActionUsageTest extends AfformUsageTestCase {

  public function tearDown(): void {
    parent::tearDown();
    CustomField::delete(FALSE)->addWhere('id', '>', '0')->execute();
    CustomGroup::delete(FALSE)->addWhere('id', '>', '0')->execute();
  }

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    self::$layouts['joinUncheckedActions'] = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" contact-dedupe="Individual.Supervised"/>
        <fieldset af-fieldset="Individual1">
          <legend class="af-text">Individual 1</legend>
          <afblock-name-individual></afblock-name-individual>
          <div af-join="Email" min="1" af-repeat="Add" actions="{update: true, delete: true}" >
            <afblock-contact-email></afblock-contact-email>
          </div>
          <div af-join="Custom_MyThings" af-repeat="Add" actions="{update: false, delete: false}">
            <afblock-custom-my-things></afblock-custom-my-things>
          </div>
        </fieldset>
        <button class="af-button btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
      </af-form>
    EOHTML;

    self::$layouts['joinCheckedActions'] = <<<EOHTML
      <af-form ctrl="afform">
        <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" contact-dedupe="Individual.Supervised"/>
        <fieldset af-fieldset="Individual1">
          <legend class="af-text">Individual 1</legend>
          <afblock-name-individual></afblock-name-individual>
          <div af-join="Email" min="1" af-repeat="Add" actions="{update: true, delete: true}" >
            <afblock-contact-email></afblock-contact-email>
          </div>
          <div af-join="Custom_MyThings" af-repeat="Add" actions="{update: true, delete: true}">
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
    CustomGroup::create(FALSE)
      ->addValue('name', 'MyThings')
      ->addValue('title', 'My Things')
      ->addValue('style', 'Tab with table')
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->addChain('fields', CustomField::save()
        ->addDefault('custom_group_id', '$id')
        ->setRecords([
          ['name' => 'my_text', 'label' => 'My Text', 'data_type' => 'String', 'html_type' => 'Text'],
          ['name' => 'my_friend', 'label' => 'My Friend', 'data_type' => 'ContactReference', 'html_type' => 'Autocomplete-Select'],
        ])
      )
      ->execute();

    $this->useValues([
      'layout' => self::$layouts['joinUncheckedActions'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $locationType = \CRM_Core_BAO_LocationType::getDefault()->id;
    $cid1 = $this->createTestRecord('Individual')['id'];
    $cid2 = $this->createTestRecord('Individual')['id'];

    // Create contact with email and custom fields
    $contact = \Civi\Api4\Contact::create(FALSE)
      ->addValue('first_name', 'Bob')
      ->addValue('last_name', $lastName)
      ->addValue('email_primary.email', '123@example.com')
      ->addValue('email_primary.location_type_id', $locationType)
      ->addValue('email_primary.is_primary', TRUE)
      ->addValue('Custom_MyThings.my_text', "One")
      ->addValue('Custom_MyThings.my_friend', $cid1)
      ->execute()->single();

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Bob',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Email' => [
              ['email' => '123@example.com', 'location_type_id' => $locationType, 'is_primary' => TRUE],
            ],
            'Custom_MyThings' => [
              ['my_text' => 'Two', 'my_friend' => $cid2],
              ['my_text' => 'Three', 'my_friend' => $cid2],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $contact = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'Custom_MyThings.my_friend')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();

    $this->assertEquals('One', $contact[0]['Custom_MyThings.my_text']);
    $this->assertEquals($cid1, $contact[0]['Custom_MyThings.my_friend']);
    $this->assertEquals('Two', $contact[1]['Custom_MyThings.my_text']);
    $this->assertEquals($cid2, $contact[1]['Custom_MyThings.my_friend']);
    // We can test more since this custom data set has no max
    $this->assertEquals('Three', $contact[2]['Custom_MyThings.my_text']);
    $this->assertEquals($cid2, $contact[2]['Custom_MyThings.my_friend']);
  }

  /**
   * Checks that checked actions will behave as is
   */
  public function testJoinEntityActionsChecked(): void {
    CustomGroup::create(FALSE)
      ->addValue('name', 'MyThings')
      ->addValue('title', 'My Things')
      ->addValue('style', 'Tab with table')
      ->addValue('extends', 'Contact')
      ->addValue('is_multiple', TRUE)
      ->addChain('fields', CustomField::save()
        ->addDefault('custom_group_id', '$id')
        ->setRecords([
          ['name' => 'my_text', 'label' => 'My Text', 'data_type' => 'String', 'html_type' => 'Text'],
          ['name' => 'my_friend', 'label' => 'My Friend', 'data_type' => 'ContactReference', 'html_type' => 'Autocomplete-Select'],
        ])
      )
      ->execute();

    $this->useValues([
      'layout' => self::$layouts['joinCheckedActions'],
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);
    $locationType = \CRM_Core_BAO_LocationType::getDefault()->id;
    $cid1 = $this->createTestRecord('Individual')['id'];
    $cid2 = $this->createTestRecord('Individual')['id'];

    // Create contact with email and custom fields
    $contact = \Civi\Api4\Contact::create(FALSE)
      ->addValue('first_name', 'Bobby')
      ->addValue('last_name', $lastName)
      ->addValue('email_primary.email', '1234@example.com')
      ->addValue('email_primary.location_type_id', $locationType)
      ->addValue('email_primary.is_primary', TRUE)
      ->addValue('Custom_MyThings.my_text', "One")
      ->addValue('Custom_MyThings.my_friend', $cid1)
      ->execute()->single();

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Bobby',
            'last_name' => $lastName,
          ],
          'joins' => [
            'Email' => [
              ['email' => '1234@example.com', 'location_type_id' => $locationType, 'is_primary' => TRUE],
            ],
            'Custom_MyThings' => [
              ['my_text' => 'Two', 'my_friend' => $cid2],
              ['my_text' => 'Three', 'my_friend' => $cid2],
            ],
          ],
        ],
      ],
    ];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    $contact = Contact::get(FALSE)
      ->addWhere('last_name', '=', $lastName)
      ->addJoin('Custom_MyThings AS Custom_MyThings', 'LEFT', ['id', '=', 'Custom_MyThings.entity_id'])
      ->addSelect('Custom_MyThings.my_text', 'Custom_MyThings.my_friend')
      ->addOrderBy('Custom_MyThings.id')
      ->execute();

    $this->assertEquals('Two', $contact[0]['Custom_MyThings.my_text']);
    $this->assertEquals($cid2, $contact[0]['Custom_MyThings.my_friend']);
    // We can test more since this custom data set has no max
    $this->assertEquals('Three', $contact[1]['Custom_MyThings.my_text']);
    $this->assertEquals($cid2, $contact[1]['Custom_MyThings.my_friend']);
  }

}
