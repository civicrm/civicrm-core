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
 * This class holds functionality shared between various front end forms.
 */
trait CRM_Financial_Form_FrontEndPaymentFormTrait {

  /**
   * The label for the pay later pseudoprocessor option.
   *
   * @var string
   */
  protected $payLaterLabel;

  /**
   * @return string
   */
  public function getPayLaterLabel(): string {
    if ($this->payLaterLabel) {
      return $this->payLaterLabel;
    }
    return $this->get('payLaterLabel') ?? '';
  }

  /**
   * @param string $payLaterLabel
   */
  public function setPayLaterLabel(string $payLaterLabel) {
    $this->set('payLaterLabel', $payLaterLabel);
    $this->payLaterLabel = $payLaterLabel;
  }

  /**
   * Alter line items for template.
   *
   * This is an early cut of what will ideally eventually be a hooklike call to the
   * CRM_Invoicing_Utils class with a potential end goal of moving this handling to an extension.
   *
   * @param $tplLineItems
   */
  protected function alterLineItemsForTemplate(&$tplLineItems) {
    if (!CRM_Invoicing_Utils::isInvoicingEnabled()) {
      return;
    }
    // @todo this should really be the first time we are determining
    // the tax rates - we can calculate them from the financial_type_id
    // & amount here so we didn't need a deeper function to semi-get
    // them but not be able to 'format them right' because they are
    // potentially being used for 'something else'.
    // @todo invoicing code - please feel the hate. Also move this 'hook-like-bit'
    // to the CRM_Invoicing_Utils class.
    foreach ($tplLineItems as $key => $value) {
      foreach ($value as $k => $v) {
        if (isset($v['tax_rate']) && $v['tax_rate'] != '') {
          // These only need assigning once, but code is more readable with them here
          $this->assign('getTaxDetails', TRUE);
          $this->assign('taxTerm', CRM_Invoicing_Utils::getTaxTerm());
          // Cast to float to display without trailing zero decimals
          $tplLineItems[$key][$k]['tax_rate'] = (float) $v['tax_rate'];
        }
      }
    }
  }

  /**
   * Assign line items to the template.
   *
   * @param $tplLineItems
   */
  protected function assignLineItemsToTemplate($tplLineItems) {
    // @todo this should be a hook that invoicing code hooks into rather than a call to it.
    $this->alterLineItemsForTemplate($tplLineItems);
    $this->assign('lineItem', $tplLineItems);
  }

  /**
   * Get the configured processors, including the pay later processor.
   *
   * @return array
   */
  protected function getProcessors(): array {
    $pps = [];
    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $key => $processor) {
        $pps[$key] = $processor['title'] ?? $processor['name'];
      }
    }
    if ($this->getPayLaterLabel()) {
      $pps[0] = $this->getPayLaterLabel();
    }
    return $pps;
  }

  /**
   * Adds in either a set of radio buttons or hidden fields to contain the payment processors on a front end form
   */
  protected function addPaymentProcessorFieldsToForm() {
    $paymentProcessors = $this->getProcessors();
    $optAttributes = [];
    foreach ($paymentProcessors as $ppKey => $ppval) {
      if ($ppKey > 0) {
        $optAttributes[$ppKey]['class'] = 'payment_processor_' . strtolower($this->_paymentProcessors[$ppKey]['payment_processor_type']);
      }
      else {
        $optAttributes[$ppKey]['class'] = 'payment_processor_paylater';
      }
    }
    if (count($paymentProcessors) > 1) {
      $this->addRadio('payment_processor_id', ts('Payment Method'), $paymentProcessors,
        NULL, "&nbsp;", FALSE, $optAttributes
      );
    }
    elseif (!empty($paymentProcessors)) {
      $ppKeys = array_keys($paymentProcessors);
      $currentPP = array_pop($ppKeys);
      $this->addElement('hidden', 'payment_processor_id', $currentPP);
    }
  }

}
