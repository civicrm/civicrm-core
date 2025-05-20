<?php

/**
 * Class CRM_Utils_AddressTest
 * @group headless
 */
class CRM_Utils_AddressTest extends CiviUnitTestCase {

  public function testAddressFormat(): void {
    $contact = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Micky',
      'last_name' => 'mouse',
      'contact_type' => 'Individual',
    ]);
    $address = $this->callAPISuccess('Address', 'create', [
      'street_address' => '1 Happy Place',
      'city' => 'Miami',
      'state_province' => 'Florida',
      'country' => 'United States',
      'postal_code' => 33101,
      'contact_id' => $contact['id'],
      'location_type_id' => 5,
      'is_primary' => 1,
    ]);
    $addressDetails = $address['values'][$address['id']];
    $countries = CRM_Core_PseudoConstant::country();
    $addressDetails['country'] = $countries[$addressDetails['country_id']];
    $formatted_address = CRM_Utils_Address::formatMailingLabel($addressDetails);
    $this->assertTrue((bool) strstr($formatted_address, 'UNITED STATES'));
  }

  public function testVcardFormat(): void {
    $address = [
      'street_address' => '1 Happy Place',
      'city' => 'Miami',
      'state_province_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'state_province_id', 'Florida'),
      'country_id:label' => 'United States',
      'postal_code' => 33101,
      'contact_id' => 1,
      'county_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'county_id', 'Alameda'),
      'postal_code_suffix' => '12345',
      'location_type_id' => 5,
      'is_primary' => 1,
      'name' => 'Big house',
      'supplemental_address_1' => 'line 1',
      'supplemental_address_2' => 'line 2',
      'supplemental_address_3' => 'line 3',
    ];
    $result = CRM_Utils_Address::formatVcard($address);
    $this->assertEquals('<div class="location vcard"><span class="adr"><span class="address-name">Big house</span>
<span class="street-address">1 Happy Place</span>
<span class="extended-address">line 1</span>
line 2
line 3
<span class="locality">Miami</span>, <span class="region">FL</span> <span class="postal-code">33101-12345</span>
<span class="country-name">United States</span></span></div>', $result);

  }

  /**
   * Test state/province field's state_province_name token on getFormattedBillingAddressFieldsFromParameters
   * and test using alternate names for state_province field
   */
  public function testStateProvinceFormattedBillingAddress(): void {
    $params = [
      'billing_street_address-' . CRM_Core_BAO_LocationType::getBilling() => '123 Happy Place',
      'billing_city-' . CRM_Core_BAO_LocationType::getBilling() => 'Miami',
      'billing_postal_code-' . CRM_Core_BAO_LocationType::getBilling() => 33101,
      // 1000 => Alabama (AL)
      'state_province-' . CRM_Core_BAO_LocationType::getBilling() => '1000',
      'country-' . CRM_Core_BAO_LocationType::getBilling() => 'United States',
    ];

    // Set address_format (we are only interested in state_province & state_province_name).
    $addFormat = '{contact.state_province}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params);
    $this->assertEquals("AL\n", $formatted_address);

    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params);
    $this->assertEquals("Alabama\n", $formatted_address);

    // Test using alternate names for state/province field.
    unset($params['state_province-' . CRM_Core_BAO_LocationType::getBilling()]);
    // Alternate name 1.
    $params['billing_state_province-' . CRM_Core_BAO_LocationType::getBilling()] = '1000';
    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params);
    $this->assertEquals("Alabama\n", $formatted_address);

    unset($params['state_province-' . CRM_Core_BAO_LocationType::getBilling()]);
    // alternate name 2
    $params['billing_state_province_id-' . CRM_Core_BAO_LocationType::getBilling()] = '1000';
    $addFormat = '{contact.state_province_name}';
    Civi::settings()->set('address_format', $addFormat);
    $formatted_address = CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params);
    $this->assertEquals("Alabama\n", $formatted_address);
  }

}
