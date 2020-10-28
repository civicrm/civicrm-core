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
  return CRM_Utils_Money::format($amount, $currency);
}
