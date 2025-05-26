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
 * This class generates form components for adding a petition.
 */
class CRM_Campaign_Form_Petition extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Making this public so we can reference it in the formRule
   * @var int
   */
  public $_surveyId;

  /**
   * @var array
   */
  protected $_values;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Survey';
  }

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_surveyId;
  }

  /**
   * Get the survey ID.
   *
   * @api supported for external use.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getSurveyID(): ?int {
    if (!isset($this->_surveyId)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_surveyId;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    if (!CRM_Campaign_BAO_Campaign::accessCampaign()) {
      CRM_Utils_System::permissionDenied();
    }

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('context', $context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $this->setTitle(ts('Edit Survey'));
      }
      else {
        $this->setTitle(ts('Delete Survey'));
      }
    }

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Survey', array_filter([
        'id' => $this->getSurveyID(),
      ]));
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
    $session->pushUserContext($url);

    $this->_values = $this->get('values');

    if (!is_array($this->_values)) {
      $this->_values = [];
      if ($this->_surveyId) {
        $params = ['id' => $this->_surveyId];
        CRM_Campaign_BAO_Survey::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }

    $this->assign('action', $this->_action);
    $this->assign('surveyId', $this->_surveyId);

    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $this->_surveyId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $this->setTitle(ts('Edit Petition'));
      }
      else {
        $this->setTitle(ts('Delete Petition'));
      }
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition');
    $session->pushUserContext($url);

    CRM_Utils_System::appendBreadCrumb([['title' => ts('Petition Dashboard'), 'url' => $url]]);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    $ufContactJoinParams = [
      'entity_table' => 'civicrm_survey',
      'entity_id' => $this->_surveyId,
      'weight' => 2,
    ];

    if ($ufContactGroupId = CRM_Core_BAO_UFJoin::findUFGroupId($ufContactJoinParams)) {
      $defaults['contact_profile_id'] = $ufContactGroupId;
    }
    $ufActivityJoinParams = [
      'entity_table' => 'civicrm_survey',
      'entity_id' => $this->_surveyId,
      'weight' => 1,
    ];

    if ($ufActivityGroupId = CRM_Core_BAO_UFJoin::findUFGroupId($ufActivityJoinParams)) {
      $defaults['profile_id'] = $ufActivityGroupId;
    }

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    $defaultSurveys = CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE);
    if (!isset($defaults['is_default']) && empty($defaultSurveys)) {
      $defaults['is_default'] = 1;
    }

    return $defaults;
  }

  public function buildQuickForm() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(
        [
          [
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    $this->add('text', 'title', ts('Petition Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'title'), TRUE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey');

    $petitionTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition');
    $this->addElement('hidden', 'activity_type_id', $petitionTypeID);

    // script / instructions / description of petition purpose
    $this->add('wysiwyg', 'instructions', ts('Introduction'), $attributes['instructions']);

    $this->addEntityRef('campaign_id', ts('Campaign'), [
      'entity' => 'Campaign',
      'create' => TRUE,
      'select' => ['minimumInputLength' => 0],
    ]);

    $customContactProfiles = CRM_Core_BAO_UFGroup::getProfiles(['Individual']);
    // custom group id
    $this->add('select', 'contact_profile_id', ts('Contact Profile'),
      [
        '' => ts('- select -'),
      ] + $customContactProfiles, TRUE
    );

    $customProfiles = CRM_Core_BAO_UFGroup::getProfiles(['Activity']);
    // custom group id
    $this->add('select', 'profile_id', ts('Activity Profile'),
      [
        '' => ts('- select -'),
      ] + $customProfiles
    );

    // thank you title and text (html allowed in text)
    $this->add('text', 'thankyou_title', ts('Thank-you Page Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'thankyou_title'));
    $this->add('wysiwyg', 'thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'thankyou_text'));

    // bypass email confirmation?
    $this->add('checkbox', 'bypass_confirm', ts('Bypass email confirmation'));

    //is share through social media
    $this->addElement('checkbox', 'is_share', ts('Add footer region with Twitter, Facebook and LinkedIn share buttons and scripts?'));

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // is default ?
    $this->add('checkbox', 'is_default', ts('Default?'));

    // add buttons
    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    // add a form rule to check default value
    $this->addFormRule(['CRM_Campaign_Form_Petition', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   * @param array $fields
   * @param $files
   * @param CRM_Core_Form $form
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    // Petitions should be unique by: title, campaign ID (if assigned) and activity type ID
    // NOTE: This class is called for both Petition create / update AND for Survey Results tab, but this rule is only for Petition.
    $where = ['activity_type_id = %1', 'title = %2'];
    $params = [
      1 => [$fields['activity_type_id'], 'Integer'],
      2 => [$fields['title'], 'String'],
    ];
    $uniqueRuleErrorMessage = ts('This title is already associated with the selected activity type. Please specify a unique title.');

    if (empty($fields['campaign_id'])) {
      $where[] = 'campaign_id IS NULL';
    }
    else {
      $where[] = 'campaign_id = %3';
      $params[3] = [$fields['campaign_id'], 'Integer'];
      $uniqueRuleErrorMessage = ts('This title is already associated with the selected campaign and activity type. Please specify a unique title.');
    }

    // Exclude current Petition row if UPDATE.
    if ($form->_surveyId) {
      $where[] = 'id != %4';
      $params[4] = [$form->_surveyId, 'Integer'];
    }

    $whereClause = implode(' AND ', $where);

    $query = "
SELECT COUNT(*) AS row_count
FROM   civicrm_survey
WHERE  $whereClause
";

    $result = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($result >= 1) {
      $errors['title'] = $uniqueRuleErrorMessage;
    }
    return empty($errors) ? TRUE : $errors;
  }

  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();

    $params['is_share'] ??= FALSE;

    if ($this->_surveyId) {

      if ($this->_action & CRM_Core_Action::DELETE) {
        CRM_Campaign_BAO_Survey::deleteRecord(['id' => $this->_surveyId]);
        CRM_Core_Session::setStatus(ts(' Petition has been deleted.'), ts('Record Deleted'), 'success');
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition'));
        return;
      }

      $params['id'] = $this->_surveyId;
    }
    else {
      $params['created_id'] = $session->get('userID');
      $params['created_date'] = date('YmdHis');
    }

    $params['bypass_confirm'] ??= 0;
    $params['is_active'] ??= 0;
    $params['is_default'] ??= 0;

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params, $this->getEntityId(), $this->getDefaultEntity());

    $surveyId = CRM_Campaign_BAO_Survey::writeRecord($params);

    // also update the ProfileModule tables
    $ufJoinParams = [
      'is_active' => 1,
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => $surveyId->id,
    ];

    // first delete all past entries
    if ($this->_surveyId) {
      CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);
    }
    if (!empty($params['profile_id'])) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    if (!empty($params['contact_profile_id'])) {
      $ufJoinParams['weight'] = 2;
      $ufJoinParams['uf_group_id'] = $params['contact_profile_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    CRM_Core_Session::setStatus(ts('Petition has been saved.'), ts('Saved'), 'success');

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another Petition.'), '', 'info');
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/petition/add', 'reset=1&action=add'));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=petition'));
    }
  }

}
