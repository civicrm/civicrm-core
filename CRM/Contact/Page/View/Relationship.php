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
class CRM_Contact_Page_View_Relationship extends CRM_Core_Page {

  use CRM_Core_Page_EntityPageTrait;

  /**
   * Casid set if called from case context.
   *
   * @var int
   */
  public $_caseId = NULL;

  /**
   * @param int $caseId
   */
  public function setCaseId($caseId) {
    $this->_caseId = $caseId;
  }

  /**
   * @return int
   */
  public function getCaseId() {
    return $this->_caseId;
  }

  /**
   * Explicitly declare the entity api name.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Relationship';
  }

  /**
   * Explicitly declare the form context.
   *
   * @return string|null
   */
  public function getDefaultContext() {
    return 'search';
  }

  /**
   * View details of a relationship.
   */
  public function view() {
    $viewRelationship = CRM_Contact_BAO_Relationship::getRelationship($this->getContactId(), NULL, NULL, NULL, $this->getEntityId());
    //To check whether selected contact is a contact_id_a in
    //relationship type 'a_b' in relationship table, if yes then
    //revert the permissionship text in template
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->id = $viewRelationship[$this->getEntityId()]['id'];

    if ($relationship->find(TRUE)) {
      if (($viewRelationship[$this->getEntityId()]['rtype'] == 'a_b') && ($this->getContactId() == $relationship->contact_id_a)) {
        $this->assign("is_contact_id_a", TRUE);
      }
    }
    $relType = $viewRelationship[$this->getEntityId()]['civicrm_relationship_type_id'];
    $this->assign('viewRelationship', $viewRelationship);

    $employerId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->getContactId(), 'employer_id');
    $this->assign('isCurrentEmployer', FALSE);

    $relTypes = CRM_Utils_Array::index(array('name_a_b'), CRM_Core_PseudoConstant::relationshipType('name'));

    if ($viewRelationship[$this->getEntityId()]['employer_id'] == $this->getContactId()) {
      $this->assign('isCurrentEmployer', TRUE);
    }
    elseif ($relType == $relTypes['Employee of']['id'] &&
      ($viewRelationship[$this->getEntityId()]['cid'] == $employerId)
    ) {
      // make sure we are viewing employee of relationship
      $this->assign('isCurrentEmployer', TRUE);
    }

    $viewNote = CRM_Core_BAO_Note::getNote($this->getEntityId());
    $this->assign('viewNote', $viewNote);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Relationship', NULL, $this->getEntityId(), 0, $relType);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->getEntityId());

    $rType = CRM_Utils_Array::value('rtype', $viewRelationship[$this->getEntityId()]);
    // add viewed contribution to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/rel',
      "action=view&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&context=home"
    );

    $session = CRM_Core_Session::singleton();
    $recentOther = array();

    if (($session->get('userID') == $this->getContactId()) ||
      CRM_Contact_BAO_Contact_Permission::allow($this->getContactId(), CRM_Core_Permission::EDIT)
    ) {
      $recentOther = array(
        'editUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=update&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&rtype={$rType}&context=home"
        ),
        'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=delete&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&rtype={$rType}&context=home"
        ),
      );
    }

    $displayName = CRM_Contact_BAO_Contact::displayName($this->getContactId());
    $this->assign('displayName', $displayName);
    CRM_Utils_System::setTitle(ts('View Relationship for') . ' ' . $displayName);

    $title = $displayName . ' (' . $viewRelationship[$this->getEntityId()]['relation'] . ' ' . CRM_Contact_BAO_Contact::displayName($viewRelationship[$this->getEntityId()]['cid']) . ')';

    // add the recently viewed Relationship
    CRM_Utils_Recent::add($title,
      $url,
      $viewRelationship[$this->getEntityId()]['id'],
      'Relationship',
      $this->getContactId(),
      NULL,
      $recentOther
    );
  }

  /**
   * called when action is browse.
   *
   */
  public function browse() {
    // do nothing :) we are using datatable for rendering relationship selectors
    $columnHeaders = CRM_Contact_BAO_Relationship::getColumnHeaders();
    $contactRelationships = $selector = NULL;
    CRM_Utils_Hook::searchColumns('relationship.columns', $columnHeaders, $contactRelationships, $selector);
    $this->assign('columnHeaders', $columnHeaders);
  }

  /**
   * called when action is update or new.
   *
   */
  public function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Relationship', ts('Contact Relationships'), $this->getAction());
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();

    // if this is called from case view, we need to redirect back to same page
    if ($this->getCaseId()) {
      $url = CRM_Utils_System::url('civicrm/contact/view/case', "action=view&reset=1&cid={$this->getContactId()}&id={$this->getCaseId()}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view', "action=browse&selectedChild=rel&reset=1&cid={$this->getContactId()}");
    }

    $session->pushUserContext($url);

    if (CRM_Utils_Request::retrieve('confirmed', 'Boolean')) {
      if ($this->getCaseId()) {
        //create an activity for case role removal.CRM-4480
        CRM_Case_BAO_Case::createCaseRoleActivity($this->getCaseId(), $this->getEntityId());
        CRM_Core_Session::setStatus(ts('Case Role has been deleted successfully.'), ts('Record Deleted'), 'success');
      }

      // delete relationship
      CRM_Contact_BAO_Relationship::del($this->getEntityId());

      CRM_Utils_System::redirect($url);
    }

    $controller->set('contactId', $this->getContactId());
    $controller->set('id', $this->getEntityId());
    $controller->process();
    $controller->run();
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $this->preProcessQuickEntityPage();

    $this->setContext();

    $this->setCaseId(CRM_Utils_Request::retrieve('caseID', 'Integer', $this));

    if ($this->isViewContext()) {
      $this->view();
    }
    elseif ($this->isEditContext() || $this->isDeleteContext()) {
      $this->edit();
    }

    // if this is called from case view, suppress browse relationships form
    else {
      $this->browse();
    }

    return parent::run();
  }

  public function setContext() {
    if ($this->getContext() == 'dashboard') {
      $url = CRM_Utils_System::url('civicrm/user', "reset=1&id={$this->getContactId()}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view', 'action=browse&selectedChild=rel');
    }
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * called to delete the relationship of a contact.
   *
   */
  public function delete() {
    // calls a function to delete relationship
    CRM_Contact_BAO_Relationship::del($this->getEntityId());
  }

  /**
   * Get action links.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%&selectedChild=rel',
          'title' => ts('View Relationship'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%',
          'title' => ts('Edit Relationship'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Relationship'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Relationship'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%',
          'title' => ts('Delete Relationship'),
        ),
        // FIXME: Not sure what to put as the key.
        // We want to use it differently later anyway (see CRM_Contact_BAO_Relationship::getRelationship). NONE should make it hidden by default.
        CRM_Core_Action::NONE => array(
          'name' => ts('Manage Case'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'action=view&reset=1&cid=%%clientid%%&id=%%caseid%%',
          'title' => ts('Manage Case'),
        ),
      );
    }
    return self::$_links;
  }

}
