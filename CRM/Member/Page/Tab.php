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
