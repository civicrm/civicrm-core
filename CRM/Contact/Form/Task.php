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
 * This class generates form components for search-result tasks.
 */
class CRM_Contact_Form_Task extends CRM_Core_Form_Task {

  /**
   * The task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The array that holds all the contact ids
   *
   * @var array
   */
  public $_contactIds;

  /**
   * The array that holds all the contact types
   *
   * @var array
   */
  public $_contactTypes;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The name of the temp table where we store the contact IDs
   *
   * @var string
   */
  protected $_componentTable = NULL;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * This includes the submitted values of the search form
   */
  static protected $_searchFormValues;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * Common pre-processing function.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommon(&$form) {
    $form->_contactIds = [];
    $form->_contactTypes = [];

    $useTable = (CRM_Utils_System::getClassName($form->controller->getStateMachine()) == 'CRM_Export_StateMachine_Standalone');

    $isStandAlone = in_array('task', $form->urlPath) || in_array('standalone', $form->urlPath);
    if ($isStandAlone) {
      list($form->_task, $title) = CRM_Contact_Task::getTaskAndTitleByClass(get_class($form));
      if (!array_key_exists($form->_task, CRM_Contact_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission()))) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      $form->_contactIds = explode(',', CRM_Utils_Request::retrieve('cids', 'CommaSeparatedIntegers', $form, TRUE));
      if (empty($form->_contactIds)) {
        CRM_Core_Error::statusBounce(ts('No Contacts Selected'));
      }
      $form->setTitle($title);
    }

    // get the submitted values of the search form
    // we'll need to get fv from either search or adv search in the future
    $fragment = 'search';
    if ($form->_action == CRM_Core_Action::ADVANCED) {
      self::$_searchFormValues = $form->controller->exportValues('Advanced');
      $fragment .= '/advanced';
    }
    elseif ($form->_action == CRM_Core_Action::PROFILE) {
      self::$_searchFormValues = $form->controller->exportValues('Builder');
      $fragment .= '/builder';
    }
    elseif ($form->_action == CRM_Core_Action::COPY) {
      self::$_searchFormValues = $form->controller->exportValues('Custom');
      $fragment .= '/custom';
    }
    elseif (!$isStandAlone) {
      self::$_searchFormValues = $form->controller->exportValues('Basic');
    }

    //set the user context for redirection of task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $cacheKey = "civicrm search {$qfKey}";

