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
 * Base Search / View form for *all* listing of multiple
 * contacts
 */
class CRM_Contact_Form_Search extends CRM_Core_Form_Search {

  /**
   * list of valid contexts.
   *
   * @var array
   */
  static $_validContext = NULL;

  /**
   * List of values used when we want to display other objects.
   *
   * @var array
   */
  static $_modeValues = NULL;

  /**
   * The contextMenu.
   *
   * @var array
   */
  protected $_contextMenu;

  /**
   * The groupId retrieved from the GET vars.
   *
   * @var int
   */
  public $_groupID;

  /**
   * The Group ID belonging to Add Member to group ID.
   * retrieved from the GET vars
   *
   * @var int
   */
  protected $_amtgID;

  /**
   * The saved search ID retrieved from the GET vars.
   *
   * @var int
   */
  protected $_ssID;

  /**
   * The group elements.
   *
   * @var array
   */
  public $_group;
  public $_groupElement;
  public $_groupIterator;

  /**
   * The tag elements.
   *
   * @var array
   */
  public $_tag;
  public $_tagElement;

  /**
   * The params used for search.
   *
   * @var array
   */
  protected $_params;

  /**
   * The return properties used for search.
   *
   * @var array
   */
  protected $_returnProperties;

  /**
   * The sort by character.
   *
   * @var string
   */
  protected $_sortByCharacter;

  /**
   * The profile group id used for display.
   *
   * @var integer
   */
  protected $_ufGroupID;

  /**
   * Csv - common search values
   *
   * @var array
   */
  static $csv = array('contact_type', 'group', 'tag');

  /**
   * @var string how to display the results. Should we display as
   *             contributons, members, cases etc
   */
  protected $_componentMode;

  /**
   * @var string what operator should we use, AND or OR
   */
  protected $_operator;

  protected $_modeValue;

  /**
   * Declare entity reference fields as they will need to be converted to using 'IN'.
   *
   * @var array
   */
  protected $entityReferenceFields = array('membership_type_id');

  /**
   * Name of the selector to use.
   */
  static $_selectorName = 'CRM_Contact_Selector';
  protected $_customSearchID = NULL;
  protected $_customSearchClass = NULL;

