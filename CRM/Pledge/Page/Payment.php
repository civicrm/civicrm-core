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
class CRM_Pledge_Page_Payment extends CRM_Core_Page {

  /**
   * the main function that is called when the page loads, it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    CRM_Pledge_Page_Tab::setContext($this);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->edit();
    }
    else {
      $pledgeId = CRM_Utils_Request::retrieve('pledgeId', 'Positive', $this);

      $paymentDetails = CRM_Pledge_BAO_PledgePayment::getPledgePayments($pledgeId);

      $this->assign('rows', $paymentDetails);
      $this->assign('pledgeId', $pledgeId);
      $this->assign('contactId', $this->_contactId);

      // check if we can process credit card contributions
      $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

      // check is the user has view/edit signer permission
      $permission = 'view';
      if (CRM_Core_Permission::check('edit pledges')) {
        $permission = 'edit';
      }
      $this->assign('permission', $permission);
    }

    return parent::run();
  }

  /**
   * called when action is update or new.
   *
   * @return null
   */
  public function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Pledge_Form_Payment',
      'Update Pledge Payment',
      $this->_action
    );

    $pledgePaymentId = CRM_Utils_Request::retrieve('ppId', 'Positive', $this);

    $controller->setEmbedded(TRUE);
    $controller->set('id', $pledgePaymentId);

    return $controller->run();
  }

}
