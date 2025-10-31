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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to record voter's interview.
 */
class CRM_Campaign_Form_Task_Interview extends CRM_Campaign_Form_Task {

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Variable to store redirect path
   * @var string
   */
  private $_userContext;

  private $_groupTree;

  private $_surveyFields;

  private $_surveyTypeId;

  private $_interviewerId;

  private $_surveyActivityIds;

  private $_votingTab = FALSE;

  private $_surveyValues;

  private $_resultOptions;

  private $_allowAjaxReleaseButton;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_votingTab = $this->get('votingTab');
    $this->_reserveToInterview = $this->get('reserveToInterview');
    if ($this->_reserveToInterview || $this->_votingTab) {
      //user came from voting tab / reserve form.
      foreach (['surveyId', 'contactIds', 'interviewerId'] as $fld) {
        $this->{"_$fld"} = $this->get($fld);
      }
      //get the target voter ids.
      if ($this->_votingTab) {
        $this->getVoterIds();
      }
    }
    else {
      parent::preProcess();
      //get the survey id from user submitted values.
      $this->_surveyId = $this->get('formValues')['campaign_survey_id'] ?? NULL;
      $this->_interviewerId = $this->get('formValues')['survey_interviewer_id'] ?? NULL;
    }

    if ($this->_surveyId) {
      $params = ['id' => $this->_surveyId];
      CRM_Campaign_BAO_Survey::retrieve($params, $this->_surveyDetails);
    }

