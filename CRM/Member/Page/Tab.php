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
class CRM_Member_Page_Tab extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;
  public static $_membershipTypesLinks = NULL;

  public $_permission = NULL;

  /**
   * Contact ID.
   *
   * @var int
   *
   * @deprecated
   */
  public $_contactId;

  /**
   * @var bool
   */
  private $_isPaymentProcessor = FALSE;

  /**
   * @var bool
   */
  private $_accessContribution = FALSE;

  /**
   * called when action is browse.
   */
  public function browse() {
    $links = self::links('all', $this->_isPaymentProcessor, $this->_accessContribution);
    $membershipTypes = \Civi\Api4\MembershipType::get(TRUE)
      ->execute()
      ->column('name', 'id');
    $addWhere = "membership_type_id IN (0)";
    if (!empty($membershipTypes)) {
      $addWhere = "membership_type_id IN (" . implode(',', array_keys($membershipTypes)) . ")";
    }

    $membership = [];
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $this->_contactId;
    $dao->whereAdd($addWhere);
    $dao->orderBy('end_date DESC');
    $dao->find();

    //CRM--4418, check for view, edit, delete
    $permissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('edit memberships')) {
      $permissions[] = CRM_Core_Permission::EDIT;
      $linkButtons['add_membership'] = [
        'title' => ts('Add Membership'),
        'url' => 'civicrm/contact/view/membership',
        'qs' => "reset=1&action=add&cid={$this->_contactId}&context=membership",
        'icon' => 'fa-plus-circle',
        'accessKey' => 'N',
      ];
      if ($this->_accessContribution && CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
        $linkButtons['creditcard_membership'] = [
          'title' => ts('Submit Credit Card Membership'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => "reset=1&action=add&cid={$this->_contactId}&context=membership&mode=live",
          'icon' => 'fa-credit-card',
          'accessKey' => 'C',
        ];
      }
      $this->assign('linkButtons', $linkButtons ?? []);
    }
    if (CRM_Core_Permission::check('delete in CiviMember')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    // get deceased status id
    $allStatus = CRM_Member_PseudoConstant::membershipStatus();
    $deceasedStatusId = array_search('Deceased', $allStatus);

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    //checks membership of contact itself
    while ($dao->fetch()) {
      $membership[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $membership[$dao->id]);

      //carry campaign.
      $membership[$dao->id]['campaign'] = $allCampaigns[$dao->campaign_id] ?? NULL;

      //get the membership status and type values.
      $statusANDType = CRM_Member_BAO_Membership::getStatusANDTypeValues($dao->id);
      foreach (['status', 'membership_type'] as $fld) {
        $membership[$dao->id][$fld] = $statusANDType[$dao->id][$fld] ?? NULL;
      }
      if (!empty($statusANDType[$dao->id]['is_current_member'])) {
        $membership[$dao->id]['active'] = TRUE;
      }
      if (empty($dao->owner_membership_id)) {
        // unset renew and followup link for deceased membership
        $currentMask = $mask;
        if ($dao->status_id == $deceasedStatusId) {
          $currentMask = $currentMask & ~CRM_Core_Action::RENEW & ~CRM_Core_Action::FOLLOWUP;
        }

        $isUpdateBilling = FALSE;
        // It would be better to determine if there is a recurring contribution &
        // is so get the entity for the recurring contribution (& skip if not).
        $paymentObject = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity(
          $membership[$dao->id]['membership_id'], 'membership', 'obj');
        if (!empty($paymentObject)) {
          $isUpdateBilling = $paymentObject->supports('updateSubscriptionBillingInfo');
        }

        // @todo - get this working with syntax style $paymentObject->supports(array
        //('CancelSubscriptionSupported'));
        $isCancelSupported = CRM_Member_BAO_Membership::isCancelSubscriptionSupported(
          $membership[$dao->id]['membership_id']);
        $links = self::links('all',
            FALSE,
            FALSE,
            $isCancelSupported,
            $isUpdateBilling
        );
        self::getPermissionedLinks($dao->membership_type_id, $links);
        $membership[$dao->id]['action'] = CRM_Core_Action::formLink($links,
          $currentMask,
          [
            'id' => $dao->id,
            'cid' => $this->_contactId,
          ],
          ts('Renew') . '...',
          FALSE,
          'membership.tab.row',
          'Membership',
          $dao->id
        );
      }
      else {
        $links = self::links('view');
        self::getPermissionedLinks($dao->membership_type_id, $links);
        $membership[$dao->id]['action'] = CRM_Core_Action::formLink($links,
          $mask,
          [
            'id' => $dao->id,
            'cid' => $this->_contactId,
          ],
          ts('more'),
          FALSE,
          'membership.tab.row',
          'Membership',
          $dao->id
        );
      }

      // Display Auto-renew status on page (0=disabled, 1=enabled, 2=enabled, but error
      if (!empty($membership[$dao->id]['contribution_recur_id'])) {
        if (CRM_Member_BAO_Membership::isSubscriptionCancelled((int) $membership[$dao->id]['membership_id'])) {
          $membership[$dao->id]['auto_renew'] = 2;
        }
        else {
          $membership[$dao->id]['auto_renew'] = 1;
        }
      }
      else {
        $membership[$dao->id]['auto_renew'] = 0;
      }

      // if relevant--membership is active and type allows inheritance--count related memberships
      if (!empty($statusANDType[$dao->id]['is_current_member'])
        && !empty($statusANDType[$dao->id]['relationship_type_id'])
        && empty($dao->owner_membership_id)
      ) {
        // not an related membership
        $num_related = \Civi\Api4\Membership::get(FALSE)
          ->selectRowCount()
          ->addJoin('MembershipStatus AS membership_status', 'LEFT')
          ->addWhere('owner_membership_id', '=', $dao->id)
          ->addWhere('is_test', '=', FALSE)
          ->addWhere('membership_status.is_current_member', '=', TRUE)
          ->addWhere('contact_id.is_deleted', '=', FALSE)
          ->execute()
          ->count();

        $max_related = $membership[$dao->id]['max_related'] ?? NULL;
        $membership[$dao->id]['related_count'] = ($max_related == '' ? ts('%1 created', [1 => $num_related]) : ts('%1 out of %2', [
          1 => $num_related,
          2 => $max_related,
        ]));
      }
      else {
        $membership[$dao->id]['related_count'] = ts('N/A');
      }
    }

    //Below code gives list of all Membership Types associated
    //with an Organization(CRM-2016)
    $membershipTypesResult = civicrm_api3('MembershipType', 'get', [
      'member_of_contact_id' => $this->_contactId,
      'options' => [
        'limit' => 0,
      ],
    ]);
    $membershipTypes = $membershipTypesResult['values'] ?? NULL;

    foreach ($membershipTypes as $key => $value) {
      $membershipTypes[$key]['action'] = CRM_Core_Action::formLink(self::membershipTypeslinks(),
        $mask,
        [
          'id' => $value['id'],
          'cid' => $this->_contactId,
        ],
        ts('more'),
        FALSE,
        'membershipType.organization.action',
        'MembershipType',
        $value['id']
      );
    }

    $activeMembers = CRM_Member_BAO_Membership::activeMembers($membership);
    $inActiveMembers = CRM_Member_BAO_Membership::activeMembers($membership, 'inactive');
    $this->assign('activeMembers', $activeMembers);
    $this->assign('inActiveMembers', $inActiveMembers);
    $this->assign('membershipTypes', $membershipTypes);

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactId);
    }
  }

  /**
   * called when action is view.
   *
   * @return mixed
   */
  public function view() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Member_Form_MembershipView',
      ts('View Membership'),
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
   */
  public function edit() {
    // We're trying to edit existing memberships or create a new one so we'll first check that a membership
    // type is configured and active, if we don't do this we instead show a permissions error and status bounce.
    $membershipTypes = \Civi\Api4\MembershipType::get(TRUE)
      ->addWhere('is_active', '=', TRUE)
      // we only need one, more is great but a single result lets us proceed!
      ->setLimit(1)
      ->execute();
    if (empty($membershipTypes)) {
      CRM_Core_Error::statusBounce(ts('You do not appear to have any active membership types configured, please add an active membership type and try again.'));
    }

    // set https for offline cc transaction
    $mode = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);
    if ($mode == 'test' || $mode == 'live') {
      CRM_Utils_System::redirectToSSL();
    }

    // build associated contributions ( note: this is called to show associated contributions in edit mode )
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->assign('accessContribution', FALSE);
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->assign('accessContribution', TRUE);
        CRM_Member_Page_Tab::associatedContribution($this->_contactId, $this->_id);

        //show associated soft credit when contribution payment is paid by different person in edit mode
        if ($this->_id && $this->_contactId) {
          $softCreditList = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->_contactId, $this->_id);
          if (!empty($softCreditList)) {
            $this->assign('softCredit', TRUE);
            $this->assign('softCreditRows', $softCreditList);
          }
        }
      }
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $path = 'CRM_Member_Form_MembershipRenewal';
      $title = ts('Renew Membership');
    }
    else {
      $path = 'CRM_Member_Form_Membership';
      $title = ts('Create Membership');
    }

    $controller = new CRM_Core_Controller_Simple($path, $title, $this->_action);
    $controller->setEmbedded(TRUE);
    $controller->set('BAOName', $this->getBAOName());
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
      $contactID = $this->getContactID();
      $this->assign('contactId', $contactID);
      CRM_Contact_Form_Inline::renderFooter($contactID, FALSE);

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);
    }

    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit memberships')) {
      // demote to view since user does not have edit membership rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * the main function that is called when the page loads, it decides the which
   * action has to be taken for the page.
   *
   * @return null
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $this->preProcess();

    // check if we can process credit card membership
    if (CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
      $this->_isPaymentProcessor = TRUE;
    }
    else {
      $this->_isPaymentProcessor = FALSE;
    }

    // Only show credit card membership signup if user has CiviContribute permission
    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->_accessContribution = TRUE;
      $this->assign('accessContribution', TRUE);

      //show associated soft credit when contribution payment is paid by different person
      $softCreditList = ($this->_id && $this->_contactId) ? CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->_contactId, $this->_id) : FALSE;
      $this->assign('softCredit', (bool) $softCreditList);
      $this->assign('softCreditRows', $softCreditList);
    }
    else {
      $this->_accessContribution = FALSE;
      $this->assign('accessContribution', FALSE);
      $this->assign('softCredit', FALSE);
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE | CRM_Core_Action::RENEW)) {
      self::setContext($this);
      $this->edit();
    }
    else {
      self::setContext($this);
      $this->browse();
    }

    return parent::run();
  }

  /**
   * @param CRM_Core_Form $form
   * @param int $contactId
   */
  public static function setContext(&$form, $contactId = NULL) {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $form, FALSE, 'search');

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $form);

    $searchContext = CRM_Utils_Request::retrieve('searchContext', 'String', $form);

    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    if (!$contactId) {
      $contactId = $form->_contactId;
    }

    switch ($context) {
      case 'dashboard':
        $url = CRM_Utils_System::url('civicrm/member', 'reset=1');
        break;

      case 'membership':
        $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$contactId}&selectedChild=member");
        break;

      case 'search':
        $urlParams = 'force=1';
        if ($qfKey) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $form->assign('searchKey', $qfKey);

        if ($searchContext) {
          $url = CRM_Utils_System::url("civicrm/$searchContext/search", $urlParams);
        }
        else {
          $url = CRM_Utils_System::url('civicrm/member/search', $urlParams);
        }
        break;

      case 'home':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      case 'activity':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$contactId}&selectedChild=activity"
        );
        break;

      case 'standalone':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      case 'fulltext':
        $action = CRM_Utils_Request::retrieve('action', 'String', $form);
        $keyName = '&qfKey';
        $urlParams = 'force=1';
        $urlString = 'civicrm/contact/search/custom';
        if ($action == CRM_Core_Action::UPDATE) {
          if ($form->_contactId) {
            $urlParams .= '&cid=' . $form->_contactId;
          }
          $keyName = '&key';
          $urlParams .= '&context=fulltext&action=view';
          $urlString = 'civicrm/contact/view/membership';
        }
        if ($qfKey) {
          $urlParams .= "$keyName=$qfKey";
        }
        $form->assign('searchKey', $qfKey);
        $url = CRM_Utils_System::url($urlString, $urlParams);
        break;

      default:
        $cid = NULL;
        if ($contactId) {
          $cid = '&cid=' . $contactId;
        }
        $url = CRM_Utils_System::url('civicrm/member/search', 'force=1' . $cid);
        break;
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * Get action links.
   *
   * @param string $status
   * @param bool $isPaymentProcessor
   * @param bool $accessContribution
   * @param bool $isCancelSupported
   * @param bool $isUpdateBilling
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links(
    $status = 'all',
    $isPaymentProcessor = FALSE,
    $accessContribution = FALSE,
    $isCancelSupported = FALSE,
    $isUpdateBilling = FALSE
  ) {
    if (empty(self::$_links['view'])) {
      self::$_links['view'] = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('View Membership'),
          // The constants are a bit backward - VIEW comes after UPDATE
          'weight' => 2,
        ],
      ];
    }

    if (empty(self::$_links['all'])) {
      $extraLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Edit Membership'),
          // The constants are a bit backward - VIEW comes after UPDATE
          'weight' => 4,
        ],
        CRM_Core_Action::RENEW => [
          'name' => ts('Renew'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=renew&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Renew Membership'),
          'weight' => CRM_Core_Action::RENEW,
        ],
        CRM_Core_Action::FOLLOWUP => [
          'name' => ts('Renew-Credit Card'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=renew&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member&mode=live',
          'title' => ts('Renew Membership Using Credit Card'),
          'weight' => CRM_Core_Action::FOLLOWUP,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Delete Membership'),
          'weight' => CRM_Core_Action::DELETE,
        ],
      ];
      if (!$isPaymentProcessor || !$accessContribution) {
        //unset the renew with credit card when payment
        //processor is not available or user is not permitted to create contributions
        unset($extraLinks[CRM_Core_Action::FOLLOWUP]);
      }
      self::$_links['all'] = self::$_links['view'] + $extraLinks;
    }

    if ($isCancelSupported) {
      $cancelMessage = ts('WARNING: If you cancel the recurring contribution associated with this membership, the membership will no longer be renewed automatically. However, the current membership status will not be affected.');
      self::$_links['all'][CRM_Core_Action::DISABLE] = [
        'name' => ts('Cancel Auto-renewal'),
        'url' => 'civicrm/contribute/unsubscribe',
        'qs' => 'reset=1&cid=%%cid%%&mid=%%id%%&context=membership&selectedChild=member',
        'title' => ts('Cancel Auto Renew Subscription'),
        'extra' => 'onclick = "if (confirm(\'' . $cancelMessage . '\') ) {  return true; else return false;}"',
        'weight' => CRM_Core_Action::DISABLE,
      ];
    }
    elseif (isset(self::$_links['all'][CRM_Core_Action::DISABLE])) {
      unset(self::$_links['all'][CRM_Core_Action::DISABLE]);
    }

    if ($isUpdateBilling) {
      self::$_links['all'][CRM_Core_Action::MAP] = [
        'name' => ts('Change Billing Details'),
        'url' => 'civicrm/contribute/updatebilling',
        'qs' => 'reset=1&cid=%%cid%%&mid=%%id%%&context=membership&selectedChild=member',
        'title' => ts('Change Billing Details'),
        'weight' => CRM_Core_Action::MAP,
      ];
    }
    elseif (isset(self::$_links['all'][CRM_Core_Action::MAP])) {
      unset(self::$_links['all'][CRM_Core_Action::MAP]);
    }
    return self::$_links[$status];
  }

  /**
   * Define action links for membership types of related organization.
   *
   * @return array
   *   self::$_membershipTypesLinks array of action links
   */
  public static function &membershipTypesLinks() {
    if (!self::$_membershipTypesLinks) {
      self::$_membershipTypesLinks = [
        CRM_Core_Action::VIEW => [
          'name' => ts('Members'),
          'url' => 'civicrm/member/search/',
          'qs' => 'reset=1&force=1&type=%%id%%',
          'title' => ts('Search'),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/member/membershipType',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Type'),
        ],
      ];
    }
    return self::$_membershipTypesLinks;
  }

  /**
   * used for the to show the associated.
   * contribution for the membership
   *
   * @param int $contactId
   * @param int $membershipId
   */
  public static function associatedContribution($contactId = NULL, $membershipId = NULL) {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contribute_Form_Search',
      ts('Contributions'),
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('force', 1);
    $controller->set('skip_cid', TRUE);
    $controller->set('memberId', $membershipId);
    $controller->set('context', 'contribution');
    $controller->process();
    $controller->run();
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Member_BAO_Membership';
  }

  /**
   * Get a list of links based on permissioned FTs.
   *
   * @param int $memTypeID
   *   membership type ID
   * @param array $links
   *   (reference) action links
   */
  public static function getPermissionedLinks($memTypeID, &$links) {
    if (!CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    $finTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $memTypeID, 'financial_type_id');
    $finType = CRM_Contribute_PseudoConstant::financialType($finTypeId);
    if (!CRM_Core_Permission::check('edit contributions of type ' . $finType)) {
      unset($links[CRM_Core_Action::UPDATE]);
      unset($links[CRM_Core_Action::RENEW]);
      unset($links[CRM_Core_Action::FOLLOWUP]);
    }
    if (!CRM_Core_Permission::check('delete contributions of type ' . $finType)) {
      unset($links[CRM_Core_Action::DELETE]);
    }
  }

  /**
   * Get the contact ID.
   *
   * @api Supported for external use.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getContactID(): ?int {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    return $this->_contactId;
  }

}
