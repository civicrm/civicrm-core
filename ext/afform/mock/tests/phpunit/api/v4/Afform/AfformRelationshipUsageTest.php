<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;
use Civi\Api4\Contact;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipType;
use Civi\Test\TransactionalInterface;

/**
 * Test case for Afform.prefill and Afform.submit.
 *
 * @group headless
 */
class AfformRelationshipUsageTest extends AfformUsageTestCase implements TransactionalInterface {

  /**
   * Tests creating a relationship between multiple contacts
   */
  public function testCreateContactsWithPresetRelationships(): void {
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
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
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

    Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute();

    $saved = Relationship::get(FALSE)
      ->addWhere('contact_id_b.last_name', '=', $lastName)
      ->addSelect('contact_id_a.first_name', 'is_active', 'relationship_type_id')
      ->addOrderBy('contact_id_a.first_name')
      ->execute();

    $this->assertCount(2, $saved);
    $this->assertEquals('Firsty2', $saved[0]['contact_id_a.first_name']);
    $this->assertEquals('Firsty3', $saved[1]['contact_id_a.first_name']);
    $this->assertEquals(1, $saved[0]['relationship_type_id']);
    $this->assertEquals(1, $saved[1]['relationship_type_id']);
  }

  /**
   * Tests creating multiple relationships using af-repeat
   */
  public function testCreateContactsWithMultipleRelationships(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual', source: 'Test Rel'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <af-entity security="FBAC" type="Relationship" name="Relationship1" label="Relationship 1" actions="{create: true, update: true}" data="{contact_id_b: ['Individual1'], contact_id_a: ['Org1']}" />
  <af-entity data="{contact_type: 'Organization', source: 'Test Rel'}" type="Contact" name="Org1" label="Org" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <afblock-name-individual></afblock-name-individual>
  </fieldset>
  <fieldset af-fieldset="Relationship1" class="af-container" af-repeat="Add" min="1">
    <af-field name="relationship_type_id"></af-field>
  </fieldset>
  <fieldset af-fieldset="Org1" class="af-container" af-title="Org 1">
    <afblock-name-organization></afblock-name-organization>
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $types = [
      uniqid(__FUNCTION__),
      uniqid(__FUNCTION__),
    ];
    $typeIds = [];

    foreach ($types as $type) {
      $typeIds[] = RelationshipType::create(FALSE)
        ->addValue('contact_type_a', 'Organization')
        ->addValue('contact_type_b', 'Individual')
        ->addValue('name_a_b', $type)
        ->addValue('name_b_a', "$type of")
        ->execute()->first()['id'];
    }

    $lastName = uniqid(__FUNCTION__);

    $submission = [
      'Individual1' => [
        ['fields' => ['first_name' => 'Firsty', 'last_name' => $lastName]],
      ],
      'Org1' => [
        ['fields' => ['organization_name' => "Hello $lastName"]],
      ],
      'Relationship1' => [
        ['fields' => ['relationship_type_id' => $typeIds[0]]],
        ['fields' => ['relationship_type_id' => $typeIds[1]]],
      ],
    ];

    Afform::submit()
      ->setName($this->formName)
      ->setValues($submission)
      ->execute();

    $saved = Relationship::get(FALSE)
      ->addWhere('contact_id_b.last_name', '=', $lastName)
      ->addSelect('contact_id_a.organization_name', 'is_active', 'relationship_type_id')
      ->addOrderBy('relationship_type_id')
      ->execute();

    $this->assertEquals("Hello $lastName", $saved[0]['contact_id_a.organization_name']);
    $this->assertEquals($typeIds[0], $saved[0]['relationship_type_id']);
    $this->assertEquals("Hello $lastName", $saved[1]['contact_id_a.organization_name']);
    $this->assertEquals($typeIds[1], $saved[1]['relationship_type_id']);
    $this->assertCount(2, $saved);
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
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    $contact = Contact::save(FALSE)
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

    $prefill = Afform::prefill(FALSE)
      ->setName($this->formName)
      ->setFillMode('entity')
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

  /**
   * Build a form that relates Person1 (a) to Person2 (b) and lets a edit b.
   */
  private function useRelationshipFormWithEditPermission(int $typeID): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" data="{}" name="Person1" actions="{create: true, update: true}" security="FBAC" url-autofill="1" />
  <af-entity type="Individual" data="{}" name="Person2" actions="{create: true, update: true}" security="FBAC" url-autofill="1" />
  <af-entity type="Relationship" name="Relationship1" actions="{create: true, update: true}" security="FBAC" data="{contact_id_a: ['Person1'], contact_id_b: ['Person2'], relationship_type_id: $typeID, is_permission_a_b: 1}" />
  <fieldset af-fieldset="Person1"><af-field name="first_name" /></fieldset>
  <fieldset af-fieldset="Person2"><af-field name="first_name" /></fieldset>
  <fieldset af-fieldset="Relationship1"><af-field name="description" /></fieldset>
</af-form>
EOHTML;
    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);
  }

  private function submitRelationshipForm(int $person1, int $person2, string $description): void {
    Afform::submit()
      ->setName($this->formName)
      ->setArgs(['Person1' => $person1, 'Person2' => $person2])
      ->setValues([
        'Person1' => [['fields' => ['first_name' => 'One']]],
        'Person2' => [['fields' => ['first_name' => 'Two']]],
        'Relationship1' => [['fields' => ['description' => $description]]],
      ])
      ->execute();
  }

  /**
   * Submitting again for an existing relationship keeps the permission direction.
   */
  public function testResubmitKeepsRelationshipPermissionDirection(): void {
    $typeID = $this->createTestRecord('RelationshipType', [
      'name_a_b' => 'Mentor of',
      'name_b_a' => 'Mentored by',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ])['id'];
    $this->useRelationshipFormWithEditPermission($typeID);
    $mentor = $this->createTestRecord('Individual')['id'];
    $mentee = $this->createTestRecord('Individual')['id'];

    foreach (['first', 'second'] as $description) {
      $this->submitRelationshipForm($mentor, $mentee, $description);
      $relationship = Relationship::get(FALSE)
        ->addWhere('relationship_type_id', '=', $typeID)
        ->execute()->single();
      $this->assertSame($description, $relationship['description']);
      $this->assertSame($mentor, $relationship['contact_id_a']);
      $this->assertSame([1, 0], [$relationship['is_permission_a_b'], $relationship['is_permission_b_a']], "after the $description submission");
    }
    // Drop the administer grant of the test case, so only the relationship counts.
    \CRM_Core_Config::singleton()->userPermissionTemp = new \CRM_Core_Permission_Temp();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->assertTrue(\CRM_Contact_BAO_Contact_Permission::allow($mentee, \CRM_Core_Permission::EDIT, $mentor));
    $this->assertFalse(\CRM_Contact_BAO_Contact_Permission::allow($mentor, \CRM_Core_Permission::EDIT, $mentee));
  }

  /**
   * A reciprocal relationship stored in the other direction gets the permissions swapped.
   */
  public function testFlippedReciprocalRelationshipSwapsPermissions(): void {
    $typeID = $this->createTestRecord('RelationshipType', [
      'name_a_b' => 'Peer of',
      'name_b_a' => 'Peer of',
      'label_a_b' => 'Peer of',
      'label_b_a' => 'Peer of',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ])['id'];
    $this->useRelationshipFormWithEditPermission($typeID);
    $person1 = $this->createTestRecord('Individual')['id'];
    $person2 = $this->createTestRecord('Individual')['id'];
    $this->createTestRecord('Relationship', [
      'relationship_type_id' => $typeID,
      'contact_id_a' => $person2,
      'contact_id_b' => $person1,
    ]);

    foreach (['first', 'second'] as $description) {
      $this->submitRelationshipForm($person1, $person2, $description);
      $relationship = Relationship::get(FALSE)
        ->addWhere('relationship_type_id', '=', $typeID)
        ->execute()->single();
      $this->assertSame($description, $relationship['description']);
      $this->assertSame($person2, $relationship['contact_id_a']);
      $this->assertSame([0, 1], [$relationship['is_permission_a_b'], $relationship['is_permission_b_a']], "after the $description submission");
    }
  }

}
