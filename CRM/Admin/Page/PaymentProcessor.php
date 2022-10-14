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
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/paymentProcessor',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Payment Processor'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Payment Processor'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Payment Processor'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/paymentProcessor',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Payment Processor'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   */
  public function run() {
    // set title and breadcrumb
    CRM_Utils_System::setTitle(ts('Settings - Payment Processor'));
    //CRM-15546
    $paymentProcessorTypes = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_PaymentProcessor', 'payment_processor_type_id', array(
      'labelColumn' => 'name',
      'flip' => 1,
    ));
    $this->assign('defaultPaymentProcessorType', $paymentProcessorTypes['PayPal']);
    $breadCrumb = array(
      array(
        'title' => ts('Administration'),
        'url' => CRM_Utils_System::url('civicrm/admin',
          'reset=1'
        ),
      ),
    );
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
        CRM_Core_Session::setStatus(ts('No test processor entry exists for %1. Not having a test entry for each processor could cause problems', [$paymentProcessor['name']]));
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
