<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Contact_Form_Search_Custom_FullText_Activity extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Activity', ts('Activities'));
  }

  /**
   * Is search active for this user.
   *
   * @return bool
   */
  public function isActive() {
    return CRM_Core_Permission::check('view all activities');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'activity_id', $result['files']);
    }
    return $result;
  }

  /**
   * @param string $queryText
   * @param string $entityIDTableName
   *
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = array();

    $contactSQL[] = "
SELECT     distinct ca.id
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_contact cat ON cat.activity_id = ca.id
INNER JOIN civicrm_contact c ON cat.contact_id = c.id
LEFT  JOIN civicrm_email e ON cat.contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id )
WHERE      (
             ({$this->matchText('civicrm_contact c', array('sort_name', 'display_name', 'nick_name'), $queryText)})
             OR
             ({$this->matchText('civicrm_email e', 'email', $queryText)} AND ca.activity_type_id = ov.value AND ov.name IN ('Inbound Email', 'Email') )
           )
AND        (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
AND        (c.is_deleted = 0 OR c.is_deleted IS NULL)
";

    $contactSQL[] = "
SELECT     et.entity_id
FROM       civicrm_entity_tag et
INNER JOIN civicrm_tag t ON et.tag_id = t.id
INNER JOIN civicrm_activity ca ON et.entity_id = ca.id
WHERE      et.entity_table = 'civicrm_activity'
AND        et.tag_id       = t.id
AND        ({$this->matchText('civicrm_tag t', 'name', $queryText)})
AND        (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
GROUP BY   et.entity_id
";

    $contactSQL[] = "
SELECT distinct ca.id
FROM   civicrm_activity ca
WHERE  ({$this->matchText('civicrm_activity ca', array('subject', 'details'), $queryText)})
AND    (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
";

    $final = array();

    $tables = array(
      'civicrm_activity' => array('fields' => array()),
      'file' => array(
        'xparent_table' => 'civicrm_activity',
      ),
      'sql' => $contactSQL,
      'final' => $final,
    );

    $this->fillCustomInfo($tables, "( 'Activity' )");
    return $tables;;
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
( table_name, activity_id, subject, details, contact_id, sort_name, record_type,
  activity_type_id, case_id, client_id )
SELECT    'Activity', ca.id, substr(ca.subject, 1, 50), substr(ca.details, 1, 250),
           c1.id, c1.sort_name, cac.record_type_id,
           ca.activity_type_id,
           cca.case_id,
           ccc.contact_id as client_id
FROM       {$fromTable} eid
INNER JOIN civicrm_activity ca ON ca.id = eid.entity_id
INNER JOIN  civicrm_activity_contact cac ON cac.activity_id = ca.id
INNER JOIN  civicrm_contact c1 ON cac.contact_id = c1.id
LEFT JOIN  civicrm_case_activity cca ON cca.activity_id = ca.id
LEFT JOIN  civicrm_case_contact ccc ON ccc.case_id = cca.case_id
WHERE (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
