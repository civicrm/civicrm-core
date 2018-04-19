<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Contact_Form_Search_Custom_FullText extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  const LIMIT = 10;

  /**
   * @var array CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery
   */
  protected $_partialQueries = NULL;

  protected $_formValues;

  protected $_columns;

  protected $_text = NULL;

  protected $_table = NULL;

  protected $_tableName = NULL;

  protected $_entityIDTableName = NULL;

  protected $_tableFields = NULL;

  /**
   * @var array|null NULL if no limit; or array(0 => $limit, 1 => $offset)
   */
  protected $_limitClause = NULL;

  /**
   * @var array|null NULL if no limit; or array(0 => $limit, 1 => $offset)
   */
  protected $_limitRowClause = NULL;

  /**
   * @var array|null NULL if no limit; or array(0 => $limit, 1 => $offset)
   */
  protected $_limitDetailClause = NULL;

  protected $_limitNumber = 10;
  protected $_limitNumberPlus1 = 11; // this should be one more than self::LIMIT

  protected $_foundRows = array();

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_partialQueries = array(
      new CRM_Contact_Form_Search_Custom_FullText_Contact(),
      new CRM_Contact_Form_Search_Custom_FullText_Activity(),
      new CRM_Contact_Form_Search_Custom_FullText_Case(),
      new CRM_Contact_Form_Search_Custom_FullText_Contribution(),
      new CRM_Contact_Form_Search_Custom_FullText_Participant(),
      new CRM_Contact_Form_Search_Custom_FullText_Membership(),
    );

    $formValues['table'] = $this->getFieldValue($formValues, 'table', 'String');
    $this->_table = $formValues['table'];

    $formValues['text'] = trim($this->getFieldValue($formValues, 'text', 'String', ''));
    $this->_text = $formValues['text'];

    if (!$this->_table) {
      $this->_limitClause = array($this->_limitNumberPlus1, NULL);
      $this->_limitRowClause = $this->_limitDetailClause = array($this->_limitNumber, NULL);
    }
    else {
      // when there is table specified, we would like to use the pager. But since
      // 1. this custom search has slightly different structure ,
      // 2. we are in constructor right now,
      // we 'll use a small hack -
      $rowCount = CRM_Utils_Array::value('crmRowCount', $_REQUEST, CRM_Utils_Pager::ROWCOUNT);
      $pageId = CRM_Utils_Array::value('crmPID', $_REQUEST, 1);
      $offset = ($pageId - 1) * $rowCount;
      $this->_limitClause = NULL;
      $this->_limitRowClause = array($rowCount, NULL);
      $this->_limitDetailClause = array($rowCount, $offset);
    }

    $this->_formValues = $formValues;
  }

  /**
   * Get a value from $formValues. If missing, get it from the request.
   *
   * @param $formValues
   * @param $field
   * @param $type
   * @param null $default
   * @return mixed|null
   */
  public function getFieldValue($formValues, $field, $type, $default = NULL) {
    $value = CRM_Utils_Array::value($field, $formValues);
    if (!$value) {
      return CRM_Utils_Request::retrieve($field, $type, CRM_Core_DAO::$_nullObject, FALSE, $default);
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

  public function buildTempTable() {
    $randomNum = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_details_{$randomNum}";

    $this->_tableFields = array(
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
      'file_ids' => 'varchar(255)', // comma-separate id listing
    );

    $sql = "
CREATE TEMPORARY TABLE {$this->_tableName} (
";

    foreach ($this->_tableFields as $name => $desc) {
      $sql .= "$name $desc,\n";
    }

    $sql .= "
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
";
    CRM_Core_DAO::executeQuery($sql);

    $this->_entityIDTableName = "civicrm_temp_custom_entityID_{$randomNum}";
    $sql = "
CREATE TEMPORARY TABLE {$this->_entityIDTableName} (
  id int unsigned NOT NULL AUTO_INCREMENT,
  entity_id int unsigned NOT NULL,

  UNIQUE INDEX unique_entity_id ( entity_id ),
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
";
    CRM_Core_DAO::executeQuery($sql);

    if (!empty($this->_formValues['is_unit_test'])) {
      $this->_tableNameForTest = $this->_tableName;
    }
  }

  public function fillTable() {
    foreach ($this->_partialQueries as $partialQuery) {
      /** @var $partialQuery CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery */
      if (!$this->_table || $this->_table == $partialQuery->getName()) {
        if ($partialQuery->isActive()) {
          $result = $partialQuery->fillTempTable($this->_text, $this->_entityIDTableName, $this->_tableName, $this->_limitClause, $this->_limitDetailClause);
          $this->_foundRows[$partialQuery->getName()] = $result['count'];
        }
      }
    }

    $this->filterACLContacts();
  }

  public function filterACLContacts() {
    if (CRM_Core_Permission::check('view all contacts')) {
      CRM_Core_DAO::executeQuery("DELETE FROM {$this->_tableName} WHERE contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)");
      return;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }

    CRM_Contact_BAO_Contact_Permission::cache($contactID);

    $params = array(1 => array($contactID, 'Integer'));

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      NOT EXISTS ( SELECT c.contact_id
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND t.contact_id = c.contact_id )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.contact_id
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.target_contact_id = c.contact_id OR t.target_contact_id IS NULL ) )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
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
    $form->add('text',
      'text',
      ts('Find'),
      TRUE
    );

    // also add a select box to allow the search to be constrained
    $tables = array('' => ts('All tables'));
    foreach ($this->_partialQueries as $partialQuery) {
      /** @var $partialQuery CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery */
      if ($partialQuery->isActive()) {
        $tables[$partialQuery->getName()] = $partialQuery->getLabel();
      }
    }

    $form->add('select', 'table', ts('Tables'), $tables);

    $form->assign('csID', $form->get('csid'));

    // also add the limit constant
    $form->assign('limit', self::LIMIT);

    // set form defaults
    if (!empty($form->_formValues)) {
      $defaults = array();

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
    $this->_columns = array(
      ts('Contact ID') => 'contact_id',
      ts('Name') => 'sort_name',
    );

    return $this->_columns;
  }

  /**
   * @return array
   */
  public function summary() {
    $this->initialize();

    $summary = array();
    foreach ($this->_partialQueries as $partialQuery) {
      /** @var $partialQuery CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery */
      $summary[$partialQuery->getName()] = array();
    }

    // now iterate through the table and add entries to the relevant section
    $sql = "SELECT * FROM {$this->_tableName}";
    if ($this->_table) {
      $sql .= " {$this->toLimit($this->_limitRowClause)} ";
    }
    $dao = CRM_Core_DAO::executeQuery($sql);

    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    $roleIds = CRM_Event_PseudoConstant::participantRole();
    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_tableFields as $name => $dontCare) {
        if ($name != 'activity_type_id') {
          $row[$name] = $dao->$name;
        }
        else {
          $row['activity_type'] = CRM_Utils_Array::value($dao->$name, $activityTypes);
        }
      }
      if (isset($row['participant_role'])) {
        $participantRole = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['participant_role']);
        $viewRoles = array();
        foreach ($participantRole as $v) {
          $viewRoles[] = $roleIds[$v];
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

    $summary['Count'] = array();
    foreach (array_keys($summary) as $table) {
      $summary['Count'][$table] = CRM_Utils_Array::value($table, $this->_foundRows);
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
      return CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM {$this->_tableName}");
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

    if ($returnSQL) {
      return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
    }
    else {
      return CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM {$this->_tableName}");
    }
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
FROM   {$this->_tableName} contact_a
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
  public function setDefaultValues() {
    return array();
  }

  /**
   * @param $row
   */
  public function alterRow(&$row) {
  }

  /**
   * @param $title
   */
  public function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
  }

  /**
   * @param int|array $limit
   * @return string
   *   SQL
   * @see CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery::toLimit
   */
  public function toLimit($limit) {
    if (is_array($limit)) {
      list ($limit, $offset) = $limit;
    }
    if (empty($limit)) {
      return '';
    }
    $result = "LIMIT {$limit}";
    if ($offset) {
      $result .= " OFFSET {$offset}";
    }
    return $result;
  }

}
