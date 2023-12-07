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
 * Format the given monetary amount (and currency) for display
 *
 * @param string|int|float $amount
 *   The monetary amount up for display.
 * @param string|null $currency
 *   The (optional) currency.
 * @param string|null $locale
 *   The (optional) locale.
 *
 * @return string
 *   formatted monetary amount
 */
function smarty_modifier_crmMoney($amount, ?string $currency = NULL, ?string $locale = NULL): string {
  try {
    return Civi::format()->money($amount, $currency, $locale);
  }
  catch (CRM_Core_Exception $e) {
    // @todo escalate this to a deprecation notice. It turns out to be depressingly
    // common for us to double process amount strings - if they are > 1000 then
    // they wind up throwing an exception in the money function.
    // It would be more correct to format in the smarty layer, only.
    Civi::log()->warning('Invalid amount passed in as money - {money}', ['money' => $amount]);
    return $amount;
  }
}
