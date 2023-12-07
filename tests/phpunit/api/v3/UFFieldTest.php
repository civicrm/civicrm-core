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
 * Test class for UFField API
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_UFFieldTest extends CiviUnitTestCase {

  /**
   * Set up for test.
   *
   * @throws \Exception
   */
  public function tearDown(): void {
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_field',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
    parent::tearDown();
  }

  /**
   * Create a field with 'weight=1' and then a second with 'weight=1'.
   *
   * The second field winds up with weight=1, and the first field gets bumped to 'weight=2'.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFFieldWithDefaultAutoWeight(int $version): void {
    $this->_apiversion = $version;
    $params = [
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
      'uf_group_id' => $this->createTestEntity('UFGroup', [
        'group_type' => 'Contact',
        'title' => 'Test Profile',
      ])['id'],
    ];
    $ufField1 = $this->callAPISuccess('uf_field', 'create', $params);
    $this->assertEquals(1, $ufField1['values'][$ufField1['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField1['id'], 'Int'],
    ]);

    // needs to be a different field
    $params['location_type_id'] = 2;
    $ufField2 = $this->callAPISuccess('UFField', 'create', $params);
    $this->assertEquals(1, $ufField2['values'][$ufField2['id']]['weight']);
    $this->assertDBQuery(1, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField2['id'], 'Int'],
    ]);
    $this->assertDBQuery(2, 'SELECT weight FROM civicrm_uf_field WHERE id = %1', [
      1 => [$ufField1['id'], 'Int'],
    ]);
  }

  /**
   * Create / updating field.
   */
  public function testReplaceUFFields(): void {
    $baseFields = [];
    $baseFields[] = [
      'field_name' => 'first_name',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 3,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    $baseFields[] = [
      'field_name' => 'country',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 2,
      'label' => 'Test Country',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
    ];
    $baseFields[] = [
      'field_name' => 'phone',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => 'Test Phone',
      'is_searchable' => 1,
      'is_active' => 1,
      'location_type_id' => 1,
      'phone_type_id' => 1,
    ];

    $params = [
      'uf_group_id' => $this->createTestEntity('UFGroup', [
        'group_type' => 'Contact',
        'title' => 'Test Profile',
      ])['id'],
      'option.autoweight' => FALSE,
      'values' => $baseFields,
      'check_permissions' => TRUE,
    ];

    $result = $this->callAPISuccess('UFField', 'replace', $params);
    $inputsByName = CRM_Utils_Array::index(['field_name'], $params['values']);
    $this->assertSameSize($params['values'], $result['values']);
    foreach ($result['values'] as $outUfField) {
      $this->assertIsString($outUfField['field_name']);
      $inUfField = $inputsByName[$outUfField['field_name']];
      foreach ($inUfField as $key => $inValue) {
        $this->assertEquals($inValue, $outUfField[$key],
          sprintf('field_name=[%s] key=[%s] expected=[%s] actual=[%s]',
            $outUfField['field_name'],
            $key,
            $inValue,
            $outUfField[$key]
          )
        );
      }
    }
  }

  /**
   * Check Profile API permission without ACL.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testProfilesWithoutACL(int $version): void {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $baseFields[] = [
      'field_name' => 'first_name',
      'field_type' => 'Contact',
      'visibility' => 'Public Pages and Listings',
      'weight' => 3,
      'label' => 'Test First Name',
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $params = [
      'uf_group_id' => $this->createTestEntity('UFGroup', [
        'group_type' => 'Contact',
        'title' => 'Test Profile',
      ])['id'],
      'option.autoweight' => FALSE,
      'values' => $baseFields,
      'check_permissions' => TRUE,
    ];
    $this->callAPIFailure('UFField', 'replace', $params);
  }

  /**
   * Check Profile ACL for API permission.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testACLPermissionForProfiles(): void {
    $this->createLoggedInUser();
    $this->groupCreate([
      'title' => 'Edit Profiles',
      'is_active' => 1,
      'name' => 'edit-profiles',
    ], 'permissioned_group');
    $this->setupACL(TRUE);
    $this->testReplaceUFFields();
  }

}
