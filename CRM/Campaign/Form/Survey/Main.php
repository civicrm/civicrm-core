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
 * This class generates form components for processing a survey.
 */
class CRM_Campaign_Form_Survey_Main extends CRM_Campaign_Form_Survey {

  /**
   * values
   *
   * @var array
   *
   */


  public $_values;

  /**
   * Context.
   *
   * @var string
   */
  protected $_context;

  public function preProcess() {
    parent::preProcess();

    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('context', $this->_context);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->setTitle(ts('Configure Survey') . ' - ' . $this->_surveyTitle);
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

    if ($this->_name != 'Petition') {
      $url = CRM_Utils_System::url('civicrm/campaign', 'reset=1&subPage=survey');
      CRM_Utils_System::appendBreadCrumb([['title' => ts('Survey Dashboard'), 'url' => $url]]);
    }

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

    if (!isset($defaults['is_active'])) {
      $defaults['is_active'] = 1;
    }

    $defaultSurveys = CRM_Campaign_BAO_Survey::getSurveys(TRUE, TRUE);
    if (!isset($defaults['is_default']) && empty($defaultSurveys)) {
      $defaults['is_default'] = 1;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->add('text', 'title', ts('Title'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'title'), TRUE);

    // Activity Type id
    $this->addSelect('activity_type_id', ['option_url' => 'civicrm/admin/campaign/surveyType'], TRUE);

    $this->addEntityRef('campaign_id', ts('Campaign'), [
      'entity' => 'Campaign',
      'create' => TRUE,
      'select' => ['minimumInputLength' => 0],
    ]);

    // script / instructions
    $this->add('wysiwyg', 'instructions', ts('Instructions for interviewers'), ['rows' => 5, 'cols' => 40]);

    // release frequency
    $this->add('number', 'release_frequency', ts('Release Frequency'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'release_frequency'));

    $this->addRule('release_frequency', ts('Release Frequency interval should be a positive number.'), 'positiveInteger');

    // max reserved contacts at a time
    $this->add('number', 'default_number_of_contacts', ts('Maximum reserved at one time'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'default_number_of_contacts'));
    $this->addRule('default_number_of_contacts', ts('Maximum reserved at one time should be a positive number'), 'positiveInteger');

    // total reserved per interviewer
    $this->add('number', 'max_number_of_contacts', ts('Total reserved per interviewer'), CRM_Core_DAO::getAttribute('CRM_Campaign_DAO_Survey', 'max_number_of_contacts'));
    $this->addRule('max_number_of_contacts', ts('Total reserved contacts should be a positive number'), 'positiveInteger');

    // is active ?
    $this->add('checkbox', 'is_active', ts('Active?'));

    // is default ?
    $this->add('checkbox', 'is_default', ts('Default?'));

    parent::buildQuickForm();
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $session = CRM_Core_Session::singleton();

    if ($this->_surveyId) {
      $params['id'] = $this->_surveyId;
    }

    $params['is_active'] ??= 0;
    $params['is_default'] ??= 0;

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(), $this->getSurveyID(), 'Survey');

    $survey = CRM_Campaign_BAO_Survey::writeRecord($params);
    $this->_surveyId = $survey->id;

    if (!empty($this->_values['result_id'])) {
      $query = "SELECT COUNT(*) FROM civicrm_survey WHERE result_id = %1";
      $countSurvey = (int) CRM_Core_DAO::singleValueQuery($query,
        [
          1 => [
            $this->_values['result_id'],
            'Positive',
          ],
        ]
      );
      // delete option group if no any survey is using it.
      if (!$countSurvey) {
        CRM_Core_BAO_OptionGroup::deleteRecord(['id' => $this->_values['result_id']]);
      }
    }

    parent::endPostProcess();
  }

}
