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
 * This class is used to browse past mailings.
 */
class CRM_Mailing_Selector_Browse extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * Array of supported links, currently null
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

  protected $_parent;

  /**
   * Class constructor.
   *
   *
   * @return \CRM_Mailing_Selector_Browse
   */
  public function __construct() {
  }

  /**
   * This method returns the links that are given for each search row.
   *
   * @return array
   */
  public static function &links() {
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
    $params['csvString'] = NULL;
    $params['rowCount'] = Civi::settings()->get('default_pager_size');
    $params['status'] = ts('Mailings %%StatusMessage%%');
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
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

      self::$_columnHeaders = [
        [
          'name' => $nameHeaderLabel,
          'sort' => 'name',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
      ];

      if (CRM_Core_I18n::isMultilingual()) {
        self::$_columnHeaders = array_merge(
          self::$_columnHeaders,
          [
            [
              'name' => ts('Language'),
              'sort' => 'language',
              'direction' => CRM_Utils_Sort::DONTCARE,
            ],
          ]
        );
      }

      self::$_columnHeaders = array_merge(
        self::$_columnHeaders,
        [
          [
            'name' => ts('Status'),
            'sort' => 'status',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
          [
            'name' => ts('Created By'),
            'sort' => 'created_by',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
          [
            'name' => ts('Created Date'),
            'sort' => 'created_date',
            'direction' => $unscheduledOrder,
          ],
          [
            'name' => ts('Sent By'),
            'sort' => 'scheduled_by',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
          [
            'name' => ts('Scheduled'),
            'sort' => 'scheduled_date',
            'direction' => $scheduledOrder,
          ],
          [
            'name' => ts('Started'),
            'sort' => 'start_date',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ],
          [
            'name' => ts('Completed'),
            'sort' => 'end_date',
            'direction' => $completedOrder,
          ],
        ]
      );

      if (CRM_Core_Component::isEnabled('CiviCampaign')) {
        self::$_columnHeaders[] = [
          'name' => ts('Campaign'),
          'sort' => 'campaign_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ];
      }

      if ($output != CRM_Core_Selector_Controller::EXPORT) {
        self::$_columnHeaders[] = ['name' => ts('Action')];
      }
    }

    CRM_Core_Smarty::singleton()->assign('multilingual', CRM_Core_I18n::isMultilingual());
    return self::$_columnHeaders;
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
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $mailingACL = CRM_Mailing_BAO_Mailing::mailingACL();

    // get the where clause.
    $params = [];
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
    static $actionLinks = NULL;
    if (empty($actionLinks)) {
      $cancelExtra = ts('Are you sure you want to cancel this mailing?');
      $deleteExtra = ts('Are you sure you want to delete this mailing?');
      $archiveExtra = ts('Are you sure you want to archive this mailing?');

      $actionLinks = [
        CRM_Core_Action::ENABLE => [
          'name' => ts('Approve/Reject'),
          'url' => 'civicrm/mailing/approve',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('Approve/Reject Mailing'),
          'weight' => -50,
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('View Mailing Report'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Cancel'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=disable&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $cancelExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Cancel Mailing'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Continue'),
          'url' => 'civicrm/mailing/send',
          'qs' => 'mid=%%mid%%&continue=true&reset=1',
          'title' => ts('Continue Mailing'),
          'weight' => 50,
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Copy'),
          'url' => 'civicrm/mailing/send',
          'qs' => 'mid=%%mid%%&reset=1',
          'title' => ts('Copy Mailing'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=delete&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $deleteExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Delete Mailing'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        CRM_Core_Action::RENEW => [
          'name' => ts('Archive'),
          'url' => 'civicrm/mailing/browse/archived',
          'qs' => 'action=renew&mid=%%mid%%&reset=1',
          'extra' => 'onclick="if (confirm(\'' . $archiveExtra . '\')) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Archive Mailing'),
          'weight' => 110,
        ],
        CRM_Core_Action::REOPEN => [
          'name' => ts('Resume'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=reopen&mid=%%mid%%&reset=1',
          'title' => ts('Resume mailing'),
          'weight' => 120,
        ],
        CRM_Core_Action::CLOSE => [
          'name' => ts('Pause'),
          'url' => 'civicrm/mailing/browse',
          'qs' => 'action=close&mid=%%mid%%&reset=1',
          'title' => ts('Pause mailing'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::BROWSE),
        ],
      ];
    }

    $allAccess = TRUE;
    $workFlow = $showApprovalLinks = $showScheduleLinks = $showCreateLinks = FALSE;
    if (CRM_Mailing_Info::workflowEnabled()) {
      $allAccess = FALSE;
      $workFlow = TRUE;
      // supercedes all permission
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

    $params = [];

    $whereClause = ' AND ' . $this->whereClause($params);

    if (empty($params)) {
      $this->_parent->assign('isSearch', 0);
    }
    else {
      $this->_parent->assign('isSearch', 1);
    }
    $rows = &$mailing->getRows($offset, $rowCount, $sort, $whereClause, $params);

    // get the search base mailing Ids, CRM-3711.
    $searchMailings = $mailing->searchMailingIDs();

    // check for delete CRM-4418
    $allowToDelete = CRM_Core_Permission::check('delete in CiviMail');

    if ($output != CRM_Core_Selector_Controller::EXPORT) {

      // create the appropriate $op to use for hook_civicrm_links
      $pageTypes = ['view', 'mailing', 'browse'];
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

      // get languages for later conversion
      $languages = CRM_Core_I18n::languages();

      foreach ($rows as $key => $row) {
        $actionMask = NULL;
        if ($row['sms_provider_id']) {
          $actionLinks[CRM_Core_Action::PREVIEW]['url'] = 'civicrm/sms/send';
          $actionLinks[CRM_Core_Action::PREVIEW]['title'] = ts('Continue SMS');
          $actionLinks[CRM_Core_Action::UPDATE]['url'] = 'civicrm/sms/send';
          $actionLinks[CRM_Core_Action::UPDATE]['title'] = ts('Copy SMS');
          $actionLinks[CRM_Core_Action::VIEW]['title'] = ts('View SMS Report');
        }
        if ($row['status'] !== 'Not scheduled') {
          if ($allAccess || $showCreateLinks) {
            $actionMask = CRM_Core_Action::VIEW;
          }
        }
        else {
          if ($allAccess || ($showCreateLinks || $showScheduleLinks)) {
            $actionMask = CRM_Core_Action::PREVIEW;
          }
        }
        if (in_array($row['status'], ['Scheduled', 'Running', 'Paused'])) {
          if ($allAccess || ($showApprovalLinks && $showCreateLinks && $showScheduleLinks)) {

            $actionMask |= CRM_Core_Action::DISABLE;
            if ($row['status'] === "Paused") {
              $actionMask |= CRM_Core_Action::REOPEN;
            }
            else {
              $actionMask |= CRM_Core_Action::CLOSE;
            }
          }
          if ($row['status'] === 'Scheduled' &&
            empty($row['approval_status_id'])
          ) {
            if ($workFlow && ($allAccess || $showApprovalLinks)) {
              $actionMask |= CRM_Core_Action::ENABLE;
            }
          }
        }

        if (in_array($row['status'], ['Complete', 'Canceled']) &&
          !$row['archived']
        ) {
          if ($allAccess || $showCreateLinks) {
            $actionMask |= CRM_Core_Action::RENEW;
          }
        }

        if ($allAccess || $showCreateLinks) {
          $actionMask |= CRM_Core_Action::UPDATE;
        }

        // check for delete permission.
        if ($allowToDelete) {
          $actionMask |= CRM_Core_Action::DELETE;
        }

        if ($actionMask == NULL) {
          $actionMask = CRM_Core_Action::ADD;
        }
        // get status strings as per locale settings CRM-4411.
        $rows[$key]['status'] = CRM_Mailing_BAO_MailingJob::status($row['status']);

        // get language string
        $rows[$key]['language'] = (isset($row['language']) ? $languages[$row['language']] : NULL);

        $validLinks = $actionLinks;
        if (($mailingUrl = CRM_Mailing_BAO_Mailing::getPublicViewUrl($row['id'])) != FALSE) {
          $validLinks[CRM_Core_Action::BROWSE] = [
            'name' => ts('Public View'),
            'url' => 'civicrm/mailing/view',
            'qs' => 'id=%%hashOrMid%%&reset=1',
            'title' => ts('Public View'),
            'fe' => TRUE,
            'weight' => 150,
          ];
          $actionMask |= CRM_Core_Action::BROWSE;
        }

        $hash = CRM_Mailing_BAO_Mailing::getMailingHash($row['id']);
        $rows[$key]['action'] = CRM_Core_Action::formLink(
          $validLinks,
          $actionMask,
          [
            'mid' => $row['id'],
            'hashOrMid' => $hash ?: $row['id'],
          ],
          'more',
          FALSE,
          $opString,
          "Mailing",
          $row['id']
        );

        // unset($rows[$key]['id']);
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
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('CiviMail Mailings');
  }

  /**
   * @param $parent
   */
  public function setParent($parent) {
    $this->_parent = $parent;
  }

  /**
   * @param array $params
   * @param bool $sortBy
   *
   * @return int|string
   */
  public function whereClause(&$params, $sortBy = TRUE) {
    $values = $clauses = [];
    $isFormSubmitted = $this->_parent->get('hidden_find_mailings');

    $title = $this->_parent->get('mailing_name');
    if ($title) {
      $clauses[] = 'name LIKE %1';
      if (str_contains($title, '%')) {
        $params[1] = [$title, 'String', FALSE];
      }
      else {
        $params[1] = [$title, 'String', TRUE];
      }
    }

    $dateClause1 = $dateClause2 = [];
    $from = $this->_parent->get('mailing_low');
    if (!CRM_Utils_System::isNull($from)) {
      if ($this->_parent->get('unscheduled')) {
        $dateClause1[] = 'civicrm_mailing.created_date >= %2';
      }
      else {
        $dateClause1[] = 'civicrm_mailing_job.start_date >= %2';
        $dateClause2[] = 'civicrm_mailing_job.scheduled_date >= %2';
      }
      $params[2] = [$from, 'String'];
    }

    $to = $this->_parent->get('mailing_high');
    if (!CRM_Utils_System::isNull($to)) {
      if ($this->_parent->get('unscheduled')) {
        $dateClause1[] = ' civicrm_mailing.created_date <= %3 ';
      }
      else {
        $dateClause1[] = 'civicrm_mailing_job.start_date <= %3';
        $dateClause2[] = 'civicrm_mailing_job.scheduled_date <= %3';
      }
      $params[3] = [$to, 'String'];
    }

    $dateClauses = [];
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
    $isDraft = $this->_parent->get('status_unscheduled');
    $isArchived = $this->_parent->get('is_archived');
    $mailingStatus = $this->_parent->get('mailing_status');

    if (!$isFormSubmitted && $this->_parent->get('scheduled')) {
      // mimic default behavior for scheduled screen
      $isArchived = 0;
      $mailingStatus = ['Scheduled' => 1, 'Complete' => 1, 'Running' => 1, 'Paused' => 1, 'Canceled' => 1];
    }
    if (!$isFormSubmitted && $this->_parent->get('archived')) {
      // mimic default behavior for archived screen
      $isArchived = 1;
    }
    if (!$isFormSubmitted && $this->_parent->get('unscheduled')) {
      // mimic default behavior for draft screen
      $isDraft = 1;
    }

    $statusClauses = [];
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
      }
      else {
        $clauses[] = "civicrm_mailing.is_archived = 0";
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
      $params[4] = ['%' . $createOrSentBy . '%', 'String'];
    }

    $createdId = $this->_parent->get('createdId');
    if ($createdId) {
      $clauses[] = "(created_id = {$createdId})";
      $params[5] = [$createdId, 'Integer'];
    }

    $campaignIds = $this->_parent->get('campaign_id');
    if (!CRM_Utils_System::isNull($campaignIds)) {
      if (!is_array($campaignIds)) {
        $campaignIds = [$campaignIds];
      }
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', array_values($campaignIds)) . ' ) )';
    }

    if ($language = $this->_parent->get('language')) {
      $clauses[] = "civicrm_mailing.language = %6";
      $params[6] = [$language, 'String'];
    }

    if (empty($clauses)) {
      return 1;
    }

    return implode(' AND ', $clauses);
  }

  public function pagerAtoZ() {

    $params = [];
    $whereClause = $this->whereClause($params, FALSE);

    $query = "
SELECT DISTINCT UPPER(LEFT(name, 1)) as sort_name
FROM civicrm_mailing
LEFT JOIN civicrm_mailing_job ON (civicrm_mailing_job.mailing_id = civicrm_mailing.id)
LEFT JOIN civicrm_contact createdContact ON ( civicrm_mailing.created_id = createdContact.id )
LEFT JOIN civicrm_contact scheduledContact ON ( civicrm_mailing.scheduled_id = scheduledContact.id )
WHERE $whereClause
ORDER BY UPPER(LEFT(name, 1))
";

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_parent->_sortByCharacter, TRUE);
    $this->_parent->assign('aToZ', $aToZBar);
  }

}
