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
class CRM_Event_Page_Tab extends CRM_Core_Page {

  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * called when action is browse.
   */
  public function browse() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Event_Form_Search',
      ts('Events'),
      $this->_action
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('cid', $this->_contactId);
    $controller->set('context', 'participant');
    $controller->process();
    $controller->run();

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('participant', $this->_contactId);
    }
  }

  /**
   * called when action is view.
   *
   * @return null
   */
  public function view() {
    // build associated contributions
    $this->associatedContribution();

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Event_Form_ParticipantView',
      ts('View Participant'),
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
   * @return null
   */
  public function edit() {
    // set https for offline cc transaction
    $mode = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);
    if ($mode == 'test' || $mode == 'live') {
      CRM_Utils_System::redirectToSSL();
    }

    if ($this->_action != CRM_Core_Action::ADD) {
      // get associated contributions only on edit/delete
      $this->associatedContribution();
    }

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Event_Form_Participant',
      'Create Participation',
      $this->_action
    );

    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);

    return $controller->run();
  }

  public function delete() {
    CRM_Core_Error::deprecatedFunctionWarning('use civicrm/participant/delete / CRM_Event_Form_Participant_Delete');
    CRM_Utils_System::redirect('civicrm/participant/delete', ['id' => CRM_Utils_Request::retrieveValue('id', NULL, TRUE), 'reset' => 1]);
  }

  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if (($context == 'standalone' || $context === 'search') && ($this->_action !== CRM_Core_Action::VIEW && $this->_action !== CRM_Core_Action::UPDATE)) {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $this->assign('contactId', $this->_contactId);

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);
    }

    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit event participants')) {
      // demote to view since user does not have edit event participants rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * the main function that is called when the page loads, it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    // check if we can process credit card registration
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

    // Only show credit card registration button if user has CiviContribute permission
    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->assign('accessContribution', TRUE);
    }
    else {
      $this->assign('accessContribution', FALSE);
    }

    $this->setContext();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit();
    }
    elseif ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::DETACH)) {
      $this->delete();
    }
    else {
      $this->browse();
    }

    return parent::run();
  }

  public function setContext() {
    $context = CRM_Utils_Request::retrieve('context',
      'String', $this, FALSE, 'search'
    );
    $compContext = CRM_Utils_Request::retrieve('compContext',
      'String', $this
    );

    $searchContext = CRM_Utils_Request::retrieve('searchContext', 'String', $this);

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);

    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    switch ($context) {
      case 'dashboard':
        $url = CRM_Utils_System::url('civicrm/event', 'reset=1');
        break;

      case 'search':
        $urlParams = 'force=1';
        if ($qfKey) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $this->assign('searchKey', $qfKey);

        if ($compContext == 'advanced') {
          $url = CRM_Utils_System::url('civicrm/contact/search/advanced', $urlParams);
        }
        elseif ($searchContext) {
          $url = CRM_Utils_System::url("civicrm/$searchContext/search", $urlParams);
        }
        else {
          $url = CRM_Utils_System::url('civicrm/event/search', $urlParams);
        }
        break;

      case 'user':
        $url = CRM_Utils_System::url('civicrm/user', 'reset=1');
        break;

      case 'participant':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$this->_contactId}&selectedChild=participant"
        );
        break;

      case 'home':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'force=1');
        break;

      case 'activity':
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&force=1&cid={$this->_contactId}&selectedChild=activity"
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
          $urlString = 'civicrm/contact/view/participant';
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
        $url = CRM_Utils_System::url('civicrm/event/search',
          'force=1' . $cid
        );
        break;
    }
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * used for the to show the associated
   * contribution for the participant
   */
  public function associatedContribution() {
    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->assign('accessContribution', TRUE);
      $controller = new CRM_Core_Controller_Simple(
        'CRM_Contribute_Form_Search',
        ts('Contributions'),
        NULL,
        FALSE, FALSE, TRUE
      );
      $controller->setEmbedded(TRUE);
      $controller->set('force', 1);
      $controller->set('skip_cid', TRUE);
      $controller->set('participantId', $this->_id);
      $controller->set('context', 'contribution');
      $controller->process();
      $controller->run();
    }
    else {
      $this->assign('accessContribution', FALSE);
    }
  }

}
