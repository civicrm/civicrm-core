<?php

/**
 * Class CRM_Core_Config_DefaultsTest
 */
class CRM_Core_Config_DefaultsTest extends CiviUnitTestCase {


  public function sizeCases() {
    $cases = [];
    $cases[] = ['20M', '20971520'];
    $cases[] = ['40G', '42949672960'];
    return $cases;
  }

  /**
   * @param $size
   * @param $expectedValue
   * @dataProvider sizeCases
   */
  public function testFormatUnitSize($size, $expectedValue) {
    $this->assertEquals($expectedValue, CRM_Core_Config_Defaults::formatUnitSize($size));
  }

}
