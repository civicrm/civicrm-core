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
   * Prefix for the controller.
   * @var string
   */
  protected $_prefix = "event_";

  /**
   * Metadata of all fields to include on the form.
   *
   * @var array
   */
  protected $searchFieldMetadata = [];

  /**
   * Get the default entity for the form.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Participant';
  }

  /**
   * Processing needed for buildForm and later.
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // SearchFormName is deprecated & to be removed - the replacement is for the task to
    // call $this->form->getSearchFormValues()
    // A couple of extensions use it.
    $this->set('searchFormName', 'Search');

    /**
     * set the button names
     */
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;

    parent::preProcess();

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, ['event_id']);
    $selector = new CRM_Event_Selector_Search($this->_queryParams,
      $this->_action,
      NULL,
      $this->_single,
      $this->_limit,
      $this->_context
    );
    $prefix = NULL;
    if ($this->_context === 'user') {
      $prefix = $this->_prefix;
    }

    $this->assign("{$prefix}limit", $this->_limit);
    $this->assign("{$prefix}single", $this->_single);

    $controller = new CRM_Core_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->getSortID(),
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
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addContactSearchFields();

    CRM_Event_BAO_Query::buildSearchForm($this);

    $rows = $this->get('rows');
    if (is_array($rows)) {
      $lineItems = $eventIds = [];
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
        $seatClause = [];
        if (($this->_formValues['participant_test'] ?? NULL) == '1' || ($this->_formValues['participant_test'] ?? NULL) == '0') {
          $seatClause[] = "( participant.is_test = {$this->_formValues['participant_test']} )";
        }
        if (!empty($this->_formValues['participant_status_id'])) {
          $seatClause[] = CRM_Contact_BAO_Query::buildClause("participant.status_id", 'IN', $this->_formValues['participant_status_id'], 'Int');
          $status = $this->_formValues['participant_status_id']['IN'] ?? NULL;
          if ($status) {
            $this->_formValues['participant_status_id'] = $status;
          }
        }
        if (!empty($this->_formValues['participant_role_id'])) {
          $escapedRoles = [];
          foreach ((array) $this->_formValues['participant_role_id'] as $participantRole) {
            $escapedRoles[] = CRM_Utils_Type::escape($participantRole, 'String');
          }
          $seatClause[] = "( participant.role_id IN ( '" . implode("' , '", $escapedRoles) . "' ) )";
        }

        // CRM-15379
        if (!empty($this->_formValues['participant_fee_id'])) {
          $participant_fee_id = $this->_formValues['participant_fee_id'];
          $val_regexp = [];
          foreach ($participant_fee_id as $k => &$val) {
            $val = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $val, 'label');
            $val_regexp[$k] = CRM_Core_DAO::escapeString(preg_quote(trim($val)));
            $val = CRM_Core_DAO::escapeString(trim($val));
          }
          $feeLabel = implode('|', $val_regexp);
          $seatClause[] = "( participant.fee_level REGEXP '{$feeLabel}' )";
        }

        $seatClause = implode(' AND ', $seatClause);
        $participantCount = CRM_Event_BAO_Event::eventTotalSeats(array_pop($eventIds), $seatClause);
      }
      $this->assign('participantCount', $participantCount);
      $this->assign('lineItems', $lineItems);

      $taskParams['ssID'] = $this->_ssID ?? NULL;
      $tasks = CRM_Event_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission(), $taskParams);

      if (isset($this->_ssID)) {
        $savedSearchValues = [
          'id' => $this->_ssID,
          'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        ];
      }
      $this->assign('savedSearch', $savedSearchValues ?? NULL);
      $this->assign('ssID', $this->_ssID);

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
   * Test submit the form.
   * @param $formValues
   */
  public function testSubmit($formValues) {
    $this->submit($formValues);
  }

  /**
   * Submit the search form with given values.
   * @param $formValues
   */
  private function submit($formValues) {
    $this->_formValues = $formValues;

    $this->fixFormValues();

    if (isset($this->_ssID) && empty($_POST)) {
      // if we are editing / running a saved search and the form has not been posted
      $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);
    }

    $this->_queryParams = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, ['event_id']);

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
      $this->getSortID(),
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
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;
    $this->setFormValues();

    $this->submit($this->_formValues);
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

  public function fixFormValues() {
    // if this search has been forced
    // then see if there are any get values, and if so over-ride the post values
    // note that this means that GET over-rides POST :)

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
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Find Participants');
  }

  /**
   * Set the metadata for the form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setSearchMetadata() {
    $this->addSearchFieldMetadata(['Participant' => CRM_Event_BAO_Query::getSearchFieldMetadata()]);
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $this->_defaults = array_merge(parent::setDefaultValues(), (array) $this->_formValues);
    $event = CRM_Utils_Request::retrieve('event', 'Positive');
    if ($event) {
      $this->_defaults['event_id'] = $event;
      $this->_defaults['event_name'] = CRM_Event_PseudoConstant::event($event, TRUE);
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
      $this->_defaults['participant_status_id'] = is_array($statusTypes) ? array_keys($statusTypes) : $statusTypes;
    }
    return $this->_defaults;
  }

}
