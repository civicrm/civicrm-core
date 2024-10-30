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
 * This class provides the functionality to group
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
 */
class CRM_Event_Form_Task_AddToGroup extends CRM_Event_Form_Task {

  /**
   * The context that we are working on.
   *
   * @var string
   */
  protected $_context;

  /**
   * The groupId retrieved from the GET vars.
   *
   * @var int
   */
  protected $_id;

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    parent::setContactIDs();
    $this->_context = $this->get('context');
    $this->_id = $this->get('amtgID');
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {

    //create radio buttons to select existing group or add a new group
    $options = [ts('Add Contact To Existing Group'), ts('Create New Group')];

    if (!$this->_id) {
      $this->addRadio('group_option', ts('Group Options'), $options, ['onclick' => "return showElements();"]);

      $this->add('text', 'title', ts('Group Name:') . ' ',
        CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title')
      );
      $this->addRule('title', ts('Name already exists in Database.'),
        'objectExists', ['CRM_Contact_DAO_Group', $this->_id, 'title']
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
    $group = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::group();

    $groupElement = $this->add('select', 'group_id', ts('Select Group'), $group);

    $this->_title = $group[$this->_id];

    if ($this->_context === 'amtg') {
      $groupElement->freeze();

      // also set the group title
      $groupValues = ['id' => $this->_id, 'title' => $this->_title];
      $this->assign('group', $groupValues);
    }

    // Set dynamic page title for 'Add Members Group (confirm)'
    if ($this->_id) {
      $this->setTitle(ts('Add Contacts: %1', [1 => $this->_title]));
    }
    else {
      $this->setTitle(ts('Add Contacts to A Group'));
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
    $defaults = [];

    if ($this->_context === 'amtg') {
      $defaults['group_id'] = $this->_id;
    }

    $defaults['group_option'] = 0;
    return $defaults;
  }

  /**
   * Add local and global form rules.
   *
   *
   * @return void
   */
  public function addRules() {
    $this->addFormRule(['CRM_Event_Form_Task_AddToGroup', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $params
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($params) {
    $errors = [];

    if (!empty($params['group_option']) && empty($params['title'])) {
      $errors['title'] = ts('Group Name is a required field');
    }
    elseif (empty($params['group_option']) && empty($params['group_id'])) {
      $errors['group_id'] = ts('Select Group is a required field.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues();
    $groupOption = $params['group_option'] ?? NULL;
    if ($groupOption) {
      $groupParams = [];
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

      $createdGroup = CRM_Contact_BAO_Group::writeRecord($groupParams);
      $groupID = $createdGroup->id;
      $groupName = $groupParams['title'];
    }
    else {
      $groupID = $params['group_id'];
      $group = CRM_Core_PseudoConstant::group();
      $groupName = $group[$groupID];
    }

    list($total, $added, $notAdded) = CRM_Contact_BAO_GroupContact::addContactsToGroup($this->_contactIds, $groupID);

    $status = [
      ts('%count contact added to group', [
        'count' => $added,
        'plural' => '%count contacts added to group',
      ]),
    ];
    if ($notAdded) {
      $status[] = ts('%count contact was already in group', [
        'count' => $notAdded,
        'plural' => '%count contacts were already in group',
      ]);
    }
    $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
    CRM_Core_Session::setStatus($status, ts('Added Contact to %1', [
      1 => $groupName,
      'count' => $added,
      'plural' => 'Added Contacts to %1',
    ]), 'success');
  }

}
