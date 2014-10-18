<?php
/**
 * Test Generated example of using activity getfields API
 * *
 */
function activity_getfields_example(){
$params = array(
  'action' => 'create',
);

try{
  $result = civicrm_api3('activity', 'getfields', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function activity_getfields_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 27,
  'values' => array(
      'source_record_id' => array(
          'name' => 'source_record_id',
          'type' => 1,
          'title' => 'Source Record',
        ),
      'activity_type_id' => array(
          'name' => 'activity_type_id',
          'type' => 1,
          'title' => 'Activity Type',
          'required' => true,
          'import' => true,
          'where' => 'civicrm_activity.activity_type_id',
          'headerPattern' => '/(activity.)?type(.id$)/i',
          'default' => '1',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'activity_type',
            ),
        ),
      'activity_date_time' => array(
          'name' => 'activity_date_time',
          'type' => 12,
          'title' => 'Activity Date',
          'import' => true,
          'where' => 'civicrm_activity.activity_date_time',
          'headerPattern' => '/(activity.)?date(.time$)?/i',
          'export' => true,
          'html' => array(
              'type' => 'Select Date',
            ),
        ),
      'phone_id' => array(
          'name' => 'phone_id',
          'type' => 1,
          'title' => 'Phone (called) ID',
          'FKClassName' => 'CRM_Core_DAO_Phone',
          'html' => array(
              'type' => 'Autocomplete-Select',
            ),
        ),
      'phone_number' => array(
          'name' => 'phone_number',
          'type' => 2,
          'title' => 'Phone (called) Number',
          'maxlength' => 64,
          'size' => 30,
          'html' => array(
              'type' => 'Text',
            ),
        ),
      'priority_id' => array(
          'name' => 'priority_id',
          'type' => 1,
          'title' => 'Priority',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'priority',
            ),
        ),
      'parent_id' => array(
          'name' => 'parent_id',
          'type' => 1,
          'title' => 'Parent Activity Id',
          'FKClassName' => 'CRM_Activity_DAO_Activity',
        ),
      'is_auto' => array(
          'name' => 'is_auto',
          'type' => 16,
          'title' => 'Auto',
        ),
      'relationship_id' => array(
          'name' => 'relationship_id',
          'type' => 1,
          'title' => 'Relationship Id',
          'default' => 'NULL',
          'FKClassName' => 'CRM_Contact_DAO_Relationship',
        ),
      'is_current_revision' => array(
          'name' => 'is_current_revision',
          'type' => 16,
          'title' => 'Is this activity a current revision in versioning chain?',
          'import' => true,
          'where' => 'civicrm_activity.is_current_revision',
          'headerPattern' => '/(is.)?(current.)?(revision|version(ing)?)/i',
          'export' => true,
          'default' => '1',
          'html' => array(
              'type' => 'CheckBox',
            ),
        ),
      'original_id' => array(
          'name' => 'original_id',
          'type' => 1,
          'title' => 'Original Activity ID ',
          'FKClassName' => 'CRM_Activity_DAO_Activity',
        ),
      'weight' => array(
          'name' => 'weight',
          'type' => 1,
          'title' => 'Weight',
          'html' => array(
              'type' => 'Text',
            ),
        ),
      'id' => array(
          'name' => 'id',
          'type' => 1,
          'title' => 'Activity ID',
          'required' => true,
          'import' => true,
          'where' => 'civicrm_activity.id',
          'export' => true,
          'uniqueName' => 'activity_id',
          'api.aliases' => array(
              '0' => 'activity_id',
            ),
        ),
      'subject' => array(
          'name' => 'subject',
          'type' => 2,
          'title' => 'Subject',
          'maxlength' => 255,
          'size' => 45,
          'import' => true,
          'where' => 'civicrm_activity.subject',
          'headerPattern' => '/(activity.)?subject/i',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'uniqueName' => 'activity_subject',
        ),
      'duration' => array(
          'name' => 'duration',
          'type' => 1,
          'title' => 'Duration',
          'import' => true,
          'where' => 'civicrm_activity.duration',
          'headerPattern' => '/(activity.)?duration(s)?$/i',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'uniqueName' => 'activity_duration',
        ),
      'location' => array(
          'name' => 'location',
          'type' => 2,
          'title' => 'Location',
          'maxlength' => 255,
          'size' => 45,
          'import' => true,
          'where' => 'civicrm_activity.location',
          'headerPattern' => '/(activity.)?location$/i',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'uniqueName' => 'activity_location',
        ),
      'details' => array(
          'name' => 'details',
          'type' => 32,
          'title' => 'Details',
          'rows' => 8,
          'cols' => 60,
          'import' => true,
          'where' => 'civicrm_activity.details',
          'headerPattern' => '/(activity.)?detail(s)?$/i',
          'export' => true,
          'html' => array(
              'type' => 'RichTextEditor',
            ),
          'uniqueName' => 'activity_details',
        ),
      'status_id' => array(
          'name' => 'status_id',
          'type' => 1,
          'title' => 'Activity Status',
          'import' => true,
          'where' => 'civicrm_activity.status_id',
          'headerPattern' => '/(activity.)?status(.label$)?/i',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'activity_status',
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
          'import' => true,
          'where' => 'civicrm_activity.is_test',
          'headerPattern' => '/(is.)?test(.activity)?/i',
          'export' => true,
          'html' => array(
              'type' => 'Select',
            ),
          'uniqueName' => 'activity_is_test',
        ),
      'medium_id' => array(
          'name' => 'medium_id',
          'type' => 1,
          'title' => 'Activity Medium',
          'default' => 'NULL',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'encounter_medium',
            ),
          'uniqueName' => 'activity_medium_id',
        ),
      'result' => array(
          'name' => 'result',
          'type' => 2,
          'title' => 'Result',
          'maxlength' => 255,
          'size' => 45,
          'html' => array(
              'type' => 'Text',
            ),
          'uniqueName' => 'activity_result',
        ),
      'is_deleted' => array(
          'name' => 'is_deleted',
          'type' => 16,
          'title' => 'Activity is in the Trash',
          'import' => true,
          'where' => 'civicrm_activity.is_deleted',
          'headerPattern' => '/(activity.)?(trash|deleted)/i',
          'export' => true,
          'html' => array(
              'type' => 'Text',
            ),
          'uniqueName' => 'activity_is_deleted',
        ),
      'campaign_id' => array(
          'name' => 'campaign_id',
          'type' => 1,
          'title' => 'Campaign',
          'import' => true,
          'where' => 'civicrm_activity.campaign_id',
          'export' => true,
          'FKClassName' => 'CRM_Campaign_DAO_Campaign',
          'html' => array(
              'type' => 'CheckBox',
            ),
          'pseudoconstant' => array(
              'table' => 'civicrm_campaign',
              'keyColumn' => 'id',
              'labelColumn' => 'title',
            ),
          'uniqueName' => 'activity_campaign_id',
        ),
      'engagement_level' => array(
          'name' => 'engagement_level',
          'type' => 1,
          'title' => 'Engagement Index',
          'import' => true,
          'where' => 'civicrm_activity.engagement_level',
          'export' => true,
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'engagement_index',
            ),
          'uniqueName' => 'activity_engagement_level',
        ),
      'source_contact_id' => array(
          'name' => 'source_contact_id',
          'title' => 'Activity Source Contact',
          'type' => 1,
          'FKClassName' => 'CRM_Activity_DAO_ActivityContact',
          'api.default' => 'user_contact_id',
        ),
      'assignee_contact_id' => array(
          'name' => 'assignee_id',
          'title' => 'assigned to',
          'type' => 1,
          'FKClassName' => 'CRM_Activity_DAO_ActivityContact',
        ),
      'target_contact_id' => array(
          'name' => 'target_id',
          'title' => 'Activity Target',
          'type' => 1,
          'FKClassName' => 'CRM_Activity_DAO_ActivityContact',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetFields and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
