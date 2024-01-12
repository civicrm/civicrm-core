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
   * @throws \CRM_Core_Exception
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
   * Use Civi::settings()->get('invoicing') instead.
   *
   * @deprecated since 5.68 expected removal time to be added when we add noisy deprecation.
   */
  public static function isInvoicingEnabled() {
    return Civi::settings()->get('invoicing');
  }

  /**
   * Function to get the tax term.
   *
   * Use Civi::settings()->get('tax_term') instead.
   *
   * @deprecated since 5.68 expected removal time to be added when we add noisy deprecation.
   */
  public static function getTaxTerm() {
    return Civi::settings()->get('tax_term');
  }

}