    $orderClause = FALSE;
    $buttonName = $this->controller->getButtonName();
    $walkListActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'WalkList');
    if ($buttonName == '_qf_Interview_submit_orderBy' && !empty($_POST['order_bys'])) {
      $orderByParams = $_POST['order_bys'] ?? NULL;
    }
    elseif ($walkListActivityId == $this->_surveyDetails['activity_type_id']) {
      $orderByParams
        = [
          1 => [
            'column' => 'civicrm_address.street_name',
            'order' => 'ASC',
          ],
          2 => [
            'column' => 'civicrm_address.street_number%2',
            'order' => 'ASC',
          ],
          3 => [
            'column' => 'civicrm_address.street_number',
            'order' => 'ASC',
          ],
          4 => [
            'column' => 'contact_a.sort_name',
            'order' => 'ASC',
          ],
        ];
    }

    $orderBy = [];
    if (!empty($orderByParams)) {
      foreach ($orderByParams as $key => $val) {
        if (!empty($val['column'])) {
          $orderBy[] = "{$val['column']} {$val['order']}";
        }
      }
      if (!empty($orderBy)) {
        $orderClause = "ORDER BY " . implode(', ', $orderBy);
      }
    }

    $this->_contactIds = array_unique($this->_contactIds);
    if (!empty($this->_contactIds) && $orderClause) {
      $clause = 'contact_a.id IN ( ' . implode(',', $this->_contactIds) . ' ) ';
      $sql = "
SELECT DISTINCT(contact_a.id)
FROM civicrm_contact contact_a
LEFT JOIN civicrm_address ON contact_a.id = civicrm_address.contact_id
WHERE {$clause}
{$orderClause}";

      $this->_contactIds = [];
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $this->_contactIds[] = $dao->id;
      }
    }

    //get the contact read only fields to display.
    $readOnlyFields = array_merge([
      'contact_type' => '',
      'sort_name' => ts('Name'),
    ]);

    //get the read only field data.
    $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
    $returnProperties['contact_sub_type'] = TRUE;

    //validate all voters for required activity.
    //get the survey activities for given voters.
    $this->_surveyActivityIds = CRM_Campaign_BAO_Survey::voterActivityDetails($this->_surveyId,
      $this->_contactIds,
      $this->_interviewerId
    );
    $scheduledStatusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Scheduled');

    $activityIds = [];
    foreach ($this->_contactIds as $key => $voterId) {
      $actVals = $this->_surveyActivityIds[$voterId] ?? NULL;
      $statusId = $actVals['status_id'] ?? NULL;
      $activityId = $actVals['activity_id'] ?? NULL;
      if ($activityId &&
        $statusId &&
        $scheduledStatusId == $statusId
      ) {
        $activityIds["activity_id_{$voterId}"] = $activityId;
      }
      else {
        unset($this->_contactIds[$key]);
      }
    }

    //retrieve the contact details.
    $voterDetails = CRM_Campaign_BAO_Survey::voterDetails($this->_contactIds, $returnProperties);

    $this->_allowAjaxReleaseButton = FALSE;
    if ($this->_votingTab &&
      (CRM_Core_Permission::check('manage campaign') ||
        CRM_Core_Permission::check('administer CiviCampaign') ||
        CRM_Core_Permission::check('release campaign contacts')
      )
    ) {
      $this->_allowAjaxReleaseButton = TRUE;
    }

    //validate voter ids across profile.
    $this->filterVoterIds();
    $this->assign('votingTab', $this->_votingTab);
    $this->assign('componentIds', $this->_contactIds);
    $this->assign('componentIdsJson', json_encode($this->_contactIds));
    $this->assign('voterDetails', $voterDetails);
    $this->assign('readOnlyFields', $readOnlyFields);
    $this->assign('interviewerId', $this->_interviewerId);
    $this->assign('surveyActivityIds', json_encode($activityIds));
    $this->assign('allowAjaxReleaseButton', $this->_allowAjaxReleaseButton);

    //get the survey values.
    $this->_surveyValues = $this->get('surveyValues');
    if (!is_array($this->_surveyValues)) {
      $this->_surveyValues = [];
      if ($this->_surveyId) {
        $surveyParams = ['id' => $this->_surveyId];
        CRM_Campaign_BAO_Survey::retrieve($surveyParams, $this->_surveyValues);
      }
      $this->set('surveyValues', $this->_surveyValues);
    }
    $this->assign('surveyValues', $this->_surveyValues);

    $result = CRM_Campaign_BAO_Survey::getReportID($this->_surveyId);
    $this->assign("instanceId", $result);

    //get the survey result options.
    $this->_resultOptions = $this->get('resultOptions');
    if (!is_array($this->_resultOptions)) {
      $this->_resultOptions = [];
      $resultOptionId = $this->_surveyValues['result_id'] ?? NULL;
      if ($resultOptionId) {
        $this->_resultOptions = CRM_Core_OptionGroup::valuesByID($resultOptionId);
      }
      $this->set('resultOptions', $this->_resultOptions);
    }

    //validate the required ids.
    $this->validateIds();

    //append breadcrumb to survey dashboard.
    if (CRM_Campaign_BAO_Campaign::accessCampaign()) {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey(s)'), 'url' => $url]]);
    }

    //set the title.
    $this->_surveyTypeId = $this->_surveyValues['activity_type_id'] ?? NULL;
    $surveyTypeLabel = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $this->_surveyTypeId);
    $this->setTitle(ts('Record %1 Responses', [1 => $surveyTypeLabel]));
  }

  public function validateIds() {
    $required = [
      'surveyId' => ts('Could not find Survey.'),
      'interviewerId' => ts('Could not find Interviewer.'),
      'contactIds' => ts('No respondents are currently reserved for you to interview.'),
      'resultOptions' => ts('Oops. It looks like there is no response option configured.'),
    ];

    $errorMessages = [];
    foreach ($required as $fld => $msg) {
      if (empty($this->{"_$fld"})) {
        if (!$this->_votingTab) {
          CRM_Core_Error::statusBounce($msg);
          break;
        }
        $errorMessages[] = $msg;
      }
    }

    $this->assign('errorMessages', empty($errorMessages) ? FALSE : $errorMessages);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->assign('surveyTypeId', $this->_surveyTypeId);

    $options
      = [
        '' => ' - none - ',
        'civicrm_address.street_name' => 'Street Name',
        'civicrm_address.street_number%2' => 'Odd / Even Street Number',
        'civicrm_address.street_number' => 'Street Number',
        'contact_a.sort_name' => 'Respondent Name',
      ];
    for ($i = 1; $i < count($options); $i++) {
      $this->addElement('select', "order_bys[{$i}][column]", ts('Order by Column'), $options);
      $this->addElement('select', "order_bys[{$i}][order]", ts('Order by Order'), [
        'ASC' => ts('Ascending'),
        'DESC' => ts('Descending'),
      ]);
    }

    //pickup the uf fields.
    $this->_surveyFields = CRM_Campaign_BAO_Survey::getSurveyResponseFields($this->_surveyId,
      $this->_surveyTypeId
    );

    foreach ($this->_contactIds as $contactId) {
      //build the profile fields.
      foreach ($this->_surveyFields as $name => $field) {
        if ($field) {
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $contactId);
        }
      }

      //build the result field.
      if (!empty($this->_resultOptions)) {
        $this->add('select', "field[$contactId][result]", ts('Result'),
          [
            '' => ts('- select -'),
          ] +
          array_combine($this->_resultOptions, $this->_resultOptions)
        );
      }

      $this->add('text', "field[{$contactId}][note]", ts('Note'));

      //need to keep control for release/reserve.
      if ($this->_allowAjaxReleaseButton) {
        $this->addElement('hidden',
          "field[{$contactId}][is_release_or_reserve]", 0,
          ['id' => "field_{$contactId}_is_release_or_reserve"]
        );
      }
    }
    $this->assign('surveyFields', empty($this->_surveyFields) ? FALSE : $this->_surveyFields);

    //no need to get qf buttons.
    if ($this->_votingTab) {
      return;
    }

    $buttons = [
      [
        'type' => 'cancel',
        'name' => ts('Done'),
        'subName' => 'interview',
        'isDefault' => TRUE,
      ],
    ];

    $buttons[] = [
      'type' => 'submit',
      'name' => ts('Order By >>'),
      'subName' => 'orderBy',
    ];

    $manageCampaign = CRM_Core_Permission::check('manage campaign');
    $adminCampaign = CRM_Core_Permission::check('administer CiviCampaign');
    if ($manageCampaign ||
      $adminCampaign ||
      CRM_Core_Permission::check('release campaign contacts')
    ) {
      $buttons[] = [
        'type' => 'next',
        'name' => ts('Release Respondents >>'),
        'subName' => 'interviewToRelease',
      ];
    }
    if ($manageCampaign ||
      $adminCampaign ||
      CRM_Core_Permission::check('reserve campaign contacts')
    ) {
      $buttons[] = [
        'type' => 'done',
        'name' => ts('Reserve More Respondents >>'),
        'subName' => 'interviewToReserve',
      ];
    }

    $this->addButtons($buttons);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    //load default data for only contact fields.
    $contactFields = $defaults = [];
    foreach ($this->_surveyFields as $name => $field) {
      $acceptable_types = CRM_Contact_BAO_ContactType::basicTypes();
      $acceptable_types[] = 'Contact';
      if (isset($field['field_type']) && (in_array($field['field_type'], $acceptable_types))) {
        $contactFields[$name] = $field;
      }
    }
    if (!empty($contactFields)) {
      foreach ($this->_contactIds as $contactId) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactId, $contactFields, $defaults, FALSE);
      }
    }

    $walkListActivityId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'WalkList');
    if ($walkListActivityId == $this->_surveyDetails['activity_type_id']) {
      $defaults['order_bys']
        = [
          1 => [
            'column' => 'civicrm_address.street_name',
            'order' => 'ASC',
          ],
          2 => [
            'column' => 'civicrm_address.street_number%2',
            'order' => 'ASC',
          ],
          3 => [
            'column' => 'civicrm_address.street_number',
            'order' => 'ASC',
          ],
          4 => [
            'column' => 'contact_a.sort_name',
            'order' => 'ASC',
          ],
        ];
    }
    else {
      $defaults['order_bys']
        = [
          1 => [
            'column' => 'contact_a.sort_name',
            'order' => 'ASC',
          ],
        ];
    }
    return $defaults;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == '_qf_Interview_done_interviewToReserve') {
      //hey its time to stop cycle.
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/survey/search', 'reset=1&op=reserve'));
    }
    elseif ($buttonName == '_qf_Interview_next_interviewToRelease') {
      //get ready to jump to release form.
      foreach (['surveyId', 'contactIds', 'interviewerId'] as $fld) {
        $this->controller->set($fld, $this->{"_$fld"});
      }
      $this->controller->set('interviewToRelease', TRUE);
    }

    // vote is done through ajax
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public static function registerInterview($params) {
    $activityId = $params['activity_id'] ?? NULL;
    $surveyTypeId = $params['activity_type_id'] ?? NULL;
    if (!is_array($params) || !$surveyTypeId || !$activityId) {
      return FALSE;
    }

    static $surveyFields;
    if (!is_array($surveyFields)) {
      $surveyFields = CRM_Core_BAO_CustomField::getFields('Activity',
        FALSE,
        FALSE,
        $surveyTypeId,
        NULL,
        FALSE,
        TRUE
      );
    }

    static $statusId;
    if (!$statusId) {
      $statusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Completed');
    }

    //format custom fields.
    $customParams = CRM_Core_BAO_CustomField::postProcess($params,
      $activityId,
      'Activity'
    );

    CRM_Core_BAO_CustomValueTable::store($customParams, 'civicrm_activity', $activityId);

    //process contact data.
    $contactParams = $fields = [];

    $contactFieldTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes());
    $responseFields = CRM_Campaign_BAO_Survey::getSurveyResponseFields($params['survey_id']);
    if (!empty($responseFields)) {
      foreach ($params as $key => $value) {
        if (array_key_exists($key, $responseFields)) {
          if (in_array($responseFields[$key]['field_type'], $contactFieldTypes)) {
            $fields[$key] = $responseFields[$key];
            $contactParams[$key] = $value;
            if (isset($params["{$key}_id"])) {
              $contactParams["{$key}_id"] = $params["{$key}_id"];
            }
          }
        }
      }
    }

    $contactId = $params['voter_id'] ?? NULL;
    if ($contactId && !empty($contactParams)) {
      CRM_Contact_BAO_Contact::createProfileContact($contactParams, $fields, $contactId);
    }

    //update activity record.
    $activity = new CRM_Activity_DAO_Activity();
    $activity->id = $activityId;

    $activity->selectAdd();
    $activity->selectAdd('activity_date_time, status_id, result, subject');
    $activity->find(TRUE);
    $activity->activity_date_time = date('YmdHis');
    $activity->status_id = $statusId;

    if (!empty($params['activity_date_time'])) {
      $activity->activity_date_time = CRM_Utils_Date::processDate($params['activity_date_time'], $params['activity_date_time_time']);
    }

    $subject = '';
    $surveyTitle = $params['surveyTitle'] ?? NULL;
    if ($surveyTitle) {
      $subject = $surveyTitle . ' - ';
    }
    $subject .= ts('Respondent Interview');

    $activity->subject = $subject;
    $activityParams = [
      'details' => 'details',
      'result' => 'result',
      'engagement_level' => 'activity_engagement_level',
      'subject' => 'activity_subject',
      'status_id' => 'activity_status_id',
      'source_contact_id' => 'source_contact',
      'location' => 'activity_location',
      'campaign_id' => 'activity_campaign_id',
      'duration' => 'activity_duration',
    ];
    foreach ($activityParams as $key => $field) {
      if (!empty($params[$field])) {
        $activity->$key = $params[$field];
      }
    }

    $activity->save();
    //really this should use Activity BAO& not be here but refactoring will have to be later
    //actually the whole ajax call could be done as an api ajax call & post hook would be sorted
    CRM_Utils_Hook::post('edit', 'Activity', $activity->id, $activity, $params);

    return $activityId;
  }

  public function getVoterIds() {
    if (!$this->_interviewerId) {
      $session = CRM_Core_Session::singleton();
      $this->_interviewerId = $session->get('userID');
    }
    if (!$this->_surveyId) {
      // use default survey id
      $dao = new CRM_Campaign_DAO_Survey();
      $dao->is_active = 1;
      $dao->is_default = 1;
      $dao->find(TRUE);
      $this->_surveyId = $dao->id;
    }

    $this->_contactIds = $this->get('contactIds');
    if (!is_array($this->_contactIds)) {
      //get the survey activities.
      $statusIds[] = CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Scheduled');
      $surveyActivities = CRM_Campaign_BAO_Survey::getSurveyVoterInfo($this->_surveyId,
        $this->_interviewerId,
        $statusIds
      );
      $this->_contactIds = [];
      foreach ($surveyActivities as $val) {
        $this->_contactIds[$val['voter_id']] = $val['voter_id'];
      }
      $this->set('contactIds', $this->_contactIds);
    }
  }

  public function filterVoterIds() {
    //do the cleanup later on.
    if (!is_array($this->_contactIds)) {
      return;
    }

    $profileId = CRM_Campaign_BAO_Survey::getSurveyProfileId($this->_surveyId);
    if ($profileId) {
      $profileType = CRM_Core_BAO_UFField::getProfileType($profileId);
      if (in_array($profileType, CRM_Contact_BAO_ContactType::basicTypes())) {
        $voterIdCount = count($this->_contactIds);

        //create temporary table to store voter ids.
        $tempTableName = CRM_Utils_SQL_TempTable::build()
          ->createWithColumns('id int unsigned NOT NULL AUTO_INCREMENT, survey_contact_id int unsigned NOT NULL, PRIMARY KEY ( id )')
          ->getName();
        $batch = 100;
        $insertedCount = 0;
        do {
          $processIds = $this->_contactIds;
          $insertIds = array_splice($processIds, $insertedCount, $batch);
          if (!empty($insertIds)) {
            $insertSQL = "INSERT IGNORE INTO {$tempTableName}( survey_contact_id )
                     VALUES (" . implode('),(', $insertIds) . ');';
            CRM_Core_DAO::executeQuery($insertSQL);
          }
          $insertedCount += $batch;
        } while ($insertedCount < $voterIdCount);

        $query = "
    SELECT  contact.id as id
      FROM  civicrm_contact contact
INNER JOIN  {$tempTableName} ON ( {$tempTableName}.survey_contact_id = contact.id )
     WHERE  contact.contact_type != %1";
        $removeContact = CRM_Core_DAO::executeQuery($query,
          [1 => [$profileType, 'String']]
        );
        while ($removeContact->fetch()) {
          unset($this->_contactIds[$removeContact->id]);
        }
      }
    }
  }

}
