<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for relationship
 *
 */
class CRM_Contact_Form_Relationship extends CRM_Core_Form {

  /**
   * max number of contacts we will display for a relationship
   */
  CONST MAX_RELATIONSHIPS = 50;

  /**
   * The relationship id, used when editing the relationship
   *
   * @var int
   */
  protected $_relationshipId;

  /**
   * The contact id, used when add/edit relationship
   *
   * @var int
   */
  protected $_contactId;

  /**
   * This is a string which is either a_b or  b_a  used to determine the relationship between to contacts
   *
   */
  protected $_rtype;

  /**
   * This is a string which is used to determine the relationship between to contacts
   *
   */
  protected $_rtypeId;

  /**
   * Display name of contact a
   *
   */
  protected $_display_name_a;

  /**
   * Display name of contact b
   *
   */
  protected $_display_name_b;

  /**
   * The relationship type id
   *
   * @var int
   */
  protected $_relationshipTypeId;

  /**
   * an array of all relationship names
   *
   * @var array
   */
  protected $_allRelationshipNames;

  /**
   * The relationship values if Updating relationship
   */
  protected $_values;

  /**
   * casid if it called from case context
   */
  protected $_caseId;

  function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    $this->_contactId = $this->get('contactId');

    $this->_relationshipId = $this->get('id');

    $this->_rtype = CRM_Utils_Request::retrieve('rtype', 'String', $this);

    $this->_rtypeId = CRM_Utils_Request::retrieve('relTypeId', 'String', $this);

