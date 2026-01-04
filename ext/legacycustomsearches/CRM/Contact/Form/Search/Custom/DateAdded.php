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
class CRM_Contact_Form_Search_Custom_DateAdded extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  protected $_datesTable = NULL;
  protected $_xgTable = NULL;
  protected $_igTable = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = self::formatSavedSearchFields($formValues);

    $this->_includeGroups = $formValues['includeGroups'] ?? [];
    $this->_excludeGroups = $formValues['excludeGroups'] ?? [];

    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Date Added') => 'date_added',
    ];
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    $form->add('datepicker', 'start_date', ts('Start Date'), [], FALSE, ['time' => FALSE]);
    $form->add('datepicker', 'end_date', ts('End Date'), [], FALSE, ['time' => FALSE]);

    $groups = CRM_Core_PseudoConstant::nestedGroup();

    $select2style = [
      'multiple' => TRUE,
      'style' => 'width: 100%; max-width: 60em;',
      'class' => 'crm-select2',
      'placeholder' => ts('- select -'),
    ];

    $form->add('select', 'includeGroups',
      ts('Include Group(s)'),
      $groups,
      FALSE,
      $select2style
    );

    $form->add('select', 'excludeGroups',
      ts('Exclude Group(s)'),
      $groups,
      FALSE,
      $select2style
    );

    $this->setTitle(ts('Search by date added to CiviCRM'));

    //redirect if group not available for search criteria
    if (count($groups) == 0) {
      CRM_Core_Error::statusBounce(ts("Atleast one Group must be present for search."),
        CRM_Utils_System::url('civicrm/contact/search/custom/list',
          'reset=1'
        )
      );
    }

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', ['start_date', 'end_date', 'includeGroups', 'excludeGroups']);
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
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
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {

    $this->_includeGroups = $this->_formValues['includeGroups'] ?? [];

    $this->_excludeGroups = $this->_formValues['excludeGroups'] ?? [];

    $this->_allSearch = FALSE;
    $this->_groups = FALSE;

    if (empty($this->_includeGroups) && empty($this->_excludeGroups)) {
      //empty search
      $this->_allSearch = TRUE;
    }

    if (!empty($this->_includeGroups) || !empty($this->_excludeGroups)) {
      //group(s) selected
      $this->_groups = TRUE;
    }

    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
      $groupBy = " GROUP BY contact_a.id";
      $sort = "contact_a.id";
    }
    else {
      $selectClause = "contact_a.id  as contact_id,
                       contact_a.contact_type as contact_type,
                       contact_a.sort_name    as sort_name,
                      d.date_added           as date_added";
      $groupBy = " GROUP BY contact_id ";
    }

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, $groupBy
    );
  }

  /**
   * @return string
   */
  public function from() {
    //define table name
    $datesTable = CRM_Utils_SQL_TempTable::build()->setCategory('dates')->setMemory();
    $this->_datesTable = $datesTable->getName();
    $xgTable = CRM_Utils_SQL_TempTable::build()->setCategory('xg')->setMemory();
    $this->_xgTable = $xgTable->getName();
    $igTable = CRM_Utils_SQL_TempTable::build()->setCategory('ig')->setMemory();
    $this->_igTable = $igTable->getName();

    //grab the contacts added in the date range first
    $datesTable->createWithColumns('id int primary key, date_added date');

    $startDate = !empty($this->_formValues['start_date']) ? $this->_formValues['start_date'] : date('Y-m-d');
    $endDateFix = NULL;
    if (!empty($this->_formValues['end_date'])) {
      // tack 11:59pm on to make search inclusive of the end date
      $endDateFix = "AND date_added <= '{$this->_formValues['end_date']} 23:59:00'";
    }

    $dateRange = "INSERT INTO {$this->_datesTable} ( id, date_added )
          SELECT
              civicrm_contact.id,
              min(civicrm_log.modified_date) AS date_added
          FROM
              civicrm_contact LEFT JOIN civicrm_log
              ON (civicrm_contact.id = civicrm_log.entity_id AND
                  civicrm_log.entity_table = 'civicrm_contact')
          GROUP BY
              civicrm_contact.id
          HAVING
              date_added >= '$startDate 00:00:00'
              $endDateFix";

    CRM_Core_DAO::executeQuery($dateRange);

    // Only include groups in the search query of one or more Include OR Exclude groups has been selected.
    // CRM-6356
    if ($this->_groups) {
      //block for Group search
      $smartGroup = [];
      $group = new CRM_Contact_DAO_Group();
      $group->is_active = 1;
      $group->find();
      while ($group->fetch()) {
        $allGroups[] = $group->id;
        if ($group->saved_search_id) {
          $smartGroup[$group->saved_search_id] = $group->id;
        }
      }
      $includedGroups = implode(',', $allGroups);

      if (!empty($this->_includeGroups)) {
        $iGroups = implode(',', $this->_includeGroups);
      }
      else {
        //if no group selected search for all groups
        $iGroups = $includedGroups;
      }
      if (is_array($this->_excludeGroups)) {
        $xGroups = implode(',', $this->_excludeGroups);
      }
      else {
        $xGroups = 0;
      }

      $xgTable->drop();
      $xgTable->createWithColumns('contact_id int primary key');

      //used only when exclude group is selected
      if ($xGroups != 0) {
        $excludeGroup = "INSERT INTO  {$this->_xgTable} ( contact_id )
                  SELECT  DISTINCT civicrm_group_contact.contact_id
                  FROM civicrm_group_contact, {$this->_datesTable} AS d
                  WHERE
                     d.id = civicrm_group_contact.contact_id AND
                     civicrm_group_contact.status = 'Added' AND
                     civicrm_group_contact.group_id IN( {$xGroups})";

        CRM_Core_DAO::executeQuery($excludeGroup);

        //search for smart group contacts
        foreach ($this->_excludeGroups as $keys => $values) {
          if (in_array($values, $smartGroup)) {
            $ssId = CRM_Utils_Array::key($values, $smartGroup);

            $smartSql = CRM_Contact_BAO_SearchCustom::contactIDSQL(NULL, $ssId);

            $smartSql .= " AND contact_a.id NOT IN (
                              SELECT contact_id FROM civicrm_group_contact
                              WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

            $smartGroupQuery = " INSERT IGNORE INTO {$this->_xgTable}(contact_id) $smartSql";

            CRM_Core_DAO::executeQuery($smartGroupQuery);
          }
        }
      }

      $igTable->drop();
      $igTable->createWithColumns('id int PRIMARY KEY AUTO_INCREMENT,
                  contact_id int,
                  group_names varchar(64)');

      $includeGroup = "INSERT INTO {$this->_igTable} (contact_id, group_names)
                 SELECT      d.id as contact_id, civicrm_group.name as group_name
                 FROM        {$this->_datesTable} AS d
                 INNER JOIN  civicrm_group_contact
                 ON          civicrm_group_contact.contact_id = d.id
                 LEFT JOIN   civicrm_group
                 ON          civicrm_group_contact.group_id = civicrm_group.id";

      //used only when exclude group is selected
      if ($xGroups != 0) {
        $includeGroup .= " LEFT JOIN        {$this->_xgTable}
                                          ON        d.id = {$this->_xgTable}.contact_id";
      }
      $includeGroup .= " WHERE
                                     civicrm_group_contact.status = 'Added'  AND
                                     civicrm_group_contact.group_id IN($iGroups)";

      //used only when exclude group is selected
      if ($xGroups != 0) {
        $includeGroup .= " AND  {$this->_xgTable}.contact_id IS null";
      }

      CRM_Core_DAO::executeQuery($includeGroup);

      //search for smart group contacts
      foreach ($this->_includeGroups as $keys => $values) {
        if (in_array($values, $smartGroup)) {

          $ssId = CRM_Utils_Array::key($values, $smartGroup);

          $smartSql = CRM_Contact_BAO_SearchCustom::contactIDSQL(NULL, $ssId);

          $smartSql .= " AND contact_a.id IN (
                                   SELECT id AS contact_id
                                   FROM {$this->_datesTable} )";

          $smartSql .= " AND contact_a.id NOT IN (
                                   SELECT contact_id FROM civicrm_group_contact
                                   WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";

          //used only when exclude group is selected
          if ($xGroups != 0) {
            $smartSql .= " AND contact_a.id NOT IN (SELECT contact_id FROM  {$this->_xgTable})";
          }

          $smartGroupQuery = " INSERT IGNORE INTO
                        {$this->_igTable}(contact_id)
                        $smartSql";

          CRM_Core_DAO::executeQuery($smartGroupQuery);
          $insertGroupNameQuery = "UPDATE IGNORE {$this->_igTable}
                        SET group_names = (SELECT title FROM civicrm_group
                            WHERE civicrm_group.id = $values)
                        WHERE {$this->_igTable}.contact_id IS NOT NULL
                            AND {$this->_igTable}.group_names IS NULL";
          CRM_Core_DAO::executeQuery($insertGroupNameQuery);
        }
      }
    }
    // end if( $this->_groups ) condition
    $this->buildACLClause('contact_a');
    $from = "FROM civicrm_contact contact_a";

    /* We need to join to this again to get the date_added value */

    $from .= " INNER JOIN {$this->_datesTable} d ON (contact_a.id = d.id) {$this->_aclFrom}";

    // Only include groups in the search query of one or more Include OR Exclude groups has been selected.
    // CRM-6356
    if ($this->_groups) {
      $from .= " INNER JOIN {$this->_igTable} temptable1 ON (contact_a.id = temptable1.contact_id)";
    }

    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $where = '(1)';
    if ($this->_aclWhere) {
      $where .= " AND {$this->_aclWhere} ";
    }
    return $where;
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * @return mixed
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  /**
   * Format saved search fields for this custom group.
   *
   * Note this is a function to facilitate the transition to jcalendar for
   * saved search groups. In time it can be stripped out again.
   *
   * @param array $formValues
   *
   * @return array
   */
  public static function formatSavedSearchFields($formValues) {
    $dateFields = [
      'start_date',
      'end_date',
    ];
    foreach ($formValues as $element => $value) {
      if (in_array($element, $dateFields) && !empty($value)) {
        $formValues[$element] = date('Y-m-d', strtotime($value));
      }
    }
    return $formValues;
  }

}
