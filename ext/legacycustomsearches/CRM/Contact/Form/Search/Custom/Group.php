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
class CRM_Contact_Form_Search_Custom_Group extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;

  /**
   * @var \CRM_Utils_SQL_TemTable
   */
  protected $_xGTable = NULL;

  /**
   * @var \CRM_Utils_SQL_TempTable
   */
  protected $_iGTable = NULL;

  /**
   * @var string
   * Table Name for xclude Groups
   */
  protected $_xGTableName = NULL;

  /**
   * @var string
   * Table Name for Inclue Groups
   */
  protected $_iGTableName = NULL;

  /**
   * @var \CRM_Utils_SQL_TempTable
   */
  protected $_xTTable = NULL;

  /**
   * @var \CRM_Utils_SQL_TempTable
   */
  protected $_iTTable = NULL;

  /**
   * @var string
   * Table Name for xclude Groups
   */
  protected $_xTTableName = NULL;

  /**
   * @var string
   * Table Name for Inclue Groups
   */
  protected $_iTTableName = NULL;

  protected $_where = ' (1) ';

  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Include Groups
   * @var array
   */
  protected $_includeGroups;

  /**
   * Exclude Groups
   * @var array
   */
  protected $_excludeGroups;

  /**
   * Include Tags
   * @var array
   */
  protected $_includeTags;

  /**
   * Exclude Tags
   * @var array
   */
  protected $_excludeTags;

  /**
   * All Search
   * @var bool
   */
  protected $_allSearch;

  /**
   * Does this search use groups
   * @var bool
   */
  protected $_groups;

  /**
   * Does this search use tags
   * @var bool
   */
  protected $_tags;

  /**
   * Is this search And or OR search
   * @var string
   */
  protected $_andOr;

  /**
   * Search Columns
   * @var array
   */
  protected $_columns;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = $formValues;
    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Contact Type') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Group Name') => 'gname',
      ts('Tag Name') => 'tname',
    ];

    $this->_includeGroups = $this->_formValues['includeGroups'] ?? [];
    $this->_excludeGroups = $this->_formValues['excludeGroups'] ?? [];
    $this->_includeTags = $this->_formValues['includeTags'] ?? [];
    $this->_excludeTags = $this->_formValues['excludeTags'] ?? [];

    //define variables
    $this->_allSearch = FALSE;
    $this->_groups = FALSE;
    $this->_tags = FALSE;
    $this->_andOr = $this->_formValues['andOr'] ?? NULL;
    //make easy to check conditions for groups and tags are
    //selected or it is empty search
    if (empty($this->_includeGroups) && empty($this->_excludeGroups) &&
      empty($this->_includeTags) && empty($this->_excludeTags)
    ) {
      //empty search
      $this->_allSearch = TRUE;
    }

    $this->_groups = (!empty($this->_includeGroups) || !empty($this->_excludeGroups));

    $this->_tags = (!empty($this->_includeTags) || !empty($this->_excludeTags));
  }

  public function __destruct() {
    // mysql drops the tables when connection is terminated
    // cannot drop tables here, since the search might be used
    // in other parts after the object is destroyed
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    $this->setTitle(ts('Include / Exclude Search'));

    $groups = CRM_Core_PseudoConstant::nestedGroup();

    $tags = CRM_Core_DAO_EntityTag::buildOptions('tag_id', 'get');
    if (count($groups) == 0 || count($tags) == 0) {
      CRM_Core_Session::setStatus(ts("At least one Group and Tag must be present for Custom Group / Tag search."), ts('Missing Group/Tag'));
      $url = CRM_Utils_System::url('civicrm/contact/search/custom/list', 'reset=1');
      CRM_Utils_System::redirect($url);
    }

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

    $andOr = [
      '1' => ts('Show contacts that meet the Groups criteria AND the Tags criteria'),
      '0' => ts('Show contacts that meet the Groups criteria OR  the Tags criteria'),
    ];
    $form->addRadio('andOr', ts('AND/OR'), $andOr, NULL, '<br />', TRUE);

    $form->add('select', 'includeTags',
      ts('Include Tag(s)'),
      $tags,
      FALSE,
      $select2style
    );

    $form->add('select', 'excludeTags',
      ts('Exclude Tag(s)'),
      $tags,
      FALSE,
      $select2style
    );

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', ['includeGroups', 'excludeGroups', 'andOr', 'includeTags', 'excludeTags']);
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param string $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {

    if ($justIDs) {
      $selectClause = 'contact_a.id as contact_id';
    }
    else {
      $selectClause = "contact_a.id as contact_id,
                         contact_a.contact_type as contact_type,
                         contact_a.sort_name    as sort_name";

      //distinguish column according to user selection
      if (($this->_includeGroups && !$this->_includeTags)) {
        unset($this->_columns[ts('Tag Name')]);
        $selectClause .= ", GROUP_CONCAT(DISTINCT group_names ORDER BY group_names ASC ) as gname";
      }
      elseif ($this->_includeTags && (!$this->_includeGroups)) {
        unset($this->_columns[ts('Group Name')]);
        $selectClause .= ", GROUP_CONCAT(DISTINCT tag_names  ORDER BY tag_names ASC ) as tname";
      }
      elseif (!empty($this->_includeTags) && !empty($this->_includeGroups)) {
        $selectClause .= ", GROUP_CONCAT(DISTINCT group_names ORDER BY group_names ASC ) as gname , GROUP_CONCAT(DISTINCT tag_names ORDER BY tag_names ASC ) as tname";
      }
      else {
        unset($this->_columns[ts('Tag Name')]);
        unset($this->_columns[ts('Group Name')]);
      }
    }

    $from = $this->from();

    $where = $this->where($includeContactIDs);

    if (!$justIDs && !$this->_allSearch) {
      $groupBy = ' GROUP BY contact_a.id';
    }
    else {
      // CRM-10850
      // we do this since this if stmt is called by the smart group part of the code
      // adding a groupBy clause and saving it as a smart group messes up the query and
      // bad things happen
      // andie hunt seemed to have rewritten this piece when they worked on this search
      $groupBy = NULL;
    }

    $sql = "SELECT $selectClause $from WHERE  $where $groupBy";

    // Define ORDER BY for query in $sort, with default value
    if (!$justIDs) {
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sort = \CRM_Utils_Type::escape($sort, 'String');
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= ' ORDER BY ' . trim($sort->orderBy());
        }
      }
      else {
        $sql .= ' ORDER BY contact_id ASC';
      }
    }
    else {
      $sql .= ' ORDER BY contact_a.id ASC';
    }

    if ($offset >= 0 && $rowcount > 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    return $sql;
  }

  /**
   * @return string
   * @throws Exception
   */
  public function from() {

    $iGroups = $xGroups = $iTags = $xTags = 0;

    //define table names
    $this->_xGTable = CRM_Utils_SQL_TempTable::build()->setCategory('xggroup');
    $this->_xGTableName = $this->_xGTable->getName();
    $this->_iGTable = CRM_Utils_SQL_TempTable::build()->setCategory('iggroup');
    $this->_iGTableName = $this->_iGTable->getName();

    //block for Group search
    $smartGroup = [];
    if ($this->_groups || $this->_allSearch) {
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

      //CRM-15049 - Include child group ids.
      $childGroupIds = CRM_Contact_BAO_Group::getChildGroupIds($this->_includeGroups);
      if (count($childGroupIds) > 0) {
        $this->_includeGroups = array_merge($this->_includeGroups, $childGroupIds);
      }

      if (!empty($this->_includeGroups)) {
        $iGroups = implode(',', $this->_includeGroups);
      }
      else {
        //if no group selected search for all groups
        $iGroups = NULL;
      }
      if (is_array($this->_excludeGroups) && !empty($this->_excludeGroups)) {
        $xGroups = implode(',', $this->_excludeGroups);
      }
      else {
        $xGroups = 0;
      }

      $this->_xGTable->createWithColumns("contact_id int primary key");
      //used only when exclude group is selected
      if ($xGroups != 0) {
        $excludeGroup = "INSERT INTO  {$this->_xGTableName} ( contact_id )
                  SELECT  DISTINCT civicrm_group_contact.contact_id
                  FROM civicrm_group_contact, civicrm_contact
                  WHERE
                     civicrm_contact.id = civicrm_group_contact.contact_id AND
                     civicrm_group_contact.status = 'Added' AND
                     civicrm_group_contact.group_id IN( {$xGroups})";

        CRM_Core_DAO::executeQuery($excludeGroup);

        //search for smart group contacts
        foreach ($this->_excludeGroups as $keys => $values) {
          if (in_array($values, $smartGroup)) {
            $ssGroup = new CRM_Contact_DAO_Group();
            $ssGroup->id = $values;
            if (!$ssGroup->find(TRUE)) {
              CRM_Core_Error::statusBounce(ts('Smart group sepecifed in exclude groups is not found in the database'));
            }
            CRM_Contact_BAO_GroupContactCache::load($ssGroup);

            $smartSql = "
SELECT gcc.contact_id
FROM   civicrm_group_contact_cache gcc
WHERE  gcc.group_id = {$ssGroup->id}
";
            $smartGroupQuery = " INSERT IGNORE INTO {$this->_xGTableName}(contact_id) $smartSql";
            CRM_Core_DAO::executeQuery($smartGroupQuery);
          }
        }
      }

      $this->_iGTable->createWithColumns("id int PRIMARY KEY AUTO_INCREMENT, contact_id int, group_names varchar(255)");

      if ($iGroups) {
        $includeGroup = "INSERT INTO {$this->_iGTableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, civicrm_group.title as group_name
                 FROM                civicrm_contact
                    INNER JOIN       civicrm_group_contact
                            ON       civicrm_group_contact.contact_id = civicrm_contact.id
                    LEFT JOIN        civicrm_group
                            ON       civicrm_group_contact.group_id = civicrm_group.id";
      }
      else {
        $includeGroup = "INSERT INTO {$this->_iGTableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, ''
                 FROM                civicrm_contact";
      }
      //used only when exclude group is selected
      if ($xGroups != 0) {
        $includeGroup .= " LEFT JOIN        {$this->_xGTableName}
                                          ON       civicrm_contact.id = {$this->_xGTableName}.contact_id";
      }

      if ($iGroups) {
        $includeGroup .= " WHERE
                                     civicrm_group_contact.status = 'Added'  AND
                                     civicrm_group_contact.group_id IN($iGroups)";
      }
      else {
        $includeGroup .= " WHERE ( 1 ) ";
      }

      //used only when exclude group is selected
      if ($xGroups != 0) {
        $includeGroup .= " AND  {$this->_xGTableName}.contact_id IS null";
      }

      CRM_Core_DAO::executeQuery($includeGroup);

      //search for smart group contacts

      foreach ($this->_includeGroups as $keys => $values) {
        if (in_array($values, $smartGroup)) {
          $ssGroup = new CRM_Contact_DAO_Group();
          $ssGroup->id = $values;
          if (!$ssGroup->find(TRUE)) {
            CRM_Core_Error::statusBounce(ts('Smart group sepecifed in include groups is not found in the database'));
          }
          CRM_Contact_BAO_GroupContactCache::load($ssGroup);

          $smartSql = "
SELECT gcc.contact_id
FROM   civicrm_group_contact_cache gcc
WHERE  gcc.group_id = {$ssGroup->id}
";

          //used only when exclude group is selected
          if ($xGroups != 0) {
            $smartSql .= " AND gcc.contact_id NOT IN (SELECT contact_id FROM  {$this->_xGTableName})";
          }

          $smartGroupQuery = " INSERT IGNORE INTO {$this->_iGTableName}(contact_id)
                                     $smartSql";

          CRM_Core_DAO::executeQuery($smartGroupQuery);
          $insertGroupNameQuery = "UPDATE IGNORE {$this->_iGTableName}
                                         SET group_names = (SELECT title FROM civicrm_group
                                                            WHERE civicrm_group.id = $values)
                                         WHERE {$this->_iGTableName}.contact_id IS NOT NULL
                                         AND {$this->_iGTableName}.group_names IS NULL";
          CRM_Core_DAO::executeQuery($insertGroupNameQuery);
        }
      }
    }
    //group contact search end here;

    //block for Tags search
    if ($this->_tags || $this->_allSearch) {
      //find all tags
      $tag = new CRM_Core_DAO_Tag();
      $tag->is_active = 1;
      $tag->find();
      while ($tag->fetch()) {
        $allTags[] = $tag->id;
      }
      $includedTags = implode(',', $allTags);

      if (!empty($this->_includeTags)) {
        $iTags = implode(',', $this->_includeTags);
      }
      else {
        //if no group selected search for all groups
        $iTags = NULL;
      }
      if (is_array($this->_excludeTags) && !empty($this->_excludeTags)) {
        $xTags = implode(',', $this->_excludeTags);
      }
      else {
        $xTags = 0;
      }

      $this->_xTTable = CRM_Utils_SQL_TempTable::build()->setCategory('xtgroup');
      $this->_xTTableName = $this->_xTTable->getName();
      $this->_iTTable = CRM_Utils_SQL_TempTable::build()->setCategory('itgroup');
      $this->_iTTableName = $this->_iTTable->getName();

      $this->_xTTable->createWithColumns("contact_id int primary key");

      //used only when exclude tag is selected
      if ($xTags != 0) {
        $excludeTag = "INSERT INTO  {$this->_xTTableName} ( contact_id )
                  SELECT  DISTINCT civicrm_entity_tag.entity_id
                  FROM civicrm_entity_tag, civicrm_contact
                  WHERE
                     civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                     civicrm_contact.id = civicrm_entity_tag.entity_id AND
                     civicrm_entity_tag.tag_id IN( {$xTags})";

        CRM_Core_DAO::executeQuery($excludeTag);
      }

      $this->_iTTable->createWithColumns("id int PRIMARY KEY AUTO_INCREMENT, contact_id int, tag_names varchar(64)");

      if ($iTags) {
        $includeTag = "INSERT INTO {$this->_iTTableName} (contact_id, tag_names)
                 SELECT              civicrm_contact.id as contact_id, civicrm_tag.name as tag_name
                 FROM                civicrm_contact
                    INNER JOIN       civicrm_entity_tag
                            ON       ( civicrm_entity_tag.entity_table = 'civicrm_contact' AND
                                       civicrm_entity_tag.entity_id = civicrm_contact.id )
                    LEFT JOIN        civicrm_tag
                            ON       civicrm_entity_tag.tag_id = civicrm_tag.id";
      }
      else {
        $includeTag = "INSERT INTO {$this->_iTTableName} (contact_id, tag_names)
                 SELECT              civicrm_contact.id as contact_id, ''
                 FROM                civicrm_contact";
      }

      //used only when exclude tag is selected
      if ($xTags != 0) {
        $includeTag .= " LEFT JOIN        {$this->_xTTableName}
                                       ON       civicrm_contact.id = {$this->_xTTableName}.contact_id";
      }
      if ($iTags) {
        $includeTag .= " WHERE   civicrm_entity_tag.tag_id IN($iTags)";
      }
      else {
        $includeTag .= " WHERE ( 1 ) ";
      }

      //used only when exclude tag is selected
      if ($xTags != 0) {
        $includeTag .= " AND  {$this->_xTTableName}.contact_id IS null";
      }

      CRM_Core_DAO::executeQuery($includeTag);
    }

    $from = " FROM civicrm_contact contact_a";

    /*
     * CRM-10850 / CRM-10848
     * If we use include / exclude groups as smart groups for ACL's having the below causes
     * a cycle which messes things up. Hence commenting out for now
     * $this->buildACLClause('contact_a');
     */

    /*
     * check the situation and set booleans
     */
    $Ig = ($iGroups != 0);
    $It = ($iTags != 0);
    $Xg = ($xGroups != 0);
    $Xt = ($xTags != 0);

    //PICK UP FROM HERE
    if (!$this->_groups && !$this->_tags) {
      $this->_andOr = 1;
    }

    /*
     * Set from statement depending on array sel
     */
    $whereitems = [];
    $tableNames = [
      'Ig' => $this->_iGTableName,
      'Xg' => $this->_xGTableName,
      'It' => $this->_iTTableName,
      'Xt' => $this->_xTTableName,
    ];
    foreach (['Ig', 'It'] as $inc) {
      if ($this->_andOr == 1) {
        if ($$inc) {
          $from .= " INNER JOIN {$tableNames[$inc]} temptable$inc ON (contact_a.id = temptable$inc.contact_id)";
        }
      }
      else {
        if ($$inc) {
          $from .= " LEFT JOIN {$tableNames[$inc]} temptable$inc ON (contact_a.id = temptable$inc.contact_id)";
        }
      }
      if ($$inc) {
        $whereitems[] = "temptable$inc.contact_id IS NOT NULL";
      }
    }
    $this->_where = $whereitems ? "(" . implode(' OR ', $whereitems) . ')' : '(1)';
    foreach (['Xg', 'Xt'] as $exc) {
      if ($$exc) {
        $from .= " LEFT JOIN {$tableNames[$exc]} temptable$exc ON (contact_a.id = temptable$exc.contact_id)";
        $this->_where .= " AND temptable$exc.contact_id IS NULL";
      }
    }

    $from .= " LEFT JOIN civicrm_email ON ( contact_a.id = civicrm_email.contact_id AND ( civicrm_email.is_primary = 1 OR civicrm_email.is_bulkmail = 1 ) ) {$this->_aclFrom}";

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    // also exclude all contacts that are deleted
    // CRM-11627
    $this->_where .= " AND (contact_a.is_deleted != 1) ";

    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    if ($includeContactIDs) {
      $contactIDs = [];

      foreach ($this->_formValues as $id => $value) {
        if ($value &&
          substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ( $contactIDs )";
      }
      $where = "{$this->_where} AND " . implode(' AND ', $clauses);
    }
    else {
      $where = $this->_where;
    }

    return $where;
  }

  /*
   * Functions below generally don't need to be modified
   */

  /**
   * @inheritDoc
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param string $sort
   * @param bool $returnSQL
   *
   * @return string
   * @throws \Exception
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = FALSE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * Define columns.
   *
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * Get summary.
   *
   * @return NULL
   */
  public function summary() {
    return NULL;
  }

  /**
   * Get template file.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Build ACL clause.
   *
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
