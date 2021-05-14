<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core;

/**
 * Class MoneyTest
 * @package Civi\Core
 * @group headless
 */
class MoneyTest extends \CiviUnitTestCase {

  /**
   * Money Format cases.
   */
  public function machineMoneyTestCases() {
    $cases = [];
    $cases[] = ['1234.56', 2, '1,234.56'];
    $cases[] = ['1234.56', 2, '1,234.56'];
    $cases[] = ['1234.56789', 2, '1,234.57'];
    return $cases;
  }

  /**
   * @dataProvider machineMoneyTestCases
   */
  public function testMachineMoney($inputAmount, $precision, $expectedAmount) {
    $this->assertEquals($expectedAmount, \Civi::service('money')->getMachineMoney($inputAmount, $precision));
  }

  /**
   * Money Locale Format Cases
   */
  public function localeMoneyTestCases() {
    $cases = [];
    $cases[] = ['1234.56', '.', '1,234.56'];
    $cases[] = ['1234.56', '.', '1,234.56'];
    $cases[] = ['1234.56789', '.', '1,234.57'];
    $cases[] = ['1234.56', ',', '1.234,56'];
    $cases[] = ['1234.56', ',', '1.234,56'];
    $cases[] = ['1234.56789', ',', '1.234,57'];
    return $cases;
  }

  /**
   * @dataProvider localeMoneyTestCases
   */
  public function testLocaleFormatMoney($inputAmount, $decimalSeparator, $expectedAmount) {
    if ($decimalSeparator !== '.') {
      \Civi::settings()->set('monetaryDecimalPoint', $decimalSeparator);
      \Civi::settings()->set('monetaryThousandSeparator', '.');
    }
    $this->assertEquals($expectedAmount, \Civi::service('money')->getLocaleFormattedMoney($inputAmount, 'EUR'));
    if ($decimalSeparator !== '.') {
      \Civi::settings()->set('monetaryDecimalPoint', '.');
      \Civi::settings()->set('monetaryThousandSeparator', ',');
    }
  }

}
