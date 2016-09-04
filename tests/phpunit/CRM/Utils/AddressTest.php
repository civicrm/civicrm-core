<?php

/**
 * Class CRM_Utils_AddressTest
 * @group headless
 */
class CRM_Utils_AddressTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testAddressFormat() {
    $contact = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'Micky',
      'last_name' => 'mouse',
      'contact_type' => 'Individual',
    ));
    $address = $this->callAPISuccess('address', 'create', array(
      'street_address' => '1 Happy Place',
      'city' => 'Miami',
      'state_province' => 'Flordia',
      'country' => 'United States',
      'postal_code' => 33101,
      'contact_id' => $contact['id'],
      'location_type_id' => 5,
      'is_primary' => 1,
    ));
    $addressDetails = $address['values'][$address['id']];
    $countries = CRM_Core_PseudoConstant::country();
    $addressDetails['country'] = $countries[$addressDetails['country_id']];
    $formatted_address = CRM_Utils_Address::format($addressDetails, 'mailing_format', FALSE, TRUE);
    $this->assertTrue((bool) strstr($formatted_address, 'UNITED STATES'));
  }

}
