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
class CRM_Contact_Form_Search_Custom_FullText_Contact extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Contact', ts('Contacts'));
  }

  /**
   * Check if search is permitted.
   *
   * @return bool
   */
  public function isActive() {
    return CRM_Core_Permission::check('view all contacts');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'contact_id', $result['files']);
    }
    return $result;
  }

  /**
   * @param string $queryText
   * @param string $entityIDTableName
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = array();
    $contactSQL[] = "
SELECT     et.entity_id
FROM       civicrm_entity_tag et
INNER JOIN civicrm_tag t ON et.tag_id = t.id
WHERE      et.entity_table = 'civicrm_contact'
AND        et.tag_id       = t.id
AND        ({$this->matchText('civicrm_tag t', 'name', $queryText)})
GROUP BY   et.entity_id
";

    // lets delete all the deceased contacts from the entityID box
    // this allows us to keep numbers in sync
    // when we have acl contacts, the situation gets even more murky
    $final = array();
    $final[] = "DELETE FROM {$entityIDTableName} WHERE entity_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)";

    $tables = array(
      'civicrm_contact' => array(
        'id' => 'id',
        'fields' => array(
          'sort_name' => NULL,
          'nick_name' => NULL,
          'display_name' => NULL,
        ),
      ),
      'civicrm_address' => array(
        'id' => 'contact_id',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
        ),
      ),
      'civicrm_email' => array(
        'id' => 'contact_id',
        'fields' => array('email' => NULL),
      ),
      'civicrm_phone' => array(
        'id' => 'contact_id',
        'fields' => array('phone' => NULL),
      ),
      'civicrm_note' => array(
        'id' => 'entity_id',
        'entity_table' => 'civicrm_contact',
        'fields' => array(
          'subject' => NULL,
          'note' => NULL,
        ),
      ),
      'file' => array(
        'xparent_table' => 'civicrm_contact',
      ),
      'sql' => $contactSQL,
      'final' => $final,
    );

    // get the custom data info
    $this->fillCustomInfo($tables,
      "( 'Contact', 'Individual', 'Organization', 'Household' )"
    );

    return $tables;
  }

  /**
   * Move IDs.
   *
   * @param $fromTable
   * @param $toTable
   * @param $limit
   */
  public function moveIDs($fromTable, $toTable, $limit) {
    $sql = "
INSERT INTO {$toTable}
( id, contact_id, sort_name, display_name, table_name )
SELECT     c.id, ct.entity_id, c.sort_name, c.display_name, 'Contact'
  FROM     {$fromTable} ct
INNER JOIN civicrm_contact c ON ct.entity_id = c.id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
