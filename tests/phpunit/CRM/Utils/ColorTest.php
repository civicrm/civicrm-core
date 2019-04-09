<?php

/**
 * Class CRM_Utils_ColorTest
 * @group headless
 */
class CRM_Utils_ColorTest extends CiviUnitTestCase {

  /**
   * @dataProvider contrastExamples
   */
  public function testGetContrast($background, $text) {
    $this->assertEquals($text, CRM_Utils_Color::getContrast($background));
  }

  public function contrastExamples() {
    return [
      ['ef4444', 'white'],
      ['FAA31B', 'black'],
      ['FFF000', 'black'],
      [' 82c341', 'black'],
      ['#009F75', 'white'],
      ['#88C6eD', 'black'],
      ['# 394ba0', 'white'],
      [' #D54799', 'white'],
    ];
  }

  /**
   * @dataProvider rgbExamples
   */
  public function testGetRgb($hex, $rgb) {
    $this->assertEquals($rgb, CRM_Utils_Color::getRgb($hex));
  }

  public function rgbExamples() {
    return [
      ['#fff', [255, 255, 255]],
      ['#000000', [0, 0, 0]],
      ['#111', [17, 17, 17]],
      [' fffc99 ', [255, 252, 153]],
    ];
  }

}
