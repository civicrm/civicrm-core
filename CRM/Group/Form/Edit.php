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

  /**
   * The group object, if an id is present
   *
   * @var object
   */
  protected $_group;

  /**
   * The title of the group being deleted
   *
   * @var string
   */
  protected $_title;

  /**
   * Store the group values
   *
   * @var array
   */
  protected $_groupValues;

  /**
   * What blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  /**
   * The civicrm_group_organization table id
   *
   * @var int
   */
  protected $_groupOrganizationID;

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'title' => [
        'name' => 'title',
        'required' => TRUE,
      ],
      'description' => ['name' => 'description'],
      'frontend_title' => ['name' => 'frontend_title'],
      'frontend_description' => ['name' => 'frontend_description'],
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
   */
  public function preProcess() {
    $this->_id = $this->get('id');
    if ($this->_id) {
      $breadCrumb = array(
        array(
          'title' => ts('Manage Groups'),
          'url' => CRM_Utils_System::url('civicrm/group',
            'reset=1'
          ),
        ),
      );
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $this->_groupValues = [];
      $params = array('id' => $this->_id);
      $this->_group = CRM_Contact_BAO_Group::retrieve($params, $this->_groupValues);
      $this->_title = $this->_groupValues['title'];
    }

    $this->assign('action', $this->_action);
    $this->assign('showBlockJS', TRUE);

    if ($this->_action == CRM_Core_Action::DELETE) {
      if (isset($this->_id)) {
        $this->assign('title', $this->_title);
        try {
          $this->assign('count', CRM_Contact_BAO_Group::memberCount($this->_id));
        }
        catch (CRM_Core_Exception $e) {
          // If the group is borked the query might fail but delete should be possible.
        }
        CRM_Utils_System::setTitle(ts('Confirm Group Delete'));
      }
      if ($this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to delete this reserved group."));
      }
    }
    else {
      if ($this->_id && $this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to change settings for this reserved group."));
      }
      if (isset($this->_id)) {
        $groupValues = array(
          'id' => $this->_id,
          'title' => $this->_title,
          'saved_search_id' => $this->_groupValues['saved_search_id'] ?? '',
        );
        if (isset($this->_groupValues['saved_search_id'])) {
          $this->assign('editSmartGroupURL', CRM_Contact_BAO_SavedSearch::getEditSearchUrl($this->_groupValues['saved_search_id']));
        }
        if (!empty($this->_groupValues['created_id'])) {
          $groupValues['created_by'] = CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_groupValues['created_id'], 'sort_name', 'id');
        }

        if (!empty($this->_groupValues['modified_id'])) {
          $groupValues['modified_by'] = CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_groupValues['modified_id'], 'sort_name', 'id');
        }

        $this->assign_by_ref('group', $groupValues);

        CRM_Utils_System::setTitle(ts('Group Settings: %1', array(1 => $this->_title)));
      }
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/group', 'reset=1'));
    }

    //build custom data
    CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'Group', $this->_id);
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

    if (!((CRM_Core_Permission::check('access CiviMail')) ||
      (CRM_Mailing_Info::workflowEnabled() &&
        CRM_Core_Permission::check('create mailings')
      )
    )
    ) {
      $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
      if ($defaults['group_type'][$groupTypes['Mailing List']] == 1) {
        $this->assign('freezeMailingList', $groupTypes['Mailing List']);
      }
      else {
        $this->assign('hideMailingList', $groupTypes['Mailing List']);
      }
    }

    if (empty($defaults['parents'])) {
      $defaults['parents'] = CRM_Core_BAO_Domain::getGroupId();
    }

    // custom data set defaults
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($this);
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    self::buildQuickEntityForm();
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

    //build custom data
    CRM_Custom_Form_CustomData::buildQuickForm($this);

    $doParentCheck = FALSE;
    if (CRM_Core_Permission::isMultisiteEnabled()) {
      $doParentCheck = !($this->_id && CRM_Core_BAO_Domain::isDomainGroup($this->_id));
    }

    $options = array(
      'selfObj' => $this,
      'parentGroups' => $parentGroups,
      'doParentCheck' => $doParentCheck,
    );
    $this->addFormRule(array('CRM_Group_Form_Edit', 'formRule'), $options);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $fileParams
   * @param array $options
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $fileParams, $options) {
    $errors = [];

    $doParentCheck = $options['doParentCheck'];
    $self = &$options['selfObj'];

    if ($doParentCheck) {
      $parentGroups = $options['parentGroups'];

      $grpRemove = 0;
      foreach ($fields as $key => $val) {
        if (substr($key, 0, 20) == 'remove_parent_group_') {
          $grpRemove++;
        }
      }

      $grpAdd = 0;
      if (!empty($fields['parents'])) {
        $grpAdd++;
      }

      if ((count($parentGroups) >= 1) && (($grpRemove - $grpAdd) >= count($parentGroups))) {
        $errors['parents'] = ts('Make sure at least one parent group is set.');
      }
    }

    // do check for both name and title uniqueness
    if (!empty($fields['title'])) {
      $title = trim($fields['title']);
      $query = "
SELECT count(*)
FROM   civicrm_group
WHERE  title = %1
";
      $params = array(1 => array($title, 'String'));

      if ($self->_id) {
        $query .= "AND id <> %2";
        $params[2] = array($self->_id, 'Integer');
      }

      $grpCnt = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($grpCnt) {
        $errors['title'] = ts('Group \'%1\' already exists.', array(1 => $fields['title']));
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    $updateNestingCache = FALSE;
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Group::discard($this->_id);
      CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", array(1 => $this->_title)), ts('Group Deleted'), 'success');
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

      $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_id,
        'Group'
      );

      $group = CRM_Contact_BAO_Group::create($params);
      // Set the entity id so it is available to postProcess hook consumers
      $this->setEntityId($group->id);
      //Remove any parent groups requested to be removed
      if (!empty($this->_groupValues['parents'])) {
        $parentGroupIds = explode(',', $this->_groupValues['parents']);
        foreach ($parentGroupIds as $parentGroupId) {
          if (isset($params["remove_parent_group_$parentGroupId"])) {
            CRM_Contact_BAO_GroupNesting::remove($parentGroupId, $group->id);
            $updateNestingCache = TRUE;
          }
        }
      }

      CRM_Core_Session::setStatus(ts('The Group \'%1\' has been saved.', array(1 => $group->title)), ts('Group Saved'), 'success');

      // Add context to the session, in case we are adding members to the group
      if ($this->_action & CRM_Core_Action::ADD) {
        $this->set('context', 'amtg');
        $this->set('amtgID', $group->id);

        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $group->id));
      }
    }

    // update the nesting cache
    if ($updateNestingCache) {
      CRM_Contact_BAO_GroupNestingCache::update();
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
          $form->addElement('checkbox', "remove_parent_group_$parentGroupId",
            $groupNames[$parentGroupId]
          );
        }
      }
    }
    $form->assign_by_ref('parent_groups', $parentGroupElements);

    if (isset($form->_id)) {
      $potentialParentGroupIds = CRM_Contact_BAO_GroupNestingCache::getPotentialCandidates($form->_id, $groupNames);
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
      $form->add('select', 'parents', ts('Add Parent'), $parentGroupSelectValues, $required, array('class' => 'crm-select2', 'multiple' => TRUE));
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
  public static function buildGroupOrganizations(&$form) {
    if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
      //group organization Element
      $props = array('api' => array('params' => array('contact_type' => 'Organization')));
      $form->addEntityRef('organization_id', ts('Organization'), $props);
    }
  }

}
