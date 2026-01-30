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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class to render contribution search results.
 */
class CRM_Contribute_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * Array of action links.
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
    'contribution_id',
    'contact_type',
    'sort_name',
    'amount_level',
    'total_amount',
    'financial_type',
    'contribution_source',
    'receive_date',
    'thankyou_date',
    'contribution_status_id',
    'contribution_status',
    'contribution_cancel_date',
    'product_name',
    'is_test',
    'is_template',
    'contribution_recur_id',
    'receipt_date',
    'membership_id',
    'currency',
    'contribution_campaign_id',
    'contribution_soft_credit_name',
    'contribution_soft_credit_contact_id',
    'contribution_soft_credit_amount',
    'contribution_soft_credit_type',
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
   * What component context are we being invoked from
   *
   * @var string
   */
  protected $_compContext = NULL;

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
  protected $_contributionClause = NULL;

  /**
   * The query object
   *
   * @var CRM_Contact_BAO_Query
   */
  protected $_query;

  protected $_includeSoftCredits = FALSE;

  /**
   * Class constructor.
   *
   * @param array $queryParams
   *   Array of parameters for query.
   * @param \const|int $action - action of search basic or advanced.
   * @param string $contributionClause
   *   If the caller wants to further restrict the search (used in contributions).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many contributions do we want returned.
   *
   * @param string $context
   * @param null $compContext
   *
   * @return CRM_Contribute_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $contributionClause = NULL,
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

    $this->_contributionClause = $contributionClause;

    // type of selector
    $this->_action = $action;
    $returnProperties = CRM_Contribute_BAO_Query::selectorReturnProperties($this->_queryParams);
    $this->_includeSoftCredits = CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($this->_queryParams);
    $this->_queryParams[] = ['contribution_id', '!=', 0, 0, 0];
    $this->_query = new CRM_Contact_BAO_Query(
      $this->_queryParams,
      $returnProperties,
      NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CONTRIBUTE
    );
    // @todo the function CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled should handle this
    // can we remove? if not why not?
    if ($this->_includeSoftCredits) {
      $this->_query->_rowCountClause = " count(civicrm_contribution.id)";
      $this->_query->_groupByComponentClause = " GROUP BY contribution_search_scredit_combined.id, contribution_search_scredit_combined.contact_id, contribution_search_scredit_combined.scredit_id ";
    }
    else {
      $this->_query->_distinctComponentClause = " civicrm_contribution.id";
      $this->_query->_groupByComponentClause = " GROUP BY civicrm_contribution.id ";
    }
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param int $componentId
   * @param null $componentAction
   * @param null $key
   * @param null $compContext
   *
   * @return array
   */
  public static function &links($componentId = NULL, $componentAction = NULL, $key = NULL, $compContext = NULL) {
    $extraParams = NULL;
    if ($componentId) {
      $extraParams = "&compId={$componentId}&compAction={$componentAction}";
    }
    if ($compContext) {
      $extraParams .= "&compContext={$compContext}";
    }
    if ($key) {
      $extraParams .= "&key={$key}";
    }

    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=contribute{$extraParams}",
          'title' => ts('View Contribution'),
          'weight' => -20,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts('Edit Contribution'),
          'weight' => -10,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts('Delete Contribution'),
          'weight' => 100,
        ],
      ];
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
    $params['status'] = ts('Contribution') . ' %%StatusMessage%%';
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
      $this->_contributionClause
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
    if ($this->_includeSoftCredits) {
      // especial sort order when rows include soft credits
      $sort = $sort->orderBy() . ", civicrm_contribution.id, civicrm_contribution_soft.id";
    }
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_contributionClause
    );
    // process the result of the query
    $rows = [];

    //CRM-4418 check for view/edit/delete
    $permissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('edit contributions')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviContribute')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    $qfKey = $this->_key;
    $componentId = $componentContext = NULL;
    if ($this->_context != 'contribute') {
      // @todo explain the significance of context & why we do not get these i that context.
      $qfKey = CRM_Utils_Request::retrieve('key', 'String');
      $componentId = CRM_Utils_Request::retrieve('id', 'Positive');
      $componentAction = CRM_Utils_Request::retrieve('action', 'String');
      $componentContext = CRM_Utils_Request::retrieve('compContext', 'String');

      if (!$componentContext &&
        $this->_compContext
      ) {
        // @todo explain when this condition might occur.
        $componentContext = $this->_compContext;
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, NULL, FALSE, 'REQUEST');
      }
      // CRM-17628 for some reason qfKey is not always set when searching from contribution search.
      // as a result if the edit link is opened using right-click + open in new tab
      // then the browser is not returned to the search results on save.
      // This is an effort to getting the qfKey without, sadly, understanding the intent of those who came before me.
      if (empty($qfKey)) {
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', CRM_Core_DAO::$_nullObject, NULL, FALSE, 'REQUEST');
      }
    }

    // get all contribution status
    $contributionStatuses = CRM_Core_OptionGroup::values('contribution_status',
      FALSE, FALSE, FALSE, NULL, 'name', FALSE
    );

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    while ($result->fetch()) {
      $this->_query->convertToPseudoNames($result);
      $links = self::links($componentId,
          $componentAction,
          $qfKey,
          $componentContext
      );
      // Set defaults to empty to prevent e-notices.
      $row = ['amount_level' => ''];
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (property_exists($result, $property)) {
          $row[$property] = $result->$property;
        }
      }

      //carry campaign on selectors.
      // @todo - I can't find any evidence that 'carrying' the campaign on selectors actually
      // results in it being displayed anywhere so why do we do this???
      $row['campaign'] = $allCampaigns[$result->contribution_campaign_id] ?? NULL;
      $row['campaign_id'] = $result->contribution_campaign_id;

      // add contribution status name
      $row['contribution_status_name'] = $contributionStatuses[$row['contribution_status_id']] ?? NULL;

      $isPayLater = FALSE;
      if ($result->is_pay_later && ($row['contribution_status_name'] ?? NULL) === 'Pending') {
        $isPayLater = TRUE;
        $row['contribution_status'] .= ' (' . ts('Pay Later') . ')';
        $links[CRM_Core_Action::ADD] = [
          'name' => ts('Pay with Credit Card'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%&mode=live',
          'title' => ts('Pay with Credit Card'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ADD),
        ];
      }
      elseif (($row['contribution_status_name'] ?? NULL) === 'Pending') {
        $row['contribution_status'] .= ' (' . ts('Incomplete Transaction') . ')';
      }

      if ($row['is_test']) {
        $row['financial_type'] = $row['financial_type'] . ' (' . ts('test') . ')';
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->contribution_id;

      $actions = [
        'id' => (int) $result->contribution_id,
        'cid' => (int) $result->contact_id,
        'cxt' => $this->_context,
        'financial_type_id' => $result->financial_type_id ? (int) $result->financial_type_id : NULL,
      ];

      if (in_array($row['contribution_status_name'], ['Partially paid', 'Pending refund']) || $isPayLater) {
        if ($row['contribution_status_name'] === 'Pending refund') {
          if (CRM_Core_Permission::check('refund contributions')) {
            $links[CRM_Core_Action::ADD] = [
              'name' => 'Record Refund',
              'url' => 'civicrm/payment',
              'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution',
              'title' => ts('Record Refund'),
              'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ADD),
            ];
          }
        }
        else {
          $links[CRM_Core_Action::ADD] = [
            'name' => 'Record Payment',
            'url' => 'civicrm/payment',
            'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution',
            'title' => ts('Record Payment'),
            'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ADD),
          ];
          if (CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
            $links[CRM_Core_Action::BASIC] = [
              'name' => ts('Submit Credit Card payment'),
              'url' => 'civicrm/payment/add',
              'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution&mode=live',
              'title' => ts('Submit Credit Card payment'),
              'weight' => 30,
            ];
          }
        }
      }
      $links = $links + CRM_Contribute_Task::getContextualLinks($row);

      $row['action'] = CRM_Core_Action::formLink(
        $links,
        $mask, $actions,
        ts('more'),
        FALSE,
        'contribution.selector.row',
        'Contribution',
        (int) $result->contribution_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?: $result->contact_type, FALSE, $result->contact_id
      );

      if (!empty($row['amount_level'])) {
        CRM_Event_BAO_Participant::fixEventLevel($row['amount_level']);
      }

      $rows[] = $row;
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
    $pre = [];
    self::$_columnHeaders = [
      [
        'name' => $this->_includeSoftCredits ? ts('Contribution Amount') : ts('Amount'),
        'sort' => 'total_amount',
        'direction' => CRM_Utils_Sort::DONTCARE,
        'field_name' => 'total_amount',
        'type' => '',
      ],
    ];
    if ($this->_includeSoftCredits) {
      self::$_columnHeaders
        = array_merge(
          self::$_columnHeaders,
          [
            [
              'name' => ts('Soft Credit Amount'),
              'sort' => 'contribution_soft_credit_amount',
              'field_name' => 'contribution_soft_credit_amount',
              'direction' => CRM_Utils_Sort::DONTCARE,
              'type' => '',
            ],
          ]
        );
    }
    self::$_columnHeaders
      = array_merge(
        self::$_columnHeaders,
        [
          [
            'name' => ts('Type'),
            'sort' => 'financial_type',
            'field_name' => 'financial_type',
            'direction' => CRM_Utils_Sort::DONTCARE,
            'type' => '',
          ],
          [
            'name' => ts('Source'),
            'sort' => 'contribution_source',
            'field_name' => 'contribution_source',
            'direction' => CRM_Utils_Sort::DONTCARE,
            'type' => '',
          ],
          [
            'name' => ts('Date'),
            'sort' => 'receive_date',
            'field_name' => 'receive_date',
            'type' => 'date',
            'direction' => CRM_Utils_Sort::DESCENDING,
          ],
          [
            'name' => ts('Thank-you Sent'),
            'sort' => 'thankyou_date',
            'field_name' => 'thankyou_date',
            'type' => 'date',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
          [
            'name' => ts('Status'),
            'sort' => 'contribution_status',
            'field_name' => 'contribution_status',
            'direction' => CRM_Utils_Sort::DONTCARE,
            'type' => '',
          ],
        ]
      );
    if (CRM_Contribute_BAO_Query::isSiteHasProducts()) {
      self::$_columnHeaders[] = [
        'name' => ts('Premium'),
        'sort' => 'product_name',
        'field_name' => 'product_name',
        'direction' => CRM_Utils_Sort::DONTCARE,
        'type' => '',
      ];
    }
    if (!$this->_single) {
      $pre = [
        [
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'field_name' => '',
          'direction' => CRM_Utils_Sort::DONTCARE,
          'type' => '',
        ],
      ];
    }
    self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
    if ($this->_includeSoftCredits) {
      self::$_columnHeaders = array_merge(
        self::$_columnHeaders,
        [
          [
            'name' => ts('Soft Credit For'),
            'sort' => 'contribution_soft_credit_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
            'field_name' => '',
          ],
          [
            'name' => ts('Soft Credit Type'),
            'sort' => 'contribution_soft_credit_type',
            'direction' => CRM_Utils_Sort::ASCENDING,
            'field_name' => '',
          ],
        ]
      );
    }
    self::$_columnHeaders
      = array_merge(
        self::$_columnHeaders, [
          ['desc' => ts('Actions'), 'type' => 'actions', 'field_name' => ''],
        ]
      );
    foreach (array_keys(self::$_columnHeaders) as $index) {
      // Add weight & space it out a bit to allow headers to be inserted.
      self::$_columnHeaders[$index]['weight'] = $index * 10;
    }

    CRM_Core_Smarty::singleton()->assign('softCreditColumns', $this->_includeSoftCredits);
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
    return ts('CiviCRM Contribution Search');
  }

  /**
   * @return mixed
   */
  public function getSummary() {
    return $this->_query->summaryContribution($this->_context);
  }

}
