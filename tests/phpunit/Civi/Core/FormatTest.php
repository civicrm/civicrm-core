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

use Civi;
use CiviUnitTestCase;

/**
 * Class MoneyTest
 *
 * @package Civi\Core
 * @group headless
 */
class FormatTest extends CiviUnitTestCase {

  /**
   * Money Locale Format Cases
   */
  public function localeMoneyTestCases(): array {
    $cases = [];
    $cases['en_US_USD'] = [
      [
        'amount' => '1234.56',
        'locale' => 'en_US',
        'currency' => 'USD',
        'money' => '$1,234.56',
        'money_number' => '1,234.56',
        'money_number_long' => '1,234.56',
        'number' => '1,234.56',
        'money_long' => '$1,234.56',
      ],
    ];
    $cases['en_US_USD_long'] = [
      [
        'amount' => '1234.56700',
        'locale' => 'en_US',
        'currency' => 'USD',
        'money' => '$1,234.57',
        'money_number' => '1,234.57',
        'money_number_long' => '1,234.567',
        'number' => '1,234.567',
        'money_long' => '$1,234.567',
      ],
    ];
    $cases['en_US_USD_pad'] = [
      [
        'amount' => '1234.50',
        'locale' => 'en_US',
        'currency' => 'USD',
        'money' => '$1,234.50',
        'money_number' => '1,234.50',
        'money_number_long' => '1,234.50',
        'number' => '1,234.5',
        'money_long' => '$1,234.50',
      ],
    ];
    $cases['en_US_EUR'] = [
      [
        'amount' => '1234.56',
        'locale' => 'en_US',
        'currency' => 'EUR',
        'money' => '€1,234.56',
        'money_number' => '1,234.56',
        'number' => '1,234.56',
        'money_long' => '€1,234.56',
        'money_number_long' => '1,234.56',
      ],
    ];
    $cases['en_US_EUR_long'] = [
      [
        'amount' => '1234.56700',
        'locale' => 'en_US',
        'currency' => 'EUR',
        'money' => '€1,234.57',
        'money_number' => '1,234.57',
        'number' => '1,234.567',
        'money_long' => '€1,234.567',
        'money_number_long' => '1,234.567',
      ],
    ];
    $cases['en_US_EUR_pad'] = [
      [
        'amount' => '1234.5',
        'locale' => 'en_US',
        'currency' => 'EUR',
        'money' => '€1,234.50',
        'money_number' => '1,234.50',
        'number' => '1,234.5',
        'money_long' => '€1,234.50',
        'money_number_long' => '1,234.50',
      ],
    ];
    $cases['fr_FR_EUR'] = [
      [
        'amount' => '1234.56',
        'locale' => 'fr_FR',
        'currency' => 'EUR',
        'money' => '1 234,56 €',
        'money_number' => '1 234,56',
        'number' => '1 234,56',
        'money_long' => '1 234,56 €',
        'money_number_long' => '1 234,56',
      ],
    ];
    $cases['fr_FR_EUR_long'] = [
      [
        'amount' => '1234.56700',
        'locale' => 'fr_FR',
        'currency' => 'EUR',
        'money' => '1 234,57 €',
        'money_number' => '1 234,57',
        'number' => '1 234,567',
        'money_long' => '1 234,567 €',
        'money_number_long' => '1 234,567',
      ],
    ];
    $cases['fr_FR_EUR_pad'] = [
      [
        'amount' => '1234.50',
        'locale' => 'fr_FR',
        'currency' => 'EUR',
        'money' => '1 234,50 €',
        'money_number' => '1 234,50',
        'number' => '1 234,5',
        'money_long' => '1 234,50 €',
        'money_number_long' => '1 234,50',
      ],
    ];
    $cases['ar_AE_KWD'] = [
      [
        'amount' => '1234.56',
        'locale' => 'ar_AE',
        'currency' => 'KWD',
        'money' => '١٬٢٣٤٫٥٦٠ د.ك.‏',
        'money_number' => '١٬٢٣٤٫٥٦٠',
        'number' => '١٬٢٣٤٫٥٦',
        'money_long' => '١٬٢٣٤٫٥٦٠ د.ك.‏',
        'money_number_long' => '١٬٢٣٤٫٥٦٠',
      ],
    ];
    $cases['ar_AE_KWD_long'] = [
      [
        'amount' => '1234.56710',
        'locale' => 'ar_AE',
        'currency' => 'KWD',
        'money' => '١٬٢٣٤٫٥٦٧ د.ك.‏',
        'money_number' => '١٬٢٣٤٫٥٦٧',
        'number' => '١٬٢٣٤٫٥٦٧١',
        'money_long' => '١٬٢٣٤٫٥٦٧١ د.ك.‏',
        'money_number_long' => '١٬٢٣٤٫٥٦٧١',
      ],
    ];
    $cases['ar_AE_KWD_pad'] = [
      [
        'amount' => '1234.56',
        'locale' => 'ar_AE',
        'currency' => 'KWD',
        'money' => '١٬٢٣٤٫٥٦٠ د.ك.‏',
        'money_number' => '١٬٢٣٤٫٥٦٠',
        'number' => '١٬٢٣٤٫٥٦',
        'money_long' => '١٬٢٣٤٫٥٦٠ د.ك.‏',
        'money_number_long' => '١٬٢٣٤٫٥٦٠',
      ],
    ];
    $cases['en_US_KWD'] = [
      [
        'amount' => '1234.56',
        'locale' => 'fr_FR',
        'currency' => 'KWD',
        'money' => '1 234,560 KWD',
        'money_number' => '1 234,560',
        'number' => '1 234,56',
        'money_long' => '1 234,560 KWD',
        'money_number_long' => '1 234,560',
      ],
    ];
    $cases['fr_FR_KWD_long'] = [
      [
        'amount' => '1234.5678000',
        'locale' => 'fr_FR',
        'currency' => 'KWD',
        'money' => '1 234,568 KWD',
        'money_number' => '1 234,568',
        'number' => '1 234,5678',
        'money_long' => '1 234,5678 KWD',
        'money_number_long' => '1 234,5678',
      ],
    ];
    $cases['en_US_KWD_pad'] = [
      [
        'amount' => '1234.5',
        'locale' => 'fr_FR',
        'currency' => 'KWD',
        'money' => '1 234,500 KWD',
        'money_number' => '1 234,500',
        'number' => '1 234,5',
        'money_long' => '1 234,500 KWD',
        'money_number_long' => '1 234,500',
      ],
    ];
    return $cases;
  }

  /**
   * @dataProvider localeMoneyTestCases
   *
   * @param array $testData
   */
  public function testMoneyAndNumbers(array $testData): void {
    $this->assertEquals($testData['money'], Civi::format()->money($testData['amount'], $testData['currency'], $testData['locale']));
    $this->assertEquals($testData['money_number'], Civi::format()->moneyNumber($testData['amount'], $testData['currency'], $testData['locale']));
    $this->assertEquals($testData['number'], Civi::format()->number($testData['amount'], $testData['locale']));
    $this->assertEquals($testData['money_long'], Civi::format()->moneyLong($testData['amount'], $testData['currency'], $testData['locale']));
    $this->assertEquals($testData['money_number_long'], Civi::format()->moneyNumberLong($testData['amount'], $testData['currency'], $testData['locale']));

  }

}
