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
class CRM_Campaign_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

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
    'sort_name',
    'street_unit',
    'street_name',
    'street_number',
    'street_address',
    'city',
    'postal_code',
    'state_province',
    'country',
    'email',
    'phone',
    'campaign_id',
    'survey_activity_id',
    'survey_activity_target_id',
    'survey_activity_target_contact_id',
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
  protected $_surveyClause = NULL;

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
   * @param string $surveyClause
   *   If the caller wants to further restrict the search.
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many voters do we want returned.
   *
   * @param string $context
   *
   * @return \CRM_Campaign_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $surveyClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single = $single;
    $this->_limit = $limit;
    $this->_context = $context;

    $this->_campaignClause = $surveyClause;
    $this->_campaignFromClause = $surveyClause['fromClause'] ?? NULL;
    $this->_campaignWhereClause = $surveyClause['whereClause'] ?? NULL;

    // type of selector
    $this->_action = $action;

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams,
      NULL, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CAMPAIGN,
      TRUE
    );
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   */
  public static function &links() {
    return self::$_links = [];
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['csvString'] = NULL;
    $params['status'] = ts('Respondents') . ' %%StatusMessage%%';
    $params['rowCount'] = ($this->_limit) ? $this->_limit : Civi::settings()->get('default_pager_size');
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
      FALSE, FALSE, FALSE,
      $this->_campaignWhereClause,
      NULL,
      $this->_campaignFromClause
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
      FALSE, $this->_campaignWhereClause,
      NULL,
      $this->_campaignFromClause
    );

    // process the result of the query
    $rows = [];

    while ($result->fetch()) {
      $this->_query->convertToPseudoNames($result);
      $row = [];
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (property_exists($result, $property)) {
          $row[$property] = $result->$property;
        }
      }
      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->contact_id;
      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_type, FALSE, $result->contact_id);

      $rows[] = $row;
    }
    $this->buildPrevNextCache($sort);

    return $rows;
  }

  /**
   * @param $sort
   */
  public function buildPrevNextCache($sort) {
    //for prev/next pagination
    $crmPID = CRM_Utils_Request::retrieve('crmPID', 'Integer');

    if (!$crmPID) {
      $cacheKey = "civicrm search {$this->_key}";
      Civi::service('prevnext')->deleteItem(NULL, $cacheKey, 'civicrm_contact');

      $sql = $this->_query->getSearchSQLParts(0, 0, $sort,
        FALSE, FALSE,
        FALSE, FALSE,
        $this->_campaignWhereClause,
        NULL,
        $this->_campaignFromClause
      );

      $selectSQL = "
      SELECT %1, contact_a.id, contact_a.display_name
{$sql['from']}
";

      try {
        Civi::service('prevnext')->fillWithSql($cacheKey, $selectSQL, [1 => [$cacheKey, 'String']]);
      }
      catch (CRM_Core_Exception $e) {
        // Heavy handed, no? Seems like this merits an explanation.
        return;
      }

      if (Civi::service('prevnext') instanceof CRM_Core_PrevNextCache_Sql) {
        // SQL-backed prevnext cache uses an extra record for pruning the cache.
        // Also ensure that caches stay alive for 2 days a per previous code.
        Civi::cache('prevNextCache')->set($cacheKey, $cacheKey, 60 * 60 * 24 * CRM_Core_PrevNextCache_Sql::cacheDays);
      }
    }
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
    self::$_columnHeaders = [];

    if (!$this->_single) {
      $contactDetails = [
        [
          'name' => ts('Contact Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ],
        [
          'name' => ts('Street Number'),
          'sort' => 'street_number',
        ],
        [
          'name' => ts('Street Name'),
          'sort' => 'street_name',
        ],
        ['name' => ts('Street Address')],
        [
          'name' => ts('City'),
          'sort' => 'city',
        ],
        [
          'name' => ts('Postal Code'),
          'sort' => 'postal_code',
        ],
        [
          'name' => ts('State'),
          'sort' => 'state_province_name',
        ],
        ['name' => ts('Country')],
        ['name' => ts('Email')],
        ['name' => ts('Phone')],
      ];
      self::$_columnHeaders = array_merge($contactDetails, self::$_columnHeaders);
    }

    return self::$_columnHeaders;
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
    return ts('CiviCRM Respondent Search');
  }

}
