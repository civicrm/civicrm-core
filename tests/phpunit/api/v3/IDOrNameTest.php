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

/**
 * Test that APIv3 stores the same id whether it is given an id or a name.
 *
 * See CRMTraits_Api_IDOrNameTrait for the purpose and method.
 *
 * @group headless
 */
class api_v3_IDOrNameTest extends CiviUnitTestCase {

  use CRMTraits_Api_IDOrNameTrait;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  public static function tearDownAfterClass(): void {
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCase');
    parent::tearDownAfterClass();
  }

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function tearDown(): void {
    $this->deleteIDOrNameCustomGroup();
    parent::tearDown();
  }

  /**
   * @dataProvider getApi3IDOrNameFields
   *
   * @param string $entity
   * @param string $field
   * @param string|array $name
   * @param string $valuesMethod
   *   Method giving the record's other values.
   */
  public function testIDOrName(string $entity, string $field, $name, string $valuesMethod): void {
    // Called first as it creates the records some cases refer to by name.
    $this->$valuesMethod();
    $expected = $this->getIDForName($entity, $field, $name);
    foreach (['id' => $expected, 'name' => $name] as $givenAs => $value) {
      $values = $this->$valuesMethod();
      unset($values[$field]);
      $values[$field] = $value;
      $result = $this->callAPISuccess($entity, 'create', $values);
      // GroupContact and EntityTag don't return the id of the row they create.
      $id = $result['id'] ?? $this->callAPISuccessGetValue($entity, $values + ['return' => 'id']);
      $this->assertEquals($expected, $this->getStoredIDOrNameValue($entity, (int) $id, $field), "$entity.$field given as $givenAs");
    }
  }

}
