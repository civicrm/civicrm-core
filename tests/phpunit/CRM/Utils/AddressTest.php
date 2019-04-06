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

  /**
   * Test state/province field's state_province_name token on getFormattedBillingAddressFieldsFromParameters
   * and test using alternate names for state_province field
   */
  public function testStateProvinceFormattedBillingAddress() {
    $params = array(
      'billing_street_address-99' => '123 Happy Place',
      'billing_city-99' => 'Miami',
      'billing_postal_code-99' => 33101,
      // 1000 => Alabama (AL)
      'state_province-99' => '1000',
      'country-99' => 'United States',
    );

    // set address_format (we are only interested in state_province & state_province_name)
    $addFormat = '{contact.state_province}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params, '99');
    $this->assertTrue((bool) $formatted_address == 'AL');

    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params, '99');
    $this->assertTrue((bool) $formatted_address == 'Alabama');

    // test using alternate names for state/province field
    unset($params['state_province-99']);
    // alternate name 1
    $params['billing_state_province-99'] = '1000';
    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params, '99');
    $this->assertTrue((bool) $formatted_address == 'Alabama');

    unset($params['state_province-99']);
    // alternate name 2
    $params['billing_state_province_id-99'] = '1000';
    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params, '99');
    $this->assertTrue((bool) $formatted_address == 'Alabama');
  }

}
