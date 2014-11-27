<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is to build the form for adding Group
 */
class CRM_Group_Form_Edit extends CRM_Core_Form {

  /**
   * the group id, used when editing a group
   *
   * @var int
   */
  protected $_id;

  /**
   * the group object, if an id is present
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
   * what blocks should we show and hide.
   *
   * @var CRM_Core_ShowHideBlocks
   */
  protected $_showHide;

  /**
   * the civicrm_group_organization table id
   *
   * @var int
   */
  protected $_groupOrganizationID;

  /**
   * set up variables to build the form
   *
   * @return void
   * @acess protected
   */
  function preProcess() {
    $this->_id = $this->get('id');
    if ($this->_id) {
      $breadCrumb = array(array('title' => ts('Manage Groups'),
          'url' => CRM_Utils_System::url('civicrm/group',
            'reset=1'
          ),
        ));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $this->_groupValues = array();
      $params             = array('id' => $this->_id);
      $this->_group       = CRM_Contact_BAO_Group::retrieve($params, $this->_groupValues);
      $this->_title = $this->_groupValues['title'];
    }

    $this->assign('action', $this->_action);
    $this->assign('showBlockJS', TRUE);

    if ($this->_action == CRM_Core_Action::DELETE) {
      if (isset($this->_id)) {
        $this->assign('title', $this->_title);
        $this->assign('count', CRM_Contact_BAO_Group::memberCount($this->_id));
        CRM_Utils_System::setTitle(ts('Confirm Group Delete'));
      }
      if ($this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to delete this reserved group."));
      }
    }
    else {
      if ($this->_groupValues['is_reserved'] == 1 && !CRM_Core_Permission::check('administer reserved groups')) {
        CRM_Core_Error::statusBounce(ts("You do not have sufficient permission to change settings for this reserved group."));
      }
      if (isset($this->_id)) {
        $groupValues = array(
          'id' => $this->_id,
          'title' => $this->_title,
          'saved_search_id' =>
          isset($this->_groupValues['saved_search_id']) ?
          $this->_groupValues['saved_search_id'] : '',
        );
        if (isset($this->_groupValues['saved_search_id'])) {
          $groupValues['mapping_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch',
            $this->_groupValues['saved_search_id'],
            'mapping_id'
          );
          $groupValues['search_custom_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch',
            $this->_groupValues['saved_search_id'],
            'search_custom_id'
          );
        }
        if (!empty($this->_groupValues['created_id']))
          $groupValues['created_by'] =
            CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_groupValues['created_id'] , 'sort_name', 'id');

        if (!empty($this->_groupValues['modified_id'])) {
          $groupValues['modified_by'] =
            CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_groupValues['modified_id'] , 'sort_name', 'id');
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

  /*
   * This function sets the default values for the form. LocationType that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   * @return void
   */
  /**
   * @return array
   */
  function setDefaultValues() {
    $defaults = array();

    if (isset($this->_id)) {
      $defaults = $this->_groupValues;
      if (!empty($defaults['group_type'])) {
        $types = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($defaults['group_type'], 1, -1)
        );
        $defaults['group_type'] = array();
        foreach ($types as $type) {
          $defaults['group_type'][$type] = 1;
        }
      }

      if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
        CRM_Contact_BAO_GroupOrganization::retrieve($this->_id, $defaults);
      }
    }

    if (!((CRM_Core_Permission::check('access CiviMail')) ||
        (CRM_Mailing_Info::workflowEnabled() &&
          CRM_Core_Permission::check('create mailings')
        )
      )) {
      $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
      if ($defaults['group_type'][$groupTypes['Mailing List']] == 1) {
        $this->assign('freezeMailignList', $groupTypes['Mailing List']);
      }
      else {
        $this->assign('hideMailignList', $groupTypes['Mailing List']);
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
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete Group'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    // We want the "new group" form to redirect the user
    if ($this->_action == CRM_Core_Action::ADD) {
      $this->preventAjaxSubmit();
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'title', ts('Name') . ' ',
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title'), TRUE
    );

    $this->add('textarea', 'description', ts('Description') . ' ',
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'description')
    );

    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    $config = CRM_Core_Config::singleton();
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

    if (CRM_Core_Permission::check('administer Multiple Organizations') && CRM_Core_Permission::isMultisiteEnabled()) {
      //group organization Element
      $props = array('api' => array('params' => array('contact_type' => 'Organization')));
      $this->addEntityRef('organization_id', ts('Organization'), $props);
    }

    // is_reserved property CRM-9936
    $this->addElement('checkbox', 'is_reserved', ts('Reserved Group?'));
    if (!CRM_Core_Permission::check('administer reserved groups')) {
      $this->freeze('is_reserved');
    }

    //build custom data
    CRM_Custom_Form_CustomData::buildQuickForm($this);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' =>
          ($this->_action == CRM_Core_Action::ADD) ?
          ts('Continue') : ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $doParentCheck = FALSE;
    if (CRM_Core_Permission::isMultisiteEnabled()) {
      $doParentCheck = ($this->_id && CRM_Core_BAO_Domain::isDomainGroup($this->_id)) ? FALSE : TRUE;
    }

    $options = array(
      'selfObj' => $this,
      'parentGroups' => $parentGroups,
      'doParentCheck' => $doParentCheck,
    );
    $this->addFormRule(array('CRM_Group_Form_Edit', 'formRule'), $options);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @param $fileParams
   * @param $options
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $fileParams, $options) {
    $errors = array();

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
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    CRM_Utils_System::flushCache('CRM_Core_DAO_Group');

    $updateNestingCache = FALSE;
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Group::discard($this->_id);
      CRM_Core_Session::setStatus(ts("The Group '%1' has been deleted.", array(1 => $this->_title)), ts('Group Deleted'), 'success');
      $updateNestingCache = TRUE;
    }
    else {
      // store the submitted values in an array
      $params = $this->controller->exportValues($this->_name);

      $params['is_active'] = CRM_Utils_Array::value('is_active', $this->_groupValues, 1);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      if ($this->_action & CRM_Core_Action::UPDATE && isset($this->_groupOrganizationID)) {
        $params['group_organization'] = $this->_groupOrganizationID;
      }

      $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);

      $customFields = CRM_Core_BAO_CustomField::getFields('Group');
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $customFields,
        $this->_id,
        'Group'
      );

