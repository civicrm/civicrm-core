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
class CRM_Contact_Form_Search_Custom_FullText_Contribution extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Contribution', ts('Contributions'));
  }

  /**
   * Check if search is permitted.
   *
   * @return bool
   */
  public function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviContribute', $config->enableComponents) &&
    CRM_Core_Permission::check('access CiviContribute');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'contribution_id', $result['files']);
    }
    return $result;
  }

  /**
   * Get contribution ids in entity tables.
   *
   * @param string $queryText
   * @param string $entityIDTableName
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = [];
    $contactSQL[] = "
SELECT     distinct cc.id
FROM       civicrm_contribution cc
INNER JOIN civicrm_contact c ON cc.contact_id = c.id
WHERE      ({$this->matchText('civicrm_contact c', ['sort_name', 'display_name', 'nick_name'], $queryText)})
";
    $tables = [
      'civicrm_contribution' => [
        'id' => 'id',
        'fields' => [
          'source' => NULL,
          'amount_level' => NULL,
          'trxn_Id' => NULL,
          'invoice_id' => NULL,
          // Odd: This is really a VARCHAR, so why are we searching like an INT?
          'check_number' => 'Int',
          'total_amount' => 'Int',
        ],
      ],
      'file' => [
        'xparent_table' => 'civicrm_contribution',
      ],
      'sql' => $contactSQL,
      'civicrm_note' => [
        'id' => 'entity_id',
        'entity_table' => 'civicrm_contribution',
        'fields' => [
          'subject' => NULL,
          'note' => NULL,
        ],
      ],
    ];

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Contribution' )");
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
( table_name, contact_id, sort_name, contribution_id, financial_type, contribution_page, contribution_receive_date,
  contribution_total_amount, contribution_trxn_Id, contribution_source, contribution_status, contribution_check_number )
   SELECT  'Contribution', c.id, c.sort_name, cc.id, cct.name, ccp.title, cc.receive_date,
           cc.total_amount, cc.trxn_id, cc.source, contribution_status.label, cc.check_number
     FROM  {$fromTable} ct
INNER JOIN civicrm_contribution cc ON cc.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cc.contact_id = c.id
LEFT JOIN  civicrm_financial_type cct ON cct.id = cc.financial_type_id
LEFT JOIN  civicrm_contribution_page ccp ON ccp.id = cc.contribution_page_id
LEFT JOIN  civicrm_option_group option_group_contributionStatus ON option_group_contributionStatus.name = 'contribution_status'
LEFT JOIN  civicrm_option_value contribution_status ON
( contribution_status.option_group_id = option_group_contributionStatus.id AND contribution_status.value = cc.contribution_status_id )
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
