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
 * Page for displaying list of payment processor types.
 */
class CRM_Admin_Page_PaymentProcessorType extends CRM_Core_Page_Basic {

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Financial_BAO_PaymentProcessorType';
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_PaymentProcessorType';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Payment Processor Type';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/paymentProcessorType';
  }

  /**
   * Get any properties that should always be present in each row (null if no value).
   *
   * @return array
   */
  protected function getExpectedRowProperties(): array {
    return ['class', 'description'];
  }

}
