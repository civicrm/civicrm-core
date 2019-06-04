<?php

/**
 * Class CRM_Utils_RuleTest
 * @group headless
 */
class CRM_Utils_MoneyTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider subtractCurrenciesDataProvider
   * @param string $leftOp
   * @param string $rightOp
   * @param string $currency
   * @param float $expectedResult
   */
  public function testSubtractCurrencies($leftOp, $rightOp, $currency, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Money::subtractCurrencies($leftOp, $rightOp, $currency));
  }

  public function testEquals() {
    $testValue = 0.01;

    for ($i = 0; $i <= 10; $i++) {
      $equalValues = CRM_Utils_Money::equals($testValue, $testValue + ($i * 0.0001), 'USD');
      $this->assertTrue($equalValues);
    }

    $this->assertFalse(CRM_Utils_Money::equals($testValue, $testValue + 0.001000000001, 'USD'));
  }

  /**
   * @return array
   */
  public function subtractCurrenciesDataProvider() {
    return array(
      array(number_format(300.00, 2), number_format(299.99, 2), 'USD', number_format(0.01, 2)),
      array(2, 1, 'USD', 1),
      array(0, 0, 'USD', 0),
      array(1, 2, 'USD', -1),
      array(number_format(19.99, 2), number_format(20.00, 2), 'USD', number_format(-0.01, 2)),
      array('notanumber', 5.00, 'USD', NULL),
    );
  }

  /**
   * Test rounded by currency function.
   *
   * In practice this only does rounding to 2 since rounding by any other amount is
   * only place-holder supported.
   */
  public function testFormatLocaleNumericRoundedByCurrency() {
    $result = CRM_Utils_Money::formatLocaleNumericRoundedByCurrency(8950.3678, 'NZD');
    $this->assertEquals('8,950.37', $result);
  }

  /**
   * Test rounded by currency function.
   *
   * This should be formatted according to European standards - . thousand separator
   * and , for decimal. (The Europeans are wrong but they don't know that. We will forgive them
   * because ... metric).
   */
  public function testFormatLocaleNumericRoundedByCurrencyEuroThousand() {
    $this->setCurrencySeparators('.');
    $result = CRM_Utils_Money::formatLocaleNumericRoundedByCurrency(8950.3678, 'NZD');
    $this->assertEquals('8.950,37', $result);
    $this->setCurrencySeparators(',');
  }

  /**
   * Test that using the space character as a currency works
   */
  public function testSpaceCurrency() {
    $this->assertEquals('  8,950.37', CRM_Utils_Money::format(8950.37, ' '));
  }

  /**
   * Test that passing an invalid currency throws an error
   */
  public function testInvalidCurrency() {
    $this->expectException(\CRM_Core_Exception::class, 'Invalid currency "NOT_A_CURRENCY"');
    CRM_Utils_Money::format(4.00, 'NOT_A_CURRENCY');
  }

}
