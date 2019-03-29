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
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'title' => ts('View Recurring Payment'),
          'url' => 'civicrm/contact/view/contributionrecur',
          'qs' => "reset=1&id=%%crid%%&cid=%%cid%%&context={$context}",
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'title' => ts('Edit Recurring Payment'),
          'url' => 'civicrm/contribute/updaterecur',
          'qs' => "reset=1&action=update&crid=%%crid%%&cid=%%cid%%&context={$context}",
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Cancel'),
          'title' => ts('Cancel'),
          'ref' => 'crm-enable-disable',
        ],
      ];
    }

    if ($recurID) {
      $links = self::$_links;
      $paymentProcessorObj = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessorObject($recurID);
      if (!$paymentProcessorObj) {
        unset($links[CRM_Core_Action::DISABLE]);
        unset($links[CRM_Core_Action::UPDATE]);
        return $links;
      }
      if ($paymentProcessorObj->supports('cancelRecurring')) {
        unset($links[CRM_Core_Action::DISABLE]['extra'], $links[CRM_Core_Action::DISABLE]['ref']);
        $links[CRM_Core_Action::DISABLE]['url'] = "civicrm/contribute/unsubscribe";
        $links[CRM_Core_Action::DISABLE]['qs'] = "reset=1&crid=%%crid%%&cid=%%cid%%&context={$context}";
      }

      if ($paymentProcessorObj->supports('UpdateSubscriptionBillingInfo')) {
        $links[CRM_Core_Action::RENEW] = [
          'name' => ts('Change Billing Details'),
          'title' => ts('Change Billing Details'),
          'url' => 'civicrm/contribute/updatebilling',
          'qs' => "reset=1&crid=%%crid%%&cid=%%cid%%&context={$context}",
        ];
      }

      if (!$paymentProcessorObj->supports('ChangeSubscriptionAmount') && !$paymentProcessorObj->supports('EditRecurringContribution')) {
        unset($links[CRM_Core_Action::UPDATE]);
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
    $annual = [];
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
      $softCreditTotals = [];

      list($softCreditTotals['count'],
        $softCreditTotals['cancel']['count'],
        $softCreditTotals['amount'],
        $softCreditTotals['avg'],
        $softCreditTotals['cancel']['amount'] // to get cancel amount
        ) = CRM_Contribute_BAO_ContributionSoft::getSoftContributionTotals($this->_contactId, $isTest);

      $this->assign('softCredit', TRUE);
      $this->assign('softCreditRows', $softCreditList);
      $this->assign('softCreditTotals', $softCreditTotals);
    }

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $tabCount = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId);
      $this->assign('tabCount', $tabCount);
      $this->ajaxResponse['tabCount'] = $tabCount;
    }
  }

  /**
   * Get all the recurring contribution information and assign to the template
   */
  private function addRecurringContributionsBlock() {
    list($activeContributions, $activeContributionsCount) = $this->getActiveRecurringContributions();
    list($inactiveRecurringContributions, $inactiveContributionsCount) = $this->getInactiveRecurringContributions();

    if (!empty($activeContributions) || !empty($inactiveRecurringContributions)) {
      // assign vars to templates
      $this->assign('action', $this->_action);
      $this->assign('activeRecurRows', $activeContributions);
      $this->assign('contributionRecurCount', $activeContributionsCount + $inactiveContributionsCount);
      $this->assign('inactiveRecurRows', $inactiveRecurringContributions);
      $this->assign('recur', TRUE);
    }
  }

  /**
   * Loads active recurring contributions for the current contact and formats
   * them to be used on the form.
   *
   * @return array;
   */
  private function getActiveRecurringContributions() {
    try {
      $contributionRecurResult = civicrm_api3('ContributionRecur', 'get', [
        'contact_id' => $this->_contactId,
        'contribution_status_id' => ['NOT IN' => CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses()],
        'options' => ['limit' => 0, 'sort' => 'is_test, start_date DESC'],
      ]);
      $recurContributions = CRM_Utils_Array::value('values', $contributionRecurResult);
    }
    catch (Exception $e) {
      $recurContributions = [];
    }

    return $this->buildRecurringContributionsArray($recurContributions);
  }

  /**
   * Loads inactive recurring contributions for the current contact and formats
   * them to be used on the form.
   *
   * @return array;
   */
  private function getInactiveRecurringContributions() {
    try {
      $contributionRecurResult = civicrm_api3('ContributionRecur', 'get', [
        'contact_id' => $this->_contactId,
        'contribution_status_id' => ['IN' => CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses()],
        'options' => ['limit' => 0, 'sort' => 'is_test, start_date DESC'],
      ]);
      $recurContributions = CRM_Utils_Array::value('values', $contributionRecurResult);
    }
    catch (Exception $e) {
      $recurContributions = NULL;
    }

    return $this->buildRecurringContributionsArray($recurContributions);
  }

  /**
   * @param $recurContributions
   *
   * @return mixed
   */
  private function buildRecurringContributionsArray($recurContributions) {
    $liveRecurringContributionCount = 0;
    foreach ($recurContributions as $recurId => $recurDetail) {
      // Is recurring contribution active?
      $recurContributions[$recurId]['is_active'] = !in_array(CRM_Contribute_PseudoConstant::contributionStatus($recurDetail['contribution_status_id'], 'name'), CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses());
      if ($recurContributions[$recurId]['is_active']) {
        $actionMask = array_sum(array_keys(self::recurLinks($recurId)));
      }
      else {
        $actionMask = CRM_Core_Action::mask([CRM_Core_Permission::VIEW]);
      }

      if (empty($recurDetail['is_test'])) {
        $liveRecurringContributionCount++;
      }

      // Get the name of the payment processor
      if (!empty($recurDetail['payment_processor_id'])) {
        $recurContributions[$recurId]['payment_processor'] = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessorName($recurDetail['payment_processor_id']);
      }
      // Get the label for the contribution status
      if (!empty($recurDetail['contribution_status_id'])) {
        $recurContributions[$recurId]['contribution_status'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $recurDetail['contribution_status_id']);
      }

      $recurContributions[$recurId]['action'] = CRM_Core_Action::formLink(self::recurLinks($recurId), $actionMask,
        [
          'cid' => $this->_contactId,
          'crid' => $recurId,
          'cxt' => 'contribution',
        ],
        ts('more'),
        FALSE,
        'contribution.selector.recurring',
        'Contribution',
        $recurId
      );
    }

    return [$recurContributions, $liveRecurringContributionCount];
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
        $this->_contactId = civicrm_api3('contribution', 'getvalue', [
            'id' => $this->_id,
            'return' => 'contact_id',
          ]);
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
