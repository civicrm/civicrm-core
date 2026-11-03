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
    $this->assertSame('Seven', CRM_Utils_EnglishNumber::toCamelCase(7));
    $this->assertSame('Nineteen', CRM_Utils_EnglishNumber::toCamelCase(19));
    $this->assertSame('Thirty', CRM_Utils_EnglishNumber::toCamelCase(30));
    $this->assertSame('FiftyFour', CRM_Utils_EnglishNumber::toCamelCase(54));
    $this->assertSame('NinetyNine', CRM_Utils_EnglishNumber::toCamelCase(99));
    $this->assertSame('OneHundred', CRM_Utils_EnglishNumber::toCamelCase(100));
    $this->assertSame('OneOhFive', CRM_Utils_EnglishNumber::toCamelCase(105));
    $this->assertSame('OneTwelve', CRM_Utils_EnglishNumber::toCamelCase(112));
    $this->assertSame('TwoFiftyTwo', CRM_Utils_EnglishNumber::toCamelCase(252));
  }

  public function testHyphen(): void {
    $this->assertSame('seven', CRM_Utils_EnglishNumber::toHyphen(7));
    $this->assertSame('nineteen', CRM_Utils_EnglishNumber::toHyphen(19));
    $this->assertSame('thirty', CRM_Utils_EnglishNumber::toHyphen(30));
    $this->assertSame('fifty-four', CRM_Utils_EnglishNumber::toHyphen(54));
    $this->assertSame('ninety-nine', CRM_Utils_EnglishNumber::toHyphen(99));
    $this->assertSame('one-hundred', CRM_Utils_EnglishNumber::toHyphen(100));
    $this->assertSame('one-oh-five', CRM_Utils_EnglishNumber::toHyphen(105));
    $this->assertSame('one-twelve', CRM_Utils_EnglishNumber::toHyphen(112));
    $this->assertSame('two-fifty-two', CRM_Utils_EnglishNumber::toHyphen(252));
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
