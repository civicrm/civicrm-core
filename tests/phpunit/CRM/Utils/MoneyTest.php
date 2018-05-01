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
   * @param $inputData
   * @param $expectedResult
   */
  public function testSubtractCurrencies($leftOp, $rightOp, $currency, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Money::subtractCurrencies($leftOp, $rightOp, $currency));
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
   * @dataProvider currenciesDataProvider
   * @param $currency
   */
  public function testGetCurrencyPrecision($currency) {
    $this->assertEquals($currency['precision'], CRM_Utils_Money::getCurrencyPrecision($currency['name']));
  }

  /**
   * FIXME: This needs to use a proper source for currency precision (but we don't have one in CiviCRM yet (maybe MoneyPHP?)
   * @return array
   */
  public function currenciesDataProvider() {
    $currencies = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array(
      'labelColumn' => 'name',
      'orderColumn' => TRUE,
    ));
    foreach ($currencies as $currency) {
      $currencyList[]['name'] = $currency;
      $currencyList[]['precision'] = 2;
    }
    return $currencyList;
  }


  /**
   * @dataProvider longDecimalDataProvider
   * @param $input
   * @param $expected
   */
  public function testFormatLongDecimal($input, $expected) {
    $this->assertEquals($expected, CRM_Utils_Money::formatLongDecimal($input));
  }

  /**
   * @return array
   */
  public function longDecimalDataProvider() {
    return array(
      // array(input, expected)
      array('10', '10'),
      array('10.23', '10.23'),
      array('10.2345678', '10.2345678'),
      array('-10.2345678', '-10.2345678'),
      array('10,2345678', '10.2345678'),
      array('£10,2345678', '10.2345678'),
    );
  }

  /*CRM_Utils_Money::formatDecimalRounded();
  CRM_Utils_Money::formatFull();
  CRM_Utils_Money::formatLocaleFull();
  CRM_Utils_Money::formatLocaleNumeric();
  CRM_Utils_Money::formatNumeric();
  CRM_Utils_Money::formatCents();
  */

}
