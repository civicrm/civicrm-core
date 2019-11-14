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

    $contactSQL = [];
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
    $final = [];
    $final[] = "DELETE FROM {$entityIDTableName} WHERE entity_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)";

    $tables = [
      'civicrm_contact' => [
        'id' => 'id',
        'fields' => [
          'sort_name' => NULL,
          'nick_name' => NULL,
          'display_name' => NULL,
        ],
      ],
      'civicrm_address' => [
        'id' => 'contact_id',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
        ],
      ],
      'civicrm_email' => [
        'id' => 'contact_id',
        'fields' => ['email' => NULL],
      ],
      'civicrm_phone' => [
        'id' => 'contact_id',
        'fields' => ['phone' => NULL],
      ],
      'civicrm_note' => [
        'id' => 'entity_id',
        'entity_table' => 'civicrm_contact',
        'fields' => [
          'subject' => NULL,
          'note' => NULL,
        ],
      ],
      'file' => [
        'xparent_table' => 'civicrm_contact',
      ],
      'sql' => $contactSQL,
      'final' => $final,
    ];

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
