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
 * This class provides the functionality for batch profile update for events.
 */
class CRM_Event_Form_Task_Batch extends CRM_Event_Form_Task {

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
   * Variable to store previous status id.
   * @var array
   */
  protected $_fromStatusIds;

  /**
   * All  the fields that belong to the group.
   * @var array
   */
  protected $_fields = [];

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    /*
     * initialize the task and row fields
     */
    parent::preProcess();

    //get the contact read only fields to display.
    $readOnlyFields = array_merge(['sort_name' => ts('Name')],
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );
    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_participantIds,
      'CiviEvent', $returnProperties
    );
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');
    if (!$ufGroupId) {
      CRM_Core_Error::statusBounce(ts('ufGroupId is missing'));
    }

    $this->_title = ts('Update multiple participants') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    $this->setTitle($this->_title);
    $this->addDefaultButtons(ts('Save'));

    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);
    if (array_key_exists('participant_status', $this->_fields)) {
      $this->assignToTemplate();
    }

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = ['File'];
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Update Participant(s)'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_participantIds);

    //load all campaigns.
    if (array_key_exists('participant_campaign_id', $this->_fields)) {
      $this->_componentCampaigns = [];
      CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
        'CRM_Event_DAO_Participant',
        TRUE, 'campaign_id', 'id',
        ' id IN (' . implode(' , ', array_values($this->_participantIds)) . ' ) '
      );
    }

    //fix for CRM-2752
    // get the option value for custom data type
    $customDataType = CRM_Core_OptionGroup::values('custom_data_type', FALSE, FALSE, FALSE, NULL, 'name');
    $roleCustomDataTypeID = array_search('ParticipantRole', $customDataType);
    $eventNameCustomDataTypeID = array_search('ParticipantEventName', $customDataType);
    $eventTypeCustomDataTypeID = array_search('ParticipantEventType', $customDataType);

    // build custom data getFields array
    $customFieldsRole = CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, $roleCustomDataTypeID);

    $customFieldsEvent = CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, $eventNameCustomDataTypeID);
    $customFieldsEventType = CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, $eventTypeCustomDataTypeID);

    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsRole,
      CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, NULL, TRUE)
    );
    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsEventType, $customFields);
    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsEvent, $customFields);

    foreach ($this->_participantIds as $participantId) {
      $roleId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $participantId, 'role_id');
      $eventId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $participantId, 'event_id');
      $eventTypeId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $eventId, 'event_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = $customFields[$customFieldID] ?? NULL;
          $entityColumnValue = [];
          if (!empty($customValue['extends_entity_column_value'])) {
            $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if (($roleCustomDataTypeID == $customValue['extends_entity_column_id']) &&
            in_array($roleId, $entityColumnValue)
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $participantId);
          }
          elseif (($eventNameCustomDataTypeID == $customValue['extends_entity_column_id']) &&
            in_array($eventId, $entityColumnValue)
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $participantId);
          }
          elseif ($eventTypeCustomDataTypeID == $customValue['extends_entity_column_id'] &&
            in_array($eventTypeId, $entityColumnValue)
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $participantId);
          }
          elseif (CRM_Utils_System::isNull($entityColumnValue)) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $participantId);
          }
        }
        else {
          if ($field['name'] === 'participant_role') {
            $field['is_multiple'] = TRUE;
          }
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $participantId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName !== '_qf_Batch_next') {
      CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple participants."), ts('Unsupported Field Type'), 'info');
    }

    $this->addDefaultButtons(ts('Update Participant(s)'));
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return [];
    }

    $defaults = [];
    foreach ($this->_participantIds as $participantId) {
      $details[$participantId] = [];

      $details[$participantId] = CRM_Event_BAO_Participant::participantDetails($participantId);
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $participantId, 'Event');

      //get the from status ids, CRM-4323
      if (array_key_exists('participant_status', $this->_fields)) {
        $this->_fromStatusIds[$participantId] = $defaults["field[$participantId][participant_status]"] ?? NULL;
      }
      if (array_key_exists('participant_role', $this->_fields)) {
        if ($defaults["field[{$participantId}][participant_role]"]) {
          $roles = $defaults["field[{$participantId}][participant_role]"];
          foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $roles) as $k => $v) {
            $defaults["field[$participantId][participant_role][{$v}]"] = 1;
          }
          unset($defaults["field[{$participantId}][participant_role]"]);
        }
      }
    }

    $this->assign('details', $details);
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $this->submit($params);
  }

  /**
   * @param int $participantId
   * @param int $statusId
   *
   * @throws \CRM_Core_Exception
   */
  public static function updatePendingOnlineContribution($participantId, $statusId) {

    $contributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution($participantId,
      'Event'
    );
    if (!$contributionId) {
      return;
    }

    //status rules.
    //1. participant - positive => contribution - completed.
    //2. participant - negative => contribution - cancelled.

    $positiveStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Positive'");
    $negativeStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    if (array_key_exists($statusId, $positiveStatuses)) {
      $params = [
        'component_id' => $participantId,
        'contribution_id' => $contributionId,
        'IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved' => 1,
      ];

      //change related contribution status.
      self::updateContributionStatus($params);
    }
    if (array_key_exists($statusId, $negativeStatuses)) {
      civicrm_api3('Contribution', 'create', ['id' => $contributionId, 'contribution_status_id' => 'Cancelled']);
      return;
    }

  }

  /**
   * Update contribution status.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   *
   * @deprecated
   * This is only called from one place in the code &
   * it is unclear whether it is a function on the way in or on the way out
   *
   */
  public static function updateContributionStatus($params) {
    $input = ['component' => 'event'];

    // reset template values.
    $template = CRM_Core_Smarty::singleton();
    $template->clearTemplateVars();

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $params['contribution_id'];
    $contribution->fetch();

    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', [
      'labelColumn' => 'name',
      'flip' => 1,
    ]);
    $input['IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved'] = $params['IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved'] ?? NULL;

    // status is not pending
    if ($contribution->contribution_status_id != $contributionStatuses['Pending']) {
      return;
    }

    //set values for ipn code.
    foreach (['fee_amount', 'check_number', 'payment_instrument_id'] as $field) {
      if (!$input[$field] = CRM_Utils_Array::value($field, $params)) {
        $input[$field] = $contribution->$field;
      }
    }
    if (!$input['trxn_id'] = CRM_Utils_Array::value('trxn_id', $params)) {
      $input['trxn_id'] = $contribution->invoice_id;
    }
    if (!$input['amount'] = CRM_Utils_Array::value('total_amount', $params)) {
      $input['amount'] = $contribution->total_amount;
    }
    $input['is_test'] = $contribution->is_test;
    $input['net_amount'] = $contribution->net_amount;
    if (!empty($input['fee_amount']) && !empty($input['amount'])) {
      $input['net_amount'] = $input['amount'] - $input['fee_amount'];
    }

    //complete the contribution.
    // @todo use the api - ie civicrm_api3('Contribution', 'completetransaction', $input);
    // as this method is not preferred / supported.
    CRM_Contribute_BAO_Contribution::completeOrder($input, NULL, $contribution->id ?? NULL);

    // reset template values before processing next transactions
    $template->clearTemplateVars();
  }

  /**
   * Assign the minimal set of variables to the template.
   */
  public function assignToTemplate() {
    $notifyingStatuses = ['Pending from waitlist', 'Pending from approval', 'Expired', 'Cancelled'];
    $notifyingStatuses = array_intersect($notifyingStatuses, CRM_Event_PseudoConstant::participantStatus());
    $this->assign('status', TRUE);
    if (!empty($notifyingStatuses)) {
      $s = '<em>' . implode('</em>, <em>', $notifyingStatuses) . '</em>';
      $this->assign('notifyingStatuses', $s);
    }
  }

  /**
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public function submit($params) {
    $statusClasses = CRM_Event_PseudoConstant::participantStatusClass();
    if (isset($params['field'])) {
      foreach ($params['field'] as $participantID => $value) {

        //check for custom data
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          $participantID,
          'Participant'
        );
        foreach (array_keys($value) as $fieldName) {
          // Unset the original custom field now that it has been formatting to the 'custom'
          // array as it may not be in the right format for the api as is (notably for
          // multiple checkbox values).
          // @todo extract submit functions on other Batch update classes &
          // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test them.
          if (substr($fieldName, 0, 7) === 'custom_') {
            unset($value[$fieldName]);
          }
        }

        $value['id'] = $participantID;

        if (!empty($value['participant_role'])) {
          if (is_array($value['participant_role'])) {
            $value['role_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($value['participant_role']));
          }
          else {
            $value['role_id'] = $value['participant_role'];
          }
        }

        //need to send mail when status change
        $statusChange = FALSE;
        $relatedStatusChange = FALSE;
        if (!empty($value['participant_status'])) {
          $value['status_id'] = $value['participant_status'];
          $fromStatusId = $this->_fromStatusIds[$participantID] ?? NULL;
          if (!$fromStatusId) {
            $fromStatusId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'status_id');
          }

          if ($fromStatusId != $value['status_id']) {
            $relatedStatusChange = TRUE;
          }
          if ($statusClasses[$fromStatusId] != $statusClasses[$value['status_id']]) {
            $statusChange = TRUE;
          }
        }

        unset($value['participant_status']);

        civicrm_api3('Participant', 'create', $value);

        //need to trigger mails when we change status
        if ($statusChange) {
          CRM_Event_BAO_Participant::transitionParticipants([$participantID], $value['status_id'], $fromStatusId);
        }
        if ($relatedStatusChange && $participantID && $value['status_id']) {
          //update related contribution status, CRM-4395
          self::updatePendingOnlineContribution((int) $participantID, $value['status_id']);
        }
      }
      CRM_Core_Session::setStatus(ts('The updates have been saved.'), ts('Saved'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('No updates have been saved.'), ts('Not Saved'), 'alert');
    }
  }

}
