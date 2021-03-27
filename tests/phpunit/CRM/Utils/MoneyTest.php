<?php

/**
 * Class CRM_Utils_RuleTest
 * @group headless
 */
class CRM_Utils_MoneyTest extends CiviUnitTestCase {

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

    for ($i = 0; $i < 10; $i++) {
      $equalValues = CRM_Utils_Money::equals($testValue, $testValue + ($i * 0.0005), 'USD');
      $this->assertTrue($equalValues, 'Currency - USD' . $testValue . ' is equal to USD' . ($testValue + ($i * 0.0005)));
    }

    $this->assertFalse(CRM_Utils_Money::equals($testValue + 0.004, $testValue + 0.006, 'USD'), 'Currency - USD' . ($testValue + 0.004) . ' is different to USD' . ($testValue + 0.006));
  }

  /**
   * @return array
   */
  public function subtractCurrenciesDataProvider() {
    return [
      [number_format(300.00, 2), number_format(299.99, 2), 'USD', number_format(0.01, 2)],
      [2, 1, 'USD', 1],
      [0, 0, 'USD', 0],
      [1, 2, 'USD', -1],
      [269.565217391, 1, 'USD', 268.57],
      [number_format(19.99, 2), number_format(20.00, 2), 'USD', number_format(-0.01, 2)],
      ['notanumber', 5.00, 'USD', NULL],
    ];
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
   * Test rounded by currency function with specified precision.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testFormatLocaleNumericRoundedByPrecision($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $result = CRM_Utils_Money::formatLocaleNumericRoundedByPrecision(8950.3678, 3);
    $expected = ($thousandSeparator === ',') ? '8,950.368' : '8.950,368';
    $this->assertEquals($expected, $result);
  }

  /**
   * Test rounded by currency function with specified precision but without padding to reach it.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testFormatLocaleNumericRoundedByOptionalPrecision($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $result = CRM_Utils_Money::formatLocaleNumericRoundedByOptionalPrecision(8950.3678, 8);
    $expected = ($thousandSeparator === ',') ? '8,950.3678' : '8.950,3678';
    $this->assertEquals($expected, $result);

    $result = CRM_Utils_Money::formatLocaleNumericRoundedByOptionalPrecision(123456789.987654321, 9);
    $expected = ($thousandSeparator === ',') ? '123,456,789.98765' : '123.456.789,98765';
    $this->assertEquals($result, $expected);
  }

  /**
   * Test that passing an invalid currency throws an error
   */
  public function testInvalidCurrency() {
    $this->expectException(\CRM_Core_Exception::class, 'Invalid currency "NOT_A_CURRENCY"');
    CRM_Utils_Money::format(4.00, 'NOT_A_CURRENCY');
  }

}
