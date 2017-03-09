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

}
