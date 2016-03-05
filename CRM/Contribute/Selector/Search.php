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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Contribute_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   */
  static $_properties = array(
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
    'cancel_date',
    'product_name',
    'is_test',
    'contribution_recur_id',
    'receipt_date',
    'membership_id',
    'currency',
    'contribution_campaign_id',
    'contribution_soft_credit_name',
    'contribution_soft_credit_contact_id',
    'contribution_soft_credit_amount',
    'contribution_soft_credit_type',
  );

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
   * @var string
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
   * @return \CRM_Contribute_Selector_Search
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

    $this->_includeSoftCredits = CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($this->_queryParams);
    $this->_query = new CRM_Contact_BAO_Query(
      $this->_queryParams,
      CRM_Contribute_BAO_Query::selectorReturnProperties(),
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
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=contribute{$extraParams}",
          'title' => ts('View Contribution'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts('Edit Contribution'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => "reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%{$extraParams}",
          'title' => ts('Delete Contribution'),
        ),
      );
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
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
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
      $sort = "civicrm_contribution.receive_date DESC, civicrm_contribution.id, civicrm_contribution_soft.id";
    }
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_contributionClause
    );
    // process the result of the query
    $rows = array();

    //CRM-4418 check for view/edit/delete
    $permissions = array(CRM_Core_Permission::VIEW);
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
      $qfKey = CRM_Utils_Request::retrieve('key', 'String', CRM_Core_DAO::$_nullObject);
      $componentId = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject);
      $componentAction = CRM_Utils_Request::retrieve('action', 'String', CRM_Core_DAO::$_nullObject);
      $componentContext = CRM_Utils_Request::retrieve('compContext', 'String', CRM_Core_DAO::$_nullObject);

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
      $links = self::links($componentId,
          $componentAction,
          $qfKey,
          $componentContext
      );
      $checkLineItem = FALSE;
      $row = array();
      // Now check for lineItems
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
        $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($result->id);
        foreach ($lineItems as $items) {
          if (!CRM_Core_Permission::check('view contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']))) {
            $checkLineItem = TRUE;
            break;
          }
          if (!CRM_Core_Permission::check('edit contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']))) {
            unset($links[CRM_Core_Action::UPDATE]);
          }
          if (!CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']))) {
            unset($links[CRM_Core_Action::DELETE]);
          }
        }
        if ($checkLineItem) {
          continue;
        }
        if (!CRM_Core_Permission::check('edit contributions of type ' . CRM_Contribute_PseudoConstant::financialType($result->financial_type_id))) {
          unset($links[CRM_Core_Action::UPDATE]);
        }
        if (!CRM_Core_Permission::check('delete contributions of type ' . CRM_Contribute_PseudoConstant::financialType($result->financial_type_id))) {
          unset($links[CRM_Core_Action::DELETE]);
        }
      }
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (property_exists($result, $property)) {
          $row[$property] = $result->$property;
        }
      }

      //carry campaign on selectors.
      // @todo - I can't find any evidence that 'carrying' the campaign on selectors actually
      // results in it being displayed anywhere so why do we do this???
      $row['campaign'] = CRM_Utils_Array::value($result->contribution_campaign_id, $allCampaigns);
      $row['campaign_id'] = $result->contribution_campaign_id;

      // add contribution status name
      $row['contribution_status_name'] = CRM_Utils_Array::value($row['contribution_status_id'],
        $contributionStatuses
      );

      if ($result->is_pay_later && CRM_Utils_Array::value('contribution_status_name', $row) == 'Pending') {
        $row['contribution_status'] .= ' (' . ts('Pay Later') . ')';
      }
      elseif (CRM_Utils_Array::value('contribution_status_name', $row) == 'Pending') {
        $row['contribution_status'] .= ' (' . ts('Incomplete Transaction') . ')';
      }

      if ($row['is_test']) {
        $row['financial_type'] = $row['financial_type'] . ' (' . ts('test') . ')';
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->contribution_id;

      $actions = array(
        'id' => $result->contribution_id,
        'cid' => $result->contact_id,
        'cxt' => $this->_context,
      );

      $row['action'] = CRM_Core_Action::formLink(
        $links,
        $mask, $actions,
        ts('more'),
        FALSE,
        'contribution.selector.row',
        'Contribution',
        $result->contribution_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
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
    $pre = array();
    self::$_columnHeaders = array(
      array(
        'name' => $this->_includeSoftCredits ? ts('Contribution Amount') : ts('Amount'),
        'sort' => 'total_amount',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ),
    );
    if ($this->_includeSoftCredits) {
      self::$_columnHeaders
        = array_merge(
          self::$_columnHeaders,
          array(
            array(
              'name' => ts('Soft Credit Amount'),
              'sort' => 'contribution_soft_credit_amount',
              'direction' => CRM_Utils_Sort::DONTCARE,
            ),
          )
        );
    }
    self::$_columnHeaders
      = array_merge(
        self::$_columnHeaders,
        array(
          array(
            'name' => ts('Type'),
            'sort' => 'financial_type',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Source'),
            'sort' => 'contribution_source',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Received'),
            'sort' => 'receive_date',
            'direction' => CRM_Utils_Sort::DESCENDING,
          ),
          array(
            'name' => ts('Thank-you Sent'),
            'sort' => 'thankyou_date',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Status'),
            'sort' => 'contribution_status',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Premium'),
            'sort' => 'product_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
        )
      );
    if (!$this->_single) {
      $pre = array(
        array(
          'name' => ts('Name'),
          'sort' => 'sort_name',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
      );
    }
    self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
    if ($this->_includeSoftCredits) {
      self::$_columnHeaders = array_merge(
        self::$_columnHeaders,
        array(
          array(
            'name' => ts('Soft Credit For'),
            'sort' => 'contribution_soft_credit_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
          array(
            'name' => ts('Soft Credit Type'),
            'sort' => 'contribution_soft_credit_type',
            'direction' => CRM_Utils_Sort::ASCENDING,
          ),
        )
      );
    }
    self::$_columnHeaders
      = array_merge(
        self::$_columnHeaders, array(
          array('desc' => ts('Actions')),
        )
      );
    CRM_Core_Smarty::singleton()->assign('softCreditColumns', $this->_includeSoftCredits);
    return self::$_columnHeaders;
  }

  /**
   * @return mixed
   */
  public function alphabetQuery() {
    return $this->_query->searchQuery(NULL, NULL, NULL, FALSE, FALSE, TRUE);
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
    return ts('CiviCRM Contribution Search');
  }

  /**
   * @return mixed
   */
  public function getSummary() {
    return $this->_query->summaryContribution($this->_context);
  }

}
