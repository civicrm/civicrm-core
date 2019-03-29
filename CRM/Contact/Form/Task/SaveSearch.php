<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class provides the functionality to save a search.
 *
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Contact_Form_Task_SaveSearch extends CRM_Contact_Form_Task {

  /**
   * Saved search id if any.
   *
   * @var int
   */
  protected $_id;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_id = NULL;

    // get the submitted values of the search form
    // we'll need to get fv from either search or adv search in the future
    if ($this->_action == CRM_Core_Action::ADVANCED) {
      $values = $this->controller->exportValues('Advanced');
    }
    elseif ($this->_action == CRM_Core_Action::PROFILE) {
      $values = $this->controller->exportValues('Builder');
    }
    elseif ($this->_action == CRM_Core_Action::COPY) {
      $values = $this->controller->exportValues('Custom');
    }
    else {
      $values = $this->controller->exportValues('Basic');
    }

    // Get Task name
    $modeValue = CRM_Contact_Form_Search::getModeValue($values['component_mode']);
    $className = $modeValue['taskClassName'];
    $taskList = $className::taskTitles();
    $this->_task = CRM_Utils_Array::value('task', $values);
    $this->assign('taskName', CRM_Utils_Array::value($this->_task, $taskList));
  }

  /**
   * Build the form object.
   *
   * It consists of
   *    - displaying the QILL (query in local language)
   *    - displaying elements for saving the search
   */
  public function buildQuickForm() {
    // @todo sync this more with CRM_Group_Form_Edit.
    $query = new CRM_Contact_BAO_Query($this->get('queryParams'));
    $this->assign('qill', $query->qill());

    // Values from the search form
    $formValues = $this->controller->exportValues();

    // the name and description are actually stored with the group and not the saved search
    $this->add('text', 'title', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title'), TRUE
    );

    $this->addElement('textarea', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'description')
    );

    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    unset($groupTypes['Access Control']);
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

    //CRM-14190
    CRM_Group_Form_Edit::buildParentGroups($this);
    CRM_Group_Form_Edit::buildGroupOrganizations($this);

    // get the group id for the saved search
    $groupID = NULL;
    if (isset($this->_id)) {
      $groupID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group',
        $this->_id,
        'id',
        'saved_search_id'
      );
      $this->addDefaultButtons(ts('Update Smart Group'));
    }
    else {
      $this->addDefaultButtons(ts('Save Smart Group'));
      $this->assign('partiallySelected', $formValues['radio_ts'] != 'ts_all');
    }
    $this->addRule('title', ts('Name already exists in Database.'),
      'objectExists', ['CRM_Contact_DAO_Group', $groupID, 'title']
    );
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // saved search form values
    // get form values of all the forms in this controller
    $formValues = $this->controller->exportValues();

    $isAdvanced = $this->get('isAdvanced');
    $isSearchBuilder = $this->get('isSearchBuilder');

    // add mapping record only for search builder saved search
    $mappingId = NULL;
    if ($isAdvanced == '2' && $isSearchBuilder == '1') {
      //save the mapping for search builder

      if (!$this->_id) {
        //save record in mapping table
        $mappingParams = [
          'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Search Builder'),
        ];
        $mapping = CRM_Core_BAO_Mapping::add($mappingParams);
        $mappingId = $mapping->id;
      }
      else {
        //get the mapping id from saved search

        $savedSearch = new CRM_Contact_BAO_SavedSearch();
        $savedSearch->id = $this->_id;
        $savedSearch->find(TRUE);
        $mappingId = $savedSearch->mapping_id;
      }

      //save mapping fields
      CRM_Core_BAO_Mapping::saveMappingFields($formValues, $mappingId);
    }

    //save the search
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $savedSearch->id = $this->_id;
    $queryParams = $this->get('queryParams');

    // Use the query parameters rather than the form values - these have already been assessed / converted
    // with the extra knowledge that the form has.
    // Note that we want to move towards a standardised way of saving the query that is not
    // an exact match for the form requirements & task the form layer with converting backwards and forwards.
    // Ideally per CRM-17075 we will use entity reference fields heavily in the form layer & convert to the
    // sql operator syntax at the query layer.
    if (!$isSearchBuilder) {
      CRM_Contact_BAO_SavedSearch::saveRelativeDates($queryParams, $formValues);
      CRM_Contact_BAO_SavedSearch::saveSkippedElement($queryParams, $formValues);
      $savedSearch->form_values = serialize($queryParams);
    }
    else {
      // We want search builder to be able to convert back & forth at the form layer
      // to a standardised style - but it can't yet!
      $savedSearch->form_values = serialize($formValues);
    }

    $savedSearch->mapping_id = $mappingId;
    $savedSearch->search_custom_id = $this->get('customSearchID');
    $savedSearch->save();
    $this->set('ssID', $savedSearch->id);
    CRM_Core_Session::setStatus(ts("Your smart group has been saved as '%1'.", [1 => $formValues['title']]), ts('Group Saved'), 'success');

    // also create a group that is associated with this saved search only if new saved search
    $params = [];
    $params['title'] = $formValues['title'];
    $params['description'] = $formValues['description'];
    if (isset($formValues['group_type']) && is_array($formValues['group_type']) && count($formValues['group_type'])) {
      $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_keys($formValues['group_type'])) . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    else {
      $params['group_type'] = '';
    }
    $params['visibility'] = 'User and User Admin Only';
    $params['saved_search_id'] = $savedSearch->id;
    $params['is_active'] = 1;

    //CRM-14190
    $params['parents'] = $formValues['parents'];

    if ($this->_id) {
      $params['id'] = CRM_Contact_BAO_SavedSearch::getName($this->_id, 'id');
    }

    $group = CRM_Contact_BAO_Group::create($params);

    // Update mapping with the name and description of the group.
    if ($mappingId && $group) {
      $mappingParams = [
        'id' => $mappingId,
        'name' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $group->id, 'name', 'id'),
        'description' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $group->id, 'description', 'id'),
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Search Builder'),
      ];
      CRM_Core_BAO_Mapping::add($mappingParams);
    }

    // CRM-9464
    $this->_id = $savedSearch->id;

    //CRM-14190
    if (!empty($formValues['parents'])) {
      CRM_Contact_BAO_GroupNestingCache::update();
    }
  }

  /**
   * Set form defaults.
   *
   * return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if (empty($defaults['parents'])) {
      $defaults['parents'] = CRM_Core_BAO_Domain::getGroupId();
    }
    return $defaults;
  }

}
