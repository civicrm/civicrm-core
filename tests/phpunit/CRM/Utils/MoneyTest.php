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

}
