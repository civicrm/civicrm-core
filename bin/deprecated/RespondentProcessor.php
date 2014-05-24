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


/*
 * This file check and update the survey respondents.
 *
 */

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

/**
 * Class CRM_RespondentProcessor
 */
class CRM_RespondentProcessor {
  /**
   *
   */
  function __construct() {
    $config = CRM_Core_Config::singleton();

    //this does not return on failure
    require_once 'CRM/Utils/System.php';
    require_once 'CRM/Utils/Hook.php';

    CRM_Utils_System::authenticateScript(TRUE);

    //log the execution time of script
    CRM_Core_Error::debug_log_message('RespondentProcessor.php');
  }

  public function releaseRespondent() {
    require_once 'CRM/Core/PseudoConstant.php';
    require_once 'CRM/Campaign/BAO/Survey.php';
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $reserveStatusId = array_search('Scheduled', $activityStatus);
    $surveyActivityTypes = CRM_Campaign_BAO_Survey::getSurveyActivityType();
    $surveyActivityTypesIds = array_keys($surveyActivityTypes);

    //retrieve all survey activities related to reserve action.
    $releasedCount = 0;
    if ($reserveStatusId && !empty($surveyActivityTypesIds)) {
      $query = '
    SELECT  activity.id as id,
            activity.activity_date_time as activity_date_time,
            survey.id as surveyId,
            survey.release_frequency as release_frequency
      FROM  civicrm_activity activity
INNER JOIN  civicrm_survey survey ON ( survey.id = activity.source_record_id )
     WHERE  activity.is_deleted = 0
       AND  activity.status_id = %1
       AND  activity.activity_type_id IN ( ' . implode(', ', $surveyActivityTypesIds) . ' )';
      $activity = CRM_Core_DAO::executeQuery($query, array(1 => array($reserveStatusId, 'Positive')));
      $releasedIds = array();
      while ($activity->fetch()) {
        if (!$activity->release_frequency) {
          continue;
        }
        $reservedSeconds = CRM_Utils_Date::unixTime($activity->activity_date_time);
        $releasedSeconds = $activity->release_frequency * 24 * 3600;
        $totalReservedSeconds = $reservedSeconds + $releasedSeconds;
        if ($totalReservedSeconds < time()) {
          $releasedIds[$activity->id] = $activity->id;
        }
      }

      //released respondent.
      if (!empty($releasedIds)) {
        $query = '
UPDATE  civicrm_activity
   SET  is_deleted = 1
 WHERE  id IN ( ' . implode(', ', $releasedIds) . ' )';
        CRM_Core_DAO::executeQuery($query);
        $releasedCount = count($releasedIds);
      }
    }

    echo "<br /><br />Number of respondents released = {$releasedCount}";
  }
}

$obj = new CRM_RespondentProcessor();
echo "Releasing..";
$obj->releaseRespondent();
echo "<br /><br />Respondent Release Done";


