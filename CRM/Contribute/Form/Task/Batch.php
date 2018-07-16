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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class provides the functionality for batch profile update for contributions.
 */
class CRM_Contribute_Form_Task_Batch extends CRM_Contribute_Form_Task {

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum profile fields that will be displayed
   */
  protected $_maxFields = 9;

  /**
   * Variable to store redirect path
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    //get the contact read only fields to display.
    $readOnlyFields = array_merge(array('sort_name' => ts('Name')),
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );
    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_contributionIds,
      'CiviContribute', $returnProperties
    );
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      CRM_Core_Error::fatal('ufGroupId is missing');
    }
    $this->_title = ts('Update multiple contributions') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = array();
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = array('File');
    foreach ($this->_fields as $name => $field) {
      if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
        in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      ) {
        $suppressFields = TRUE;
        unset($this->_fields[$name]);
      }

      //fix to reduce size as we are using this field in grid
      if (is_array($field['attributes']) && !empty($this->_fields[$name]['attributes']['size']) && $this->_fields[$name]['attributes']['size'] > 19) {
        //shrink class to "form-text-medium"
        $this->_fields[$name]['attributes']['size'] = 19;
      }
    }

    $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Update Contribution(s)'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_contributionIds);

    //load all campaigns.
    if (array_key_exists('contribution_campaign_id', $this->_fields)) {
      $this->_componentCampaigns = array();
      CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
        'CRM_Contribute_DAO_Contribution',
        TRUE, 'campaign_id', 'id',
        ' id IN (' . implode(' , ', array_values($this->_contributionIds)) . ' ) '
      );
    }

    // It is possible to have fields that are required in CiviCRM not be required in the
    // profile. Overriding that here. Perhaps a better approach would be to
    // make them required in the schema & read that up through getFields functionality.
    $requiredFields = array('receive_date');

    //fix for CRM-2752
    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');
    foreach ($this->_contributionIds as $contributionId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Contribute_DAO_Contribution", $contributionId, 'financial_type_id');
      foreach ($this->_fields as $name => $field) {
        $entityColumnValue = array();
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          if (!empty($customValue['extends_entity_column_value'])) {
            $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              $customValue['extends_entity_column_value']
            );
          }

          if (!empty($entityColumnValue[$typeId]) ||
            CRM_Utils_System::isNull(CRM_Utils_Array::value($typeId, $entityColumnValue))
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contributionId);
          }
        }
        else {
          // handle non custom fields
          if (in_array($field['name'], $requiredFields)) {
            $field['is_required'] = TRUE;
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contributionId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields && $buttonName != '_qf_Batch_next') {
      CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple contributions."), ts('Unsupported Field Type'), 'error');
    }

    $this->addDefaultButtons(ts('Update Contributions'));
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    $defaults = array();
    foreach ($this->_contributionIds as $contributionId) {
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $contributionId, 'Contribute');
    }

    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->exportValues();
    // @todo extract submit functions &
    // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test
    // handling of custom data, specifically checkbox fields.
    if (isset($params['field'])) {
      foreach ($params['field'] as $contributionID => $value) {

        $value['id'] = $contributionID;
        if (!empty($value['financial_type'])) {
          $value['financial_type_id'] = $value['financial_type'];
        }

        $value['options'] = array(
          'reload' => 1,
        );
        $contribution = civicrm_api3('Contribution', 'create', $value);
        $contribution = $contribution['values'][$contributionID];

        // @todo add check as to whether the status is updated.
        if (!empty($value['contribution_status_id'])) {
          // @todo - use completeorder api or make api call do this.
          CRM_Contribute_BAO_Contribution::transitionComponentWithReturnMessage($contribution['id'],
            $value['contribution_status_id'],
            CRM_Utils_Array::value("field[{$contributionID}][contribution_status_id]", $this->_defaultValues),
            $contribution['receive_date']
          );
        }
      }
      CRM_Core_Session::setStatus(ts("Your updates have been saved."), ts('Saved'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts("No updates have been saved."), ts('Not Saved'), 'alert');
    }
  }

}
