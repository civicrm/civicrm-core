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
 * This class is used to retrieve and display a range of contacts that match the given criteria.
 */
class CRM_Case_Selector_Search extends CRM_Core_Selector_Base {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  public static $_links;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  public static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   */
  public static $_properties = [
    'contact_id',
    'contact_type',
    'sort_name',
    'display_name',
    'case_id',
    'case_subject',
    'case_status_id',
    'case_status',
    'case_type_id',
    'case_type',
    'case_role',
    'phone',
  ];

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var bool
   */
  protected $_limit = NULL;

  /**
   * What context are we being invoked from
   *
   * @var string
   */
  protected $_context = NULL;

  /**
   * QueryParams is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_queryParams;

  /**
   * Represent the type of selector
   *
   * @var int
   */
  protected $_action;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_additionalClause;

  /**
   * The query object
   *
   * @var CRM_Contact_BAO_Query
   */
  protected $_query;

  /**
   * Class constructor.
   *
   * @param array $queryParams
   *   Array of parameters for query.
   * @param int $action - action of search basic or advanced.
   * @param string $additionalClause
   *   If the caller wants to further restrict the search (used in participations).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many signers do we want returned.
   *
   * @param string $context
   *
   * @return \CRM_Case_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $additionalClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single = $single;
    $this->_limit = $limit;
    $this->_context = $context;

    $this->_additionalClause = $additionalClause;

    // type of selector
    $this->_action = $action;

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams,
      CRM_Case_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CASE,
        FALSE
      ),
      NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CASE
    );

    $this->_query->_distinctComponentClause = " civicrm_case.id ";
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_case.id ";
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param bool $isDeleted
   * @param string|null $key
   *
   * @return array
   */
  public static function &links($isDeleted = FALSE, $key = NULL) {
    $extraParams = ($key) ? "&key={$key}" : NULL;

    if ($isDeleted) {
      self::$_links = [
        CRM_Core_Action::RENEW => [
          'name' => ts('Restore'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&action=renew&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'restore-case',
          'title' => ts('Restore Case'),
          'weight' => -30,
        ],
      ];
    }
    else {
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('Manage'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=case' . $extraParams,
          'ref' => 'manage-case',
          'class' => 'no-popup',
          'title' => ts('Manage Case'),
          'weight' => -20,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'delete-case',
          'title' => ts('Delete Case'),
          'weight' => -10,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Assign to Another Client'),
          'url' => 'civicrm/contact/view/case/editClient',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'reassign',
          'class' => 'medium-popup',
          'title' => ts('Assign to Another Client'),
          'weight' => -10,
        ],
      ];
    }

    $actionLinks = [];
    foreach (self::$_links as $index => $value) {
      $actionLinks['primaryActions'][$index] = $value;
    }

    return $actionLinks;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Case') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    if ($this->_limit) {
      $params['rowCount'] = $this->_limit;
    }
    else {
      $params['rowCount'] = Civi::settings()->get('default_pager_size');
    }

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * Returns total number of rows for the query.
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount() {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_additionalClause
    );
  }

  /**
   * Returns all the rows in the given offset and rowCount.
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return int
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_additionalClause
    );
    // process the result of the query
    $rows = [];

    //CRM-4418 check for view, edit, delete
    $permissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('access all cases and activities')
      || CRM_Core_Permission::check('access my cases and activities')
    ) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviCase')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    $caseStatus = CRM_Core_OptionGroup::values('case_status', FALSE, FALSE, FALSE, " AND v.name = 'Urgent' ");

    $scheduledInfo = [];

    while ($result->fetch()) {
      $row = [];
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        $row[$property] = $result->$property ?? NULL;
      }

      $isDeleted = FALSE;
      if ($result->case_deleted) {
        $isDeleted = TRUE;
        $row['case_status_id'] = empty($row['case_status_id']) ? "" : $row['case_status_id'] . '<br />' . ts('(deleted)');
      }

      $scheduledInfo['case_id'][] = $result->case_id;
      $scheduledInfo['contact_id'][] = $result->contact_id;
      $scheduledInfo['case_deleted'] = $result->case_deleted;
      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->case_id;

      $links = self::links($isDeleted, $this->_key);
      $row['action'] = CRM_Core_Action::formLink($links['primaryActions'],
        $mask, [
          'id' => $result->case_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
        ],
        ts('more'),
        FALSE,
        'case.selector.actions',
        'Case',
        $result->case_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?: $result->contact_type
      );

      //adding case manager to case selector.CRM-4510.
      $caseType = CRM_Case_BAO_Case::getCaseType($result->case_id, 'name');
      $row['casemanager'] = CRM_Case_BAO_Case::getCaseManagerContact($caseType, $result->case_id);

      if (isset($result->case_status_id) &&
        array_key_exists($result->case_status_id, $caseStatus)
      ) {
        $row['class'] = "status-urgent";
      }
      else {
        $row['class'] = "status-normal";
      }

      $rows[$result->case_id] = $row;
    }

    //retrieve the scheduled & recent Activity type and date for selector
    if (!empty($scheduledInfo)) {
      $scheduledActivity = CRM_Case_BAO_Case::getNextScheduledActivity($scheduledInfo, 'upcoming');
      foreach ($rows as $key => $row) {
        $rows[$key]['case_scheduled_activity_date'] = $scheduledActivity[$key]['date'] ?? NULL;
        $rows[$key]['case_scheduled_activity_type'] = $scheduledActivity[$key]['type'] ?? NULL;
      }
      $recentActivity = CRM_Case_BAO_Case::getNextScheduledActivity($scheduledInfo, 'recent');
      foreach ($rows as $key => $row) {
        $rows[$key]['case_recent_activity_date'] = $recentActivity[$key]['date'] ?? NULL;
        $rows[$key]['case_recent_activity_type'] = $recentActivity[$key]['type'] ?? NULL;
      }
    }
    return $rows;
  }

  /**
   * @inheritDoc
   */
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = [
        [
          'name' => ts('Subject'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Status'),
          'sort' => 'case_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Case Type'),
          'sort' => 'case_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('My Role'),
          'sort' => 'case_role',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Case Manager'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Most Recent'),
          // @fixme: Triggers DB error field not found on "Find Cases": 'sort' => 'case_recent_activity_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Next Sched.'),
          // @fixme: Triggers DB error field not found on "Find Cases": 'sort' => 'case_scheduled_activity_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        ['name' => ts('Actions')],
      ];

      if (!$this->_single) {
        $pre = [
          [
            'name' => ts('Client'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::ASCENDING,
          ],
        ];

        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
    }
    return self::$_columnHeaders;
  }

  /**
   * @return mixed
   */
  public function alphabetQuery() {
    return $this->_query->alphabetQuery();
  }

  /**
   * @return CRM_Contact_BAO_Query
   */
  public function &getQuery() {
    return $this->_query;
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('Case Search');
  }

  /**
   * Add the set of "actionLinks" to the case activity
   *
   * @param int $caseID
   * @param int $contactID
   * @param int $userID
   * @param string $context
   * @param \CRM_Activity_BAO_Activity $dao
   * @param bool $allowView
   *
   * @return string $linksMarkup
   */
  public static function addCaseActivityLinks($caseID, $contactID, $userID, $context, $dao, $allowView = TRUE) {
    $caseDeleted = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'is_deleted');
    $actionLinks = self::actionLinks();
    // Check logged in user for permission.
    if (CRM_Case_BAO_Case::checkPermission($dao->id, 'view', $dao->activity_type_id, $userID)) {
      $permissions[] = CRM_Core_Permission::VIEW;
    }
    if (!$allowView) {
      unset($actionLinks[CRM_Core_Action::VIEW]);
    }
    if (!$dao->deleted) {
      // Activity is not deleted, allow user to edit/delete if they have permission
      // hide Edit link if:
      // 1. User does not have edit permission.
      // 2. Activity type is NOT editable (special case activities).CRM-5871
      if (CRM_Case_BAO_Case::checkPermission($dao->id, 'edit', $dao->activity_type_id, $userID)) {
        $permissions[] = CRM_Core_Permission::EDIT;
      }
      if (in_array($dao->activity_type_id, CRM_Activity_BAO_Activity::getViewOnlyActivityTypeIDs())) {
        unset($actionLinks[CRM_Core_Action::UPDATE]);
      }
      if (CRM_Case_BAO_Case::checkPermission($dao->id, 'delete', $dao->activity_type_id, $userID)) {
        $permissions[] = CRM_Core_Permission::DELETE;
      }
      unset($actionLinks[CRM_Core_Action::RENEW]);
    }
    $extraMask = 0;
    if ($dao->deleted && !$caseDeleted
      && (CRM_Case_BAO_Case::checkPermission($dao->id, 'delete', $dao->activity_type_id, $userID))) {
      // Case is not deleted but activity is.
      // Allow user to restore activity if they have delete permissions
      unset($actionLinks[CRM_Core_Action::DELETE]);
      $extraMask = CRM_Core_Action::RENEW;
    }
    if (!CRM_Case_BAO_Case::checkPermission($dao->id, 'Move To Case', $dao->activity_type_id)) {
      unset($actionLinks[CRM_Core_Action::DETACH]);
    }
    if (!CRM_Case_BAO_Case::checkPermission($dao->id, 'Copy To Case', $dao->activity_type_id)) {
      unset($actionLinks[CRM_Core_Action::COPY]);
    }
    $actionMask = CRM_Core_Action::mask($permissions) | $extraMask;
    $values = [
      'aid' => $dao->id,
      'cid' => $contactID,
      'cxt' => empty($context) ? '' : "&context={$context}",
      'caseid' => $caseID,
    ];
    $linksMarkup = CRM_Core_Action::formLink($actionLinks,
      $actionMask,
      $values,
      ts('more'),
      FALSE,
      'case.tab.row',
      'Activity',
      $dao->id
    );
    // if there are file attachments we will return how many and, if only one, add a link to it
    if (!empty($dao->attachment_ids)) {
      $linksMarkup .= implode(' ', CRM_Core_BAO_File::paperIconAttachment('civicrm_activity', $dao->id));
    }
    return $linksMarkup;
  }

  /**
   * @param int $caseID
   * @param int $contactID
   * @param int $userID
   * @param string $context
   * @param int $activityTypeID
   * @param int $activityDeleted
   * @param int $activityID
   * @param bool $allowView
   *
   * @return array|null
   */
  public static function permissionedActionLinks($caseID, $contactID, $userID, $context, $activityTypeID, $activityDeleted, $activityID, $allowView = TRUE) {
    $caseDeleted = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'is_deleted');
    $values = [
      'aid' => $activityID,
      'cid' => $contactID,
      'cxt' => empty($context) ? '' : "&context={$context}",
      'caseid' => $caseID,
    ];
    $actionLinks = self::actionLinks();

    // Check logged in user for permission.
    if (CRM_Case_BAO_Case::checkPermission($activityID, 'view', $activityTypeID, $userID)) {
      $permissions[] = CRM_Core_Permission::VIEW;
    }
    if (!$allowView) {
      unset($actionLinks[CRM_Core_Action::VIEW]);
    }
    if (!$activityDeleted) {
      // Activity is not deleted, allow user to edit/delete if they have permission

      // hide Edit link if:
      // 1. User does not have edit permission.
      // 2. Activity type is NOT editable (special case activities).CRM-5871
      if (CRM_Case_BAO_Case::checkPermission($activityID, 'edit', $activityTypeID, $userID)) {
        $permissions[] = CRM_Core_Permission::EDIT;
      }
      if (in_array($activityTypeID, CRM_Activity_BAO_Activity::getViewOnlyActivityTypeIDs())) {
        unset($actionLinks[CRM_Core_Action::UPDATE]);
      }
      if (CRM_Case_BAO_Case::checkPermission($activityID, 'delete', $activityTypeID, $userID)) {
        $permissions[] = CRM_Core_Permission::DELETE;
      }
      unset($actionLinks[CRM_Core_Action::RENEW]);
    }
    $extraMask = 0;
    if ($activityDeleted && !$caseDeleted
      && (CRM_Case_BAO_Case::checkPermission($activityID, 'delete', $activityTypeID, $userID))) {
      // Case is not deleted but activity is.
      // Allow user to restore activity if they have delete permissions
      unset($actionLinks[CRM_Core_Action::DELETE]);
      $extraMask = CRM_Core_Action::RENEW;
    }
    if (!CRM_Case_BAO_Case::checkPermission($activityID, 'Move To Case', $activityTypeID)) {
      unset($actionLinks[CRM_Core_Action::DETACH]);
    }
    if (!CRM_Case_BAO_Case::checkPermission($activityID, 'Copy To Case', $activityTypeID)) {
      unset($actionLinks[CRM_Core_Action::COPY]);
    }

    $actionMask = CRM_Core_Action::mask($permissions) | $extraMask;
    return CRM_Core_Action::filterLinks($actionLinks, $actionMask, $values, 'case.activity', 'Activity', $activityID);
  }

  /**
   * Get the action links for this page.
   *
   * @return array
   */
  public static function actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      self::$_actionLinks = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/case/activity/view',
          'qs' => 'reset=1&cid=%%cid%%&caseid=%%caseid%%&aid=%%aid%%',
          'title' => ts('View'),
          'accessKey' => '',
          'ref' => 'View',
          'weight' => -20,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/case/activity',
          'qs' => 'reset=1&cid=%%cid%%&caseid=%%caseid%%&id=%%aid%%&action=update%%cxt%%',
          'title' => ts('Edit'),
          'icon' => 'fa-pencil',
          'accessKey' => '',
          'ref' => 'Edit',
          'weight' => -10,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/case/activity',
          'qs' => 'reset=1&cid=%%cid%%&caseid=%%caseid%%&id=%%aid%%&action=delete%%cxt%%',
          'title' => ts('Delete'),
          'icon' => 'fa-trash',
          'accessKey' => '',
          'ref' => 'Delete',
          'weight' => 100,
        ],
        CRM_Core_Action::RENEW => [
          'name' => ts('Restore'),
          'url' => 'civicrm/case/activity',
          'qs' => 'reset=1&cid=%%cid%%&caseid=%%caseid%%&id=%%aid%%&action=renew%%cxt%%',
          'title' => ts('Restore'),
          'icon' => 'fa-undo',
          'accessKey' => '',
          'ref' => 'Restore',
          'weight' => 90,
        ],
        CRM_Core_Action::DETACH => [
          'name' => ts('Move To Case'),
          'ref' => 'move_to_case_action',
          'title' => ts('Move To Case'),
          'extra' => 'onclick = "Javascript:fileOnCase( \'move\', %%aid%%, %%caseid%%, this ); return false;"',
          'icon' => 'fa-clipboard',
          'accessKey' => '',
          'weight' => 60,
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy To Case'),
          'ref' => 'copy_to_case_action',
          'title' => ts('Copy To Case'),
          'extra' => 'onclick = "Javascript:fileOnCase( \'copy\', %%aid%%, %%caseid%%, this ); return false;"',
          'icon' => 'fa-files-o',
          'accessKey' => '',
          'weight' => 70,
        ],
      ];
    }
    return self::$_actionLinks;
  }

}
