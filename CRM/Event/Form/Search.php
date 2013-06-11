<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Files required
 */

/**
 * This file is for civievent search
 */
class CRM_Event_Form_Search extends CRM_Core_Form {

  /**
   * Are we forced to run a search
   *
   * @var int
   * @access protected
   */
  protected $_force;

  /**
   * name of search button
   *
   * @var string
   * @access protected
   */
  protected $_searchButtonName;

  /**
   * name of print button
   *
   * @var string
   * @access protected
   */
  protected $_printButtonName;

  /**
   * name of action button
   *
   * @var string
   * @access protected
   */
  protected $_actionButtonName;

  /**
   * form values that we will be using
   *
   * @var array
   * @access protected
   */
  protected $_formValues;

  /**
   * the params that are sent to the query
   *
   * @var array
   * @access protected
   */
  protected $_queryParams;

  /**
   * have we already done this search
   *
   * @access protected
   * @var boolean
   */
  protected $_done;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * prefix for the controller
   *
   */
  protected $_prefix = "event_";

  protected $_defaults;

  /**
   * the saved search ID retrieved from the GET vars
   *
   * @var int
   * @access protected
   */
  protected $_ssID;

  /**
   * processing needed for buildForm and later
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->set('searchFormName', 'Search');

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_printButtonName = $this->getButtonName('next', 'print');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */
    $this->_reset   = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force   = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit   = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    $this->_ssID    = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->assign("context", $this->_context);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
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
    $selector = new CRM_Event_Selector_Search($this->_queryParams,
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
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $this->addElement('text', 'sort_name', ts('Participant Name or Email'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    CRM_Event_BAO_Query::buildSearchForm($this);

    /*
     * add form checkboxes for each row. This is needed out here to conform to QF protocol
     * of all elements being declared in builQuickForm
     */
    $rows = $this->get('rows');
    if (is_array($rows)) {
      $lineItems = $eventIds = array();
      if (!$this->_single) {
        $this->addElement('checkbox',
          'toggleSelect',
          NULL,
          NULL,
          array('onclick' => "toggleTaskAction( true ); return toggleCheckboxVals('mark_x_',this);")
        );
      }
      foreach ($rows as $row) {
        $eventIds[$row['event_id']] = $row['event_id'];
        if (!$this->_single) {
          $this->addElement('checkbox', $row['checkbox'],
            NULL, NULL,
            array('onclick' => "toggleTaskAction( true ); return checkSelectedBox('" . $row['checkbox'] . "');")
          );
        }
        if (CRM_Event_BAO_Event::usesPriceSet($row['event_id'])) {
          // add line item details if applicable
          $lineItems[$row['participant_id']] = CRM_Price_BAO_LineItem::getLineItems($row['participant_id']);
        }
      }

      //get actual count only when we are dealing w/ single event.
      $participantCount = 0;
      if (count($eventIds) == 1) {
        //convert form values to clause.
        $seatClause = array();
        // Filter on is_test if specified in search form
        if (CRM_Utils_Array::value('participant_test', $this->_formValues) == '1' || CRM_Utils_Array::value('participant_test', $this->_formValues) == '0' ) {
          $seatClause[] = "( participant.is_test = {$this->_formValues['participant_test']} )";
        }
        if (CRM_Utils_Array::value('participant_status_id', $this->_formValues)) {
          $statuses = array_keys($this->_formValues['participant_status_id']);
          $seatClause[] = '( participant.status_id IN ( ' . implode(' , ', $statuses) . ' ) )';
        }
        if (CRM_Utils_Array::value('participant_role_id', $this->_formValues)) {
          $roles = array_keys($this->_formValues['participant_role_id']);
          $seatClause[] = '( participant.role_id IN ( ' . implode(' , ', $roles) . ' ) )';
        }
        $clause = NULL;
        if (!empty($seatClause)) {
          $clause = implode(' AND ', $seatClause);
        }

        $participantCount = CRM_Event_BAO_Event::eventTotalSeats(array_pop($eventIds), $clause);
      }
      $this->assign('participantCount', $participantCount);
      $this->assign('lineItems', $lineItems);

      $total = $cancel = 0;

      $permission = CRM_Core_Permission::getPermission();

      $tasks = array('' => ts('- actions -')) + CRM_Event_Task::permissionedTaskTitles($permission);
      if (isset($this->_ssID)) {
        if ($permission == CRM_Core_Permission::EDIT) {
          $tasks = $tasks + CRM_Event_Task::optionalTaskTitle();
        }

        $savedSearchValues = array(
          'id' => $this->_ssID,
          'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        );
        $this->assign_by_ref('savedSearch', $savedSearchValues);
        $this->assign('ssID', $this->_ssID);
      }

      $this->add('select', 'task', ts('Actions:') . ' ', $tasks);
      $this->add('submit', $this->_actionButtonName, ts('Go'),
        array(
          'class' => 'form-submit',
          'id' => 'Go',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 0);",
        )
      );

      $this->add('submit', $this->_printButtonName, ts('Print'),
        array(
          'class' => 'form-submit',
          'onclick' => "return checkPerformAction('mark_x', '" . $this->getName() . "', 1);",
        )
      );

      // need to perform tasks on all or selected items ? using radio_ts(task selection) for it
      $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel',
        array('checked' => 'checked')
      );
      $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all',
        array('onclick' => $this->getName() . ".toggleSelect.checked = false; toggleCheckboxVals('mark_x_',this); toggleTaskAction( true );")
      );
    }

    // add buttons
    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      ));
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
   * The processing consists of using a Selector / Controller framework for getting the
   * search results.
   *
   * @param
   *
   * @return void
   * @access public
   */
  function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    if (empty($this->_formValues)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    // We don't show test records in summaries or dashboards
    if (empty($this->_formValues['participant_test']) && $this->_force) {
      $this->_formValues["participant_test"] = 0;
    }

    CRM_Core_BAO_CustomValue::fixFieldValueOfTypeMemo($this->_formValues);

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);

    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_queryParams);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName || $buttonName == $this->_printButtonName) {
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

    $selector = new CRM_Event_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );

    $selector->setKey($this->controller->_key);

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
      CRM_Core_Selector_Controller::SESSION,
      $prefix
    );
    $controller->setEmbedded(TRUE);

    $query = $selector->getQuery();
    if ($this->_context == 'user') {
      $query->setSkipPermission(TRUE);
    }
    $controller->run();
  }

  /**
   * This function is used to add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return None
   * @access public
   * @see valid_date
   */
  function addRules() {
    $this->addFormRule(array('CRM_Event_Form_Search', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   * @param array $errors list of errors to be posted back to the form
   *
   * @return void
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = array();

    if ($fields['event_name'] && !is_numeric($fields['event_id'])) {
      $errors['event_id'] = ts('Please select valid event.');
    }

    if ($fields['event_type'] && !is_numeric($fields['event_type_id'])) {
      $errors['event_type'] = ts('Please select valid event type.');
    }
    if (!empty($errors)) {
      return $errors;
    }

    return TRUE;
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_formValues;
    return $defaults;
  }

  function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)
    $event = CRM_Utils_Request::retrieve('event', 'Positive',
      CRM_Core_DAO::$_nullObject
    );
    if ($event) {
      $this->_formValues['event_id'] = $event;
      $this->_formValues['event_name'] = CRM_Event_PseudoConstant::event($event, TRUE);
    }

    $status = CRM_Utils_Request::retrieve('status', 'String',
      CRM_Core_DAO::$_nullObject
    );

    if (isset($status)) {
      if ($status === 'true') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 1");
      }
      elseif ($status === 'false') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 0");
      }
      elseif (is_numeric($status)) {
        $status = (int) $status;
        $statusTypes = array($status => CRM_Event_PseudoConstant::participantStatus($status));
      }
      $status = array();
      foreach ($statusTypes as $key => $value) {
        $status[$key] = 1;
      }
      $this->_formValues['participant_status_id'] = $status;
    }

    $role = CRM_Utils_Request::retrieve('role', 'String',
      CRM_Core_DAO::$_nullObject
    );

    if (isset($role)) {
      if ($role === 'true') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 1");
      }
      elseif ($role === 'false') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 0");
      }
      elseif (is_numeric($role)) {
        $role = (int) $role;
        $roleTypes = array($role => CRM_Event_PseudoConstant::participantRole($role));
      }
      $role = array();
      foreach ($roleTypes as $key => $value) {
        $role[$key] = 1;
      }
      $this->_formValues['participant_role_id'] = $role;
    }

    $type = CRM_Utils_Request::retrieve('type', 'Positive',
      CRM_Core_DAO::$_nullObject
    );
    if ($type) {
      $this->_formValues['event_type'] = $type;
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($cid) {
      $cid = CRM_Utils_Type::escape($cid, 'Integer');
      if ($cid > 0) {
        $this->_formValues['contact_id'] = $cid;

        // also assign individual mode to the template
        $this->_single = TRUE;
      }
    }
  }

  function getFormValues() {
    return NULL;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Find Participants');
  }
}

