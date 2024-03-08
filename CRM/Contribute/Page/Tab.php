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
class CRM_Contribute_Page_Tab extends CRM_Core_Page {

  /**
   * The permission we have on this contact
   *
   * @var string
   */
  public $_permission = NULL;

  /**
   * The contact ID for the contributions we are acting on
   * @var int
   */
  public $_contactId = NULL;

  /**
   * The recurring contribution ID (if any)
   * @var int
   */
  public $_crid = NULL;

  /**
   * This method returns the links that are given for recur search row.
   * currently the links added for each row are:
   * - View
   * - Edit
   * - Cancel
   *
   * @param int $recurID
   * @param string $context
   *
   * @return array
   */
  public static function recurLinks(int $recurID, $context = 'contribution') {
    $paymentProcessorObj = Civi\Payment\System::singleton()->getById(CRM_Contribute_BAO_ContributionRecur::getPaymentProcessorID($recurID));
    $links = [
      CRM_Core_Action::VIEW => [
        'name' => ts('View'),
        'title' => ts('View Recurring Payment'),
        'url' => 'civicrm/contact/view/contributionrecur',
        'qs' => "reset=1&id=%%crid%%&cid=%%cid%%&context={$context}",
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
      ],
    ];

    // In case there extension which have recurring payment and then
    // extension is disabled and in that case payment object may be null
    // To avoid the fatal error, return with VIEW link.
    if (!is_object($paymentProcessorObj)) {
      return $links;
    }

    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($recurID);
    if (
      (CRM_Core_Permission::check('edit contributions') || $context !== 'contribution') &&
      ($paymentProcessorObj->supports('ChangeSubscriptionAmount')
        || $paymentProcessorObj->supports('EditRecurringContribution')
      )) {
      $links[CRM_Core_Action::UPDATE] = [
        'name' => ts('Edit'),
        'title' => ts('Edit Recurring Payment'),
        'url' => 'civicrm/contribute/updaterecur',
        'qs' => "reset=1&action=update&crid=%%crid%%&cid=%%cid%%&context={$context}",
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
      ];
    }

    $links[CRM_Core_Action::DISABLE] = [
      'name' => ts('Cancel'),
      'title' => ts('Cancel'),
      'url' => 'civicrm/contribute/unsubscribe',
      'qs' => 'reset=1&crid=%%crid%%&cid=%%cid%%&context=' . $context,
      'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
    ];

    if ($paymentProcessorObj->supports('UpdateSubscriptionBillingInfo')) {
      $links[CRM_Core_Action::RENEW] = [
        'name' => ts('Change Billing Details'),
        'title' => ts('Change Billing Details'),
        'url' => 'civicrm/contribute/updatebilling',
        'qs' => "reset=1&crid=%%crid%%&cid=%%cid%%&context={$context}",
        'weight' => 110,
      ];
    }
    if (!empty($templateContribution['id']) && $paymentProcessorObj->supportsEditRecurringContribution()) {
      // Use constant CRM_Core_Action::PREVIEW as there is no such thing as view template.
      // And reusing view will mangle the actions.
      $links[CRM_Core_Action::PREVIEW] = [
        'name' => ts('View Template'),
        'title' => ts('View Template Contribution'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => "reset=1&id={$templateContribution['id']}&cid=%%cid%%&action=view&context={$context}&force_create_template=1",
        'weight' => 120,
      ];
    }

    return $links;
  }

  /**
   * Get the recur links to return for self service.
   *
   * These are the links to present to a logged in user wishing
   * to service their own
   *
   * @param int $recurID
   *
   * @return array|array[]
   * @throws \CRM_Core_Exception
   */
  public static function selfServiceRecurLinks(int $recurID): array {
    $links = [];
    $paymentProcessorObj = Civi\Payment\System::singleton()->getById(CRM_Contribute_BAO_ContributionRecur::getPaymentProcessorID($recurID));
    // In case there extension which have recurring payment and then
    // extension is disabled and in that case payment object may be null
    // To avoid the fatal error, return with VIEW link.
    if (!is_object($paymentProcessorObj)) {
      return $links;
    }
    if ($paymentProcessorObj->supports('cancelRecurring')
      && $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'cancel')
    ) {
      $url = $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'cancel');
      $links[CRM_Core_Action::DISABLE] = [
        'url' => $url,
        'name' => ts('Cancel'),
        'title' => ts('Cancel'),
        // Only display on-site links in a popup.
        'class' => (stripos($url, 'http') !== FALSE) ? 'no-popup' : '',
        'weight' => -50,
      ];
    }

