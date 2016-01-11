<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Contact_Page_View_Relationship extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * Casid set if called from case context.
   *
   * @var int
   */
  public $_caseId = NULL;

  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * View details of a relationship.
   */
  public function view() {
    $viewRelationship = CRM_Contact_BAO_Relationship::getRelationship($this->_contactId, NULL, NULL, NULL, $this->_id);
    //To check whether selected contact is a contact_id_a in
    //relationship type 'a_b' in relationship table, if yes then
    //revert the permissionship text in template
    $relationship = new CRM_Contact_DAO_Relationship();
    $relationship->id = $viewRelationship[$this->_id]['id'];

    if ($relationship->find(TRUE)) {
      if (($viewRelationship[$this->_id]['rtype'] == 'a_b') && ($this->_contactId == $relationship->contact_id_a)) {
        $this->assign("is_contact_id_a", TRUE);
      }
    }
    $relType = $viewRelationship[$this->_id]['civicrm_relationship_type_id'];
    $this->assign('viewRelationship', $viewRelationship);

    $employerId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'employer_id');
    $this->assign('isCurrentEmployer', FALSE);

    $relTypes = CRM_Utils_Array::index(array('name_a_b'), CRM_Core_PseudoConstant::relationshipType('name'));

    if ($viewRelationship[$this->_id]['employer_id'] == $this->_contactId) {
      $this->assign('isCurrentEmployer', TRUE);
    }
    elseif ($relType == $relTypes['Employee of']['id'] &&
      ($viewRelationship[$this->_id]['cid'] == $employerId)
    ) {
      // make sure we are viewing employee of relationship
      $this->assign('isCurrentEmployer', TRUE);
    }

    $viewNote = CRM_Core_BAO_Note::getNote($this->_id);
    $this->assign('viewNote', $viewNote);

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Relationship', $this, $this->_id, 0, $relType);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->_id);

    $rType = CRM_Utils_Array::value('rtype', $viewRelationship[$this->_id]);
    // add viewed contribution to recent items list
    $url = CRM_Utils_System::url('civicrm/contact/view/rel',
      "action=view&reset=1&id={$viewRelationship[$this->_id]['id']}&cid={$this->_contactId}&context=home"
    );

    $session = CRM_Core_Session::singleton();
    $recentOther = array();

    if (($session->get('userID') == $this->_contactId) ||
      CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)
    ) {
      $recentOther = array(
        'editUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=update&reset=1&id={$viewRelationship[$this->_id]['id']}&cid={$this->_contactId}&rtype={$rType}&context=home"
        ),
        'deleteUrl' => CRM_Utils_System::url('civicrm/contact/view/rel',
          "action=delete&reset=1&id={$viewRelationship[$this->_id]['id']}&cid={$this->_contactId}&rtype={$rType}&context=home"
        ),
      );
    }

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    $this->assign('displayName', $displayName);
    CRM_Utils_System::setTitle(ts('View Relationship for') . ' ' . $displayName);

    $title = $displayName . ' (' . $viewRelationship[$this->_id]['relation'] . ' ' . CRM_Contact_BAO_Contact::displayName($viewRelationship[$this->_id]['cid']) . ')';

    // add the recently viewed Relationship
    CRM_Utils_Recent::add($title,
      $url,
      $viewRelationship[$this->_id]['id'],
      'Relationship',
      $this->_contactId,
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
  }

  /**
   * called when action is update or new.
   *
   */
  public function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Relationship', ts('Contact Relationships'), $this->_action);
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();

    // if this is called from case view, we need to redirect back to same page
    if ($this->_caseId) {
      $url = CRM_Utils_System::url('civicrm/contact/view/case', "action=view&reset=1&cid={$this->_contactId}&id={$this->_caseId}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view', "action=browse&selectedChild=rel&reset=1&cid={$this->_contactId}");
    }

    $session->pushUserContext($url);

    if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
      CRM_Core_DAO::$_nullObject
    )
    ) {
      if ($this->_caseId) {
        //create an activity for case role removal.CRM-4480
        CRM_Case_BAO_Case::createCaseRoleActivity($this->_caseId, $this->_id);
        CRM_Core_Session::setStatus(ts('Case Role has been deleted successfully.'), ts('Record Deleted'), 'success');
      }

      // delete relationship
      CRM_Contact_BAO_Relationship::del($this->_id);

      CRM_Utils_System::redirect($url);
    }

    $controller->set('contactId', $this->_contactId);
    $controller->set('id', $this->_id);
    $controller->process();
    $controller->run();
  }

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $this->setContext();

    $this->_caseId = CRM_Utils_Request::retrieve('caseID', 'Integer', $this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit();
    }

    // if this is called from case view, suppress browse relationships form
    else {
      $this->browse();
    }

    return parent::run();
  }

  public function setContext() {
    $context = CRM_Utils_Request::retrieve('context', 'String',
      $this, FALSE, 'search'
    );

    if ($context == 'dashboard') {
      $cid = CRM_Utils_Request::retrieve('cid', 'Integer',
        $this, FALSE
      );
      $url = CRM_Utils_System::url('civicrm/user',
        "reset=1&id={$cid}"
      );
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
    CRM_Contact_BAO_Relationship::del($this->_id);
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
