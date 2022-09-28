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
class CRM_Contact_Form_Search_Custom_FullText_Case extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Case', ts('Cases'));
  }

  /**
   * Is CiviCase active?
   *
   * @return bool
   */
  public function isActive() {
    return CRM_Core_Component::isEnabled('CiviCase');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'case_id', $result['files']);
    }
    return $result;
  }

  /**
   * Prepare queries.
   *
   * @param string $queryText
   * @param string $entityIDTableName
   *
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = [];

    $contactSQL[] = "
SELECT    distinct cc.id
FROM      civicrm_case cc
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     ({$this->matchText('civicrm_contact c', ['sort_name', 'display_name', 'nick_name'], $queryText)})
          AND (cc.is_deleted = 0 OR cc.is_deleted IS NULL)
";

    if (is_numeric($queryText)) {
      $contactSQL[] = "
SELECT    distinct cc.id
FROM      civicrm_case cc
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     cc.id = {$queryText}
          AND (cc.is_deleted = 0 OR cc.is_deleted IS NULL)
";
    }

    $contactSQL[] = "
SELECT     et.entity_id
FROM       civicrm_entity_tag et
INNER JOIN civicrm_tag t ON et.tag_id = t.id
WHERE      et.entity_table = 'civicrm_case'
AND        et.tag_id       = t.id
AND        ({$this->matchText('civicrm_tag t', 'name', $queryText)})
GROUP BY   et.entity_id
";

    $tables = [
      'civicrm_case' => ['fields' => []],
      'file' => [
        'xparent_table' => 'civicrm_case',
      ],
      'sql' => $contactSQL,
    ];

    return $tables;
  }

  /**
   * Move IDs.
   *
   * @param string $fromTable
   * @param string $toTable
   * @param int $limit
   */
  public function moveIDs($fromTable, $toTable, $limit) {
    $sql = "
INSERT INTO {$toTable}
( table_name, contact_id, sort_name, case_id, case_start_date, case_end_date, case_is_deleted )
SELECT 'Case', c.id, c.sort_name, cc.id, DATE(cc.start_date), DATE(cc.end_date), cc.is_deleted
FROM       {$fromTable} ct
INNER JOIN civicrm_case cc ON cc.id = ct.entity_id
LEFT JOIN  civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN  civicrm_contact c ON ccc.contact_id = c.id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
