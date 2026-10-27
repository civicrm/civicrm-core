<?php
use Civi\Api4\ContactType;

/**
 * Class CRM_Contact_BAO_ContactType_ContactTypeTest
 * @group headless
 */
class CRM_Contact_BAO_ContactType_ContactTypeTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $params = [
      'label' => 'Sub1 Individual',
      'name' => 'sub1_individual',
      'parent_id:name' => 'Individual',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'Sub2 Individual',
      'name' => 'sub2_individual',
      'parent_id:name' => 'Individual',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'Sub Organization',
      'name' => 'sub_organization',
      'parent_id:name' => 'Organization',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'Sub Household',
      'name' => 'sub_household',
      'parent_id:name' => 'Household',
      'is_active' => 1,
    ];
    $this->ids['ContactType'][] = (int) ContactType::create()->setValues($params)->execute()->first()['id'];
  }

  private function strictArrayCompare($arr1, $arr2) {
    ksort($arr1);
    foreach ($arr1 as &$value) {
      ksort($value);
    }
    ksort($arr2);
    foreach ($arr2 as &$value) {
      ksort($value);
    }
    $this->assertSame($arr1, $arr2);
  }

  /**
   * Cleanup contact types.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    parent::tearDown();
    ContactType::delete()->addWhere('id', 'IN', $this->ids['ContactType'])->execute();
    ContactType::update(FALSE)
      ->addValue('is_active', TRUE)
      ->addWhere('id', '>', 0)
      ->execute();
  }

  /**
   * Test contactTypes() and subTypes() methods return correct contact types.
   */
  public function testGetMethods(): void {
    $result = CRM_Contact_BAO_ContactType::contactTypes(TRUE);
    $this->assertEquals(array_keys($this->getExpectedContactTypes()), $result);

    // check for type:Individual
    $result = CRM_Contact_BAO_ContactType::subTypes('Individual');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Individual')), $result);
    $this->strictArrayCompare($this->getExpectedContactSubTypes('Individual'), CRM_Contact_BAO_ContactType::subTypeInfo('Individual'));

    // check for type:Organization
    $result = CRM_Contact_BAO_ContactType::subTypes('Organization');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Organization')), $result);
    $this->strictArrayCompare($this->getExpectedContactSubTypes('Organization'), CRM_Contact_BAO_ContactType::subTypeInfo('Organization'));

    // check for type:Household
    $result = CRM_Contact_BAO_ContactType::subTypes('Household');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Household')), $result);
    $this->strictArrayCompare($this->getExpectedContactSubTypes('Household'), CRM_Contact_BAO_ContactType::subTypeInfo('Household'));

    // check for all contact types
    $result = CRM_Contact_BAO_ContactType::subTypes();
    $subtypes = array_keys($this->getExpectedAllSubtypes());
    $this->assertEquals(sort($subtypes), sort($result));
    $this->strictArrayCompare($this->getExpectedAllSubtypes(), CRM_Contact_BAO_ContactType::subTypeInfo());

    // Invalid input yields empty result
    $params = 'invalid';
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEmpty($result);

    $params = ['invalid'];
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEmpty($result);

    // Test getBasicType with string input
    $this->assertEquals('Individual', CRM_Contact_BAO_ContactType::getBasicType('sub1_individual'));
    $this->assertEquals('Individual', CRM_Contact_BAO_ContactType::getBasicType('sub2_individual'));
    $this->assertEquals('Organization', CRM_Contact_BAO_ContactType::getBasicType('sub_organization'));
    $this->assertEquals('Household', CRM_Contact_BAO_ContactType::getBasicType('sub_household'));
    $this->assertNull(CRM_Contact_BAO_ContactType::getBasicType('invalid'));

    // Test getBasicTypes with array input
    $subTypeToType = [
      'sub1_individual' => 'Individual',
      'sub2_individual' => 'Individual',
      'sub_organization' => 'Organization',
      'sub_household' => 'Household',
    ];
    $this->assertEquals($subTypeToType, CRM_Contact_BAO_ContactType::getBasicType(array_keys($subTypeToType)));

    // Test getSelectElements
    $selectElements = CRM_Contact_BAO_ContactType::getSelectElements();
    $this->assertEquals('- Sub1 Individual', $selectElements['Individual__sub1_individual']);
    $this->assertEquals('- Sub Organization', $selectElements['Organization__sub_organization']);
    $selectOrder = array_flip(array_keys($selectElements));
    // Individual is always first
    $this->assertEquals(0, $selectOrder['Individual']);
    // Sub-types should come after the primary type, but before the next primary type
    $this->assertGreaterThan($selectOrder['Individual__sub1_individual'], $selectOrder['Individual__sub2_individual']);
    $this->assertGreaterThan($selectOrder['Individual__sub1_individual'], $selectOrder['Organization']);
    $this->assertGreaterThan($selectOrder['Organization'], $selectOrder['Organization__sub_organization']);
  }

  /**
   * Test function for getting contact types.
   */
  public function testContactTypeInfo(): void {
    $blahType = ['is_active' => 0, 'name' => 'blah', 'label' => 'blah blah', 'parent_id:name' => 'Individual', 'icon' => 'fa-random'];
    $createdType = $this->createTestEntity('ContactType', $blahType);
    $activeTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    $expected = $this->getExpectedContactTypes();
    $this->strictArrayCompare($expected, $activeTypes);
    $allTypes = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    $expected['blah'] = [
      'is_active' => FALSE,
      'name' => 'blah',
      'label' => 'blah blah',
      'id' => $createdType['id'],
      'parent_id' => $this->getContactTypeID('Individual'),
      'is_reserved' => FALSE,
      'parent' => 'Individual',
      'parent_label' => 'Individual',
      'description' => NULL,
      'image_URL' => NULL,
      'icon' => 'fa-random',
    ];
    // Verify function returns field values formatted correctly by type
    $this->strictArrayCompare($expected, $allTypes);
  }

  public function testGetBasicTypePairs(): void {
    ContactType::update(FALSE)
      ->addValue('is_active', TRUE)
      ->addWhere('parent_id', 'IS NULL')
      ->execute();

    $result = CRM_Contact_BAO_ContactType::basicTypePairs();
    $this->assertEquals(['Individual', 'Household', 'Organization'], array_keys($result));

    $result = CRM_Contact_BAO_ContactType::basicTypePairs(FALSE, 'id');
    $this->assertEquals([$this->getContactTypeID('Individual'), $this->getContactTypeID('Household'), $this->getContactTypeID('Organization')], array_keys($result));
    $this->assertEquals(['Individual', 'Household', 'Organization'], array_values($result));

    ContactType::update(FALSE)
      ->addValue('is_active', FALSE)
      ->addWhere('name', '=', 'Household')
      ->execute();

    $this->assertTrue(CRM_Contact_BAO_ContactType::isActive('Individual'));
    $this->assertFalse(CRM_Contact_BAO_ContactType::isActive('Household'));
    $this->assertFalse(CRM_Contact_BAO_ContactType::isActive('Not a real contact type name'));

    $result = CRM_Contact_BAO_ContactType::basicTypePairs();
    $this->assertEquals(['Individual', 'Organization'], array_keys($result));

    $result = CRM_Contact_BAO_ContactType::basicTypePairs(TRUE);
    $this->assertEquals(['Individual', 'Household', 'Organization'], array_values($result));

    $result = CRM_Contact_BAO_ContactType::basicTypePairs(FALSE, 'id');
    $this->assertEquals([$this->getContactTypeID('Individual'), $this->getContactTypeID('Organization')], array_keys($result));
    $this->assertEquals(['Individual', 'Organization'], array_values($result));
  }

  /**
   * Get all expected types.
   *
   * @return array
   */
  public function getExpectedContactTypes(): array {
    return [
      'Individual' => [
        'id' => $this->getContactTypeID('Individual'),
        'name' => 'Individual',
        'label' => 'Individual',
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'description' => NULL,
        'parent_id' => NULL,
        'parent' => NULL,
        'parent_label' => NULL,
        'image_URL' => NULL,
        'icon' => 'fa-user',
      ],
      'Household' => [
        'id' => $this->getContactTypeID('Household'),
        'name' => 'Household',
        'label' => 'Household',
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'description' => NULL,
        'parent_id' => NULL,
        'parent' => NULL,
        'parent_label' => NULL,
        'image_URL' => NULL,
        'icon' => 'fa-home',
      ],
      'Organization' => [
        'id' => $this->getContactTypeID('Organization'),
        'name' => 'Organization',
        'label' => 'Organization',
        'is_active' => TRUE,
        'is_reserved' => TRUE,
        'description' => NULL,
        'parent_id' => NULL,
        'parent' => NULL,
        'parent_label' => NULL,
        'image_URL' => NULL,
        'icon' => 'fa-building',
      ],
      'Student' => [
        'id' => $this->getContactTypeID('Student'),
        'name' => 'Student',
        'label' => 'Student',
        'parent_id' => $this->getContactTypeID('Individual'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Individual',
        'parent_label' => 'Individual',
        'image_URL' => NULL,
        'icon' => 'fa-graduation-cap',
      ],
      'Parent' => [
        'id' => $this->getContactTypeID('Parent'),
        'name' => 'Parent',
        'label' => 'Parent',
        'parent_id' => $this->getContactTypeID('Individual'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Individual',
        'parent_label' => 'Individual',
        'image_URL' => NULL,
        'icon' => 'fa-user-circle-o',
      ],
      'Staff' => [
        'id' => $this->getContactTypeID('Staff'),
        'name' => 'Staff',
        'label' => 'Staff',
        'parent_id' => $this->getContactTypeID('Individual'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Individual',
        'parent_label' => 'Individual',
        'image_URL' => NULL,
        'icon' => 'fa-id-badge',
      ],
      'Team' => [
        'id' => $this->getContactTypeID('Team'),
        'name' => 'Team',
        'label' => 'Team',
        'parent_id' => $this->getContactTypeID('Organization'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Organization',
        'parent_label' => 'Organization',
        'image_URL' => NULL,
        'icon' => 'fa-users',
      ],
      'Sponsor' => [
        'id' => $this->getContactTypeID('Sponsor'),
        'name' => 'Sponsor',
        'label' => 'Sponsor',
        'parent_id' => $this->getContactTypeID('Organization'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Organization',
        'parent_label' => 'Organization',
        'image_URL' => NULL,
        'icon' => 'fa-leaf',
      ],
      'sub1_individual' => [
        'id' => $this->ids['ContactType'][0],
        'name' => 'sub1_individual',
        'label' => 'Sub1 Individual',
        'parent_id' => $this->getContactTypeID('Individual'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Individual',
        'parent_label' => 'Individual',
        'image_URL' => NULL,
        'icon' => 'fa-user',
      ],
      'sub2_individual' => [
        'id' => $this->ids['ContactType'][1],
        'name' => 'sub2_individual',
        'label' => 'Sub2 Individual',
        'parent_id' => $this->getContactTypeID('Individual'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Individual',
        'parent_label' => 'Individual',
        'image_URL' => NULL,
        'icon' => 'fa-user',
      ],
      'sub_organization' => [
        'id' => $this->ids['ContactType'][2],
        'name' => 'sub_organization',
        'label' => 'Sub Organization',
        'parent_id' => $this->getContactTypeID('Organization'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Organization',
        'parent_label' => 'Organization',
        'image_URL' => NULL,
        'icon' => 'fa-building',
      ],
      'sub_household' => [
        'id' => $this->ids['ContactType'][3],
        'name' => 'sub_household',
        'label' => 'Sub Household',
        'parent_id' => $this->getContactTypeID('Household'),
        'is_active' => TRUE,
        'is_reserved' => FALSE,
        'description' => NULL,
        'parent' => 'Household',
        'parent_label' => 'Household',
        'image_URL' => NULL,
        'icon' => 'fa-home',
      ],
    ];
  }

  /**
   * Get subtypes for all main types.
   *
   * @return array
   */
  public function getExpectedAllSubtypes(): array {
    return array_merge(
      $this->getExpectedContactSubTypes('Individual'),
      $this->getExpectedContactSubTypes('Household'),
      $this->getExpectedContactSubTypes('Organization')
    );
  }

  /**
   * Get the expected subtypes of the given contact type.
   *
   * @param string $parentType
   *
   * @return array
   */
  public function getExpectedContactSubTypes($parentType) {
    $expected = $this->getExpectedContactTypes();
    foreach ($expected as $index => $values) {
      if (($values['parent_label'] ?? '') !== $parentType) {
        unset($expected[$index]);
      }
    }
    return $expected;
  }

}
