<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
    $contactId = Contact::createIndividual();

    $params = array();
    $params['address']['1'] = array(
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'Attn: Accounting',
      'supplemental_address_2' => 'Powai',
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

    $cid = $this->assertDBNotNull('CRM_Core_DAO_Address', $contactId, 'id', 'contact_id',
      'Database check for updated address by contactId.'
    );
    $addressId = $this->assertDBNotNull('CRM_Core_DAO_Address', '120 Terminal Road', 'id', 'street_address',
      'Database check for updated address by street_name.'
    );
    Contact::delete($contactId);
  }

  /**
   * Add() method ( )
   */
  public function testAdd() {
    $contactId = Contact::createIndividual();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
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
    Contact::delete($contactId);
  }

  /**
   * AllAddress() method ( )
   */
  public function testallAddress() {
    $contactId = Contact::createIndividual();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
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

    Contact::delete($contactId);
  }

  /**
   * AllAddress() method ( ) with null value
   */
  public function testnullallAddress() {
    $contactId = Contact::createIndividual();

    $fixParams = array(
      'street_address' => 'E 906N Pine Pl W',
      'supplemental_address_1' => 'Editorial Dept',
      'supplemental_address_2' => '',
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

    Contact::delete($contactId);
  }

  /**
   * GetValues() method (get Address fields)
   */
  public function testGetValues() {
    $contactId = Contact::createIndividual();

    $params = array();
    $params['address']['1'] = array(
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'Attn: Accounting',
      'supplemental_address_2' => 'Powai',
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
    Contact::delete($contactId);
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

}
