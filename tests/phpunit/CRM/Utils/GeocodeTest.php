<?php
/**
 * Class CRM_Utils_GeocodeTest
 * @group headless
 */
class CRM_Utils_GeocodeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testStateProvinceFormat() {
    $params = array('state_province_id' => 1022, 'country' => 'U.S.A');
    $formatted = CRM_Utils_Geocode_Google::format($params);
    $this->assertTrue($formatted);
    $this->assertApproxEquals('46.72', $params['geo_code_1'], 1);
    $this->assertApproxEquals('-94.68', $params['geo_code_2'], 1);
  }

  public function testGeocodeMethodOff() {
    // Set a geocoding provider.
    $result = civicrm_api3('Setting', 'create', array(
      'geoProvider' => "Google",
    ));

    // Save a contact without disabling geo coding.
    $params = array(
      'first_name' => 'Abraham',
      'last_name' => 'Lincoln',
      'contact_type' => 'Individual',
      'api.Address.create' => array(
        'street_address' => '1600 Pennsylvania Avenue',
        'city' => 'Washington',
        'state_province' => 'DC',
        'location_type_id' => 1
      )
    );
    $result = civicrm_api3('Contact', 'create', $params);
    $contact_values = array_pop($result['values']);
    $address_values = array_pop($contact_values['api.Address.create']['values']);
    // We should get a geo code setting.
    $this->assertApproxEquals('38.89', $address_values['geo_code_1'], 1);

    // Set geocodeMethod to empty.
    $config = CRM_Core_Config::singleton();
    $config->geocodeMethod = '';

    // Do it again. This time, we should not geocode.
    $new_result = civicrm_api3('Contact', 'create', $params);
    $new_contact_values = array_pop($new_result['values']);
    $new_address_values = array_pop($new_contact_values['api.Address.create']['values']);
    $this->assertArrayNotHasKey('geo_code_1', $new_address_values, 'No geocoding when geocodeMethod is empty');

  }
}
