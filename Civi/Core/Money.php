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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

 namespace Civi\Core;

class Money {

  /**
   * Get Machine version of Money (en_US locale version used for storing in the database)
   * @param string $inputMoney
   * @param int $number_of_palaces = optional number of decimals to return.
   */
  public function getMachineMoney(string $inputMoney, int $number_of_palaces = 2): string {
    return \CRM_Utils_Money::formatUSLocaleNumericRounded($inputMoney, $number_of_palaces);
  }

  /**
   * Get Locale version of money
   * @param string $inputMoney
   * @param string|null $currency
   */
  public function getLocaleFormattedMoney(string $inputMoney, ?string $currency): string {
    return \CRM_Utils_Money::formatLocaleNumericRoundedByCurrency($inputMoney, $currency);
  }

}
