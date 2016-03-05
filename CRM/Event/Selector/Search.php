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
 * $Id$
 *
 */

/**
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Event_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

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
    'contact_type',
    'sort_name',
    'event_id',
    'participant_status_id',
    'event_title',
    'participant_fee_level',
    'participant_id',
    'event_start_date',
    'event_end_date',
    'event_type_id',
    'modified_date',
    'participant_is_test',
    'participant_role_id',
    'participant_register_date',
    'participant_fee_amount',
    'participant_fee_currency',
    'participant_status',
    'participant_role',
    'participant_campaign_id',
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
  protected $_eventClause = NULL;

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
   * @param string $eventClause
   *   If the caller wants to further restrict the search (used in participations).
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many participations do we want returned.
   *
   * @param string $context
   * @param null $compContext
   *
   * @return \CRM_Event_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $eventClause = NULL,
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

    $this->_eventClause = $eventClause;

    // type of selector
    $this->_action = $action;

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams,
      CRM_Event_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_EVENT,
        FALSE
      ),
      NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_EVENT
    );
    $this->_query->_distinctComponentClause = " civicrm_participant.id";
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_participant.id ";
  }

  /**
   * Can be used to alter the number of participation returned from a buildForm hook.
   *
   * @param int $limit
   *   How many participations do we want returned.
   */
  public function setLimit($limit) {
    $this->_limit = $limit;
  }

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param null $qfKey
   * @param null $context
   * @param null $compContext
   *
   * @return array
   */
  public static function &links($qfKey = NULL, $context = NULL, $compContext = NULL) {
    $extraParams = NULL;
    if ($compContext) {
      $extraParams .= "&compContext={$compContext}";
    }
    elseif ($context == 'search') {
      $extraParams .= '&compContext=participant';
    }

    if ($qfKey) {
      $extraParams .= "&key={$qfKey}";
    }

    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/participant',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=event' . $extraParams,
          'title' => ts('View Participation'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/participant',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'title' => ts('Edit Participation'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/participant',
          'qs' => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'title' => ts('Delete Participation'),
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
    $params['status'] = ts('Event') . ' %%StatusMessage%%';
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
      $this->_eventClause
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
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_eventClause
    );
    // process the result of the query
    $rows = array();

    //lets handle view, edit and delete separately. CRM-4418
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit event participants')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviEvent')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    $statusTypes = CRM_Event_PseudoConstant::participantStatus();
    $statusClasses = CRM_Event_PseudoConstant::participantStatusClass();
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    while ($result->fetch()) {
      $row = array();
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (isset($result->$property)) {
          $row[$property] = $result->$property;
        }
      }

      //carry campaign on selectors.
      $row['campaign'] = CRM_Utils_Array::value($result->participant_campaign_id, $allCampaigns);
      $row['campaign_id'] = $result->participant_campaign_id;

      // gross hack to show extra information for pending status
      $statusClass = NULL;
      if ((isset($row['participant_status_id'])) &&
        ($statusId = array_search($row['participant_status_id'], $statusTypes))
      ) {
        $statusClass = $statusClasses[$statusId];
      }

      $row['showConfirmUrl'] = ($statusClass == 'Pending') ? TRUE : FALSE;

      if (!empty($row['participant_is_test'])) {
        $row['participant_status'] .= ' (' . ts('test') . ')';
      }

      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->participant_id;
      $links = self::links($this->_key, $this->_context, $this->_compContext);

      if ($statusTypes[$row['participant_status_id']] == 'Partially paid') {
        $links[CRM_Core_Action::ADD] = array(
          'name' => ts('Record Payment'),
          'url' => 'civicrm/payment',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=event',
          'title' => ts('Record Payment'),
        );
      }

      if ($statusTypes[$row['participant_status_id']] == 'Pending refund') {
        $links[CRM_Core_Action::ADD] = array(
          'name' => ts('Record Refund'),
          'url' => 'civicrm/payment',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=event',
          'title' => ts('Record Refund'),
        );
      }

      $row['action'] = CRM_Core_Action::formLink($links,
        $mask,
        array(
          'id' => $result->participant_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
        ),
        ts('more'),
        FALSE,
        'participant.selector.row',
        'Participant',
        $result->participant_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );

      $row['paid'] = CRM_Event_BAO_Event::isMonetary($row['event_id']);

      if (!empty($row['participant_fee_level'])) {
        CRM_Event_BAO_Participant::fixEventLevel($row['participant_fee_level']);
      }

      if (CRM_Event_BAO_Event::usesPriceSet($row['event_id'])) {
        // add line item details if applicable
        $lineItems[$row['participant_id']] = CRM_Price_BAO_LineItem::getLineItems($row['participant_id']);
      }

      if (!empty($row['participant_role_id'])) {
        $viewRoles = array();
        foreach (explode($sep, $row['participant_role_id']) as $k => $v) {
          $viewRoles[] = $participantRoles[$v];
        }
        $row['participant_role_id'] = implode(', ', $viewRoles);
      }
      $rows[] = $row;
    }
    CRM_Core_Selector_Controller::$_template->assign_by_ref('lineItems', $lineItems);

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
      self::$_columnHeaders = array(
        array(
          'name' => ts('Event'),
          'sort' => 'event_title',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Fee Level'),
          'sort' => 'participant_fee_level',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Amount'),
          'sort' => 'participant_fee_amount',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Registered'),
          'sort' => 'participant_register_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Event Date(s)'),
          'sort' => 'event_start_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Status'),
          'sort' => 'participant_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Role'),
          'sort' => 'participant_role_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('desc' => ts('Actions')),
      );

      if (!$this->_single) {
        $pre = array(
          array('desc' => ts('Contact Type')),
          array(
            'name' => ts('Participant'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
        );
        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
    }
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
    return ts('CiviCRM Event Search');
  }

}
