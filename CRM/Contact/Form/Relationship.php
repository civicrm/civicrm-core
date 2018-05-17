<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * This class generates form components for relationship.
 */
class CRM_Contact_Form_Relationship extends CRM_Core_Form {

  /**
   * The relationship id, used when editing the relationship
   *
   * @var int
   */
  public $_relationshipId;

  /**
   * The contact id, used when add/edit relationship
   *
   * @var int
   */
  public $_contactId;

  /**
   * This is a string which is either a_b or  b_a  used to determine the relationship between to contacts
   */
  public $_rtype;

  /**
   * This is a string which is used to determine the relationship between to contacts
   */
  public $_rtypeId;

  /**
   * Display name of contact a
   */
  public $_display_name_a;

  /**
   * Display name of contact b
   */
  public $_display_name_b;

  /**
   * The relationship type id
   *
   * @var int
   */
  public $_relationshipTypeId;

  /**
   * An array of all relationship names
   *
   * @var array
   */
  public $_allRelationshipNames;

  /**
   * @var bool
   */
  public $_enabled;

  /**
   * @var bool
   */
  public $_isCurrentEmployer;

  /**
   * @var string
   */
  public $_contactType;

  /**
   * The relationship values if Updating relationship
   */
  public $_values;

  /**
   * Case id if it called from case context
   */
  public $_caseId;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Relationship';
  }

  public function preProcess() {
    $this->_contactId = $this->get('contactId');

    $this->_contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'contact_type');

    $this->_relationshipId = $this->get('id');

    $this->_rtype = CRM_Utils_Request::retrieve('rtype', 'String', $this);

    $this->_rtypeId = CRM_Utils_Request::retrieve('relTypeId', 'String', $this);

    $this->_display_name_a = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'display_name');

    $this->assign('display_name_a', $this->_display_name_a);
    //get the relationship values.
    $this->_values = array();
    if ($this->_relationshipId) {
      $params = array('id' => $this->_relationshipId);
      CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Relationship', $params, $this->_values);
    }

    // Check for permissions
    if (in_array($this->_action, array(CRM_Core_Action::ADD, CRM_Core_Action::UPDATE, CRM_Core_Action::DELETE))) {
      if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)
        && !CRM_Contact_BAO_Contact_Permission::allow($this->_values['contact_id_b'], CRM_Core_Permission::EDIT)) {
        CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to edit this contact.'));
      }
    }

    // Set page title based on action
    switch ($this->_action) {
      case CRM_Core_Action::VIEW:
        CRM_Utils_System::setTitle(ts('View Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::ADD:
        CRM_Utils_System::setTitle(ts('Add Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::UPDATE:
        CRM_Utils_System::setTitle(ts('Edit Relationship for %1', array(1 => $this->_display_name_a)));
        break;

      case CRM_Core_Action::DELETE:
        CRM_Utils_System::setTitle(ts('Delete Relationship for %1', array(1 => $this->_display_name_a)));
        break;
    }

    $this->_caseId = CRM_Utils_Request::retrieve('caseID', 'Integer', $this);

    if (!$this->_rtypeId) {
      $params = CRM_Utils_Request::exportValues();
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

    //need to assign custom data type and subtype to the template - FIXME: explain why
    $this->assign('customDataType', 'Relationship');
    $this->assign('customDataSubType', $this->_relationshipTypeId);
    $this->assign('entityID', $this->_relationshipId);

    //use name as it remain constant, CRM-3336
    $this->_allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');

    // Current employer?
    if ($this->_action & CRM_Core_Action::UPDATE) {
      if ($this->_allRelationshipNames[$this->_relationshipTypeId]["name_a_b"] == 'Employee of') {
        $this->_isCurrentEmployer = $this->_values['contact_id_b'] == CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_values['contact_id_a'], 'employer_id');
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_relationshipTypeId, 1, 'Relationship', $this->_relationshipId);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {

    $defaults = array();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      if (!empty($this->_values)) {
        $defaults['relationship_type_id'] = $this->_rtypeId;
        if (!empty($this->_values['start_date'])) {
          list($defaults['start_date']) = CRM_Utils_Date::setDateDefaults($this->_values['start_date']);
        }
        if (!empty($this->_values['end_date'])) {
          list($defaults['end_date']) = CRM_Utils_Date::setDateDefaults($this->_values['end_date']);
        }
        $defaults['description'] = CRM_Utils_Array::value('description', $this->_values);
        $defaults['is_active'] = CRM_Utils_Array::value('is_active', $this->_values);

        // The javascript on the form will swap these fields if it is a b_a relationship, so we compensate here
        $defaults['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_' . $this->_rtype, $this->_values);
        $defaults['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_' . strrev($this->_rtype), $this->_values);

        $defaults['is_current_employer'] = $this->_isCurrentEmployer;

        // Load info about the related contact
        $contact = new CRM_Contact_DAO_Contact();
        if ($this->_rtype == 'a_b' && $this->_values['contact_id_a'] == $this->_contactId) {
          $contact->id = $this->_values['contact_id_b'];
        }
        else {
          $contact->id = $this->_values['contact_id_a'];
        }
        if ($contact->find(TRUE)) {
          $defaults['related_contact_id'] = $contact->id;
          $this->_display_name_b = $contact->display_name;
          $this->assign('display_name_b', $this->_display_name_b);
        }

        $noteParams = array(
          'entity_id' => $this->_relationshipId,
          'entity_table' => 'civicrm_relationship',
          'limit' => 1,
          'version' => 3,
        );
        $note = civicrm_api('Note', 'getsingle', $noteParams);
        $defaults['note'] = CRM_Utils_Array::value('note', $note);
      }
    }
    else {
      $defaults['is_active'] = $defaults['is_current_employer'] = 1;
      $defaults['relationship_type_id'] = $this->_rtypeId;
      $defaults['is_permission_a_b'] = $defaults['is_permission_b_a'] = CRM_Contact_BAO_Relationship::NONE;
    }

    $this->_enabled = $defaults['is_active'];
    return $defaults;
  }

  /**
   * Add the rules for form.
   */
  public function addRules() {

    if (!($this->_action & CRM_Core_Action::DELETE)) {
      $this->addFormRule(array('CRM_Contact_Form_Relationship', 'dateRule'));
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
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

    // Select list
    $relationshipList = CRM_Contact_BAO_Relationship::getContactRelationshipType($this->_contactId, $this->_rtype, $this->_relationshipId);

    $this->assign('contactTypes', CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE));

    foreach ($this->_allRelationshipNames as $id => $vals) {
      if ($vals['name_a_b'] === 'Employee of') {
        $this->assign('employmentRelationship', $id);
        break;
      }
    }

    $this->addField(
      'relationship_type_id',
      array(
        'options' => array('' => ts('- select -')) + $relationshipList,
        'class' => 'huge',
        'placeholder' => '- select -',
        'option_url' => 'civicrm/admin/reltype',
        'option_context' => array(
          'contact_id' => $this->_contactId,
          'relationship_direction' => $this->_rtype,
          'relationship_id' => $this->_relationshipId,
          'is_form' => TRUE,
        ),
      ),
      TRUE
    );

    $label = $this->_action & CRM_Core_Action::ADD ? ts('Contact(s)') : ts('Contact');
    $contactField = $this->addField('related_contact_id', array('label' => $label, 'name' => 'contact_id_b', 'multiple' => TRUE, 'create' => TRUE), TRUE);
    // This field cannot be updated
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $contactField->freeze();
    }

    $this->add('advcheckbox', 'is_current_employer', $this->_contactType == 'Organization' ? ts('Current Employee') : ts('Current Employer'));

    $this->addField('start_date', array('label' => ts('Start Date'), 'formatType' => 'searchDate'));
    $this->addField('end_date', array('label' => ts('End Date'), 'formatType' => 'searchDate'));

    $this->addField('is_active', array('label' => ts('Enabled?'), 'type' => 'advcheckbox'));

    $this->addField('is_permission_a_b', array(), TRUE);
    $this->addField('is_permission_b_a', array(), TRUE);

    $this->addField('description', array('label' => ts('Description')));

    CRM_Contact_Form_Edit_Notes::buildQuickForm($this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
        ),
      ));
    }
    else {
      // make this form an upload since we don't know if the custom data injected dynamically is of type file etc.
      $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save Relationship'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      ));
    }
  }

  /**
   * This function is called when the form is submitted and also from unit test.
   * @param array $params
   *
   * @return array
   */
  public function submit($params) {
    switch ($this->getAction()) {
      case CRM_Core_Action::DELETE:
        $this->deleteAction($this->_relationshipId);
        return array();

      case CRM_Core_Action::UPDATE:
        return $this->updateAction($params);

      default:
        return $this->createAction($params);
    }
  }

  /**
   * This function is called when the form is submitted.
   */
  public function postProcess() {
    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);

    $values = $this->submit($params);
    if (empty($values)) {
      return;
    }
    list ($params, $relationshipIds) = $values;

    // if this is called from case view,
    //create an activity for case role removal.CRM-4480
    // @todo this belongs in the BAO.
    if ($this->_caseId) {
      CRM_Case_BAO_Case::createCaseRoleActivity($this->_caseId, $relationshipIds, $params['contact_check'], $this->_contactId);
    }

    // @todo this belongs in the BAO.
    $note = !empty($params['note']) ? $params['note'] : '';
    $this->saveRelationshipNotes($relationshipIds, $note);

    $this->setEmploymentRelationship($params, $relationshipIds);

    // Refresh contact tabs which might have been affected
    $this->ajaxResponse = array(
      'reloadBlocks' => array('#crm-contactinfo-content'),
      'updateTabs' => array(
        '#tab_member' => CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactId),
        '#tab_contribute' => CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId),
      ),
    );

  }

  /**
   * Date validation.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function dateRule($params) {
    $errors = array();

    // check start and end date
    if (!empty($params['start_date']) && !empty($params['end_date'])) {
      $start_date = CRM_Utils_Date::format(CRM_Utils_Array::value('start_date', $params));
      $end_date = CRM_Utils_Date::format(CRM_Utils_Array::value('end_date', $params));
      if ($start_date && $end_date && (int ) $end_date < (int ) $start_date) {
        $errors['end_date'] = ts('The relationship end date cannot be prior to the start date.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set Status message to reflect outcome of the update action.
   *
   * @param array $outcome
   *   Outcome of save action - including
   *   - 'valid' : Number of valid relationships attempted.
   *   - 'invalid' : Number of invalid relationships attempted.
   *   - 'duplicate' : Number of duplicate relationships attempted.
   *   - 'saved' : boolean of whether save was successful
   */
  protected function setMessage($outcome) {
    if (!empty($outcome['valid']) && empty($outcome['saved'])) {
      CRM_Core_Session::setStatus(ts('Relationship created.', array(
        'count' => $outcome['valid'],
        'plural' => '%count relationships created.',
      )), ts('Saved'), 'success');
    }
    if (!empty($outcome['invalid'])) {
      CRM_Core_Session::setStatus(ts('%count relationship record was not created due to an invalid contact type.', array(
        'count' => $outcome['invalid'],
        'plural' => '%count relationship records were not created due to invalid contact types.',
      )), ts('%count invalid relationship record', array(
        'count' => $outcome['invalid'],
        'plural' => '%count invalid relationship records',
      )));
    }
    if (!empty($outcome['duplicate'])) {
      CRM_Core_Session::setStatus(ts('One relationship was not created because it already exists.', array(
        'count' => $outcome['duplicate'],
        'plural' => '%count relationships were not created because they already exist.',
      )), ts('%count duplicate relationship', array(
        'count' => $outcome['duplicate'],
        'plural' => '%count duplicate relationships',
      )));
    }
    if (!empty($outcome['saved'])) {
      CRM_Core_Session::setStatus(ts('Relationship record has been updated.'), ts('Saved'), 'success');
    }
  }

  /**
   * @param $relationshipList
   * @return array
   */
  public static function getRelationshipTypeMetadata($relationshipList) {
    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo(TRUE);
    $allRelationshipNames = CRM_Core_PseudoConstant::relationshipType('name');
    $jsData = array();
    // Get just what we need to keep the dom small
    $whatWeWant = array_flip(array(
      'contact_type_a',
      'contact_type_b',
      'contact_sub_type_a',
      'contact_sub_type_b',
    ));
    foreach ($allRelationshipNames as $id => $vals) {
      if (isset($relationshipList["{$id}_a_b"]) || isset($relationshipList["{$id}_b_a"])) {
        $jsData[$id] = array_filter(array_intersect_key($allRelationshipNames[$id], $whatWeWant));
        // Add user-friendly placeholder
        foreach (array('a', 'b') as $x) {
          $type = !empty($jsData[$id]["contact_sub_type_$x"]) ? $jsData[$id]["contact_sub_type_$x"] : CRM_Utils_Array::value("contact_type_$x", $jsData[$id]);
          $jsData[$id]["placeholder_$x"] = $type ? ts('- select %1 -', array(strtolower($contactTypes[$type]['label']))) : ts('- select contact -');
        }
      }
    }
    return $jsData;
  }

  /**
   * Handling 'delete relationship' action
   *
   * @param int $id
   *   Relationship ID
   */
  private function deleteAction($id) {
    CRM_Contact_BAO_Relationship::del($id);

    // reload all blocks to reflect this change on the user interface.
    $this->ajaxResponse['reloadBlocks'] = array('#crm-contactinfo-content');
  }

  /**
   * Handling updating relationship action
   *
   * @param array $params
   *
   * @return array
   */
  private function updateAction($params) {
    $params = $this->preparePostProcessParameters($params);
    $params = $params[0];

    try {
      civicrm_api3('relationship', 'create', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      throw new CRM_Core_Exception('Relationship create error ' . $e->getMessage());
    }

    $this->setMessage(array('saved' => TRUE));
    return array($params, array($this->_relationshipId));
  }

  /**
   * Handling creating relationship action
   *
   * @param array $params
   *
   * @return array
   */
  private function createAction($params) {
    list($params, $primaryContactLetter) = $this->preparePostProcessParameters($params);

    $outcome = CRM_Contact_BAO_Relationship::createMultiple($params, $primaryContactLetter);

    $relationshipIds = $outcome['relationship_ids'];

    $this->setMessage($outcome);

    return array($params, $relationshipIds);
  }


  /**
   * Prepares parameters to be used for create/update actions
   *
   * @param array $params
   *
   * @return array
   */
  private function preparePostProcessParameters($params) {
    $relationshipTypeParts = explode('_', $params['relationship_type_id']);

    $params['relationship_type_id'] = $relationshipTypeParts[0];
    $params['contact_id_' . $relationshipTypeParts[1]] = $this->_contactId;

    if (empty($this->_relationshipId)) {
      $params['contact_id_' . $relationshipTypeParts[2]] = explode(',', $params['related_contact_id']);
    }
    else {
      $params['id'] = $this->_relationshipId;
      $params['contact_id_' . $relationshipTypeParts[2]] = $params['related_contact_id'];

      foreach (array('start_date', 'end_date') as $dateParam) {
        if (!empty($params[$dateParam])) {
          $params[$dateParam] = CRM_Utils_Date::processDate($params[$dateParam]);
        }
      }
    }

    // CRM-14612 - Don't use adv-checkbox as it interferes with the form js
    $params['is_permission_a_b'] = CRM_Utils_Array::value('is_permission_a_b', $params, 0);
    $params['is_permission_b_a'] = CRM_Utils_Array::value('is_permission_b_a', $params, 0);

    return array($params, $relationshipTypeParts[1]);
  }

  /**
   * Updates/Creates relationship notes
   *
   * @param array $relationshipIds
   * @param string $note
   */
  private function saveRelationshipNotes($relationshipIds, $note) {
    foreach ($relationshipIds as $id) {
      $noteParams = array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_relationship',
      );

      $existing = civicrm_api3('note', 'get', $noteParams);
      if (!empty($existing['id'])) {
        $noteParams['id'] = $existing['id'];
      }

      $action = NULL;
      if (!empty($note)) {
        $action = 'create';
        $noteParams['note'] = $note;
        $noteParams['contact_id'] = $this->_contactId;
      }
      elseif (!empty($noteParams['id'])) {
        $action = 'delete';
      }

      if (!empty($action)) {
        civicrm_api3('note', $action, $noteParams);
      }
    }
  }

  /**
   * Sets current employee/employer relationship
   *
   * @param $params
   * @param array $relationshipIds
   */
  private function setEmploymentRelationship($params, $relationshipIds) {
    $employerParams = array();
    foreach ($relationshipIds as $id) {
      if (!CRM_Contact_BAO_Relationship::isCurrentEmployerNeedingToBeCleared($params, $id)
        //don't think this is required to check again.
        && $this->_allRelationshipNames[$params['relationship_type_id']]["name_a_b"] == 'Employee of') {
        // Fixme this is dumb why do we have to look this up again?
        $rel = CRM_Contact_BAO_Relationship::getRelationshipByID($id);
        $employerParams[$rel->contact_id_a] = $rel->contact_id_b;
      }
    }
    if (!empty($employerParams)) {
      // @todo this belongs in the BAO.
      CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($employerParams);
    }
  }

}
