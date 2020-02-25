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
trait CRM_Financial_Form_SalesTaxTrait {

  /**
   * Assign the sales tax term to the template.
   */
  public function assignSalesTaxTermToTemplate() {
    $this->assign('taxTerm', $this->getSalesTaxTerm());
  }

  /**
   * Assign sales tax rates to the template.
   */
  public function assignSalesTaxRates() {
    $this->assign('taxRates', json_encode(CRM_Core_PseudoConstant::getTaxRates()));
  }

  /**
   * Return the string to be assigned to the template for sales tax - e.g GST, VAT.
   *
   * @return string
   */
  public function getSalesTaxTerm() {
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    if (!$invoicing) {
      return '';
    }
    return CRM_Utils_Array::value('tax_term', $invoiceSettings);
  }

  /**
   * Assign information to the template required for sales tax purposes.
   */
  public function assignSalesTaxMetadataToTemplate() {
    $this->assignSalesTaxRates();
    $this->assignSalesTaxTermToTemplate();
  }

  /**
   * Get sales tax rates.
   *
   * @return array
   */
  public function getTaxRatesForFinancialTypes() {
    return CRM_Core_PseudoConstant::getTaxRates();
  }

  /**
   * @param int $financialTypeID
   *
   * @return string
   */
  public function getTaxRateForFinancialType($financialTypeID) {
    return CRM_Utils_Array::value($financialTypeID, $this->getTaxRatesForFinancialTypes());
  }

}
