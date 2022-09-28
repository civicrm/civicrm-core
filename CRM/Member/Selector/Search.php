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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 */
class CRM_Member_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

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
    'membership_id',
    'contact_type',
    'sort_name',
    'membership_type',
    'membership_join_date',
    'membership_start_date',
    'membership_end_date',
    'membership_source',
    'status_id',
    'member_is_test',
    'owner_membership_id',
    'membership_status',
    'member_campaign_id',
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
  protected $_memberClause = NULL;

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
   * @param string $memberClause
   *   If the caller wants to further restrict the search (used in memberships).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many memberships do we want returned.
   *
   * @param string $context
   *
   * @return \CRM_Member_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $memberClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single = $single;
    $this->_limit = $limit;
    $this->_context = $context;

    $this->_memberClause = $memberClause;

    // type of selector
    $this->_action = $action;

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams,
      CRM_Member_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_MEMBER,
        FALSE
      ),
      NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_MEMBER
    );
    $this->_query->_distinctComponentClause = " civicrm_membership.id";
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_membership.id ";
  }

  /**
   * This method returns the links that are given for each search row.
   *
   * Currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param string $status
   * @param bool $isPaymentProcessor
   * @param null $accessContribution
   * @param null $qfKey
   * @param null $context
   * @param bool $isCancelSupported
   *
   * @return array
   */
  public static function &links(
    $status = 'all',
    $isPaymentProcessor = NULL,
    $accessContribution = NULL,
    $qfKey = NULL,
    $context = NULL,
    $isCancelSupported = FALSE
  ) {
    $extraParams = NULL;
    if ($context == 'search') {
      $extraParams .= '&compContext=membership';
    }
    if ($qfKey) {
      $extraParams .= "&key={$qfKey}";
    }

    if (empty(self::$_links['view'])) {
      self::$_links['view'] = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=member' . $extraParams,
          'title' => ts('View Membership'),
        ],
      ];
    }
    if (!isset(self::$_links['all']) || !self::$_links['all']) {
      $extraLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'title' => ts('Edit Membership'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'title' => ts('Delete Membership'),
        ],
        CRM_Core_Action::RENEW => [
          'name' => ts('Renew'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'reset=1&action=renew&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'title' => ts('Renew Membership'),
        ],
        CRM_Core_Action::FOLLOWUP => [
          'name' => ts('Renew-Credit Card'),
          'url' => 'civicrm/contact/view/membership',
          'qs' => 'action=renew&reset=1&cid=%%cid%%&id=%%id%%&context=%%cxt%%&mode=live' . $extraParams,
          'title' => ts('Renew Membership Using Credit Card'),
        ],
      ];
      if (!$isPaymentProcessor || !$accessContribution) {
        //unset the renew with credit card when payment
        //processor is not available or user not permitted to make contributions
        unset($extraLinks[CRM_Core_Action::FOLLOWUP]);
      }

      self::$_links['all'] = self::$_links['view'] + $extraLinks;
    }

    if ($isCancelSupported) {
      self::$_links['all'][CRM_Core_Action::DISABLE] = [
        'name' => ts('Cancel Auto-renewal'),
        'url' => 'civicrm/contribute/unsubscribe',
        'qs' => 'reset=1&mid=%%id%%&context=%%cxt%%' . $extraParams,
        'title' => ts('Cancel Auto Renew Subscription'),
      ];
    }
    elseif (isset(self::$_links['all'][CRM_Core_Action::DISABLE])) {
      unset(self::$_links['all'][CRM_Core_Action::DISABLE]);
    }

    return self::$_links[$status];
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param int $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['status'] = ts('Member') . ' %%StatusMessage%%';
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
      $this->_memberClause
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
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    // check if we can process credit card registration
    $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE,
      "billing_mode IN ( 1, 3 )"
    );
    if (count($processors) > 0) {
      $this->_isPaymentProcessor = TRUE;
    }
    else {
      $this->_isPaymentProcessor = FALSE;
    }

    // Only show credit card membership signup and renewal if user has CiviContribute permission
    if (CRM_Core_Permission::access('CiviContribute')) {
      $this->_accessContribution = TRUE;
    }
    else {
      $this->_accessContribution = FALSE;
    }

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_memberClause
    );

    // process the result of the query
    $rows = [];

    //CRM-4418 check for view, edit, delete
    $permissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('edit memberships')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviMember')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    while ($result->fetch()) {
      $row = [];

      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (property_exists($result, $property)) {
          $row[$property] = $result->$property;
        }
      }

      //carry campaign on selectors.
      $row['campaign'] = $allCampaigns[$result->member_campaign_id] ?? NULL;
      $row['campaign_id'] = $result->member_campaign_id;

      if (!empty($row['member_is_test'])) {
        $row['membership_type'] = CRM_Core_TestEntity::appendTestText($row['membership_type']);
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->membership_id;

      if (!isset($result->owner_membership_id)) {
        // unset renew and followup link for deceased membership
        $currentMask = $mask;
        if ($result->membership_status == 'Deceased') {
          $currentMask = $currentMask & ~CRM_Core_Action::RENEW & ~CRM_Core_Action::FOLLOWUP;
        }

        $isCancelSupported = CRM_Member_BAO_Membership::isCancelSubscriptionSupported($row['membership_id']);
        $links = self::links('all',
          $this->_isPaymentProcessor,
          $this->_accessContribution,
          $this->_key,
          $this->_context,
          $isCancelSupported
        );

        // check permissions
        $finTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $result->membership_type_id, 'financial_type_id');
        $finType = CRM_Contribute_PseudoConstant::financialType($finTypeId);
        if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
          && !CRM_Core_Permission::check('edit contributions of type ' . $finType)
        ) {
          unset($links[CRM_Core_Action::UPDATE]);
        }
        if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus() &&
          !CRM_Core_Permission::check('delete contributions of type ' . $finType)
        ) {
          unset($links[CRM_Core_Action::DELETE]);
        }
        $row['action'] = CRM_Core_Action::formLink($links,
          $currentMask,
          [
            'id' => $result->membership_id,
            'cid' => $result->contact_id,
            'cxt' => $this->_context,
          ],
          ts('Renew') . '...',
          FALSE,
          'membership.selector.row',
          'Membership',
          $result->membership_id
        );
      }
      else {
        $links = self::links('view');
        $row['action'] = CRM_Core_Action::formLink($links, $mask,
          [
            'id' => $result->membership_id,
            'cid' => $result->contact_id,
            'cxt' => $this->_context,
          ],
          ts('more'),
          FALSE,
          'membership.selector.row',
          'Membership',
          $result->membership_id
        );
      }

      // Display Auto-renew status on page (0=disabled, 1=enabled, 2=enabled, but error
      if (!empty($result->membership_recur_id)) {
        if (CRM_Member_BAO_Membership::isSubscriptionCancelled($row['membership_id'])) {
          $row['auto_renew'] = 2;
        }
        else {
          $row['auto_renew'] = 1;
        }
      }
      else {
        $row['auto_renew'] = 0;
      }

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );

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
   * Returns the column headers as an array of tuples.
   *
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
          'sort' => 'membership_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Member Since'),
          'sort' => 'membership_join_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ],
        [
          'name' => ts('Start Date'),
          'sort' => 'membership_start_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('End Date'),
          'sort' => 'membership_end_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Source'),
          'sort' => 'membership_source',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Status'),
          'sort' => 'membership_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Auto-renew?'),
        ],
        ['desc' => ts('Actions')],
      ];

      if (!$this->_single) {
        $pre = [
          ['desc' => ts('Contact Type')],
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
   * Alphabet query.
   *
   * @return mixed
   */
  public function alphabetQuery() {
    return $this->_query->alphabetQuery();
  }

  /**
   * Get query.
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
    return ts('CiviCRM Member Search');
  }

}
