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
 * This class generates form components for groupContact.
 */
class CRM_Contact_Form_GroupContact extends CRM_Core_Form {

  /**
   * The groupContact id, used when editing the groupContact
   *
   * @var int
   */
  protected $_groupContactId;

  /**
   * The contact id, used when add/edit groupContact
   *
   * @var int
   */
  protected $_contactId;

  /**
   * The context this page is being rendered in
   *
   * @var string
   */
  protected $_context;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'GroupContact';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Pre process form.
   */
  public function preProcess() {
    $this->_contactId = $this->get('contactId');
    $this->_groupContactId = $this->get('groupContactId');
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // get the list of all the groups
    if ($this->_context == 'user') {
      $onlyPublicGroups = CRM_Utils_Request::retrieve('onlyPublicGroups', 'Boolean', $this, FALSE);
      $ids = CRM_Core_PseudoConstant::allGroup();
      $heirGroups = CRM_Contact_BAO_Group::getGroupsHierarchy($ids);

      $allGroups = [];
      foreach ($heirGroups as $id => $group) {
        // make sure that this group has public visibility
        if ($onlyPublicGroups && $group['visibility'] == 'User and User Admin Only') {
          continue;
        }
        $allGroups[$group['id']] = $group;
      }
    }
    else {
      $allGroups = CRM_Core_PseudoConstant::group();
    }

    // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
    $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($allGroups, NULL, '&nbsp;&nbsp;', TRUE);

    // get the list of groups contact is currently in ("Added") or unsubscribed ("Removed").
    $currentGroups = CRM_Contact_BAO_GroupContact::getGroupList($this->_contactId);

    // Remove current groups from drowdown options ($groupSelect)
    if (is_array($currentGroups)) {
      // Compare array keys, since the array values (group title) in $groupList may have extra spaces for indenting child groups
      $groupSelect = array_diff_key($groupHierarchy, $currentGroups);
    }
    else {
      $groupSelect = $groupHierarchy;
    }

    $groupSelect = ['' => ts('- select group -')] + $groupSelect;

    if (count($groupSelect) > 1) {
      $session = CRM_Core_Session::singleton();
      // user dashboard
      if (str_contains($session->readUserContext(), 'user')) {
        $msg = ts('Join a Group');
      }
      else {
        $msg = ts('Add to a group');
      }
      $this->assign('groupLabel', $msg);
      $this->addField('group_id', ['class' => 'crm-action-menu fa-plus', 'placeholder' => $msg, 'options' => $groupSelect]);

      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Add'),
          'isDefault' => TRUE,
        ],
      ]);
    }
  }

  /**
   * Post process form.
   */
  public function postProcess() {
    $contactID = [$this->_contactId];
    $groupId = $this->controller->exportValue('GroupContact', 'group_id');
    $method = ($this->_context == 'user') ? 'Web' : 'Admin';

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID == $this->_contactId) {
      $method = 'Web';
    }
    $groupContact = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactID, $groupId, $method);

    if ($groupContact && $this->_context != 'user') {
      $groups = CRM_Core_PseudoConstant::group();
      CRM_Core_Session::setStatus(ts("Contact has been added to '%1'.", [1 => $groups[$groupId]]), ts('Added to Group'), 'success');
    }
  }

}
