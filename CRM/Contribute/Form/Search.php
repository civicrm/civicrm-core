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
  protected $_prefix = "contribute_";

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contribution';
  }

  /**
   * Processing needed for buildForm and later.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    parent::preProcess();

    //membership ID
    $memberShipId = CRM_Utils_Request::retrieve('memberId', 'Positive', $this);
    if (isset($memberShipId)) {
      $this->_formValues['contribution_membership_id'] = $memberShipId;
    }
    $participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);
    if (isset($participantId)) {
      $this->_formValues['contribution_participant_id'] = $participantId;
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
   * @throws \Exception
   */
  public function setDefaultValues() {
    $lowReceiveDate = CRM_Utils_Request::retrieve('start', 'Timestamp');
    if (!empty($lowReceiveDate)) {
      $this->_formValues['receive_date_low'] = date('Y-m-d H:i:s', strtotime($lowReceiveDate));
      CRM_Core_Error::deprecatedFunctionWarning('pass receive_date_low not start');
    }
    $highReceiveDate = CRM_Utils_Request::retrieve('end', 'Timestamp');
    if (!empty($highReceiveDate)) {
      $this->_formValues['receive_date_high'] = date('Y-m-d H:i:s', strtotime($highReceiveDate));
      CRM_Core_Error::deprecatedFunctionWarning('pass receive_date_high not end');
    }
    $this->_defaults = parent::setDefaultValues();

    $this->_defaults = array_merge($this->getEntityDefaults('ContributionRecur'), $this->_defaults);

    if (empty($this->_defaults['contribution_status_id']) && !$this->_force) {
      // In force mode only parameters from the url will be used. When visible/ explicit this is a useful default.
      $this->_defaults['contribution_status_id'][1] = CRM_Core_PseudoConstant::getKey(
        'CRM_Contribute_BAO_Contribution',
        'contribution_status_id',
        'Completed'
      );
    }
    return $this->_defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    if ($this->isFormInViewOrEditMode()) {
      parent::buildQuickForm();
      $this->addContactSearchFields();

      CRM_Contribute_BAO_Query::buildSearchForm($this);
    }

    $rows = $this->get('rows');
    if (is_array($rows)) {
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }

      $permission = CRM_Core_Permission::getPermission();

      $queryParams = $this->get('queryParams');
      $taskParams['softCreditFiltering'] = FALSE;
      if (!empty($queryParams)) {
        $taskParams['softCreditFiltering'] = CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($queryParams);
      }
      $tasks = CRM_Contribute_Task::permissionedTaskTitles($permission, $taskParams);
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
   * Get the label for the tag field.
   *
   * We do this in a function so the 'ts' wraps the whole string to allow
   * better translation.
   *
   * @return string
   */
  protected function getTagLabel() {
    return ts('Contributor Tag(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getGroupLabel() {
    return ts('Contributor Group(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getContactTypeLabel() {
    return ts('Contributor Contact Type');
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

    $this->setFormValues();
    // @todo - stop changing formValues - respect submitted form values, change a working array.
    $this->fixFormValues();

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['contribution_test']) && $this->_force && !empty($this->_context) && $this->_context == 'dashboard') {
      // @todo - stop changing formValues - respect submitted form values, change a working array.
      $this->_formValues["contribution_test"] = 0;
    }

    foreach ([
      'contribution_amount_low',
      'contribution_amount_high',
    ] as $f) {
      if (isset($this->_formValues[$f])) {
        // @todo - stop changing formValues - respect submitted form values, change a working array.
        $this->_formValues[$f] = CRM_Utils_Rule::cleanMoney($this->_formValues[$f]);
      }
    }

    if (!empty($_POST)) {
      $specialParams = [
        'financial_type_id',
        'contribution_soft_credit_type_id',
        'contribution_status_id',
        'contribution_trxn_id',
        'contribution_page_id',
        'contribution_product_id',
        'invoice_id',
        'payment_instrument_id',
        'contribution_batch_id',
      ];
      // @todo - stop changing formValues - respect submitted form values, change a working array.
      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, $specialParams);

      $tags = CRM_Utils_Array::value('contact_tags', $this->_formValues);
      if ($tags && !is_array($tags)) {
        // @todo - stop changing formValues - respect submitted form values, change a working array.
        unset($this->_formValues['contact_tags']);
        $this->_formValues['contact_tags'][$tags] = 1;
      }

      if ($tags && is_array($tags)) {
        unset($this->_formValues['contact_tags']);
        foreach ($tags as $notImportant => $tagID) {
          // @todo - stop changing formValues - respect submitted form values, change a working array.
          $this->_formValues['contact_tags'][$tagID] = 1;
        }
      }

      $group = CRM_Utils_Array::value('group', $this->_formValues);
      if ($group && !is_array($group)) {
        // @todo - stop changing formValues - respect submitted form values, change a working array.
        unset($this->_formValues['group']);
        $this->_formValues['group'][$group] = 1;
      }

      if ($group && is_array($group)) {
        // @todo - stop changing formValues - respect submitted form values, change a working array.
        unset($this->_formValues['group']);
        foreach ($group as $groupID) {
          $this->_formValues['group'][$groupID] = 1;
        }
      }
    }

    // @todo - stop changing formValues - respect submitted form values, change a working array.
    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    // @todo - stop changing formValues - respect submitted form values, change a working array.
    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

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

    // @todo - stop changing formValues - respect submitted form values, change a working array.
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

    $status = CRM_Utils_Request::retrieve('status', 'String');
    if ($status) {
      $this->_formValues['contribution_status_id'] = [$status => 1];
      $this->_defaults['contribution_status_id'] = [$status => 1];
    }

    $pcpid = (array) CRM_Utils_Request::retrieve('pcpid', 'String', $this);
    if ($pcpid) {
      // Add new pcpid to the tail of the array...
      foreach ($pcpid as $pcpIdList) {
        $this->_formValues['contribution_pcp_made_through_id'][] = $pcpIdList;
      }
      // and avoid any duplicate
      $this->_formValues['contribution_pcp_made_through_id'] = array_unique($this->_formValues['contribution_pcp_made_through_id']);
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

    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive',
      $this
    );

    $test = CRM_Utils_Request::retrieve('test', 'Boolean');
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

  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Contributions');
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Contribution' => CRM_Contribute_BAO_Query::getSearchFieldMetadata()]);
    $this->addSearchFieldMetadata(['ContributionRecur' => CRM_Contribute_BAO_ContributionRecur::getContributionRecurSearchFieldMetadata()]);
  }

}
