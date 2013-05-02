<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Case_Selector_Search extends CRM_Core_Selector_Base {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   * @static
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   * @static
   */
  static $_properties = array(
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
  );

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * queryParams is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   * @access protected
   */
  public $_queryParams;

  /**
   * represent the type of selector
   *
   * @var int
   * @access protected
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
   * Class constructor
   *
   * @param array   $queryParams array of parameters for query
   * @param int     $action - action of search basic or advanced.
   * @param string  $additionalClause if the caller wants to further restrict the search (used in participations)
   * @param boolean $single are we dealing only with one contact?
   * @param int     $limit  how many signers do we want returned
   *
   * @return CRM_Contact_Selector
   * @access public
   */ function __construct(&$queryParams,
    $action           = CRM_Core_Action::NONE,
    $additionalClause = NULL,
    $single           = FALSE,
    $limit            = NULL,
    $context          = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_single  = $single;
    $this->_limit   = $limit;
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
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   * @access public
   *
   */
  static
  function &links($isDeleted = FALSE, $key = NULL) {
    $extraParams = ($key) ? "&key={$key}" : NULL;

    if ($isDeleted) {
      self::$_links = array(
        CRM_Core_Action::RENEW => array(
          'name' => ts('Restore'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&action=renew&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'restore-case',
          'title' => ts('Restore Case'),
        ),
      );
    }
    else {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('Manage'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=case' . $extraParams,
          'ref' => 'manage-case',
          'title' => ts('Manage Case'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/contact/view/case',
          'qs' => 'reset=1&action=delete&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'delete-case',
          'title' => ts('Delete Case'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Assign to Another Client'),
          'url' => 'civicrm/contact/view/case/editClient',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%' . $extraParams,
          'ref' => 'reassign',
          'title' => ts('Assign to Another Client'),
        ),
      );
    }

    $actionLinks = array();
    foreach (self::$_links as $key => $value) {
      if ($value['ref'] == 'reassign') {
        $actionLinks['moreActions'][$key] = $value;
      }
      else {
        $actionLinks['primaryActions'][$key] = $value;
      }
    }

    return $actionLinks;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['status'] = ts('Case') . ' %%StatusMessage%%';
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
  //end of function

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_additionalClause
    );
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_additionalClause
    );
    // process the result of the query
    $rows = array();

    //CRM-4418 check for view, edit, delete
    $permissions = array(CRM_Core_Permission::VIEW);
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

    $scheduledInfo = array();

    while ($result->fetch()) {
      $row = array();
      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (isset($result->$property)) {
          $row[$property] = $result->$property;
        }
      }

      $isDeleted = FALSE;
      if ($result->case_deleted) {
        $isDeleted = TRUE;
        $row['case_status_id'] = empty($row['case_status_id']) ? "" : $row['case_status_id'] . '<br />(deleted)';
      }

      $scheduledInfo['case_id'][] = $result->case_id;
      $scheduledInfo['contact_id'][] = $result->contact_id;
      $scheduledInfo['case_deleted'] = $result->case_deleted;
      $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->case_id;

      $links = self::links($isDeleted, $this->_key);
      $row['action'] = CRM_Core_Action::formLink($links['primaryActions'],
        $mask, array(
          'id' => $result->case_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
        )
      );
      $row['moreActions'] = CRM_Core_Action::formLink(CRM_Utils_Array::value('moreActions', $links),
        $mask, array(
          'id' => $result->case_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
        ),
        ts('more'),
        TRUE
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
        $result->contact_sub_type : $result->contact_type
      );

      //adding case manager to case selector.CRM-4510.
      $caseType = CRM_Case_BAO_Case::getCaseType($result->case_id, 'name');
      $caseManagerContact = CRM_Case_BAO_Case::getCaseManagerContact($caseType, $result->case_id);

      if (!empty($caseManagerContact)) {
        $row['casemanager_id'] = CRM_Utils_Array::value('casemanager_id', $caseManagerContact);
        $row['casemanager'] = CRM_Utils_Array::value('casemanager', $caseManagerContact);
      }

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

    //retrive the scheduled & recent Activity type and date for selector
    if (!empty($scheduledInfo)) {
      $schdeduledActivity = CRM_Case_BAO_Case::getNextScheduledActivity($scheduledInfo, 'upcoming');
      foreach ($schdeduledActivity as $key => $value) {
        $rows[$key]['case_scheduled_activity_date'] = $value['date'];
        $rows[$key]['case_scheduled_activity_type'] = $value['type'];
      }
      $recentActivity = CRM_Case_BAO_Case::getNextScheduledActivity($scheduledInfo, 'recent');
      foreach ($recentActivity as $key => $value) {
        $rows[$key]['case_recent_activity_date'] = $value['date'];
        $rows[$key]['case_recent_activity_type'] = $value['type'];
      }
    }
    return $rows;
  }

  /**
   *
   * @return array              $qill         which contains an array of strings
   * @access public
   */

  // the current internationalisation is bad, but should more or less work
  // for most of "European" languages
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    if (!isset(self::$_columnHeaders)) {
      self::$_columnHeaders = array(
        array(
          'name' => ts('Subject'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Status'),
          'sort' => 'case_status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Case Type'),
          'sort' => 'case_type_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('My Role'),
          'sort' => 'case_role',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Case Manager'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Most Recent'),
          'sort' => 'case_recent_activity_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Next Sched.'),
          'sort' => 'case_scheduled_activity_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array('name' => ts('Actions')),
      );

      if (!$this->_single) {
        $pre = array(
          array(
            'name' => ts('Client'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::ASCENDING,
          ),
        );

        self::$_columnHeaders = array_merge($pre, self::$_columnHeaders);
      }
    }
    return self::$_columnHeaders;
  }

  function alphabetQuery() {
    return $this->_query->searchQuery(NULL, NULL, NULL, FALSE, FALSE, TRUE);
  }

  function &getQuery() {
    return $this->_query;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('Case Search');
  }
}
//end of class

