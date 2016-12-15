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
    return array(
      array('ef4444', 'white'),
      array('FAA31B', 'black'),
      array('FFF000', 'black'),
      array(' 82c341', 'black'),
      array('#009F75', 'white'),
      array('#88C6eD', 'black'),
      array('# 394ba0', 'white'),
      array(' #D54799', 'white'),
    );
  }

}
