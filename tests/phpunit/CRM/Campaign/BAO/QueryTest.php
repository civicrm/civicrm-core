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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Campaign_BAO_QueryTest extends CiviUnitTestCase {

  public function testCampaignVoterClause() {
    $loggedInContact = $this->createLoggedInUser();
    $contact = $this->individualCreate();
    $activityType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'survey');
    $surveyParams = [
      'title' => 'Test Survey',
      'activity_type_id' => $activityType,
      'created_id' => $loggedInContact,
    ];
    $survery = CRM_Campaign_BAO_Survey::create($surveyParams);
    $voterClauseParams = [
      'campaign_search_voter_for' => 'reserve',
      'campaign_survey_id' => $survery->id,
      'survey_interviewer_id' => $loggedInContact,
    ];
    CRM_Campaign_BAO_Query::voterClause($voterClauseParams);
  }

}
