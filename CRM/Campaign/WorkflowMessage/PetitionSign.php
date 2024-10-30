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

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Invoice generated when invoicing is enabled.
 *
 * @method $this setSurveyID(int $surveyID)
 * @method int getSurveyID()
 * @method $this setSurvey(array $survey)
 * @method array getSurvey()
 *
 * @support template-only
 *
 * @see CRM_Campaign_BAO_Petition::sendEmail
 */
class CRM_Campaign_WorkflowMessage_PetitionSign extends GenericWorkflowMessage {

  public const WORKFLOW = 'petition_sign';

  /**
   * Survey ID.
   *
   * @var int
   *
   * @scope tokenContext as surveyId
   */
  public $surveyID;

  /**
   * Survey.
   *
   * @var array
   *
   * @scope tokenContext as survey
   */
  public $survey;

}