  protected $_openedPanes = array();

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Contact';
  }

  /**
   * Define the set of valid contexts that the search form operates on.
   *
   * @return array
   *   the valid context set and the titles
   */
  public static function &validContext() {
    if (!(self::$_validContext)) {
      self::$_validContext = array(
        'smog' => 'Show members of group',
        'amtg' => 'Add members to group',
        'basic' => 'Basic Search',
        'search' => 'Search',
        'builder' => 'Search Builder',
        'advanced' => 'Advanced Search',
        'custom' => 'Custom Search',
      );
    }
    return self::$_validContext;
  }

  /**
   * @param $context
   *
   * @return bool
   */
  public static function isSearchContext($context) {
    $searchContext = CRM_Utils_Array::value($context, self::validContext());
    return $searchContext ? TRUE : FALSE;
  }

  public static function setModeValues() {
    if (!self::$_modeValues) {
      self::$_modeValues = array(
        1 => array(
          'selectorName' => self::$_selectorName,
          'selectorLabel' => ts('Contacts'),
          'taskFile' => 'CRM/Contact/Form/Search/ResultTasks.tpl',
          'taskContext' => NULL,
          'resultFile' => 'CRM/Contact/Form/Selector.tpl',
          'resultContext' => NULL,
          'taskClassName' => 'CRM_Contact_Task',
        ),
        2 => array(
          'selectorName' => 'CRM_Contribute_Selector_Search',
          'selectorLabel' => ts('Contributions'),
          'taskFile' => 'CRM/common/searchResultTasks.tpl',
          'taskContext' => 'Contribution',
          'resultFile' => 'CRM/Contribute/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Contribute_Task',
        ),
        3 => array(
          'selectorName' => 'CRM_Event_Selector_Search',
          'selectorLabel' => ts('Event Participants'),
          'taskFile' => 'CRM/common/searchResultTasks.tpl',
          'taskContext' => NULL,
          'resultFile' => 'CRM/Event/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Event_Task',
        ),
        4 => array(
          'selectorName' => 'CRM_Activity_Selector_Search',
          'selectorLabel' => ts('Activities'),
          'taskFile' => 'CRM/common/searchResultTasks.tpl',
          'taskContext' => NULL,
          'resultFile' => 'CRM/Activity/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Activity_Task',
        ),
        5 => array(
          'selectorName' => 'CRM_Member_Selector_Search',
          'selectorLabel' => ts('Memberships'),
          'taskFile' => "CRM/common/searchResultTasks.tpl",
          'taskContext' => NULL,
          'resultFile' => 'CRM/Member/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Member_Task',
        ),
        6 => array(
          'selectorName' => 'CRM_Case_Selector_Search',
          'selectorLabel' => ts('Cases'),
          'taskFile' => "CRM/common/searchResultTasks.tpl",
          'taskContext' => NULL,
          'resultFile' => 'CRM/Case/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Case_Task',
        ),
        7 => array(
          'selectorName' => self::$_selectorName,
          'selectorLabel' => ts('Related Contacts'),
          'taskFile' => 'CRM/Contact/Form/Search/ResultTasks.tpl',
          'taskContext' => NULL,
          'resultFile' => 'CRM/Contact/Form/Selector.tpl',
          'resultContext' => NULL,
          'taskClassName' => 'CRM_Contact_Task',
        ),
        8 => array(
          'selectorName' => 'CRM_Mailing_Selector_Search',
          'selectorLabel' => ts('Mailings'),
          'taskFile' => "CRM/common/searchResultTasks.tpl",
          'taskContext' => NULL,
          'resultFile' => 'CRM/Mailing/Form/Selector.tpl',
          'resultContext' => 'Search',
          'taskClassName' => 'CRM_Mailing_Task',
        ),
      );
    }
  }

  /**
   * @param int $mode
   *
   * @return mixed
   */
  public static function getModeValue($mode = 1) {
    self::setModeValues();

    if (!array_key_exists($mode, self::$_modeValues)) {
      $mode = 1;
    }

    return self::$_modeValues[$mode];
  }

  /**
   * @return array
   */
  public static function getModeSelect() {
    self::setModeValues();

    $select = array();
    foreach (self::$_modeValues as $id => & $value) {
      $select[$id] = $value['selectorLabel'];
    }

    // unset contributions or participants if user does not have
    // permission on them
    if (!CRM_Core_Permission::access('CiviContribute')) {
      unset($select['2']);
    }

    if (!CRM_Core_Permission::access('CiviEvent')) {
      unset($select['3']);
    }

    if (!CRM_Core_Permission::check('view all activities')) {
      unset($select['4']);
    }
    return $select;
  }

  /**
   * Builds the list of tasks or actions that a searcher can perform on a result set.
   *
   * @return array
   */
  public function buildTaskList() {
    if ($this->_context !== 'amtg') {
      $permission = CRM_Core_Permission::getPermission();

      if ($this->_componentMode == 1 || $this->_componentMode == 7) {
        $this->_taskList += CRM_Contact_Task::permissionedTaskTitles($permission,
          CRM_Utils_Array::value('deleted_contacts', $this->_formValues)
        );
      }
      else {
        $className = $this->_modeValue['taskClassName'];
        $this->_taskList += $className::permissionedTaskTitles($permission, FALSE);
      }

      // Only offer the "Update Smart Group" task if a smart group/saved search is already in play
      if (isset($this->_ssID) && $permission == CRM_Core_Permission::EDIT) {
        $this->_taskList += CRM_Contact_Task::optionalTaskTitle();
      }
    }

    asort($this->_taskList);
    return $this->_taskList;
  }

  /**
   * Build the common elements between the search/advanced form.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Core_Resources::singleton()
      // jsTree is needed for tags popup
      ->addScriptFile('civicrm', 'packages/jquery/plugins/jstree/jquery.jstree.js', 0, 'html-header', FALSE)
      ->addStyleFile('civicrm', 'packages/jquery/plugins/jstree/themes/default/style.css', 0, 'html-header');
    $permission = CRM_Core_Permission::getPermission();
    // some tasks.. what do we want to do with the selected contacts ?
    $tasks = array();
    if ($this->_componentMode == 1 || $this->_componentMode == 7) {
      $tasks += CRM_Contact_Task::permissionedTaskTitles($permission,
        CRM_Utils_Array::value('deleted_contacts', $this->_formValues)
      );
    }
    else {
      $className = $this->_modeValue['taskClassName'];
      $tasks += $className::permissionedTaskTitles($permission, FALSE);
    }

    if (isset($this->_ssID)) {
      if ($permission == CRM_Core_Permission::EDIT) {
        $tasks = $tasks + CRM_Contact_Task::optionalTaskTitle();
      }

      $search_custom_id
        = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'search_custom_id');

      $savedSearchValues = array(
        'id' => $this->_ssID,
        'name' => CRM_Contact_BAO_SavedSearch::getName($this->_ssID, 'title'),
        'search_custom_id' => $search_custom_id,
      );
      $this->assign_by_ref('savedSearch', $savedSearchValues);
      $this->assign('ssID', $this->_ssID);
    }

    if ($this->_context === 'smog') {
      // CRM-11788, we might want to do this for all of search where force=1
      $formQFKey = CRM_Utils_Array::value('qfKey', $this->_formValues);
      $getQFKey = CRM_Utils_Array::value('qfKey', $_GET);
      $postQFKey = CRM_Utils_Array::value('qfKey', $_POST);
      if ($formQFKey && empty($getQFKey) && empty($postQFKey)) {
        $url = CRM_Utils_System::makeURL('qfKey') . $formQFKey;
        CRM_Utils_System::redirect($url);
      }
      $permissionForGroup = FALSE;

      if (!empty($this->_groupID)) {
        // check if user has permission to edit members of this group
        $permission = CRM_Contact_BAO_Group::checkPermission($this->_groupID);
        if ($permission && in_array(CRM_Core_Permission::EDIT, $permission)) {
          $permissionForGroup = TRUE;
        }

        // check if _groupID exists, it might not if
        // we are displaying a hidden group
        if (!isset($this->_group[$this->_groupID])) {
          $this->_group[$this->_groupID]
            = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'title');
        }

        // set the group title
        $groupValues = array('id' => $this->_groupID, 'title' => $this->_group[$this->_groupID]);
        $this->assign_by_ref('group', $groupValues);

        // also set ssID if this is a saved search
        $ssID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id');
        $this->assign('ssID', $ssID);

        //get the saved search mapping id
        if ($ssID) {
          $this->_ssID = $ssID;
          $ssMappingId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $ssID, 'mapping_id');
          $this->assign('ssMappingID', $ssMappingId);
        }

        // Set dynamic page title for 'Show Members of Group'
        CRM_Utils_System::setTitle(ts('Contacts in Group: %1', array(1 => $this->_group[$this->_groupID])));
      }

      $group_contact_status = array();
      foreach (CRM_Core_SelectValues::groupContactStatus() as $k => $v) {
        if (!empty($k)) {
          $group_contact_status[] = $this->createElement('checkbox', $k, NULL, $v);
        }
      }
      $this->addGroup($group_contact_status,
        'group_contact_status', ts('Group Status')
      );

      $this->assign('permissionedForGroup', $permissionForGroup);
    }

    // add the go button for the action form, note it is of type 'next' rather than of type 'submit'
    if ($this->_context === 'amtg') {
      // check if _groupID exists, it might not if
      // we are displaying a hidden group
      if (!isset($this->_group[$this->_amtgID])) {
        $this->assign('permissionedForGroup', FALSE);
        $this->_group[$this->_amtgID]
          = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_amtgID, 'title');
      }

      // Set dynamic page title for 'Add Members Group'
      CRM_Utils_System::setTitle(ts('Add to Group: %1', array(1 => $this->_group[$this->_amtgID])));
      // also set the group title and freeze the action task with Add Members to Group
      $groupValues = array('id' => $this->_amtgID, 'title' => $this->_group[$this->_amtgID]);
      $this->assign_by_ref('group', $groupValues);
      $this->add('submit', $this->_actionButtonName, ts('Add Contacts to %1', array(1 => $this->_group[$this->_amtgID])),
        array(
          'class' => 'crm-form-submit',
        )
      );
      $this->add('hidden', 'task', CRM_Contact_Task::GROUP_CONTACTS);
      $selectedRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_sel', array('checked' => 'checked'));
      $allRowsRadio = $this->addElement('radio', 'radio_ts', NULL, '', 'ts_all');
      $this->assign('ts_sel_id', $selectedRowsRadio->_attributes['id']);
      $this->assign('ts_all_id', $allRowsRadio->_attributes['id']);
    }

    $selectedContactIds = array();
    $qfKeyParam = CRM_Utils_Array::value('qfKey', $this->_formValues);
    // We use ajax to handle selections only if the search results component_mode is set to "contacts"
    if ($qfKeyParam && ($this->get('component_mode') <= 1 || $this->get('component_mode') == 7)) {
      $this->addClass('crm-ajax-selection-form');
      $qfKeyParam = "civicrm search {$qfKeyParam}";
      $selectedContactIdsArr = CRM_Core_BAO_PrevNextCache::getSelection($qfKeyParam);
      $selectedContactIds = array_keys($selectedContactIdsArr[$qfKeyParam]);
    }

    $this->assign_by_ref('selectedContactIds', $selectedContactIds);

    $rows = $this->get('rows');

    if (is_array($rows)) {
      $this->addRowSelectors($rows);
    }

  }

  /**
   * Processing needed for buildForm and later.
   */
  public function preProcess() {
    // set the various class variables

    $this->_group = CRM_Core_PseudoConstant::group();

    $this->_groupIterator = CRM_Core_PseudoConstant::groupIterator();
    $this->_tag = CRM_Core_BAO_Tag::getTags();
    $this->_done = FALSE;

    /*
     * we allow the controller to set force/reset externally, useful when we are being
     * driven by the wizard framework
     */

    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean',
      CRM_Core_DAO::$_nullObject
    );

    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_groupID = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    $this->_amtgID = CRM_Utils_Request::retrieve('amtgID', 'Positive', $this);
    $this->_ssID = CRM_Utils_Request::retrieve('ssID', 'Positive', $this);
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter', 'String', $this);
    $this->_ufGroupID = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_componentMode = CRM_Utils_Request::retrieve('component_mode', 'Positive', $this, FALSE, 1, $_REQUEST);
    $this->_operator = CRM_Utils_Request::retrieve('operator', 'String', $this, FALSE, 1, $_REQUEST, 'AND');

    /**
     * set the button names
     */
    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->assign('actionButtonName', $this->_actionButtonName);

    // reset from session, CRM-3526
    $session = CRM_Core_Session::singleton();
    if ($this->_force && $session->get('selectedSearchContactIds')) {
      $session->resetScope('selectedSearchContactIds');
    }

    // if we dont get this from the url, use default if one exsts
    $config = CRM_Core_Config::singleton();
    if ($this->_ufGroupID == NULL &&
      $config->defaultSearchProfileID != NULL
    ) {
      $this->_ufGroupID = $config->defaultSearchProfileID;
    }

    // assign context to drive the template display, make sure context is valid
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');
    if (!CRM_Utils_Array::value($this->_context, self::validContext())) {
      $this->_context = 'search';
    }
    $this->set('context', $this->_context);
    $this->assign('context', $this->_context);

    $this->_modeValue = self::getModeValue($this->_componentMode);
    $this->assign($this->_modeValue);

    $this->set('selectorName', self::$_selectorName);

    // get user submitted values
    // get it from controller only if form has been submitted, else preProcess has set this
    // $this->controller->isModal( ) returns TRUE if page is
    // valid, i.e all the validations are TRUE

    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);

      $this->normalizeFormValues();
      $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
      $this->_returnProperties = &$this->returnProperties();

      // also get the uf group id directly from the post value
      $this->_ufGroupID = CRM_Utils_Array::value('uf_group_id', $_POST, $this->_ufGroupID);
      $this->_formValues['uf_group_id'] = $this->_ufGroupID;
      $this->set('id', $this->_ufGroupID);

      // also get the object mode directly from the post value
      $this->_componentMode = CRM_Utils_Array::value('component_mode', $_POST, $this->_componentMode);

      // also get the operator from the post value if set
      $this->_operator = CRM_Utils_Array::value('operator', $_POST, $this->_operator);
      $this->_formValues['operator'] = $this->_operator;
      $this->set('operator', $this->_operator);
    }
    else {
      $this->_formValues = $this->get('formValues');
      $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues, 0, FALSE, NULL, $this->entityReferenceFields);
      $this->_returnProperties = &$this->returnProperties();
      if (!empty($this->_ufGroupID)) {
        $this->set('id', $this->_ufGroupID);
      }
    }

    if (empty($this->_formValues)) {
      //check if group is a smart group (fix for CRM-1255)
      if ($this->_groupID) {
        if ($ssId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $this->_groupID, 'saved_search_id')) {
          $this->_ssID = $ssId;
        }
      }

      // fix for CRM-1907
      if (isset($this->_ssID) && $this->_context != 'smog') {
        // we only retrieve the saved search values if out current values are null
        $this->_formValues = CRM_Contact_BAO_SavedSearch::getFormValues($this->_ssID);

        //fix for CRM-1505
        if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $this->_ssID, 'mapping_id')) {
          $this->_params = CRM_Contact_BAO_SavedSearch::getSearchParams($this->_ssID);
        }
        else {
          $this->_params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
        }
        $this->_returnProperties = &$this->returnProperties();
      }
      else {
        if (isset($this->_ufGroupID)) {
          // also set the uf group id if not already present
          $this->_formValues['uf_group_id'] = $this->_ufGroupID;
        }
        if (isset($this->_componentMode)) {
          $this->_formValues['component_mode'] = $this->_componentMode;
        }
        if (isset($this->_operator)) {
          $this->_formValues['operator'] = $this->_operator;
        }

        // FIXME: we should generalise in a way that components could inject url-filters
        // just like they build their own form elements
        foreach (array(
                   'mailing_id',
                   'mailing_delivery_status',
                   'mailing_open_status',
                   'mailing_click_status',
                   'mailing_reply_status',
                   'mailing_optout',
                   'mailing_forward',
                   'mailing_unsubscribe',
                   'mailing_date_low',
                   'mailing_date_high',
                 ) as $mailingFilter) {
          $type = 'String';
          if ($mailingFilter == 'mailing_id' &&
            $filterVal = CRM_Utils_Request::retrieve('mailing_id', 'Positive', $this)
          ) {
            $this->_formValues[$mailingFilter] = array($filterVal);
          }
          elseif ($filterVal = CRM_Utils_Request::retrieve($mailingFilter, $type, $this)) {
            $this->_formValues[$mailingFilter] = $filterVal;
          }
          if ($filterVal) {
            $this->_openedPanes['Mailings'] = 1;
            $this->_formValues['hidden_CiviMail'] = 1;
          }
        }
      }
    }
    $this->assign('id',
      CRM_Utils_Array::value('uf_group_id', $this->_formValues)
    );
    $operator = CRM_Utils_Array::value('operator', $this->_formValues, 'AND');
    $this->set('queryOperator', $operator);
    if ($operator == 'OR') {
      $this->assign('operator', ts('OR'));
    }
    else {
      $this->assign('operator', ts('AND'));
    }

    // show the context menu only when we’re not searching for deleted contacts; CRM-5673
    if (empty($this->_formValues['deleted_contacts'])) {
      $menuItems = CRM_Contact_BAO_Contact::contextMenu();
      $primaryActions = CRM_Utils_Array::value('primaryActions', $menuItems, array());
      $this->_contextMenu = CRM_Utils_Array::value('moreActions', $menuItems, array());
      $this->assign('contextMenu', $primaryActions + $this->_contextMenu);
    }

    if (!isset($this->_componentMode)) {
      $this->_componentMode = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
    self::setModeValues();

    self::$_selectorName = $this->_modeValue['selectorName'];

    $setDynamic = FALSE;
    if (strpos(self::$_selectorName, 'CRM_Contact_Selector') !== FALSE) {
      $selector = new self::$_selectorName(
        $this->_customSearchClass,
        $this->_formValues,
        $this->_params,
        $this->_returnProperties,
        $this->_action,
        FALSE, TRUE,
        $this->_context,
        $this->_contextMenu
      );
      $setDynamic = TRUE;
    }
    else {
      $selector = new self::$_selectorName(
        $this->_params,
        $this->_action,
        NULL, FALSE, NULL,
        "search", "advanced"
      );
    }

    $selector->setKey($this->controller->_key);

    $controller = new CRM_Contact_Selector_Controller($selector,
      $this->get(CRM_Utils_Pager::PAGE_ID),
      $this->get(CRM_Utils_Sort::SORT_ID),
      CRM_Core_Action::VIEW,
      $this,
      CRM_Core_Selector_Controller::TRANSFER
    );
    $controller->setEmbedded(TRUE);
    $controller->setDynamicAction($setDynamic);

    if ($this->_force) {

      $this->postProcess();

      /*
       * Note that we repeat this, since the search creates and stores
       * values that potentially change the controller behavior. i.e. things
       * like totalCount etc
       */
      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
          $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $sortID,
        CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::TRANSFER
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
    }

    $controller->moveFromSessionToTemplate();
  }

  /**
   * @return array
   */
  public function &getFormValues() {
    return $this->_formValues;
  }

  /**
   * Common post processing.
   */
  public function postProcess() {
    /*
     * sometime we do a postProcess early on, so we dont need to repeat it
     * this will most likely introduce some more bugs :(
     */

    if ($this->_done) {
      return;
    }
    $this->_done = TRUE;

    //for prev/next pagination
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

    if (array_key_exists($this->_searchButtonName, $_POST) ||
      ($this->_force && !$crmPID)
    ) {
      //reset the cache table for new search
      $cacheKey = "civicrm search {$this->controller->_key}";
      CRM_Core_BAO_PrevNextCache::deleteItem(NULL, $cacheKey);
    }

    //get the button name
    $buttonName = $this->controller->getButtonName();

    if (isset($this->_ufGroupID) && empty($this->_formValues['uf_group_id'])) {
      $this->_formValues['uf_group_id'] = $this->_ufGroupID;
    }

    if (isset($this->_componentMode) && empty($this->_formValues['component_mode'])) {
      $this->_formValues['component_mode'] = $this->_componentMode;
    }

    if (isset($this->_operator) && empty($this->_formValues['operator'])) {
      $this->_formValues['operator'] = $this->_operator;
    }

    if (empty($this->_formValues['qfKey'])) {
      $this->_formValues['qfKey'] = $this->controller->_key;
    }

    if (!CRM_Core_Permission::check('access deleted contacts')) {
      unset($this->_formValues['deleted_contacts']);
    }

    $this->set('type', $this->_action);
    $this->set('formValues', $this->_formValues);
    $this->set('queryParams', $this->_params);
    $this->set('returnProperties', $this->_returnProperties);

    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page
      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }
    else {
      $output = CRM_Core_Selector_Controller::SESSION;

      // create the selector, controller and run - store results in session
      $searchChildGroups = TRUE;
      if ($this->get('isAdvanced')) {
        $searchChildGroups = FALSE;
      }

      $setDynamic = FALSE;

      if (strpos(self::$_selectorName, 'CRM_Contact_Selector') !== FALSE) {
        $selector = new self::$_selectorName(
          $this->_customSearchClass,
          $this->_formValues,
          $this->_params,
          $this->_returnProperties,
          $this->_action,
          FALSE,
          $searchChildGroups,
          $this->_context,
          $this->_contextMenu
        );
        $setDynamic = TRUE;
      }
      else {
        $selector = new self::$_selectorName(
          $this->_params,
          $this->_action,
          NULL,
          FALSE,
          NULL,
          "search",
          "advanced"
        );
      }

      $selector->setKey($this->controller->_key);

      // added the sorting  character to the form array
      $config = CRM_Core_Config::singleton();
      // do this only for contact search
      if ($setDynamic && $config->includeAlphabeticalPager) {
        // Don't recompute if we are just paging/sorting
        if ($this->_reset || (empty($_GET['crmPID']) && empty($_GET['crmSID']) && !$this->_sortByCharacter)) {
          $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($selector, $this->_sortByCharacter);
          $this->set('AToZBar', $aToZBar);
        }
      }

      $sortID = NULL;
      if ($this->get(CRM_Utils_Sort::SORT_ID)) {
        $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
          $this->get(CRM_Utils_Sort::SORT_DIRECTION)
        );
      }
      $controller = new CRM_Contact_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $sortID,
        CRM_Core_Action::VIEW,
        $this,
        $output
      );
      $controller->setEmbedded(TRUE);
      $controller->setDynamicAction($setDynamic);
      $controller->run();
    }
  }

  /**
   * @return NULL
   */
  public function &returnProperties() {
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Search');
  }

}
