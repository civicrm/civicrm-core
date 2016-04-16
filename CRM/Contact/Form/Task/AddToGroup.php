<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class provides the functionality to group contacts.
 *
 * This class provides functionality for the actual
 * addition of contacts to groups.
 */
class CRM_Contact_Form_Task_AddToGroup extends CRM_Contact_Form_Task {

  /**
   * The context that we are working on
   *
   * @var string
   */
  protected $_context;

  /**
   * The groupId retrieved from the GET vars
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    $this->_context = $this->get('context');
    $this->_id = $this->get('amtgID');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    //create radio buttons to select existing group or add a new group
    $options = array(ts('Add Contact To Existing Group'), ts('Create New Group'));

    if (!$this->_id) {
      $this->addRadio('group_option', ts('Group Options'), $options, array('onclick' => "return showElements();"));

      $this->add('text', 'title', ts('Group Name:') . ' ',
        CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
      );
      $this->addRule('title', ts('Name already exists in Database.'),
        'objectExists', array('CRM_Contact_DAO_Group', $this->_id, 'title')
      );

      $this->add('textarea', 'description', ts('Description:') . ' ',
        CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'description')
      );

      $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
      if (!CRM_Core_Permission::access('CiviMail')) {
        $isWorkFlowEnabled = CRM_Mailing_Info::workflowEnabled();
        if ($isWorkFlowEnabled &&
          !CRM_Core_Permission::check('create mailings') &&
          !CRM_Core_Permission::check('schedule mailings') &&
          !CRM_Core_Permission::check('approve mailings')
        ) {
          unset($groupTypes['Mailing List']);
        }
      }

      if (!empty($groupTypes)) {
        $this->addCheckBox('group_type',
          ts('Group Type'),
          $groupTypes,
          NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
        );
      }
    }

    // add select for groups
    $group = array('' => ts('- select group -')) + CRM_Core_PseudoConstant::nestedGroup();

    $groupElement = $this->add('select', 'group_id', ts('Select Group'), $group, FALSE, array('class' => 'crm-select2 huge'));

    $this->_title = $group[$this->_id];

    if ($this->_context === 'amtg') {
      $groupElement->freeze();

      // also set the group title
      $groupValues = array('id' => $this->_id, 'title' => $this->_title);
      $this->assign_by_ref('group', $groupValues);
    }

    // Set dynamic page title for 'Add Members Group (confirm)'
    if ($this->_id) {
      CRM_Utils_System::setTitle(ts('Add Contacts: %1', array(1 => $this->_title)));
    }
    else {
      CRM_Utils_System::setTitle(ts('Add Contacts to A Group'));
    }

    $this->addDefaultButtons(ts('Add to Group'));
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();

    if ($this->_context === 'amtg') {
      $defaults['group_id'] = $this->_id;
    }

    $defaults['group_option'] = 0;
    return $defaults;
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_task_AddToGroup', 'formRule'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $params
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($params) {
    $errors = array();

    if (!empty($params['group_option']) && empty($params['title'])) {
      $errors['title'] = "Group Name is a required field";
    }
    elseif (empty($params['group_option']) && empty($params['group_id'])) {
      $errors['group_id'] = "Select Group is a required field.";
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues();
    $groupOption = CRM_Utils_Array::value('group_option', $params, NULL);
    if ($groupOption) {
      $groupParams = array();
      $groupParams['title'] = $params['title'];
      $groupParams['description'] = $params['description'];
      $groupParams['visibility'] = "User and User Admin Only";
      if (array_key_exists('group_type', $params) && is_array($params['group_type'])) {
        $groupParams['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
            array_keys($params['group_type'])
          ) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      else {
        $groupParams['group_type'] = '';
      }
      $groupParams['is_active'] = 1;

      $createdGroup = CRM_Contact_BAO_Group::create($groupParams);
      $groupID = $createdGroup->id;
      $groupName = $groupParams['title'];
    }
    else {
      $groupID = $params['group_id'];
      $group = CRM_Core_PseudoConstant::group();
      $groupName = $group[$groupID];
    }

    list($total, $added, $notAdded) = CRM_Contact_BAO_GroupContact::addContactsToGroup($this->_contactIds, $groupID);

    $status = array(
      ts('%count contact added to group', array(
          'count' => $added,
          'plural' => '%count contacts added to group',
      )),
    );
    if ($notAdded) {
      $status[] = ts('%count contact was already in group', array(
          'count' => $notAdded,
          'plural' => '%count contacts were already in group',
        ));
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts('Added Contact to %1', array(
          1 => $groupName,
          'count' => $added,
          'plural' => 'Added Contacts to %1',
        )), 'success', array('expires' => 0));

    if ($this->_context === 'amtg') {
      CRM_Core_Session::singleton()
        ->pushUserContext(CRM_Utils_System::url('civicrm/group/search', "reset=1&force=1&context=smog&gid=$groupID"));
    }
  }

}
