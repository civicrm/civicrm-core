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
namespace Civi\CCase;

/**
 * Class Analyzer
 *
 * @package Civi\CCase
 */
class Analyzer {
  /**
   * @var int
   */
  private $caseId;

  /**
   * The "Case" data, formatted per APIv3.
   *
   * @var array
   */
  private $case;

  /**
   * @var string
   */
  private $caseType;

  /**
   * List of activities, formatted per APIv3.
   *
   * @var array
   */
  private $activities;

  /**
   * @var \SimpleXMLElement
   */
  private $xml;

  /**
   * A list of activity indices, which sort the various activities by some set of keys.
   *
   * Each index is identified by its key-set - e.g. "activity_type_id;source_contact_id" would be a
   * two-dimensional index listing activities by their type ID and their source.
   *
   * @var array
   */
  private $indices;

  /**
   * @param $caseId
   */
  public function __construct($caseId) {
    $this->caseId = $caseId;
    $this->flush();
  }

  /**
   * Determine if case includes an activity of given type/status
   *
   * @param string $type
   *   Eg "Phone Call", "Interview Prospect", "Background Check".
   * @param string $status
   *   Eg "Scheduled", "Completed".
   * @return bool
   */
  public function hasActivity($type, $status = NULL) {
    $idx = $this->getActivityIndex(['activity_type_id', 'status_id']);
    $activityTypeGroup = civicrm_api3('option_group', 'get', ['name' => 'activity_type']);
    $activityType = [
      'name' => $type,
      'option_group_id' => $activityTypeGroup['id'],
    ];
    $activityTypeID = civicrm_api3('option_value', 'get', $activityType);
    $activityTypeID = $activityTypeID['values'][$activityTypeID['id']]['value'];
    if ($status) {
      $activityStatusGroup = civicrm_api3('option_group', 'get', ['name' => 'activity_status']);
      $activityStatus = [
        'name' => $status,
        'option_group_id' => $activityStatusGroup['id'],
      ];
      $activityStatusID = civicrm_api3('option_value', 'get', $activityStatus);
      $activityStatusID = $activityStatusID['values'][$activityStatusID['id']]['value'];
    }
    if ($status === NULL) {
      return !empty($idx[$activityTypeID]);
    }
    else {
      return !empty($idx[$activityTypeID][$activityStatusID]);
    }
  }

  /**
   * Get a list of all activities in the case.
   *
   * @return array
   *   list of activity records (api/v3 format)
   */
  public function getActivities() {
    if ($this->activities === NULL) {
      // TODO find batch-oriented API for getting all activities in a case
      $case = $this->getCase();
      $activities = [];
      if (isset($case['activities'])) {
        foreach ($case['activities'] as $actId) {
          $result = civicrm_api3('Activity', 'get', [
            'id' => $actId,
            'is_current_revision' => 1,
          ]);
          $activities = array_merge($activities, $result['values']);
        }
      }
      $this->activities = $activities;
    }
    return $this->activities;
  }

  /**
   * Get a single activity record by type.
   * This function is only used by SequenceListenerTest
   *
   * @param string $type
   * @throws \Civi\CCase\Exception\MultipleActivityException
   * @return array|NULL, activity record (api/v3)
   */
  public function getSingleActivity($type) {
    $idx = $this->getActivityIndex(['activity_type_id', 'id']);
    $actTypes = array_flip(\CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate'));
    $typeId = $actTypes[$type];
    $count = isset($idx[$typeId]) ? count($idx[$typeId]) : 0;

    if ($count === 0) {
      return NULL;
    }
    elseif ($count === 1) {
      foreach ($idx[$typeId] as $item) {
        return $item;
      }
    }
    else {
      throw new \Civi\CCase\Exception\MultipleActivityException("Wrong quantity of [$type] records. Expected 1 but found " . $count);
    }
  }

  /**
   * @return int
   */
  public function getCaseId() {
    return $this->caseId;
  }

  /**
   * @return array, Case record (api/v3 format)
   */
  public function getCase() {
    if ($this->case === NULL) {
      $this->case = civicrm_api3('case', 'getsingle', ['id' => $this->caseId]);
    }
    return $this->case;
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getCaseType() {
    if ($this->caseType === NULL) {
      $case = $this->getCase();
      $caseTypes = \CRM_Case_XMLRepository::singleton()->getAllCaseTypes();
      if (!isset($caseTypes[$case['case_type_id']])) {
        throw new \CRM_Core_Exception("Case does not have a recognized case-type!");
      }
      $this->caseType = $caseTypes[$case['case_type_id']];
    }
    return $this->caseType;
  }

  /**
   * Get a list of all activities in the case (indexed by some property/properties)
   *
   * @param array $keys
   *   List of properties by which to index activities.
   * @return array
   *   list of activity records (api/v3 format), indexed by $keys
   */
  public function getActivityIndex($keys) {
    $key = implode(";", $keys);
    if (!isset($this->indices[$key])) {
      $this->indices[$key] = \CRM_Utils_Array::index($keys, $this->getActivities());
    }
    return $this->indices[$key];
  }

  /**
   * @return \SimpleXMLElement|NULL
   */
  public function getXml() {
    if ($this->xml === NULL) {
      $this->xml = \CRM_Case_XMLRepository::singleton()->retrieve($this->getCaseType());
    }
    return $this->xml;
  }

  /**
   * Flush any cached information.
   */
  public function flush() {
    $this->case = NULL;
    $this->caseType = NULL;
    $this->activities = NULL;
    $this->indices = [];
  }

}
