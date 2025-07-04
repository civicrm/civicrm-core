<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmMoneyTest
 * @group headless
 * @group locale
 */
class CRM_Core_Smarty_plugins_CrmMoneyTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * @return array
   */
  public static function moneyCases() {
    $cases = [];
    $cases[] = ['$4.00', '{assign var="amount" value="4.00"}{$amount|crmMoney:USD}'];
    $cases[] = ['€1,234.00', '{assign var="amount" value="1234.00"}{$amount|crmMoney:EUR}'];
    return $cases;
  }

  /**
   * @dataProvider moneyCases
   * @param $expected
   * @param $input
   */
  public function testMoney($expected, $input) {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty($input);
    $this->assertEquals($expected, $actual, "Process input=[$input]");
  }

}
