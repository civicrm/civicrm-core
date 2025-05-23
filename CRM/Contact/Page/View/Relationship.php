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
  public function getDefaultEntity(): string {
    return 'Relationship';
  }

  /**
   * Explicitly declare the form context.
   *
   * @return string|null
   */
  public function getDefaultContext(): ?string {
    return 'search';
  }

  /**
   * View details of a relationship.
   *
   * @throws \CRM_Core_Exception
   */
  public function view() {
    $viewRelationship = CRM_Contact_BAO_Relationship::getRelationship($this->getContactId(), NULL, NULL, NULL, $this->getEntityId());
    //To check whether selected contact is a contact_id_a in
    //relationship type 'a_b' in relationship table, if yes then
    //reverse the text in the template
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->id = $viewRelationship[$this->getEntityId()]['id'];

    if ($relationship->find(TRUE)) {
      if (($viewRelationship[$this->getEntityId()]['rtype'] === 'a_b') && ($this->getContactId() == $relationship->contact_id_a)) {
        $this->assign('is_contact_id_a', TRUE);
      }
    }
    $relType = $viewRelationship[$this->getEntityId()]['civicrm_relationship_type_id'];
    $this->assign('viewRelationship', $viewRelationship);

    $employerId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->getContactId(), 'employer_id');
    $this->assign('isCurrentEmployer', FALSE);

    $relTypes = CRM_Utils_Array::index(['name_a_b'], CRM_Core_PseudoConstant::relationshipType('name'));

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

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Relationship', NULL, $this->getEntityId(), 0, $relType,
      NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->getEntityId());

    $rType = $viewRelationship[$this->getEntityId()]['rtype'];
    // add viewed contribution to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/rel',
      "action=view&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&context=home"
    );

    $session = CRM_Core_Session::singleton();
    $recentOther = [];

    if (($session->get('userID') == $this->getContactId()) ||
      CRM_Contact_BAO_Contact_Permission::allow($this->getContactId(), CRM_Core_Permission::EDIT)
    ) {
      $recentOther = [
        'editUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=update&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&rtype={$rType}&context=home"
        ),
        'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=delete&reset=1&id={$viewRelationship[$this->getEntityId()]['id']}&cid={$this->getContactId()}&rtype={$rType}&context=home"
        ),
      ];
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
      CRM_Contact_BAO_Relationship::deleteRecord(['id' => $this->getEntityId()]);
      CRM_Core_Session::setStatus(ts('Selected relationship has been deleted successfully.'), ts('Record Deleted'), 'success');

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
    $this->assign('entityInClassFormat', 'relationship');
    $this->preProcessQuickEntityPage();

    $this->setContext();

    $this->setCaseId(CRM_Utils_Request::retrieve('caseID', 'Integer', $this));

    if ($this->isViewContext()) {
      $this->view();
    }
    elseif ($this->isEditContext() || $this->isDeleteContext()) {
      $this->edit();
    }

    return parent::run();
  }

  public function setContext() {
    if ($this->getContext() === 'dashboard') {
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
    CRM_Contact_BAO_Relationship::deleteRecord(['id' => $this->getEntityId()]);
    CRM_Core_Session::setStatus(ts('Selected relationship has been deleted successfully.'), ts('Record Deleted'), 'success');
  }

  /**
   * @deprecated since 5.68. Will be removed around 5.74.
   *
   * Only-used-by-user-dashboard.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=view&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%&selectedChild=rel',
          'title' => ts('View Relationship'),
          'weight' => -20,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=update&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%',
          'title' => ts('Edit Relationship'),
          'weight' => -10,
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Relationship'),
          'weight' => 30,
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Relationship'),
          'weight' => 40,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/rel',
          'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&rtype=%%rtype%%',
          'title' => ts('Delete Relationship'),
          'weight' => 100,
        ],
        // FIXME: Not sure what to put as the key.
        // We want to use it differently later anyway (see CRM_Contact_BAO_Relationship::getRelationship). NONE should make it hidden by default.
        CRM_Core_Action::NONE => [
          'name' => ts('Manage Case'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'action=view&reset=1&cid=%%clientid%%&id=%%caseid%%',
          'title' => ts('Manage Case'),
        ],
      ];
    }
    return self::$_links;
  }

}
