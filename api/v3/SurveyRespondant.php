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
 * This api exposes CiviCRM Survey Respondant.
 *
 * @deprecated - api currently not supported
 *
 * @package CiviCRM_APIv3
 */

/**
 * Notify caller of deprecated function.
 *
 * @deprecated api notice
 * @return string
 *   String output indicates this entire api entity as deprecated
 */
function _civicrm_api3_survey_respondant_deprecation() {
  return 'The SurveyRespondant api is not currently supported.';
}

/**
 * Get the list of signatories.
 *
 * @deprecated - api currently not supported
 *
 * @param array $params
 *   input parameters.
 *
 * @return array
 */
function civicrm_api3_survey_respondant_get($params) {

  civicrm_api3_verify_one_mandatory($params, NULL, ['survey_id', 'id']);

  if (array_key_exists('survey_id', $params)) {
    $surveyID = $params['survey_id'];
  }
  else {
    $surveyID = $params['id'];
  }

  $interviewerID = NULL;
  if (array_key_exists('interviewer_id', $params)) {
    $interviewerID = $params['interviewer_id'];
  }

  $statusIds = [];
  if (array_key_exists('status_id', $params)) {
    $statusIds = explode(',', $params['status_id']);
  }

  $respondants = CRM_Campaign_BAO_Survey::getSurveyActivities($surveyID, $interviewerID, $statusIds);

  return (civicrm_api3_create_success($respondants, $params, 'SurveyRespondant', 'get'));
}

/**
 * Count survey respondents.
 *
 * @deprecated - api currently not supported
 *
 * @param array $params
 * @return array
 */
function &civicrm_api3_survey_respondant_count($params) {

  $petition = new CRM_Campaign_BAO_Petition();
  if (array_key_exists('groupby', $params) &&
    $params['groupby'] == 'country'
  ) {
    $signaturesCount = $petition->getPetitionSignatureTotalbyCountry($params['survey_id']);
  }
  else {
    $signaturesCount = $petition->getPetitionSignatureTotal($params['survey_id']);
  }
  return ($signaturesCount);
}
