<?php

use Civi\Api4\Contact;
use Civi\Api4\GroupContact;

/**
 * Test case for Afform with autocomplete.
 *
 * @group headless
 */
class api_v4_AfformAutocompleteUsageTest extends api_v4_AfformUsageTestCase {

  /**
   * Tests creating a relationship between multiple contacts
   */
  public function testAutocompleteWithSavedSearchFilter(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
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
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // Saved search for filtering
    \Civi\Api4\SavedSearch::create(FALSE)
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
    Contact::save(FALSE)
      ->setRecords($sampleContacts)
      ->addDefault('last_name', $lastName)
      ->execute();

    $result = Contact::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('Individual1:id')
      ->setInput($lastName)
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals('A ' . $lastName, $result[0]['label']);
    $this->assertEquals('B ' . $lastName, $result[1]['label']);
  }

  public function testCustomContactRefFieldWithGroupsFilter(): void {
    $lastName = uniqid(__FUNCTION__);

    $sampleData = [
      ['last_name' => $lastName, 'first_name' => 'A'],
      ['last_name' => $lastName, 'first_name' => 'B'],
      ['last_name' => $lastName, 'first_name' => 'C'],
    ];

    $contacts = Contact::save(FALSE)
      ->setRecords($sampleData)
      ->execute();

    $group = \Civi\Api4\Group::create(FALSE)
      ->addValue('name', $lastName)
      ->addValue('title', $lastName)
      ->addChain('A', GroupContact::create()->addValue('group_id', '$id')->addValue('contact_id', $contacts[0]['id']))
      ->addChain('B', GroupContact::create()->addValue('group_id', '$id')->addValue('contact_id', $contacts[1]['id']))
      ->execute()->single();

    \Civi\Api4\CustomGroup::create(FALSE)
      ->addValue('title', 'test_af_fields')
      ->addValue('extends', 'Contact')
      ->addChain('fields', \Civi\Api4\CustomField::save()
        ->addDefault('custom_group_id', '$id')
        ->setRecords([
          ['label' => 'contact_ref', 'data_type' => 'ContactReference', 'html_type' => 'Autocomplete', 'filter' => 'action=get&group=' . $group['id']],
        ])
      )
      ->execute();

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <div class="af-container">
      <af-field name="test_af_fields.contact_ref" />
    </div>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $result = Contact::autocomplete()
      ->setFormName('afform:' . $this->formName)
      ->setFieldName('Individual1:test_af_fields.contact_ref')
      ->setInput($lastName)
      ->execute();

    $this->assertCount(2, $result);
    $this->assertEquals('A ' . $lastName, $result[0]['label']);
    $this->assertEquals('B ' . $lastName, $result[1]['label']);
  }

}
