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
class CRM_Invoicing_Utils {

  /**
   * Function to call when invoicing is toggled on or off.
   *
   * We add or remove invoicing from the user dashboard here.
   *
   * @param bool $oldValue
   * @param bool $newValue
   * @param array $metadata
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function onToggle($oldValue, $newValue, $metadata) {
    if ($oldValue == $newValue) {
      return;
    }
    $existingUserViewOptions = civicrm_api3('Setting', 'get', ['return' => 'user_dashboard_options'])['values'][CRM_Core_Config::domainID()]['user_dashboard_options'];
    $optionValues = civicrm_api3('Setting', 'getoptions', ['field' => 'user_dashboard_options'])['values'];
    $invoiceKey = array_search('Invoices / Credit Notes', $optionValues);
    $existingIndex = array_search($invoiceKey, $existingUserViewOptions);

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