    if ($paymentProcessorObj->supports('UpdateSubscriptionBillingInfo')
      && $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'billing')
    ) {
      $url = $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'billing');
      $links[CRM_Core_Action::RENEW] = [
        'name' => ts('Change Billing Details'),
        'title' => ts('Change Billing Details'),
        'url' => $url,
        // Only display on-site links in a popup.
        'class' => (stripos($url, 'http') !== FALSE) ? 'no-popup' : '',
        'weight' => -15,
      ];
    }

    if (($paymentProcessorObj->supports('ChangeSubscriptionAmount')
    || $paymentProcessorObj->supports('EditRecurringContribution'))
    && $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'update')
    ) {
      $url = $paymentProcessorObj->subscriptionURL($recurID, 'recur', 'update');
      $links[CRM_Core_Action::UPDATE] = [
        'name' => ts('Edit'),
        'title' => ts('Edit Recurring Payment'),
        'url' => $url,
        // Only display on-site links in a popup.
        'class' => (stripos($url, 'http') !== FALSE) ? 'no-popup' : '',
        'weight' => -10,
      ];
    }
    return $links;
  }

  /**
   * Get recurring links appropriate to viewing a user dashboard.
   *
   * A contact should be able to see links appropriate to them (e.g
   * payment processor cancel page) if viewing their own dashboard and
   * links appropriate to the contact they are viewing, if they have
   * permission, if viewing another user.
   *
   * @param int $recurID
   * @param int $contactID
   *
   * @return array|array[]
   * @throws \CRM_Core_Exception
   */
  public static function dashboardRecurLinks(int $recurID, int $contactID): array {
    $links = [];
    if ($contactID && $contactID === CRM_Core_Session::getLoggedInContactID()) {
      $links = self::selfServiceRecurLinks($recurID);
    }
    $links += self::recurLinks($recurID, 'dashboard');
    return $links;
  }

  /**
   * called when action is browse.
   *
   */
  public function browse() {
    // add annual contribution
    $annual = [];
    [$annual['count'], $annual['amount'], $annual['avg']] = CRM_Contribute_BAO_Contribution::annual($this->_contactId);
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
        // to get cancel amount
        $softCreditTotals['cancel']['amount']
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
    [$activeContributions, $activeContributionsCount] = $this->getActiveRecurringContributions();
    [$inactiveRecurringContributions, $inactiveContributionsCount] = $this->getInactiveRecurringContributions();
    // assign vars to templates
    $this->assign('action', $this->_action);
    $this->assign('activeRecurRows', $activeContributions);
    $this->assign('contributionRecurCount', $activeContributionsCount + $inactiveContributionsCount);
    $this->assign('inactiveRecurRows', $inactiveRecurringContributions);
    $this->assign('recur', !empty($activeContributions) || !empty($inactiveRecurringContributions));
  }

  /**
   * Loads active recurring contributions for the current contact and formats
   * them to be used on the form.
   *
   * @return array
   */
  private function getActiveRecurringContributions() {
    try {
      $contributionRecurResult = civicrm_api3('ContributionRecur', 'get', [
        'contact_id' => $this->_contactId,
        'contribution_status_id' => ['NOT IN' => CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses()],
        'options' => ['limit' => 0, 'sort' => 'is_test, start_date DESC'],
      ]);
      $recurContributions = $contributionRecurResult['values'] ?? NULL;
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
   * @return array
   */
  private function getInactiveRecurringContributions() {
    try {
      $contributionRecurResult = civicrm_api3('ContributionRecur', 'get', [
        'contact_id' => $this->_contactId,
        'contribution_status_id' => ['IN' => CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses()],
        'options' => ['limit' => 0, 'sort' => 'is_test, start_date DESC'],
      ]);
      $recurContributions = $contributionRecurResult['values'] ?? NULL;
    }
    catch (Exception $e) {
      $recurContributions = NULL;
    }

    return $this->buildRecurringContributionsArray($recurContributions);
  }

  /**
   * @param $recurContributions
   *
   * @return array
   */
  private function buildRecurringContributionsArray($recurContributions) {
    $liveRecurringContributionCount = 0;
    foreach ($recurContributions as $recurId => $recurDetail) {
      // API3 does not return "installments" if it is not set. But we need it set to avoid PHP notices on ContributionRecurSelector.tpl
      $recurContributions[$recurId]['installments'] = $recurDetail['installments'] ?? NULL;
      $recurContributions[$recurId]['next_sched_contribution_date'] = $recurDetail['next_sched_contribution_date'] ?? NULL;
      $recurContributions[$recurId]['cancel_date'] = $recurDetail['cancel_date'] ?? NULL;
      $recurContributions[$recurId]['end_date'] = $recurDetail['end_date'] ?? NULL;
      // Is recurring contribution active?
      $recurContributions[$recurId]['is_active'] = !in_array(CRM_Contribute_PseudoConstant::contributionStatus($recurDetail['contribution_status_id'], 'name'), CRM_Contribute_BAO_ContributionRecur::getInactiveStatuses());
      if ($recurContributions[$recurId]['is_active']) {
        $actionMask = array_sum(array_keys(self::recurLinks((int) $recurId)));
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

      $recurContributions[$recurId]['action'] = CRM_Core_Action::formLink(self::recurLinks((int) $recurId), $actionMask,
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

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($context === 'standalone') {
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

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);
    }
    $this->assign('contactId', $this->_contactId);
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
   * @throws \CRM_Core_Exception
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

    parent::run();
  }

  /**
   * @throws \CRM_Core_Exception
   */
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
