<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\SavedSearch;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class AfformAutocompleteUsageTest extends AfformUsageTestCase {

  public function tearDown(): void {
    CustomGroup::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    parent::tearDown();
  }

  /**
   * Ensure that Afform restricts autocomplete results when it's set to use a SavedSearch
   */
  public function testAutocompleteWithSavedSearchFilter(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <div class="af-container">
      <af-field name="id" defn="{saved_search: 'the_unit_test_search', input_attrs: {}}" />
      <afblock-name-individual></afblock-name-individual>
    </div>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Saved search for filtering
    SavedSearch::create(FALSE)
      ->setValues([
        'name' => 'the_unit_test_search',
        'label' => 'the_unit_test_search',
        'api_entity' => 'Contact',
        'api_params' => [
          'version' => 4,
          'select' => ['id', 'display_name'],
          'orderBy' => [],
          'where' => [
            ['contact_type:name', '=', 'Individual'],
            ['source', '=', 'Yes'],
          ],
        ],
      ])
      ->execute();

    $lastName = uniqid(__FUNCTION__);

    $sampleContacts = [
      ['source' => 'Yes', 'first_name' => 'B'],
      ['source' => 'Yes', 'first_name' => 'A'],
      ['source' => 'No', 'first_name' => 'C'],
    ];
    $contacts = Contact::save(FALSE)
      ->setRecords($sampleContacts)
      ->addDefault('last_name', $lastName)
      ->execute()->column('id', 'first_name');

    $result = Contact::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('Individual1:id')
      ->setInput($lastName)
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals($lastName . ', A', $result[0]['label']);
    $this->assertEquals($lastName . ', B', $result[1]['label']);

    // Ensure form validates submission, restricting it to contacts A & B
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Changed',
            // Not allowed because contact C doesn't meet filter criteria
            'id' => $contacts['C'],
          ],
        ],
      ],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail();
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Validation Error', $e->getMessage());
    }

    // Submit with a valid ID, it should work
    $values['Individual1'][0]['fields']['id'] = $contacts['B'];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    // Verify one contact was changed
    $check = Contact::get(FALSE)
      ->addWhere('first_name', '=', 'Changed')
      ->addWhere('last_name', '=', $lastName)
      ->selectRowCount()->execute();
    $this->assertCount(1, $check);
  }

  /**
   * Ensure Afform enforces group filter set on a custom contact reference field
   */
  public function testCustomContactRefFieldWithGroupsFilter(): void {
    $lastName = uniqid(__FUNCTION__);

    $sampleData = [
      ['last_name' => $lastName, 'first_name' => 'A'],
      ['last_name' => $lastName, 'first_name' => 'B'],
      ['last_name' => $lastName, 'first_name' => 'C'],
    ];

    $contacts = Contact::save(FALSE)
      ->setRecords($sampleData)
      ->execute()->column('id', 'first_name');

    // Place contacts A & B in the group, but not contact C
    $group = Group::create(FALSE)
      ->addValue('name', $lastName)
      ->addValue('title', $lastName)
      ->addChain('A', GroupContact::create()->addValue('group_id', '$id')->addValue('contact_id', $contacts['A']))
      ->addChain('B', GroupContact::create()->addValue('group_id', '$id')->addValue('contact_id', $contacts['B']))
      ->execute()->single();

    CustomGroup::create(FALSE)
      ->addValue('title', 'test_af_fields')
      ->addValue('extends', 'Contact')
      ->addChain('fields', CustomField::save()
        ->addDefault('custom_group_id', '$id')
        ->setRecords([
          ['label' => 'contact_ref', 'data_type' => 'ContactReference', 'html_type' => 'Autocomplete-Select', 'filter' => 'action=get&group=' . $group['id']],
        ])
      )
      ->execute();

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <div class="af-container">
      <af-field name="first_name" />
      <af-field name="test_af_fields.contact_ref" />
    </div>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $result = Contact::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('Individual1:test_af_fields.contact_ref')
      ->setInput($lastName)
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals($lastName . ', A', $result[0]['label']);
    $this->assertEquals($lastName . ', B', $result[1]['label']);

    // Ensure form validates submission, restricting it to contacts A & B
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Testy',
            // Not allowed because contact C doesn't meet filter criteria
            'test_af_fields.contact_ref' => $contacts['C'],
          ],
        ],
      ],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail();
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Validation Error', $e->getMessage());
    }

    // Submit with a valid ID, it should work
    $values['Individual1'][0]['fields']['test_af_fields.contact_ref'] = $contacts['B'];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    // Verify contact was saved with custom value
    $check = Contact::get(FALSE)
      ->addWhere('test_af_fields.contact_ref', '=', $contacts['B'])
      ->selectRowCount()->execute();
    $this->assertCount(1, $check);
  }

  /**
   * Ensure autocomplete contact reference fields work on a join entity
   */
  public function testCustomContactRefFieldOnJoinEntity(): void {
    $lastName = uniqid(__FUNCTION__);

    $sampleData = [
      ['last_name' => $lastName, 'first_name' => 'A', 'source' => 'in'],
      ['last_name' => $lastName, 'first_name' => 'B', 'source' => 'out'],
      ['last_name' => $lastName, 'first_name' => 'C', 'source' => 'in'],
    ];

    $contacts = Contact::save(FALSE)
      ->setRecords($sampleData)
      ->execute()->column('id', 'first_name');

    CustomGroup::create(FALSE)
      ->addValue('title', 'test_address_fields')
      ->addValue('extends', 'Address')
      ->addChain('fields', CustomField::save()
        ->addDefault('custom_group_id', '$id')
        ->setRecords([
          ['label' => 'contact_ref', 'data_type' => 'ContactReference', 'html_type' => 'Autocomplete-Select', 'filter' => 'action=get&source=in'],
        ])
      )
      ->execute();

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <div class="af-container">
      <af-field name="first_name" />
      <div af-join="Address" data="{is_primary: true}">
        <af-field name="street_address" />
        <af-field name="test_address_fields.contact_ref" />
      </div>
    </div>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $result = Contact::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('Individual1+Address:test_address_fields.contact_ref')
      ->setInput($lastName)
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals($lastName . ', A', $result[0]['label']);
    $this->assertEquals($lastName . ', C', $result[1]['label']);

    // Ensure form validates submission, restricting it to contacts A & C
    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Testy',
          ],
          'joins' => [
            'Address' => [
              // Not allowed because contact B doesn't meet filter criteria
              ['test_address_fields.contact_ref' => $contacts['B']],
            ],
          ],
        ],
      ],
    ];
    try {
      Afform::submit()
        ->setName($this->formName)
        ->setValues($values)
        ->execute();
      $this->fail();
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Validation Error', $e->getMessage());
    }

    // Submit with a valid ID, it should work
    $values['Individual1'][0]['joins']['Address'][0]['test_address_fields.contact_ref'] = $contacts['A'];
    Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    // Verify contact was saved with custom value
    $check = Contact::get(FALSE)
      ->addWhere('address_primary.test_address_fields.contact_ref', '=', $contacts['A'])
      ->selectRowCount()->execute();
    $this->assertCount(1, $check);
  }

}
