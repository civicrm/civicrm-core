<?php
/**
 * Class CRM_Utils_GeocodeTest
 * @group headless
 */
class CRM_Utils_GeocodeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tareDown() {
    parent::tareDown();
  }

  public function testStateProvinceFormat() {
    $params = array('state_province_id' => 1022, 'country' => 'U.S.A');
    $formatted = CRM_Utils_Geocode_Google::format($params);
    $this->assertTrue($formatted);
    $this->assertEquals('46.729553', $params['geo_code_1']);
    $this->assertEquals('-94.6858998', $params['geo_code_2']);
  }

}
