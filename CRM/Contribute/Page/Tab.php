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
class CRM_Contribute_Page_Tab extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;
  static $_recurLinks = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;
  public $_crid = NULL;

  /**
   * This method returns the links that are given for recur search row.
   * currently the links added for each row are:
   * - View
   * - Edit
   * - Cancel
   *
   * @param bool $recurID
   * @param string $context
   *
   * @return array
   */
  public static function &recurLinks($recurID = FALSE, $context = 'contribution') {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'title' => ts('View Recurring Payment'),
          'url' => 'civicrm/contact/view/contributionrecur',
          'qs' => "reset=1&id=%%crid%%&cid=%%cid%%&context={$context}",
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'title' => ts('Edit Recurring Payment'),
          'url' => 'civicrm/contribute/updaterecur',
          'qs' => "reset=1&action=update&crid=%%crid%%&cid=%%cid%%&context={$context}",
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Cancel'),
          'title' => ts('Cancel'),
          'ref' => 'crm-enable-disable',
        ),
      );
    }

    if ($recurID) {
      $links = self::$_links;
      $paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($recurID, 'recur', 'obj');
      if (is_object($paymentProcessorObj) && $paymentProcessorObj->supports('cancelRecurring')) {
        unset($links[CRM_Core_Action::DISABLE]['extra'], $links[CRM_Core_Action::DISABLE]['ref']);
        $links[CRM_Core_Action::DISABLE]['url'] = "civicrm/contribute/unsubscribe";
        $links[CRM_Core_Action::DISABLE]['qs'] = "reset=1&crid=%%crid%%&cid=%%cid%%&context={$context}";
      }

      if (is_object($paymentProcessorObj) && $paymentProcessorObj->isSupported('updateSubscriptionBillingInfo')) {
        $links[CRM_Core_Action::RENEW] = array(
          'name' => ts('Change Billing Details'),
          'title' => ts('Change Billing Details'),
          'url' => 'civicrm/contribute/updatebilling',
          'qs' => "reset=1&crid=%%crid%%&cid=%%cid%%&context={$context}",
        );
      }
      return $links;
    }

    return self::$_links;
  }

  /**
   * called when action is browse.
   *
   */
  public function browse() {
    // add annual contribution
    $annual = array();
    list($annual['count'],
      $annual['amount'],
      $annual['avg']
      ) = CRM_Contribute_BAO_Contribution::annual($this->_contactId);
    $this->assign('annual', $annual);

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contribute_Form_Search',
      ts('Contributions'),
      $this->_action,
      FALSE, FALSE, TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('cid', $this->_contactId);
    $controller->set('crid', $this->_crid);
    $controller->set('context', 'contribution');
    $controller->set('limit', 50);
    $controller->process();
    $controller->run();

    // add recurring block
    $this->addRecurringContributionsBlock();

    // enable/disable soft credit records for test contribution
    $isTest = 0;
    if (CRM_Utils_Request::retrieve('isTest', 'Positive', $this)) {
      $isTest = 1;
    }
    $this->assign('isTest', $isTest);

    $softCreditList = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->_contactId, NULL, $isTest);

    if (!empty($softCreditList)) {
      $softCreditTotals = array();

      list($softCreditTotals['amount'],
        $softCreditTotals['avg'],
        $softCreditTotals['currency'],
        $softCreditTotals['cancelAmount'] // to get cancel amount
        ) = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($this->_contactId, $isTest);

      $this->assign('softCredit', TRUE);
      $this->assign('softCreditRows', $softCreditList);
      $this->assign('softCreditTotals', $softCreditTotals);
    }

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId);
    }
  }

  /**
   * Get all the recurring contribution information and assign to the template
   */
  private function addRecurringContributionsBlock() {
    try {
      $contributionRecurResult = civicrm_api3('ContributionRecur', 'get', array(
        'contact_id' => $this->_contactId,
        'options' => array('limit' => 0, 'sort' => 'start_date ASC'),
      ));
      $recurContributions = CRM_Utils_Array::value('values', $contributionRecurResult);
    }
    catch (Exception $e) {
      $recurContributions = NULL;
    }

    if (!empty($recurContributions)) {
      foreach ($recurContributions as $recurId => $recurDetail) {
        $action = array_sum(array_keys($this->recurLinks($recurId)));
        // no action allowed if it's not active
        $recurContributions[$recurId]['is_active'] = (!CRM_Contribute_BAO_Contribution::isContributionStatusNegative($recurDetail['contribution_status_id']));

        // Get the name of the payment processor
        if (!empty($recurDetail['payment_processor_id'])) {
          $recurContributions[$recurId]['payment_processor'] = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessorName($recurDetail['payment_processor_id']);
        }
        // Get the label for the contribution status
        if (!empty($recurDetail['contribution_status_id'])) {
          $recurContributions[$recurId]['contribution_status'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $recurDetail['contribution_status_id']);
        }

        if ($recurContributions[$recurId]['is_active']) {
          $details = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recurContributions[$recurId]['id'], 'recur');
          $hideUpdate = $details->membership_id & $details->auto_renew;

          if ($hideUpdate) {
            $action -= CRM_Core_Action::UPDATE;
          }

          $recurContributions[$recurId]['action'] = CRM_Core_Action::formLink(self::recurLinks($recurId), $action,
            array(
              'cid' => $this->_contactId,
              'crid' => $recurId,
              'cxt' => 'contribution',
            ),
            ts('more'),
            FALSE,
            'contribution.selector.recurring',
            'Contribution',
            $recurId
          );
        }
      }
      // assign vars to templates
      $this->assign('action', $this->_action);
      $this->assign('recurRows', $recurContributions);
      $this->assign('recur', TRUE);
    }
  }

  /**
   * called when action is view.
   *
   * @return mixed
   */
  public function view() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contribute_Form_ContributionView',
      ts('View Contribution'),
      $this->_action
    );
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);

    return $controller->run();
  }

  /**
   * called when action is update or new.
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function edit() {
    // set https for offline cc transaction
    $mode = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);
    if ($mode == 'test' || $mode == 'live') {
      CRM_Utils_System::redirectToSSL();
    }

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contribute_Form_Contribution',
      'Create Contribution',
      $this->_action
    );
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);

    return $controller->run();
  }

  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($context == 'standalone') {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, empty($this->_id));
      if (empty($this->_contactId)) {
        $this->_contactId = civicrm_api3('contribution', 'getvalue', array(
            'id' => $this->_id,
            'return' => 'contact_id',
          ));
      }
      $this->assign('contactId', $this->_contactId);

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);
    }
    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit contributions')) {
      // demote to view since user does not have edit contrib rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    // check if we can process credit card contribs
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

    $this->setContext();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit();
    }
    else {
      $this->browse();
    }

    return parent::run();
  }

  public function setContext() {
    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric',
      $this, FALSE, 'search'
    );
    $compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);

    $searchContext = CRM_Utils_Request::retrieve('searchContext', 'String', $this);

    //swap the context.
    if ($context == 'search' && $compContext) {
      $context = $compContext;
    }
    else {
      $compContext = NULL;
    }

    // make sure we dont get tricked with a bad key
    // so check format
    if (!CRM_Core_Key::valid($qfKey)) {
      $qfKey = NULL;
    }

    $session = CRM_Core_Session::singleton();

    switch ($context) {
      case 'user':
        $url = CRM_Utils_System::url('civicrm/user', 'reset=1');
        break;

      case 'dashboard':
        $url = CRM_Utils_System::url('civicrm/contribute',
          'reset=1'
        );
        break;

      case 'pledgeDashboard':
        $url = CRM_Utils_System::url('civicrm/pledge',
          'reset=1'
        );
        break;

      case 'contribution':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$this->_contactId}&selectedChild=contribute"
        );
        break;

      case 'search':
      case 'advanced':
        $extraParams = "force=1";
        if ($qfKey) {
          $extraParams .= "&qfKey=$qfKey";
        }

        $this->assign('searchKey', $qfKey);
        if ($context == 'advanced') {
          $url = CRM_Utils_System::url('civicrm/contact/search/advanced', $extraParams);
        }
        elseif ($searchContext) {
          $url = CRM_Utils_System::url("civicrm/$searchContext/search", $extraParams);
        }
        else {
          $url = CRM_Utils_System::url('civicrm/contribute/search', $extraParams);
        }
        break;

      case 'home':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      case 'activity':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$this->_contactId}&selectedChild=activity"
        );
        break;

      case 'member':
      case 'membership':
        $componentId = CRM_Utils_Request::retrieve('compId', 'Positive', $this);
        $componentAction = CRM_Utils_Request::retrieve('compAction', 'Integer', $this);

        $context = 'membership';
        $searchKey = NULL;
        if ($compContext) {
          $context = 'search';
          if ($qfKey) {
            $searchKey = "&key=$qfKey";
          }
          $compContext = "&compContext={$compContext}";
        }
        if ($componentAction & CRM_Core_Action::VIEW) {
          $action = 'view';
        }
        else {
          $action = 'update';
        }
        $url = CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action={$action}&id={$componentId}&cid={$this->_contactId}&context={$context}&selectedChild=member{$searchKey}{$compContext}"
        );
        break;

      case 'participant':
        $componentId = CRM_Utils_Request::retrieve('compId', 'Positive', $this);
        $componentAction = CRM_Utils_Request::retrieve('compAction', 'Integer', $this);

        $context = 'participant';
        $searchKey = NULL;
        if ($compContext) {
          $context = 'search';
          if ($qfKey) {
            $searchKey = "&key=$qfKey";
          }
          $compContext = "&compContext={$compContext}";
        }
        if ($componentAction == CRM_Core_Action::VIEW) {
          $action = 'view';
        }
        else {
          $action = 'update';
        }
        $url = CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&action={$action}&id={$componentId}&cid={$this->_contactId}&context={$context}&selectedChild=event{$searchKey}{$compContext}"
        );
        break;

      case 'pledge':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$this->_contactId}&selectedChild=pledge"
        );
        break;

      case 'standalone':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      case 'fulltext':
        $keyName = '&qfKey';
        $urlParams = 'force=1';
        $urlString = 'civicrm/contact/search/custom';
        if ($this->_action == CRM_Core_Action::UPDATE) {
          if ($this->_contactId) {
            $urlParams .= '&cid=' . $this->_contactId;
          }
          $keyName = '&key';
          $urlParams .= '&context=fulltext&action=view';
          $urlString = 'civicrm/contact/view/contribution';
        }
        if ($qfKey) {
          $urlParams .= "$keyName=$qfKey";
        }
        $this->assign('searchKey', $qfKey);
        $url = CRM_Utils_System::url($urlString, $urlParams);
        break;

      default:
        $cid = NULL;
        if ($this->_contactId) {
          $cid = '&cid=' . $this->_contactId;
        }
        $url = CRM_Utils_System::url('civicrm/contribute/search',
          'reset=1&force=1' . $cid
        );
        break;
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

}
