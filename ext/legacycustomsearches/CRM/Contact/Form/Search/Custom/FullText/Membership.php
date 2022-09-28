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
class CRM_Contact_Form_Search_Custom_FullText_Membership extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Membership', ts('Memberships'));
  }

  /**
   * Check if search is permitted.
   *
   * @return bool
   */
  public function isActive() {
    return CRM_Core_Component::isEnabled('CiviMember') &&
      CRM_Core_Permission::check('access CiviMember');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'membership_id', $result['files']);
    }
    return $result;
  }

  /**
   * Get membership ids in entity tables.
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
SELECT     distinct cm.id
FROM       civicrm_membership cm
INNER JOIN civicrm_contact c ON cm.contact_id = c.id
WHERE      ({$this->matchText('civicrm_contact c', ['sort_name', 'display_name', 'nick_name'], $queryText)})
";
    $tables = [
      'civicrm_membership' => [
        'id' => 'id',
        'fields' => ['source' => NULL],
      ],
      'file' => [
        'xparent_table' => 'civicrm_membership',
      ],
      'sql' => $contactSQL,
    ];

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Membership' )");
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
( table_name, contact_id, sort_name, membership_id, membership_type, membership_fee, membership_start_date,
membership_end_date, membership_source, membership_status )
   SELECT  'Membership', c.id, c.sort_name, cm.id, cmt.name, cc.total_amount, cm.start_date, cm.end_date, cm.source, cms.name
     FROM  {$fromTable} ct
INNER JOIN civicrm_membership cm ON cm.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cm.contact_id = c.id
LEFT JOIN  civicrm_membership_type cmt ON cmt.id = cm.membership_type_id
LEFT JOIN  civicrm_membership_payment cmp ON cmp.membership_id = cm.id
LEFT JOIN  civicrm_contribution cc ON cc.id = cmp.contribution_id
LEFT JOIN  civicrm_membership_status cms ON cms.id = cm.status_id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
