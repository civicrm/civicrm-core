<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is used to browse past mailings.
 */
class CRM_Mailing_Selector_Browse extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * array of supported links, currenly null
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

  protected $_parent;

  /**
   * Class constructor
   *
   * @internal param $
   *
   * @return \CRM_Mailing_Selector_Browse
  @access public
   */
  function __construct() {
  }
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   *
   * @return array
   * @access public
   *
   */
  static function &links() {
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param $params
   *
   * @internal param $
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['status'] = ts('Mailings %%StatusMessage%%');
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

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
  function &getColumnHeaders($action = NULL, $output = NULL) {
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    if (!isset(self::$_columnHeaders)) {
      $completedOrder = NULL;

      // Set different default sort depending on type of mailings (CRM-7652)
      $unscheduledOrder = $scheduledOrder = $archivedOrder = CRM_Utils_Sort::DONTCARE;
      if ($this->_parent->get('unscheduled')) {
        $unscheduledOrder = CRM_Utils_Sort::DESCENDING;
      }
      elseif ($this->_parent->get('scheduled')) {
        $scheduledOrder = CRM_Utils_Sort::DESCENDING;
      }
      else {
        // sort by completed date for archived and undefined get
        $completedOrder = CRM_Utils_Sort::DESCENDING;
      }
      $nameHeaderLabel = ($this->_parent->get('sms')) ? ts('SMS Name') : ts('Mailing Name');

      self::$_columnHeaders = array(
        array(
          'name' => $nameHeaderLabel,
          'sort' => 'name',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Status'),
          'sort' => 'status',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Created By'),
          'sort' => 'created_by',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Created Date'),
          'sort' => 'created_date',
          'direction' => $unscheduledOrder,
        ),
        array(
          'name' => ts('Sent By'),
          'sort' => 'scheduled_by',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Scheduled'),
          'sort' => 'scheduled_date',
          'direction' => $scheduledOrder,
        ),
        array(
          'name' => ts('Started'),
          'sort' => 'start_date',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Completed'),
          'sort' => 'end_date',
          'direction' => $completedOrder,
        ),
      );

      if (CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
        self::$_columnHeaders[] = array('name' => ts('Campaign'),
          'sort' => 'campaign_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        );
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT) {
        self::$_columnHeaders[] = array('name' => ts('Action'));
      }
    }
    return self::$_columnHeaders;
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    $job        = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailing    = CRM_Mailing_BAO_Mailing::getTableName();
    $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL();

    //get the where clause.
    $params = array();
    $whereClause = "$mailingACL AND " . $this->whereClause($params);

    // CRM-11919 added addition ON clauses to mailing_job to match getRows
    $query = "
   SELECT  COUNT( DISTINCT $mailing.id ) as count
     FROM  $mailing
LEFT JOIN  $job ON ( $mailing.id = $job.mailing_id AND civicrm_mailing_job.is_test = 0 AND civicrm_mailing_job.parent_id IS NULL )
LEFT JOIN  civicrm_contact createdContact   ON ( $mailing.created_id   = createdContact.id )
LEFT JOIN  civicrm_contact scheduledContact ON ( $mailing.scheduled_id = scheduledContact.id )
    WHERE  $whereClause";

    return CRM_Core_DAO::singleValueQuery($query, $params);
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
    static $actionLinks = NULL;
    if (empty($actionLinks)) {
      $cancelExtra  = ts('Are you sure you want to cancel this mailing?');
      $deleteExtra  = ts('Are you sure you want to delete this mailing?');
      $archiveExtra = ts('Are you sure you want to archive this mailing?');

      $actionLinks = array(
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Approve/Reject'),
          'url' => 'civicrm/mailing/approve',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('Approve/Reject Mailing'),
        ),
        CRM_Core_Action::VIEW => array(
          'name' => ts('Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('View Mailing Report'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Re-Use'),
          'url' => 'civicrm/mailing/send',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('Re-Send Mailing'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Cancel'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=disable&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $cancelExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Cancel Mailing'),
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Continue'),
          'url' => 'civicrm/mailing/send',
          'qs' => 'mid=%%mid%%&continue=true&reset=1',
          'title' => ts('Continue Mailing'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=delete&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $deleteExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Delete Mailing'),
        ),
        CRM_Core_Action::RENEW => array(
          'name' => ts('Archive'),
          'url' => 'civicrm/mailing/browse/archived',
          'qs' => 'action=renew&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $archiveExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Archive Mailing'),
        ),
      );
    }

    $allAccess = TRUE;
    $workFlow = $showApprovalLinks = $showScheduleLinks = $showCreateLinks = FALSE;
    if (CRM_Mailing_Info::workflowEnabled()) {
      $allAccess = FALSE;
      $workFlow = TRUE;
      //supercedes all permission
      if (CRM_Core_Permission::check('access CiviMail')) {
        $allAccess = TRUE;
      }

      if (CRM_Core_Permission::check('approve mailings')) {
        $showApprovalLinks = TRUE;
      }

      if (CRM_Core_Permission::check('create mailings')) {
        $showCreateLinks = TRUE;
      }

      if (CRM_Core_Permission::check('schedule mailings')) {
        $showScheduleLinks = TRUE;
      }
    }
    $mailing = new CRM_Mailing_BAO_Mailing();

    $params = array();

    $whereClause = ' AND ' . $this->whereClause($params);

    if (empty($params)) {
      $this->_parent->assign('isSearch', 0);
    }
    else {
      $this->_parent->assign('isSearch', 1);
    }
    $rows = &$mailing->getRows($offset, $rowCount, $sort, $whereClause, $params);

    //get the search base mailing Ids, CRM-3711.
    $searchMailings = $mailing->searchMailingIDs();

    //check for delete CRM-4418
    $allowToDelete = CRM_Core_Permission::check('delete in CiviMail');

    if ($output != CRM_Core_Selector_Controller::EXPORT) {

      //create the appropriate $op to use for hook_civicrm_links
      $pageTypes = array('view', 'mailing', 'browse');
      if ($this->_parent->_unscheduled) {
        $pageTypes[] = 'unscheduled';
      }
      if ($this->_parent->_scheduled) {
        $pageTypes[] = 'scheduled';
      }
      if ($this->_parent->_archived) {
        $pageTypes[] = 'archived';
      }
      $opString = implode('.', $pageTypes);

      foreach ($rows as $key => $row) {
        $actionMask = NULL;
        if ($row['sms_provider_id']) {
          $actionLinks[CRM_Core_Action::PREVIEW]['url'] = 'civicrm/sms/send';
        }

        if (!($row['status'] == 'Not scheduled') && !$row['sms_provider_id']) {
          if ($allAccess || $showCreateLinks) {
            $actionMask = CRM_Core_Action::VIEW;
          }

          if (!in_array($row['id'], $searchMailings)) {
            if ($allAccess || $showCreateLinks) {
              $actionMask |= CRM_Core_Action::UPDATE;
            }
          }
        }
        else {
          if ($allAccess || ($showCreateLinks || $showScheduleLinks)) {
            $actionMask = CRM_Core_Action::PREVIEW;
          }
        }
        if (in_array($row['status'], array(
          'Scheduled', 'Running', 'Paused'))) {
          if ($allAccess ||
            ($showApprovalLinks && $showCreateLinks && $showScheduleLinks)
          ) {

            $actionMask |= CRM_Core_Action::DISABLE;
          }
          if ($row['status'] == 'Scheduled' &&
            empty($row['approval_status_id'])
          ) {
            if ($workFlow && ($allAccess || $showApprovalLinks)) {
              $actionMask |= CRM_Core_Action::ENABLE;
            }
          }
        }

        if (in_array($row['status'], array('Complete', 'Canceled')) &&
          !$row['archived']) {
          if ($allAccess || $showCreateLinks) {
            $actionMask |= CRM_Core_Action::RENEW;
          }
        }

        //check for delete permission.
        if ($allowToDelete) {
          $actionMask |= CRM_Core_Action::DELETE;
        }

        if ($actionMask == NULL) {
          $actionMask = CRM_Core_Action::ADD;
        }
        //get status strings as per locale settings CRM-4411.
        $rows[$key]['status'] = CRM_Mailing_BAO_MailingJob::status($row['status']);

        $rows[$key]['action'] = CRM_Core_Action::formLink($actionLinks,
          $actionMask,
          array('mid' => $row['id']),
          "more",
          FALSE,
          $opString,
          "Mailing",
          $row['id']
        );

        //unset($rows[$key]['id']);
        // if the scheduled date is 0, replace it with an empty string
        if ($rows[$key]['scheduled_iso'] == '0000-00-00 00:00:00') {
          $rows[$key]['scheduled'] = '';
        }
        unset($rows[$key]['scheduled_iso']);
      }
    }

    // also initialize the AtoZ pager
    $this->pagerAtoZ();
    return $rows;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviMail Mailings');
  }

  /**
   * @param $parent
   */
  function setParent($parent) {
    $this->_parent = $parent;
  }

  /**
   * @param $params
   * @param bool $sortBy
   *
   * @return int|string
   */
  function whereClause(&$params, $sortBy = TRUE) {
    $values = $clauses = array();
    $isFormSubmitted   = $this->_parent->get('hidden_find_mailings');

    $title = $this->_parent->get('mailing_name');
    if ($title) {
      $clauses[] = 'name LIKE %1';
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    $dateClause1 = $dateClause2 = array();
    $from = $this->_parent->get('mailing_from');
    if (!CRM_Utils_System::isNull($from)) {
      if ($this->_parent->get('unscheduled')) {
        $dateClause1[] = 'civicrm_mailing.created_date >= %2';
      }
      else {
        $dateClause1[] = 'civicrm_mailing_job.start_date >= %2';
        $dateClause2[] = 'civicrm_mailing_job.scheduled_date >= %2';
      }
      $params[2] = array($from, 'String');
    }

    $to = $this->_parent->get('mailing_to');
    if (!CRM_Utils_System::isNull($to)) {
      if ($this->_parent->get('unscheduled')) {
        $dateClause1[] = ' civicrm_mailing.created_date <= %3 ';
      }
      else {
        $dateClause1[] = 'civicrm_mailing_job.start_date <= %3';
        $dateClause2[] = 'civicrm_mailing_job.scheduled_date <= %3';
      }
      $params[3]     = array($to, 'String');
    }

    $dateClauses = array();
    if (!empty($dateClause1)) {
      $dateClauses[] = implode(' AND ', $dateClause1);
    }
    if (!empty($dateClause2)) {
      $dateClauses[] = implode(' AND ', $dateClause2);
    }
    $dateClauses = implode(' OR ', $dateClauses);
    if (!empty($dateClauses)) {
      $clauses[] = "({$dateClauses})";
    }

    if ($this->_parent->get('sms')) {
      $clauses[] = "civicrm_mailing.sms_provider_id IS NOT NULL";
    }
    else {
      $clauses[] = "civicrm_mailing.sms_provider_id IS NULL";
    }

    // get values submitted by form
    $isDraft       = $this->_parent->get('status_unscheduled');
    $isArchived    = $this->_parent->get('is_archived');
    $mailingStatus = $this->_parent->get('mailing_status');

    if (!$isFormSubmitted && $this->_parent->get('scheduled')) {
      // mimic default behavior for scheduled screen
      $isArchived = 0;
      $mailingStatus = array('Scheduled' => 1, 'Complete' => 1, 'Running' => 1, 'Canceled' => 1);
    }
    if (!$isFormSubmitted && $this->_parent->get('archived')) {
      // mimic default behavior for archived screen
      $isArchived = 1;
    }
    if (!$isFormSubmitted && $this->_parent->get('unscheduled')) {
      // mimic default behavior for draft screen
      $isDraft = 1;
    }

    $statusClauses = array();
    if ($isDraft) {
      $statusClauses[] = "civicrm_mailing.scheduled_id IS NULL";
    }
    if (!empty($mailingStatus)) {
      $statusClauses[] = "civicrm_mailing_job.status IN ('" . implode("', '", array_keys($mailingStatus)) . "')";
    }
    if (!empty($statusClauses)) {
      $clauses[] = "(" . implode(' OR ', $statusClauses) . ")";
    }

    if (isset($isArchived)) {
      if ($isArchived) {
        $clauses[] = "civicrm_mailing.is_archived = 1";
      } else {
        $clauses[] = "(civicrm_mailing.is_archived IS NULL OR civicrm_mailing.is_archived = 0)";
      }
    }

    if ($sortBy &&
      $this->_parent->_sortByCharacter !== NULL
    ) {
      $clauses[] = "name LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_parent->_sortByCharacter)) . "%'";
    }

    // dont do a the below assignement when doing a
    // AtoZ pager clause
    if ($sortBy) {
      if (count($clauses) > 1) {
        $this->_parent->assign('isSearch', 1);
      }
      else {
        $this->_parent->assign('isSearch', 0);
      }
    }

    $createOrSentBy = $this->_parent->get('sort_name');
    if (!CRM_Utils_System::isNull($createOrSentBy)) {
      $clauses[] = '(createdContact.sort_name LIKE %4 OR scheduledContact.sort_name LIKE %4)';
      $params[4] = array('%' . $createOrSentBy . '%', 'String');
    }

    $createdId = $this->_parent->get('createdId');
    if ($createdId) {
      $clauses[] = "(created_id = {$createdId})";
      $params[5] = array($createdId, 'Integer');
    }

    $campainIds = $this->_parent->get('campaign_id');
    if (!CRM_Utils_System::isNull($campainIds)) {
      if (!is_array($campainIds)) {
        $campaignIds = array($campaignIds);
      }
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', array_values($campainIds)) . ' ) )';
    }

    if (empty($clauses)) {
      return 1;
    }

    return implode(' AND ', $clauses);
  }

  function pagerAtoZ() {

    $params = array();
    $whereClause = $this->whereClause($params, FALSE);

    $query = "
SELECT DISTINCT UPPER(LEFT(name, 1)) as sort_name
FROM civicrm_mailing
LEFT JOIN civicrm_mailing_job ON (civicrm_mailing_job.mailing_id = civicrm_mailing.id)
LEFT JOIN civicrm_contact createdContact ON ( civicrm_mailing.created_id = createdContact.id )
LEFT JOIN civicrm_contact scheduledContact ON ( civicrm_mailing.scheduled_id = scheduledContact.id )
WHERE $whereClause
ORDER BY LEFT(name, 1)
";

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_parent->_sortByCharacter, TRUE);
    $this->_parent->assign('aToZ', $aToZBar);
  }
}
//end of class

