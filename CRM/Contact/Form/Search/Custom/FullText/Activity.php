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
class CRM_Contact_Form_Search_Custom_FullText_Activity extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  function __construct() {
    parent::__construct('Activity', ts('Activities'));
  }

  function isActive() {
    return CRM_Core_Permission::check('view all activities');
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $count = $this->fillActivityIDs($queryText, $entityIDTableName, $queryLimit);
    $this->moveActivityIDs($entityIDTableName, $toTable, $detailLimit);
    return $count;
  }

  /**
   * @param string $queryText
   * @return int the total number of matches
   */
  function fillActivityIDs($queryText, $entityIDTableName, $limit) {
    $contactSQL = array();

    $contactSQL[] = "
SELECT     distinct ca.id
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_contact cat ON cat.activity_id = ca.id
INNER JOIN civicrm_contact c ON cat.contact_id = c.id
LEFT  JOIN civicrm_email e ON cat.contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id )
WHERE      ( (c.sort_name LIKE {$this->toSqlWildCard($queryText)} OR c.display_name LIKE {$this->toSqlWildCard($queryText)}) OR
             ( e.email LIKE {$this->toSqlWildCard($queryText)}    AND
               ca.activity_type_id = ov.value AND
               ov.name IN ('Inbound Email', 'Email') ) )
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
AND        t.name LIKE {$this->toSqlWildCard($queryText)}
AND        (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
GROUP BY   et.entity_id
";

    $contactSQL[] = "
SELECT distinct ca.id
FROM   civicrm_activity ca
WHERE  (ca.subject LIKE  {$this->toSqlWildCard($queryText)} OR ca.details LIKE  {$this->toSqlWildCard($queryText)})
AND    (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
";

    $final = array();

    $tables = array(
      'civicrm_activity' => array('fields' => array()),
      'sql' => $contactSQL,
      'final' => $final,
    );

    $this->fillCustomInfo($tables, "( 'Activity' )");
    return $this->runQueries($queryText, $tables, $entityIDTableName, $limit);
  }

  public function moveActivityIDs($fromTable, $toTable, $limit) {
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
GROUP BY ca.id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}