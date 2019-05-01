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
 * This class is used to retrieve and display a range of  contacts that match the given criteria.
 *
 * Specifically for results of advanced search options.
 */
class CRM_Activity_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  public static $_links = NULL;

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
    'contact_sub_type',
    'sort_name',
    'display_name',
    'activity_id',
    'activity_date_time',
    'activity_status_id',
    'activity_status',
    'activity_subject',
    'source_record_id',
    'activity_type_id',
    'activity_type',
    'activity_is_test',
    'activity_campaign_id',
    'activity_engagement_level',
  ];

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
   * What context are we being invoked from.
   *
   * @var string
   */
  protected $_context = NULL;

  /**
   * What component context are we being invoked from.
   *
   * @var string
   */
  protected $_compContext = NULL;

  /**
   * QueryParams is the array returned by exportValues called on.
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_queryParams;

  /**
   * Represent the type of selector.
   *
   * @var int
   */
  protected $_action;

  /**
   * The additional clause that we restrict the search with.
   *
   * @var string
   */
  protected $_activityClause = NULL;

  /**
   * The query object.
   *
   * @var string
   */
  protected $_query;

  /**
   * Class constructor.
   *
   * @param array $queryParams
   *   Array of parameters for query.
   * @param \const|int $action - action of search basic or advanced.
   * @param string $activityClause
   *   If the caller wants to further restrict the search (used in activities).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many activities do we want returned.
   *
   * @param string $context
   * @param null $compContext
   *
   * @return \CRM_Activity_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $activityClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search',
    $compContext = NULL
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single = $single;
    $this->_limit = $limit;
    $this->_context = $context;
    $this->_compContext = $compContext;

    $this->_activityClause = $activityClause;

    // CRM-12675
    $components = CRM_Core_Component::getNames();
    $componentClause = [];
    foreach ($components as $componentID => $componentName) {
      // CRM-19201: Add support for searching CiviCampaign and CiviCase
      // activities. For CiviCase, "access all cases and activities" is
      // required here rather than "access my cases and activities" to
      // prevent those with only the later permission from seeing a list
      // of all cases which might present a privacy issue.
      if (!CRM_Core_Permission::access($componentName, TRUE, TRUE)) {
        $componentClause[] = " (activity_type.component_id IS NULL OR activity_type.component_id <> {$componentID}) ";
      }
    }

    if (!empty($componentClause)) {
      $componentRestriction = implode(' AND ', $componentClause);
      if (empty($this->_activityClause)) {
        $this->_activityClause = $componentRestriction;
      }
      else {
        $this->_activityClause .= ' AND ' . $componentRestriction;
      }
    }

    // type of selector
    $this->_action = $action;
    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams,
      CRM_Activity_BAO_Query::selectorReturnProperties(),
      NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_ACTIVITY
    );
    $this->_query->_distinctComponentClause = '( civicrm_activity.id )';
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_activity.id ";
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Activities %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param string $action
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_activityClause
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
   * @return array
   *   rows in the given offset and rowCount
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $result = $this->_query->searchQuery(
      $offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_activityClause
    );
    $rows = [];
    $mailingIDs = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    $accessCiviMail = CRM_Core_Permission::check('access CiviMail');

    // Get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    $engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $bulkActivityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Bulk Email');

    while ($result->fetch()) {
      $row = [];

      // Ignore rows where we dont have an activity id.
      if (empty($result->activity_id)) {
        continue;
      }
      $this->_query->convertToPseudoNames($result);

      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (isset($result->$property)) {
          $row[$property] = $result->$property;
        }
      }

      $contactId = CRM_Utils_Array::value('contact_id', $row);
      if (!$contactId) {
        $contactId = CRM_Utils_Array::value('source_contact_id', $row);
      }

      $row['target_contact_name'] = CRM_Activity_BAO_ActivityContact::getNames($row['activity_id'], $targetID);
      $row['assignee_contact_name'] = CRM_Activity_BAO_ActivityContact::getNames($row['activity_id'], $assigneeID);
      list($row['source_contact_name'], $row['source_contact_id']) = CRM_Activity_BAO_ActivityContact::getNames($row['activity_id'], $sourceID, TRUE);
      $row['source_contact_name'] = implode(',', array_values($row['source_contact_name']));
      $row['source_contact_id'] = implode(',', $row['source_contact_id']);

      if ($this->_context == 'search') {
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->activity_id;
      }
      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );
      $accessMailingReport = FALSE;
      $activityTypeId = $row['activity_type_id'];
      if ($row['activity_is_test']) {
        $row['activity_type'] = CRM_Core_TestEntity::appendTestText($row['activity_type']);
      }
      $row['mailingId'] = '';
      if (
        $accessCiviMail &&
        ($mailingIDs === TRUE || in_array($result->source_record_id, $mailingIDs)) &&
        ($bulkActivityTypeID == $activityTypeId)
      ) {
        $row['mailingId'] = CRM_Utils_System::url('civicrm/mailing/report',
          "mid={$result->source_record_id}&reset=1&cid={$contactId}&context=activitySelector"
        );
        $row['recipients'] = ts('(recipients)');
        $row['target_contact_name'] = '';
        $row['assignee_contact_name'] = '';
        $accessMailingReport = TRUE;
      }
      $activityActions = new CRM_Activity_Selector_Activity($result->contact_id, NULL);
      $actionLinks = $activityActions->actionLinks($activityTypeId,
        CRM_Utils_Array::value('source_record_id', $row),
        $accessMailingReport,
        CRM_Utils_Array::value('activity_id', $row),
        $this->_key,
        $this->_compContext
      );
      $row['action'] = CRM_Core_Action::formLink($actionLinks, NULL,
        [
          'id' => $result->activity_id,
          'cid' => $contactId,
          'cxt' => $this->_context,
        ],
        ts('more'),
        FALSE,
        'activity.selector.row',
        'Activity',
        $result->activity_id
      );

      // Carry campaign to selector.
      $row['campaign'] = CRM_Utils_Array::value($result->activity_campaign_id, $allCampaigns);
      $row['campaign_id'] = $result->activity_campaign_id;

      if ($engagementLevel = CRM_Utils_Array::value('activity_engagement_level', $row)) {
        $row['activity_engagement_level'] = CRM_Utils_Array::value($engagementLevel,
          $engagementLevels, $engagementLevel
        );
      }

      // Check if recurring activity.
      $repeat = CRM_Core_BAO_RecurringEntity::getPositionAndCount($row['activity_id'], 'civicrm_activity');
      $row['repeat'] = '';
      if ($repeat) {
        $row['repeat'] = ts('Repeating (%1 of %2)', [1 => $repeat[0], 2 => $repeat[1]]);
      }
      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * @return array
   *   which contains an array of strings
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
          'name' => ts('Type'),
          'sort' => 'activity_type_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Subject'),
          'sort' => 'activity_subject',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Added By'),
          'sort' => 'source_contact',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        ['name' => ts('With')],
        ['name' => ts('Assigned')],
        [
          'name' => ts('Date'),
          'sort' => 'activity_date_time',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ],
        [
          'name' => ts('Status'),
          'sort' => 'activity_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'desc' => ts('Actions'),
        ],
      ];
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
   * @return string
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
    return ts('CiviCRM Activity Search');
  }

}
