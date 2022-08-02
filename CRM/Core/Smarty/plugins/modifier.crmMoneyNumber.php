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

/**
 * Get a money amount formatted without a currency symbol.
 *
 * This allows the rounding and separators to be determined
 * by the currency & locale but without the addition of a currency
 * symbol.
 *
 * @param string|int|float $amount
 *   The monetary amount up for display.
 * @param string|null $currency
 *   The currency (optional, defaults to site default).
 * @param string|null $locale
 *   The locale  (optional, defaults to site default).
 *
 * @return string
 *   formatted number, using separators and rounding based on currency and locale.
 */
function smarty_modifier_crmMoneyNumber($amount, ?string $currency = NULL, ?string $locale = NULL): string {
  return Civi::format()->moneyNumber($amount, $currency, $locale);
}