    $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);

    $form->_task = CRM_Utils_Array::value('task', self::$_searchFormValues);
    $crmContactTaskTasks = CRM_Contact_Task::taskTitles();
    $form->assign('taskName', CRM_Utils_Array::value($form->_task, $crmContactTaskTasks));

    if ($useTable) {
      $tempTable = CRM_Utils_SQL_TempTable::build()->setCategory('tskact')->setDurable()->setId($qfKey)->setUtf8();
      $form->_componentTable = $tempTable->getName();
      $tempTable->drop();
      $tempTable->createWithColumns('contact_id int primary key');
    }

    // all contacts or action = save a search
    if ((CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_all') ||
      ($form->_task == CRM_Contact_Task::SAVE_SEARCH)
    ) {
      // since we don't store all contacts in prevnextcache, when user selects "all" use query to retrieve contacts
      // rather than prevnext cache table for most of the task actions except export where we rebuild query to fetch
      // final result set
      if ($useTable) {
        $allCids = Civi::service('prevnext')->getSelection($cacheKey, "getall");
      }
      else {
        $allCids[$cacheKey] = self::getContactIds($form);
      }

      $form->_contactIds = [];
      if ($useTable) {
        $count = 0;
        $insertString = [];
        foreach ($allCids[$cacheKey] as $cid => $ignore) {
          $count++;
          $insertString[] = " ( {$cid} ) ";
          if ($count % 200 == 0) {
            $string = implode(',', $insertString);
            $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
            CRM_Core_DAO::executeQuery($sql);
            $insertString = [];
          }
        }
        if (!empty($insertString)) {
          $string = implode(',', $insertString);
          $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
          CRM_Core_DAO::executeQuery($sql);
        }
      }
      elseif (empty($form->_contactIds)) {
        // filter duplicates here
        // CRM-7058
        // might be better to do this in the query, but that logic is a bit complex
        // and it decides when to use distinct based on input criteria, which needs
        // to be fixed and optimized.

        foreach ($allCids[$cacheKey] as $cid => $ignore) {
          $form->_contactIds[] = $cid;
        }
      }
    }
    elseif (CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_sel') {
      // selected contacts only
      // need to perform action on only selected contacts
      $insertString = [];

      // refire sql in case of custom search
      if ($form->_action == CRM_Core_Action::COPY) {
        // selected contacts only
        // need to perform action on only selected contacts
        foreach (self::$_searchFormValues as $name => $value) {
          if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
            $contactID = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
            if ($useTable) {
              $insertString[] = " ( {$contactID} ) ";
            }
            else {
              $form->_contactIds[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
            }
          }
        }
      }
      else {
        // fetching selected contact ids of passed cache key
        $selectedCids = Civi::service('prevnext')->getSelection($cacheKey);
        foreach ($selectedCids[$cacheKey] as $selectedCid => $ignore) {
          if ($useTable) {
            $insertString[] = " ( {$selectedCid} ) ";
          }
          else {
            $form->_contactIds[] = $selectedCid;
          }
        }
      }

      if (!empty($insertString)) {
        $string = implode(',', $insertString);
        $sql = "REPLACE INTO {$form->_componentTable} ( contact_id ) VALUES $string";
        CRM_Core_DAO::executeQuery($sql);
      }
    }

    //contact type for pick up profiles as per selected contact types with subtypes
    //CRM-5521
    if ($selectedTypes = CRM_Utils_Array::value('contact_type', self::$_searchFormValues)) {
      if (!is_array($selectedTypes)) {
        $selectedTypes = explode(' ', $selectedTypes);
      }
      foreach ($selectedTypes as $ct => $dontcare) {
        if (strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR) === FALSE) {
          $form->_contactTypes[] = $ct;
        }
        else {
          $separator = strpos($ct, CRM_Core_DAO::VALUE_SEPARATOR);
          $form->_contactTypes[] = substr($ct, $separator + 1);
        }
      }
    }

    if (CRM_Utils_Array::value('radio_ts', self::$_searchFormValues) == 'ts_sel'
      && ($form->_action != CRM_Core_Action::COPY)
    ) {
      $sel = CRM_Utils_Array::value('radio_ts', self::$_searchFormValues);
      $form->assign('searchtype', $sel);
      $result = self::getSelectedContactNames();
      $form->assign("value", $result);
    }

    if (!empty($form->_contactIds)) {
      $form->_componentClause = ' contact_a.id IN ( ' . implode(',', $form->_contactIds) . ' ) ';
      $form->assign('totalSelectedContacts', count($form->_contactIds));

      $form->_componentIds = $form->_contactIds;
    }
  }

  /**
   * Get the contact ids for:
   *   - "Select Records: All xx records"
   *   - custom search (FIXME: does this still apply to custom search?).
   * When we call this function we are not using the prev/next cache
   *
   * @param $form CRM_Core_Form
   *
   * @return array $contactIds
   */
  public static function getContactIds($form) {
    // need to perform action on all contacts
    // fire the query again and get the contact id's + display name
    $sortID = NULL;
    if ($form->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($form->get(CRM_Utils_Sort::SORT_ID),
        $form->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $selectorName = $form->controller->selectorName();

    $fv = $form->get('formValues');
    $customClass = $form->get('customSearchClass');
    $returnProperties = CRM_Core_BAO_Mapping::returnProperties(self::$_searchFormValues);

    $selector = new $selectorName($customClass, $fv, NULL, $returnProperties);

    $params = $form->get('queryParams');

    // fix for CRM-5165
    $sortByCharacter = $form->get('sortByCharacter');
    if ($sortByCharacter && $sortByCharacter != 1) {
      $params[] = ['sortByCharacter', '=', $sortByCharacter, 0, 0];
    }
    $queryOperator = $form->get('queryOperator');
    if (!$queryOperator) {
      $queryOperator = 'AND';
    }
    $dao = $selector->contactIDQuery($params, $sortID,
      CRM_Utils_Array::value('display_relationship_type', $fv),
      $queryOperator
    );

    $contactIds = [];
    while ($dao->fetch()) {
      $contactIds[$dao->contact_id] = $dao->contact_id;
    }

    return $contactIds;
  }


  /**
   * Set default values for the form. Relationship that in edit/view action.
   *
   * The default values are retrieved from the database.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }

  /**
   * Add the rules for form.
   */
  public function addRules() {
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Confirm Action'));
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
  }

  /**
   * Simple shell that derived classes can call to add form buttons.
   *
   * Allows customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
   * @param string $backType
   * @param bool $submitOnce
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons([
        [
          'type' => $nextType,
          'name' => $title,
          'isDefault' => TRUE,
        ],
        [
          'type' => $backType,
          'name' => ts('Cancel'),
          'icon' => 'fa-times',
        ],
      ]
    );
  }

  /**
   * Replace ids of household members in $this->_contactIds with the id of their household.
   *
   * CRM-8338
   */
  public function mergeContactIdsByHousehold() {
    if (empty($this->_contactIds)) {
      return;
    }

    $contactRelationshipTypes = CRM_Contact_BAO_Relationship::getContactRelationshipType(
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      'name',
      FALSE
    );

    // Get Head of Household & Household Member relationships
    $relationKeyMOH = CRM_Utils_Array::key('Household Member of', $contactRelationshipTypes);
    $relationKeyHOH = CRM_Utils_Array::key('Head of Household for', $contactRelationshipTypes);
    $householdRelationshipTypes = [
      $relationKeyMOH => $contactRelationshipTypes[$relationKeyMOH],
      $relationKeyHOH => $contactRelationshipTypes[$relationKeyHOH],
    ];

    $relID = implode(',', $this->_contactIds);

    foreach ($householdRelationshipTypes as $rel => $dnt) {
      list($id, $direction) = explode('_', $rel, 2);
      // identify the relationship direction
      $contactA = 'contact_id_a';
      $contactB = 'contact_id_b';
      if ($direction == 'b_a') {
        $contactA = 'contact_id_b';
        $contactB = 'contact_id_a';
      }

      // Find related households.
      $relationSelect = "SELECT contact_household.id as household_id, {$contactA} as refContact ";
      $relationFrom = " FROM civicrm_contact contact_household
              INNER JOIN civicrm_relationship crel ON crel.{$contactB} = contact_household.id AND crel.relationship_type_id = {$id} ";

      // Check for active relationship status only.
      $today = date('Ymd');
      $relationActive = " AND (crel.is_active = 1 AND ( crel.end_date is NULL OR crel.end_date >= {$today} ) )";
      $relationWhere = " WHERE contact_household.is_deleted = 0  AND crel.{$contactA} IN ( {$relID} ) {$relationActive}";
      $relationGroupBy = " GROUP BY crel.{$contactA}, contact_household.id";
      $relationQueryString = "$relationSelect $relationFrom $relationWhere $relationGroupBy";

      $householdsDAO = CRM_Core_DAO::executeQuery($relationQueryString);
      while ($householdsDAO->fetch()) {
        // Remove contact's id from $this->_contactIds and replace with their household's id.
        foreach (array_keys($this->_contactIds, $householdsDAO->refContact) as $idKey) {
          unset($this->_contactIds[$idKey]);
        }
        if (!in_array($householdsDAO->household_id, $this->_contactIds)) {
          $this->_contactIds[] = $householdsDAO->household_id;
        }
      }
      $householdsDAO->free();
    }

    // If contact list has changed, households will probably be at the end of
    // the list. Sort it again by sort_name.
    if (implode(',', $this->_contactIds) != $relID) {
      $result = civicrm_api3('Contact', 'get', [
        'return' => ['id'],
        'id' => ['IN' => $this->_contactIds],
        'options' => [
          'limit' => 0,
          'sort' => "sort_name",
        ],
      ]);
      $this->_contactIds = array_keys($result['values']);
    }
  }

  /**
   * @return array
   *   List of contact names.
   *   NOTE: These are raw values from the DB. In current data-model, that means
   *   they are pre-encoded HTML.
   */
  private static function getSelectedContactNames() {
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');
    $cacheKey = "civicrm search {$qfKey}";

    $cids = [];
    // Gymanstic time!
    foreach (Civi::service('prevnext')->getSelection($cacheKey) as $cacheKey => $values) {
      $cids = array_unique(array_merge($cids, array_keys($values)));
    }

    $result = CRM_Utils_SQL_Select::from('civicrm_contact')
      ->where('id IN (#cids)', ['cids' => $cids])
      ->execute()
      ->fetchMap('id', 'sort_name');
    return $result;
  }

  /**
   * Given this task's list of targets, produce a hidden group.
   *
   * @return array
   *   Array(0 => int $groupID, 1 => int|NULL $ssID).
   * @throws Exception
   */
  public function createHiddenGroup() {
    // Did the user select "All" matches or cherry-pick a few records?
    $searchParams = $this->controller->exportValues();
    if ($searchParams['radio_ts'] == 'ts_sel') {
      // Create a static group.
      $randID = md5(time() . rand(1, 1000)); // groups require a unique name
      $grpTitle = "Hidden Group {$randID}";
      $grpID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $grpTitle, 'id', 'title');

      if (!$grpID) {
        $groupParams = [
          'title' => $grpTitle,
          'is_active' => 1,
          'is_hidden' => 1,
          'group_type' => ['2' => 1],
        ];

        $group = CRM_Contact_BAO_Group::create($groupParams);
        $grpID = $group->id;

        CRM_Contact_BAO_GroupContact::addContactsToGroup($this->_contactIds, $group->id);

        $newGroupTitle = "Hidden Group {$grpID}";
        $groupParams = [
          'id' => $grpID,
          'name' => CRM_Utils_String::titleToVar($newGroupTitle),
          'title' => $newGroupTitle,
          'group_type' => ['2' => 1],
        ];
        CRM_Contact_BAO_Group::create($groupParams);
      }

      // note at this point its a static group
      return [$grpID, NULL];
    }
    else {
      // Create a smart group.
      $ssId = $this->get('ssID');
      $hiddenSmartParams = [
        'group_type' => ['2' => 1],
        // queryParams have been preprocessed esp WRT any entity reference fields - see +
        // https://github.com/civicrm/civicrm-core/pull/13250
        'form_values' => $this->get('queryParams'),
        'saved_search_id' => $ssId,
        'search_custom_id' => $this->get('customSearchID'),
        'search_context' => $this->get('context'),
      ];

      list($smartGroupId, $savedSearchId) = CRM_Contact_BAO_Group::createHiddenSmartGroup($hiddenSmartParams);
      return [$smartGroupId, $savedSearchId];
    }

  }

}
