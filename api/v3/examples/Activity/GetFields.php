<?php
/**
 * Test Generated example demonstrating the Activity.getfields API.
 *
 * @return array
 *   API result array
 */
function activity_getfields_example() {
  $params = array(
    'action' => 'create',
  );

  try{
    $result = civicrm_api3('Activity', 'getfields', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function activity_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 29,
    'values' => array(
      'source_record_id' => array(
        'name' => 'source_record_id',
        'type' => 1,
        'title' => 'Source Record',
        'description' => 'Artificial FK to original transaction (e.g. contribution) IF it is not an Activity. Table can be figured out through activity_type_id, and further through component registry.',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
      ),
      'activity_type_id' => array(
        'name' => 'activity_type_id',
        'type' => 1,
        'title' => 'Activity Type ID',
        'description' => 'FK to civicrm_option_value.id, that has to be valid, registered activity type.',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_activity.activity_type_id',
        'headerPattern' => '/(activity.)?type(.id$)/i',
        'export' => TRUE,
        'default' => '1',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'activity_type',
          'optionEditPath' => 'civicrm/admin/options/activity_type',
        ),
      ),
      'activity_date_time' => array(
        'name' => 'activity_date_time',
        'type' => 12,
        'title' => 'Activity Date',
        'description' => 'Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.',
        'import' => TRUE,
        'where' => 'civicrm_activity.activity_date_time',
        'headerPattern' => '/(activity.)?date(.time$)?/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select Date',
          'format' => 'activityDateTime',
        ),
      ),
      'phone_id' => array(
        'name' => 'phone_id',
        'type' => 1,
        'title' => 'Phone (called) ID',
        'description' => 'Phone ID of the number called (optional - used if an existing phone number is selected).',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'FKClassName' => 'CRM_Core_DAO_Phone',
        'html' => array(
          'type' => 'EntityRef',
          'size' => 6,
          'maxlength' => 14,
        ),
        'FKApiName' => 'Phone',
      ),
      'phone_number' => array(
        'name' => 'phone_number',
        'type' => 2,
        'title' => 'Phone (called) Number',
        'description' => 'Phone number in case the number does not exist in the civicrm_phone table.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ),
      ),
      'priority_id' => array(
        'name' => 'priority_id',
        'type' => 1,
        'title' => 'Priority',
        'description' => 'ID of the priority given to this activity. Foreign key to civicrm_option_value.',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'priority',
          'optionEditPath' => 'civicrm/admin/options/priority',
        ),
      ),
      'parent_id' => array(
        'name' => 'parent_id',
        'type' => 1,
        'title' => 'Parent Activity Id',
        'description' => 'Parent meeting ID (if this is a follow-up item). This is not currently implemented',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'FKClassName' => 'CRM_Activity_DAO_Activity',
        'FKApiName' => 'Activity',
      ),
      'is_auto' => array(
        'name' => 'is_auto',
        'type' => 16,
        'title' => 'Auto',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
      ),
      'relationship_id' => array(
        'name' => 'relationship_id',
        'type' => 1,
        'title' => 'Relationship Id',
        'description' => 'FK to Relationship ID',
        'default' => 'NULL',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'FKClassName' => 'CRM_Contact_DAO_Relationship',
        'FKApiName' => 'Relationship',
      ),
      'is_current_revision' => array(
        'name' => 'is_current_revision',
        'type' => 16,
        'title' => 'Is this activity a current revision in versioning chain?',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_current_revision',
        'headerPattern' => '/(is.)?(current.)?(revision|version(ing)?)/i',
        'export' => TRUE,
        'default' => '1',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'CheckBox',
        ),
      ),
      'original_id' => array(
        'name' => 'original_id',
        'type' => 1,
        'title' => 'Original Activity ID ',
        'description' => 'Activity ID of the first activity record in versioning chain.',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'FKClassName' => 'CRM_Activity_DAO_Activity',
        'FKApiName' => 'Activity',
      ),
      'weight' => array(
        'name' => 'weight',
        'type' => 1,
        'title' => 'Order',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'size' => 6,
          'maxlength' => 14,
        ),
      ),
      'is_star' => array(
        'name' => 'is_star',
        'type' => 16,
        'title' => 'Is Starred',
        'description' => 'Activity marked as favorite.',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_star',
        'headerPattern' => '/(activity.)?(star|favorite)/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
      ),
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Activity ID',
        'description' => 'Unique  Other Activity ID',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_activity.id',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'uniqueName' => 'activity_id',
        'api.aliases' => array(
          '0' => 'activity_id',
        ),
      ),
      'subject' => array(
        'name' => 'subject',
        'type' => 2,
        'title' => 'Subject',
        'description' => 'The subject/purpose/short description of the activity.',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_activity.subject',
        'headerPattern' => '/(activity.)?subject/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 255,
          'size' => 45,
        ),
        'uniqueName' => 'activity_subject',
      ),
      'duration' => array(
        'name' => 'duration',
        'type' => 1,
        'title' => 'Duration',
        'description' => 'Planned or actual duration of activity expressed in minutes. Conglomerate of former duration_hours and duration_minutes.',
        'import' => TRUE,
        'where' => 'civicrm_activity.duration',
        'headerPattern' => '/(activity.)?duration(s)?$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'size' => 6,
          'maxlength' => 14,
        ),
        'uniqueName' => 'activity_duration',
      ),
      'location' => array(
        'name' => 'location',
        'type' => 2,
        'title' => 'Location',
        'description' => 'Location of the activity (optional, open text).',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_activity.location',
        'headerPattern' => '/(activity.)?location$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 255,
          'size' => 45,
        ),
        'uniqueName' => 'activity_location',
      ),
      'details' => array(
        'name' => 'details',
        'type' => 32,
        'title' => 'Details',
        'description' => 'Details about the activity (agenda, notes, etc).',
        'import' => TRUE,
        'where' => 'civicrm_activity.details',
        'headerPattern' => '/(activity.)?detail(s)?$/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'RichTextEditor',
          'rows' => 2,
          'cols' => 80,
        ),
        'uniqueName' => 'activity_details',
      ),
      'status_id' => array(
        'name' => 'status_id',
        'type' => 1,
        'title' => 'Activity Status',
        'description' => 'ID of the status this activity is currently in. Foreign key to civicrm_option_value.',
        'import' => TRUE,
        'where' => 'civicrm_activity.status_id',
        'headerPattern' => '/(activity.)?status(.label$)?/i',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'activity_status',
          'optionEditPath' => 'civicrm/admin/options/activity_status',
        ),
        'uniqueName' => 'activity_status_id',
        'api.aliases' => array(
          '0' => 'activity_status',
        ),
      ),
      'is_test' => array(
        'name' => 'is_test',
        'type' => 16,
        'title' => 'Test',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_test',
        'headerPattern' => '/(is.)?test(.activity)?/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
        ),
        'uniqueName' => 'activity_is_test',
      ),
      'medium_id' => array(
        'name' => 'medium_id',
        'type' => 1,
        'title' => 'Activity Medium',
        'description' => 'Activity Medium, Implicit FK to civicrm_option_value where option_group = encounter_medium.',
        'default' => 'NULL',
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'encounter_medium',
          'optionEditPath' => 'civicrm/admin/options/encounter_medium',
        ),
        'uniqueName' => 'activity_medium_id',
      ),
      'result' => array(
        'name' => 'result',
        'type' => 2,
        'title' => 'Result',
        'description' => 'Currently being used to store result id for survey activity, FK to option value.',
        'maxlength' => 255,
        'size' => 45,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
          'maxlength' => 255,
          'size' => 45,
        ),
        'uniqueName' => 'activity_result',
      ),
      'is_deleted' => array(
        'name' => 'is_deleted',
        'type' => 16,
        'title' => 'Activity is in the Trash',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_deleted',
        'headerPattern' => '/(activity.)?(trash|deleted)/i',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Text',
        ),
        'uniqueName' => 'activity_is_deleted',
      ),
      'campaign_id' => array(
        'name' => 'campaign_id',
        'type' => 1,
        'title' => 'Campaign',
        'description' => 'The campaign for which this activity has been triggered.',
        'import' => TRUE,
        'where' => 'civicrm_activity.campaign_id',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'FKClassName' => 'CRM_Campaign_DAO_Campaign',
        'html' => array(
          'type' => 'CheckBox',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'table' => 'civicrm_campaign',
          'keyColumn' => 'id',
          'labelColumn' => 'title',
        ),
        'uniqueName' => 'activity_campaign_id',
        'FKApiName' => 'Campaign',
      ),
      'engagement_level' => array(
        'name' => 'engagement_level',
        'type' => 1,
        'title' => 'Engagement Index',
        'description' => 'Assign a specific level of engagement to this activity. Used for tracking constituents in ladder of engagement.',
        'import' => TRUE,
        'where' => 'civicrm_activity.engagement_level',
        'export' => TRUE,
        'table_name' => 'civicrm_activity',
        'entity' => 'Activity',
        'bao' => 'CRM_Activity_BAO_Activity',
        'html' => array(
          'type' => 'Select',
          'size' => 6,
          'maxlength' => 14,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'engagement_index',
          'optionEditPath' => 'civicrm/admin/options/engagement_index',
        ),
        'uniqueName' => 'activity_engagement_level',
      ),
      'source_contact_id' => array(
        'name' => 'source_contact_id',
        'title' => 'Activity Source Contact',
        'description' => 'Person who created this activity. Defaults to current user.',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'api.default' => 'user_contact_id',
        'FKApiName' => 'Contact',
      ),
      'assignee_contact_id' => array(
        'name' => 'assignee_id',
        'title' => 'Activity Assignee',
        'description' => 'Contact(s) assigned to this activity.',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
      'target_contact_id' => array(
        'name' => 'target_id',
        'title' => 'Activity Target',
        'description' => 'Contact(s) participating in this activity.',
        'type' => 1,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
      'case_id' => array(
        'name' => 'case_id',
        'title' => 'Case ID',
        'description' => 'For creating an activity as part of a case.',
        'type' => 1,
        'FKClassName' => 'CRM_Case_DAO_Case',
        'FKApiName' => 'Case',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetFields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
