<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Core_Page_AJAX_Payment {

  /**
   * Handle payment notification response
   */
  public static function ipn() {
    $paymentProcessorID = CRM_Utils_Request::retrieve('paymentProcessorID', 'Integer', CRM_Core_DAO::$_nullObject, TRUE);
    if (!$paymentProcessorID) {
      CRM_Utils_System::civiExit();
    }
    $paymentProcesors = civicrm_api3('PaymentProcessor', 'getsingle', array('id' => $paymentProcessorID));
    $payment = Civi\Payment\System::singleton()->getByProcessor($paymentProcesors);
    $payment->handlePaymentNotification();
  }

}
