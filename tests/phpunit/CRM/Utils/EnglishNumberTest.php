<?php

/**
 * Class CRM_Utils_EnglishNumberTest
 *
 * @group headless
 */
class CRM_Utils_EnglishNumberTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testRoundTrip() {
    for ($i = 0; $i < 100; $i++) {
      $camel = CRM_Utils_EnglishNumber::toCamelCase($i);
      $camelToInt = CRM_Utils_EnglishNumber::toInt($camel);
      $this->assertEquals($camelToInt, $i);

      $hyphen = CRM_Utils_EnglishNumber::toHyphen($i);
      $hyphenToInt = CRM_Utils_EnglishNumber::toInt($hyphen);
      $this->assertEquals($hyphenToInt, $i);
    }
  }

  public function testCamel() {
    $this->assertEquals('Seven', CRM_Utils_EnglishNumber::toCamelCase(7));
    $this->assertEquals('Nineteen', CRM_Utils_EnglishNumber::toCamelCase(19));
    $this->assertEquals('Thirty', CRM_Utils_EnglishNumber::toCamelCase(30));
    $this->assertEquals('FiftyFour', CRM_Utils_EnglishNumber::toCamelCase(54));
    $this->assertEquals('NinetyNine', CRM_Utils_EnglishNumber::toCamelCase(99));
  }

  public function testHyphen() {
    $this->assertEquals('seven', CRM_Utils_EnglishNumber::toHyphen(7));
    $this->assertEquals('nineteen', CRM_Utils_EnglishNumber::toHyphen(19));
    $this->assertEquals('thirty', CRM_Utils_EnglishNumber::toHyphen(30));
    $this->assertEquals('fifty-four', CRM_Utils_EnglishNumber::toHyphen(54));
    $this->assertEquals('ninety-nine', CRM_Utils_EnglishNumber::toHyphen(99));
  }

  public function testIsNumeric() {
    $assertNumeric = function($expectBool, $string) {
      $this->assertEquals($expectBool, CRM_Utils_EnglishNumber::isNumeric($string), "isNumeric($string) should return " . (int) $expectBool);
    };
    $assertNumeric(TRUE, 'FiveThirtyEight');
    $assertNumeric(TRUE, 'Seventeen');
    $assertNumeric(TRUE, 'four-one-one');
    $assertNumeric(FALSE, 'Eleventy');
    $assertNumeric(FALSE, 'Bazillions');
  }

}
