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

  public function testPrefillContactsByRelationship(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" autofill="relationship:Child of" autofill-relationship="Individual2"/>
  <af-entity data="{contact_type: 'Organization'}" type="Contact" name="Organization1" label="Organization 1" actions="{create: true, update: true}" security="RBAC" url-autofill="1" />
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual2" label="Individual 2" actions="{create: true, update: true}" security="RBAC" autofill="relationship:Employee of" autofill-relationship="Organization1"/>
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1" af-repeat="Add" min="1" max="2">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="Individual2" class="af-container" af-title="Individual 2">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="Organization1" class="af-container" af-title="Organization1">
    <afblock-name-organization></afblock-name-organization>
  </fieldset>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $contact = \Civi\Api4\Contact::save(FALSE)
      ->addRecord(['first_name' => 'Child1'])
      ->addRecord(['first_name' => 'Child2', 'is_deleted' => TRUE])
      ->addRecord(['first_name' => 'Parent'])
      ->addRecord(['organization_name' => 'Employer'])
      ->addRecord(['first_name' => 'Child3'])
      ->addRecord(['first_name' => 'Child4'])
      ->addRecord(['first_name' => 'Child5'])
      ->execute()->column('id');

    Relationship::save(FALSE)
      ->addRecord(['contact_id_a' => $contact[2], 'contact_id_b' => $contact[3], 'relationship_type_id:name' => 'Employee of'])
      ->addRecord(['contact_id_a' => $contact[0], 'contact_id_b' => $contact[2], 'relationship_type_id:name' => 'Child of'])
      ->addRecord(['contact_id_a' => $contact[1], 'contact_id_b' => $contact[2], 'relationship_type_id:name' => 'Child of'])
      ->addRecord(['contact_id_a' => $contact[4], 'contact_id_b' => $contact[2], 'relationship_type_id:name' => 'Child of', 'is_active' => FALSE])
      ->addRecord(['contact_id_a' => $contact[5], 'contact_id_b' => $contact[2], 'relationship_type_id:name' => 'Child of'])
      ->addRecord(['contact_id_a' => $contact[6], 'contact_id_b' => $contact[2], 'relationship_type_id:name' => 'Child of'])
      ->execute();

    $prefill = Civi\Api4\Afform::prefill(FALSE)
      ->setName($this->formName)
      ->setArgs(['Organization1' => $contact[3]])
      ->execute()
      ->indexBy('name');

    $this->assertEquals('Employer', $prefill['Organization1']['values'][0]['fields']['organization_name']);
    $this->assertEquals('Parent', $prefill['Individual2']['values'][0]['fields']['first_name']);
    $this->assertEquals('Child1', $prefill['Individual1']['values'][0]['fields']['first_name']);
    $this->assertEquals('Child4', $prefill['Individual1']['values'][1]['fields']['first_name']);
    // No room on form for a 3rd child because af-repeat max=2
    $this->assertCount(2, $prefill['Individual1']['values']);
  }

}
