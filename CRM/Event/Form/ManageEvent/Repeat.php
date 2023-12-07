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
 * Class to manage the "Repeat" functionality for event
 */
class CRM_Event_Form_ManageEvent_Repeat extends CRM_Event_Form_ManageEvent {

  /**
   * Parent Event Start Date.
   * @var string
   */
  protected $_parentEventStartDate = NULL;

  /**
   * Parent Event End Date.
   * @var string
   */
  protected $_parentEventEndDate = NULL;

  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('repeat');
    $this->assign('currentEventId', $this->getEventID());

    $checkParentExistsForThisId = CRM_Core_BAO_RecurringEntity::getParentFor($this->getEventID(), 'civicrm_event');
    //If this ID has parent, send parent id
    if ($checkParentExistsForThisId) {
      /**
       * Get connected event information list
       */
      //Get all connected event ids
      $allEventIdsArray = CRM_Core_BAO_RecurringEntity::getEntitiesForParent($checkParentExistsForThisId, 'civicrm_event');
      $allEventIds = [];
      if (!empty($allEventIdsArray)) {
        foreach ($allEventIdsArray as $key => $val) {
          $allEventIds[] = $val['id'];
        }
        if (!empty($allEventIds)) {
          $params = [];
          $query = "
            SELECT *
            FROM civicrm_event
            WHERE id IN (" . implode(",", $allEventIds) . ")
            ORDER BY start_date asc
             ";

          $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Event_DAO_Event');
          $permissions = CRM_Event_BAO_Event::getAllPermissions();
          while ($dao->fetch()) {
            if (in_array($dao->id, $permissions[CRM_Core_Permission::VIEW])) {
              $manageEvent[$dao->id] = [];
              CRM_Core_DAO::storeValues($dao, $manageEvent[$dao->id]);
            }
          }
        }
        $this->assign('rows', $manageEvent);
      }
    }

    $parentEventParams = ['id' => $this->getEventID()];
    $parentEventValues = [];
    $parentEventReturnProperties = ['start_date', 'end_date'];
    $parentEventAttributes = CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $parentEventParams, $parentEventValues, $parentEventReturnProperties);
    $this->_parentEventStartDate = $parentEventAttributes->start_date;
    $this->_parentEventEndDate = $parentEventAttributes->end_date;
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    //Always pass current event's start date by default
    $defaults['repetition_start_date'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->getEventID(), 'start_date', 'id');
    $recurringEntityDefaults = CRM_Core_Form_RecurringEntity::setDefaultValues();
    return array_merge($defaults, $recurringEntityDefaults);
  }

  public function buildQuickForm() {
    CRM_Core_Form_RecurringEntity::buildQuickForm($this);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->getEventID()) {
      $params = $this->controller->exportValues($this->_name);
      if ($this->_parentEventStartDate && $this->_parentEventEndDate) {
        $interval = CRM_Core_BAO_RecurringEntity::getInterval($this->_parentEventStartDate, $this->_parentEventEndDate);
        $params['intervalDateColumns'] = ['end_date' => $interval];
      }
      $params['dateColumns'] = ['start_date'];
      $params['excludeDateRangeColumns'] = ['start_date', 'end_date'];
      $params['entity_table'] = 'civicrm_event';
      $params['entity_id'] = $this->getEventID();

      // CRM-16568 - check if parent exist for the event.
      $parentId = CRM_Core_BAO_RecurringEntity::getParentFor($this->getEventID(), 'civicrm_event');
      $params['parent_entity_id'] = !empty($parentId) ? $parentId : $params['entity_id'];
      //Unset event id
      unset($params['id']);

      $url = 'civicrm/event/manage/repeat';
      $urlParams = "action=update&reset=1&id={$this->getEventID()}&selectedChild=repeat";

      $linkedEntities = [
        [
          'table' => 'civicrm_price_set_entity',
          'findCriteria' => [
            'entity_id' => $this->getEventID(),
            'entity_table' => 'civicrm_event',
          ],
          'linkedColumns' => ['entity_id'],
          'isRecurringEntityRecord' => FALSE,
        ],
        [
          'table' => 'civicrm_uf_join',
          'findCriteria' => [
            'entity_id' => $this->getEventID(),
            'entity_table' => 'civicrm_event',
          ],
          'linkedColumns' => ['entity_id'],
          'isRecurringEntityRecord' => FALSE,
        ],
        [
          'table' => 'civicrm_tell_friend',
          'findCriteria' => [
            'entity_id' => $this->getEventID(),
            'entity_table' => 'civicrm_event',
          ],
          'linkedColumns' => ['entity_id'],
          'isRecurringEntityRecord' => TRUE,
        ],
        [
          'table' => 'civicrm_pcp_block',
          'findCriteria' => [
            'entity_id' => $this->getEventID(),
            'entity_table' => 'civicrm_event',
          ],
          'linkedColumns' => ['entity_id'],
          'isRecurringEntityRecord' => TRUE,
        ],
      ];
      CRM_Core_Form_RecurringEntity::postProcess($params, 'civicrm_event', $linkedEntities);
      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
    else {
      CRM_Core_Error::statusBounce(ts('Could not find Event ID'));
    }
    parent::endPostProcess();
  }

  /**
   * This function gets the number of participant count for the list of related event ids.
   *
   * @param array $listOfRelatedEntities
   *   List of related event ids .
   *
   *
   * @return array
   */
  public static function getParticipantCountforEvent($listOfRelatedEntities = []) {
    $participantDetails = [];
    if (!empty($listOfRelatedEntities)) {
      $implodeRelatedEntities = implode(',', array_map(function ($entity) {
        return $entity['id'];
      }, $listOfRelatedEntities));
      if ($implodeRelatedEntities) {
        $query = "SELECT p.event_id as event_id,
          concat_ws(' ', e.title, concat_ws(' - ', DATE_FORMAT(e.start_date, '%b %d %Y %h:%i %p'), DATE_FORMAT(e.end_date, '%b %d %Y %h:%i %p'))) as event_data,
          count(p.id) as participant_count
          FROM civicrm_participant p, civicrm_event e
          WHERE p.event_id = e.id AND p.event_id IN ({$implodeRelatedEntities})
          GROUP BY p.event_id";
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $participantDetails['countByID'][$dao->event_id] = $dao->participant_count;
          $participantDetails['countByName'][$dao->event_id][$dao->event_data] = $dao->participant_count;
        }
      }
    }
    return $participantDetails;
  }

  /**
   * This function checks if there was any registration for related event ids,
   * and returns array of ids with no registrations
   *
   * @param mixed $eventID string, int or object
   *
   * @return array
   */
  public static function checkRegistrationForEvents($eventID): array {
    $eventIdsWithNoRegistration = [];
    if ($eventID) {
      $getRelatedEntities = CRM_Core_BAO_RecurringEntity::getEntitiesFor($eventID, 'civicrm_event', TRUE);
      $participantDetails = CRM_Event_Form_ManageEvent_Repeat::getParticipantCountforEvent($getRelatedEntities);
      //Check if participants exists for events
      foreach ($getRelatedEntities as $key => $value) {
        if (empty($participantDetails['countByID'][$value['id']]) && $value['id'] != $eventID) {
          //CRM_Event_BAO_Event::del($value['id']);
          $eventIdsWithNoRegistration[] = $value['id'];
        }
      }
    }
    return $eventIdsWithNoRegistration;
  }

}
