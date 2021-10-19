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
      'label' => 'sub1_individual',
      'name' => 'sub1_individual',
      'parent_id:name' => 'Individual',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'sub2_individual',
      'name' => 'sub2_individual',
      'parent_id:name' => 'Individual',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'sub_organization',
      'name' => 'sub_organization',
      'parent_id:name' => 'Organization',
      'is_active' => 1,
    ];

    $this->ids['ContactType'][] = ContactType::create()->setValues($params)->execute()->first()['id'];

    $params = [
      'label' => 'sub_household',
      'name' => 'sub_household',
      'parent_id:name' => 'Household',
      'is_active' => 1,
    ];
    $this->ids['ContactType'][] = (int) ContactType::create()->setValues($params)->execute()->first()['id'];
  }

  /**
   * Cleanup contact types.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    parent::tearDown();
    ContactType::delete()->addWhere('id', 'IN', $this->ids['ContactType'])->execute();
  }

  /**
   * Test contactTypes() and subTypes() methods return correct contact types.
   */
  public function testGetMethods() {
    $result = CRM_Contact_BAO_ContactType::contactTypes(TRUE);
    $this->assertEquals(array_keys($this->getExpectedContactTypes()), $result);

    // check for type:Individual
    $result = CRM_Contact_BAO_ContactType::subTypes('Individual');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Individual')), $result);
    $this->assertEquals($this->getExpectedContactSubTypes('Individual'), CRM_Contact_BAO_ContactType::subTypeInfo('Individual'));

    // check for type:Organization
    $result = CRM_Contact_BAO_ContactType::subTypes('Organization');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Organization')), $result);
    $this->assertEquals($this->getExpectedContactSubTypes('Organization'), CRM_Contact_BAO_ContactType::subTypeInfo('Organization'));

    // check for type:Household
    $result = CRM_Contact_BAO_ContactType::subTypes('Household');
    $this->assertEquals(array_keys($this->getExpectedContactSubTypes('Household')), $result);
    $this->assertEquals($this->getExpectedContactSubTypes('Household'), CRM_Contact_BAO_ContactType::subTypeInfo('Household'));

    // check for all contact types
    $result = CRM_Contact_BAO_ContactType::subTypes();
    $subtypes = array_keys($this->getExpectedAllSubtypes());
    $this->assertEquals(sort($subtypes), sort($result));
    $this->assertEquals($this->getExpectedAllSubtypes(), CRM_Contact_BAO_ContactType::subTypeInfo());

  }

  /**
   * Test subTypes() methods with invalid data
   */
  public function testGetMethodsInvalid() {

    $params = 'invalid';
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE);

    $params = ['invalid'];
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE);
  }

  /**
   * Test function for getting contact types.
   *
   * @throws \API_Exception
   */
  public function testContactTypeInfo() {
    $blahType = ['is_active' => 0, 'name' => 'blah', 'label' => 'blah blah', 'parent_id:name' => 'Individual'];
    $createdType = ContactType::create()->setValues($blahType)->execute()->first();
    $activeTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    $expected = $this->getExpectedContactTypes();
    $this->assertEquals($expected, $activeTypes);
    $allTypes = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    $expected['blah'] = [
      'is_active' => FALSE,
      'name' => 'blah',
      'label' => 'blah blah',
      'id' => $createdType['id'],
      'parent_id' => 1,
      'is_reserved' => FALSE,
      'parent' => 'Individual',
      'parent_label' => 'Individual',
      'description' => '',
      'image_URL' => '',
    ];
    $this->assertEquals($expected, $allTypes);
  }

  /**
   * Get all expected types.
   *
   * @return array
   */
  public function getExpectedContactTypes() {
    return [
      'Individual' =>
        [
          'id' => '1',
          'name' => 'Individual',
          'label' => 'Individual',
          'is_active' => TRUE,
          'is_reserved' => TRUE,
          'description' => '',
          'parent_id' => NULL,
          'parent' => NULL,
          'parent_label' => NULL,
          'image_URL' => '',
        ],
      'Household' =>
        [
          'id' => '2',
          'name' => 'Household',
          'label' => 'Household',
          'is_active' => TRUE,
          'is_reserved' => TRUE,
          'description' => '',
          'parent_id' => NULL,
          'parent' => NULL,
          'parent_label' => NULL,
          'image_URL' => '',
        ],
      'Organization' =>
        [
          'id' => '3',
          'name' => 'Organization',
          'label' => 'Organization',
          'is_active' => TRUE,
          'is_reserved' => TRUE,
          'description' => '',
          'parent_id' => NULL,
          'parent' => NULL,
          'parent_label' => NULL,
          'image_URL' => '',
        ],
      'Student' =>
        [
          'id' => 4,
          'name' => 'Student',
          'label' => 'Student',
          'parent_id' => 1,
          'is_active' => '1',
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Individual',
          'parent_label' => 'Individual',
          'image_URL' => '',
        ],
      'Parent' =>
        [
          'id' => 5,
          'name' => 'Parent',
          'label' => 'Parent',
          'parent_id' => 1,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Individual',
          'parent_label' => 'Individual',
          'image_URL' => '',
        ],
      'Staff' =>
        [
          'id' => 6,
          'name' => 'Staff',
          'label' => 'Staff',
          'parent_id' => 1,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Individual',
          'parent_label' => 'Individual',
          'image_URL' => '',
        ],
      'Team' =>
        [
          'id' => 7,
          'name' => 'Team',
          'label' => 'Team',
          'parent_id' => 3,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Organization',
          'parent_label' => 'Organization',
          'image_URL' => '',
        ],
      'Sponsor' =>
        [
          'id' => 8,
          'name' => 'Sponsor',
          'label' => 'Sponsor',
          'parent_id' => 3,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Organization',
          'parent_label' => 'Organization',
          'image_URL' => '',
        ],
      'sub1_individual' =>
        [
          'id' => $this->ids['ContactType'][0],
          'name' => 'sub1_individual',
          'label' => 'sub1_individual',
          'parent_id' => 1,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Individual',
          'parent_label' => 'Individual',
          'image_URL' => '',
        ],
      'sub2_individual' =>
        [
          'id' => $this->ids['ContactType'][1],
          'name' => 'sub2_individual',
          'label' => 'sub2_individual',
          'parent_id' => 1,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Individual',
          'parent_label' => 'Individual',
          'image_URL' => '',
        ],
      'sub_organization' =>
        [
          'id' => $this->ids['ContactType'][2],
          'name' => 'sub_organization',
          'label' => 'sub_organization',
          'parent_id' => 3,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Organization',
          'parent_label' => 'Organization',
          'image_URL' => '',
        ],
      'sub_household' =>
        [
          'id' => $this->ids['ContactType'][3],
          'name' => 'sub_household',
          'label' => 'sub_household',
          'parent_id' => 2,
          'is_active' => TRUE,
          'is_reserved' => FALSE,
          'description' => '',
          'parent' => 'Household',
          'parent_label' => 'Household',
          'image_URL' => '',
        ],
    ];
  }

  /**
   * Get subtypes for all main types.
   *
   * @return array
   */
  public function getExpectedAllSubtypes() {
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

  /**
   * Test add() methods with valid data
   * success expected
   */
  public function testAdd() {

    $params = [
      'label' => 'indiviSubType',
      'name' => 'indiviSubType',
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label']);
    $this->assertEquals($result->name, $params['name']);
    $this->assertEquals($result->parent_id, $params['parent_id']);
    $this->assertEquals($result->is_active, $params['is_active']);
    CRM_Contact_BAO_ContactType::del($result->id);

    $params = [
      'label' => 'householdSubType',
      'name' => 'householdSubType',
      'parent_id' => 2,
      'is_active' => 0,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label']);
    $this->assertEquals($result->name, $params['name']);
    $this->assertEquals($result->parent_id, $params['parent_id']);
    $this->assertEquals($result->is_active, $params['is_active']);
    CRM_Contact_BAO_ContactType::del($result->id);
  }

  /**
   * Test add() with invalid data
   */
  public function testAddInvalid1() {

    // parent id does not exist in db
    $params = [
      'label' => 'subType',
      'name' => 'subType',
      // non existent
      'parent_id' => 100,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL);
  }

  public function testAddInvalid2() {

    // params does not have name and label keys
    $params = [
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL);
  }

  public function testAddInvalid3() {

    // params does not have parent_id
    $params = [
      'label' => 'subType',
      'name' => 'subType',
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL);
  }

  /**
   * Test del() with valid data.
   */
  public function testDel() {

    $params = [
      'label' => 'indiviSubType',
      'name' => 'indiviSubType',
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $subtype = CRM_Contact_BAO_ContactType::add($params);
    $result = CRM_Contact_BAO_ContactType::subTypes();
    $this->assertEquals(TRUE, in_array($subtype->name, $result, TRUE));
    $this->callAPISuccess('ContactType', 'delete', ['id' => $subtype->id]);

    $result = CRM_Contact_BAO_ContactType::subTypes();
    $this->assertEquals(FALSE, in_array($subtype->name, $result, TRUE));
  }

  /**
   * Test del() with invalid data
   */
  public function testDelInvalid() {
    $del = CRM_Contact_BAO_ContactType::del(NULL);
    $this->assertEquals($del, FALSE);
  }

}
