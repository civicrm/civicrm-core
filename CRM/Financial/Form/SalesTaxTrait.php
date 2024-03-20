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
   *
   * @deprecated since 5.69 will be removed around 5.80
   */
  public function assignSalesTaxTermToTemplate() {
    CRM_Core_Error::deprecatedFunctionWarning('assign the setting');
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
    if (!Civi::settings()->get('invoicing')) {
      return '';
    }
    return Civi::settings()->get('tax_term');
  }

  /**
   * Assign information to the template required for sales tax purposes.
   */
  public function assignSalesTaxMetadataToTemplate() {
    $this->assignSalesTaxRates();
    $this->assign('taxTerm', $this->getSalesTaxTerm());
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
    return $this->getTaxRatesForFinancialTypes()[$financialTypeID] ?? NULL;
  }

}