    $this->_display_name_a = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'display_name');

    $this->assign('sort_name_a', $this->_display_name_a);
    CRM_Utils_System::setTitle(ts('Relationships for') . ' ' . $this->_display_name_a);

    $this->_caseId = CRM_Utils_Request::retrieve('caseID', 'Integer', $this);

    //get the relationship values.
    $this->_values = array();
    if ($this->_relationshipId) {
      $params = array('id' => $this->_relationshipId);
      CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Relationship', $params, $this->_values);
    }

    if (!$this->_rtypeId) {
      $params = $this->controller->exportValues($this->_name);
      if (isset($params['relationship_type_id'])) {
        $this->_rtypeId = $params['relationship_type_id'];
      }
      elseif (!empty($this->_values)) {
        $this->_rtypeId = $this->_values['relationship_type_id'] . '_' . $this->_rtype;
      }
    }

    //get the relationship type id
    $this->_relationshipTypeId = str_replace(array('_a_b', '_b_a'), array('', ''), $this->_rtypeId);

    //get the relationship type
    if (!$this->_rtype) {
      $this->_rtype = str_replace($this->_relationshipTypeId . '_', '', $this->_rtypeId);
    }
    $this->assign('rtype', $this->_rtype);


    //use name as it remain constant, CRM-3336
    $this->_allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');

    // when custom data is included in this page
    if (CRM_Utils_Array::value('hidden_custom', $_POST)) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * This function sets the default values for the form. Relationship that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $defaults = array();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (!empty($this->_values)) {
        $defaults['relationship_type_id'] = $this->_rtypeId;
        if (CRM_Utils_Array::value('start_date', $this->_values)) {
          list($defaults['start_date']) = CRM_Utils_Date::setDateDefaults($this->_values['start_date']);
        }
        if (CRM_Utils_Array::value('end_date', $this->_values)) {
          list($defaults['end_date']) = CRM_Utils_Date::setDateDefaults($this->_values['end_date']);
        }
        $defaults['description'] = CRM_Utils_Array::value('description', $this->_values);
        $defaults['is_active'] = CRM_Utils_Array::value('is_active', $this->_values);
        $defaults['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_a_b', $this->_values);
        $defaults['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_b_a', $this->_values);
        $contact = new CRM_Contact_DAO_Contact();
        if ($this->_rtype == 'a_b' && $this->_values['contact_id_a'] == $this->_contactId) {
          $contact->id = $this->_values['contact_id_b'];
        }
        else {
          $contact->id = $this->_values['contact_id_a'];
        }
        if ($contact->find(TRUE)) {
          $this->_display_name_b = $contact->display_name;
          $this->assign('sort_name_b', $this->_display_name_b);

          //is current employee/employer.
          if ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"] == 'Employee of' &&
            $contact->id == CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'employer_id')
          ) {
            $defaults['is_current_employer'] = 1;
            $this->_values['current_employee_id'] = $this->_contactId;
            $this->_values['current_employer_id'] = $contact->id;
          }
          elseif ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"] == 'Employer of' &&
            $this->_contactId == $contact->employer_id
          ) {
            $defaults['is_current_employer'] = 1;
            $this->_values['current_employee_id'] = $contact->id;
            $this->_values['current_employer_id'] = $this->_contactId;
          }
        }

        $relationshipID = $this->_values['id'];
        $query          = "SELECT id, note FROM civicrm_note where entity_table = 'civicrm_relationship' and entity_id = $relationshipID  order by modified_date desc";
        $dao            = new CRM_Core_DAO();
        $dao->query($query);
        if ($dao->fetch($query)) {
          $defaults['note'] = $dao->note;
        }
      }
    }
    else {
      $defaults['is_active'] = 1;
      $defaults['relationship_type_id'] = $this->_rtypeId;
    }

    $this->_enabled = $defaults['is_active'];
    return $defaults;
  }

  /**
   * This function is used to add the rules for form.
   *
   * @return None
   * @access public
   */
  function addRules() {
    if ($this->_cdType) {
      return;
    }

    if (!($this->_action & CRM_Core_Action::DELETE)) {
      $this->addRule('relationship_type_id', ts('Please select a relationship type.'), 'required');

      // add a form rule only when creating a new relationship
      // edit is severely limited, so add a simpleer form rule
      if ($this->_action & CRM_Core_Action::ADD) {
        $this->addFormRule(array('CRM_Contact_Form_Relationship', 'formRule'), $this);
        $this->addFormRule(array('CRM_Contact_Form_Relationship', 'dateRule'));
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $this->addFormRule(array('CRM_Contact_Form_Relationship', 'dateRule'));
      }
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    $relTypeID = explode('_', $this->_rtypeId, 3);

    if ($this->_action & CRM_Core_Action::DELETE) {

      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
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

    $callAjax = $this->get('callAjax');

    $searchRows = NULL;
    if (!$callAjax) {
      $searchRows = $this->get('searchRows');
    }
    else {

      $this->addElement('hidden', 'store_contacts', '', array('id' => 'store_contacts'));
      $sourceUrl = 'snippet=4&relType=' . $this->get('relType');
      $sourceUrl .= '&relContact=' . $this->get('relContact');
      $sourceUrl .= '&cid=' . $this->_contactId;

      $this->assign('searchCount', TRUE);

      // To handle employee of and employer of
      if (!empty($this->_relationshipTypeId) &&
        !empty($this->_rtype)
      ) {
        $sourceUrl .= '&typeName=' . $this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"];
      }
      $this->assign('sourceUrl', CRM_Utils_System::url('civicrm/ajax/relationshipcontacts', $sourceUrl, FALSE, NULL, FALSE));
    }

    $this->assign('callAjax', $callAjax);
    $this->_callAjax = $callAjax;

    $this->addElement('select',
      'relationship_type_id',
      ts('Relationship Type'),
      array(
        '' => ts('- select -')) +
      CRM_Contact_BAO_Relationship::getContactRelationshipType($this->_contactId,
        $this->_rtype,
        $this->_relationshipId,
        NULL, FALSE, 'label'
      )
    );

    // add a ajax facility for searching contacts
    $dataUrl = CRM_Utils_System::url('civicrm/ajax/search', 'reset=1', TRUE, NULL, FALSE);
    $this->assign('dataUrl', $dataUrl);
    CRM_Contact_Form_NewContact::buildQuickForm($this);

    $this->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'searchDate'));
    $this->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'searchDate'));
    $this->addElement('checkbox', 'is_active', ts('Enabled?'), NULL, NULL);

    $this->addElement('checkbox', 'is_permission_a_b', ts('Permission for contact a to view and update information for contact b'), NULL);
    $this->addElement('checkbox', 'is_permission_b_a', ts('permission for contact b to view and update information for contact a'), NULL);

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Relationship', 'description'));

    CRM_Contact_Form_Edit_Notes::buildQuickForm($this);

    $searchCount = $this->get('searchCount');
    $duplicateRelationship = $this->get('duplicateRelationship');
    $searchDone = $this->get('searchDone');

    $isEmployeeOf = $isEmployerOf = FALSE;
    if (!empty($this->_relationshipTypeId) &&
      !empty($this->_rtype)
    ) {
      if ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"] == 'Employee of') {
        $isEmployeeOf = TRUE;
      }
      elseif ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"] == 'Employer of') {
        $isEmployerOf = TRUE;
      }
    }

    $employers = $checkBoxes = $employees = array();
    if ($searchRows) {
      foreach ($searchRows as $id => $row) {
        $checkBoxes[$id] = $this->createElement('checkbox', $id, NULL, '');
        if ($isEmployeeOf) {
          $employers[$id] = $this->createElement('radio', NULL, $id, NULL, $id);
        }
        elseif ($isEmployerOf) {
          $employees[$id] = $this->createElement('checkbox', $id, NULL, '');
        }
      }

      $this->addGroup($checkBoxes, 'contact_check');
      $this->assign('searchRows', $searchRows);
    }

    if ($isEmployeeOf) {
      $this->assign('isEmployeeOf', $isEmployeeOf);
      if (!$callAjax) {
        $this->addGroup($employers, 'employee_of');
      }
    }
    elseif ($isEmployerOf) {
      $this->assign('isEmployerOf', $isEmployerOf);
      if (!$callAjax) {
        $this->addGroup($employees, 'employer_of');
      }
    }

    if ($callAjax && ($isEmployeeOf || $isEmployerOf)) {
      $this->addElement('hidden', 'store_employers', '', array('id' => 'store_employers'));
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->addElement('checkbox', 'is_current_employer');
    }

    $this->assign('duplicateRelationship', $duplicateRelationship);
    $this->assign('searchCount', $searchCount);
    $this->assign('searchDone', $searchDone);

    if ($this->get('contact_type')) {
      $typeLabel = CRM_Contact_BAO_ContactType::getLabel($this->get('contact_type'));
      $this->assign('contact_type', $this->get('contact_type'));
      $this->assign('contact_type_display', $typeLabel);
    }

    if ($searchDone) {
      $searchBtn = ts('Search Again');
    }
    else {
      $searchBtn = ts('Search');
    }
    $this->addElement('submit', $this->getButtonName('refresh'), $searchBtn, array('class' => 'form-submit', 'id' => 'search-button'));
    $this->addElement('submit', $this->getButtonName('refresh', 'save'), 'Quick Save', array('class' => 'form-submit', 'id' => 'quick-save'));
    $this->addElement('submit', $this->getButtonName('cancel'), ts('Cancel'), array('class' => 'form-submit'));

    $this->addElement('submit', $this->getButtonName('refresh', 'savedetails'), 'Save Relationship', array('class' => 'form-submit hiddenElement', 'id' => 'details-save'));
    $this->addElement('checkbox', 'add_current_employer', ts('Current Employer'), NULL);
    $this->addElement('checkbox', 'add_current_employee', ts('Current Employee'), NULL);

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Relationship');
    $this->assign('customDataSubType', $this->_relationshipTypeId);
    $this->assign('entityID', $this->_relationshipId);

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get(
    // 'uploadNames' );
    $buttonParams = array(
      'type' => 'upload',
      'name' => ts('Save Relationship'),
      'isDefault' => TRUE,
    );
    if ($callAjax) {
      $buttonParams['js'] = array('onclick' => ' submitAjaxData();');
    }

    $this->addButtons(array(
      $buttonParams
        ,
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   *  This function is called when the form is submitted
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $quickSave = FALSE;
    if (CRM_Utils_Array::value('_qf_Relationship_refresh_save', $_POST) ||
      CRM_Utils_Array::value('_qf_Relationship_refresh_savedetails', $_POST)
    ) {
      $quickSave = TRUE;
    }

    $this->set('searchDone', 0);
    $this->set('callAjax', FALSE);
    if (CRM_Utils_Array::value('_qf_Relationship_refresh', $_POST) || $quickSave) {
      if (is_numeric($params['contact_select_id'][1])) {
        if ($quickSave) {
          $params['contact_check'] = array($params['contact_select_id'][1] => 1);
        }
      }
      else {
        $this->set('callAjax', TRUE);
        $this->set('relType', $params['relationship_type_id']);
        $this->set('relContact', $params['contact'][1]);
        $quickSave = FALSE;
      }
      $this->set('searchDone', 1);
      if (!$quickSave) {
        return;
      }
    }

    // action is taken depending upon the mode
    $ids = array();
    $ids['contact'] = $this->_contactId;

    // modify params for ajax call
    $this->modifyParams($params);

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_Relationship::del($this->_relationshipId);
      return;
    }

    $relationshipTypeId = str_replace(array('_a_b', '_b_a'), array('', ''), $params['relationship_type_id']);
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $ids['relationship'] = $this->_relationshipId;
      $relation = CRM_Contact_BAO_Relationship::getContactIds($this->_relationshipId);
      $ids['contactTarget'] = ($relation->contact_id_a == $this->_contactId) ? $relation->contact_id_b : $relation->contact_id_a;

      //if relationship type change and previously it was
      //employer / emplyee relationship with current employer
      //than clear the current employer. CRM-3235.

      //make sure we has to have employer id before firing queries, CRM-7306
      $employerId = CRM_Utils_Array::value('current_employee_id', $this->_values);
      $isDisabled = TRUE;
      if (CRM_Utils_Array::value('is_active', $params)) {
        $isDisabled = FALSE;
      }
      $relChanged = TRUE;
      if ($relationshipTypeId == $this->_values['relationship_type_id']) {
        $relChanged = FALSE;
      }
      if ($employerId && ($isDisabled || $relChanged)) {
        CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($this->_values['current_employee_id']);
      }

      //if field key doesn't exists in params that means the user has unchecked checkbox,
      //hence fill FALSE to params
      $params['is_active'] = $isDisabled ? FALSE : TRUE;
      $params['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_a_b', $params, FALSE);
      $params['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_b_a', $params, FALSE);
    }
    elseif ($quickSave) {
      if (CRM_Utils_Array::value('add_current_employee', $params) &&
        $this->_allRelationshipNames[$relationshipTypeId]['name_a_b'] == 'Employee of'
      ) {
        $params['employee_of'] = $params['contact_select_id'][1];
      }
      elseif (CRM_Utils_Array::value('add_current_employer', $params) &&
        $this->_allRelationshipNames[$relationshipTypeId]['name_b_a'] == 'Employer of'
      ) {
        $params['employer_of'] = array($params['contact_select_id'][1] => 1);
      }
      if (!$this->_rtype) {
        $this->_rtype = str_replace($relationshipTypeId . '_', '', $params['relationship_type_id']);
      }
    }

    if (!$params['note']) {
      $params['note'] = 'null';
    }
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], NULL, TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], NULL, TRUE);

    //special case to handle if all checkboxes are unchecked
    $customFields = CRM_Core_BAO_CustomField::getFields('Relationship', FALSE, FALSE, $relationshipTypeId);
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess(
      $params,
      $customFields,
      $this->_relationshipId,
      'Relationship'
    );

    list($valid, $invalid, $duplicate, $saved, $relationshipIds) = CRM_Contact_BAO_Relationship::create($params, $ids);

    // if this is called from case view,
    //create an activity for case role removal.CRM-4480
    if ($this->_caseId) {
      CRM_Case_BAO_Case::createCaseRoleActivity($this->_caseId, $relationshipIds, $params['contact_check'], $this->_contactId);
    }

    $status = '';
    if ($valid) {
      CRM_Core_Session::setStatus(ts('New relationship created.', array('count' => $valid, 'plural' => '%count new relationships created.')), ts('Saved'), 'success');
    }
    if ($invalid) {
      CRM_Core_Session::setStatus(ts('%count relationship record was not created due to an invalid target contact type.', array('count' => $invalid, 'plural' => '%count relationship records were not created due to invalid target contact types.')), ts('%count invalid relationship record', array('count' => $invalid, 'plural' => '%count invalid relationship records')));
    }
    if ($duplicate) {
      CRM_Core_Session::setStatus(ts('One relationship was not created because it already exists.', array('count' => $duplicate, 'plural' => '%count relationships were not created because they already exist.')), ts('%count duplicate relationship', array('count' => $duplicate, 'plural' => '%count duplicate relationships')));
    }
    if ($saved) {
      CRM_Core_Session::setStatus(ts('Relationship record has been updated.'), ts('Saved'), 'success');
    }

    if (!empty($relationshipIds)) {
      $note               = new CRM_Core_DAO_Note();
      $note->entity_id    = $relationshipIds[0];
      $note->entity_table = 'civicrm_relationship';
      $noteIds            = array();
      if ($note->find(TRUE)) {
        $id = $note->id;
        $noteIds['id'] = $id;
      }

      $noteParams = array(
        'entity_id' => $relationshipIds[0],
        'entity_table' => 'civicrm_relationship',
        'note' => $params['note'],
        'contact_id' => $this->_contactId,
      );
      CRM_Core_BAO_Note::add($noteParams, $noteIds);

      $params['relationship_ids'] = $relationshipIds;
    }

    // Membership for related contacts CRM-1657
    if (CRM_Core_Permission::access('CiviMember') && (!$duplicate)) {
      if ($this->_action & CRM_Core_Action::ADD) {
        CRM_Contact_BAO_Relationship::relatedMemberships($this->_contactId,
          $params, $ids,
          $this->_action
        );
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        //fixes for CRM-7985
        //only if the relationship has been toggled to enable /disable
        if (CRM_Utils_Array::value('is_active', $params) != $this->_enabled) {
          $active = CRM_Utils_Array::value('is_active', $params) ? CRM_Core_Action::ENABLE : CRM_Core_Action::DISABLE;
          CRM_Contact_BAO_Relationship::disableEnableRelationship($this->_relationshipId, $active);
        }
      }
    }
    //handle current employee/employer relationship, CRM-3532
    if ($this->_allRelationshipNames[$relationshipTypeId]["name_{$this->_rtype}"] == 'Employee of') {
      $orgId = NULL;
      if (CRM_Utils_Array::value('employee_of', $params)) {
        $orgId = $params['employee_of'];
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        if (CRM_Utils_Array::value('is_current_employer', $params) &&
          CRM_Utils_Array::value('is_active', $params)
        ) {
          if (CRM_Utils_Array::value('contactTarget', $ids) !=
            CRM_Utils_Array::value('current_employer_id', $this->_values)
          ) {
            $orgId = CRM_Utils_Array::value('contactTarget', $ids);
          }
        }
        elseif (CRM_Utils_Array::value('contactTarget', $ids) ==
          CRM_Utils_Array::value('current_employer_id', $this->_values)
        ) {
          //clear current employer.
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($this->_contactId);
        }
      }

      //set current employer
      if ($orgId) {
        $currentEmpParams[$this->_contactId] = $orgId;
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }
    }
    elseif ($this->_allRelationshipNames[$relationshipTypeId]["name_{$this->_rtype}"] == 'Employer of') {
      $individualIds = array();
      if (CRM_Utils_Array::value('employer_of', $params)) {
        $individualIds = array_keys($params['employer_of']);
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        if (CRM_Utils_Array::value('is_current_employer', $params)) {
          if (CRM_Utils_Array::value('contactTarget', $ids) !=
            CRM_Utils_Array::value('current_employee_id', $this->_values)
          ) {
            $individualIds[] = CRM_Utils_Array::value('contactTarget', $ids);
          }
        }
        elseif (CRM_Utils_Array::value('contactTarget', $ids) ==
          CRM_Utils_Array::value('current_employee_id', $this->_values)
        ) {
          // clear current employee
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($ids['contactTarget']);
        }
      }

      //set current employee
      if (!empty($individualIds)) {

        //build the employee params.
        foreach ($individualIds as $key => $Id) {
          $currentEmpParams[$Id] = $this->_contactId;
        }

        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }
    }

    if ($quickSave) {
      $session = CRM_Core_Session::singleton();
      CRM_Utils_System::redirect($session->popUserContext());
    }
  }
  //end of function

  /**
   * function for validation
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params, $files, $form) {
    // hack, no error check for refresh
    if (CRM_Utils_Array::value('_qf_Relationship_refresh', $_POST) ||
      CRM_Utils_Array::value('_qf_Relationship_refresh_save', $_POST) ||
      CRM_Utils_Array::value('_qf_Relationship_refresh_savedetails', $_POST)
    ) {
      return TRUE;
    }

    $form->modifyParams($params);

    $ids                 = array();
    $session             = CRM_Core_Session::singleton();
    $ids['contact']      = $form->get('contactId');
    $ids['relationship'] = $form->get('relationshipId');

    $errors = array();
    $employerId = NULL;
    if (CRM_Utils_Array::value('contact_check', $params) && is_array($params['contact_check'])) {
      foreach ($params['contact_check'] as $cid => $dontCare) {
        $message = CRM_Contact_BAO_Relationship::checkValidRelationship($params, $ids, $cid);
        if ($message) {
          $errors['relationship_type_id'] = $message;
          break;
        }

        if ($cid == CRM_Utils_Array::value('employee_of', $params)) {
          $employerId = $cid;
        }
      }
    }
    else {
      if ($form->_callAjax) {
        $errors['store_contacts'] = ts('Select select at least one contact from Target Contact(s).');
      }
      else {
        $errors['contact_check'] = ts('Please select at least one contact.');
      }
    }

    if (CRM_Utils_Array::value('employee_of', $params) &&
      !$employerId
    ) {
      if ($form->_callAjax) {
        $errors['store_employer'] = ts('Current employer should be one of the selected contacts.');
      }
      else {
        $errors['employee_of'] = ts('Current employer should be one of the selected contacts.');
      }
    }

    if (CRM_Utils_Array::value('employer_of', $params) &&
      CRM_Utils_Array::value('contact_check', $params) &&
      array_diff(array_keys($params['employer_of']), array_keys($params['contact_check']))
    ) {
      if ($form->_callAjax) {
        $errors['store_employer'] = ts('Current employee should be among the selected contacts.');
      }
      else {
        $errors['employer_of'] = ts('Current employee should be among the selected contacts.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * function for date validation
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function dateRule($params) {
    $errors = array();

    // check start and end date
    if (CRM_Utils_Array::value('start_date', $params) &&
      CRM_Utils_Array::value('end_date', $params)
    ) {
      $start_date = CRM_Utils_Date::format(CRM_Utils_Array::value('start_date', $params));
      $end_date = CRM_Utils_Date::format(CRM_Utils_Array::value('end_date', $params));
      if ($start_date && $end_date && (int ) $end_date < (int ) $start_date) {
        $errors['end_date'] = ts('The relationship end date cannot be prior to the start date.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  function modifyParams(&$params) {
    if (!$this->_callAjax) {
      return;
    }

    if (CRM_Utils_Array::value('store_contacts', $params)) {
      $storedContacts = array();
      foreach (explode(',', $params['store_contacts']) as $value) {
        if ($value) {
          $storedContacts[$value] = 1;
        }
      }
      $params['contact_check'] = $storedContacts;
    }

    if (CRM_Utils_Array::value('store_employers', $params)) {
      $employeeContacts = array();
      foreach (explode(',', $params['store_employers']) as $value) {
        if ($value) {
          $employeeContacts[$value] = $value;
        }
      }
      if ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_{$this->_rtype}"] == 'Employee of') {
        $params['employee_of'] = current($employeeContacts);
      }
      else {
        $params['employer_of'] = $employeeContacts;
      }
    }
  }
}

