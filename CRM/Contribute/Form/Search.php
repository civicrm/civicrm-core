<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Advanced search, extends basic search.
 */
class CRM_Contribute_Form_Search extends CRM_Core_Form_Search {

  /**
   * The params that are sent to the query.
   *
   * @var array
   */
  protected $_queryParams;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * Prefix for the controller.
   */
  protected $_prefix = "contribute_";

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    // @todo - is this an error - $this->_defaults is used.
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');

    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    //membership ID
    $memberShipId = CRM_Utils_Request::retrieve('memberId', 'Positive', $this);
    if (isset($memberShipId)) {
      $this->_formValues['contribution_membership_id'] = $memberShipId;
    }
    $participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);
    if (isset($participantId)) {
      $this->_formValues['contribution_participant_id'] = $participantId;
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
    $selector = new CRM_Contribute_Selector_Search($this->_queryParams,
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

    $this->assign('contributionSummary', $this->get('summary'));
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues() {
    if (empty($this->_defaults['contribution_status'])) {
      $this->_defaults['contribution_status'][1] = 1;
    }
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addSortNameField();

    $this->_group = CRM_Core_PseudoConstant::nestedGroup();

    // multiselect for groups
    if ($this->_group) {
      $this->add('select', 'group', ts('Groups'), $this->_group, FALSE,
        array('id' => 'group', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );
    }

    // multiselect for tags
    $contactTags = CRM_Core_BAO_Tag::getTags();

    if ($contactTags) {
      $this->add('select', 'contact_tags', ts('Tags'), $contactTags, FALSE,
        array('id' => 'contact_tags', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );
    }

    CRM_Contribute_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $permission = CRM_Core_Permission::getPermission();

      $queryParams = $this->get('queryParams');
      $softCreditFiltering = FALSE;
      if (!empty($queryParams)) {
        $softCreditFiltering = CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($queryParams);
      }
      $tasks = CRM_Contribute_Task::permissionedTaskTitles($permission, $softCreditFiltering);
      $this->addTaskMenu($tasks);
    }

  }

  /**
   * Get the label for the sortName field if email searching is on.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithEmail() {
    return ts('Contributor Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Contributor Name');
  }

  /**
   * The post processing of the form gets done here.
   *
   * Key things done during post processing are
   *      - check for reset or next request. if present, skip post processing.
   *      - now check if user requested running a saved search, if so, then
   *        the form values associated with the saved search are used for searching.
   *      - if user has done a submit with new values the regular post submission is
   *        done.
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
    }

    $this->fixFormValues();

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['contribution_test']) && $this->_force && !empty($this->_context) && $this->_context == 'dashboard') {
      $this->_formValues["contribution_test"] = 0;
    }

    foreach (array(
               'contribution_amount_low',
               'contribution_amount_high',
             ) as $f) {
      if (isset($this->_formValues[$f])) {
        $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
      }
    }

    $config = CRM_Core_Config::singleton();
    if (!empty($_POST)) {
      $specialParams = array(
        'financial_type_id',
        'contribution_soft_credit_type_id',
        'contribution_status_id',
        'contribution_source',
        'contribution_trxn_id',
        'contribution_page_id',
        'contribution_product_id',
        'invoice_id',
        'payment_instrument_id',
      );
      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams);

      $tags = CRM_Utils_Array::value('contact_tags', $this->_formValues);
      if ($tags && !is_array($tags)) {
        unset($this->_formValues['contact_tags']);
        $this->_formValues['contact_tags'][$tags] = 1;
      }

      if ($tags && is_array($tags)) {
        unset($this->_formValues['contact_tags']);
        foreach ($tags as $notImportant => $tagID) {
          $this->_formValues['contact_tags'][$tagID] = 1;
        }
      }

      if (!defined('CIVICRM_GROUPTREE')) {
        $group = CRM_Utils_Array::value('group', $this->_formValues);
        if ($group && !is_array($group)) {
          unset($this->_formValues['group']);
          $this->_formValues['group'][$group] = 1;
        }

        if ($group && is_array($group)) {
          unset($this->_formValues['group']);
          foreach ($group as $notImportant => $groupID) {
            $this->_formValues['group'][$groupID] = 1;
          }
        }
      }
    }

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

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
    $selector = new CRM_Contribute_Selector_Search($this->_queryParams,
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
    $summary = &$query->summaryContribution($this->_context);
    $this->set('summary', $summary);
    $this->assign('contributionSummary', $summary);
    $controller->run();
  }

  /**
   * Use values from $_GET if force is set to TRUE.
   *
   * Note that this means that GET over-rides POST. This was a historical decision & the reasoning is not explained.
   */
  public function fixFormValues() {
    if (!$this->_force) {
      return;
    }

    $status = CRM_Utils_Request::retrieve('status', 'String',
      CRM_Core_DAO::$_nullObject
    );
    if ($status) {
      $this->_formValues['contribution_status_id'] = array($status => 1);
      $this->_defaults['contribution_status_id'] = array($status => 1);
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;
        // @todo - why do we retrieve these when they are not used?
        list($display, $image) = CRM_Contact_BAO_Contact::getDisplayAndImage($cid);
        $this->_defaults['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid,
          'sort_name'
        );
        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }

    $lowDate = CRM_Utils_Request::retrieve('start', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($lowDate) {
      $lowDate = CRM_Utils_Type::escape($lowDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($lowDate);
      $this->_formValues['contribution_date_low'] = $this->_defaults['contribution_date_low'] = $date[0];
    }

    $highDate = CRM_Utils_Request::retrieve('end', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($highDate) {
      $highDate = CRM_Utils_Type::escape($highDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($highDate);
      $this->_formValues['contribution_date_high'] = $this->_defaults['contribution_date_high'] = $date[0];
    }

    if ($highDate || $lowDate) {
      //set the Choose Date Range value
      $this->_formValues['contribution_date_relative'] = 0;
    }

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive',
      $this
    );

    $test = CRM_Utils_Request::retrieve('test', 'Boolean',
      CRM_Core_DAO::$_nullObject
    );
    if (isset($test)) {
      $test = CRM_Utils_Type::escape($test, 'Boolean');
      $this->_formValues['contribution_test'] = $test;
    }
    //Recurring id
    $recur = CRM_Utils_Request::retrieve('recur', 'Positive', $this, FALSE);
    if ($recur) {
      $this->_formValues['contribution_recur_id'] = $recur;
      $this->_formValues['contribution_recurring'] = 1;
    }

    //check for contribution page id.
    $contribPageId = CRM_Utils_Request::retrieve('pid', 'Positive', $this);
    if ($contribPageId) {
      $this->_formValues['contribution_page_id'] = $contribPageId;
    }

    //give values to default.
    $this->_defaults = $this->_formValues;
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Contributions');
  }

}
