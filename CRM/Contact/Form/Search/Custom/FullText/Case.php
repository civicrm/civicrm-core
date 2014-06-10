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
class CRM_Contact_Form_Search_Custom_FullText_Case extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  function __construct() {
    parent::__construct('Case', ts('Cases'));
  }

  function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviCase', $config->enableComponents);
  }

  /**
   * {@inheritdoc}
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
   * @param string $queryText
   * @param string $entityIDTableName
   * @return array list tables/queries (for runQueries)
   */
  function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = array();

    $contactSQL[] = "
SELECT    distinct cc.id
FROM      civicrm_case cc
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     ({$this->matchText('civicrm_contact c', array('sort_name', 'display_name', 'nick_name'), $queryText)})
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

    $tables = array(
      'civicrm_case' => array('fields' => array()),
      'file' => array(
        'xparent_table' => 'civicrm_case',
      ),
      'sql' => $contactSQL,
    );

    return $tables;
  }

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