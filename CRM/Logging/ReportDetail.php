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
class CRM_Logging_ReportDetail extends CRM_Report_Form {

  const ROW_COUNT_LIMIT = 50;
  protected $cid;

  /**
   * Other contact ID.
   *
   * This would be set if we are viewing a merge of 2 contacts.
   *
   * @var int
   */
  protected $oid;
  protected $db;
  protected $log_conn_id;
  protected $log_date;
  protected $raw;
  protected $tables = [];
  protected $interval = '10 SECOND';

  protected $altered_name;
  protected $altered_by;
  protected $altered_by_id;

  /**
   * detail/summary report ids
   * @var int
   */
  protected $detail;
  protected $summary;

  /**
   * Instance of Differ.
   *
   * @var CRM_Logging_Differ
   */
  protected $differ;

  /**
   * Array of changes made.
   *
   * @var array
   */
  protected $diffs = [];

  /**
   * Don't display the Add these contacts to Group button.
   *
   * @var bool
   */
  protected $_add2groupSupported = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {

    $this->storeDB();

    $this->parsePropertiesFromUrl();

    parent::__construct();

    CRM_Utils_System::resetBreadCrumb();
    $breadcrumb = [
      [
        'title' => ts('Home'),
        'url' => CRM_Utils_System::url(),
      ],
      [
        'title' => ts('CiviCRM'),
        'url' => CRM_Utils_System::url('civicrm', 'reset=1'),
      ],
      [
        'title' => ts('View Contact'),
        'url' => CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->cid}"),
      ],
      [
        'title' => ts('Search Results'),
        'url' => CRM_Utils_System::url('civicrm/contact/search', "force=1"),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    if (CRM_Utils_Request::retrieve('revert', 'Boolean')) {
      $this->revert();
    }

    $this->_columnHeaders = [
      'field' => ['title' => ts('Field')],
      'from' => ['title' => ts('Changed From')],
      'to' => ['title' => ts('Changed To')],
    ];
  }

  /**
   * Build query for report.
   *
   * We override this to be empty & calculate the rows in the buildRows function.
   *
   * @param bool $applyLimit
   */
  public function buildQuery($applyLimit = TRUE) {
  }

  /**
   * Build rows from query.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    // safeguard for when there aren’t any log entries yet
    if (!$this->log_conn_id && !$this->log_date) {
      return;
    }
    $this->getDiffs();
    $rows = $this->convertDiffsToRows();
  }

  /**
   * Get the diffs for the report, calculating them if not already done.
   *
   * Note that contact details report now uses a more comprehensive method but
   * the contribution logging details report still uses this.
   *
   * @return array
   */
  protected function getDiffs() {
    if (empty($this->diffs)) {
      foreach ($this->tables as $table) {
        $this->diffs = array_merge($this->diffs, $this->diffsInTable($table));
      }
    }
    return $this->diffs;
  }

  /**
   * @param $table
   *
   * @return array
   */
  protected function diffsInTable($table) {
    $this->setDiffer();
    return $this->differ->diffsInTable($table, $this->cid);
  }

  /**
   * Convert the diffs to row format.
   *
   * @return array
   */
  protected function convertDiffsToRows() {
    // return early if nothing found
    if (empty($this->diffs)) {
      return [];
    }

    // populate $rows with only the differences between $changed and $original (skipping certain columns and NULL ↔ empty changes unless raw requested)
    $skipped = ['id'];
    $nRows = $rows = [];
    foreach ($this->diffs as $diff) {
      $table = $diff['table'];
      if (empty($metadata[$table])) {
        list($metadata[$table]['titles'], $metadata[$table]['values']) = $this->differ->titlesAndValuesForTable($table, $diff['log_date']);
      }
      $values = CRM_Utils_Array::value('values', $metadata[$diff['table']], []);
      $titles = $metadata[$diff['table']]['titles'];
      $field = $diff['field'];
      $from = $diff['from'];
      $to = $diff['to'];

      if ($this->raw) {
        $field = "$table.$field";
      }
      else {
        if (in_array($field, $skipped)) {
          continue;
        }
        // $differ filters out === values; for presentation hide changes like 42 → '42'
        if ($from == $to) {
          continue;
        }

        // special-case for multiple values. Also works for CRM-7251: preferred_communication_method
        if ((substr($from, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR &&
            substr($from, -1, 1) == CRM_Core_DAO::VALUE_SEPARATOR) ||
          (substr($to, 0, 1) == CRM_Core_DAO::VALUE_SEPARATOR &&
            substr($to, -1, 1) == CRM_Core_DAO::VALUE_SEPARATOR)
        ) {
          $froms = $tos = [];
          foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($from, CRM_Core_DAO::VALUE_SEPARATOR)) as $val) {
            $froms[] = $values[$field][$val] ?? NULL;
          }
          foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($to, CRM_Core_DAO::VALUE_SEPARATOR)) as $val) {
            $tos[] = $values[$field][$val] ?? NULL;
          }
          $from = implode(', ', array_filter($froms));
          $to = implode(', ', array_filter($tos));
        }

        if (isset($values[$field][$from])) {
          $from = $values[$field][$from];
        }
        if (isset($values[$field][$to])) {
          $to = $values[$field][$to];
        }
        if (isset($titles[$field])) {
          $field = $titles[$field];
        }
        if ($diff['action'] == 'Insert') {
          $from = '';
        }
        if ($diff['action'] == 'Delete') {
          $to = '';
        }
      }
      // Rework the results to provide grouping based on the ID
      // We don't need that field displayed so we will output empty
      if ($field == 'Modified Date') {
        $nRows[$diff['id']][] = ['field' => '', 'from' => $from, 'to' => $to];
      }
      else {
        $nRows[$diff['id']][] = ['field' => $field . " (id: {$diff['id']})", 'from' => $from, 'to' => $to];
      }
    }
    // Transform the output so that we can compact the changes into the proper amount of rows IF trData is holding more than 1 array
    foreach ($nRows as $trData) {
      if (count($trData) > 1) {
        $keys = array_intersect(...array_map('array_keys', $trData));
        $mergedRes = array_combine($keys, array_map(function ($key) use ($trData) {
          // If more than 1 entry is found, we are assigning them as subarrays, then the tpls will be responsible for concatenating the results
          return array_column($trData, $key);
        }, $keys));
        $rows[] = $mergedRes;
      }
      else {
        // We always need the first row of that array
        $rows[] = $trData[0];
      }

    }
    return $rows;
  }

  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->assign('whom_url', CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->cid}"));
    $this->assign('who_url', CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$this->altered_by_id}"));
    $this->assign('whom_name', $this->altered_name);
    $this->assign('who_name', $this->altered_by);

    $this->assign('log_date', CRM_Utils_Date::mysqlToIso($this->log_date));

    $q = "reset=1&log_conn_id={$this->log_conn_id}&log_date={$this->log_date}";
    if ($this->oid) {
      $q .= '&oid=' . $this->oid;
    }
    $this->assign('revertURL', CRM_Report_Utils_Report::getNextUrl($this->detail, "$q&revert=1", FALSE, TRUE));
    $this->assign('revertConfirm', ts('Are you sure you want to revert all changes?'));
  }

  /**
   * Store the dsn for the logging database in $this->db.
   */
  protected function storeDB() {
    $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
    $dsn = DB::parseDSN($dsn);
    $this->db = $dsn['database'];
  }

  /**
   * Calculate all the contact related diffs for the change.
   */
  protected function calculateContactDiffs() {
    $this->_rowsFound = $this->getCountOfAllContactChangesForConnection();
    // Apply some limits before asking for all contact changes
    $this->getLimit();
    $this->diffs = $this->getAllContactChangesForConnection();
  }

  /**
   * Get an array of changes made in the mysql connection.
   *
   * @return mixed
   */
  public function getAllContactChangesForConnection() {
    if (empty($this->log_conn_id)) {
      return [];
    }
    $this->setDiffer();
    try {
      return $this->differ->getAllChangesForConnection($this->tables, $this->dblimit, $this->dboffset);
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage());
    }
  }

  /**
   * Get an count of contacts with changes.
   *
   * @return mixed
   */
  public function getCountOfAllContactChangesForConnection() {
    if (empty($this->log_conn_id)) {
      return [];
    }
    $this->setDiffer();
    try {
      return $this->differ->getCountOfAllContactChangesForConnection($this->tables);
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage());
    }
  }

  /**
   * Make sure the differ is defined.
   */
  protected function setDiffer() {
    if (empty($this->differ)) {
      $this->differ = new CRM_Logging_Differ($this->log_conn_id, $this->log_date, $this->interval);
    }
  }

  /**
   * Set this tables to reflect tables changed in a merge.
   */
  protected function setTablesToContactRelatedTables() {
    $schema = new CRM_Logging_Schema();
    $this->tables = $schema->getLogTablesForContact();
    // allow tables to be extended by report hook query objects.
    // This is a report specific hook. It's unclear how it interacts to / overlaps the main one.
    // It probably precedes the main one and was never reconciled with it....
    CRM_Report_BAO_Hook::singleton()->alterLogTables($this, $this->tables);
  }

  /**
   * Revert the changes defined by the parameters.
   */
  protected function revert() {
    $reverter = new CRM_Logging_Reverter($this->log_conn_id, $this->log_date);
    $reverter->calculateDiffsFromLogConnAndDate($this->tables);
    $reverter->revert();
    CRM_Core_Session::setStatus(ts('The changes have been reverted.'), ts('Reverted'), 'success');
    if ($this->cid) {
      if ($this->oid) {
        CRM_Utils_System::redirect(CRM_Utils_System::url(
          'civicrm/contact/merge',
          "reset=1&cid={$this->cid}&oid={$this->oid}",
          FALSE,
          NULL,
          FALSE)
        );
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=log&cid={$this->cid}", FALSE, NULL, FALSE));
      }
    }
    else {
      CRM_Utils_System::redirect(CRM_Report_Utils_Report::getNextUrl($this->summary, 'reset=1', FALSE, TRUE));
    }
  }

  /**
   * Get the properties that might be in the URL.
   */
  protected function parsePropertiesFromUrl() {
    $this->log_conn_id = CRM_Utils_Request::retrieve('log_conn_id', 'String');
    $this->log_date = CRM_Utils_Request::retrieve('log_date', 'String');
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    $this->raw = CRM_Utils_Request::retrieve('raw', 'Boolean');

    $this->altered_name = CRM_Utils_Request::retrieve('alteredName', 'String');
    $this->altered_by = CRM_Utils_Request::retrieve('alteredBy', 'String');
    $this->altered_by_id = CRM_Utils_Request::retrieve('alteredById', 'Integer');
    $this->layout = CRM_Utils_Request::retrieve('layout', 'String');
  }

  /**
   * Override to set limit
   * @param int $rowCount
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    parent::limit($rowCount);
  }

  /**
   * Override to set pager with limit
   * @param int $rowCount
   */
  public function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    // We should not be rendering the pager in overlay mode
    if (!isset($this->layout)) {
      $this->_dashBoardRowCount = $rowCount;
      $this->_limit = TRUE;
      parent::setPager($rowCount);
    }
  }

  /**
   * This is a function similar to limit, in fact we copied it as-is and removed
   * some `set` statements
   *
   */
  public function getLimit($rowCount = self::ROW_COUNT_LIMIT) {
    if ($this->addPaging) {

      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer');

      // @todo all http vars should be extracted in the preProcess
      // - not randomly in the class
      if (!$pageId && !empty($_POST)) {
        if (isset($_POST['PagerBottomButton']) && isset($_POST['crmPID_B'])) {
          $pageId = max((int) $_POST['crmPID_B'], 1);
        }
        elseif (isset($_POST['PagerTopButton']) && isset($_POST['crmPID'])) {
          $pageId = max((int) $_POST['crmPID'], 1);
        }
        unset($_POST['crmPID_B'], $_POST['crmPID']);
      }

      $pageId = $pageId ? $pageId : 1;
      $offset = ($pageId - 1) * $rowCount;

      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');
      $this->_limit = " LIMIT $offset, $rowCount";
      $this->dblimit = $rowCount;
      $this->dboffset = $offset;
    }
  }

}
