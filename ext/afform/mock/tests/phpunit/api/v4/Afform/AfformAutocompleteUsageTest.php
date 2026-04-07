<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\OptionValue;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class AfformAutocompleteUsageTest extends AfformUsageTestCase {

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
    $this->createTestRecord('SavedSearch', [
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
    ]);

    $lastName = uniqid(__FUNCTION__);

    $sampleContacts = [
      ['source' => 'Yes', 'first_name' => 'B'],
      ['source' => 'Yes', 'first_name' => 'A'],
      ['source' => 'No', 'first_name' => 'C'],
    ];
    $contacts = $this->saveTestRecords('Contact', [
      'records' => $sampleContacts,
      'defaults' => ['last_name' => $lastName],
    ])->column('id', 'first_name');

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
      $this->assertEquals('Illegal value for Existing Individual.', $e->getMessage());
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

    $contacts = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
    ])->column('id', 'first_name');

    $group = $this->createTestRecord('Group', [
      'name' => $lastName,
      'title' => $lastName,
    ]);
    // Place contacts A & B in the group, but not contact C
    $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contacts['A'],
    ]);
    $this->createTestRecord('GroupContact', [
      'group_id' => $group['id'],
      'contact_id' => $contacts['B'],
    ]);

    $this->createTestRecord('CustomGroup', [
      'extends' => 'Contact',
      'title' => 'test_af_fields',
    ]);
    $this->saveTestRecords('CustomField', [
      'defaults' => [
        'custom_group_id.name' => 'test_af_fields',
      ],
      'records' => [
        [
          'html_type' => 'Autocomplete-Select',
          'data_type' => 'ContactReference',
          'label' => 'contact_ref',
          'filter' => 'action=get&group=' . $group['id'],
        ],
      ],
    ]);

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
      $this->assertEquals('Illegal value for test_af_fields: contact_ref.', $e->getMessage());
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

    $contacts = $this->saveTestRecords('Contact', [
      'records' => $sampleData,
    ])->column('id', 'first_name');

    $this->createTestRecord('CustomGroup', [
      'extends' => 'Address',
      'title' => 'test_address_fields',
    ]);

    $this->saveTestRecords('CustomField', [
      'defaults' => [
        'custom_group_id.name' => 'test_address_fields',
      ],
      'records' => [
        [
          'html_type' => 'Autocomplete-Select',
          'data_type' => 'ContactReference',
          'label' => 'contact_ref',
          'filter' => 'action=get&source=in',
        ],
      ],
    ]);

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
      $this->assertEquals('Illegal value for test_address_fields: contact_ref.', $e->getMessage());
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

  /**
   * Tests autocomplete fields used as savedSearch filters
   *
   * @return void
   */
  public function testAutocompleteWithSearchJoin(): void {
    $this->createTestRecord('CustomGroup', [
      'extends' => 'Individual',
      'name' => 'test_af_autocomplete_search',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'test_af_autocomplete_search',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'String',
      'label' => 'select_auto',
      'option_values' => ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue', 'y' => 'Yellow'],
    ]);

    $this->createTestRecord('SavedSearch', [
      'name' => 'test_activity_search',
      'api_entity' => 'Activity',
      'api_params' => [
        'version' => 4,
        'select' => [
          'activity_type_id:label',
          'Activity_ActivityContact_Contact_01.sort_name',
          'Activity_ActivityContact_Contact_01.test_af_autocomplete_search.select_auto:label',
        ],
        'orderBy' => [],
        'where' => [],
        'groupBy' => [],
        'join' => [
          [
            'Contact AS Activity_ActivityContact_Contact_01',
            'LEFT',
            'ActivityContact',
            [
              'id',
              '=',
              'Activity_ActivityContact_Contact_01.activity_id',
            ],
            [
              'Activity_ActivityContact_Contact_01.record_type_id:name',
              '=',
              '"Activity Targets"',
            ],
          ],
        ],
        'having' => [],
      ],
    ]);

    $this->createTestRecord('SearchDisplay', [
      'name' => 'test_activity_search_display',
      'saved_search_id.name' => 'test_activity_search',
      'type' => 'table',
      'settings' => [
        'description' => NULL,
        'sort' => [],
        'limit' => 50,
        'pager' => [],
        'placeholder' => 5,
        'columns' => [
          [
            'type' => 'field',
            'key' => 'activity_type_id:label',
            'sortable' => TRUE,
          ],
          [
            'type' => 'field',
            'key' => 'Activity_ActivityContact_Contact_01.sort_name',
            'sortable' => TRUE,
          ],
          [
            'type' => 'field',
            'key' => 'Activity_ActivityContact_Contact_01.test_af_autocomplete_search.select_auto:label',
            'sortable' => TRUE,
          ],
        ],
      ],
    ]);

    $layout = <<<EOHTML
<div af-fieldset="">
  <af-field name="Activity_ActivityContact_Contact_01.test_af_autocomplete_search.select_auto" defn="{input_attrs: {multiple: true}}" />
  <crm-search-display-table search-name="test_activity_search" display-name="test_activity_search_display"></crm-search-display-table>
</div>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Autocompleting with the letter "l" will give 2 matches: Blue & Yellow
    $result = OptionValue::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('test_activity_search_display:Activity_ActivityContact_Contact_01.test_af_autocomplete_search.select_auto')
      ->setInput('l')
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals('Blue', $result[0]['label']);
    $this->assertEquals('Yellow', $result[1]['label']);
  }

}
