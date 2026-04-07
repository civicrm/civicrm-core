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
   * Is pay later enabled on this form?
   *
   * @var bool
   */
  protected $isPayLater = FALSE;

  /**
   * The label for the pay later pseudoprocessor option.
   *
   * @var string
   */
  protected $payLaterLabel;

  /**
   * Is this a back office form
   *
   * @var bool
   */
  public $isBackOffice = FALSE;

  /**
   * The payment mode that we are in ("live" or "test")
   * This should be protected and retrieved via getPaymentMode() but it's accessed all over the place so we have to leave it public for now.
   *
   * @var string
   */
  public $_mode;

  /**
   * @return bool
   */
  public function isPayLater() {
    return $this->isPayLater;
  }

  /**
   * @param bool $isPayLater
   */
  public function setIsPayLater($isPayLater) {
    $this->isPayLater = $isPayLater;
  }

  /**
   * @return bool
   */
  public function getIsBackOffice() {
    return $this->isBackOffice;
  }

  /**
   * Get the payment mode ('live' or 'test')
   *
   * @return string
   */
  public function getPaymentMode() {
    return $this->_mode;
  }

  /**
   * Set the payment mode ('live' or 'test')
   */
  public function setPaymentMode() {
    $this->_mode = ($this->_action === CRM_Core_Action::PREVIEW) ? 'test' : 'live';
  }

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
   * @param array $tplLineItems
   */
  protected function alterLineItemsForTemplate(&$tplLineItems) {
    if (!\Civi::settings()->get('invoicing')) {
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
          $this->assign('taxTerm', \Civi::settings()->get('tax_term'));
          // Cast to float to display without trailing zero decimals
          $tplLineItems[$key][$k]['tax_rate'] = (float) $v['tax_rate'];
        }
      }
    }
  }

  /**
   * Assign line items to the template.
   *
   * @param array $tplLineItems
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
        $pps[$key] = $this->getPaymentProcessorTitle($processor);
      }
    }
    if ($this->getPayLaterLabel()) {
      $pps[0] = $this->getPayLaterLabel();
    }
    return $pps;
  }

  /**
   * Get the title of the payment processor to display to the user
   * Note: There is an identical function in CRM_Core_Payment
   *
   * @param array $processor
   *
   * @return string
   */
  protected function getPaymentProcessorTitle($processor) {
    return $processor['frontend_title'];
  }

  /**
   * Adds in either a set of radio buttons or hidden fields to contain the payment processors on a front end form
   */
  protected function addPaymentProcessorFieldsToForm() {
    $paymentProcessors = $this->getProcessors();
    $optAttributes = [];
    foreach ($paymentProcessors as $ppKey => $ppval) {
      if ($ppKey > 0) {
        $optAttributes[$ppKey]['class'] = 'payment_processor_' . strtolower(CRM_Utils_String::munge($this->_paymentProcessors[$ppKey]['payment_processor_type'], '-'));
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

  /**
   * @return bool
   */
  public function isTest(): bool {
    return $this->_action & CRM_Core_Action::PREVIEW;
  }

}
