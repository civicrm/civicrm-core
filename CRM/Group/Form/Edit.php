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
 * This class is to build the form for adding Group.
 */
class CRM_Group_Form_Edit extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * The group object, if an id is present
   *
   * @var object
   */
  protected $_group;

  /**
   * Store the group values
   *
   * @var array
   */
  protected $_groupValues;

  /**
   * The civicrm_group_organization table id
   *
   * @var int
   */
  protected $_groupOrganizationID;

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields(): void {
    $this->entityFields = [
      'frontend_title' => ['name' => 'frontend_title', 'required' => TRUE],
      'frontend_description' => ['name' => 'frontend_description'],
      'title' => [
        'name' => 'title',
        'required' => TRUE,
      ],
      'description' => ['name' => 'description'],
    ];
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = '';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Group';
  }

  /**
   * Set up variables to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->addExpectedSmartyVariables([
      'parent_groups',
      'editSmartGroupURL',
    ]);
    // current set id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if ($this->_id) {
      $breadCrumb = [
        [
          'title' => ts('Manage Groups'),
          'url' => CRM_Utils_System::url('civicrm/group', 'reset=1'),
        ],
      ];
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $this->_groupValues = [];
      $params = ['id' => $this->_id];
      $this->_group = CRM_Contact_BAO_Group::retrieve($params, $this->_groupValues);
    }

    $this->assign('action', $this->_action);
    $this->assign('showBlockJS', TRUE);

    if ($this->_action == CRM_Core_Action::DELETE) {
      if (isset($this->_id)) {
        $this->assign('title', $this->_groupValues['title']);
        if (empty($this->_groupValues['saved_search_id'])) {
          try {
            $count = CRM_Contact_BAO_Group::memberCount($this->_id);
          }
          catch (CRM_Core_Exception $e) {
            // If the group is borked the query might fail but delete should be possible.
          }
        }
        $this->assign('count', $count ?? NULL);
        $this->assign('smartGroupsUsingThisGroup', CRM_Contact_BAO_SavedSearch::getSmartGroupsUsingGroup($this->_id));
        $this->setTitle(ts('Confirm Group Delete'));
      }
      if ($this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts('You do not have sufficient permission to delete this reserved group.'));
      }
    }
    else {
      if ($this->_id && $this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts('You do not have sufficient permission to change settings for this reserved group.'));
      }
      if (isset($this->_id)) {
        $groupValues = [
          'id' => $this->_id,
          'title' => $this->_groupValues['title'],
          'saved_search_id' => $this->_groupValues['saved_search_id'] ?? '',
        ];
        $this->assign('editSmartGroupURL', isset($this->_groupValues['saved_search_id']) ? CRM_Contact_BAO_SavedSearch::getEditSearchUrl($this->_groupValues['saved_search_id']) : NULL);
        $groupValues['created_by'] = empty($this->_groupValues['created_id']) ? NULL : CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_groupValues['created_id'], 'sort_name', 'id');
        $groupValues['modified_by'] = empty($this->_groupValues['modified_id']) ? NULL : CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_groupValues['modified_id'], 'sort_name', 'id');

        $this->assign('group', $groupValues);

        $this->setTitle(ts('Group Settings: %1', [1 => $this->_groupValues['title']]));
      }
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/group', 'reset=1'));
    }
    $this->addExpectedSmartyVariables(['freezeMailingList', 'hideMailingList']);
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_id)) {
      $defaults = $this->_groupValues;
      if (!empty($defaults['group_type'])) {
        $types = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($defaults['group_type'], 1, -1)
        );
        $defaults['group_type'] = [];
        foreach ($types as $type) {
          $defaults['group_type'][$type] = 1;
        }
      }

      if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
        CRM_Contact_BAO_GroupOrganization::retrieve($this->_id, $defaults);
      }
    }
    else {
      $defaults['is_active'] = 1;
    }

    if (!$this->isPermitMailingGroupAccess()) {
      $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
      if ($defaults['group_type'][$groupTypes['Mailing List']] == 1) {
        $this->assign('freezeMailingList', $groupTypes['Mailing List']);
      }
      else {
        $this->assign('hideMailingList', $groupTypes['Mailing List']);
      }
    }

    $parentGroupIds = explode(',', ($this->_groupValues['parents'] ?? ''));
    $defaults['parents'] = $parentGroupIds;
    if (empty($defaults['parents'])) {
      $defaults['parents'] = CRM_Core_BAO_Domain::getGroupId();
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->buildQuickEntityForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    // We want the "new group" form to redirect the user
    if ($this->_action == CRM_Core_Action::ADD) {
      $this->preventAjaxSubmit();
    }

    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);

    if (isset($this->_id) && !empty($this->_groupValues['saved_search_id'])) {
      unset($groupTypes['Access Control']);
    }

    if (!empty($groupTypes)) {
      $this->addCheckBox('group_type',
        ts('Group Type'),
        $groupTypes,
        NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
      );
    }

    $this->add('select', 'visibility', ts('Visibility'), CRM_Core_SelectValues::groupVisibility(), TRUE);

    //CRM-14190
    $parentGroups = self::buildParentGroups($this);
    self::buildGroupOrganizations($this);

    // is_reserved property CRM-9936
    $this->addElement('checkbox', 'is_reserved', ts('Reserved Group?'));
    if (!CRM_Core_Permission::check('administer reserved groups')) {
      $this->freeze('is_reserved');
    }
    $this->addElement('checkbox', 'is_active', ts('Is active?'));

    if ($this->isSubmitted()) {
      $this->addCustomDataFieldsToForm('Group');
    }

    $options = [
      'selfObj' => $this,
      'parentGroups' => $parentGroups,
    ];
    $this->addFormRule(['CRM_Group_Form_Edit', 'formRule'], $options);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $fileParams
   * @param array $options
   *
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $fileParams, $options) {
    $errors = [];

    $self = &$options['selfObj'];

    // do check for both name and title uniqueness
    if (!empty($fields['title'])) {
      $title = trim($fields['title']);
      $query = "
SELECT count(*)
FROM   civicrm_group
WHERE  title = %1
";
      $params = [1 => [$title, 'String']];

      if ($self->_id) {
        $query .= "AND id <> %2";
        $params[2] = [$self->_id, 'Integer'];
      }

      $grpCnt = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($grpCnt) {
        $errors['title'] = ts('Group \'%1\' already exists.', [1 => $fields['title']]);
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    Civi::rebuild(['system' => TRUE])->execute();

    $updateNestingCache = FALSE;
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Group::discard($this->_id);
      CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", [1 => $this->_groupValues['title']]), ts('Group Deleted'), 'success');
      $updateNestingCache = TRUE;
    }
    else {
      // store the submitted values in an array
      $params = $this->controller->exportValues($this->_name);
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      if ($this->_action & CRM_Core_Action::UPDATE && isset($this->_groupOrganizationID)) {
        $params['group_organization'] = $this->_groupOrganizationID;
      }

      // CRM-21431 If all group_type are unchecked, the change will not be saved otherwise.
      if (!isset($params['group_type'])) {
        $params['group_type'] = [];
      }

      $params['is_reserved'] ??= FALSE;
      $params['is_active'] ??= FALSE;
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
        $this->_id,
        'Group'
      );

      if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
        $params['organization_id'] = empty($params['organization_id']) ? 'null' : $params['organization_id'];
      }

      $group = CRM_Contact_BAO_Group::writeRecord($params);
      // Set the entity id so it is available to postProcess hook consumers
      $this->setEntityId($group->id);

      CRM_Core_Session::setStatus(ts('The Group \'%1\' has been saved.', [1 => $group->title]), ts('Group Saved'), 'success');

      // Add context to the session, in case we are adding members to the group
      if ($this->_action & CRM_Core_Action::ADD) {
        $this->set('context', 'amtg');
        $this->set('amtgID', $group->id);

        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $group->id));
      }
    }

  }

  /**
   * Build parent groups form elements.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   *   parent groups
   */
  public static function buildParentGroups(&$form) {
    $groupNames = CRM_Core_PseudoConstant::group();
    $parentGroups = $parentGroupElements = [];
    if (isset($form->_id) && !empty($form->_groupValues['parents'])) {
      $parentGroupIds = explode(',', $form->_groupValues['parents']);
      foreach ($parentGroupIds as $parentGroupId) {
        $parentGroups[$parentGroupId] = $groupNames[$parentGroupId];
        if (array_key_exists($parentGroupId, $groupNames)) {
          $parentGroupElements[$parentGroupId] = $groupNames[$parentGroupId];
        }
      }
    }
    $form->assign('parent_groups', $parentGroupElements);

    if (isset($form->_id)) {
      $potentialParentGroupIds = CRM_Contact_BAO_GroupNestingCache::getPotentialCandidates($form->_id, $groupNames);
      // put back current groups because they are selected by default
      if (!empty($parentGroupIds)) {
        $potentialParentGroupIds = array_merge($potentialParentGroupIds, $parentGroupIds);
      }
    }
    else {
      $potentialParentGroupIds = array_keys($groupNames);
    }

    $parentGroupSelectValues = [];
    foreach ($potentialParentGroupIds as $potentialParentGroupId) {
      if (array_key_exists($potentialParentGroupId, $groupNames)) {
        $parentGroupSelectValues[$potentialParentGroupId] = $groupNames[$potentialParentGroupId];
      }
    }

    if (count($parentGroupSelectValues) > 1) {
      if (CRM_Core_Permission::isMultisiteEnabled()) {
        $required = !isset($form->_id) || ($form->_id && CRM_Core_BAO_Domain::isDomainGroup($form->_id)) ? FALSE : empty($parentGroups);
      }
      else {
        $required = FALSE;
      }
      $form->add('select', 'parents', ts('Parents'), $parentGroupSelectValues, $required, ['class' => 'crm-select2', 'multiple' => TRUE]);
    }

    return $parentGroups;
  }

  /**
   * Add the group organization checkbox to the form.
   *
   * Note this was traditionally a multisite thing - there is no particular reason why it is not available
   * as a general field - it's historical use-case driven.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildGroupOrganizations($form) {
    if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
      //group organization Element
      $props = ['api' => ['params' => ['contact_type' => 'Organization']]];
      $form->addEntityRef('organization_id', ts('Organization'), $props);
    }
  }

  /**
   * Does the user have permissions allowing them to create groups with option_type set to mailing?
   *
   * @return bool
   */
  protected function isPermitMailingGroupAccess(): bool {
    return CRM_Core_Permission::check('access CiviMail') || (CRM_Mailing_Info::workflowEnabled() && CRM_Core_Permission::check('create mailings'));
  }

}
