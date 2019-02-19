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
