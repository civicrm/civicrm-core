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

  public function testRoundTrip(): void {
    for ($i = 0; $i < 100; $i++) {
      $camel = CRM_Utils_EnglishNumber::toCamelCase($i);
      $camelToInt = CRM_Utils_EnglishNumber::toInt($camel);
      $this->assertEquals($camelToInt, $i);

      $hyphen = CRM_Utils_EnglishNumber::toHyphen($i);
      $hyphenToInt = CRM_Utils_EnglishNumber::toInt($hyphen);
      $this->assertEquals($hyphenToInt, $i);
    }
  }

  public function testCamel(): void {
    $this->assertEquals('Seven', CRM_Utils_EnglishNumber::toCamelCase(7));
    $this->assertEquals('Nineteen', CRM_Utils_EnglishNumber::toCamelCase(19));
    $this->assertEquals('Thirty', CRM_Utils_EnglishNumber::toCamelCase(30));
    $this->assertEquals('FiftyFour', CRM_Utils_EnglishNumber::toCamelCase(54));
    $this->assertEquals('NinetyNine', CRM_Utils_EnglishNumber::toCamelCase(99));
    $this->assertEquals('OneHundred', CRM_Utils_EnglishNumber::toCamelCase(100));
    $this->assertEquals('OneOhFive', CRM_Utils_EnglishNumber::toCamelCase(105));
    $this->assertEquals('OneTwelve', CRM_Utils_EnglishNumber::toCamelCase(112));
    $this->assertEquals('TwoFiftyTwo', CRM_Utils_EnglishNumber::toCamelCase(252));
  }

  public function testHyphen(): void {
    $this->assertEquals('seven', CRM_Utils_EnglishNumber::toHyphen(7));
    $this->assertEquals('nineteen', CRM_Utils_EnglishNumber::toHyphen(19));
    $this->assertEquals('thirty', CRM_Utils_EnglishNumber::toHyphen(30));
    $this->assertEquals('fifty-four', CRM_Utils_EnglishNumber::toHyphen(54));
    $this->assertEquals('ninety-nine', CRM_Utils_EnglishNumber::toHyphen(99));
    $this->assertEquals('one-hundred', CRM_Utils_EnglishNumber::toHyphen(100));
    $this->assertEquals('one-oh-five', CRM_Utils_EnglishNumber::toHyphen(105));
    $this->assertEquals('one-twelve', CRM_Utils_EnglishNumber::toHyphen(112));
    $this->assertEquals('two-fifty-two', CRM_Utils_EnglishNumber::toHyphen(252));
  }

  public function testIsNumeric(): void {
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
