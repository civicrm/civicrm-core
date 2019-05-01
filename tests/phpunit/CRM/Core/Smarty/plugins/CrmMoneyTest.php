<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmMoneyTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmMoneyTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    require_once 'CRM/Core/Smarty.php';

    // Templates should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  /**
   * @return array
   */
  public function moneyCases() {
    $cases = [];
    $cases[] = ['$ 4.00', '{assign var="amount" value="4.00"}{$amount|crmMoney:USD}'];
    $cases[] = ['€ 1,234.00', '{assign var="amount" value="1234.00"}{$amount|crmMoney:EUR}'];
    $cases[] = [
      '$ <input size="10" style="background-color:#EBECE4" readonly="readonly" name="eachPaymentAmount" type="text" id="eachPaymentAmount" class="crm-form-text">',
      '{assign var="amount" value=\'<input size="10" style="background-color:#EBECE4" readonly="readonly" name="eachPaymentAmount" type="text" id="eachPaymentAmount" class="crm-form-text">\'}{$amount|crmMoney:USD}',
    ];
    return $cases;
  }

  /**
   * @dataProvider moneyCases
   * @param $expected
   * @param $input
   */
  public function testMoney($expected, $input) {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:' . $input);
    $this->assertEquals($expected, $actual, "Process input=[$input]");
  }

}
