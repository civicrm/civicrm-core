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
 * This class provides the functionality for batch profile update for Activities
 */
class CRM_Activity_Form_Task_Batch extends CRM_Activity_Form_Task {

  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * maximum profile fields that will be displayed
   *
   */
  protected $_maxFields = 9;

  /**
   * variable to store redirect path
   *
   */
  protected $_userContext;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    /*
     * initialize the task and row fields
     */

    parent::preProcess();

    //get the contact read only fields to display.
    $readOnlyFields = array_merge(array('sort_name' => ts('Added By'), 'target_sort_name' => ts('With Contact')),
      CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options',
        TRUE, NULL, FALSE, 'name', TRUE
      )
    );

    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_activityHolderIds,
      'Activity', $returnProperties
    );
    $this->assign('contactDetails', $contactDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $ufGroupId = $this->get('ufGroupId');

    if (!$ufGroupId) {
      CRM_Core_Error::fatal('ufGroupId is missing');
    }
    $this->_title = ts('Batch Update for Activities') . ' - ' . CRM_Core_BAO_UFGroup::getTitle($ufGroupId);
    CRM_Utils_System::setTitle($this->_title);

    $this->addDefaultButtons(ts('Save'));
    $this->_fields = array();
    $this->_fields = CRM_Core_BAO_UFGroup::getFields($ufGroupId, FALSE, CRM_Core_Action::VIEW);

    // remove file type field and then limit fields
    $suppressFields = FALSE;
    $removehtmlTypes = array('File', 'Autocomplete-Select');
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

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Update Activities'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );


    $this->assign('profileTitle', $this->_title);
    $this->assign('componentIds', $this->_activityHolderIds);
    $fileFieldExists = FALSE;

    //load all campaigns.
    if (array_key_exists('activity_campaign_id', $this->_fields)) {
      $this->_componentCampaigns = array();
      CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
        'CRM_Activity_DAO_Activity',
        TRUE, 'campaign_id', 'id',
        ' id IN (' . implode(' , ', array_values($this->_activityHolderIds)) . ' ) '
      );
    }

    $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

    foreach ($this->_activityHolderIds as $activityId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Activity_DAO_Activity", $activityId, 'activity_type_id');
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          $customValue = CRM_Utils_Array::value($customFieldID, $customFields);
          if (CRM_Utils_Array::value('extends_entity_column_value', $customValue)) {
            $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
              $customValue['extends_entity_column_value']
            );
          }
          if (CRM_Utils_Array::value($typeId, $entityColumnValue) ||
            CRM_Utils_System::isNull($entityColumnValue[$typeId])
          ) {
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
          }
        }
        else {
          // handle non custom fields
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $activityId);
        }
      }
    }

    $this->assign('fields', $this->_fields);

    // don't set the status message when form is submitted.
    // $buttonName = $this->controller->getButtonName('submit');

    if ($suppressFields) {
      CRM_Core_Session::setStatus(ts("FILE or Autocomplete Select type field(s) in the selected profile are not supported for Batch Update."), ts("Some fields have been excluded"), "info");
    }

    $this->addDefaultButtons(ts('Update Activities'));
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (empty($this->_fields)) {
      return;
    }

    $defaults = array();
    foreach ($this->_activityHolderIds as $activityId) {
      $details[$activityId] = array();
      CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $activityId, 'Activity');
    }

    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->exportValues();

    if (isset($params['field'])) {
      foreach ($params['field'] as $key => $value) {

        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value,
          CRM_Core_DAO::$_nullObject,
          $key, 'Activity'
        );
        $value['id'] = $key;

        if ($value['activity_date_time']) {
          $value['activity_date_time'] = CRM_Utils_Date::processDate($value['activity_date_time'], $value['activity_date_time_time']);
        }

        if (CRM_Utils_Array::value('activity_status_id', $value)) {
          $value['status_id'] = $value['activity_status_id'];
        }

        if (CRM_Utils_Array::value('activity_details', $value)) {
          $value['details'] = $value['activity_details'];
        }

        if (CRM_Utils_Array::value('activity_duration', $value)) {
          $value['duration'] = $value['activity_duration'];
        }

        if (CRM_Utils_Array::value('activity_location', $value)) {
          $value['location'] = $value['activity_location'];
        }

        if (CRM_Utils_Array::value('activity_subject', $value)) {
          $value['subject'] = $value['activity_subject'];
        }

        $query = "
SELECT activity_type_id , source_contact_id
FROM   civicrm_activity
WHERE  id = %1";
        $params = array(1 => array($key, 'Integer'));
        $dao = CRM_Core_DAO::executeQuery($query, $params);
        $dao->fetch();

        // Get Activity Type ID
        $value['activity_type_id'] = $dao->activity_type_id;

        // Get Conatct ID
        $value['source_contact_id'] = $dao->source_contact_id;

        // make call use API 3
        $value['version'] = 3;

        $activityId = civicrm_api('activity', 'update', $value);

        // add custom field values
        if (CRM_Utils_Array::value('custom', $value) &&
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
  //end of function
}

