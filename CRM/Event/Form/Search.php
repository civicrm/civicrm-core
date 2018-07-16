<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Files required
 */

/**
 * This file is for civievent search
 */
class CRM_Event_Form_Search extends CRM_Core_Form_Search {

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
  protected $_prefix = "event_";

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * Processing needed for buildForm and later.
   *
   * @return void
   */
  public function preProcess() {
    $this->set('searchFormName', 'Search');

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean');
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'search');
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
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

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, array('event_id'));
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
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addContactSearchFields();

    CRM_Event_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      $lineItems = $eventIds = array();
      if (!$this->_single) {
        $this->addRowSelectors($rows);
      }
      foreach ($rows as $row) {
        $eventIds[$row['event_id']] = $row['event_id'];
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
        if (CRM_Utils_Array::value('participant_test', $this->_formValues) == '1' || CRM_Utils_Array::value('participant_test', $this->_formValues) == '0') {
          $seatClause[] = "( participant.is_test = {$this->_formValues['participant_test']} )";
        }
        if (!empty($this->_formValues['participant_status_id'])) {
          $seatClause[] = CRM_Contact_BAO_Query::buildClause("participant.status_id", '=', $this->_formValues['participant_status_id'], 'Int');
          if ($status = CRM_Utils_Array::value('IN', $this->_formValues['participant_status_id'])) {
            $this->_formValues['participant_status_id'] = $status;
          }
        }
        if (!empty($this->_formValues['participant_role_id'])) {
          $escapedRoles = array();
          foreach ((array) $this->_formValues['participant_role_id'] as $participantRole) {
            $escapedRoles[] = CRM_Utils_Type::escape($participantRole, 'String');
          }
          $seatClause[] = "( participant.role_id IN ( '" . implode("' , '", $escapedRoles) . "' ) )";
        }

        // CRM-15379
        if (!empty($this->_formValues['participant_fee_id'])) {
          $participant_fee_id = $this->_formValues['participant_fee_id'];
          foreach ($participant_fee_id as $k => &$val) {
            $val = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $val, 'label');
            $val = CRM_Core_DAO::escapeString(trim($val));
          }
          $feeLabel = implode('|', $participant_fee_id);
          $seatClause[] = "( participant.fee_level REGEXP '{$feeLabel}' )";
        }

        $seatClause = implode(' AND ', $seatClause);
        $participantCount = CRM_Event_BAO_Event::eventTotalSeats(array_pop($eventIds), $seatClause);
      }
      $this->assign('participantCount', $participantCount);
      $this->assign('lineItems', $lineItems);

      $taskParams['ssID'] = isset($this->_ssID) ? $this->_ssID : NULL;
      $tasks = CRM_Event_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission(), $taskParams);

      if (isset($this->_ssID)) {
        $savedSearchValues = array(
          'id' => $this->_ssID,
          'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        );
        $this->assign_by_ref('savedSearch', $savedSearchValues);
        $this->assign('ssID', $this->_ssID);
      }

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
    return ts('Participant Name or Email');
  }

  /**
   * Get the label for the sortName field if email searching is off.
   *
   * (email searching is a setting under search preferences).
   *
   * @return string
   */
  protected function getSortNameLabelWithOutEmail() {
    return ts('Participant Name');
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
    return ts('Participant Tag(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getGroupLabel() {
    return ts('Participant Group(s)');
  }

  /**
   * Get the label for the group field.
   *
   * @return string
   */
  protected function getContactTypeLabel() {
    return ts('Participant Contact Type');
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
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
      CRM_Contact_BAO_Query::processSpecialFormValue($this->_formValues, array('participant_status_id'));
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

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($this->_formValues);

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, array('event_id'));

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

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, array('event_id'));

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
   * add the rules (mainly global rules) for form.
   * All local rules are added near the element
   *
   * @return void
   * @see valid_date
   */
  public function addRules() {
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_formValues;
    return $defaults;
  }

  public function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)
    $event = CRM_Utils_Request::retrieve('event', 'Positive');
    if ($event) {
      $this->_formValues['event_id'] = $event;
      $this->_formValues['event_name'] = CRM_Event_PseudoConstant::event($event, TRUE);
    }

    $status = CRM_Utils_Request::retrieve('status', 'String');

    if (isset($status)) {
      if ($status === 'true') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 1");
      }
      elseif ($status === 'false') {
        $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, "is_counted = 0");
      }
      elseif (is_numeric($status)) {
        $statusTypes = (int) $status;
      }
      elseif (is_array($status) && !array_key_exists('IN', $status)) {
        $statusTypes = array_keys($status);
      }
      $this->_formValues['participant_status_id'] = is_array($statusTypes) ? array('IN' => array_keys($statusTypes)) : $statusTypes;
    }

    $role = CRM_Utils_Request::retrieve('role', 'String');

    if (isset($role)) {
      if ($role === 'true') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 1");
      }
      elseif ($role === 'false') {
        $roleTypes = CRM_Event_PseudoConstant::participantRole(NULL, "filter = 0");
      }
      elseif (is_numeric($role)) {
        $roleTypes = (int) $role;
      }
      $this->_formValues['participant_role_id'] = is_array($roleTypes) ? array_keys($roleTypes) : $roleTypes;
    }

    $type = CRM_Utils_Request::retrieve('type', 'Positive');
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

  /**
   * @return null
   */
  public function getFormValues() {
    return NULL;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Participants');
  }

}
