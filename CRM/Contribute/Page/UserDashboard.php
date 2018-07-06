<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Contribute_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * called when action is browse.
   */
  public function listContribution() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contribute_Form_Search',
      ts('Contributions'),
      NULL,
      FALSE, FALSE, TRUE, FALSE
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 12);
    $controller->set('cid', $this->_contactId);
    $controller->set('context', 'user');
    $controller->set('force', 1);
    $controller->process();
    $controller->run();

    //add honor block
    $params = CRM_Contribute_BAO_Contribution::getHonorContacts($this->_contactId);

    if (!empty($params)) {
      // assign vars to templates
      $this->assign('honorRows', $params);
      $this->assign('honor', TRUE);
    }

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->contact_id = $this->_contactId;
    $recur->is_test = 0;
    $recur->find();

    $config = CRM_Core_Config::singleton();

    $recurStatus = CRM_Contribute_PseudoConstant::contributionStatus();

    $recurRow = array();
    $recurIDs = array();
    while ($recur->fetch()) {
      $mode = $recur->is_test ? 'test' : 'live';
      $paymentProcessor = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessor($recur->id,
        $mode
      );
      if (!$paymentProcessor) {
        continue;
      }

      require_once 'api/v3/utils.php';
      //@todo calling api functions directly is not supported
      _civicrm_api3_object_to_array($recur, $values);

      $values['recur_status'] = $recurStatus[$values['contribution_status_id']];
      $recurRow[$values['id']] = $values;

      $action = array_sum(array_keys(CRM_Contribute_Page_Tab::recurLinks($recur->id, 'dashboard')));

      $details = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recur->id, 'recur');
      $hideUpdate = $details->membership_id & $details->auto_renew;

      if ($hideUpdate) {
        $action -= CRM_Core_Action::UPDATE;
      }

      $recurRow[$values['id']]['action'] = CRM_Core_Action::formLink(CRM_Contribute_Page_Tab::recurLinks($recur->id, 'dashboard'),
        $action, array(
          'cid' => $this->_contactId,
          'crid' => $values['id'],
          'cxt' => 'contribution',
        ),
        ts('more'),
        FALSE,
        'contribution.dashboard.recurring',
        'Contribution',
        $values['id']
      );

      $recurIDs[] = $values['id'];

      //reset $paymentObject for checking other paymenet processor
      //recurring url
      $paymentObject = NULL;
    }
    if (is_array($recurIDs) && !empty($recurIDs)) {
      $getCount = CRM_Contribute_BAO_ContributionRecur::getCount($recurIDs);
      foreach ($getCount as $key => $val) {
        $recurRow[$key]['completed'] = $val;
        $recurRow[$key]['link'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&recur=$key"
        );
      }
    }

    $this->assign('recurRows', $recurRow);
    if (!empty($recurRow)) {
      $this->assign('recur', TRUE);
    }
    else {
      $this->assign('recur', FALSE);
    }
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   */
  public function run() {
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    $defaultInvoicePage = CRM_Utils_Array::value('default_invoice_page', $invoiceSettings);
    $this->assign('invoicing', $invoicing);
    $this->assign('defaultInvoicePage', $defaultInvoicePage);
    parent::preProcess();
    $this->listContribution();
  }

}
