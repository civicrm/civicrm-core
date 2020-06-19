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

use Brick\Money\Money;
use Brick\Money\Context\DefaultContext;
use Brick\Math\RoundingMode;

/**
 * Format the given monetary amount (and currency) for display
 *
 * @param float $amount
 *   The monetary amount up for display.
 * @param string $currency
 *   The (optional) currency.
 *
 * @return string
 *   formatted monetary amount
 *
 * @throws \CRM_Core_Exception
 */
function smarty_modifier_crmMoney($amount, $currency = NULL) {
  if (!$amount) {
    return $amount;
  }
  $currency = $currency ?? CRM_Core_Config::singleton()->defaultCurrency;
  $money = Money::of($amount, $currency, new DefaultContext(), RoundingMode::CEILING);
  $formatter = new \NumberFormatter(CRM_Core_I18n::singleton()->getLocale(), \NumberFormatter::CURRENCY);
  return $money->formatWith($formatter);
}
