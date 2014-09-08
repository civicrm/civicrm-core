<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
namespace Civi\CCase;

class Analyzer {
  /**
   * @var int
   */
  private $caseId;

  /**
   * @var array per APIv3
   */
  private $case;

  /**
   * @var string
   */
  private $caseType;

  /**
   * @var array per APIv3
   */
  private $activities;

  /**
   * @var \SimpleXMLElement
   */
  private $xml;

  /**
   * @var array<string,array>
   */
  private $indices;

  public function __construct($caseId) {
    $this->caseId = $caseId;
    $this->flush();
  }

  /**
   * Determine if case includes an activity of given type/status
   *
   * @param string $type eg "Phone Call", "Interview Prospect", "Background Check"
   * @param string $status eg "Scheduled", "Completed"
   * @return bool
   */
  public function hasActivity($type, $status = NULL) {
    $idx = $this->getActivityIndex(array('activity_type_id', 'status_id'));
    $activityTypeGroup = civicrm_api3('option_group', 'get', array('name' => 'activity_type'));
    $activityType = array(
      'name' => $type,
      'option_group_id' => $activityTypeGroup['id'],
    );
    $activityTypeID = civicrm_api3('option_value', 'get', $activityType);
    $activityTypeID = $activityTypeID['values'][$activityTypeID['id']]['value'];
    if ($status) {
      $activityStatusGroup = civicrm_api3('option_group', 'get', array('name' => 'activity_status'));
      $activityStatus = array(
        'name' => $status,
        'option_group_id' => $activityStatusGroup['id']
      );
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
   * Get a list of all activities in the case
   *
   * @return array list of activity records (api/v3 format)
   */
  public function getActivities() {
    if ($this->activities === NULL) {
      // TODO find batch-oriented API for getting all activities in a case
      $case = $this->getCase();
      $activities = array();
      if (isset($case['activities'])) {
        foreach ($case['activities'] as $actId) {
          $result = civicrm_api3('Activity', 'get', array(
            'id' => $actId,
            'is_current_revision' => 1,
          ));
          $activities = array_merge($activities, $result['values']);
        }
      }
      $this->activities = $activities;
    }
    return $this->activities;
  }

  /**
   * Get a single activity record by type
   *
   * @param string $type
   * @throws \Civi\CCase\Exception\MultipleActivityException
   * @return array|NULL, activity record (api/v3)
   */
  public function getSingleActivity($type) {
    $idx = $this->getActivityIndex(array('activity_type_id', 'id'));
    $actTypes = array_flip(\CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name'));
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
      $this->case = civicrm_api3('case', 'getsingle', array('id' => $this->caseId));
    }
    return $this->case;
  }

  /**
   * @return string
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
   * @param array $keys list of properties by which to index activities
   * @return array list of activity records (api/v3 format), indexed by $keys
   */
  public function getActivityIndex($keys) {
    $key = implode(";", $keys);
    if (!isset($this->indices[$key])) {
      $this->indices[$key] = \CRM_Utils_Array::index($keys, $this->getActivities());
    }
    return $this->indices[$key];
  }

  /**
   * @return SimpleXMLElement|NULL
   */
  public function getXml() {
    if ($this->xml === NULL) {
      $this->xml = \CRM_Case_XMLRepository::singleton()->retrieve($this->getCaseType());
    }
    return $this->xml;
  }

  /**
   * Flush any cached information
   *
   * @return void
   */
  public function flush() {
    $this->case = NULL;
    $this->caseType = NULL;
    $this->activities = NULL;
    $this->indices = array();
  }
}
