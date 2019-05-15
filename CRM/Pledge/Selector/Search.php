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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 */
class CRM_Pledge_Selector_Search extends CRM_Core_Selector_Base {

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
   *
   * @var array
   */
  public static $_properties = [
    'contact_id',
    'sort_name',
    'display_name',
    'pledge_id',
    'pledge_amount',
    'pledge_create_date',
    'pledge_total_paid',
    'pledge_next_pay_date',
    'pledge_next_pay_amount',
    'pledge_outstanding_amount',
    'pledge_status_id',
    'pledge_status',
    'pledge_is_test',
    'pledge_contribution_page_id',
    'pledge_financial_type',
    'pledge_campaign_id',
    'pledge_currency',
  ];

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact
   *
   * @var boolean
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
  protected $_additionalClause = NULL;

  /**
   * The query object
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
   * @param string $additionalClause
   *   If the caller wants to further restrict the search (used in participations).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many signers do we want returned.
   *
   * @param string $context
   *
   * @return \CRM_Pledge_Selector_Search
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

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams, NULL, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_PLEDGE
    );

    $this->_query->_distinctComponentClause = "civicrm_pledge.id";
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_pledge.id ";
  }

  /**
   * This method returns the links that are given for each search row.
   *
   * Currently the links added for each row are:
   * - View
   * - Edit
   *
   * @return array
   */
  public static function &links() {
    $args = func_get_args();
    $hideOption = CRM_Utils_Array::value(0, $args);
    $key = CRM_Utils_Array::value(1, $args);

    $extraParams = ($key) ? "&key={$key}" : NULL;

    $cancelExtra = ts('Cancelling this pledge will also cancel any scheduled (and not completed) pledge payments.') . ' ' . ts('This action cannot be undone.') . ' ' . ts('Do you want to continue?');
    self::$_links = [
      CRM_Core_Action::VIEW => [
        'name' => ts('View'),
        'url' => 'civicrm/contact/view/pledge',
        'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=pledge' . $extraParams,
        'title' => ts('View Pledge'),
      ],
      CRM_Core_Action::UPDATE => [
        'name' => ts('Edit'),
        'url' => 'civicrm/contact/view/pledge',
        'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
        'title' => ts('Edit Pledge'),
      ],
      CRM_Core_Action::DETACH => [
        'name' => ts('Cancel'),
        'url' => 'civicrm/contact/view/pledge',
        'qs' => 'reset=1&action=detach&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
        'extra' => 'onclick = "return confirm(\'' . $cancelExtra . '\');"',
        'title' => ts('Cancel Pledge'),
      ],
      CRM_Core_Action::DELETE => [
        'name' => ts('Delete'),
        'url' => 'civicrm/contact/view/pledge',
        'qs' => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
        'title' => ts('Delete Pledge'),
      ],
    ];

    if (in_array('Cancel', $hideOption)) {
      unset(self::$_links[CRM_Core_Action::DETACH]);
    }

    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Pledge') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    if ($this->_limit) {
      $params['rowCount'] = $this->_limit;
    }
    else {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param int $action
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
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

    // get all pledge status
    $pledgeStatuses = CRM_Pledge_BAO_Pledge::buildOptions('status_id');

    // get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    // CRM-4418 check for view, edit and delete
    $permissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('edit pledges')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviPledge')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    while ($result->fetch()) {
      $row = [];
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (isset($result->$property)) {
          $row[$property] = $result->$property;
        }
      }

      // carry campaign on selectors.
      $row['campaign'] = CRM_Utils_Array::value($result->pledge_campaign_id, $allCampaigns);
      $row['campaign_id'] = $result->pledge_campaign_id;

      // add pledge status name
      if (!empty($row['pledge_status_id'])) {
        $row['pledge_status_name'] = CRM_Utils_Array::value($row['pledge_status_id'],
          $pledgeStatuses
        );
      }
      // append (test) to status label
      if (!empty($row['pledge_is_test'])) {
        $row['pledge_status'] = CRM_Core_TestEntity::appendTestText($row['pledge_status']);
      }

      $hideOption = [];
      if (CRM_Utils_Array::key('Cancelled', $row) ||
        CRM_Utils_Array::key('Completed', $row)
      ) {
        $hideOption[] = 'Cancel';
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->pledge_id;

      $row['action'] = CRM_Core_Action::formLink(self::links($hideOption, $this->_key),
        $mask,
        [
          'id' => $result->pledge_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
        ],
        ts('more'),
        FALSE,
        'pledge.selector.row',
        'Pledge',
        $result->pledge_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Get qill (display what was searched on).
   *
   * @inheritDoc
   */
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * Returns the column headers as an array of tuples.
   *
   * Keys are name, sortName, key to the sort array
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
          'name' => ts('Pledged'),
          'sort' => 'pledge_amount',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Total Paid'),
          'sort' => 'pledge_total_paid',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Balance'),
        ],
        [
          'name' => ts('Pledged For'),
          'sort' => 'pledge_financial_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Pledge Made'),
          'sort' => 'pledge_create_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ],
        [
          'name' => ts('Next Pay Date'),
          'sort' => 'pledge_next_pay_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Next Amount'),
          'sort' => 'pledge_next_pay_amount',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Status'),
          'sort' => 'pledge_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        ['desc' => ts('Actions')],
      ];

      if (!$this->_single) {
        $pre = [
          ['desc' => ts('Contact ID')],
          [
            'name' => ts('Name'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
        ];

        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
    }
    return self::$_columnHeaders;
  }

  /**
   * Get sql query string.
   *
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
    return ts('Pledge Search');
  }

}