      $group = CRM_Contact_BAO_Group::create($params);

      /*
       * Remove any parent groups requested to be removed
       */

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

      /*
             * Add context to the session, in case we are adding members to the group
             */

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

  /*
   * Build parent groups form elements
   *
   * @obj form object passed by reference
   *
   * @return parent groups array
   * @static
   * @access public
   */
  /**
   * @param $obj
   *
   * @return array
   */
  static function buildParentGroups( &$obj ) {
    $groupNames = CRM_Core_PseudoConstant::group();
    $parentGroups = $parentGroupElements = array();
    if (isset($obj->_id) &&
      CRM_Utils_Array::value('parents', $obj->_groupValues)
    ) {
      $parentGroupIds = explode(',', $obj->_groupValues['parents']);
      foreach ($parentGroupIds as $parentGroupId) {
        $parentGroups[$parentGroupId] = $groupNames[$parentGroupId];
        if (array_key_exists($parentGroupId, $groupNames)) {
          $parentGroupElements[$parentGroupId] = $groupNames[$parentGroupId];
          $obj->addElement('checkbox', "remove_parent_group_$parentGroupId",
            $groupNames[$parentGroupId]
          );
        }
      }
    }
    $obj->assign_by_ref('parent_groups', $parentGroupElements);

    if (isset($obj->_id)) {
      $potentialParentGroupIds = CRM_Contact_BAO_GroupNestingCache::getPotentialCandidates($obj->_id,
        $groupNames
      );
    }
    else {
      $potentialParentGroupIds = array_keys($groupNames);
    }

    $parentGroupSelectValues = array('' => '- ' . ts('select group') . ' -');
    foreach ($potentialParentGroupIds as $potentialParentGroupId) {
      if (array_key_exists($potentialParentGroupId, $groupNames)) {
        $parentGroupSelectValues[$potentialParentGroupId] = $groupNames[$potentialParentGroupId];
      }
    }

    if (count($parentGroupSelectValues) > 1) {
      if (CRM_Core_Permission::isMultisiteEnabled()) {
        $required = !isset($obj->_id) || ($obj->_id && CRM_Core_BAO_Domain::isDomainGroup($obj->_id)) ? FALSE : empty($parentGroups);
      }
      else {
        $required = FALSE;
      }
      $obj->add('select', 'parents', ts('Add Parent'), $parentGroupSelectValues, $required, array('class' => 'crm-select2'));
    }

    return $parentGroups;
  }
}

