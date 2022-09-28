<?php

use Civi\Api4\Relationship;

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class api_v4_AfformRelationshipUsageTest extends api_v4_AfformUsageTestCase {

  /**
   * Tests creating a relationship between multiple contacts
   */
  public function testCreateContactsWithRelationships(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual', source: 'Test Rel'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <af-entity security="FBAC" type="Relationship" name="Relationship1" label="Relationship 1" actions="{create: true, update: true}" data="{contact_id_b: ['Individual1'], contact_id_a: ['Individual2'], relationship_type_id: '1'}" />
  <af-entity data="{contact_type: 'Individual', source: 'Test Rel'}" type="Contact" name="Individual2" label="Individual 2" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="Relationship1" class="af-container"></fieldset>
  <fieldset af-fieldset="Individual2" class="af-container" af-title="Individual 2" af-repeat="Add" min="1">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $lastName = uniqid(__FUNCTION__);

    $submission = [
      'Individual1' => [
        ['fields' => ['first_name' => 'Firsty1', 'last_name' => $lastName]],
      ],
      'Individual2' => [
        ['fields' => ['first_name' => 'Firsty2', 'last_name' => $lastName]],
        ['fields' => ['first_name' => 'Firsty3', 'last_name' => $lastName]],
      ],
    ];

    Civi\Api4\Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute();

    $saved = Relationship::get(FALSE)
      ->addWhere('contact_id_b.last_name', '=', $lastName)
      ->addSelect('contact_id_a.first_name', 'is_active')
      ->addOrderBy('contact_id_a.first_name')
      ->execute();

    $this->assertCount(2, $saved);
    $this->assertEquals('Firsty2', $saved[0]['contact_id_a.first_name']);
    $this->assertEquals('Firsty3', $saved[1]['contact_id_a.first_name']);
  }

}
