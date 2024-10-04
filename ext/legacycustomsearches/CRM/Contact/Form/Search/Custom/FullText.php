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
class CRM_Contact_Form_Search_Custom_FullText extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  const LIMIT = 10;

  /**
   * @var CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery[]
   */
  protected $_partialQueries = NULL;

  protected $_formValues;

  protected $_columns;

  protected $_text = NULL;

  protected $_table;

  protected $tableName;

  /**
   * @return mixed
   */
  public function getTableName() {
    return $this->tableName;
  }

  protected $_entityIDTableName = NULL;

  protected $_tableFields = NULL;

  /**
   * Limit clause.
   *
   * NULL if no limit; or array(0 => $limit, 1 => $offset).
   *
   * @var array|null
   */
  protected $_limitClause = NULL;

  /**
   * Limit row clause.
   *
   * NULL if no limit; or array(0 => $limit, 1 => $offset)
   *
   * @var array|null
   */
  protected $_limitRowClause;

  /**
   * Limit detail clause.
   *
   * NULL if no limit; or array(0 => $limit, 1 => $offset).
   *
   * @var array|null
   */
  protected $_limitDetailClause = NULL;

  protected $_limitNumber = 10;

  /**
   * This should be one more than self::LIMIT.
   *
   * @var int
   */
  protected $_limitNumberPlus1 = 11;

  protected $_foundRows = [];

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_partialQueries = [
      new CRM_Contact_Form_Search_Custom_FullText_Contact(),
      new CRM_Contact_Form_Search_Custom_FullText_Activity(),
      new CRM_Contact_Form_Search_Custom_FullText_Case(),
      new CRM_Contact_Form_Search_Custom_FullText_Contribution(),
      new CRM_Contact_Form_Search_Custom_FullText_Participant(),
      new CRM_Contact_Form_Search_Custom_FullText_Membership(),
    ];

    $formValues['table'] = $this->getFieldValue($formValues, 'table', 'String');
    $this->_table = $formValues['table'];

    $formValues['text'] = trim($this->getFieldValue($formValues, 'text', 'String', '') ?? '');
    $this->_text = $formValues['text'];

    if (!$this->_table) {
      $this->_limitClause = [$this->_limitNumberPlus1, NULL];
      $this->_limitRowClause = $this->_limitDetailClause = [$this->_limitNumber, NULL];
    }
    else {
      // when there is table specified, we would like to use the pager. But since
      // 1. this custom search has slightly different structure ,
      // 2. we are in constructor right now,
      // we 'll use a small hack -
      $rowCount = $_REQUEST['crmRowCount'] ?? Civi::settings()->get('default_pager_size');
      $pageId = $_REQUEST['crmPID'] ?? 1;
      $offset = ($pageId - 1) * $rowCount;
      $this->_limitClause = NULL;
      $this->_limitRowClause = [$rowCount, NULL];
      $this->_limitDetailClause = [$rowCount, $offset];
    }

    $this->_formValues = $formValues;
  }

  /**
   * Get a value from $formValues. If missing, get it from the request.
   *
   * @param array $formValues
   * @param string $field
   * @param $type
   * @param null $default
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
   */
  public function getFieldValue(array $formValues, string $field, $type, $default = NULL) {
    $value = $formValues[$field] ?? NULL;
    if (!$value) {
      return CRM_Utils_Request::retrieve($field, $type, NULL, FALSE, $default);
    }
    return $value;
  }

  public function __destruct() {
  }

  public function initialize() {
    static $initialized = FALSE;

    if (!$initialized) {
      $initialized = TRUE;

      $this->buildTempTable();

      $this->fillTable();
    }
  }

  public function buildTempTable(): void {
    $table = CRM_Utils_SQL_TempTable::build()->setCategory('custom')->setMemory();
    $this->tableName = $table->getName();

    $this->_tableFields = [
      'id' => 'int unsigned NOT NULL AUTO_INCREMENT',
      'table_name' => 'varchar(16)',
      'contact_id' => 'int unsigned',
      'sort_name' => 'varchar(128)',
      'display_name' => 'varchar(128)',
      'assignee_contact_id' => 'int unsigned',
      'assignee_sort_name' => 'varchar(128)',
      'target_contact_id' => 'int unsigned',
      'target_sort_name' => 'varchar(128)',
      'activity_id' => 'int unsigned',
      'activity_type_id' => 'int unsigned',
      'record_type' => 'varchar(16)',
      'client_id' => 'int unsigned',
      'case_id' => 'int unsigned',
      'case_start_date' => 'datetime',
      'case_end_date' => 'datetime',
      'case_is_deleted' => 'tinyint',
      'subject' => 'varchar(255)',
      'details' => 'varchar(255)',
      'contribution_id' => 'int unsigned',
      'financial_type' => 'varchar(255)',
      'contribution_page' => 'varchar(255)',
      'contribution_receive_date' => 'datetime',
      'contribution_total_amount' => 'decimal(20,2)',
      'contribution_trxn_Id' => 'varchar(255)',
      'contribution_source' => 'varchar(255)',
      'contribution_status' => 'varchar(255)',
      'contribution_check_number' => 'varchar(255)',
      'participant_id' => 'int unsigned',
      'event_title' => 'varchar(255)',
      'participant_fee_level' => 'varchar(255)',
      'participant_fee_amount' => 'int unsigned',
      'participant_source' => 'varchar(255)',
      'participant_register_date' => 'datetime',
      'participant_status' => 'varchar(255)',
      'participant_role' => 'varchar(255)',
      'membership_id' => 'int unsigned',
      'membership_fee' => 'int unsigned',
      'membership_type' => 'varchar(255)',
      'membership_start_date' => 'datetime',
      'membership_end_date' => 'datetime',
      'membership_source' => 'varchar(255)',
      'membership_status' => 'varchar(255)',
      // We may have multiple files to list on one record.
      // The temporary-table approach can't store full details for all of them
      // comma-separate id listing
      'file_ids' => 'varchar(255)',
    ];

    $sql = "
";

    foreach ($this->_tableFields as $name => $desc) {
      $sql .= "$name $desc,\n";
    }

    $sql .= '
  PRIMARY KEY ( id )
';
    $table->createWithColumns($sql);

    $entityIdTable = CRM_Utils_SQL_TempTable::build()->setCategory('custom')->setMemory();
    $this->_entityIDTableName = $entityIdTable->getName();
    $sql = '
  id int unsigned NOT NULL AUTO_INCREMENT,
  entity_id int unsigned NOT NULL,

  UNIQUE INDEX unique_entity_id ( entity_id ),
  PRIMARY KEY ( id )
';
    $entityIdTable->createWithColumns($sql);
  }

  public function fillTable(): void {
    /** @var CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery $partialQuery */
    foreach ($this->_partialQueries as $partialQuery) {
      if (!$this->_table || $this->_table == $partialQuery->getName()) {
        if ($partialQuery->isActive()) {
          $result = $partialQuery->fillTempTable($this->_text, $this->_entityIDTableName, $this->tableName, $this->_limitClause, $this->_limitDetailClause);
          $this->_foundRows[$partialQuery->getName()] = $result['count'];
        }
      }
    }

    $this->filterACLContacts();
  }

  public function filterACLContacts(): void {
    if (CRM_Core_Permission::check('view all contacts')) {
      CRM_Core_DAO::executeQuery("DELETE FROM {$this->tableName} WHERE contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)");
      return;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }

    CRM_Contact_BAO_Contact_Permission::cache($contactID);

    $params = [1 => [$contactID, 'Integer']];

    $sql = "
DELETE     t.*
FROM       {$this->tableName} t
WHERE      NOT EXISTS ( SELECT c.contact_id
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND t.contact_id = c.contact_id )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.contact_id
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.target_contact_id = c.contact_id OR t.target_contact_id IS NULL ) )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.contact_id
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.assignee_contact_id = c.contact_id OR t.assignee_contact_id IS NULL ) )
";
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $config = CRM_Core_Config::singleton();

    $form->applyFilter('__ALL__', 'trim');
    $form->add('text', 'text', ts('Find'), NULL, TRUE);

    $form->assign('hasAllACLs', CRM_Core_Permission::giveMeAllACLs());

    // also add a select box to allow the search to be constrained
    $tables = ['' => ts('All tables')];
    /** @var CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery $partialQuery */
    foreach ($this->_partialQueries as $partialQuery) {
      if ($partialQuery->isActive()) {
        $tables[$partialQuery->getName()] = $partialQuery->getLabel();
      }
    }

    $form->add('select', 'table', ts('in...'), $tables);

    $form->assign('csID', $form->get('csid'));

    // also add the limit constant
    $form->assign('limit', self::LIMIT);

    // set form defaults
    $form->assign('table', '');
    if (!empty($form->_formValues)) {
      $defaults = [];

      if (isset($form->_formValues['text'])) {
        $defaults['text'] = $form->_formValues['text'];
      }

      if (isset($form->_formValues['table'])) {
        $defaults['table'] = $form->_formValues['table'];
        $form->assign('table', $form->_formValues['table']);
      }

      $form->setDefaults($defaults);
    }

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Full-text Search'));

    $searchService = CRM_Core_BAO_File::getSearchService();
    $form->assign('allowFileSearch', !empty($searchService) && CRM_Core_Permission::check('access uploaded files'));
  }

  /**
   * @return array
   */
  public function &columns() {
    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Name') => 'sort_name',
    ];

    return $this->_columns;
  }

  /**
   * @return array
   */
  public function summary() {
    $this->initialize();

    $summary = [];
    /** @var CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery $partialQuery */
    foreach ($this->_partialQueries as $partialQuery) {
      $summary[$partialQuery->getName()] = [];
    }

    // now iterate through the table and add entries to the relevant section
    $sql = "SELECT * FROM {$this->tableName}";
    if ($this->_table) {
      $sql .= " {$this->toLimit($this->_limitRowClause)} ";
    }
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_tableFields as $name => $dontCare) {
        if ($name !== 'activity_type_id') {
          $row[$name] = $dao->$name;
        }
        else {
          $row['activity_type'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $dao->$name);
        }
      }
      if (isset($row['participant_role'])) {
        $participantRole = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['participant_role']);
        $viewRoles = [];
        foreach ($participantRole as $v) {
          $viewRoles[] = CRM_Core_PseudoConstant::getLabel('CRM_Event_BAO_Participant', 'role_id', $v);
        }
        $row['participant_role'] = implode(', ', $viewRoles);
      }
      if (!empty($row['file_ids'])) {
        $fileIds = (explode(',', $row['file_ids']));
        $fileHtml = '';
        foreach ($fileIds as $fileId) {
          $paperclip = CRM_Core_BAO_File::paperIconAttachment('*', $fileId);
          if ($paperclip) {
            $fileHtml .= implode('', $paperclip);
          }
        }
        $row['fileHtml'] = $fileHtml;
      }
      $summary[$dao->table_name][] = $row;
    }

    $summary['Count'] = [];
    foreach (array_keys($summary) as $table) {
      $summary['Count'][$table] = $this->_foundRows[$table] ?? NULL;
      if ($summary['Count'][$table] >= self::LIMIT) {
        $summary['addShowAllLink'][$table] = TRUE;
      }
      else {
        $summary['addShowAllLink'][$table] = FALSE;
      }
    }

    return $summary;
  }

  /**
   * @return null|string
   */
  public function count() {
    $this->initialize();

    if ($this->_table) {
      return $this->_foundRows[$this->_table];
    }
    else {
      return CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM {$this->tableName}");
    }
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL
   *
   * @return null|string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    $this->initialize();
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $this->initialize();

    if ($justIDs) {
      $select = "contact_a.id as contact_id";
    }
    else {
      $select = "
  contact_a.contact_id   as contact_id  ,
  contact_a.sort_name as sort_name
";
    }

    $sql = "
SELECT $select
FROM   {$this->tableName} contact_a
       {$this->toLimit($this->_limitRowClause)}
";
    return $sql;
  }

  /**
   * @return null
   */
  public function from() {
    return NULL;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return null
   */
  public function where($includeContactIDs = FALSE) {
    return NULL;
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/FullText.tpl';
  }

  /**
   * @return array
   */
  public function setDefaultValues(): array {
    return [];
  }

  /**
   * @param $row
   */
  public function alterRow(&$row) {
  }

  /**
   * @param int|array $limit
   * @return string
   *   SQL
   * @see CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery::toLimit
   */
  public function toLimit($limit): string {
    if (is_array($limit)) {
      [$limit, $offset] = $limit;
    }
    if (empty($limit)) {
      return '';
    }
    $result = "LIMIT {$limit}";
    if ($offset) {
      $result .= " OFFSET $offset";
    }
    return $result;
  }

}
