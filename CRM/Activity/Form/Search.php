<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This file is for activity search.
 */
class CRM_Activity_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var bool
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller.
   * @var string
   */
  protected $_prefix = "activity_";

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * The compoent name of Activity, later used to filter and format filters on forced search
   */
  protected $_component = 'CiviActivity';

  /**
   * @return string
   */
  public function getDefaultEntity() {
    return 'Activity';
  }

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    // set the button names
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    $this->loadStandardSearchOptionsFromUrl();

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');

      if ($this->_force) {
        // If we force the search then merge form values with url values
        // and set submit values to form values.
        // @todo this is not good security practice. Instead define the fields in metadata & use
        // getEntityDefaults.
        $this->_formValues = array_merge((array) $this->_formValues, CRM_Utils_Request::exportValues());
        $this->_submitValues = $this->_formValues;
      }
    }

    if (empty($this->_formValues)) {
      if (isset($this->_ssID)) {
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
      }
    }

    if ($this->_force) {
      $this->postProcess();
      $this->set('force', 0);
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $controller->moveFromSessionToTemplate();

    $this->assign('summary', $this->get('summary'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addSortNameField();

    CRM_Activity_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $this->addTaskMenu(CRM_Activity_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()));
    }

  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post procesing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submissing is
   *        done.
   *
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      $specialParams = [
        'activity_type_id',
        'status_id',
        'priority_id',
        'activity_text',
      ];
      $changeNames = [
        'status_id' => 'activity_status_id',
        'priority_id' => 'activity_priority_id',
      ];

      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams, $changeNames);
    }

    // construct formValues on forced search
    $this->loadSearchParamsFromUrl();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['activity_test']) && $this->_force) {
      $this->_formValues["activity_test"] = 0;
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    // we already processing the formvalues on force search so no need to do that again
    if (!$this->_force) {
      $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

      $this->set('formValues', $this->_formValues);
      $this->set('queryParams', $this->_queryParams);
    }

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page
      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $selector = new CRM_Activity_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $selector->setKey($this->controller->_key);

    $prefix = NULL;
    if ($this->_context == 'basic' || $this->_context == 'user') {
      $prefix = $this->_prefix;
    }

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $sortID,
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);
    $query = &$selector->getQuery();

    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * @return null
   */
  public function getFormValues() {
    return NULL;
  }

  /**
   * This virtual function is used to set the default values of various form elements.
   *
   * @return array|NULL
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    return array_merge($this->getEntityDefaults($this->getDefaultEntity()), (array) $this->_formValues);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Activities');
  }

  protected function getEntityMetadata() {
    return CRM_Activity_BAO_Query::getSearchFieldMetadata();
  }

  /**
   * Responsible to set search params found as url arguments
   */
  public static function setSearchParamFromUrl(&$form) {
    $searchFields = array_merge(
      self::getActivitySearchFields(),
      CRM_Contact_Form_Search_Criteria::getCustomSearchFields(array('Activity'))
    );
    foreach ($searchFields as $name => $info) {
      if ($value = CRM_Utils_Request::retrieve($name, $info['data_type'])) {
        $value = $form->formatSpecialFormValue($name, $value, $info['data_type']);
        $form->_formValues[$info['name']] = $form->_defaults[$info['name']] = $value;
        if ($name == 'survey') {
          $activity_type_id = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'activity_type_id');
          // since checkbox are replaced by multiple select option
          $form->_formValues['activity_type_id'] = $activity_type_id;
          $form->_defaults['activity_type_id'] = $activity_type_id;
        }
        elseif ($name == 'cid') {
          if (empty($form->_formValues['activity_role'])) {
            $form->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'sort_name');
          }
          $form->_single = TRUE;
        }
        elseif (in_array($name, ['dateLow', 'dateHigh'])) {
          $form->_formValues['activity_date_relative'] = 0;
          $form->_defaults['activity_date_relative'] = 0;
        }
      }
    }

    if (!empty($form->_defaults)) {
      $form->setDefaults($form->_defaults);
    }

    $form->_params = CRM_Contact_BAO_Query::convertFormValues($form->_formValues);
    $form->set('formValues', $form->_formValues);
    $form->set('queryParams', $form->_params);
  }

  public static function getActivitySearchFields() {
    return [
      'activity_role' => ['name' => 'activity_role', 'data_type' => 'Integer'],
      'status' => ['name' => 'activity_status_id', 'data_type' => 'CommaSeparatedIntegers'],
      'activity_status_id' => ['name' => 'activity_status_id', 'data_type' => 'CommaSeparatedIntegers'],
      'activity_type_id' => ['name' => 'activity_type_id', 'data_type' => 'CommaSeparatedIntegers'],
      'survey' => ['name' => 'activity_survey_id', 'data_type' => 'Integer'],
      'activity_survey_id' => ['name' => 'activity_survey_id', 'data_type' => 'Integer'],
      'cid' => ['name' => 'contact_id', 'data_type' => 'Integer'],
      'sort_name' => ['name' => 'sort_name', 'data_type' => 'String'],
      'activity_date_relative' => ['name' => 'activity_date_relative', 'data_type' => 'String'],
      'dateLow' => ['name' => 'activity_date_low', 'data_type' => 'Date'],
      'dateHigh' => ['name' => 'activity_date_high', 'data_type' => 'Date'],
      'activity_date_low' => ['name' => 'activity_date_low', 'data_type' => 'Date'],
      'activity_date_high' => ['name' => 'activity_date_high', 'data_type' => 'Date'],
      'parent_id' => ['name' => 'parent_id', 'data_type' => 'Positive'],
      'followup_parent_id' => ['name' => 'parent_id', 'data_type' => 'Positive'],
      'activity_text' => ['name' => 'activity_text', 'data_type' => 'String'],
      'activity_option' => ['name' => 'activity_option', 'data_type' => 'Integer'],
      'activity_test' => ['name' => 'activity_test', 'data_type' => 'Positive'],
      'activity_tags' => ['name' => 'activity_tags', 'data_type' => 'CommaSeparatedIntegers'],
      'activity_engagement_level' => ['name' => 'activity_engagement_level', 'data_type' => 'Integer'],
    ];
  }

}
