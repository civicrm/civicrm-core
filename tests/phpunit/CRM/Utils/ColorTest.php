<?php

/**
 * Class CRM_Utils_ColorTest
 * @group headless
 */
class CRM_Utils_ColorTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * @dataProvider contrastExamples
   */
  public function testGetContrast($background, $text) {
    $this->assertEquals($text, CRM_Utils_Color::getContrast($background));
  }

  public static function contrastExamples() {
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
  public function testGetRgb($color, $expectedRGB, $expectedHex) {
    $rgb = CRM_Utils_Color::getRgb($color);
    $this->assertEquals($expectedRGB, $rgb);
    $this->assertEquals($expectedHex, CRM_Utils_Color::rgbToHex($rgb));
  }

  public static function rgbExamples() {
    return [
      ['#fff', [255, 255, 255], '#ffffff'],
      ['white', [255, 255, 255], '#ffffff'],
      ['#000000', [0, 0, 0], '#000000'],
      [' black', [0, 0, 0], '#000000'],
      ['  #111 ', [17, 17, 17], '#111111'],
      [' fffc99 ', [255, 252, 153], '#fffc99'],
      ['blue', [0, 0, 255], '#0000ff'],
      ['Green', [0, 128, 0], '#008000'],
      ['rgb(12, 0, 123)', [12, 0, 123], '#0c007b'],
      [' rgb ( 123, 0, 12 ) !important', [123, 0, 12], '#7b000c'],
    ];
  }

}
