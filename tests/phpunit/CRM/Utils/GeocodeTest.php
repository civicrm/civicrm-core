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

  /**
   * Test the format returned by Google GeoCoding
   * @group ornery
   */
  public function testStateProvinceFormat() {
    $params = array('state_province_id' => 1022, 'country' => 'U.S.A');
    $formatted = CRM_Utils_Geocode_Google::format($params);
    if (isset($params['geo_code_error']) && $params['geo_code_error'] == 'OVER_QUERY_LIMIT') {
      $this->markTestIncomplete('geo_code_error: OVER_QUERY_LIMIT');
    }
    $this->assertTrue($formatted);
    $this->assertApproxEquals('46.72', $params['geo_code_1'], 1);
    $this->assertApproxEquals('-94.68', $params['geo_code_2'], 1);
  }

  /**
   * Test Geoging Method off
   * @group ornery
   */
  public function testGeocodeMethodOff() {
    // Set a geocoding provider.
    $result = civicrm_api3('Setting', 'create', array(
      'geoProvider' => "Google",
    ));

    CRM_Utils_GeocodeProvider::disableForSession();

    // Save a contact with geo coding disabled.
    $params = array(
      'first_name' => 'Abraham',
      'last_name' => 'Lincoln',
      'contact_type' => 'Individual',
      'api.Address.create' => array(
        'street_address' => '1600 Pennsylvania Avenue',
        'city' => 'Washington',
        'state_province' => 'DC',
        'location_type_id' => 1,
      ),
    );
    $result = civicrm_api3('Contact', 'create', $params);
    $contact_values = array_pop($result['values']);
    $address_values = array_pop($contact_values['api.Address.create']['values']);

    $this->assertArrayNotHasKey('geo_code_1', $address_values, 'No geocoding when geocodeMethod is empty');

    // Run the geocode job on that specific contact
    CRM_Utils_GeocodeProvider::reset();
    try {
      $params_geocode = array(
        'start' => $contact_values['id'],
        'end' => $contact_values['id'] + 1,
        'geocoding' => 1,
        'parse' => 0,
      );
      $result_geocode = civicrm_api3('Job', 'geocode', $params_geocode);
    }
    catch (CiviCRM_API3_Exception $e) {
      if ($e->getMessage() == 'Aborting batch geocoding. Hit the over query limit on geocoder.') {
        $this->markTestIncomplete('Job.geocode error_message: A fatal error was triggered: Aborting batch geocoding. Hit the over query limit on geocoder.');
      }
      else {
        throw $e;
      }
    }
    $params_address_getsingle = array(
      'contact_id' => $contact_values['id'],
    );
    $result_address_getsingle = civicrm_api3('Address', 'getsingle', $params_address_getsingle);

    // We should get a geo code setting.
    $this->assertApproxEquals('38.89', CRM_Utils_Array::value('geo_code_1', $result_address_getsingle), 1);
  }

}
