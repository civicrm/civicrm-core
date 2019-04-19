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
   */
  public function browse($action = NULL) {
    // get all custom groups sorted by weight
    $paymentProcessor = array();
    $dao = new CRM_Financial_DAO_PaymentProcessor();
    $dao->is_test = 0;
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $paymentProcessor[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $paymentProcessor[$dao->id]);
      $paymentProcessor[$dao->id]['payment_processor_type'] = CRM_Core_PseudoConstant::getLabel(
        'CRM_Financial_DAO_PaymentProcessor', 'payment_processor_type_id', $dao->payment_processor_type_id
      );

      // form all action links
      $action = array_sum(array_keys($this->links()));

      // update enable/disable links.
      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $paymentProcessor[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
        array('id' => $dao->id),
        ts('more'),
        FALSE,
        'paymentProcessor.manage.action',
        'PaymentProcessor',
        $dao->id
      );
      $paymentProcessor[$dao->id]['financialAccount'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($dao->id, NULL, 'civicrm_payment_processor', 'financial_account_id.name');

      try {
        $paymentProcessor[$dao->id]['test_id'] = CRM_Financial_BAO_PaymentProcessor::getTestProcessorId($dao->id);
      }
      catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Session::setStatus(ts('No test processor entry exists for %1. Not having a test entry for each processor could cause problems', [$dao->name]));
      }
    }

    $this->assign('rows', $paymentProcessor);
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
