<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Invoicing_Utils {

  /**
   * Function to call when invoicing is toggled on or off.
   *
   * We add or remove invoicing from the user dashboard here.
   *
   * @param bool $oldValue
   * @param bool $newValue
   * @param array $metadata
   */
  public static function onToggle($oldValue, $newValue, $metadata) {
    if ($oldValue == $newValue) {
      return;
    }
    $existingUserViewOptions = civicrm_api3('Setting', 'get', ['return' => 'user_dashboard_options'])['values'][CRM_Core_Config::domainID()]['user_dashboard_options'];
    $optionValues = civicrm_api3('Setting', 'getoptions', ['field' => 'user_dashboard_options'])['values'];
    $invoiceKey = array_search('Invoices / Credit Notes', $optionValues);
    $existingIndex = in_array($invoiceKey, $existingUserViewOptions);

    if ($newValue && $existingIndex === FALSE) {
      $existingUserViewOptions[] = $invoiceKey;
    }
    elseif (!$newValue && $existingIndex !== FALSE) {
      unset($existingUserViewOptions[$existingIndex]);
    }
    civicrm_api3('Setting', 'create', ['user_dashboard_options' => $existingUserViewOptions]);
  }

  /**
   * Function to call to determine if invoicing is enabled.
   *
   * Historically the invoicing was declared as a setting but actually
   * set within contribution_invoice_settings (which stores multiple settings
   * as an array in a non-standard way).
   *
   * We check both here. But will deprecate the latter in time.
   */
  public static function isInvoicingEnabled() {
    if (Civi::settings()->get('invoicing')) {
      return TRUE;
    }
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    return CRM_Utils_Array::value('invoicing', $invoiceSettings);
  }

  /**
   * Function to call to determine default invoice page.
   *
   * Historically the invoicing was declared as a setting but actually
   * set within contribution_invoice_settings (which stores multiple settings
   * as an array in a non-standard way).
   *
   * We check both here. But will deprecate the latter in time.
   */
  public static function getDefaultPaymentPage() {
    $value = Civi::settings()->get('default_invoice_page');
    if (is_numeric($value)) {
      return $value;
    }
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    return CRM_Utils_Array::value('default_invoice_page', $invoiceSettings);
  }

  /**
   * Function to get the tax term.
   *
   * The value is nested in the contribution_invoice_settings setting - which
   * is unsupported. Here we have a wrapper function to make later cleanup easier.
   */
  public static function getTaxTerm() {
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    return CRM_Utils_Array::value('tax_term', $invoiceSettings);
  }

}
