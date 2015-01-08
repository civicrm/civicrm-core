<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Member_Page_Tab extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;
  static $_membershipTypesLinks = NULL;

  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * This function is called when action is browse
   *
   * return null
   * @access public
   */
  function browse() {
    $links = self::links('all', $this->_isPaymentProcessor, $this->_accessContribution);

    $membership = array();
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $this->_contactId;
    $dao->is_test = 0;
    //$dao->orderBy('name');
    $dao->find();

    //CRM--4418, check for view, edit, delete
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit memberships')) {
      $permissions[] = CRM_Core_Permission::EDIT;
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
      $membership[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $membership[$dao->id]);

      //carry campaign.
      $membership[$dao->id]['campaign'] = CRM_Utils_Array::value($dao->campaign_id, $allCampaigns);

      //get the membership status and type values.
      $statusANDType = CRM_Member_BAO_Membership::getStatusANDTypeValues($dao->id);
      foreach (array('status', 'membership_type') as $fld) {
        $membership[$dao->id][$fld] = CRM_Utils_Array::value($fld, $statusANDType[$dao->id]);
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
        $paymentObject = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity(
          $membership[$dao->id]['membership_id'], 'membership', 'obj');
        if (!empty($paymentObject)) {
          $isUpdateBilling = $paymentObject->isSupported('updateSubscriptionBillingInfo');
        }

        $isCancelSupported = CRM_Member_BAO_Membership::isCancelSubscriptionSupported(
          $membership[$dao->id]['membership_id']);

        $membership[$dao->id]['action'] = CRM_Core_Action::formLink(self::links('all',
            NULL,
            NULL,
            $isCancelSupported,
            $isUpdateBilling
          ),
          $currentMask,
          array(
            'id' => $dao->id,
            'cid' => $this->_contactId,
          ),
          ts('more'),
          FALSE,
          'membership.tab.row',
          'Membership',
          $dao->id
        );
      }
      else {
        $membership[$dao->id]['action'] = CRM_Core_Action::formLink(self::links('view'),
          $mask,
          array(
            'id' => $dao->id,
            'cid' => $this->_contactId,
          ),
          ts('more'),
          FALSE,
          'membership.tab.row',
          'Membership',
          $dao->id
        );
      }

      //does membership have auto renew CRM-7137.
      if (!empty($membership[$dao->id]['contribution_recur_id']) &&
        !CRM_Member_BAO_Membership::isSubscriptionCancelled($membership[$dao->id]['membership_id'])
      ) {
        $membership[$dao->id]['auto_renew'] = 1;
      }
      else {
        $membership[$dao->id]['auto_renew'] = 0;
      }

      // if relevant, count related memberships
      if (CRM_Utils_Array::value('is_current_member', $statusANDType[$dao->id]) // membership is active
        && CRM_Utils_Array::value('relationship_type_id', $statusANDType[$dao->id]) // membership type allows inheritance
        && empty($dao->owner_membership_id)
      ) { // not an related membership
        $query = "
 SELECT COUNT(m.id)
   FROM civicrm_membership m
     LEFT JOIN civicrm_membership_status ms ON ms.id = m.status_id
     LEFT JOIN civicrm_contact ct ON ct.id = m.contact_id
  WHERE m.owner_membership_id = {$dao->id} AND m.is_test = 0 AND ms.is_current_member = 1 AND ct.is_deleted = 0";
        $num_related = CRM_Core_DAO::singleValueQuery($query);
        $max_related = CRM_Utils_Array::value('max_related', $membership[$dao->id]);
        $membership[$dao->id]['related_count'] = ($max_related == '' ?
          ts('%1 created', array(1 => $num_related)) :
          ts('%1 out of %2', array(1 => $num_related, 2 => $max_related))
        );
      }
      else {
        $membership[$dao->id]['related_count'] = ts('N/A');
      }
    }

    //Below code gives list of all Membership Types associated
    //with an Organization(CRM-2016)
    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypesByOrg($this->_contactId);
    foreach ($membershipTypes as $key => $value) {
      $membershipTypes[$key]['action'] = CRM_Core_Action::formLink(self::membershipTypeslinks(),
        $mask,
        array(
          'id' => $value['id'],
          'cid' => $this->_contactId,
        ),
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
      // Refresh other tabs with related data
      $this->ajaxResponse['updateTabs'] = array(
        '#tab_activity' => CRM_Contact_BAO_Contact::getCountComponent('activity', $this->_contactId),
        '#tab_rel' => CRM_Contact_BAO_Contact::getCountComponent('rel', $this->_contactId),
      );
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->ajaxResponse['updateTabs']['#tab_contribute'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId);
      }
    }
  }

  /**
   * This function is called when action is view
   *
   * return null
   * @access public
   */
  function view() {
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
   * This function is called when action is update or new
   *
   * return null
   * @access public
   */
  function edit() {
    // set https for offline cc transaction
    $mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
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
          $filter = " AND cc.id IN (SELECT contribution_id FROM civicrm_membership_payment WHERE membership_id = {$this->_id})";
          $softCreditList = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->_contactId, $filter);
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

  function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($context == 'standalone') {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $this->assign('contactId', $this->_contactId);

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
   * This function is the main function that is called when the page loads, it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();

    // check if we can process credit card membership
    $newCredit = CRM_Core_Config::isEnabledBackOfficeCreditCardPayments();
    $this->assign('newCredit', $newCredit);

    if ($newCredit) {
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
      if ($this->_id && $this->_contactId) {
        $filter = " AND cc.id IN (SELECT contribution_id FROM civicrm_membership_payment WHERE membership_id = {$this->_id})";
        $softCreditList = CRM_Contribute_BAO_ContributionSoft::getSoftContributionList($this->_contactId, $filter);
        if (!empty($softCreditList)) {
          $this->assign('softCredit', TRUE);
          $this->assign('softCreditRows', $softCreditList);
        }
      }
    }
    else {
      $this->_accessContribution = FALSE;
      $this->assign('accessContribution', FALSE);
      $this->assign('softCredit', FALSE);
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE
        | CRM_Core_Action::RENEW)) {
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
   * @param $form
   * @param null $contactId
   */
  public static function setContext(&$form, $contactId = NULL) {
    $context = CRM_Utils_Request::retrieve('context', 'String', $form, FALSE, 'search' );

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
   * Get action links
   *
   * @param string $status
   * @param null $isPaymentProcessor
   * @param null $accessContribution
   * @param bool $isCancelSupported
   * @param bool $isUpdateBilling
   *
   * @return array (reference) of action links
   * @static
   */
  static function &links($status = 'all',
                         $isPaymentProcessor = NULL,
                         $accessContribution = NULL,
                         $isCancelSupported = FALSE,
                         $isUpdateBilling = FALSE
  ) {
    if (!CRM_Utils_Array::value('view', self::$_links)) {
      self::$_links['view'] = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('View Membership'),
        ),
      );
    }

    if (!CRM_Utils_Array::value('all', self::$_links)) {
      $extraLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Edit Membership'),
        ),
        CRM_Core_Action::RENEW => array(
          'name' => ts('Renew'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=renew&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Renew Membership'),
        ),
        CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('Renew-Credit Card'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=renew&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member&mode=live',
          'title' => ts('Renew Membership Using Credit Card'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&context=membership&selectedChild=member',
          'title' => ts('Delete Membership'),
        ),
      );
      if (!$isPaymentProcessor || !$accessContribution) {
        //unset the renew with credit card when payment
        //processor is not available or user is not permitted to create contributions
        unset($extraLinks[CRM_Core_Action::FOLLOWUP]);
      }
      self::$_links['all'] = self::$_links['view'] + $extraLinks;
    }


    if ($isCancelSupported) {
      $cancelMessage = ts('WARNING: If you cancel the recurring contribution associated with this membership, the membership will no longer be renewed automatically. However, the current membership status will not be affected.');
      self::$_links['all'][CRM_Core_Action::DISABLE] = array(
        'name' => ts('Cancel Auto-renewal'),
        'url' => 'civicrm/contribute/unsubscribe',
        'qs' => 'reset=1&cid=%%cid%%&mid=%%id%%&context=membership&selectedChild=member',
        'title' => ts('Cancel Auto Renew Subscription'),
        'extra' => 'onclick = "if (confirm(\'' . $cancelMessage . '\') ) {  return true; else return false;}"',
      );
    }
    elseif (isset(self::$_links['all'][CRM_Core_Action::DISABLE])) {
      unset(self::$_links['all'][CRM_Core_Action::DISABLE]);
    }

    if ($isUpdateBilling) {
      self::$_links['all'][CRM_Core_Action::MAP] = array(
        'name' => ts('Change Billing Details'),
        'url' => 'civicrm/contribute/updatebilling',
        'qs' => 'reset=1&cid=%%cid%%&mid=%%id%%&context=membership&selectedChild=member',
        'title' => ts('Change Billing Details'),
      );
    }
    elseif (isset(self::$_links['all'][CRM_Core_Action::MAP])) {
      unset(self::$_links['all'][CRM_Core_Action::MAP]);
    }
    return self::$_links[$status];
  }

  /**
   * Function to define action links for membership types of related organization
   *
   * @return array self::$_membershipTypesLinks array of action links
   * @access public
   */
  static function &membershipTypesLinks() {
    if (!self::$_membershipTypesLinks) {
      self::$_membershipTypesLinks = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('Members'),
          'url' => 'civicrm/member/search/',
          'qs' => 'reset=1&force=1&type=%%id%%',
          'title' => ts('Search'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/member/membershipType',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Type'),
        ),
      );
    }
    return self::$_membershipTypesLinks;
  }

  /**
   * This function is used for the to show the associated
   * contribution for the membership
   * @form array $form (ref.) an assoc array of name/value pairs
   * return null
   * @access public
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
    $controller->set('cid', $contactId);
    $controller->set('memberId', $membershipId);
    $controller->set('context', 'contribution');
    $controller->process();
    $controller->run();
  }

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Member_BAO_Membership';
  }
}

