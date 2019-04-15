<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_BAO_AddressTest
 * @group headless
 */
class CRM_Core_BAO_AddressTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->quickCleanup(array('civicrm_contact', 'civicrm_address'));
  }

  /**
   * Create() method (create and update modes)
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = array();
    $params['address']['1'] = array(
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'Attn: Accounting',
      'supplemental_address_2' => 'Powai',
      'supplemental_address_3' => 'Somewhere',
      'city' => 'Athens',
      'postal_code' => '01903',
      'state_province_id' => '1000',
      'country_id' => '1228',
      'geo_code_1' => '18.219023',
      'geo_code_2' => '-105.00973',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
    );

    $params['contact_id'] = $contactId;

    $fixAddress = TRUE;

    CRM_Core_BAO_Address::create($params, $fixAddress, $entity = NULL);
    $addressId = $this->assertDBNotNull('CRM_Core_DAO_Address', 'Oberoi Garden', 'id', 'street_address',
      'Database check for created address.'
    );

    // Now call add() to modify an existing  address

    $params = array();
    $params['address']['1'] = array(
      'id' => $addressId,
      'street_address' => '120 Terminal Road',
      'supplemental_address_1' => 'A-wing:3037',
      'supplemental_address_2' => 'Bandra',
      'supplemental_address_3' => 'Somewhere',
      'city' => 'Athens',
      'postal_code' => '01903',
      'state_province_id' => '1000',
      'country_id' => '1228',
      'geo_code_1' => '18.219023',
      'geo_code_2' => '-105.00973',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
    );
    $params['contact_id'] = $contactId;

    $block = CRM_Core_BAO_Address::create($params, $fixAddress, $entity = NULL);

    $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for updated address by contactId.'
    );
    $this->assertDBNotNull('CRM_Core_DAO_Address', '120 Terminal Road', 'id', 'street_address',
      'Database check for updated address by street_name.'
    );
    $this->contactDelete($contactId);
  }

  /**
   * Add() method ( )
   */
  public function testAdd() {
    $contactId = $this->individualCreate();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
      'supplemental_address_3' => '',
      'city' => 'El Paso',
      'postal_code' => '88575',
      'postal_code_suffix' => '',
      'state_province_id' => '1001',
      'country_id' => '1228',
      'geo_code_1' => '31.694842',
      'geo_code_2' => '-106.29998',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
      'contact_id' => $contactId,
    );

    $addAddress = CRM_Core_BAO_Address::add($fixParams, $fixAddress = TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );

    $this->assertEquals($addAddress->street_address, 'E 906N Pine Pl W', 'In line' . __LINE__);
    $this->assertEquals($addAddress->supplemental_address_1, 'Editorial Dept', 'In line' . __LINE__);
    $this->assertEquals($addAddress->city, 'El Paso', 'In line' . __LINE__);
    $this->assertEquals($addAddress->postal_code, '88575', 'In line' . __LINE__);
    $this->assertEquals($addAddress->geo_code_1, '31.694842', 'In line' . __LINE__);
    $this->assertEquals($addAddress->geo_code_2, '-106.29998', 'In line' . __LINE__);
    $this->assertEquals($addAddress->country_id, '1228', 'In line' . __LINE__);
    $this->contactDelete($contactId);
  }

  /**
   * AllAddress() method ( )
   */
  public function testallAddress() {
    $contactId = $this->individualCreate();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
      'supplemental_address_3' => '',
      'city' => 'El Paso',
      'postal_code' => '88575',
      'postal_code_suffix' => '',
      'state_province_id' => '1001',
      'country_id' => '1228',
      'geo_code_1' => '31.694842',
      'geo_code_2' => '-106.29998',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Address::add($fixParams, $fixAddress = TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );
    $fixParams = array(
      'street_address' => 'SW 719B Beech Dr NW',
      'supplemental_address_1' => 'C/o OPDC',
      'supplemental_address_2' => '',
      'supplemental_address_3' => '',
      'city' => 'Neillsville',
      'postal_code' => '54456',
      'postal_code_suffix' => '',
      'state_province_id' => '1001',
      'country_id' => '1228',
      'geo_code_1' => '44.553719',
      'geo_code_2' => '-90.61457',
      'location_type_id' => '2',
      'is_primary' => '',
      'is_billing' => '1',
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Address::add($fixParams, $fixAddress = TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );

    $allAddress = CRM_Core_BAO_Address::allAddress($contactId);

    $this->assertEquals(count($allAddress), 2, 'Checking number of returned addresses.');

    $this->contactDelete($contactId);
  }

  /**
   * AllAddress() method ( ) with null value
   */
  public function testnullallAddress() {
    $contactId = $this->individualCreate();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
      'supplemental_address_3' => '',
      'city' => 'El Paso',
      'postal_code' => '88575',
      'postal_code_suffix' => '',
      'state_province_id' => '1001',
      'country_id' => '1228',
      'geo_code_1' => '31.694842',
      'geo_code_2' => '-106.29998',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Address::add($fixParams, $fixAddress = TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );

    $contact_Id = NULL;

    $allAddress = CRM_Core_BAO_Address::allAddress($contact_Id);

    $this->assertEquals($allAddress, NULL, 'Checking null for returned addresses.');

    $this->contactDelete($contactId);
  }

  /**
   * GetValues() method (get Address fields)
   */
  public function testGetValues() {
    $contactId = $this->individualCreate();

    $params = array();
    $params['address']['1'] = array(
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'Attn: Accounting',
      'supplemental_address_2' => 'Powai',
      'supplemental_address_3' => 'Somewhere',
      'city' => 'Athens',
      'postal_code' => '01903',
      'state_province_id' => '1000',
      'country_id' => '1228',
      'geo_code_1' => '18.219023',
      'geo_code_2' => '-105.00973',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
    );

    $params['contact_id'] = $contactId;

    $fixAddress = TRUE;

    CRM_Core_BAO_Address::create($params, $fixAddress, $entity = NULL);

    $addressId = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created address.'
    );

    $entityBlock = array('contact_id' => $contactId);
    $address = CRM_Core_BAO_Address::getValues($entityBlock);
    $this->assertEquals($address[1]['id'], $addressId);
    $this->assertEquals($address[1]['contact_id'], $contactId);
    $this->assertEquals($address[1]['street_address'], 'Oberoi Garden');
    $this->contactDelete($contactId);
  }

  public function setStreetAddressParsing($status) {
    $address_options = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options',
      TRUE, NULL, TRUE
    );
    if ($status) {
      $value = 1;
    }
    else {
      $value = 0;
    }
    $address_options['street_address_parsing'] = $value;
    CRM_Core_BAO_Setting::setValueOption(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options',
      $address_options
    );
  }

  /**
   * ParseStreetAddress if enabled, otherwise, don't.
   */
  public function testParseStreetAddressIfEnabled() {
    // Turn off address standardization. Parsing should work without it.
    Civi::settings()->set('address_standardization_provider', NULL);

    // Ensure street parsing happens if enabled.
    $this->setStreetAddressParsing(TRUE);

    $contactId = $this->individualCreate();
    $street_address = "54 Excelsior Ave.";
    $params = array(
      'contact_id' => $contactId,
      'street_address' => $street_address,
      'location_type_id' => 1,
    );

    $result = civicrm_api3('Address', 'create', $params);
    $value = array_pop($result['values']);
    $street_number = CRM_Utils_Array::value('street_number', $value);
    $this->assertEquals($street_number, '54');

    // Ensure street parsing does not happen if disabled.
    $this->setStreetAddressParsing(FALSE);
    $result = civicrm_api3('Address', 'create', $params);
    $value = array_pop($result['values']);
    $street_number = CRM_Utils_Array::value('street_number', $value);
    $this->assertEmpty($street_number);

  }

  /**
   * ParseStreetAddress() method (get street address parsed)
   */
  public function testParseStreetAddress() {

    // valid Street address to be parsed ( without locale )
    $street_address = "54A Excelsior Ave. Apt 1C";
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address);
    $this->assertEquals($parsedStreetAddress['street_name'], 'Excelsior Ave.');
    $this->assertEquals($parsedStreetAddress['street_unit'], 'Apt 1C');
    $this->assertEquals($parsedStreetAddress['street_number'], '54');
    $this->assertEquals($parsedStreetAddress['street_number_suffix'], 'A');

    // Out-of-range street number to be parsed.
    $street_address = "505050505050 Main St";
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address);
    $this->assertEquals($parsedStreetAddress['street_name'], '');
    $this->assertEquals($parsedStreetAddress['street_unit'], '');
    $this->assertEquals($parsedStreetAddress['street_number'], '');
    $this->assertEquals($parsedStreetAddress['street_number_suffix'], '');

    // valid Street address to be parsed ( $locale = 'en_US' )
    $street_address = "54A Excelsior Ave. Apt 1C";
    $locale = 'en_US';
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address, $locale);
    $this->assertEquals($parsedStreetAddress['street_name'], 'Excelsior Ave.');
    $this->assertEquals($parsedStreetAddress['street_unit'], 'Apt 1C');
    $this->assertEquals($parsedStreetAddress['street_number'], '54');
    $this->assertEquals($parsedStreetAddress['street_number_suffix'], 'A');

    // invalid Street address ( $locale = 'en_US' )
    $street_address = "West St. Apt 1";
    $locale = 'en_US';
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address, $locale);
    $this->assertEquals($parsedStreetAddress['street_name'], 'West St.');
    $this->assertEquals($parsedStreetAddress['street_unit'], 'Apt 1');
    $this->assertNotContains('street_number', $parsedStreetAddress);
    $this->assertNotContains('street_number_suffix', $parsedStreetAddress);

    // valid Street address to be parsed ( $locale = 'fr_CA' )
    $street_address = "2-123CA Main St";
    $locale = 'fr_CA';
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address, $locale);
    $this->assertEquals($parsedStreetAddress['street_name'], 'Main St');
    $this->assertEquals($parsedStreetAddress['street_unit'], '2');
    $this->assertEquals($parsedStreetAddress['street_number'], '123');
    $this->assertEquals($parsedStreetAddress['street_number_suffix'], 'CA');

    // invalid Street address ( $locale = 'fr_CA' )
    $street_address = "123 Main St";
    $locale = 'fr_CA';
    $parsedStreetAddress = CRM_Core_BAO_Address::parseStreetAddress($street_address, $locale);
    $this->assertEquals($parsedStreetAddress['street_name'], 'Main St');
    $this->assertEquals($parsedStreetAddress['street_number'], '123');
    $this->assertNotContains('street_unit', $parsedStreetAddress);
    $this->assertNotContains('street_number_suffix', $parsedStreetAddress);
  }

  /**
   * @dataProvider supportedAddressParsingLocales
   */
  public function testIsSupportedByAddressParsingReturnTrueForSupportedLocales($locale) {
    $isSupported = CRM_Core_BAO_Address::isSupportedParsingLocale($locale);
    $this->assertTrue($isSupported);
  }

  /**
   * @dataProvider supportedAddressParsingLocales
   */
  public function testIsSupportedByAddressParsingReturnTrueForSupportedDefaultLocales($locale) {
    CRM_Core_Config::singleton()->lcMessages = $locale;
    $isSupported = CRM_Core_BAO_Address::isSupportedParsingLocale();
    $this->assertTrue($isSupported);

  }

  public function supportedAddressParsingLocales() {
    return array(
      array('en_US'),
      array('en_CA'),
      array('fr_CA'),
    );
  }

  /**
   * @dataProvider sampleOFUnsupportedAddressParsingLocales
   */
  public function testIsSupportedByAddressParsingReturnFalseForUnSupportedLocales($locale) {
    $isNotSupported = CRM_Core_BAO_Address::isSupportedParsingLocale($locale);
    $this->assertFalse($isNotSupported);
  }

  /**
   * @dataProvider sampleOFUnsupportedAddressParsingLocales
   */
  public function testIsSupportedByAddressParsingReturnFalseForUnSupportedDefaultLocales($locale) {
    CRM_Core_Config::singleton()->lcMessages = $locale;
    $isNotSupported = CRM_Core_BAO_Address::isSupportedParsingLocale();
    $this->assertFalse($isNotSupported);
  }

  public function sampleOFUnsupportedAddressParsingLocales() {
    return array(
      array('en_GB'),
      array('af_ZA'),
      array('da_DK'),
    );
  }

  /**
   * CRM-21214 - Ensure all child addresses are updated correctly - 1.
   * 1. First, create three contacts: A, B, and C
   * 2. Create an address for contact A
   * 3. Use contact A's address for contact B
   * 4. Use contact B's address for contact C
   * 5. Change contact A's address
   * Address of Contact C should reflect contact A's address change
   * Also, Contact C's address' master_id should be Contact A's address id.
   */
  public function testSharedAddressChaining1() {
    $contactIdA = $this->individualCreate(array(), 0);
    $contactIdB = $this->individualCreate(array(), 1);
    $contactIdC = $this->individualCreate(array(), 2);

    $addressParamsA = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    );
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $addressParamsB = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdB,
    );
    $addAddressB = CRM_Core_BAO_Address::add($addressParamsB, FALSE);

    $addressParamsC = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressB->id,
      'contact_id' => $contactIdC,
    );
    $addAddressC = CRM_Core_BAO_Address::add($addressParamsC, FALSE);

    $updatedAddressParamsA = array(
      'id' => $addAddressA->id,
      'street_address' => '1313 New Address Lane',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    );
    $updatedAddressA = CRM_Core_BAO_Address::add($updatedAddressParamsA, FALSE);

    // CRM-21214 - Has Address C been updated with Address A's new values?
    $newAddressC = new CRM_Core_DAO_Address();
    $newAddressC->id = $addAddressC->id;
    $newAddressC->find(TRUE);
    $newAddressC->fetch(TRUE);

    $this->assertEquals($updatedAddressA->street_address, $newAddressC->street_address);
    $this->assertEquals($updatedAddressA->id, $newAddressC->master_id);
  }

  /**
   * CRM-21214 - Ensure all child addresses are updated correctly - 2.
   * 1. First, create three contacts: A, B, and C
   * 2. Create an address for contact A and B
   * 3. Use contact A's address for contact C
   * 4. Use contact B's address for contact A
   * 5. Change contact B's address
   * Address of Contact C should reflect contact B's address change
   * Also, Contact C's address' master_id should be Contact B's address id.
   */
  public function testSharedAddressChaining2() {
    $contactIdA = $this->individualCreate(array(), 0);
    $contactIdB = $this->individualCreate(array(), 1);
    $contactIdC = $this->individualCreate(array(), 2);

    $addressParamsA = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    );
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $addressParamsB = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdB,
    );
    $addAddressB = CRM_Core_BAO_Address::add($addressParamsB, FALSE);

    $addressParamsC = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdC,
    );
    $addAddressC = CRM_Core_BAO_Address::add($addressParamsC, FALSE);

    $updatedAddressParamsA = array(
      'id' => $addAddressA->id,
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressB->id,
      'contact_id' => $contactIdA,
    );
    $updatedAddressA = CRM_Core_BAO_Address::add($updatedAddressParamsA, FALSE);

    $updatedAddressParamsB = array(
      'id' => $addAddressB->id,
      'street_address' => '1313 New Address Lane',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdB,
    );
    $updatedAddressB = CRM_Core_BAO_Address::add($updatedAddressParamsB, FALSE);

    // CRM-21214 - Has Address C been updated with Address B's new values?
    $newAddressC = new CRM_Core_DAO_Address();
    $newAddressC->id = $addAddressC->id;
    $newAddressC->find(TRUE);
    $newAddressC->fetch(TRUE);

    $this->assertEquals($updatedAddressB->street_address, $newAddressC->street_address);
    $this->assertEquals($updatedAddressB->id, $newAddressC->master_id);
  }

  /**
   * CRM-21214 - Ensure all child addresses are updated correctly - 3.
   * 1. First, create a contact: A
   * 2. Create an address for contact A
   * 3. Use contact A's address for contact A's address
   * An error should be given, and master_id should remain the same.
   */
  public function testSharedAddressChaining3() {
    $contactIdA = $this->individualCreate(array(), 0);

    $addressParamsA = array(
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    );
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $updatedAddressParamsA = array(
      'id' => $addAddressA->id,
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdA,
    );
    $updatedAddressA = CRM_Core_BAO_Address::add($updatedAddressParamsA, FALSE);

    // CRM-21214 - AdressA shouldn't be master of itself.
    $this->assertEmpty($updatedAddressA->master_id);
  }

}
