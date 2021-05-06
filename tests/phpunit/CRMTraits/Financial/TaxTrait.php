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
 * Trait TaxTrait
 *
 * Trait for working with tax in tests
 */
trait CRMTraits_Financial_TaxTrait {

  /**
   * Create the financial account for tax.
   *
   * @param string $key
   * @param array $params
   */
  public function createFinancialTaxAccount(string $key, array $params): void {
    $this->ids['FinancialAccount'][$key] = $this->callAPISuccess('FinancialAccount', 'create', array_merge([
      'name' => 'Test Tax financial account ',
      'contact_id' => $this->createLoggedInUser(),
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ], $params))['id'];
  }

  /**
   * Create a financial type with related sales tax config.
   *
   * @param string $key
   * @param array $financialTypeParams
   * @param array $financialAccountParams
   *
   * @throws \CRM_Core_Exception
   */
  public function createFinancialTypeWithSalesTax(string $key = 'taxable', array $financialTypeParams = [], array $financialAccountParams = []): void {
    $this->ids['FinancialType'][$key] = $this->callAPISuccess('FinancialType', 'create', array_merge([
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ], $financialTypeParams))['id'];
    $this->createFinancialTaxAccount($key, $financialAccountParams);
    $financialAccountParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->ids['FinancialType'][$key],
      'account_relationship' => 10,
      'financial_account_id' => $this->ids['FinancialAccount'][$key],
    ];
    CRM_Financial_BAO_FinancialTypeAccount::add($financialAccountParams);
  }

}
