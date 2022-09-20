<?php

use Civi\Api4\Contact;

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

}
