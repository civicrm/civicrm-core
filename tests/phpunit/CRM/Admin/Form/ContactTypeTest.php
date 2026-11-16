<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\ContactType;
use Civi\Api4\Navigation;
use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

/**
 * Tests for CRM_Admin_Form_ContactType.
 *
 * @group headless
 */
class CRM_Admin_Form_ContactTypeTest extends CiviUnitTestCase {
  use FormTrait;

  /**
   * @var array
   */
  private array $createdContactTypeIds = [];

  /**
   * @var array
   */
  private array $originalContactTypes = [];

  public function tearDown(): void {
    foreach ($this->createdContactTypeIds as $id) {
      ContactType::delete(FALSE)->addWhere('id', '=', $id)->execute();
    }
    foreach ($this->originalContactTypes as $id => $values) {
      ContactType::update(FALSE)
        ->addWhere('id', '=', $id)
        ->setValues($values)
        ->execute();
    }
    parent::tearDown();
  }

  /**
   * Test adding a new contact subtype.
   */
  public function testAddContactSubtype(): void {
    $individual = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->execute()
      ->first();
    $this->originalContactTypes[$individual['id']] = [
      'label' => $individual['label'],
      'description' => $individual['description'],
      'is_active' => $individual['is_active'],
    ];

    $submittedValues = [
      'label' => 'Alumni',
      'parent_id' => $individual['id'],
      'description' => 'Former students',
      'icon' => 'fa-graduation-cap',
      'is_active' => 1,
    ];

    $this->getTestForm('CRM_Admin_Form_ContactType', $submittedValues, ['action' => 'add'])
      ->processForm();

    $created = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Alumni')
      ->execute()
      ->first();

    $this->assertNotEmpty($created);
    $this->createdContactTypeIds[] = $created['id'];
    $this->assertSame('Alumni', $created['label']);
    $this->assertSame($individual['id'], $created['parent_id']);
    $this->assertSame('Former students', $created['description']);
    $this->assertSame('fa-graduation-cap', $created['icon']);
    $this->assertTrue($created['is_active']);

    $nav = Navigation::get(FALSE)
      ->addWhere('name', '=', 'New Alumni')
      ->execute()
      ->first();
    $this->assertNotEmpty($nav);
    $this->assertSame('New Alumni', $nav['label']);
  }

  /**
   * Test validation rules when adding a contact subtype.
   */
  public function testAddContactSubtypeValidation(): void {
    $individual = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->execute()
      ->first();
    $this->originalContactTypes[$individual['id']] = [
      'label' => $individual['label'],
      'description' => $individual['description'],
      'is_active' => $individual['is_active'],
    ];

    // Missing required parent_id on add
    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Valid Subtype',
    ], ['action' => 'add'])->processForm(FormWrapper::VALIDATED);
    $this->assertValidationError([
      'parent_id' => 'Basic Contact Type is a required field.',
    ]);

    // Duplicate contact type name (use a seeded subtype like 'Student', not a reserved keyword)
    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Student',
      'parent_id' => $individual['id'],
    ], ['action' => 'add'])->processForm(FormWrapper::VALIDATED);
    $this->assertValidationError([
      'label' => 'This contact type name already exists in database. Contact type names must be unique.',
    ]);

    // Reserved keyword
    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Activity',
      'parent_id' => $individual['id'],
    ], ['action' => 'add'])->processForm(FormWrapper::VALIDATED);
    $this->assertValidationError([
      'label' => 'Contact type names should not use reserved keywords.',
    ]);

    // Invalid parent_id (not a basic contact type)
    $student = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Student')
      ->execute()
      ->first();
    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Valid Subtype',
      'parent_id' => $student['id'],
    ], ['action' => 'add'])->processForm(FormWrapper::VALIDATED);
    $this->assertValidationError([
      'parent_id' => 'Basic Contact Type is not valid.',
    ]);
  }

  /**
   * Test updating an existing contact subtype.
   */
  public function testUpdateContactSubtype(): void {
    $individual = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->execute()
      ->first();

    $subtype = ContactType::create(FALSE)
      ->addValue('name', 'GraduateFellow')
      ->addValue('label', 'Graduate Fellow')
      ->addValue('parent_id', $individual['id'])
      ->addValue('image_URL', 'https://example.org/fellow.png')
      ->addValue('is_active', TRUE)
      ->execute()
      ->first();
    $this->createdContactTypeIds[] = $subtype['id'];

    $submittedValues = [
      'label' => 'Graduate Student',
      'parent_id' => $individual['id'],
      'description' => 'Grad students',
      'icon' => 'fa-user-graduate',
      'is_active' => 0,
    ];

    $this->getTestForm('CRM_Admin_Form_ContactType', $submittedValues, [
      'action' => 'update',
      'id' => $subtype['id'],
    ])->processForm();

    $updated = ContactType::get(FALSE)
      ->addWhere('id', '=', $subtype['id'])
      ->execute()
      ->first();

    $this->assertSame('Graduate Student', $updated['label']);
    $this->assertSame($individual['id'], $updated['parent_id']);
    $this->assertSame('Grad students', $updated['description']);
    $this->assertSame('fa-user-graduate', $updated['icon']);
    $this->assertEmpty($updated['image_URL']);
    $this->assertFalse($updated['is_active']);
  }

  /**
   * Test updating built-in contact types (Individual cannot be disabled, Household can).
   */
  public function testUpdateBuiltInContactType(): void {
    $individual = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Individual')
      ->execute()
      ->first();
    $this->originalContactTypes[$individual['id']] = [
      'label' => $individual['label'],
      'description' => $individual['description'],
      'is_active' => $individual['is_active'],
    ];

    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Person',
      'description' => 'Individual person',
      'is_active' => 1,
    ], [
      'action' => 'update',
      'id' => $individual['id'],
    ])->processForm();

    $updatedIndividual = ContactType::get(FALSE)
      ->addWhere('id', '=', $individual['id'])
      ->execute()
      ->first();

    $this->assertSame('Person', $updatedIndividual['label']);
    $this->assertSame('Individual person', $updatedIndividual['description']);
    $this->assertNull($updatedIndividual['parent_id']);
    $this->assertTrue($updatedIndividual['is_active']);

    $household = ContactType::get(FALSE)
      ->addWhere('name', '=', 'Household')
      ->execute()
      ->first();
    $this->originalContactTypes[$household['id']] = [
      'label' => $household['label'],
      'is_active' => $household['is_active'],
    ];

    $this->getTestForm('CRM_Admin_Form_ContactType', [
      'label' => 'Household',
      'is_active' => 0,
    ], [
      'action' => 'update',
      'id' => $household['id'],
    ])->processForm();

    $updatedHousehold = ContactType::get(FALSE)
      ->addWhere('id', '=', $household['id'])
      ->execute()
      ->first();

    $this->assertFalse($updatedHousehold['is_active']);
  }

  /**
   * Test deleting a contact subtype.
   */
  public function testDeleteContactSubtype(): void {
    $subtype = ContactType::create(FALSE)
      ->addValue('name', 'TempSubtype')
      ->addValue('label', 'Temp Subtype')
      ->addValue('parent_id.name', 'Individual')
      ->execute()
      ->first();

    $this->getTestForm('CRM_Admin_Form_ContactType', [], [
      'action' => 'delete',
      'id' => $subtype['id'],
    ])->processForm();

    $count = ContactType::get(FALSE)
      ->addWhere('id', '=', $subtype['id'])
      ->execute()
      ->count();

    $this->assertSame(0, $count);
  }

}
