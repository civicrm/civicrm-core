<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Financial_Form_Payment extends CRM_Core_Form {
  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $this->_paymentProcessorID = CRM_Utils_Request::retrieve('processor_id', 'Integer', CRM_Core_DAO::$_nullObject,
      TRUE);

    $this->assignBillingType();

    // @todo - round about way to load it - just load as an object using civi\payment\system::getByProcessor
    $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->_paymentProcessorID, 'unused');
    CRM_Core_Payment_ProcessorForm::preProcess($this);

    self::addCreditCardJs();

    $this->assign('paymentProcessorID', $this->_paymentProcessorID);
  }

  public function buildQuickForm() {
    CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
  }

  /**
   * Add JS to show icons for the accepted credit cards
   */
  public static function addCreditCardJs() {
    $creditCardTypes = CRM_Core_Payment_Form::getCreditCardCSSNames();
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Core/BillingBlock.js', 10)
      // workaround for CRM-13634
      // ->addSetting(array('config' => array('creditCardTypes' => $creditCardTypes)));
      ->addScript('CRM.config.creditCardTypes = ' . json_encode($creditCardTypes) . ';');
  }

}
