<?php


/*
 
 */
function activity_getfields_example() {
  $params = array(
    'version' => 3,
    'action' => 'create',
  );

  require_once 'api/api.php';
  $result = civicrm_api('activity', 'getfields', $params);

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 28,
    'values' => array(
      'source_contact_id' => array(
        'name' => 'source_contact_id',
        'type' => 1,
        'title' => 'Source Contact',
        'import' => TRUE,
        'where' => 'civicrm_activity.source_contact_id',
        'headerPattern' => '/(activity.)?source(.contact(.id)?)?/i',
        'export' => TRUE,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'api.default' => 'user_contact_id',
      ),
      'source_record_id' => array(
        'name' => 'source_record_id',
        'type' => 1,
      ),
      'activity_type_id' => array(
        'name' => 'activity_type_id',
        'type' => 1,
        'title' => 'Activity Type ID',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_activity.activity_type_id',
        'headerPattern' => '/(activity.)?type(.id$)/i',
      ),
      'activity_date_time' => array(
        'name' => 'activity_date_time',
        'type' => 12,
        'title' => 'Activity Date',
        'import' => TRUE,
        'where' => 'civicrm_activity.activity_date_time',
        'headerPattern' => '/(activity.)?date(.time$)?/i',
        'export' => TRUE,
      ),
      'phone_id' => array(
        'name' => 'phone_id',
        'type' => 1,
        'FKClassName' => 'CRM_Core_DAO_Phone',
      ),
      'phone_number' => array(
        'name' => 'phone_number',
        'type' => 2,
        'title' => 'Phone Number',
        'maxlength' => 64,
        'size' => 30,
      ),
      'priority_id' => array(
        'name' => 'priority_id',
        'type' => 1,
        'pseudoconstant' => 'priority',
        'options' => array(
          '1' => 'Urgent',
          '2' => 'Normal',
          '3' => 'Low',
        ),
      ),
      'parent_id' => array(
        'name' => 'parent_id',
        'type' => 1,
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
        'default' => 'UL',
        'FKClassName' => 'CRM_Contact_DAO_Relationship',
      ),
      'is_current_revision' => array(
        'name' => 'is_current_revision',
        'type' => 16,
        'title' => 'Is this activity a current revision in versioning chain?',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_current_revision',
        'headerPattern' => '/(is.)?(current.)?(revision|version(ing)?)/i',
        'export' => TRUE,
      ),
      'original_id' => array(
        'name' => 'original_id',
        'type' => 1,
        'FKClassName' => 'CRM_Activity_DAO_Activity',
      ),
      'weight' => array(
        'name' => 'weight',
        'type' => 1,
        'title' => 'Weight',
      ),
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Activity ID',
        'required' => TRUE,
        'import' => TRUE,
        'where' => 'civicrm_activity.id',
        'export' => TRUE,
        'uniqueName' => 'activity_id',
      ),
      'subject' => array(
        'name' => 'subject',
        'type' => 2,
        'title' => 'Subject',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_activity.subject',
        'headerPattern' => '/(activity.)?subject/i',
        'export' => TRUE,
        'uniqueName' => 'activity_subject',
        'api.required' => 1,
      ),
      'duration' => array(
        'name' => 'duration',
        'type' => 1,
        'title' => 'Duration',
        'import' => TRUE,
        'where' => 'civicrm_activity.duration',
        'headerPattern' => '/(activity.)?duration(s)?$/i',
        'export' => TRUE,
        'uniqueName' => 'activity_duration',
      ),
      'location' => array(
        'name' => 'location',
        'type' => 2,
        'title' => 'Location',
        'maxlength' => 255,
        'size' => 45,
        'import' => TRUE,
        'where' => 'civicrm_activity.location',
        'headerPattern' => '/(activity.)?location$/i',
        'export' => TRUE,
        'uniqueName' => 'activity_location',
      ),
      'details' => array(
        'name' => 'details',
        'type' => 32,
        'title' => 'Details',
        'rows' => 8,
        'cols' => 60,
        'import' => TRUE,
        'where' => 'civicrm_activity.details',
        'headerPattern' => '/(activity.)?detail(s)?$/i',
        'export' => TRUE,
        'uniqueName' => 'activity_details',
      ),
      'status_id' => array(
        'name' => 'status_id',
        'type' => 1,
        'title' => 'Activity Status Id',
        'import' => TRUE,
        'where' => 'civicrm_activity.status_id',
        'headerPattern' => '/(activity.)?status(.label$)?/i',
        'uniqueName' => 'activity_status_id',
      ),
      'is_test' => array(
        'name' => 'is_test',
        'type' => 16,
        'title' => 'Test',
        'import' => TRUE,
        'where' => 'civicrm_activity.is_test',
        'headerPattern' => '/(is.)?test(.activity)?/i',
        'export' => TRUE,
        'uniqueName' => 'activity_is_test',
      ),
      'medium_id' => array(
        'name' => 'medium_id',
        'type' => 1,
        'title' => 'Activity Medium',
        'default' => 'UL',
        'uniqueName' => 'activity_medium_id',
      ),
      'result' => array(
        'name' => 'result',
        'type' => 2,
        'title' => 'Result',
        'maxlength' => 255,
        'size' => 45,
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
        'uniqueName' => 'activity_is_deleted',
      ),
      'campaign_id' => array(
        'name' => 'campaign_id',
        'type' => 1,
        'title' => 'Campaign ID',
        'import' => TRUE,
        'where' => 'civicrm_activity.campaign_id',
        'export' => TRUE,
        'FKClassName' => 'CRM_Campaign_DAO_Campaign',
        'uniqueName' => 'activity_campaign_id',
      ),
      'engagement_level' => array(
        'name' => 'engagement_level',
        'type' => 1,
        'title' => 'Engagement Index',
        'import' => TRUE,
        'where' => 'civicrm_activity.engagement_level',
        'export' => TRUE,
        'uniqueName' => 'activity_engagement_level',
      ),
      'assignee_contact_id' => array(
        'name' => 'assignee_id',
        'title' => 'assigned to',
        'type' => 1,
        'FKClassName' => 'CRM_Activity_DAO_ActivityAssignment',
      ),
      'target_contact_id' => array(
        'name' => 'target_id',
        'title' => 'Activity Target',
        'type' => 1,
        'FKClassName' => 'CRM_Activity_DAO_ActivityTarget',
      ),
      'activity_status_id' => array(
        'name' => 'status_id',
        'title' => 'Status Id',
        'type' => 1,
      ),
    ),
  );

  return $expectedResult;
}




/*
* This example has been generated from the API test suite. The test that created it is called
* 
* testGetFields and can be found in 
* http://svn.civicrm.org/civicrm/branches/v3.4/tests/phpunit/CiviTest/api/v3/ActivityTest.php
* 
* You can see the outcome of the API tests at 
* http://tests.dev.civicrm.org/trunk/results-api_v3
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*/

