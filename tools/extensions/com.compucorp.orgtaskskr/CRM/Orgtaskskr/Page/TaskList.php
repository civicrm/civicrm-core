<?php

require_once 'CRM/Core/Page.php';

class CRM_Orgtaskskr_Page_TaskList extends CRM_Core_Page {
  
  /**
   * Holds cache of contacts per activity. 
   * @var array
   */
  private $activityContacts;
  
  public function __construct($title = NULL, $mode = NULL) {
    $this->activityContacts = array();
    
    parent::__construct($title, $mode);
  }
  
  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Organisation Activities'));
    
    $contactid = CRM_Utils_Request::retrieve('contact', 'Positive');
    
    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('activities', $this->getTasks($contactid));
    
    parent::run();
  }
  
  /**
   * Method that retrieves values for given option group.
   * 
   * @param string $optgroup_name optgroup name
   * @return array values belonging to given optgroup.
   */
  private function getParameterValues($optgroup_name) {
    $values = array();
    
    $result = civicrm_api3('OptionGroup', 'get', array(
      'sequential' => 1,
      'return' => array("name"),
      'name' => $optgroup_name,
      'api.OptionValue.get' => array('option_group_id' => "\$value.id"),
    ));
    
    foreach ($result['values'] as $currGroup) {
      foreach ($currGroup['api.OptionValue.get']['values'] as $currOptValue) {
        $values[$currOptValue['value']] = $currOptValue;
      }
    }
    
    return $values;
  }
  
  /**
   * Returns array of activity statuses configured in DB.
   * 
   * @return array of possible statuses for an activity.
   */
  private function getActivityStatus() {
    return $this->getParameterValues('activity_status');
  }
  
  /**
   * Returns array of activity types.
   * 
   * @return array list of activity types
   */
  private function getActivityTypes() {
    return $this->getParameterValues('activity_type');
  }
  
  /**
   * Method the searches for given organistion contact's activities.
   * 
   * @param int $contactid ID of organisation.
   * 
   * @return array List of activities belonging to contacts of organisation
   */
  private function getTasks($contactid) {
    $activitytypes = $this->getActivityTypes();
    $activityStatus = $this->getActivityStatus();
    $allowed_activities = Civi::settings()->get('orgtasks_included_activities');
    
    $params = $this->buildRelationshipParamsArray($contactid);
    
    // Get list of contacts associated to organisation + activity list per contact
    $activitiesPerContact = civicrm_api3('Relationship', 'get', $params);
    
    // Go through contacts, building task list
    $activites = array();
    foreach ($activitiesPerContact['values'] as $currentContact) {
      if (count($currentContact['api.ActivityContact.get']['values']) > 0) {
        foreach ($currentContact['api.ActivityContact.get']['values'] as $currentActivity) {
          if (in_array($currentActivity['activity_id.activity_type_id'], $allowed_activities)) {
            $currentActivity['activity_type'] = $activitytypes[$currentActivity['activity_id.activity_type_id']];
            $currentActivity['subject'] = $currentActivity['activity_id.subject'];
            $currentActivity['date'] = $currentActivity['activity_id.activity_date_time'];
            $currentActivity['creator'] = $this->getActivityCreator($currentActivity['activity_id']);
            $currentActivity['targets'] = $this->getActivityTargets($currentActivity['activity_id']);
            $currentActivity['assigned'] = $this->getActivityAssigned($currentActivity['activity_id']);
            $currentActivity['status'] = $activityStatus[$currentActivity['activity_id.status_id']];
            $activities[$currentActivity['activity_id']] = $currentActivity;
          }
        }
      }
    }
    
    //var_dump('<pre>', $activities);
    return $activities;
  }
  
  /**
   * Method builds params array for API call to get contacts and chained activities.
   * 
   * @param int $contactid ID of organiation
   * @return array parameters array to make call to API
   */
  private function buildRelationshipParamsArray($contactid) {
    $params = array(
      'sequential' => 1,
      'return' => array(
          "contact_id_a.display_name", 
          "contact_id_b.display_name", 
          "relationship_type_id", 
          "contact_id_a.id", 
          "contact_id_b.id"
       ),
      'contact_id_b' => $contactid,
      'contact_id_a.contact_type' => "Individual",
      'api.ActivityContact.get' => array(
        'sequential' => 1, 
        'contact_id' => "\$value.contact_id_a.id", 
        'return' => "activity_id, record_type_id, contact_id, activity_id.subject, activity_id.status_id, activity_id.activity_date_time, activity_id.activity_type_id, activity_id.activity_type, status_id",
      ),
    );
    
    $allowed_relationships = Civi::settings()->get('orgtasks_included_relationships');
    if (count($allowed_relationships) > 0) {
      $params['relationship_type_id'] = $allowed_relationships;
    }
    
    $allowed_activities = Civi::settings()->get('orgtasks_included_activities');
    if (count($allowed_activities) > 0) {
      $params['api.ActivityContact.get']['activity_id.activity_type_id'] = $allowed_activities;
    }
    
    return $params;
  }
  
  /**
   * Method retuns contacts to which the activity is assigned.
   * 
   * @param int $activityid ID of activity.
   * @return array list of contacts to which the activity was assigned.
   */
  private function getActivityAssigned($activityid) {
    return $this->getActivityContactsByType($activityid, 1);
  }
  
  /**
   * Returns target contacts of activity.
   * 
   * @param int $activityid ID of activity.
   * @return array list of target contacts.
   */
  private function getActivityTargets($activityid) {
    return $this->getActivityContactsByType($activityid, 3);
  }
  
  /**
   * Gets activity creator contact for given activity id,
   * 
   * @param int $activityid ID of activity
   * @return array holding values of contact
   */
  private function getActivityCreator($activityid) {
    $creator = $this->getActivityContactsByType($activityid, 2);
    return $creator[0];
  }
  
  /**
   * Returns contacts associated to activity, filtering by contact type.
   * 
   * @param int $activityid ID of activity
   * @param int $contacttype ID of contact type (1: Assigned, 2: Creator, 3: Target.
   * @return array of contacts of given type associated to activity identified by given id.
   */
  private function getActivityContactsByType($activityid, $contacttype) {
    if (!isset($this->activityContacts[$activityid])) {
      $this->loadActivityContacts($activityid);
    }
    
    return $this->activityContacts[$activityid][$contacttype];
  }
  
  /**
   * Loads all contacts of activity to a class attribute to hold as caché.
   * 
   * @param type $activityid
   */
  private function loadActivityContacts($activityid) {
    $contacts = civicrm_api3('ActivityContact', 'get', array(
      'sequential' => 1,
      'return' => array(
        'contact_id', 
        'contact_id.display_name', 
        'contact_id.phone', 
        'record_type_id'
      ),
      'activity_id' => $activityid,
      'api.Contact.get' => array(
        'contact_id' => "\$value.contact_id",
        'return' => 'contact_type, phone, street_address, city, state_province, country, postal_code, email, gender_id, birth_date'
      )
    ));
    //var_dump('<pre>', $contacts, '</pre><br><br>');
    foreach ($contacts['values'] as $currContact) {
      foreach ($currContact as $field => $value) {
        $currContact[strtr($field, array('contact_id.' => ''))] = $value;
      }
      
      foreach ($currContact['api.Contact.get']['values'] as $innerData) {
        foreach ($innerData as $field => $value) {
          $currContact['inner_' . $field] = $value;
        }
      }
      
      $this->activityContacts[$activityid][$currContact['record_type_id']][] = $currContact;
    }
    
    //var_dump('<pre>', $this->activityContacts[$activityid]);
  }
}
