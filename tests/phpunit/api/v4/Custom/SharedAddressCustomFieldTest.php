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

namespace api\v4\Custom;

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Address;
use Civi\Api4\CustomField;
use Civi\Test\FormWrapper;

/**
 * Custom data on an address shared from a contact the user cannot edit.
 *
 * @group headless
 */
class SharedAddressCustomFieldTest extends Api4TestBase {

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    // Each copy of a shared address duplicates the file.
    array_map('unlink', glob(\CRM_Core_Config::singleton()->customFileUploadDir . 'shared_extra_doc*'));
    parent::tearDown();
  }

  public function testSharedAddressSavesWithUnchangedCustomValues(): void {
    [, $sharedAddress] = $this->setupSharedAddressWithCustomData();

    Address::update()
      ->addWhere('id', '=', $sharedAddress)
      ->addValue('SharedExtra.Code', 'A-1')
      ->addValue('SharedExtra.Since', '2026-01-15')
      ->addValue('is_billing', TRUE)
      ->execute();

    $this->assertTrue(Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['is_billing']);
  }

  public function testSharedAddressSavesWithEquivalentCustomValues(): void {
    [, $sharedAddress] = $this->setupSharedAddressWithCustomData();

    Address::update()
      ->addWhere('id', '=', $sharedAddress)
      ->addValue('SharedExtra.Country', '')
      ->addValue('SharedExtra.Tags', ['c', 'a'])
      ->addValue('is_billing', TRUE)
      ->execute();

    $this->assertTrue(Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['is_billing']);
  }

  public function testSharedAddressSavesWithItsOwnCopyOfTheFile(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();
    $files = Address::get(FALSE)
      ->addSelect('SharedExtra.Doc')
      ->addWhere('id', 'IN', [$masterAddress, $sharedAddress])
      ->execute()->column('SharedExtra.Doc', 'id');
    $this->assertNotEquals($files[$masterAddress], $files[$sharedAddress]);

    Address::update()
      ->addWhere('id', '=', $sharedAddress)
      ->addValue('SharedExtra.Doc', $files[$sharedAddress])
      ->addValue('is_billing', TRUE)
      ->execute();

    $this->assertTrue(Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['is_billing']);
  }

  public function testAddressTakesCustomValuesOfNewlySharedMaster(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();
    $userContact = Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['contact_id'];
    $ownAddress = $this->createTestRecord('Address', [
      'contact_id' => $userContact,
      'location_type_id:name' => 'Work',
      'SharedExtra.Code' => 'OWN',
    ])['id'];

    Address::update(FALSE)
      ->addWhere('id', '=', $ownAddress)
      ->addValue('master_id', $masterAddress)
      ->execute();

    $address = Address::get(FALSE)
      ->addSelect('master_id', 'SharedExtra.Code')
      ->addWhere('id', '=', $ownAddress)
      ->execute()->single();
    $this->assertEquals($masterAddress, $address['master_id']);
    $this->assertEquals('A-1', $address['SharedExtra.Code']);
  }

  public function testAddressFormKeepsCustomValuesOfSharedAddress(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();
    $userContact = Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['contact_id'];
    $codeField = CustomField::get(FALSE)->addWhere('custom_group_id:name', '=', 'SharedExtra')->addWhere('name', '=', 'Code')->execute()->single()['id'];
    $homeLocationTypeID = \CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', 'Home');

    $form = new FormWrapper('CRM_Contact_Form_Inline_Address', [
      'oplock_ts' => \CRM_Core_DAO::singleValueQuery('SELECT modified_date FROM civicrm_contact WHERE id = %1', [1 => [$userContact, 'Integer']]),
      'address' => [
        1 => [
          'location_type_id' => $homeLocationTypeID,
          'is_primary' => 1,
          'use_shared_address' => 1,
          'master_id' => $masterAddress,
          "custom_{$codeField}_-1" => 'A-1',
        ],
      ],
    ], ['cid' => $userContact, 'locno' => 1, 'aid' => $sharedAddress]);
    $form->processForm();

    $this->assertEmpty($form->getValidationOutput());
    $codes = Address::get(FALSE)
      ->addSelect('SharedExtra.Code')
      ->addWhere('id', 'IN', [$masterAddress, $sharedAddress])
      ->execute()->column('SharedExtra.Code', 'id');
    $this->assertEquals([$masterAddress => 'A-1', $sharedAddress => 'A-1'], $codes);
  }

  public function testAddressDropsCustomValuesTheNewlySharedMasterLacks(): void {
    [, $sharedAddress] = $this->setupSharedAddressWithCustomData();
    $userContact = Address::get(FALSE)->addWhere('id', '=', $sharedAddress)->execute()->single()['contact_id'];
    $otherMaster = $this->createTestRecord('Address', [
      'contact_id' => $this->createTestRecord('Contact', ['contact_type' => 'Household', 'household_name' => 'Other Household'])['id'],
      'location_type_id:name' => 'Home',
      'SharedExtra.Tags' => ['b'],
    ])['id'];
    $ownAddress = $this->createTestRecord('Address', [
      'contact_id' => $userContact,
      'location_type_id:name' => 'Work',
      'SharedExtra.Code' => 'OWN',
      'SharedExtra.Since' => '2020-02-02',
    ])['id'];

    Address::update(FALSE)
      ->addWhere('id', '=', $ownAddress)
      ->addValue('master_id', $otherMaster)
      ->execute();

    $address = Address::get(FALSE)
      ->addSelect('SharedExtra.Code', 'SharedExtra.Since', 'SharedExtra.Tags')
      ->addWhere('id', '=', $ownAddress)
      ->execute()->single();
    $this->assertNull($address['SharedExtra.Code']);
    $this->assertNull($address['SharedExtra.Since']);
    $this->assertEquals(['b'], $address['SharedExtra.Tags']);
  }

  public function testClearedMasterValueReachesSharedAddress(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();

    Address::update(FALSE)
      ->addWhere('id', '=', $masterAddress)
      ->addValue('SharedExtra.Code', '')
      ->execute();

    $this->assertNull(Address::get(FALSE)->addSelect('SharedExtra.Code')->addWhere('id', '=', $sharedAddress)->execute()->single()['SharedExtra.Code']);
  }

  public function testSharedAddressRefusesChangedTextThatLooksNumeric(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();
    Address::update(FALSE)
      ->addWhere('id', '=', $masterAddress)
      ->addValue('SharedExtra.Code', '00123')
      ->execute();

    $this->expectException(UnauthorizedException::class);
    Address::update()
      ->addWhere('id', '=', $sharedAddress)
      ->addValue('SharedExtra.Code', '123')
      ->execute();
  }

  public function testSharedAddressRefusesChangedCustomValue(): void {
    [$masterAddress, $sharedAddress] = $this->setupSharedAddressWithCustomData();

    try {
      Address::update()
        ->addWhere('id', '=', $sharedAddress)
        ->addValue('SharedExtra.Code', 'B-2')
        ->execute();
      $this->fail('Expected UnauthorizedException');
    }
    catch (UnauthorizedException $e) {
      $this->assertEquals('Permission denied to modify shared upstream address.', $e->getMessage());
    }

    $codes = Address::get(FALSE)
      ->addSelect('SharedExtra.Code')
      ->addWhere('id', 'IN', [$masterAddress, $sharedAddress])
      ->execute()->column('SharedExtra.Code', 'id');
    $this->assertEquals([$masterAddress => 'A-1', $sharedAddress => 'A-1'], $codes);
  }

  /**
   * @return int[] [masterAddressId, sharedAddressId]
   */
  private function setupSharedAddressWithCustomData(): array {
    $customGroup = $this->createTestRecord('CustomGroup', [
      'name' => 'SharedExtra',
      'title' => 'Shared Extra',
      'extends' => 'Address',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Code',
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Since',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'Country',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Tags',
      'html_type' => 'CheckBox',
      'data_type' => 'String',
      'option_values' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Doc',
      'html_type' => 'File',
      'data_type' => 'File',
    ]);
    $file = $this->createTestRecord('File', ['file_name' => 'shared-extra-doc.txt', 'mime_type' => 'text/plain', 'content' => 'shared']);

    $userContact = $this->createLoggedInUser();
    $household = $this->createTestRecord('Contact', [
      'contact_type' => 'Household',
      'household_name' => 'Hidden Household',
    ]);
    $masterAddress = $this->createTestRecord('Address', [
      'contact_id' => $household['id'],
      'location_type_id:name' => 'Home',
      'street_address' => '500 Shared Lane',
      'SharedExtra.Code' => 'A-1',
      'SharedExtra.Since' => '2026-01-15 00:00:00',
      'SharedExtra.Tags' => ['a', 'c'],
      'SharedExtra.Doc' => $file['id'],
    ])['id'];
    $sharedAddress = $this->createTestRecord('Address', [
      'contact_id' => $userContact,
      'location_type_id:name' => 'Home',
      'master_id' => $masterAddress,
    ])['id'];

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
      'view my contact',
      'access all custom data',
    ];
    return [$masterAddress, $sharedAddress];
  }

}
