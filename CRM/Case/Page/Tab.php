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

/**
 * This class handle case related functions.
 */
class CRM_Case_Page_Tab extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //validate case configuration.
    $configured = CRM_Case_BAO_Case::isCaseConfigured($this->_contactId);
    $this->assign('notConfigured', !$configured['configured']);
    $this->assign('allowToAddNewCase', $configured['allowToAddNewCase']);
    $this->assign('redirectToCaseAdmin', $configured['redirectToCaseAdmin']);
    if (!$configured['configured'] || $configured['redirectToCaseAdmin']) {
      return;
    }

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($this->_contactId) {
      $this->assign('contactId', $this->_contactId);
      // check logged in user permission
      if ($this->_id && ($this->_action & CRM_Core_Action::VIEW)) {
        //user might have special permissions to view this case, CRM-5666
        if (!CRM_Core_Permission::check('access all cases and activities')) {
          $userCases = CRM_Case_BAO_Case::getCases(FALSE, ['type' => 'any']);
          if (!array_key_exists($this->_id, $userCases)) {
            CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
          }
        }
      }
      else {
        CRM_Contact_Page_View::checkUserPermission($this);
      }
    }

    $activityTypes = CRM_Case_PseudoConstant::caseActivityType();

    $this->assign('openCaseId', $activityTypes['Open Case']['id']);
    $this->assign('changeCaseTypeId', $activityTypes['Change Case Type']['id']);
    $this->assign('changeCaseStatusId', $activityTypes['Change Case Status']['id']);
    $this->assign('changeCaseStartDateId', $activityTypes['Change Case Start Date']['id']);
  }

  /**
   * View details of a case.
   */
  public function view() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Case_Form_CaseView',
      'View Case',
      $this->_action,
      FALSE,
      FALSE,
      TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);
    $controller->run();

    $this->assign('caseId', $this->_id);
    $output = CRM_Core_Selector_Controller::SESSION;
    $selector = new CRM_Activity_Selector_Activity($this->_contactId, $this->_permission, FALSE, 'case');
    $controller = new CRM_Core_Selector_Controller(
        $selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        NULL,
        CRM_Core_Action::VIEW,
        $this,
        $output,
        NULL,
        $this->_id
      );

    $controller->setEmbedded(TRUE);

    $controller->run();
    $controller->moveFromSessionToTemplate();

    $this->assign('context', 'case');
  }

  /**
   * Called when action is browse.
   */
  public function browse() {

    $controller = new CRM_Core_Controller_Simple('CRM_Case_Form_Search', ts('Case'), CRM_Core_Action::BROWSE);
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 20);
    $controller->set('force', 1);
    $controller->set('context', 'case');
    $controller->process();
    $controller->run();

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('case', $this->_contactId);
    }
  }

  /**
   * called when action is update or new.
   *
   * @return null
   */
  public function edit() {
    $config = CRM_Core_Config::singleton();

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Case_Form_Case',
      'Open Case',
      $this->_action
    );

    $controller->setEmbedded(TRUE);

    return $controller->run();
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive');
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    if ($context == 'standalone' && !$contactID) {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      // we need to call parent preprocess only when we are viewing / editing / adding participant record
      $this->preProcess();
    }

    $this->assign('action', $this->_action);

    self::setContext($this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif (($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD |
          CRM_Core_Action::DELETE | CRM_Core_Action::RENEW
        )
      ) ||
      !empty($_POST)
    ) {
      $this->edit();
    }
    elseif ($this->_contactId) {
      $this->browse();
    }

    return parent::run();
  }

  /**
   * Get action links.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {

    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('Manage'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%',
          'class' => 'no-popup',
          'title' => ts('Manage Case'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%',
          'title' => ts('Delete Case'),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function setContext(&$form) {
    $context = $form->get('context');
    $url = NULL;

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $form);
    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    switch ($context) {
      case 'activity':
        if ($form->_contactId) {
          $url = CRM_Utils_System::url('civicrm/contact/view',
            "reset=1&force=1&cid={$form->_contactId}&selectedChild=activity"
          );
        }
        break;

      case 'dashboard':
        $url = CRM_Utils_System::url('civicrm/case', "reset=1");
        break;

      case 'search':
        $urlParams = 'force=1';
        if ($qfKey) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $url = CRM_Utils_System::url('civicrm/case/search', $urlParams);
        break;

      case 'dashlet':
      case 'dashletFullscreen':
      case 'home':
      case 'standalone':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      case 'fulltext':
        $action = CRM_Utils_Request::retrieve('action', 'String', $form);
        $urlParams = 'force=1';
        $urlString = 'civicrm/contact/search/custom';
        if ($action == CRM_Core_Action::RENEW) {
          if ($form->_contactId) {
            $urlParams .= '&cid=' . $form->_contactId;
          }
          $urlParams .= '&context=fulltext&action=view';
          $urlString = 'civicrm/contact/view/case';
        }
        if ($qfKey) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $url = CRM_Utils_System::url($urlString, $urlParams);
        break;

      default:
        if ($form->_contactId) {
          $url = CRM_Utils_System::url('civicrm/contact/view',
            "reset=1&force=1&cid={$form->_contactId}&selectedChild=case"
          );
        }
        break;
    }

    if ($url) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($url);
    }
  }

}
