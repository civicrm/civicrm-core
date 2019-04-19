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
 * This class provides the functionality for batch profile update for Activities
 */
class CRM_Activity_Form_Task_Batch extends CRM_Activity_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum profile fields that will be displayed.
   * @var int
   */
  protected $_maxFields = 9;

  /**
   * Variable to store redirect path.
   * @var string
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    // Initialize the task and row fields.
    parent::preProcess();

    // Get the contact read only fields to display.
    $readOnlyFields = array_merge(['sort_name' => ts('Added By'), 'target_sort_name' => ts('With Contact')],
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );

    // Get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_activityHolderIds,
      'Activity', $returnProperties
    );
    $readOnlyFields['assignee_display_name'] = ts('Assigned to');
    if (!empty($contactDetails)) {
      foreach ($contactDetails as $key => $value) {
        $assignee = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId($key);
        foreach ($assignee as $values) {
          $assigneeContact[] = CRM_Contact_BAO_Contact::displayName($values);
        }
        $contactDetails[$key]['assignee_display_name'] = !empty($assigneeContact) ? implode(';', $assigneeContact) : NULL;
      }
    }
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      throw new CRM_Core_Exception('The profile id is missing');
    }
    $this->_title = ts('Update multiple activities') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = [];
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = ['File'];
    foreach ($this->_fields as $name => $field) {
      if (CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      // Fix to reduce size as we are using this field in grid.
      if (is_array($field['attributes']) && !empty($this->_fields[$name]['attributes']['size']) && $this->_fields[$name]['attributes']['size'] > 19) {
        // Shrink class to "form-text-medium".
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Update Activities'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_activityHolderIds);

    // Load all campaigns.
    if (array_key_exists('activity_campaign_id', $this->_fields)) {
      $this->_componentCampaigns = [];
      CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
        'CRM_Activity_DAO_Activity',
        TRUE, 'campaign_id', 'id',
        ' id IN (' . implode(' , ', array_values($this->_activityHolderIds)) . ' ) '
      );
    }

    $customFields = CRM_Core_BAO_CustomField::getFields('Activity');
    // It is possible to have fields that are required in CiviCRM not be required in the
    // profile. Overriding that here. Perhaps a better approach would be to
    // make them required in the schema & read that up through getFields functionality.
    $requiredFields = ['activity_date_time'];

    foreach ($this->_activityHolderIds as $activityId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Activity_DAO_Activity", $activityId, 'activity_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          if (!empty($customValue['extends_entity_column_value'])) {
            $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if (!empty($entityColumnValue[$typeId]) ||
            CRM_Utils_System::isNull($entityColumnValue[$typeId])
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
          }
        }
        else {
          // Handle non custom fields.
          if (in_array($field['name'], $requiredFields)) {
            $field['is_required'] = TRUE;
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // Don't set the status message when form is submitted.
    // $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields) {
      CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple activities."), ts('Some Fields Excluded'), 'info');
    }

    $this->addDefaultButtons(ts('Update Activities'));
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    $defaults = [];
    foreach ($this->_activityHolderIds as $activityId) {
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $activityId, 'Activity');
    }

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->exportValues();

    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {

        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          $key, 'Activity'
        );
        $value['id'] = $key;

        if (!empty($value['activity_status_id'])) {
          $value['status_id'] = $value['activity_status_id'];
        }

        if (!empty($value['activity_details'])) {
          $value['details'] = $value['activity_details'];
        }

        if (!empty($value['activity_location'])) {
          $value['location'] = $value['activity_location'];
        }

        if (!empty($value['activity_subject'])) {
          $value['subject'] = $value['activity_subject'];
        }

        $activityId = civicrm_api3('activity', 'create', $value);

        // @todo this would be done by the api call above if the parames were passed through.
        // @todo extract submit functions &
        // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test
        // handling of custom data, specifically checkbox fields.
        if (!empty($value['custom']) &&
          is_array($value['custom'])
        ) {
          CRM_Core_BAO_CustomValueTable::store($value['custom'], 'civicrm_activity', $activityId['id']);
        }
      }
      CRM_Core_Session::setStatus("", ts("Updates Saved"), "success");
    }
    else {
      CRM_Core_Session::setStatus("", ts("No Updates Saved"), "info");
    }
  }

}
