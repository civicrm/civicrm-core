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

use Civi\Api4\PaymentProcessor;

/**
 * Page for displaying list of payment processors.
 */
class CRM_Admin_Page_PaymentProcessor extends CRM_Core_Page_Basic {

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Financial_BAO_PaymentProcessor';
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {

    $civiContribute = \Civi\Api4\Extension::get(FALSE)
      ->addWhere('status', '=', 'installed')
      ->addWhere('key', '=', 'civi_contribute')
      ->execute()
      ->first();

    if (!$civiContribute) {
      $extensionsAdminUrl = \Civi::url('backend://civicrm/admin/extensions?reset=1');
      \CRM_Core_Error::statusBounce(ts('You must enable CiviContribute before configuring Payment Processors'), $extensionsAdminUrl);
      return;
    }

    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - Payment Processor'));
    $breadCrumb = [
      [
        'title' => ts('Administer'),
        'url' => CRM_Utils_System::url('civicrm/admin',
          'reset=1'
        ),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    return parent::run();
  }

  /**
   * Browse all payment processors.
   *
   * @param null $action
   *
   * @throws \CRM_Core_Exception
   */
  public function browse($action = NULL): void {
    $paymentProcessors = PaymentProcessor::get(FALSE)
      ->addWhere('is_test', '=', 0)
      ->addWhere('domain_id', '=', CRM_Core_Config::domainID())
      ->setSelect(['id', 'name', 'description', 'title', 'is_active', 'is_default', 'payment_processor_type_id:label'])
      ->addOrderBy('name')->execute()->indexBy('id');

    foreach ($paymentProcessors as $paymentProcessorID => $paymentProcessor) {
      // Annoyingly Smarty can't handle the colon syntax (or a .)
      $paymentProcessors[$paymentProcessorID]['payment_processor_type'] = $paymentProcessor['payment_processor_type_id:label'];

      // form all action links
      $action = array_sum(array_keys($this->links()));
      $action -= $paymentProcessor['is_active'] ? CRM_Core_Action::ENABLE : CRM_Core_Action::DISABLE;

      $paymentProcessors[$paymentProcessorID]['action'] = CRM_Core_Action::formLink($this->links(), $action,
        ['id' => $paymentProcessorID],
        ts('more'),
        FALSE,
        'paymentProcessor.manage.action',
        'PaymentProcessor',
        $paymentProcessorID
      );
      $paymentProcessors[$paymentProcessorID]['financialAccount'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($paymentProcessorID, NULL, 'civicrm_payment_processor', 'financial_account_id.name');

      try {
        $paymentProcessors[$paymentProcessorID]['test_id'] = CRM_Financial_BAO_PaymentProcessor::getTestProcessorId($paymentProcessorID);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Session::setStatus(ts('No test processor entry exists for %1. Not having a test entry for each processor could cause problems', [1 => $paymentProcessor['name']]));
      }
    }

    $this->assign('rows', $paymentProcessors);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_PaymentProcessor';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Payment Processors';
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
    return 'civicrm/admin/paymentProcessor';
  }

}
