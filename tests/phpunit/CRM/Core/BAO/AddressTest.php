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
 * Class CRM_Core_BAO_AddressTest
 * @group headless
 */
class CRM_Core_BAO_AddressTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function setUp(): void {
    parent::setUp();

    $this->quickCleanup(['civicrm_contact', 'civicrm_address']);
  }

  /**
   * Create() method (create and update modes)
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = [];
    $params['address']['1'] = [
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
    ];

    $params['contact_id'] = $contactId;

    $fixAddress = TRUE;

    CRM_Core_BAO_Address::legacyCreate($params, $fixAddress);
    $addressId = $this->assertDBNotNull('CRM_Core_DAO_Address', 'Oberoi Garden', 'id', 'street_address',
      'Database check for created address.'
    );

    // Now call add() to modify an existing  address

    $params = [];
    $params['address']['1'] = [
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
    ];
    $params['contact_id'] = $contactId;

    $block = CRM_Core_BAO_Address::legacyCreate($params, $fixAddress);

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

    $fixParams = [
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
    ];

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

    $fixParams = [
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
    ];

    CRM_Core_BAO_Address::add($fixParams, $fixAddress = TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );
    $fixParams = [
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
    ];

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

    $fixParams = [
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
    ];

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

    $params = [];
    $params['address']['1'] = [
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
    ];

    $params['contact_id'] = $contactId;

    $fixAddress = TRUE;

    CRM_Core_BAO_Address::legacyCreate($params, $fixAddress);

    $addressId = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created address.'
    );

    $entityBlock = ['contact_id' => $contactId];
    $address = CRM_Core_BAO_Address::getValues($entityBlock);
    $this->assertEquals($address[1]['id'], $addressId);
    $this->assertEquals($address[1]['contact_id'], $contactId);
    $this->assertEquals($address[1]['state_province_abbreviation'], 'AL');
    $this->assertEquals($address[1]['state_province'], 'Alabama');
    $this->assertEquals($address[1]['country'], 'United States');
    $this->assertEquals($address[1]['street_address'], 'Oberoi Garden');
    $this->contactDelete($contactId);
  }

  /**
   * Enable street address parsing.
   *
   * @param string $status
   *
   * @throws \CRM_Core_Exception
   */
  public function setStreetAddressParsing($status) {
    $options = $this->callAPISuccess('Setting', 'getoptions', ['field' => 'address_options'])['values'];
    $address_options = reset($this->callAPISuccess('Setting', 'get', ['return' => 'address_options'])['values'])['address_options'];
    $parsingOption = array_search('Street Address Parsing', $options, TRUE);
    $optionKey = array_search($parsingOption, $address_options, FALSE);
    if ($status && !$optionKey) {
      $address_options[] = $parsingOption;
    }
    if (!$status && $optionKey) {
      unset($address_options[$optionKey]);
    }
    $this->callAPISuccess('Setting', 'create', ['address_options' => $address_options]);
  }

  /**
   * ParseStreetAddress if enabled, otherwise, don't.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testParseStreetAddressIfEnabled() {
    // Turn off address standardization. Parsing should work without it.
    Civi::settings()->set('address_standardization_provider', NULL);

    // Ensure street parsing happens if enabled.
    $this->setStreetAddressParsing(TRUE);

    $contactId = $this->individualCreate();
    $street_address = '54 Excelsior Ave.';
    $params = [
      'contact_id' => $contactId,
      'street_address' => $street_address,
      'location_type_id' => 1,
    ];

    $result = civicrm_api3('Address', 'create', $params);
    $value = array_pop($result['values']);
    $street_number = $value['street_number'] ?? NULL;
    $this->assertEquals($street_number, '54');

    // Ensure street parsing does not happen if disabled.
    $this->setStreetAddressParsing(FALSE);
    $result = civicrm_api3('Address', 'create', $params);
    $value = array_pop($result['values']);
    $street_number = $value['street_number'] ?? NULL;
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
    $street_address = '505050505050 Main St';
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
    return [
      ['en_US'],
      ['en_CA'],
      ['fr_CA'],
    ];
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
    return [
      ['en_GB'],
      ['af_ZA'],
      ['da_DK'],
    ];
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
    $contactIdA = $this->individualCreate([], 0);
    $contactIdB = $this->individualCreate([], 1);
    $contactIdC = $this->individualCreate([], 2);

    $addressParamsA = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    ];
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $addressParamsB = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdB,
    ];
    $addAddressB = CRM_Core_BAO_Address::add($addressParamsB, FALSE);

    $addressParamsC = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressB->id,
      'contact_id' => $contactIdC,
    ];
    $addAddressC = CRM_Core_BAO_Address::add($addressParamsC, FALSE);

    $updatedAddressParamsA = [
      'id' => $addAddressA->id,
      'street_address' => '1313 New Address Lane',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    ];
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
    $contactIdA = $this->individualCreate([], 0);
    $contactIdB = $this->individualCreate([], 1);
    $contactIdC = $this->individualCreate([], 2);

    $addressParamsA = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    ];
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $addressParamsB = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdB,
    ];
    $addAddressB = CRM_Core_BAO_Address::add($addressParamsB, FALSE);

    $addressParamsC = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdC,
    ];
    $addAddressC = CRM_Core_BAO_Address::add($addressParamsC, FALSE);

    $updatedAddressParamsA = [
      'id' => $addAddressA->id,
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressB->id,
      'contact_id' => $contactIdA,
    ];
    $updatedAddressA = CRM_Core_BAO_Address::add($updatedAddressParamsA, FALSE);

    $updatedAddressParamsB = [
      'id' => $addAddressB->id,
      'street_address' => '1313 New Address Lane',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdB,
    ];
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
    $contactIdA = $this->individualCreate([], 0);

    $addressParamsA = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
    ];
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $updatedAddressParamsA = [
      'id' => $addAddressA->id,
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdA,
    ];
    $updatedAddressA = CRM_Core_BAO_Address::add($updatedAddressParamsA, FALSE);

    // CRM-21214 - AdressA shouldn't be master of itself.
    $this->assertEmpty($updatedAddressA->master_id);
  }

  /**
   * dev/core#1670 - Ensure that the custom fields on adresses are copied
   * to inherited address
   * 1. test the creation of the shared address with custom field
   * 2. test the update of the custom field in the master
   */
  public function testSharedAddressCustomField() {

    $this->createCustomGroupWithFieldOfType(['extends' => 'Address'], 'text');
    $customField = $this->getCustomFieldName('text');

    $contactIdA = $this->individualCreate([], 0);
    $contactIdB = $this->individualCreate([], 1);

    $addressParamsA = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
      $customField => 'this is a custom text field',
    ];

    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    // without having the custom field, we should still copy the values from master
    $addressParamsB = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'master_id' => $addAddressA->id,
      'contact_id' => $contactIdB,
    ];
    $addAddressB = CRM_Core_BAO_Address::add($addressParamsB, FALSE);

    // 1. check if the custom fields values have been copied from master to shared address
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $addAddressB->id, 'return' => $this->getCustomFieldName('text')]);
    $this->assertEquals($addressParamsA[$customField], $address[$customField]);

    // 2. now, we update addressA custom field to see if it goes into addressB
    $addressParamsA['id'] = $addAddressA->id;
    $addressParamsA[$customField] = 'updated custom text field';
    $addAddressA = CRM_Core_BAO_Address::add($addressParamsA, FALSE);

    $address = $this->callAPISuccessGetSingle('Address', ['id' => $addAddressB->id, 'return' => $this->getCustomFieldName('text')]);
    $this->assertEquals($addressParamsA[$customField], $address[$customField]);

  }

  /**
   * Pinned countries with Default country
   */
  public function testPinnedCountriesWithDefaultCountry() {
    // Guyana, Netherlands, United States
    $pinnedCountries = ['1093', '1152', '1228'];

    // set default country to Netherlands
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => 1152, 'pinnedContactCountries' => $pinnedCountries]);
    // get the list of country
    $availableCountries = CRM_Core_PseudoConstant::country(FALSE, FALSE);
    // get the order of country id using their keys
    $availableCountries = array_keys($availableCountries);

    // default country is set, so first country should be Netherlands, then rest from pinned countries.

    // Netherlands
    $this->assertEquals(1152, $availableCountries[0]);
    // Guyana
    $this->assertEquals(1093, $availableCountries[1]);
    // United States
    $this->assertEquals(1228, $availableCountries[2]);
  }

  /**
   * Pinned countries with out Default country
   */
  public function testPinnedCountriesWithOutDefaultCountry() {
    // Guyana, Netherlands, United States
    $pinnedCountries = ['1093', '1152', '1228'];

    // unset default country
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => NULL, 'pinnedContactCountries' => $pinnedCountries]);

    // get the list of country
    $availableCountries = CRM_Core_PseudoConstant::country(FALSE, FALSE);
    // get the order of country id using their keys
    $availableCountries = array_keys($availableCountries);

    // no default country, so sequnece should be present as per pinned countries.

    // Guyana
    $this->assertEquals(1093, $availableCountries[0]);
    // Netherlands
    $this->assertEquals(1152, $availableCountries[1]);
    // United States
    $this->assertEquals(1228, $availableCountries[2]);
  }

  /**
   * Test core#2379 fix - geocodes shouldn't be > 14 characters.
   */
  public function testLongGeocodes() {
    $contactId = $this->individualCreate();

    $fixParams = [
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
      'supplemental_address_3' => '',
      'city' => 'El Paso',
      'postal_code' => '88575',
      'postal_code_suffix' => '',
      'state_province_id' => '1001',
      'country_id' => '1228',
      'geo_code_1' => '41.701308979563',
      'geo_code_2' => '-73.91941868639',
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
      'contact_id' => $contactId,
    ];

    $addAddress = CRM_Core_BAO_Address::add($fixParams, TRUE);

    $addParams = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for created contact address.'
    );

    $this->assertEquals('41.70130897956', $addAddress->geo_code_1, 'In line' . __LINE__);
    $this->assertEquals('-73.9194186863', $addAddress->geo_code_2, 'In line' . __LINE__);
  }

}
